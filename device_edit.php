<?php
session_start();
if(!isset($_SESSION['user_id']) || !isset($_GET['id'])){ 
    header("Location: devices.php"); 
    exit; 
}
require 'db.php';

$id = intval($_GET['id']);
$device = $conn->query("SELECT * FROM devices WHERE id = $id")->fetch_assoc();
$cats = $conn->query("SELECT * FROM categories ORDER BY name ASC");

if(!$device) { 
    echo "<script>alert('ไม่พบข้อมูลอุปกรณ์นี้'); window.location.href='devices.php';</script>"; 
    exit; 
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Edit Device | BMS Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style> 
        body { 
            font-family: 'Kanit', sans-serif; 
            background-color: #f8fafc; 
        } 
        
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
                    <h3 class="fw-bold mb-0 text-warning">✏️ แก้ไขข้อมูลอุปกรณ์</h3>
                </div>

                <div class="card shadow border-0 rounded-4 border-top border-warning border-4 mb-5">
                    <div class="card-body p-4 p-md-5">
                        <form id="editDeviceForm" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?= $device['id'] ?>">
                            
                            <div class="row mb-4 g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-slate-700">ชื่ออุปกรณ์</label>
                                    <input type="text" name="name" class="form-control form-control-lg rounded-3" required value="<?= htmlspecialchars($device['name']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-slate-700"><i data-lucide="map-pin" class="w-4 h-4 inline-block text-primary"></i> สถานที่ติดตั้ง (Location)</label>
                                    <input type="text" name="location" class="form-control form-control-lg rounded-3" required value="<?= htmlspecialchars($device['location']) ?>">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold text-slate-700">หมวดหมู่</label>
                                <select name="category_id" class="form-select form-select-lg rounded-3">
                                    <?php while($c = $cats->fetch_assoc()): ?>
                                        <option value="<?= $c['id'] ?>" <?= $c['id'] == $device['category_id'] ? 'selected' : '' ?>><?= $c['name'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="bg-light p-4 rounded-4 mb-4 border border-slate-200 shadow-inner">
                                <div class="row align-items-start g-4">
                                    <div class="col-md-4 col-lg-3 text-center">
                                        <label class="form-label fw-bold text-slate-700 mb-3"><i data-lucide="eye" class="w-4 h-4 inline-block me-1"></i>พรีวิว</label>
                                        <div class="p-3 bg-white rounded-4 shadow-sm border border-slate-100 d-inline-block">
                                            <?php $img_src = filter_var($device['image'], FILTER_VALIDATE_URL) ? $device['image'] : "assets/uploads/" . $device['image']; ?>
                                            <img id="mainPreview" src="<?= $img_src ?>" class="img-thumbnail border-0 rounded-3" style="width: 140px; height: 140px; object-fit: contain;" onerror="this.src='https://via.placeholder.com/140?text=No+Image'">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-8 col-lg-9">
                                        <input type="hidden" name="selected_image" id="selectedImage" value="<?= htmlspecialchars($device['image']) ?>">
                                        
                                        <label class="form-label fw-bold text-slate-700 mb-2"><i data-lucide="image" class="w-4 h-4 inline-block me-1 text-primary"></i> 1. เปลี่ยนไอคอนมาตรฐาน (Specialized IoT Icons)</label>
                                        
                                        <div class="icon-gallery-container mb-3 shadow-inner">
                                            <div class="row row-cols-3 row-cols-sm-4 row-cols-md-5 g-2">
                                                <?php
                                                    $icons = [
                                                        "https://cdn-icons-png.flaticon.com/512/5555/5555230.png",
                                                        "https://cdn-icons-png.flaticon.com/512/2103/2103567.png",
                                                        "https://cdn-icons-png.flaticon.com/512/1548/1548050.png",
                                                        "https://cdn-icons-png.flaticon.com/512/18592/18592064.png",
                                                        "https://cdn-icons-png.flaticon.com/512/3436/3436268.png",
                                                        "https://cdn-icons-png.flaticon.com/512/12041/12041855.png",
                                                        "https://cdn-icons-png.flaticon.com/512/6227/6227652.png",
                                                        "https://cdn-icons-png.flaticon.com/512/8923/8923689.png",
                                                        "https://cdn-icons-png.flaticon.com/512/16338/16338649.png",
                                                        "https://cdn-icons-png.flaticon.com/512/15271/15271114.png",
                                                        "assets/uploads/default.png"
                                                    ];
                                                    foreach($icons as $icon_url):
                                                ?>
                                                <div class="col">
                                                    <img src="<?= $icon_url ?>" class="preset-img shadow-sm <?= ($device['image'] === $icon_url) ? 'active' : '' ?>" onclick="selectPreset('<?= $icon_url ?>', this)">
                                                </div>
                                                <?php endforeach; ?>
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
                                    <option value="real" <?= (!empty($device['mode']) && strtolower($device['mode']) == 'real') ? 'selected' : '' ?>>🟢 ดึงจากอุปกรณ์จริง (Real Hardware Modbus TCP)</option>
                                    <option value="mock" <?= (!empty($device['mode']) && strtolower($device['mode']) == 'mock') ? 'selected' : '' ?>>🟠 จำลองข้อมูล (Mock Simulation) - สำหรับทดสอบ</option>
                                </select>
                            </div>

                            <div class="bg-light p-4 rounded-4 mb-4 border border-slate-200 shadow-inner">
                                <h5 class="mb-3 fw-bold text-primary"><i data-lucide="network" class="w-5 h-5 inline-block me-1"></i> การเชื่อมต่อ (Modbus TCP)</h5>
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label class="form-label fw-bold small text-slate-600">IP Address</label>
                                        <input type="text" name="ip_address" class="form-control rounded-3" required value="<?= htmlspecialchars($device['ip_address']) ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold small text-slate-600">Port</label>
                                        <input type="number" name="port" class="form-control rounded-3" required value="<?= htmlspecialchars($device['port']) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small text-slate-600">Slave ID (Unit ID)</label>
                                        <input type="number" name="slave_id" class="form-control rounded-3" required value="<?= htmlspecialchars($device['slave_id']) ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="bg-warning bg-opacity-10 p-4 rounded-4 mb-4 border border-warning-subtle shadow-sm">
                                <h5 class="mb-3 fw-bold text-warning-emphasis"><i data-lucide="bell-ring" class="w-5 h-5 inline-block me-1"></i> ตั้งค่าขอบเขตแจ้งเตือน Telegram (Thresholds)</h5>
                                <p class="small text-muted mb-3">ระบุตัวเลขเพื่อแจ้งเตือนเมื่อค่าที่อ่านได้ต่ำกว่าหรือสูงกว่า (ใส่ 0 หากไม่ต้องการแจ้งเตือน)</p>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-warning-emphasis">แจ้งเตือนเมื่อค่าต่ำกว่า (Min Alert)</label>
                                        <input type="number" step="0.01" name="alert_min" class="form-control rounded-3" value="<?= $device['alert_min'] ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-warning-emphasis">แจ้งเตือนเมื่อค่าสูงกว่า (Max Alert)</label>
                                        <input type="number" step="0.01" name="alert_max" class="form-control rounded-3" value="<?= $device['alert_max'] ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="text-end mt-4">
                                <button type="submit" class="btn btn-warning btn-lg px-5 shadow fw-bold text-dark rounded-pill">
                                    <i data-lucide="save" class="w-5 h-5 inline-block me-1"></i> บันทึกการแก้ไข
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

    document.getElementById('editDeviceForm').addEventListener('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        
        fetch('api/update_device.php', { 
            method: 'POST', 
            body: formData 
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                Swal.fire({
                    title: 'สำเร็จ!',
                    text: 'อัปเดตข้อมูลอุปกรณ์เรียบร้อยแล้ว',
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