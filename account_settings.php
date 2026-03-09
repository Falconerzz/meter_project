<?php
session_start();
if(!isset($_SESSION['user_id'])){ header("Location: index.php"); exit; }
require 'db.php';

$user_id = $_SESSION['user_id'];
$user_data = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Account Settings | BMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style> body { font-family: 'Kanit', sans-serif; background-color: #f3f4f6; } </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content p-4">
    <div class="container-fluid">
        <h3 class="fw-bold mb-4">⚙️ ตั้งค่าบัญชีและการแจ้งเตือน (Settings & Alerts)</h3>
        
        <div class="row">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-sm border-0 rounded-4 mb-4">
                    <div class="card-body p-4">
                        <form id="accountForm" enctype="multipart/form-data">
                            
                            <div class="text-center mb-4">
                                <img src="assets/uploads/<?= $user_data['profile_pic'] ?>" id="previewImg" class="rounded-circle border border-3 border-primary shadow" style="width: 120px; height: 120px; object-fit: cover; margin-bottom: 15px;" onerror="this.src='https://via.placeholder.com/120'">
                                <br>
                                <label class="btn btn-sm btn-outline-primary">
                                    <i data-lucide="camera" class="w-4 h-4 me-1"></i> เปลี่ยนรูปโปรไฟล์
                                    <input type="file" name="profile_pic" class="d-none" accept="image/*" onchange="previewImage(this)">
                                </label>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">ชื่อผู้ใช้งาน (Username)</label>
                                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user_data['username']) ?>" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">รหัสผ่านใหม่ <span class="text-muted small fw-normal">(ปล่อยว่างไว้หากไม่ต้องการเปลี่ยน)</span></label>
                                <input type="password" name="new_password" class="form-control" placeholder="รหัสผ่านใหม่">
                            </div>

                            <div class="bg-light p-3 rounded-3 border mb-4">
                                <h6 class="fw-bold text-primary mb-3"><i data-lucide="send" class="w-4 h-4 inline-block me-1"></i> ตั้งค่าแจ้งเตือน Telegram</h6>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Telegram Bot Token (จาก @BotFather)</label>
                                    <input type="text" name="telegram_token" class="form-control form-control-sm" value="<?= htmlspecialchars($user_data['telegram_token'] ?? '') ?>" placeholder="e.g. 123456789:ABCdefGHIjklMNOpqr...">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-bold">Chat ID (จาก @userinfobot)</label>
                                    <input type="text" name="telegram_chat_id" class="form-control form-control-sm" value="<?= htmlspecialchars($user_data['telegram_chat_id'] ?? '') ?>" placeholder="e.g. 123456789">
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary px-4 shadow-sm"><i data-lucide="save" class="w-4 h-4 inline-block"></i> บันทึกข้อมูล</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();

    function previewImage(input) {
        if (input.files && input.files[0]) {
            let reader = new FileReader();
            reader.onload = function(e) { document.getElementById('previewImg').src = e.target.result; }
            reader.readAsDataURL(input.files[0]);
        }
    }

    document.getElementById('accountForm').addEventListener('submit', function(e) {
        e.preventDefault();
        fetch('api/update_account.php', { method: 'POST', body: new FormData(this) })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                Swal.fire('สำเร็จ!', 'อัปเดตข้อมูลเรียบร้อยแล้ว', 'success').then(() => location.reload());
            } else {
                Swal.fire('ข้อผิดพลาด', data.message, 'error');
            }
        });
    });
</script>
</body>
</html> 