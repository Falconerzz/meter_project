<?php
session_start();

// ถ้าล็อกอินอยู่แล้ว ให้เด้งไปหน้า dashboard เลย
if(isset($_SESSION['user_id'])){
    header("Location: dashboard.php");
    exit;
}

require 'db.php';
$error = '';

// ตรวจสอบเมื่อมีการกดปุ่ม Login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($password === $row['password']) {
            // สร้าง Session
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['profile_pic'] = $row['profile_pic'];

            // 💾 ระบบจำชื่อผู้ใช้งาน (Keep Name) 30 วัน
            if (isset($_POST['keep_name'])) {
                setcookie("saved_username", $username, time() + (86400 * 30), "/"); 
            } else {
                setcookie("saved_username", "", time() - 3600, "/"); 
            }

            // 💾 ระบบจำรหัสผ่าน (Keep Password) 30 วัน
            if (isset($_POST['keep_pass'])) {
                setcookie("saved_password", base64_encode($password), time() + (86400 * 30), "/");
            } else {
                setcookie("saved_password", "", time() - 3600, "/");
            }

            header("Location: dashboard.php");
            exit;
        } else {
            $error = "รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $error = "ไม่พบชื่อผู้ใช้งานนี้";
    }
}

// ดึงค่าจาก Cookie มาแสดงในฟอร์ม (ถ้าเคยบันทึกไว้)
$fill_username = isset($_COOKIE['saved_username']) ? $_COOKIE['saved_username'] : '';
$fill_password = isset($_COOKIE['saved_password']) ? base64_decode($_COOKIE['saved_password']) : '';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PowerPulse Pro | BMS Secure Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;700&family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body, html { height: 100%; margin: 0; font-family: 'Kanit', sans-serif; background-color: #f1f5f9; overflow: hidden; }
        
        .login-container { 
            display: flex; align-items: center; justify-content: center; 
            min-height: 100vh; padding: 1rem; position: relative; z-index: 10; 
        }
        
        .glass-panel { 
            background: rgba(255, 255, 255, 0.88); 
            backdrop-filter: blur(20px); 
            -webkit-backdrop-filter: blur(20px); 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15); 
            border: 1px solid rgba(255,255,255,0.7); 
        }
        
        /* CSS สำหรับปุ่ม Checkbox ให้ดู Modern */
        .custom-checkbox {
            appearance: none; background-color: #fff; margin: 0; font: inherit; color: currentColor;
            width: 1.15em; height: 1.15em; border: 2px solid #cbd5e1; border-radius: 0.25em;
            display: grid; place-content: center; transition: all 0.2s; cursor: pointer;
        }
        .custom-checkbox::before {
            content: ""; width: 0.65em; height: 0.65em; transform: scale(0); transition: 120ms transform ease-in-out;
            box-shadow: inset 1em 1em white; background-color: white; transform-origin: center;
            clip-path: polygon(14% 44%, 0 65%, 50% 100%, 100% 16%, 80% 0%, 43% 62%);
        }
        .custom-checkbox:checked { background-color: #3b82f6; border-color: #3b82f6; }
        .custom-checkbox:checked::before { transform: scale(1); }

        /* 🎨 CSS สำหรับ Animation ก้อนสี Blob พื้นหลัง */
        @keyframes blob {
            0% { transform: translate(0px, 0px) scale(1); }
            33% { transform: translate(40px, -60px) scale(1.1); }
            66% { transform: translate(-30px, 30px) scale(0.95); }
            100% { transform: translate(0px, 0px) scale(1); }
        }
        .animate-blob { animation: blob 8s infinite; }
        .animation-delay-2000 { animation-delay: 2s; }
        .animation-delay-4000 { animation-delay: 4s; }
        .animation-delay-6000 { animation-delay: 6s; }
    </style>
</head>
<body>

    <div class="fixed inset-0 w-full h-full z-0 pointer-events-none">
        <div class="absolute top-[-15%] left-[-10%] w-[550px] h-[550px] bg-blue-400/50 rounded-full mix-blend-multiply filter blur-[90px] animate-blob"></div>
        <div class="absolute top-[15%] right-[-15%] w-[450px] h-[450px] bg-indigo-400/50 rounded-full mix-blend-multiply filter blur-[90px] animate-blob animation-delay-2000"></div>
        <div class="absolute bottom-[5%] right-[15%] w-[450px] h-[450px] bg-purple-400/50 rounded-full mix-blend-multiply filter blur-[80px] animate-blob animation-delay-6000"></div>
        <div class="absolute bottom-[-10%] left-[10%] w-[500px] h-[500px] bg-pink-400/50 rounded-full mix-blend-multiply filter blur-[90px] animate-blob animation-delay-4000"></div>
    </div>

    <div class="login-container">
        <div class="w-full max-w-md relative">
            
            <div class="glass-panel p-10 rounded-[2rem] relative z-10">
                
                <div class="flex flex-col items-center mb-8">
                    <div class="bg-gradient-to-br from-blue-500 to-indigo-600 p-4 rounded-3xl shadow-lg shadow-blue-500/30 mb-5">
                        <i data-lucide="zap" class="text-white w-8 h-8"></i>
                    </div>
                    <h1 class="text-3xl font-bold tracking-tight text-slate-800 uppercase italic">PowerPulse <span class="text-blue-600 font-light">Pro</span></h1>
                    <p class="text-xs text-slate-500 tracking-[0.2em] uppercase mt-2 font-medium">BMS Authentication</p>
                </div>

                <div class="bg-blue-50/90 border border-blue-200/60 rounded-xl p-4 mb-6 flex items-start gap-3 backdrop-blur-sm">
                    <i data-lucide="info" class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0"></i>
                    <div>
                        <p class="text-sm text-blue-800 font-semibold mb-1">Demo Access / ทดสอบระบบ</p>
                        <p class="text-xs text-blue-600">Username: <strong class="bg-white px-1.5 py-0.5 rounded shadow-sm border border-blue-100">admin</strong></p>
                        <p class="text-xs text-blue-600 mt-1">Password: <strong class="bg-white px-1.5 py-0.5 rounded shadow-sm border border-blue-100">admin</strong></p>
                    </div>
                </div>

                <?php if(!empty($error)): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 text-sm p-4 rounded-r-xl mb-6 shadow-sm flex items-center gap-3 font-medium animate-pulse">
                        <i data-lucide="alert-circle" class="w-5 h-5"></i> <?= $error ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    
                    <div class="mb-5">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Username</label>
                        <div class="relative">
                            <i data-lucide="user" class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-slate-400"></i>
                            <input type="text" name="username" value="<?= htmlspecialchars($fill_username) ?>" required 
                                   class="w-full bg-white/70 border border-slate-200 rounded-2xl py-3.5 pl-12 pr-4 text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all font-medium shadow-sm">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Password</label>
                        <div class="relative">
                            <i data-lucide="lock" class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-slate-400"></i>
                            <input type="password" name="password" id="passwordInput" value="<?= htmlspecialchars($fill_password) ?>" required 
                                   class="w-full bg-white/70 border border-slate-200 rounded-2xl py-3.5 pl-12 pr-12 text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all font-medium font-['JetBrains_Mono'] tracking-wider shadow-sm">
                            
                            <button type="button" id="togglePasswordBtn" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-blue-500 focus:outline-none transition-colors">
                                <i data-lucide="eye" id="eyeIcon" class="w-5 h-5"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-5 mb-8 px-1">
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input type="checkbox" name="keep_name" class="custom-checkbox" <?= !empty($fill_username) ? 'checked' : '' ?>>
                            <span class="text-sm text-slate-600 font-medium group-hover:text-blue-600 transition-colors">จำชื่อผู้ใช้</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input type="checkbox" name="keep_pass" class="custom-checkbox" <?= !empty($fill_password) ? 'checked' : '' ?>>
                            <span class="text-sm text-slate-600 font-medium group-hover:text-blue-600 transition-colors">จำรหัสผ่าน</span>
                        </label>
                    </div>

                    <button type="submit" class="w-full bg-slate-900 hover:bg-blue-600 text-white font-bold py-4 rounded-2xl shadow-lg hover:shadow-blue-500/30 transition-all duration-300 hover:-translate-y-1 uppercase tracking-widest text-sm flex justify-center items-center gap-2">
                        <span>Sign In</span> <i data-lucide="arrow-right" class="w-4 h-4 inline-block"></i>
                    </button>
                </form>
            </div>
            
            <p class="text-center text-xs text-slate-400 mt-6 font-medium tracking-wide">
                &copy; <?= date("Y") ?> PowerPulse Pro. All rights reserved.
            </p>
        </div>
    </div>

    <script>
        // โหลดไอคอนตอนเริ่มต้น
        lucide.createIcons();

        // สคริปต์สำหรับสลับการแสดงรหัสผ่าน
        const togglePasswordBtn = document.getElementById('togglePasswordBtn');
        const passwordInput = document.getElementById('passwordInput');
        const eyeIcon = document.getElementById('eyeIcon');

        togglePasswordBtn.addEventListener('click', function () {
            // สลับ type ระหว่าง password กับ text
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            
            // สลับไอคอนระหว่างตาเปิด (eye) กับตาปิด (eye-off)
            eyeIcon.setAttribute('data-lucide', isPassword ? 'eye-off' : 'eye');
            
            // สั่งให้ Lucide อัปเดตไอคอนใหม่
            lucide.createIcons();
        });
    </script>
</body>
</html>