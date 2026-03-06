<?php
session_start();
if(!isset($_SESSION['user_id'])){ header("Location: index.php"); exit; }
require 'db.php';

$cats = $conn->query("SELECT * FROM categories ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Add New Device | BMS</title>
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
                    <h3 class="fw-bold mb-0">🔌 เพิ่มอุปกรณ์ใหม่</h3>
                </div>

                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-5">
                        <form id="addDeviceForm" enctype="multipart/form-data">
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">ชื่ออุปกรณ์</label>
                                <input type="text" name="name" class="form-control form-control-lg" required placeholder="เช่น Main Chiller Meter">
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">หมวดหมู่</label>
                                    <select name="category_id" id="categorySelect" class="form-select form-select-lg" onchange="checkNewCategory()">
                                        <option value="">-- เลือกหมวดหมู่ --</option>
                                        <?php while($c = $cats->fetch_assoc()): ?>
                                            <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                                        <?php endwhile; ?>
                                        <option value="new" class="text-primary fw-bold">+ สร้างหมวดหมู่ใหม่</option>
                                    </select>
                                </div>
                                <div class="col-md-6" id="newCategoryDiv" style="display:none;">
                                    <label class="form-label fw-bold text-primary">ชื่อหมวดหมู่ใหม่</label>
                                    <input type="text" name="new_category" class="form-control form-control-lg border-primary" placeholder="ระบุหมวดหมู่ใหม่">
                                </div>
                            </div>

                            <div class="bg-light p-4 rounded-4 mb-4 border">
                                <label class="form-label fw-bold mb-3"><i data-lucide="image" class="w-5 h-5 inline-block me-1"></i> รูปภาพอุปกรณ์</label>
                                <div class="row align-items-center">
                                    <div class="col-md-4 text-center mb-3 mb-md-0">
                                        <img id="mainPreview" src="assets/uploads/default.png" class="img-thumbnail rounded-4 shadow-sm bg-white" style="width: 160px; height: 160px; object-fit: contain;" onerror="this.src='https://via.placeholder.com/160?text=No+Image'">
                                    </div>
                                    <div class="col-md-8">
                                        <p class="small text-muted fw-bold mb-2">1. เลือกจากไอคอนระบบ:</p>
                                        <div class="d-flex gap-2 mb-3 flex-wrap">
                                            <input type="hidden" name="selected_image" id="selectedImage" value="default.png">
                                            
                                            <img src="assets/uploads/default.png" class="preset-img active bg-white p-1 shadow-sm" style="width: 60px; height: 60px; object-fit: contain;" onclick="selectPreset('default.png', this, 'assets/uploads/default.png')" title="Default Image">
                                            
                                            <img src="https://cdn-icons-png.flaticon.com/512/3256/3256127.png" class="preset-img bg-white p-1 shadow-sm" style="width: 60px; height: 60px; object-fit: contain;" onclick="selectPreset('https://cdn-icons-png.flaticon.com/512/3256/3256127.png', this, 'https://cdn-icons-png.flaticon.com/512/3256/3256127.png')" title="Meter">
                                            
                                            <img src="https://cdn-icons-png.flaticon.com/512/2833/2833778.png" class="preset-img bg-white p-1 shadow-sm" style="width: 60px; height: 60px; object-fit: contain;" onclick="selectPreset('https://cdn-icons-png.flaticon.com/512/2833/2833778.png', this, 'https://cdn-icons-png.flaticon.com/512/2833/2833778.png')" title="HVAC">
                                            
                                            <img src="https://cdn-icons-png.flaticon.com/512/10008/10008354.png" class="preset-img bg-white p-1 shadow-sm" style="width: 60px; height: 60px; object-fit: contain;" onclick="selectPreset('https://cdn-icons-png.flaticon.com/512/10008/10008354.png', this, 'https://cdn-icons-png.flaticon.com/512/10008/10008354.png')" title="Pump">
                                        </div>

                                        <p class="small text-muted fw-bold mb-2 mt-3 border-top pt-3">2. หรืออัปโหลดรูปภาพของคุณเอง:</p>
                                        <input type="file" name="image" id="imageUpload" class="form-control" accept="image/*" onchange="previewUpload(this)">
                                    </div>
                                </div>
                            </div>

                            <div class="bg-light p-4 rounded-4 mb-4 border">
                                <h5 class="mb-3 fw-bold text-primary"><i data-lucide="network" class="w-5 h-5 inline-block me-1"></i> การเชื่อมต่อ (Modbus TCP)</h5>
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label class="form-label fw-bold small">IP Address</label>
                                        <input type="text" name="ip_address" class="form-control" required placeholder="192.168.x.x">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold small">Port</label>
                                        <input type="number" name="port" class="form-control" required value="502">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small">Slave ID (Unit ID)</label>
                                        <input type="number" name="slave_id" class="form-control" required value="1">
                                    </div>
                                </div>
                            </div>

                            <div class="text-end mt-2">
                                <button type="submit" class="btn btn-primary btn-lg px-5 shadow"><i data-lucide="save" class="w-5 h-5 inline-block me-1"></i> บันทึกข้อมูลอุปกรณ์</button>
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

    // ฟังก์ชันเลือกหมวดหมู่ใหม่
    function checkNewCategory() {
        const select = document.getElementById('categorySelect');
        const newCatDiv = document.getElementById('newCategoryDiv');
        if(select.value === 'new') { newCatDiv.style.display = 'block'; } 
        else { newCatDiv.style.display = 'none'; }
    }

    // ฟังก์ชันเมื่อคลิกเลือกรูปภาพจาก Preset
    function selectPreset(filename, imgElement, srcUrl) {
        document.getElementById('mainPreview').src = srcUrl; // อัปเดตกรอบพรีวิว
        document.getElementById('selectedImage').value = filename; // เก็บชื่อไฟล์/ลิงก์ เพื่อส่งไป API
        document.getElementById('imageUpload').value = ''; // ล้างค่าไฟล์ที่อาจอัปโหลดค้างไว้
        
        // จัดการลบกรอบสีฟ้าออกจากรูปอื่น แล้วใส่ให้รูปที่เลือก
        document.querySelectorAll('.preset-img').forEach(el => el.classList.remove('active'));
        imgElement.classList.add('active');
    }

    // ฟังก์ชันพรีวิวเมื่อผู้ใช้อัปโหลดไฟล์รูปเอง
    function previewUpload(input) {
        if (input.files && input.files[0]) {
            let reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('mainPreview').src = e.target.result;
                document.getElementById('selectedImage').value = ''; // ล้างค่า Preset เพราะใช้อัปโหลดแทน
                document.querySelectorAll('.preset-img').forEach(el => el.classList.remove('active'));
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // บันทึกข้อมูล
    document.getElementById('addDeviceForm').addEventListener('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        
        fetch('api/save_device.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                Swal.fire('สำเร็จ!', 'เพิ่มอุปกรณ์เรียบร้อยแล้ว', 'success').then(() => {
                    window.location.href = 'devices.php';
                });
            } else {
                Swal.fire('ข้อผิดพลาด', data.message, 'error');
            }
        }).catch(err => {
            Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
        });
    });
</script>
</body>
</html>