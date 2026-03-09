<?php
session_start();
require '../db.php';
header('Content-Type: application/json');

// รับค่าจาก JSON Body ที่ส่งมาจากหน้าเว็บ
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { 
    echo json_encode(['status' => 'error', 'message' => 'Invalid input data']); 
    exit; 
}

$device_id = intval($input['device_id']);
$ip = $input['ip'];
$port = intval($input['port']);
$slave_id = intval($input['slave']);
$fc = intval($input['fc']);
$address = intval($input['address']);
$quantity = intval($input['quantity']);

// ดึงข้อมูลอุปกรณ์
$device = $conn->query("SELECT * FROM devices WHERE id = $device_id")->fetch_assoc();
// ดึงข้อมูล Admin เพื่อใช้ส่ง Telegram
$user = $conn->query("SELECT * FROM users LIMIT 1")->fetch_assoc();

// ตรวจสอบสวิตช์อนุญาตรับค่า (ถ้าเป็น 0 จะไม่อ่านข้อมูล)
if ($device['is_active'] == 0) {
    echo json_encode(['status' => 'error', 'message' => 'อุปกรณ์นี้ถูกระงับการรับข้อมูลชั่วคราว']);
    exit;
}

$mode = (!empty($device['mode']) && strtolower($device['mode']) === 'mock') ? 'mock' : 'real';
$registers = [];
$byte_count = 0;

// ==========================================
// 🔀 แยกการทำงาน: Mock (จำลอง) VS Real (ของจริง)
// ==========================================
if ($mode === 'mock') {
    // 🟠 โหมดจำลองข้อมูล (Mock Data)
    for ($i = 0; $i < $quantity; $i++) {
        // ให้โอกาส 5% ที่ระบบจะสุ่มค่าให้ต่ำกว่าค่า Min หรือสูงกว่าค่า Max เพื่อจำลองการเกิด Alert
        if (rand(1, 100) > 95) { 
            if ($device['alert_min'] > 0) {
                $registers[] = $device['alert_min'] - 10; // ต่ำกว่า Min
            } else if ($device['alert_max'] > 0) {
                $registers[] = $device['alert_max'] + 10; // สูงกว่า Max
            } else {
                $registers[] = rand(100, 300); // ถ้าไม่ได้ตั้ง Alert เลย
            }
        } else { 
            // ค่าปกติ
            if ($device['alert_min'] > 0 && $device['alert_max'] > 0) {
                $registers[] = rand(intval($device['alert_min']) + 5, intval($device['alert_max']) - 5);
            } else {
                $registers[] = rand(218, 230); // ค่าแรงดันไฟทั่วไป
            }
        }
    }
    
    $byte_count = $quantity * 2;
    $status = 'success';
    usleep(300000); 

} else {
    // 🟢 โหมดดึงข้อมูลจากฮาร์ดแวร์จริง (Real Hardware Modbus TCP)
    $timeout = 2; 
    $trans_id = rand(1, 65000);
    $packet = pack("nnnCCnn", $trans_id, 0, 6, $slave_id, $fc, $address, $quantity);
    
    $socket = @fsockopen($ip, $port, $errno, $errstr, $timeout);
    
    if (!$socket) { 
        echo json_encode(["status" => "error", "message" => "Connection failed: $errstr"]); 
        exit; 
    }
    
    stream_set_timeout($socket, $timeout);
    fwrite($socket, $packet);
    $response = fread($socket, 2048);
    fclose($socket);
    
    if (strlen($response) < 9) { 
        echo json_encode(["status" => "error", "message" => "No Response: ไม่ได้รับข้อมูลตอบกลับ"]); 
        exit; 
    }
    
    $header = unpack("nTrans/nProto/nLen/CUnit/CFunction/CByteCount", substr($response, 0, 9));
    
    if ($header['Function'] > 0x80) { 
        $exception_code = unpack("C", substr($response, 8, 1))[1];
        echo json_encode(["status" => "error", "message" => "Modbus Exception Code: 0x".dechex($exception_code)]); 
        exit; 
    }
    
    $data_payload = substr($response, 9, $header['ByteCount']);
    $registers = array_values(unpack("n*", $data_payload));
    $byte_count = $header['ByteCount'];
    $status = 'success';
}

