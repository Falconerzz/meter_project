<?php
session_start();
if(!isset($_SESSION['user_id'])){ 
    header("Location: index.php"); 
    exit; 
}
require 'db.php';

// ดึงสถิติรวมของอุปกรณ์
$total_devices = $conn->query("SELECT COUNT(*) as count FROM devices")->fetch_assoc()['count'];
$online_devices = $conn->query("SELECT COUNT(*) as count FROM devices WHERE status = 'Online'")->fetch_assoc()['count'];
$offline_devices = $total_devices - $online_devices;

// ดึงประวัติแจ้งเตือนความผิดปกติล่าสุด 10 รายการ
$alerts = $conn->query("SELECT a.*, d.name as device_name, d.location 
                        FROM alert_logs a 
                        LEFT JOIN devices d ON a.device_id = d.id 
                        ORDER BY a.id DESC LIMIT 10");

// ฟังก์ชันแปลงเวลาเป็นภาษาไทย
function timeAgoThai($datetime) {
    if (empty($datetime)) return "ไม่ระบุ";
    $time_ago = strtotime($datetime);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    
    if($seconds <= 60) return "เพิ่งเกิดเมื่อสักครู่";
    else if($minutes <= 60) return "$minutes นาทีที่แล้ว";
    else if($hours <= 24) return "$hours ชั่วโมงที่แล้ว";
    else return "$days วันที่แล้ว";
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Overview | BMS Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style> 
        body { 
            font-family: 'Kanit', sans-serif; 
            background-color: #f8fafc; 
            color: #1e293b; 
        } 
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content p-4">
    <div class="container-fluid">
        <div class="d-flex align-items-center mb-4">
            <h3 class="fw-bold mb-0">📊 ภาพรวมระบบ (Dashboard Overview)</h3>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card border-0 rounded-4 shadow-sm bg-primary text-white h-100">
                    <div class="card-body p-4 d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-1 text-white-50 fw-bold text-uppercase">อุปกรณ์ทั้งหมดในระบบ</p>
                            <h2 class="fw-bold mb-0 display-4"><?= $total_devices ?> <span class="fs-5">เครื่อง</span></h2>
                        </div>
                        <div class="bg-white bg-opacity-25 p-3 rounded-circle"><i data-lucide="cpu" class="w-8 h-8"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 rounded-4 shadow-sm bg-success text-white h-100">
                    <div class="card-body p-4 d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-1 text-white-50 fw-bold text-uppercase">กำลังออนไลน์ (Online)</p>
                            <h2 class="fw-bold mb-0 display-4"><?= $online_devices ?> <span class="fs-5">เครื่อง</span></h2>
                        </div>
                        <div class="bg-white bg-opacity-25 p-3 rounded-circle"><i data-lucide="wifi" class="w-8 h-8"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 rounded-4 shadow-sm bg-danger text-white h-100">
                    <div class="card-body p-4 d-flex align-items-center justify-content-between">
                        <div>
                            <p class="mb-1 text-white-50 fw-bold text-uppercase">ขาดการติดต่อ (Offline)</p>
                            <h2 class="fw-bold mb-0 display-4"><?= $offline_devices ?> <span class="fs-5">เครื่อง</span></h2>
                        </div>
                        <div class="bg-white bg-opacity-25 p-3 rounded-circle"><i data-lucide="wifi-off" class="w-8 h-8"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 rounded-4 shadow-sm">
            <div class="card-header bg-white p-4 border-bottom d-flex align-items-center">
                <i data-lucide="bell-ring" class="text-warning me-2 w-5 h-5"></i>
                <h5 class="fw-bold mb-0">บันทึกการแจ้งเตือนความผิดปกติล่าสุด (Recent Alerts)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="py-3 px-4">วัน-เวลา</th>
                                <th class="py-3">อุปกรณ์ที่พบปัญหา</th>
                                <th class="py-3">สถานที่ติดตั้ง</th>
                                <th class="py-3">รายละเอียดการแจ้งเตือน</th>
                                <th class="py-3">เวลาที่ผ่านมา</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(isset($alerts) && $alerts->num_rows > 0): ?>
                                <?php while($a = $alerts->fetch_assoc()): 
                                    
                                    // ⭐️ กำหนดสีและไอคอนตามประเภทของการแจ้งเตือน (UX UI)
                                    $badge_class = "bg-danger bg-opacity-10 text-danger border-danger-subtle";
                                    $icon_name = "alert-triangle";
                                    
                                    // ถ้าข้อความเป็นเรื่องของระบบกลับมาออนไลน์
                                    if (strpos($a['message'], 'ออนไลน์') !== false) {
                                        $badge_class = "bg-success bg-opacity-10 text-success border-success-subtle";
                                        $icon_name = "check-circle";
                                    } 
                                    // ถ้าข้อความเป็นเรื่องของการออฟไลน์/หลุดเชื่อมต่อ
                                    elseif (strpos($a['message'], 'ออฟไลน์') !== false || strpos($a['message'], 'ขาดการติดต่อ') !== false) {
                                        $badge_class = "bg-secondary bg-opacity-10 text-secondary border-secondary-subtle";
                                        $icon_name = "wifi-off";
                                    }
                                ?>
                                <tr>
                                    <td class="py-3 px-4 text-muted small"><?= date('d/m/Y H:i:s', strtotime($a['created_at'])) ?></td>
                                    <td class="py-3 fw-bold text-primary"><?= htmlspecialchars($a['device_name']) ?></td>
                                    <td class="py-3 text-muted">
                                        <i data-lucide="map-pin" class="w-3 h-3 inline-block me-1"></i> 
                                        <?= htmlspecialchars($a['location'] ?? 'ไม่ระบุ') ?>
                                    </td>
                                    <td class="py-3">
                                        <span class="badge <?= $badge_class ?> border px-2 py-1 fs-6 fw-medium">
                                            <i data-lucide="<?= $icon_name ?>" class="w-4 h-4 inline-block me-1 mb-1"></i>
                                            <?= htmlspecialchars($a['message']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 small text-muted fw-bold"><?= timeAgoThai($a['created_at']) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i data-lucide="check-circle" class="w-10 h-10 mb-2 text-success opacity-50"></i>
                                        <p class="mb-0 fw-bold fs-5">ระบบทำงานปกติ</p>
                                        <p class="small">ยังไม่มีการแจ้งเตือนความผิดปกติในระบบ</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();
</script>
</body>
</html>