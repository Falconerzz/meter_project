let mainChart;
const maxPoints = 20;
const labels = [];
const values = [];

let totalKWh = 0;
const electricityRate = 7.0; 
const powerFactor = 0.85;    

function toggleTheme() {
    const body = document.getElementById('main-body');
    const icon = document.getElementById('theme-icon');
    
    body.classList.toggle('light-mode');
    
    const isLight = body.classList.contains('light-mode');
    localStorage.setItem('theme', isLight ? 'light' : 'dark');
    
    icon.setAttribute('data-lucide', isLight ? 'sun' : 'moon');
    lucide.createIcons();
    
    if (mainChart) {
        const gridColor = isLight ? 'rgba(0, 0, 0, 0.05)' : 'rgba(255, 255, 255, 0.05)';
        const tickColor = isLight ? '#475569' : '#64748b';
        
        mainChart.options.scales.y.grid.color = gridColor;
        mainChart.options.scales.y.ticks.color = tickColor;
        mainChart.options.scales.x.ticks.color = tickColor;
        mainChart.update();
    }
}

function loadSavedTheme() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'light') {
        document.getElementById('main-body').classList.add('light-mode');
        document.getElementById('theme-icon').setAttribute('data-lucide', 'sun');
    }
}

function initChart() {
    const canvas = document.getElementById('mainChart');
    if(!canvas) return; // ป้องกัน Error ถ้าหน้า History เรียกใช้ไฟล์นี้แล้วไม่มี Canvas
    
    const ctx = canvas.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
    gradient.addColorStop(1, 'rgba(59, 130, 246, 0)');

    const isLight = document.getElementById('main-body').classList.contains('light-mode');

    mainChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                borderColor: '#3b82f6',
                borderWidth: 3,
                fill: true,
                backgroundColor: gradient,
                tension: 0.4,
                pointRadius: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { 
                    grid: { color: isLight ? 'rgba(0,0,0,0.05)' : 'rgba(255,255,255,0.05)' }, 
                    ticks: { color: isLight ? '#475569' : '#64748b' } 
                },
                x: { 
                    grid: { display: false }, 
                    ticks: { color: isLight ? '#475569' : '#64748b' } 
                }
            }
        }
    });
}

async function updateDashboard() {
    // ถ้าไม่ได้อยู่หน้า dashboard ให้หยุดทำงาน
    if(typeof currentDeviceId === 'undefined') return;

    const ids = ['v1', 'v2', 'v3', 'a1', 'cost-val'];
    const statusBox = document.getElementById('status-container');
    const statusDot = document.getElementById('status-dot');
    const statusText = document.getElementById('status-text');

    try {
        // ส่ง device_id ไปให้ PHP ค้นหาอุปกรณ์ให้ถูกตัว
        const res = await fetch(`get_data.php?device_id=${currentDeviceId}`);
        const data = await res.json();

        if (data.status === "Online") {
            statusBox.className = "flex items-center gap-2 bg-emerald-500/10 px-4 py-2 rounded-xl border border-emerald-500/20";
            statusDot.className = "w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-[0_0_15px_#10b981]";
            statusText.innerText = "Online";
            statusText.className = "text-[10px] font-bold text-emerald-500 uppercase tracking-widest";

            document.getElementById('v1').innerText = data.v1.toFixed(1);
            document.getElementById('v2').innerText = data.v2.toFixed(1);
            document.getElementById('v3').innerText = data.v3.toFixed(1);
            document.getElementById('a1').innerText = data.a1.toFixed(3);
            
            ids.forEach(id => document.getElementById(id).classList.remove('stale-data'));

            const watts = data.v1 * data.a1 * powerFactor;
            document.getElementById('watt-val').innerText = Math.round(watts);
            const energyStep = (watts / 1000) * (3 / 3600);
            totalKWh += energyStep;
            document.getElementById('cost-val').innerText = (totalKWh * electricityRate).toFixed(2);

            const now = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            if (values.length >= maxPoints) { labels.shift(); values.shift(); }
            labels.push(now);
            values.push(data.a1);
            if(mainChart) mainChart.update();
        } else {
            setOfflineUI(ids, statusBox, statusDot, statusText);
        }
    } catch (e) {
        setOfflineUI(ids, statusBox, statusDot, statusText);
    }
}

function setOfflineUI(ids, box, dot, text) {
    box.className = "flex items-center gap-2 bg-red-500/10 px-4 py-2 rounded-xl border border-red-500/20";
    dot.className = "w-2.5 h-2.5 rounded-full bg-red-500 animate-ping shadow-[0_0_15px_#ef4444]";
    text.innerText = "Offline";
    text.className = "text-[10px] font-bold text-red-500 uppercase tracking-widest";

    ids.forEach(id => {
        const el = document.getElementById(id);
        if(id !== 'cost-val') el.innerText = "---";
        el.classList.add('stale-data');
    });
}

loadSavedTheme();
initChart();
setInterval(updateDashboard, 3000);
updateDashboard();