<?php
session_start();
require '../db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $location = $conn->real_escape_string($_POST['location']);
    $cat_id = $conn->real_escape_string($_POST['category_id']);
    $ip = $conn->real_escape_string($_POST['ip_address']);
    $port = intval($_POST['port']);
    $slave_id = intval($_POST['slave_id']);
    $mode = isset($_POST['mode']) ? $conn->real_escape_string($_POST['mode']) : 'real';
    
    $alert_min = floatval($_POST['alert_min']);
    $alert_max = floatval($_POST['alert_max']);

    // ถ้าเลือกสร้างหมวดหมู่ใหม่
    if ($cat_id === 'new' && !empty($_POST['new_category'])) {
        $new_cat = $conn->real_escape_string($_POST['new_category']);
        $colors = ['#ef4444', '#f97316', '#84cc16', '#10b981', '#06b6d4', '#3b82f6', '#6366f1', '#8b5cf6', '#d946ef', '#f43f5e'];
        $random_color = $colors[array_rand($colors)];
        $conn->query("INSERT INTO categories (name, color) VALUES ('$new_cat', '$random_color')");
        $cat_id = $conn->insert_id;
    }

    $image_name = isset($_POST['selected_image']) && !empty($_POST['selected_image']) ? $_POST['selected_image'] : 'default.png';

    // ถ้ามีการอัปโหลดไฟล์
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image_name = 'dev_' . time() . '_' . rand(100,999) . '.' . $ext;
        $upload_path = '../assets/uploads/' . $image_name;
        move_uploaded_file($_FILES['image']['tmp_name'], $upload_path);
    }

    // บันทึกข้อมูลลงฐานข้อมูล
    $stmt = $conn->prepare("INSERT INTO devices (category_id, name, location, image, ip_address, port, slave_id, mode, alert_min, alert_max) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssiisdd", $cat_id, $name, $location, $image_name, $ip, $port, $slave_id, $mode, $alert_min, $alert_max);
    
    if($stmt->execute()) {
        echo json_encode(['status' => 'success']); 
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]); 
    }
}
?>