<?php
session_start();
require '../db.php';
header('Content-Type: application/json');

// เช็คสิทธิ์การเข้าถึง
if(!isset($_SESSION['user_id'])) { 
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); 
    exit; 
}

$data = json_decode(file_get_contents('php://input'), true);

if(isset($data['id']) && isset($data['is_active'])) {
    $id = intval($data['id']);
    $is_active = intval($data['is_active']);
    
    // อัปเดตเฉพาะค่าสวิตช์รับค่า (is_active) โดยไม่ไปยุ่งกับสถานะ (status)
    $sql = "UPDATE devices SET is_active = $is_active WHERE id = $id";
    
    if($conn->query($sql)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Data Payload']);
}
?>