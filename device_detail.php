<?php
session_start();
if(!isset($_SESSION['user_id']) || !isset($_GET['id'])){ 
    header("Location: devices.php"); 
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

$id = intval($_GET['id']);
$device = $conn->query("SELECT * FROM devices WHERE id = $id")->fetch_assoc();

if(!$device) { 
    echo "<script>alert('ไม่พบอุปกรณ์ในระบบ'); window.location.href='devices.php';</script>"; 
    exit; 
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($device['name']) ?> | Real-time Modbus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style> 
        body { 
            font-family: 'Kanit', sans-serif; 
            background-color: #f3f4f6; 
        } 
        .console { 
            font-family: 'JetBrains Mono', monospace; 
            background: #111827; 
            color: #10b981; 
            padding: 15px; 
            border-radius: 8px; 
            min-height: 300px; 
            font-size: 0.9rem;
        } 
        .status-dot { 
            display: inline-block; 
            width: 10px; 
            height: 10px; 
            border-radius: 50%; 
            margin-right: 5px; 
        }
        .dot-active { 
            background-color: #10b981; 
            box-shadow: 0 0 8px #10b981; 
            animation: pulse 1.5s infinite; 
        }
        .dot-inactive { 
            background-color: #ef4444; 
        }
        @keyframes pulse { 
            0% { opacity: 1; } 
            50% { opacity: 0.4; } 
            100% { opacity: 1; } 
        }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content p-4">
    <div class="container-fluid">
        
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <a href="devices.php" class="btn btn-outline-secondary btn-sm rounded-circle p-2 shadow-sm">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <div>
                    <h3 class="fw-bold mb-0">📡 ดูรายละเอียดและมอนิเตอร์อุปกรณ์</h3>
                    <p class="text-muted small mb-0">แสดงผลข้อมูลแบบ Real-time จาก Modbus TCP</p>
                </div>
            </div>
            
            <button onclick="deleteDevice(<?= $device['id'] ?>, '<?= htmlspecialchars($device['name']) ?>')" class="btn btn-danger rounded-pill px-4 shadow-sm">
                <i data-lucide="trash-2" class="w-4 h-4 inline-block me-1"></i> ลบอุปกรณ์นี้
            </button>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 rounded-4 mb-4">
                    <?php $img_src = filter_var($device['image'], FILTER_VALIDATE_URL) ? $device['image'] : "assets/uploads/" . $device['image']; ?>
                    <img src="<?= $img_src ?>" class="card-img-top p-4 bg-light rounded-top-4" style="height: 250px; object-fit: contain;" onerror="this.src='https://via.placeholder.com/200'">
                    <div class="card-body border-top">
                        <h5 class="fw-bold text-center mb-4 text-primary"><?= htmlspecialchars($device['name']) ?></h5>
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted"><i data-lucide="database" class="w-4 h-4 inline-block me-1"></i> Data Mode</span> 
                                <strong class="fs-6">
                                    <?= (!empty($device['mode']) && strtolower($device['mode']) == 'mock') 
                                        ? '<span class="text-warning">🟠 จำลองข้อมูล (Mock)</span>' 
                                        : '<span class="text-success">🟢 รับค่าจากเครื่องจริง</span>' 
                                    ?>
                                </strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted"><i data-lucide="network" class="w-4 h-4 inline-block me-1"></i> IP Address</span> 
                                <strong class="fs-6 font-monospace"><?= $device['ip_address'] ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted"><i data-lucide="plug" class="w-4 h-4 inline-block me-1"></i> Port</span> 
                                <strong class="fs-6 font-monospace"><?= $device['port'] ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted"><i data-lucide="cpu" class="w-4 h-4 inline-block me-1"></i> Slave ID</span> 
                                <strong class="fs-6"><?= $device['slave_id'] ?></strong>
                            </li>
                            
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-light rounded-3 mt-2 p-2 border-bottom-0">
                                <span class="text-muted"><i data-lucide="calendar-clock" class="w-4 h-4 inline-block me-1"></i> วันที่เพิ่มเข้าระบบ</span> 
                                <div class="text-end">
                                    <strong class="fs-6 d-block"><?= date('d/m/Y H:i', strtotime($device['created_at'])) ?> น.</strong>
                                    <span class="small text-primary fw-medium"><?= timeAgoThai($device['created_at']) ?></span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow-sm border-0 rounded-4 h-100">
                    <div class="card-header bg-dark text-white p-3 rounded-top-4 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i data-lucide="terminal" class="w-5 h-5 text-success"></i>
                            <h6 class="mb-0 fw-bold">Live Modbus Data</h6>
                        </div>
                        <div class="text-end small">
                            <span id="pollIndicator" class="status-dot dot-inactive"></span> 
                            <span id="pollStatusText" class="text-muted">Stopped</span>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <form id="modbusForm" class="row g-3 mb-4">
                            <input type="hidden" id="ip" value="<?= $device['ip_address'] ?>">
                            <input type="hidden" id="port" value="<?= $device['port'] ?>">
                            <input type="hidden" id="slave" value="<?= $device['slave_id'] ?>">
                            
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-muted small">Function Code</label>
                                <select id="fc" class="form-select bg-light" onchange="restartPolling()">
                                    <option value="3">03 Read Holding Registers</option>
                                    <option value="4">04 Read Input Registers</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-muted small">Start Address (Dec)</label>
                                <input type="number" id="address" class="form-control bg-light" value="0" min="0" onchange="restartPolling()">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-muted small">Quantity</label>
                                <input type="number" id="quantity" class="form-control bg-light" value="10" min="1" max="100" onchange="restartPolling()">
                            </div>
                            <div class="col-12 mt-4 text-end">
                                <button type="button" class="btn btn-danger px-4 shadow-sm" id="btnTogglePoll" onclick="togglePolling()">
                                    <i data-lucide="square" class="w-4 h-4 inline-block me-1"></i> หยุดอ่านค่า (Stop)
                                </button>
                            </div>
                        </form>
                        
                        <div class="d-flex justify-content-between align-items-end mb-2">
                            <h6 class="fw-bold text-muted small text-uppercase tracking-wider mb-0">Response Data:</h6>
                            <small id="lastUpdate" class="text-muted">Last Update: --:--:--</small>
                        </div>
                        
                        <div class="console shadow-inner">
                            <div id="statusConsole" class="mb-2 text-muted">Initialize Auto-Read...</div>
                            <div id="dataConsole"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();

    let pollInterval; 
    let isPolling = false; 
    const pollRate = 3000;

    async function readModbusData() {
        const statusConsole = document.getElementById('statusConsole');
        const dataConsole = document.getElementById('dataConsole');
        const lastUpdate = document.getElementById('lastUpdate');
        
        const payload = {
            device_id: <?= $device['id'] ?>, 
            ip: document.getElementById('ip').value,
            port: document.getElementById('port').value, 
            slave: document.getElementById('slave').value,
            fc: document.getElementById('fc').value, 
            address: document.getElementById('address').value,
            quantity: document.getElementById('quantity').value
        };

        try {
            const res = await fetch('api/modbus_read.php', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' }, 
                body: JSON.stringify(payload) 
            });
            const data = await res.json();
            
            const now = new Date().toLocaleTimeString('th-TH');
            lastUpdate.innerText = `Last Update: ${now}`;

            if(data.status === 'success') {
                statusConsole.innerHTML = `> Status: <span style="color:#10b981">OK (200)</span> | Mode: <span class="text-info text-uppercase">${data.mode}</span> | Bytes: ${data.byte_count}`;
                
                let output = `<table class="table table-sm table-dark mt-2 mb-0" style="color:#10b981; border-color:#374151; background: transparent;">`;
                output += `<thead><tr><th style="color:#9ca3af">Address</th><th style="color:#9ca3af">Value (Dec)</th><th style="color:#9ca3af">Value (Hex)</th></tr></thead><tbody>`;
                
                let startAddr = parseInt(payload.address);
                data.registers.forEach((val, index) => {
                    let hex = "0x" + val.toString(16).padStart(4, '0').toUpperCase();
                    let valStyle = (val < 210 || val > 240) ? "color:#ef4444;" : "";
                    output += `<tr><td>${startAddr + index}</td><td class="fw-bold" style="${valStyle}">${val}</td><td>${hex}</td></tr>`;
                });
                output += `</tbody></table>`;
                
                dataConsole.innerHTML = output;
            } else {
                statusConsole.innerHTML = `> 🚨 <span style="color:#ef4444; font-weight:bold;">${data.message}</span>`;
                dataConsole.innerHTML = '';
            }
        } catch (err) { 
            statusConsole.innerHTML = `> 🚨 <span class="text-danger">System Error: ไม่สามารถเชื่อมต่อกับ API Server ได้</span>`; 
            dataConsole.innerHTML = '';
        }
    }

    function togglePolling() {
        const btn = document.getElementById('btnTogglePoll');
        const indicator = document.getElementById('pollIndicator');
        const statusText = document.getElementById('pollStatusText');

        if (isPolling) {
            clearInterval(pollInterval); 
            isPolling = false;
            btn.classList.replace('btn-danger', 'btn-success'); 
            btn.innerHTML = '<i data-lucide="play" class="w-4 h-4 inline-block me-1"></i> เริ่มอ่านค่า (Auto-Read)';
            indicator.classList.replace('dot-active', 'dot-inactive'); 
            statusText.innerText = "Stopped";
            statusText.classList.replace('text-success', 'text-muted');
        } else {
            readModbusData(); 
            pollInterval = setInterval(readModbusData, pollRate); 
            isPolling = true;
            btn.classList.replace('btn-success', 'btn-danger'); 
            btn.innerHTML = '<i data-lucide="square" class="w-4 h-4 inline-block me-1"></i> หยุดอ่านค่า (Stop)';
            indicator.classList.replace('dot-inactive', 'dot-active'); 
            statusText.innerText = "Live Polling...";
            statusText.classList.replace('text-muted', 'text-success');
        }
        lucide.createIcons();
    }

    function restartPolling() { 
        if(isPolling) { 
            clearInterval(pollInterval); 
            readModbusData(); 
            pollInterval = setInterval(readModbusData, pollRate); 
        } 
    }
    
    // โหลดหน้าเสร็จดึงค่าทันที
    document.addEventListener('DOMContentLoaded', () => { 
        togglePolling(); 
    });

    function deleteDevice(id, name) {
        Swal.fire({
            title: 'ยืนยันการลบอุปกรณ์?',
            html: `คุณกำลังจะลบ <b>${name}</b><br><span style="color:#ef4444; font-size: 0.9em;">*ข้อมูลประวัติจะหายทั้งหมด!</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'ลบข้อมูล',
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
                        window.location.href = 'devices.php';
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