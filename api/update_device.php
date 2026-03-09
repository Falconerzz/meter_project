<?php
session_start();
require '../db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    
    $id = intval($_POST['id']);
    $name = $conn->real_escape_string($_POST['name']);
    $location = $conn->real_escape_string($_POST['location']);
    $cat_id = intval($_POST['category_id']);
    $ip = $conn->real_escape_string($_POST['ip_address']);
    $port = intval($_POST['port']);
    $slave_id = intval($_POST['slave_id']);
    $mode = isset($_POST['mode']) ? $conn->real_escape_string($_POST['mode']) : 'real';
    
    // รับค่าการตั้งค่าขอบเขตแจ้งเตือน
    $alert_min = floatval($_POST['alert_min']);
    $alert_max = floatval($_POST['alert_max']);

    // ดึงข้อมูลเก่ามาเช็คเรื่องรูปภาพ
    $old_data = $conn->query("SELECT image FROM devices WHERE id = $id")->fetch_assoc();
    
    $final_image = isset($_POST['selected_image']) && !empty($_POST['selected_image']) ? $_POST['selected_image'] : $old_data['image'];

    // ถ้ามีการอัปโหลดไฟล์รูปใหม่
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $final_image = 'dev_' . time() . '_' . rand(100,999) . '.' . $ext;
        $upload_path = '../assets/uploads/' . $final_image;
        
        if(move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)){
            // ลบรูปเก่าทิ้ง (ถ้าไม่ใช่รูป default และไม่ใช่ URL ของ Flaticon)
            if($old_data['image'] !== 'default.png' && !filter_var($old_data['image'], FILTER_VALIDATE_URL)){
                @unlink('../assets/uploads/' . $old_data['image']);
            }
        }
    }

    // อัปเดตข้อมูลทั้งหมดลงฐานข้อมูล
    $stmt = $conn->prepare("UPDATE devices SET category_id=?, name=?, location=?, image=?, ip_address=?, port=?, slave_id=?, mode=?, alert_min=?, alert_max=? WHERE id=?");
    $stmt->bind_param("issssiisddi", $cat_id, $name, $location, $final_image, $ip, $port, $slave_id, $mode, $alert_min, $alert_max, $id);
    
    if($stmt->execute()){ 
        echo json_encode(['status' => 'success']); 
    } else { 
        echo json_encode(['status' => 'error', 'message' => $conn->error]); 
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']); 
}
?>