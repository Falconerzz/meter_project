<?php
session_start();
require '../db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$id = isset($data['id']) ? intval($data['id']) : 0;

if($id > 0) {
    $stmt = $conn->query("SELECT ip_address, port FROM devices WHERE id = $id");
    if($stmt->num_rows > 0) {
        $device = $stmt->fetch_assoc();
        $ip = $device['ip_address'];
        $port = intval($device['port']);
        $timeout = 1; // ตั้งเวลาเช็คแค่ 1 วินาทีเพื่อไม่ให้เว็บค้างนาน

        // ลองเชื่อมต่อ
        $socket = @fsockopen($ip, $port, $errno, $errstr, $timeout);
        $live_status = $socket ? 'Online' : 'Offline';
        if($socket) fclose($socket);

        // อัปเดตสถานะจริงลงฐานข้อมูล
        $conn->query("UPDATE devices SET status = '$live_status' WHERE id = $id");

        echo json_encode(['status' => 'success', 'id' => $id, 'live_status' => $live_status]);
        exit;
    }
}
echo json_encode(['status' => 'error']);
?>