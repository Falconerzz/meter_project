<?php
session_start();
require '../db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$id = isset($data['id']) ? intval($data['id']) : 0;

if($id > 0) {
    // ⭐️ ดึงข้อมูลเดิมออกมา เพื่อเอามาเทียบว่าสถานะเปลี่ยนไปจากเดิมหรือไม่
    $stmt = $conn->query("SELECT name, location, ip_address, port, mode, status as old_status FROM devices WHERE id = $id");
    
    if($stmt->num_rows > 0) {
        $device = $stmt->fetch_assoc();
        $old_status = $device['old_status']; // สถานะล่าสุดในระบบ
        
        // ตรวจสอบสถานะการเชื่อมต่อปัจจุบัน (Live Status)
        if (strtolower($device['mode']) === 'mock') {
            $live_status = 'Online';
        } else {
            $ip = $device['ip_address'];
            $port = intval($device['port']);
            $timeout = 1; // 1 วินาที
            
            $socket = @fsockopen($ip, $port, $errno, $errstr, $timeout);
            
            if ($socket) {
                $live_status = 'Online';
                fclose($socket);
            } else {
                $live_status = 'Offline';
            }
        }

        // ==========================================
        // 🚨 ตรวจจับการเปลี่ยนแปลง (State Change Detection)
        // ==========================================
        // ถ้าสถานะปัจจุบัน ไม่ตรงกับสถานะเดิม (แปลว่ามีการหลุด หรือ กลับมาเชื่อมต่อ)
        if ($old_status !== null && $old_status !== '' && $old_status !== $live_status) {
            
            // 1. บันทึกลงตาราง Alert (เพื่อโชว์ในหน้า Dashboard)
            $log_msg = ($live_status === 'Online') ? "สัญญาณกลับมาออนไลน์ (Back Online)" : "อุปกรณ์ขาดการติดต่อ (Offline)";
            $conn->query("INSERT INTO alert_logs (device_id, message) VALUES ($id, '$log_msg')");

            // 2. เตรียมข้อมูลสำหรับส่ง Telegram
            $user = $conn->query("SELECT * FROM users LIMIT 1")->fetch_assoc();
            $token = $user['telegram_token'];
            $chat_id = $user['telegram_chat_id'];
            
            if (!empty($token) && !empty($chat_id)) {
                
                // แยกข้อความตามสถานะ
                if ($live_status === 'Online') {
                    $msg = "✅ <b>[BMS Network] อุปกรณ์กลับมาออนไลน์!</b>\n";
                    $status_text = "🟢 กลับมาออนไลน์ (Online)";
                } else {
                    $msg = "🔴 <b>[BMS Network] อุปกรณ์ขาดการติดต่อ!</b>\n";
                    $status_text = "❌ ออฟไลน์ (Offline)";
                }
                
                $msg .= "--------------------------------------\n";
                $msg .= "📟 <b>อุปกรณ์:</b> " . htmlspecialchars($device['name']) . "\n";
                $msg .= "📍 <b>สถานที่:</b> " . htmlspecialchars($device['location']) . "\n";
                $msg .= "🔌 <b>สถานะ:</b> " . $status_text . "\n";
                $msg .= "🕒 <b>เวลา:</b> " . date('Y-m-d H:i:s');
                
                // ยิง API แจ้งเตือนเข้า Telegram
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
            }
        }

        // ==========================================
        // อัปเดตสถานะเครือข่ายลงฐานข้อมูล
        // ==========================================
        if ($live_status === 'Online') {
            $conn->query("UPDATE devices SET status = '$live_status', last_seen = CURRENT_TIMESTAMP WHERE id = $id");
        } else {
            $conn->query("UPDATE devices SET status = '$live_status' WHERE id = $id");
        }

        echo json_encode(['status' => 'success', 'id' => $id, 'live_status' => $live_status]);
        exit;
    }
}
echo json_encode(['status' => 'error', 'message' => 'Invalid Device ID']);
?>