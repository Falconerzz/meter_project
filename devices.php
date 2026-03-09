<?php
session_start();
if(!isset($_SESSION['user_id'])){ 
    header("Location: index.php"); 
    exit; 
}
require 'db.php';

// ฟังก์ชันคำนวณเวลาว่า "ผ่านไปนานแค่ไหนแล้ว" (ภาษาไทย)
function timeAgoThai($datetime) {
    if (empty($datetime)) return "ไม่ระบุ";
    $time_ago = strtotime($datetime);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    $minutes      = round($seconds / 60 );
    $hours        = round($seconds / 3600);
    $days         = round($seconds / 86400);
    $weeks        = round($seconds / 604800);
    $months       = round($seconds / 2629440);
    $years        = round($seconds / 31553280);

    if($seconds <= 60) { return "เพิ่งเพิ่มเมื่อสักครู่"; }
    else if($minutes <= 60) { return "$minutes นาทีที่แล้ว"; }
    else if($hours <= 24) { return "$hours ชั่วโมงที่แล้ว"; }
    else if($days <= 7) { return "$days วันที่แล้ว"; }
    else if($weeks <= 4.3) { return "$weeks สัปดาห์ที่แล้ว"; }
    else if($months <= 12) { return "$months เดือนที่แล้ว"; }
    else { return "$years ปีที่แล้ว"; }
}

// ดึงข้อมูลหมวดหมู่ทั้งหมด
$cats = $conn->query("SELECT * FROM categories");

// ดึงข้อมูลอุปกรณ์ทั้งหมด
$sql = "SELECT d.*, c.name as cat_name, c.color as cat_color 
        FROM devices d 
        LEFT JOIN categories c ON d.category_id = c.id 
        ORDER BY d.id DESC";
