<?php
session_start();
require '../db.php';
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){ echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $username = $conn->real_escape_string($_POST['username']);
    $new_password = $_POST['new_password'];
    
    // 1. เช็คว่าชื่อผู้ใช้ซ้ำกับคนอื่นไหม
    $check = $conn->query("SELECT id FROM users WHERE username = '$username' AND id != $user_id");
    if($check->num_rows > 0) { echo json_encode(['status' => 'error', 'message' => 'ชื่อผู้ใช้นี้ถูกใช้งานแล้ว']); exit; }

    // 2. จัดการรูปภาพ
    $pic_update_sql = "";
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {
        $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
        $image_name = 'user_' . $user_id . '_' . time() . '.' . $ext;
        $upload_path = '../assets/uploads/' . $image_name;
        
        if(move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_path)){
            $pic_update_sql = ", profile_pic = '$image_name'";
            $_SESSION['profile_pic'] = $image_name; // อัปเดต Session
        }
    }

    // 3. อัปเดตข้อมูล
    $_SESSION['username'] = $username;
    
    if(!empty($new_password)){
        // ถ้าใส่รหัสผ่านใหม่
        $pass = $conn->real_escape_string($new_password);
        $sql = "UPDATE users SET username = '$username', password = '$pass' $pic_update_sql WHERE id = $user_id";
    } else {
        // ถ้าเปลี่ยนแค่ชื่อ/รูป
        $sql = "UPDATE users SET username = '$username' $pic_update_sql WHERE id = $user_id";
    }

    if($conn->query($sql)){
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'DB Error']);
    }
}
?>