// ⭐️ อัปเดตเวลาออนไลน์ล่าสุด (Last Seen) ทันทีที่การดึงค่าสำเร็จ
$conn->query("UPDATE devices SET last_seen = CURRENT_TIMESTAMP WHERE id = $device_id");

// ==========================================
// 🚨 ระบบแจ้งเตือนตามเกณฑ์ (Dynamic Alert System)
// ==========================================
if (isset($registers[0])) {
    $val = $registers[0]; // ดึงค่าแรกมาเช็ค
    $alert_min = floatval($device['alert_min']);
    $alert_max = floatval($device['alert_max']);
    
    $is_alert = false;
    $alert_type = "";
    
    // ตรวจสอบเงื่อนไข (ทำงานก็ต่อเมื่อมีการตั้งค่า Min/Max มากกว่า 0)
    if ($alert_min > 0 && $val < $alert_min) { 
        $is_alert = true; 
        $alert_type = "⚠️ ต่ำกว่าเกณฑ์ที่กำหนด (Under Min Threshold)"; 
    }
    if ($alert_max > 0 && $val > $alert_max) { 
        $is_alert = true; 
        $alert_type = "🔥 สูงกว่าเกณฑ์ที่กำหนด (Over Max Threshold)"; 
    }
    
    if ($is_alert) {
        
        // 1. บันทึกข้อมูลลงฐานข้อมูล (เพื่อไปโชว์ในหน้า Dashboard)
        $log_msg = "ค่าที่อ่านได้ ($val) $alert_type";
        $conn->query("INSERT INTO alert_logs (device_id, message) VALUES ($device_id, '$log_msg')");

        // 2. แจ้งเตือนผ่าน Telegram
        $token = $user['telegram_token'];
        $chat_id = $user['telegram_chat_id'];
        
        if (!empty($token) && !empty($chat_id)) {
            $last_alert = isset($_SESSION['last_alert'][$device_id]) ? $_SESSION['last_alert'][$device_id] : 0;
            
            // ป้องกันการสแปม (จำกัดการส่ง 1 ครั้ง ต่อ 60 วินาที)
            if (time() - $last_alert > 60) {
                
                $msg = "🚨 <b>[BMS Alert] พบค่าผิดปกติ!</b>\n";
                $msg .= "--------------------------------------\n";
                $msg .= "📟 <b>อุปกรณ์:</b> " . htmlspecialchars($device['name']) . "\n";
                $msg .= "📍 <b>สถานที่:</b> " . htmlspecialchars($device['location']) . "\n";
                $msg .= "⚡ <b>ค่าที่อ่านได้:</b> " . $val . "\n";
                $msg .= "🎯 <b>สถานะ:</b> " . $alert_type . "\n";
                $msg .= "🕒 <b>เวลา:</b> " . date('Y-m-d H:i:s');
                
                $url = "https://api.telegram.org/bot$token/sendMessage";
                $post_params = [
                    'chat_id' => $chat_id, 
                    'text' => $msg, 
                    'parse_mode' => 'HTML'
                ];
                
                $options = [ 
                    'http' => [ 
                        'header' => "Content-type: application/x-www-form-urlencoded\r\n", 
                        'method' => 'POST', 
                        'content' => http_build_query($post_params) 
                    ] 
                ];
                $context = stream_context_create($options);
                @file_get_contents($url, false, $context);
                
                $_SESSION['last_alert'][$device_id] = time();
            }
        }
    }
}

// ส่ง JSON สรุปการอ่านค่ากลับไปที่หน้าเว็บ
echo json_encode([
    "status" => $status, 
    "byte_count" => $byte_count, 
    "mode" => $mode, 
    "registers" => $registers
]);
?>