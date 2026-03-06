<?php
session_start();
require '../db.php';
header('Content-Type: application/json');

// เช็คสิทธิ์การเข้าถึง
if(!isset($_SESSION['user_id'])) { 
    echo json_encode(['status'=>'error', 'message'=>'Unauthorized']); 
    exit; 
}

// รับค่า ID ที่ส่งมาจาก JavaScript
$data = json_decode(file_get_contents('php://input'), true);
$id = isset($data['id']) ? intval($data['id']) : 0;

if($id > 0) {
    // 1. ค้นหารูปภาพของอุปกรณ์นี้ เพื่อทำการลบไฟล์ออกจากโฟลเดอร์ (ถ้าไม่ใช่รูป default)
    $stmt = $conn->query("SELECT image FROM devices WHERE id = $id");
    if($stmt->num_rows > 0) {
        $row = $stmt->fetch_assoc();
        // ถ้าไม่ใช่รูป Preset ของระบบ และไฟล์มีอยู่จริง ให้ลบทิ้ง
        if($row['image'] !== 'default.png' && !filter_var($row['image'], FILTER_VALIDATE_URL)) {
            $file_path = '../assets/uploads/' . $row['image'];
            if(file_exists($file_path)) {
                unlink($file_path);
            }
        }
    }

    // 2. ลบประวัติการเก็บข้อมูลของอุปกรณ์นี้ (ป้องกัน Error Foreign Key)
    $conn->query("DELETE FROM current_meter WHERE device_id = $id");

    // 3. ลบตัวอุปกรณ์
    if($conn->query("DELETE FROM devices WHERE id = $id")) {
        echo json_encode(['status'=>'success']);
    } else {
        echo json_encode(['status'=>'error', 'message'=>'Database Error: '.$conn->error]);
    }
} else {
    echo json_encode(['status'=>'error', 'message'=>'Invalid Device ID']);
}
?>