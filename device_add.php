<?php
session_start();
if(!isset($_SESSION['user_id'])){ 
    header("Location: index.php"); 
    exit; 
}
require 'db.php';

$cats = $conn->query("SELECT * FROM categories ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Add New Device | BMS Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style> 
        body { 
            font-family: 'Kanit', sans-serif; 
            background-color: #f8fafc; 
        } 
        
        /* สไตล์สำหรับแกลเลอรี่ไอคอน */
        .icon-gallery-container {
            max-height: 250px; 
            overflow-y: auto;  
            padding: 1rem;
            border-radius: 0.75rem;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
        }

        .preset-img { 
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); 
            cursor: pointer; 
            border: 3px solid transparent; 
            border-radius: 0.75rem; 
            padding: 0.5rem;
            background-color: #f8fafc;
            width: 100%; 
            height: 70px;
            object-fit: contain;
        }
        
        .preset-img:hover { 
            transform: scale(1.05); 
            background-color: #eff6ff;
            border-color: #bfdbfe;
        }
        
        .preset-img.active { 
            border-color: #3b82f6; 
            background-color: #eff6ff;
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3), 0 4px 6px -4px rgba(59, 130, 246, 0.2);
        }
        
        /* จัดการ Scrollbar */
        .icon-gallery-container::-webkit-scrollbar { width: 6px; }
        .icon-gallery-container::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        .icon-gallery-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .icon-gallery-container::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content p-4">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-11 col-lg-9 col-xl-8">
                <div class="d-flex align-items-center mb-4 gap-3">
                    <a href="devices.php" class="btn btn-outline-secondary btn-sm rounded-circle p-2 shadow-sm">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    </a>
                    <h3 class="fw-bold mb-0" style="color: #0f172a;">🔌 เพิ่มอุปกรณ์ใหม่</h3>
                </div>

                <div class="card shadow border-0 rounded-4 border-top border-primary border-4 mb-5">
                    <div class="card-body p-4 p-md-5">
                        <form id="addDeviceForm" enctype="multipart/form-data">
                            
                            <div class="row mb-4 g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-slate-700">ชื่ออุปกรณ์</label>
                                    <input type="text" name="name" class="form-control form-control-lg rounded-3" required placeholder="เช่น Main Chiller Meter">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-slate-700"><i data-lucide="map-pin" class="w-4 h-4 inline-block text-primary"></i> สถานที่ติดตั้ง (Location)</label>
                                    <input type="text" name="location" class="form-control form-control-lg rounded-3" required placeholder="เช่น ตึก A ชั้น 1 ห้องเครื่อง">
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-slate-700">หมวดหมู่</label>
                                    <select name="category_id" id="categorySelect" class="form-select form-select-lg rounded-3" onchange="checkNewCategory()">
                                        <option value="">-- เลือกหมวดหมู่ --</option>
                                        <?php while($c = $cats->fetch_assoc()): ?>
                                            <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                                        <?php endwhile; ?>
                                        <option value="new" class="text-primary fw-bold">+ สร้างหมวดหมู่ใหม่</option>
                                    </select>
                                </div>
                                <div class="col-md-6" id="newCategoryDiv" style="display:none;">
                                    <label class="form-label fw-bold text-primary">ชื่อหมวดหมู่ใหม่</label>
                                    <input type="text" name="new_category" class="form-control form-control-lg border-primary rounded-3" placeholder="ระบุหมวดหมู่ใหม่">
                                </div>
                            </div>

                            <div class="bg-light p-4 rounded-4 mb-4 border border-slate-200 shadow-inner">
                                <div class="row align-items-start g-4">
                                    <div class="col-md-4 col-lg-3 text-center">
                                        <label class="form-label fw-bold text-slate-700 mb-3"><i data-lucide="eye" class="w-4 h-4 inline-block me-1"></i>พรีวิว</label>
                                        <div class="p-3 bg-white rounded-4 shadow-sm border border-slate-100 d-inline-block">
                                            <img id="mainPreview" src="https://cdn-icons-png.flaticon.com/512/1548/1548050.png" class="img-thumbnail border-0 rounded-3" style="width: 140px; height: 140px; object-fit: contain;">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-8 col-lg-9">
                                        <input type="hidden" name="selected_image" id="selectedImage" value="https://cdn-icons-png.flaticon.com/512/1548/1548050.png">
                                        
                                        <label class="form-label fw-bold text-slate-700 mb-2"><i data-lucide="image" class="w-4 h-4 inline-block me-1 text-primary"></i> 1. เลือกจากไอคอนมาตรฐาน (Specialized IoT Icons)</label>
                                        
                                        <div class="icon-gallery-container mb-3 shadow-inner">
                                            <div class="row row-cols-3 row-cols-sm-4 row-cols-md-5 g-2">
                                                <div class="col"><img src="https://cdn-icons-png.flaticon.com/512/5555/5555230.png" class="preset-img shadow-sm" onclick="selectPreset('https://cdn-icons-png.flaticon.com/512/5555/5555230.png', this)" title="IoT Gateway"></div>
                                                <div class="col"><img src="https://cdn-icons-png.flaticon.com/512/2103/2103567.png" class="preset-img shadow-sm" onclick="selectPreset('https://cdn-icons-png.flaticon.com/512/2103/2103567.png', this)" title="IoT Server"></div>
                                                <div class="col"><img src="https://cdn-icons-png.flaticon.com/512/1548/1548050.png" class="preset-img active shadow-sm" onclick="selectPreset('https://cdn-icons-png.flaticon.com/512/1548/1548050.png', this)" title="Electric Meter"></div>
                                                <div class="col"><img src="https://cdn-icons-png.flaticon.com/512/18592/18592064.png" class="preset-img shadow-sm" onclick="selectPreset('https://cdn-icons-png.flaticon.com/512/18592/18592064.png', this)" title="Water Level Sensor"></div>
                                                <div class="col"><img src="https://cdn-icons-png.flaticon.com/512/3436/3436268.png" class="preset-img shadow-sm" onclick="selectPreset('https://cdn-icons-png.flaticon.com/512/3436/3436268.png', this)" title="Pressure Gauge Sensor"></div>
                                                <div class="col"><img src="https://cdn-icons-png.flaticon.com/512/12041/12041855.png" class="preset-img shadow-sm" onclick="selectPreset('https://cdn-icons-png.flaticon.com/512/12041/12041855.png', this)" title="pH Meter Sensor"></div>
                                                <div class="col"><img src="https://cdn-icons-png.flaticon.com/512/6227/6227652.png" class="preset-img shadow-sm" onclick="selectPreset('https://cdn-icons-png.flaticon.com/512/6227/6227652.png', this)" title="Temperature Sensor"></div>
                                                <div class="col"><img src="https://cdn-icons-png.flaticon.com/512/8923/8923689.png" class="preset-img shadow-sm" onclick="selectPreset('https://cdn-icons-png.flaticon.com/512/8923/8923689.png', this)" title="Humidity Sensor Type 1"></div>
                                                <div class="col"><img src="https://cdn-icons-png.flaticon.com/512/16338/16338649.png" class="preset-img shadow-sm" onclick="selectPreset('https://cdn-icons-png.flaticon.com/512/16338/16338649.png', this)" title="Humidity Sensor Type 2"></div>
                                                <div class="col"><img src="https://cdn-icons-png.flaticon.com/512/15271/15271114.png" class="preset-img shadow-sm" onclick="selectPreset('https://cdn-icons-png.flaticon.com/512/15271/15271114.png', this)" title="Thermostat"></div>
                                            </div>
                                        </div>
                                        
                                        <hr class="my-3 border-slate-300">
                                        
                                        <label class="form-label fw-bold text-slate-700 mb-2"><i data-lucide="upload-cloud" class="w-4 h-4 inline-block me-1 text-primary"></i> 2. หรือ อัปโหลดรูปภาพใหม่จากเครื่อง</label>
                                        <input type="file" name="image" id="imageUpload" class="form-control rounded-3" accept="image/*" onchange="previewUpload(this)">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4 bg-primary bg-opacity-10 p-4 rounded-4 border border-primary-subtle shadow-sm">
                                <label class="form-label fw-bold text-primary fs-5"><i data-lucide="database" class="w-5 h-5 inline-block me-1"></i> แหล่งที่มาของข้อมูล (Data Mode)</label>
                                <select name="mode" class="form-select form-select-lg border-primary text-primary fw-bold rounded-3 shadow-sm">
                                    <option value="real">🟢 ดึงจากอุปกรณ์จริง (Real Hardware Modbus TCP)</option>
                                    <option value="mock">🟠 จำลองข้อมูล (Mock Simulation) - สำหรับทดสอบและพรีเซนต์</option>
                                </select>
                            </div>

                            <div class="bg-light p-4 rounded-4 mb-4 border border-slate-200 shadow-inner">
                                <h5 class="mb-3 fw-bold text-primary"><i data-lucide="network" class="w-5 h-5 inline-block me-1"></i> การเชื่อมต่อ (Modbus TCP)</h5>
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label class="form-label fw-bold small text-slate-600">IP Address</label>
                                        <input type="text" name="ip_address" class="form-control rounded-3" required placeholder="192.168.x.x">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold small text-slate-600">Port</label>
                                        <input type="number" name="port" class="form-control rounded-3" required value="502">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small text-slate-600">Slave ID (Unit ID)</label>
                                        <input type="number" name="slave_id" class="form-control rounded-3" required value="1">
                                    </div>
                                </div>
                            </div>

                            <div class="bg-danger bg-opacity-10 p-4 rounded-4 mb-4 border border-danger-subtle shadow-sm">
                                <h5 class="mb-3 fw-bold text-danger"><i data-lucide="bell-ring" class="w-5 h-5 inline-block me-1"></i> ตั้งค่าขอบเขตแจ้งเตือน Telegram (Thresholds)</h5>
                                <p class="small text-muted mb-3">ระบุตัวเลขเพื่อแจ้งเตือนเมื่อค่าที่อ่านได้ต่ำกว่าหรือสูงกว่า (ใส่ 0 หากไม่ต้องการแจ้งเตือน)</p>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-danger">แจ้งเตือนเมื่อค่าต่ำกว่า (Min Alert)</label>
                                        <input type="number" step="0.01" name="alert_min" class="form-control rounded-3" value="0" placeholder="เช่น 210">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-danger">แจ้งเตือนเมื่อค่าสูงกว่า (Max Alert)</label>
                                        <input type="number" step="0.01" name="alert_max" class="form-control rounded-3" value="0" placeholder="เช่น 240">
                                    </div>
                                </div>
                            </div>

                            <div class="text-end mt-2">
                                <button type="submit" class="btn btn-primary btn-lg px-5 shadow rounded-pill fw-bold">
                                    <i data-lucide="save" class="w-5 h-5 inline-block me-1"></i> บันทึกข้อมูลอุปกรณ์
                                </button>
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

    function checkNewCategory() {
        const select = document.getElementById('categorySelect');
        const newCatDiv = document.getElementById('newCategoryDiv');
        if(select.value === 'new') { 
            newCatDiv.style.display = 'block'; 
        } else { 
            newCatDiv.style.display = 'none'; 
        }
    }

    function selectPreset(url, imgElement) {
        document.getElementById('mainPreview').src = url;
        document.getElementById('selectedImage').value = url;
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

    document.getElementById('addDeviceForm').addEventListener('submit', function(e) {
        e.preventDefault();
        fetch('api/save_device.php', { 
            method: 'POST', 
            body: new FormData(this) 
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                Swal.fire({
                    title: 'สำเร็จ!',
                    text: 'เพิ่มอุปกรณ์ใหม่เรียบร้อยแล้ว',
                    icon: 'success',
                    confirmButtonColor: '#3b82f6'
                }).then(() => {
                    window.location.href = 'devices.php';
                });
            } else {
                Swal.fire('ข้อผิดพลาด', data.message, 'error');
            }
        })
        .catch(error => {
            Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
        });
    });
</script>
</body>
</html>