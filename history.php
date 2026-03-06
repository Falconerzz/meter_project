<?php
session_start();
if(!isset($_SESSION['user_id'])){ header("Location: index.php"); exit; }
require 'db.php';

// ดึงข้อมูล 100 รายการล่าสุด พร้อมชื่ออุปกรณ์
$sql = "SELECT m.*, d.name as device_name 
        FROM current_meter m 
        LEFT JOIN devices d ON m.device_id = d.id 
        ORDER BY m.last_update DESC LIMIT 100";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Data History | BMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style> body { font-family: 'Kanit', sans-serif; background-color: #f3f4f6; } </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content p-4">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-0">🗄️ ประวัติข้อมูล (System Logs)</h3>
                <p class="text-muted small">ประวัติการบันทึกข้อมูลของอุปกรณ์ทั้งหมด 100 รายการล่าสุด</p>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="py-3 px-4">Log ID</th>
                            <th class="py-3 px-4">Device Name</th>
                            <th class="py-3 px-4">Timestamp</th>
                            <th class="py-3 px-4 text-info">V1 (V)</th>
                            <th class="py-3 px-4 text-primary">V2 (V)</th>
                            <th class="py-3 px-4 text-warning">V3 (V)</th>
                            <th class="py-3 px-4 text-success">Current (A)</th>
                        </tr>
                    </thead>
                    <tbody style="font-family: 'JetBrains Mono', monospace; font-size: 0.9rem;">
                        <?php if($result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="py-3 px-4 text-muted">#<?= $row['id'] ?></td>
                            <td class="py-3 px-4 fw-bold" style="font-family: 'Kanit', sans-serif;"><?= htmlspecialchars($row['device_name'] ?? 'Unknown') ?></td>
                            <td class="py-3 px-4"><?= $row['last_update'] ?></td>
                            <td class="py-3 px-4"><?= number_format($row['v1'], 1) ?></td>
                            <td class="py-3 px-4"><?= number_format($row['v2'], 1) ?></td>
                            <td class="py-3 px-4"><?= number_format($row['v3'], 1) ?></td>
                            <td class="py-3 px-4 fw-bold text-success"><?= number_format($row['a1'], 3) ?></td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="7" class="p-5 text-center text-muted" style="font-family: 'Kanit', sans-serif;">ไม่มีข้อมูลประวัติในระบบ</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>lucide.createIcons();</script>
</body>
</html>