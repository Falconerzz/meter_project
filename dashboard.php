<?php
session_start();
if(!isset($_SESSION['user_id'])){ header("Location: index.php"); exit; }
require 'db.php';

$devices_query = $conn->query("SELECT * FROM devices");
$devices = []; while($row = $devices_query->fetch_assoc()) { $devices[] = $row; }
$current_device_id = isset($_GET['device']) ? intval($_GET['device']) : (count($devices) > 0 ? $devices[0]['id'] : 1);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>PowerPulse Pro | BMS Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;700&family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style> body { font-family: 'Kanit', sans-serif; } </style>
    <script> const currentDeviceId = <?= $current_device_id ?>; </script>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="p-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded-4 shadow-sm">
            <div>
                <h4 class="mb-0 fw-bold">Live Monitoring</h4>
                <p class="text-muted small mb-0">ระบบติดตามการใช้พลังงานแบบเรียลไทม์</p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <select onchange="window.location.href='?device='+this.value" class="form-select form-select-sm fw-bold border-primary text-primary" style="width: 200px;">
                    <?php foreach($devices as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $d['id'] == $current_device_id ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div id="status-container" class="badge bg-danger p-2 text-uppercase d-flex align-items-center gap-2">
                    <span id="status-dot" class="spinner-grow spinner-grow-sm" role="status"></span> <span id="status-text">Connecting...</span>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm border-0 rounded-4 bg-primary text-white text-center p-4">
                    <div class="small text-uppercase opacity-75 mb-2">Voltage L1</div>
                    <div class="display-5 fw-bold" style="font-family:'JetBrains Mono'" id="v1">---</div><small>V</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 rounded-4 bg-info text-white text-center p-4">
                    <div class="small text-uppercase opacity-75 mb-2">Voltage L2</div>
                    <div class="display-5 fw-bold" style="font-family:'JetBrains Mono'" id="v2">---</div><small>V</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 rounded-4 bg-secondary text-white text-center p-4">
                    <div class="small text-uppercase opacity-75 mb-2">Voltage L3</div>
                    <div class="display-5 fw-bold" style="font-family:'JetBrains Mono'" id="v3">---</div><small>V</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 rounded-4 border border-success border-2 text-center p-4">
                    <div class="small text-success text-uppercase fw-bold mb-2">Current (A1)</div>
                    <div class="display-5 fw-bold text-success" style="font-family:'JetBrains Mono'" id="a1">---</div><small class="text-muted">A</small>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="assets/script.js"></script>
<script>lucide.createIcons();</script>
</body>
</html>