$devices = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Device Management | BMS Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style> 
        body { 
            font-family: 'Kanit', sans-serif; 
            background-color: #f8fafc; 
            color: #1e293b; 
        } 
        
        .filter-btn { 
            border-radius: 50rem; 
            padding: 0.4rem 1.2rem; 
            font-weight: 600; 
            font-size: 0.9rem; 
            border: 2px solid var(--cat-color); 
            background-color: transparent; 
            color: var(--cat-color); 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
        }
        .filter-btn:hover { 
            background-color: var(--cat-color); 
            color: white; 
            opacity: 0.8; 
        }
        .filter-btn.active { 
            background-color: var(--cat-color); 
            color: white; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.15); 
        }

        .bms-card { 
            background: #ffffff; 
            border-radius: 1.25rem; 
            border: 1px solid #f1f5f9; 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); 
            transition: all 0.3s; 
            overflow: hidden; 
        }
        .bms-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 15px 25px -5px rgba(0,0,0,0.1); 
            border-color: #e2e8f0; 
        }
        
        .img-wrapper { 
            background: radial-gradient(circle at center, #ffffff 0%, #f1f5f9 100%); 
            position: relative; 
            padding: 2rem; 
            text-align: center; 
            border-bottom: 1px solid #f1f5f9; 
        }
        .device-img { 
            height: 120px; 
            object-fit: contain; 
            filter: drop-shadow(0 10px 15px rgba(0,0,0,0.1)); 
            transition: transform 0.3s ease; 
        }
        .bms-card:hover .device-img { 
            transform: scale(1.05); 
        }

        .status-badge { 
            position: absolute; 
            top: 1rem; 
            right: 1rem; 
            padding: 0.35rem 0.85rem; 
            border-radius: 2rem; 
            font-size: 0.75rem; 
            font-weight: 700; 
            letter-spacing: 0.5px; 
            background: rgba(255, 255, 255, 0.9); 
            backdrop-filter: blur(4px); 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
            display: flex; 
            align-items: center; 
            gap: 0.4rem; 
            text-transform: uppercase; 
            transition: color 0.3s; 
        }
        .status-dot { 
            width: 8px; 
            height: 8px; 
            border-radius: 50%; 
            transition: background-color 0.3s; 
        }
        
        .status-badge.online { color: #059669; } 
        .status-badge.online .status-dot { background-color: #10b981; box-shadow: 0 0 8px #10b981; }
        
        .status-badge.offline { color: #dc2626; } 
        .status-badge.offline .status-dot { background-color: #ef4444; box-shadow: 0 0 8px #ef4444; }
        
        .status-badge.checking { color: #d97706; } 
        .status-badge.checking .status-dot { background-color: #f59e0b; animation: pulse 1s infinite; }
        
        @keyframes pulse { 
            0% { opacity: 1; } 
            50% { opacity: 0.4; } 
            100% { opacity: 1; } 
        }

        .btn-action { 
            border: none; 
            font-weight: 600; 
            transition: all 0.2s; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 0.3rem; 
        }
        .btn-monitor { background-color: #eff6ff; color: #2563eb; } 
        .btn-monitor:hover { background-color: #3b82f6; color: white; }
        
        .btn-edit { background-color: #fefce8; color: #ca8a04; } 
        .btn-edit:hover { background-color: #eab308; color: white; }
        
        .btn-delete { background-color: #fef2f2; color: #ef4444; } 
        .btn-delete:hover { background-color: #ef4444; color: white; }
        
        .cat-badge { 
            padding: 0.35rem 1rem; 
            border-radius: 50rem; 
            font-size: 0.8rem; 
            font-weight: 600; 
            display: inline-block; 
            align-self: flex-start; 
            margin-bottom: 0.5rem; 
        }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content p-4">
    <div class="container-fluid">
        
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold mb-1" style="color: #0f172a;">📋 Device Management</h3>
                <p class="text-muted small mb-0">
                    สถานะการเชื่อมต่อถูกอัปเดตอัตโนมัติ 
                    <span id="syncText" class="text-warning fw-bold ms-2">
                        <i class="spinner-border spinner-border-sm me-1"></i>กำลังเช็คสถานะเครือข่าย...
                    </span>
                </p>
            </div>
            <a href="device_add.php" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm d-flex align-items-center gap-2 fw-semibold">
                <i data-lucide="plus" class="w-4 h-4"></i> เพิ่มอุปกรณ์ใหม่
            </a>
        </div>
        
        <div class="bg-white p-3 rounded-4 shadow-sm mb-4 border border-slate-100 d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
            
            <div class="d-flex flex-wrap gap-2 align-items-center" id="categoryFilters">
                <span class="fw-bold me-2 text-slate-400 small text-uppercase tracking-wider"><i data-lucide="tag" class="w-4 h-4 inline-block me-1"></i> หมวดหมู่ :</span>
                <button class="filter-btn active" data-filter="all" style="--cat-color: #64748b;">ทั้งหมด</button>
                <?php while($c = $cats->fetch_assoc()): ?>
                    <button class="filter-btn" data-filter="<?= $c['id'] ?>" style="--cat-color: <?= $c['color'] ?>;">
                        <?= htmlspecialchars($c['name']) ?>
                    </button>
                <?php endwhile; ?>
            </div>

            <div class="d-flex gap-3">
                <select id="statusFilter" class="form-select form-select-sm fw-medium border-slate-200 text-slate-600 rounded-3" style="width: 140px;" onchange="applyFilters()">
                    <option value="all">สถานะทั้งหมด</option>
                    <option value="Online">🟢 Online</option>
                    <option value="Offline">🔴 Offline</option>
                </select>
                <select id="sortFilter" class="form-select form-select-sm fw-medium border-slate-200 text-slate-600 rounded-3" style="width: 160px;" onchange="applyFilters()">
                    <option value="default">ค่าเริ่มต้น (Online ก่อน)</option>
                    <option value="newest">เพิ่มใหม่สุด</option>
                    <option value="oldest">เก่าสุด</option>
                </select>
            </div>
        </div>

        <div class="row g-4" id="deviceGrid">
            <?php 
            $device_ids = []; 
            while($d = $devices->fetch_assoc()): 
                $device_ids[] = $d['id'];
                $catColor = !empty($d['cat_color']) ? $d['cat_color'] : '#64748b';
            ?>
            <div class="col-md-6 col-lg-4 col-xl-3 device-item" data-cat="<?= $d['category_id'] ?>" data-status="checking" data-id="<?= $d['id'] ?>">
                <div class="bms-card h-100 d-flex flex-column">
                    <div class="img-wrapper">
                        <div class="status-badge checking" id="badge-<?= $d['id'] ?>">
                            <span class="status-dot"></span>
                            <span class="status-text">Checking...</span>
                        </div>
                        <?php $img_src = filter_var($d['image'], FILTER_VALIDATE_URL) ? $d['image'] : "assets/uploads/" . $d['image']; ?>
                        <img src="<?= $img_src ?>" class="device-img" alt="<?= htmlspecialchars($d['name']) ?>" onerror="this.src='https://via.placeholder.com/200?text=No+Image'">
                    </div>
                    
                    <div class="card-body d-flex flex-column p-4">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="cat-badge" style="background-color: <?= $catColor ?>15; color: <?= $catColor ?>; border: 1px solid <?= $catColor ?>40;">
                                <?= htmlspecialchars($d['cat_name']) ?>
                            </div>
                        </div>
                        
                        <h5 class="fw-bold text-truncate mb-3" style="color: #1e293b;" title="<?= htmlspecialchars($d['name']) ?>">
                            <?= htmlspecialchars($d['name']) ?>
                        </h5>
                        
                        <div class="bg-slate-50 p-3 rounded-3 mb-4 border border-slate-100">
                            <div class="d-flex align-items-center mb-2 text-slate-600 small">
                                <i data-lucide="network" class="w-4 h-4 me-2 text-primary"></i>
                                <span class="fw-medium font-monospace"><?= htmlspecialchars($d['ip_address']) ?> : <?= htmlspecialchars($d['port']) ?></span>
                            </div>
                            <div class="d-flex align-items-center mb-2 text-slate-600 small">
                                <i data-lucide="database" class="w-4 h-4 me-2 text-info"></i>
                                <span class="fw-medium">
                                    <?= (!empty($d['mode']) && strtolower($d['mode']) == 'mock') 
                                        ? '<span class="text-warning fw-bold">🟠 จำลองข้อมูล (Mock)</span>' 
                                        : '<span class="text-success fw-bold">🟢 รับค่าจากอุปกรณ์จริง</span>' 
                                    ?>
                                </span>
                            </div>
                            
                            <div class="d-flex align-items-center text-slate-500 small pt-2 mt-2 border-top border-slate-200">
                                <i data-lucide="clock" class="w-3 h-3 me-2 text-slate-400"></i>
                                <span>
                                    เพิ่มเมื่อ: <?= date('d/m/Y H:i น.', strtotime($d['created_at'])) ?> 
                                    <br>
                                    <span class="text-primary fw-medium" style="font-size: 0.75rem;">(<?= timeAgoThai($d['created_at']) ?>)</span>
                                </span>
                            </div>
                        </div>
                        
                        <div class="mt-auto d-flex gap-2">
                            <a href="device_detail.php?id=<?= $d['id'] ?>" class="btn btn-action btn-monitor flex-grow-1 rounded-3 shadow-sm" title="เข้าดูข้อมูล Modbus">
                                <i data-lucide="activity" class="w-4 h-4"></i> Monitor
                            </a>
                            <a href="device_edit.php?id=<?= $d['id'] ?>" class="btn btn-action btn-edit px-3 rounded-3 shadow-sm" title="แก้ไขตั้งค่าอุปกรณ์">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </a>
                            <button onclick="deleteDevice(<?= $d['id'] ?>, '<?= htmlspecialchars(addslashes($d['name'])) ?>')" class="btn btn-action btn-delete px-3 rounded-3 shadow-sm" title="ลบอุปกรณ์ออกจากระบบ">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <div id="emptyState" class="text-center py-5" style="display: none;">
            <i data-lucide="server-off" class="w-16 h-16 text-slate-300 mx-auto mb-3"></i>
            <h5 class="text-slate-500 fw-bold">ไม่พบอุปกรณ์ที่ตรงกับเงื่อนไข</h5>
            <p class="text-slate-400">ลองเปลี่ยนตัวกรองสถานะ หรือคลิกปุ่ม "ทั้งหมด"</p>
        </div>

    </div>
</div>

<script>
    lucide.createIcons();
    const deviceIds = <?= json_encode($device_ids) ?>;
    let checkedCount = 0;

    // 📡 ฟังก์ชันเช็คสถานะการเชื่อมต่อ
    async function checkAllDeviceStatus() {
        if(deviceIds.length === 0) {
            document.getElementById('syncText').innerHTML = "<span class='text-success'><i data-lucide='check-circle' class='w-4 h-4 inline-block me-1'></i>อัปเดตแล้ว</span>";
            lucide.createIcons();
            return;
        }

        for (let id of deviceIds) {
            try {
                const res = await fetch('api/check_status.php', {
                    method: 'POST', 
                    headers: { 'Content-Type': 'application/json' }, 
                    body: JSON.stringify({ id: id })
                });
                const data = await res.json();
                
                if (data.status === 'success') {
                    const badge = document.getElementById(`badge-${id}`);
                    const card = document.querySelector(`.device-item[data-id="${id}"]`);
                    
                    card.setAttribute('data-status', data.live_status);
                    
                    if(data.live_status === 'Online') {
                        badge.className = "status-badge online";
                        badge.querySelector('.status-text').innerText = "Online";
                    } else {
                        badge.className = "status-badge offline";
                        badge.querySelector('.status-text').innerText = "Offline";
                    }
                }
            } catch (e) {
                console.error("Status check failed for device " + id, e);
            }
            
            checkedCount++;
            if(checkedCount === deviceIds.length) {
                document.getElementById('syncText').innerHTML = "<span class='text-success'><i data-lucide='check-circle' class='w-4 h-4 inline-block me-1'></i>อัปเดตสถานะล่าสุดแล้ว</span>";
                lucide.createIcons();
                applyFilters();
            }
        }
    }

    document.addEventListener('DOMContentLoaded', () => { 
        checkAllDeviceStatus(); 
    });

    // 🔍 ฟังก์ชันเรียงลำดับและตัวกรอง
    function applyFilters() {
        const activeCat = document.querySelector('.filter-btn.active').getAttribute('data-filter');
        const activeStatus = document.getElementById('statusFilter').value;
        const sortValue = document.getElementById('sortFilter').value;
        
        const container = document.getElementById('deviceGrid');
        let items = Array.from(document.querySelectorAll('.device-item'));
        let visibleCount = 0;

        items.sort((a, b) => {
            let idA = parseInt(a.getAttribute('data-id'));
            let idB = parseInt(b.getAttribute('data-id'));
            let statA = a.getAttribute('data-status');
            let statB = b.getAttribute('data-status');

            if(sortValue === 'newest') return idB - idA;
            if(sortValue === 'oldest') return idA - idB;
            if(statA === statB) return idB - idA; 
            return statA === 'Online' ? -1 : 1; 
        });

        items.forEach(item => container.appendChild(item));

        items.forEach(item => {
            const matchCat = (activeCat === 'all' || item.getAttribute('data-cat') === activeCat);
            const matchStatus = (activeStatus === 'all' || item.getAttribute('data-status') === activeStatus);

            if (matchCat && matchStatus) {
                item.style.display = 'block';
                visibleCount++;
                setTimeout(() => item.style.opacity = "1", 10);
            } else {
                item.style.opacity = "0";
                setTimeout(() => item.style.display = 'none', 300);
            }
        });

        document.getElementById('emptyState').style.display = visibleCount === 0 ? 'block' : 'none';
    }

    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            e.target.classList.add('active');
            applyFilters();
        });
    });

    // 🗑️ ฟังก์ชันลบอุปกรณ์
    function deleteDevice(id, name) {
        Swal.fire({
            title: 'ยืนยันการลบอุปกรณ์?',
            html: `คุณกำลังจะลบอุปกรณ์ <b>${name}</b><br><span style="color:#ef4444; font-size: 0.9em; font-weight: 500;">*ประวัติการดึงข้อมูลทั้งหมดจะถูกลบทิ้งถาวร!</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'ใช่, ลบเลย',
            cancelButtonText: 'ยกเลิก',
            border: 'border-radius: 1rem;'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('api/delete_device.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        location.reload();
                    } else {
                        Swal.fire('ข้อผิดพลาด', data.message, 'error');
                    }
                });
            }
        });
    }
</script>
</body>
</html>