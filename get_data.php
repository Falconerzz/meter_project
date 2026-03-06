<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(["status" => "Offline", "error" => "Unauthorized"]);
    exit;
}

require 'db.php';
header('Content-Type: application/json');

// รับ ID อุปกรณ์ที่ส่งมาจาก JavaScript
$device_id = isset($_GET['device_id']) ? intval($_GET['device_id']) : 1;

// 1. ค้นหา IP Address และ Port จากฐานข้อมูล
$stmt = $conn->prepare("SELECT ip_address, port FROM devices WHERE id = ?");
$stmt->bind_param("i", $device_id);
$stmt->execute();
$device_result = $stmt->get_result();

if ($device_result->num_rows === 0) {
    echo json_encode(["status" => "Offline", "error" => "Device not found"]);
    exit;
}
$device = $device_result->fetch_assoc();

$ip = $device['ip_address'];
$port = $device['port'];
$timeout = 2;

// 2. ดึงข้อมูลผ่าน Modbus TCP
$packet = "\x00\x01\x00\x00\x00\x06\x01\x03\x00\x00\x00\x0a";
$socket = @fsockopen($ip, $port, $errno, $errstr, $timeout);

if (!$socket) {
    echo json_encode(["status" => "Offline", "error" => "Cannot connect to " . $ip]);
    exit;
}

fwrite($socket, $packet);
$response = fread($socket, 2048);
fclose($socket);

if (strlen($response) < 9) {
    echo json_encode(["status" => "Offline", "error" => "Incomplete Data from " . $ip]);
    exit;
}

$res = unpack("n*", substr($response, 9));
$v1 = $res[2] / 10.0;
$v2 = $res[4] / 10.0;
$v3 = $res[6] / 10.0;
$a1 = $res[8] / 1000.0;

// 3. บันทึกประวัติลงตาราง current_meter (จำกัดการบันทึก 1 นาทีต่อ 1 อุปกรณ์)
$check_stmt = $conn->prepare("SELECT MAX(last_update) as last_time FROM current_meter WHERE device_id = ?");
$check_stmt->bind_param("i", $device_id);
$check_stmt->execute();
$row = $check_stmt->get_result()->fetch_assoc();
$should_insert = true;

if ($row['last_time']) {
    $last_time = strtotime($row['last_time']);
    if (time() - $last_time < 60) { 
        $should_insert = false;
    }
}

if ($should_insert) {
    $insert_stmt = $conn->prepare("INSERT INTO current_meter (device_id, v1, v2, v3, a1) VALUES (?, ?, ?, ?, ?)");
    $insert_stmt->bind_param("idddd", $device_id, $v1, $v2, $v3, $a1);
    $insert_stmt->execute();
}

echo json_encode([
    "status" => "Online",
    "v1" => $v1,
    "v2" => $v2,
    "v3" => $v3,
    "a1" => $a1
]);
?>