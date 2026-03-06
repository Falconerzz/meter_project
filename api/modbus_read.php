<?php
session_start();
header('Content-Type: application/json');

// รับค่าจาก Fetch API (JSON)
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) { echo json_encode(['status' => 'error', 'message' => 'Invalid input']); exit; }

$ip = $input['ip'];
$port = intval($input['port']);
$slave_id = intval($input['slave']);
$fc = intval($input['fc']);
$address = intval($input['address']);
$quantity = intval($input['quantity']);
$timeout = 2; // เวลา Timeout

$trans_id = rand(1, 65000);
$packet = pack("nnnCCnn", $trans_id, 0, 6, $slave_id, $fc, $address, $quantity);

// เชื่อมต่ออุปกรณ์
$socket = @fsockopen($ip, $port, $errno, $errstr, $timeout);

if (!$socket) {
    echo json_encode(["status" => "error", "message" => "Connection failed: $errstr (IP: $ip)"]);
    exit;
}

stream_set_timeout($socket, $timeout);
fwrite($socket, $packet);
$response = fread($socket, 2048);
$info = stream_get_meta_data($socket);
fclose($socket);

if ($info['timed_out']) {
    echo json_encode(["status" => "error", "message" => "Timeout Error: อุปกรณ์ไม่ตอบสนองภายใน $timeout วินาที"]);
    exit;
}

if (strlen($response) < 9) {
    echo json_encode(["status" => "error", "message" => "No Response: ไม่ได้รับข้อมูลตอบกลับจาก Slave ID $slave_id"]);
    exit;
}

$header = unpack("nTrans/nProto/nLen/CUnit/CFunction/CByteCount", substr($response, 0, 9));

// ==========================================
// 🚨 ดักจับ Modbus Exception Codes 
// ==========================================
if ($header['Function'] > 0x80) {
    $exception_code = unpack("C", substr($response, 8, 1))[1];
    
    // แปลงรหัส Error ตามมาตรฐาน Modbus
    $error_msgs = [
        1 => "01 Illegal Function (ฟังก์ชันนี้ไม่รองรับ)",
        2 => "02 Illegal Data Address (ไม่มี Address นี้ในอุปกรณ์)",
        3 => "03 Illegal Data Value (ค่า Quantity ไม่ถูกต้อง)",
        4 => "04 Slave Device Failure (อุปกรณ์ปลายทางขัดข้อง)",
        5 => "05 Acknowledge (รับทราบคำสั่ง กำลังประมวลผล)",
        6 => "06 Slave Device Busy (อุปกรณ์ปลายทางไม่ว่าง)",
        10 => "0A Gateway Path Unavailable (ไม่สามารถเชื่อมต่อเส้นทาง Gateway ได้)",
        11 => "0B Gateway Target Device Failed to Respond (อุปกรณ์ปลายทางหลัง Gateway ไม่ตอบสนอง)"
    ];
    
    $msg = isset($error_msgs[$exception_code]) ? $error_msgs[$exception_code] : "Unknown Exception Code: 0x".dechex($exception_code);
    echo json_encode(["status" => "error", "message" => "Modbus Exception: $msg"]);
    exit;
}

// ถ้าปกติ ดึงข้อมูลออกมา
$data_payload = substr($response, 9, $header['ByteCount']);
$registers = unpack("n*", $data_payload);
$clean_registers = array_values($registers);

echo json_encode([
    "status" => "success",
    "byte_count" => $header['ByteCount'],
    "registers" => $clean_registers
]);
?>