<?php
// หาชื่อไฟล์ปัจจุบันเพื่อใช้ทำปุ่ม Active ให้ไฮไลท์สีฟ้าตรงเมนูที่กำลังเปิดอยู่
$current_page = basename($_SERVER['PHP_SELF']);
$profile_img = isset($_SESSION['profile_pic']) && $_SESSION['profile_pic'] != '' ? $_SESSION['profile_pic'] : 'default_avatar.png';
?>
<style>
    .sidebar { width: 260px; min-height: 100vh; background-color: #111827; color: #fff; position: fixed; left: 0; top: 0; display: flex; flex-direction: column; transition: all 0.3s; z-index: 1000; }
    .main-content { margin-left: 260px; min-height: 100vh; background-color: #f3f4f6; transition: all 0.3s; }
    .sidebar-header { padding: 20px; text-align: center; border-bottom: 1px solid #1f2937; }
    .sidebar-menu { padding: 15px 0; flex-grow: 1; overflow-y: auto; }
    .menu-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; color: #6b7280; padding: 10px 20px; margin-top: 10px; font-weight: bold; }
    .sidebar-link { color: #d1d5db; text-decoration: none; padding: 12px 20px; display: flex; align-items: center; gap: 12px; transition: 0.2s; font-size: 0.95rem; }
    .sidebar-link:hover, .sidebar-link.active { background-color: #1f2937; color: #3b82f6; border-left: 4px solid #3b82f6; }
    .user-profile { padding: 15px 20px; border-top: 1px solid #1f2937; display: flex; align-items: center; gap: 10px; background: #0f141e; }
    .user-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #3b82f6; }
</style>

<div class="sidebar">
    <div class="sidebar-header d-flex align-items-center justify-content-center gap-2">
        <i data-lucide="zap" class="text-primary"></i>
        <h4 class="mb-0 fw-bold" style="font-family: 'JetBrains Mono', monospace;">PowerPulse<span class="text-primary">Pro</span></h4>
    </div>

    <div class="sidebar-menu">
        <div class="menu-label">Main System</div>
        
        <a href="dashboard.php" class="sidebar-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <i data-lucide="layout-dashboard" style="width: 20px;"></i> แดชบอร์ด
        </a>
        
        <a href="history.php" class="sidebar-link <?= $current_page == 'history.php' ? 'active' : '' ?>">
            <i data-lucide="history" style="width: 20px;"></i> ประวัติข้อมูล
        </a>
        
        <div class="menu-label">Device Management</div>
        
        <a href="devices.php" class="sidebar-link <?= ($current_page == 'devices.php' || $current_page == 'device_detail.php') ? 'active' : '' ?>">
            <i data-lucide="server" style="width: 20px;"></i> อุปกรณ์ทั้งหมด
        </a>
        
        <a href="device_add.php" class="sidebar-link <?= $current_page == 'device_add.php' ? 'active' : '' ?>">
            <i data-lucide="plus-circle" style="width: 20px;"></i> เพิ่มอุปกรณ์ใหม่
        </a>

        <div class="menu-label">Settings</div>
        
        <a href="account_settings.php" class="sidebar-link <?= $current_page == 'account_settings.php' ? 'active' : '' ?>">
            <i data-lucide="user-cog" style="width: 20px;"></i> ตั้งค่าบัญชี
        </a>
    </div>

    <div class="user-profile">
        <img src="assets/uploads/<?= $profile_img ?>" class="user-avatar" onerror="this.src='https://via.placeholder.com/40'">
        <div style="flex-grow: 1; overflow: hidden;">
            <div class="small fw-bold text-truncate"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></div>
            <div style="font-size: 0.7rem; color: #10b981;">● Online</div>
        </div>
        <a href="logout.php" class="text-danger" title="Logout"><i data-lucide="log-out" style="width: 18px;"></i></a>
    </div>
</div>