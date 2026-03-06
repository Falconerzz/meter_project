<?php
session_start();
if(!isset($_SESSION['user_id']) || !isset($_GET['id'])){ header("Location: devices.php"); exit; }
require 'db.php';

$id = intval($_GET['id']);
$device = $conn->query("SELECT * FROM devices WHERE id = $id")->fetch_assoc();
$cats = $conn->query("SELECT * FROM categories ORDER BY name ASC");

if(!$device) { echo "<script>alert('ไม่พบข้อมูล'); window.location='devices.php';</script>"; exit; }
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Edit Device | BMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style> 
        body { font-family: 'Kanit', sans-serif; background-color: #f3f4f6; } 
        .preset-img { transition: all 0.2s; cursor: pointer; border: 3px solid transparent; border-radius: 0.5rem; }
        .preset-img:hover { transform: scale(1.1); }
        .preset-img.active { border-color: #3b82f6; box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.5); }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content p-4">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <div class="d-flex align-items-center mb-4 gap-3">
                    <a href="devices.php" class="btn btn-outline-secondary btn-sm rounded-circle p-2"><i data-lucide="arrow-left" class="w-4 h-4"></i></a>
                    <h3 class="fw-bold mb-0 text-warning">✏️ แก้ไขข้อมูลอุปกรณ์</h3>
                </div>

                <div class="card shadow-sm border-0 rounded-4 border-top border-warning border-4">
                    <div class="card-body p-5">
                        <form id="editDeviceForm" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?= $device['id'] ?>">
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">ชื่ออุปกรณ์</label>
                                <input type="text" name="name" class="form-control form-control-lg" required value="<?= htmlspecialchars($device['name']) ?>">
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">หมวดหมู่</label>
                                <select name="category_id" class="form-select form-select-lg">
                                    <?php while($c = $cats->fetch_assoc()): ?>
                                        <option value="<?= $c['id'] ?>" <?= $c['id'] == $device['category_id'] ? 'selected' : '' ?>><?= $c['name'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="bg-light p-4 rounded-4 mb-4 border">
                                <label class="form-label fw-bold mb-3"><i data-lucide="image" class="w-5 h-5 inline-block me-1"></i> เปลี่ยนรูปภาพอุปกรณ์</label>
                                <div class="row align-items-center">
                                    <div class="col-md-4 text-center mb-3 mb-md-0">
                                        <?php $img_src = filter_var($device['image'], FILTER_VALIDATE_URL) ? $device['image'] : "assets/uploads/" . $device['image']; ?>
                                        <img id="mainPreview" src="<?= $img_src ?>" class="img-thumbnail rounded-4 shadow-sm bg-white" style="width: 160px; height: 160px; object-fit: contain;">
                                    </div>
                                    <div class="col-md-8">
                                        <input type="hidden" name="selected_image" id="selectedImage" value="<?= htmlspecialchars($device['image']) ?>">
                                        <div class="d-flex gap-2 mb-3 flex-wrap">
                                            <img src="assets/uploads/default.png" class="preset-img bg-white p-1" style="width: 60px; height: 60px; object-fit: contain;" onclick="selectPreset('default.png', this, 'assets/uploads/default.png')">
                                            <img src="https://cdn-icons-png.flaticon.com/512/3256/3256127.png" class="preset-img bg-white p-1" style="width: 60px; height: 60px; object-fit: contain;" onclick="selectPreset('https://cdn-icons-png.flaticon.com/512/3256/3256127.png', this, 'https://cdn-icons-png.flaticon.com/512/3256/3256127.png')">
                                            <img src="https://cdn-icons-png.flaticon.com/512/2833/2833778.png" class="preset-img bg-white p-1" style="width: 60px; height: 60px; object-fit: contain;" onclick="selectPreset('https://cdn-icons-png.flaticon.com/512/2833/2833778.png', this, 'https://cdn-icons-png.flaticon.com/512/2833/2833778.png')">
                                        </div>
                                        <p class="small text-muted fw-bold mb-2 mt-3 border-top pt-3">หรืออัปโหลดรูปภาพใหม่:</p>
                                        <input type="file" name="image" id="imageUpload" class="form-control" accept="image/*" onchange="previewUpload(this)">
                                    </div>
                                </div>
                            </div>

                            <div class="bg-light p-4 rounded-4 mb-4 border">
                                <h5 class="mb-3 fw-bold text-primary"><i data-lucide="network" class="w-5 h-5 inline-block me-1"></i> การเชื่อมต่อ (Modbus TCP)</h5>
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label class="form-label fw-bold small">IP Address</label>
                                        <input type="text" name="ip_address" class="form-control" required value="<?= $device['ip_address'] ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold small">Port</label>
                                        <input type="number" name="port" class="form-control" required value="<?= $device['port'] ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small">Slave ID (Unit ID)</label>
                                        <input type="number" name="slave_id" class="form-control" required value="<?= $device['slave_id'] ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="text-end mt-2">
                                <button type="submit" class="btn btn-warning btn-lg px-5 shadow fw-bold text-dark"><i data-lucide="save" class="w-5 h-5 inline-block me-1"></i> บันทึกการแก้ไข</button>
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

    function selectPreset(filename, imgElement, srcUrl) {
        document.getElementById('mainPreview').src = srcUrl;
        document.getElementById('selectedImage').value = filename;
        document.getElementById('imageUpload').value = '';
        document.querySelectorAll('.preset-img').forEach(el => el.classList.remove('active'));
        imgElement.classList.add('active');
    }

    function previewUpload(input) {
        if (input.files && input.files[0]) {
            let reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('mainPreview').src = e.target.result;
                document.getElementById('selectedImage').value = '';
                document.querySelectorAll('.preset-img').forEach(el => el.classList.remove('active'));
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    document.getElementById('editDeviceForm').addEventListener('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        
        fetch('api/update_device.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                Swal.fire('สำเร็จ!', 'แก้ไขข้อมูลเรียบร้อยแล้ว', 'success').then(() => {
                    window.location.href = 'devices.php';
                });
            } else {
                Swal.fire('ข้อผิดพลาด', data.message, 'error');
            }
        });
    });
</script>
</body>
</html>