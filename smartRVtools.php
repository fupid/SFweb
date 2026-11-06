<?php
// เชื่อมต่อข้อมูล Inventory กลาง
$filename = 'inventory.json';
$inventoryData = file_exists($filename) ? file_get_contents($filename) : '{"servers":[],"rams":[],"ssds":[],"hdds":[]}';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>SANGFOR HCI - Smart Analyzer v4.7</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .card-suggest { border-left: 8px solid #2563eb; background: white; }
        .chart-container { position: relative; height: 320px; width: 100%; }
        .notice-tag { background: #eff6ff; color: #1e40af; padding: 2px 10px; border-radius: 8px; font-size: 11px; font-weight: 800; border: 1px solid #bfdbfe; }
    </style>
</head>
<body class="p-4 md:p-8">

    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-end mb-10">
            <div>
                <a href="index.html" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-blue-600 transition mb-3 no-print" style="text-decoration:none;">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                    Back to Menu
                </a>
                <h1 class="text-4xl font-black text-slate-800 tracking-tighter uppercase">HCI Smart Sizer <span class="text-blue-600 italic">v4.7</span></h1>
                <p class="text-slate-500 uppercase text-[11px] font-bold tracking-[0.3em]">Workload Analysis & Comparison Engine</p>
            </div>
            <div class="bg-blue-100 text-blue-700 px-4 py-2 rounded-full text-[10px] font-black border border-blue-200 uppercase tracking-widest">
                ● 2-Copy Policy Active
            </div>
        </div>

        <div id="uploadZone" class="bg-white p-20 rounded-[3rem] border-2 border-dashed border-blue-200 text-center shadow-sm mb-10 hover:bg-blue-50/50 transition-all cursor-pointer">
            <div class="text-7xl mb-6">📤</div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Upload RVTools Report</h2>
            <p class="text-gray-500 mb-8 text-sm">ระบบจะประมวลผล Storage, RAM, และ CPU ตามลำดับความสำคัญเพื่อลดจำนวน Node</p>
            <input type="file" id="fileInput" class="hidden" accept=".xlsx" onchange="processFile(event)">
            <button onclick="document.getElementById('fileInput').click()" class="bg-blue-600 text-white px-14 py-4 rounded-2xl font-bold text-lg shadow-xl shadow-blue-200 hover:scale-105 transition">Start Analysis</button>
        </div>

        <div id="resultArea" class="hidden space-y-8">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 text-center">
                <div class="bg-white p-7 rounded-3xl border shadow-sm"><p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">VMs</p><p class="text-3xl font-black" id="statVM">-</p></div>
                <div class="bg-white p-7 rounded-3xl border shadow-sm"><p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">vCPU Total</p><p class="text-3xl font-black" id="statCPU">-</p></div>
                <div class="bg-white p-7 rounded-3xl border shadow-sm"><p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Actual RAM (GB)</p><p class="text-3xl font-black text-green-600" id="statRAM">-</p></div>
                <div class="bg-white p-7 rounded-3xl border shadow-sm"><p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Actual Store (TB)</p><p class="text-3xl font-black text-orange-600" id="statStorage">-</p></div>
            </div>

            <div id="suggestionBox" class="card-suggest p-10 rounded-[3rem] shadow-2xl border border-blue-100"></div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 bg-white p-8 rounded-[3rem] border shadow-sm">
                    <h3 class="font-black text-slate-800 mb-8 flex items-center gap-3">
                        <span class="w-2 h-6 bg-blue-600 rounded-full"></span> 
                        Capability Comparison: Current vs New System
                    </h3>
                    <div class="chart-container">
                        <canvas id="comparisonChart"></canvas>
                    </div>
                </div>
                
                <div class="bg-slate-900 p-8 rounded-[3rem] shadow-2xl text-white">
                    <h3 class="font-black text-xl border-b border-slate-800 pb-4 mb-6 uppercase tracking-tight">Sizing Factors</h3>
                    <div class="space-y-8">
                        <div>
                            <p class="text-orange-400 font-bold text-xs uppercase tracking-widest flex justify-between">1. Storage First <span class="notice-tag">+30% OH</span></p>
                            <p class="text-[11px] text-slate-400 mt-2 italic leading-relaxed">คำนวณพื้นที่แบบ 2-Copy และหักลบพื้นที่ OS/System 15%</p>
                        </div>
                        <div>
                            <p class="text-green-400 font-bold text-xs uppercase tracking-widest flex justify-between">2. Memory Logic <span class="notice-tag">+30% OH</span></p>
                            <p class="text-[11px] text-slate-400 mt-2 italic leading-relaxed">ใช้การเพิ่ม RAM Add-on เป็นหลักเพื่อคุมจำนวน Node ไว้ที่ 3 เครื่อง</p>
                        </div>
                        <div>
                            <p class="text-blue-400 font-bold text-xs uppercase tracking-widest flex justify-between">3. CPU Density <span class="notice-tag">+30% OH</span></p>
                            <p class="text-[11px] text-slate-400 mt-2 italic leading-relaxed">สำรองกำลังประมวลผลให้เพียงพอต่อสภาวะเครื่องเสีย 1 Node (N+1)</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const db = <?php echo $inventoryData; ?>; 
        let chartInstance = null;

        function cleanVal(val) {
            if (val === undefined || val === null) return 0;
            if (typeof val === 'number') return val;
            let cleaned = String(val).replace(/,/g, '').match(/[\d.]+/);
            return cleaned ? parseFloat(cleaned[0]) : 0;
        }

        function processFile(event) {
            const file = event.target.files[0];
            const reader = new FileReader();
            reader.onload = function(e) {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, { type: 'array' });
                const vInfo = XLSX.utils.sheet_to_json(workbook.Sheets['vInfo'] || workbook.Sheets[0]);
                analyze(vInfo);
            };
            reader.readAsArrayBuffer(file);
        }

        function analyze(vms) {
            if (!vms || vms.length === 0) return;

            const firstVM = vms[0];
            const ramKey = Object.keys(firstVM).find(k => k === 'Memory') || 'Memory';
            const storeKey = Object.keys(firstVM).find(k => k.includes('Provisioned')) || 'Provisioned MiB';

            let actCPU = 0, actRAM_MB = 0, actStore_MB = 0;
            vms.forEach(vm => {
                actCPU += cleanVal(vm.CPUs || vm.vCPUs);
                actRAM_MB += cleanVal(vm[ramKey]);
                actStore_MB += cleanVal(vm[storeKey]);
            });

            const actRAM_GB = Math.round(actRAM_MB / 1024);
            const actStore_GB = Math.round(actStore_MB / 1024);

            document.getElementById('statVM').innerText = vms.length;
            document.getElementById('statCPU').innerText = actCPU;
            document.getElementById('statRAM').innerText = actRAM_GB.toLocaleString();
            document.getElementById('statStorage').innerText = (actStore_GB / 1024).toFixed(2);

            runSizing(actCPU, actRAM_GB, actStore_GB);
            document.getElementById('uploadZone').classList.add('hidden');
            document.getElementById('resultArea').classList.remove('hidden');
        }

        function runSizing(aC, aR, aS) {
            const OH = 1.3; // 30% Overhead
            const targetCPU = aC * OH;
            const targetRAM = aR * OH;
            const targetStoreGB = aS * OH;
            const copies = 2;
            let nodes = 3;

            const sorted = [...db.servers].sort((a,b) => (a.core*a.socket) - (b.core*b.socket));
            let finalModel = sorted[0];
            let finalAddon = 0;
            let finalHddQtyPerNode = 0;

            for (let m of sorted) {
                let currentN = nodes;
                let success = false;
                while (currentN <= 16) {
                    const failNodes = currentN - 1; // N+1 Strategy
                    const maxAddon = db.rams[db.rams.length-1];
                    
                    // 1. Check RAM (Base + Addon)
                    const ramPerNode = targetRAM / failNodes;
                    if (m.baseRam + maxAddon >= ramPerNode) {
                        
                        // 2. FIXED STORAGE LOGIC: Calculate per node with balanced distribution
                        const totalRawNeeded = (targetStoreGB * copies) / 0.85; // 15% HCI Overhead
                        const rawPerNode = totalRawNeeded / currentN;
                        const hddSize = 8000; // Standard 8TB HDD
                        
                        const hddQty = Math.ceil(rawPerNode / hddSize);
                        const availableSlots = m.maxDisk - 2; // Reserved 2 slots for SSD Cache
                        
                        if (hddQty <= availableSlots) {
                            finalModel = m;
                            nodes = currentN;
                            finalHddQtyPerNode = Math.max(2, hddQty); // Min 2 disks
                            const needed = Math.max(0, ramPerNode - m.baseRam);
                            finalAddon = db.rams.find(r => r >= needed) || maxAddon;
                            success = true;
                            break;
                        }
                    }
                    currentN++;
                }
                if(success) break;
            }

            // Calculation for recommended Cluster performance (N-1 Capacity)
            const recC = (finalModel.core * finalModel.socket) * (nodes - 1) * 3; // 1:3 ratio
            const recR = (finalModel.baseRam + finalAddon) * (nodes - 1);
            const totalRawCluster = (finalHddQtyPerNode * nodes * 8000);
            const recS = (totalRawCluster * 0.85) / copies;

            updateUI(finalModel, nodes, finalAddon, finalHddQtyPerNode, aC, targetCPU, recC, aR, targetRAM, recR, aS, targetStoreGB, recS);
        }

        function updateUI(m, n, addon, hQty, aC, tC, rC, aR, tR, rR, aS, tS, rS) {
            document.getElementById('suggestionBox').innerHTML = `
                <div class="flex justify-between items-start mb-10">
                    <div><h2 class="text-3xl font-black text-blue-800 tracking-tight italic uppercase">Suggested HCI Configuration</h2></div>
                    <div class="bg-blue-700 text-white p-6 rounded-3xl shadow-xl text-center min-w-[150px]">
                        <span class="text-[10px] font-bold text-blue-200 block mb-1">Total Nodes</span><span class="text-5xl font-black">${n}</span>
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="space-y-4">
                        <div class="bg-slate-50 p-6 rounded-3xl border border-slate-200">
                            <p class="text-[9px] font-bold text-slate-400 uppercase mb-2 tracking-widest">Base Platform</p>
                            <p class="text-xl font-bold text-slate-800">${m.name}</p>
                            <p class="text-[10px] text-slate-500 font-bold italic">${m.socket}x Intel Xeon | ${m.core} Cores</p>
                        </div>
                        <div class="bg-green-50 p-6 rounded-3xl border border-green-200">
                            <p class="text-[9px] font-bold text-green-600 uppercase mb-2 tracking-widest">RAM / Node</p>
                            <p class="text-3xl font-black text-green-800">${m.baseRam + addon}GB</p>
                            <p class="text-[9px] text-green-700 mt-2 font-bold uppercase tracking-tighter">Base ${m.baseRam}GB + Addon ${addon}GB</p>
                        </div>
                    </div>
                    <div class="lg:col-span-2 bg-blue-50/50 p-8 rounded-[3rem] border border-blue-100 flex flex-col justify-center">
                        <p class="text-[10px] font-black text-blue-600 uppercase mb-8 tracking-[0.3em] text-center">Disk Layout (Balanced per Node)</p>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-white p-5 rounded-2xl shadow-sm border border-blue-100 text-center">
                                <span class="text-[9px] font-bold text-gray-400 block mb-1 uppercase tracking-widest">SSD Cache Tier</span>
                                <span class="text-xl font-black text-blue-700 underline decoration-blue-200 decoration-4">1.92TB SSD x 2</span>
                            </div>
                            <div class="bg-white p-5 rounded-2xl shadow-sm border border-blue-100 text-center">
                                <span class="text-[9px] font-bold text-gray-400 block mb-1 uppercase tracking-widest">HDD Capacity Tier</span>
                                <span class="text-xl font-black text-slate-800 underline decoration-slate-200 decoration-4">8TB HDD x ${hQty}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            drawChart(aC, tC, rC, aR, tR, rR, aS, tS, rS);
        }

        function drawChart(aC, tC, rC, aR, tR, rR, aS, tS, rS) {
            const ctx = document.getElementById('comparisonChart').getContext('2d');
            if(chartInstance) chartInstance.destroy();
            chartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['vCPU (Consolidate Ratio)', 'Memory (GB)', 'Storage (TB x 10)'],
                    datasets: [
                        { label: 'Current Usage (RVTools)', data: [aC, aR, (aS/1024)*10], backgroundColor: '#94a3b8' },
                        { label: 'Target Spec (+30% OH)', data: [tC, tR, (tS/1024)*10], backgroundColor: '#fbbf24' },
                        { label: 'Recommended Cluster Power', data: [rC, rR, (rS/1024)*10], backgroundColor: '#2563eb' }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { font: { size: 11, weight: 'bold' } } } },
                    scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } }
                }
            });
        }
    </script>
</body>
</html>