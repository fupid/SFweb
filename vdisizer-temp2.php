<?php
// กำหนดข้อมูล Inventory ภายในไฟล์เพื่อความชัวร์ในการดึงข้อมูล
$vdiData = [
    "servers" => [
        ["name" => "aServer-X5-1200", "socket" => 2, "core" => 16, "freq" => 2.8, "baseRam" => 128, "maxDisk" => 7, "u2_max" => 4],
        ["name" => "aServer-X5-2105", "socket" => 2, "core" => 12, "freq" => 2.4, "baseRam" => 128, "maxDisk" => 12, "u2_max" => 2],
        ["name" => "aServer-X5-2205", "socket" => 2, "core" => 16, "freq" => 2.8, "baseRam" => 128, "maxDisk" => 12, "u2_max" => 2],
        ["name" => "aServer-X5-2305P", "socket" => 2, "core" => 32, "freq" => 2.1, "baseRam" => 128, "maxDisk" => 12, "u2_max" => 4],
        ["name" => "aServer-X5-2405", "socket" => 2, "core" => 32, "freq" => 2.8, "baseRam" => 128, "maxDisk" => 12, "u2_max" => 4],
        ["name" => "aServer-X5-G2200", "socket" => 2, "core" => 16, "freq" => 2.8, "baseRam" => 128, "maxDisk" => 10, "u2_max" => 10]
    ],
    "rams" => [0, 64, 128, 256, 384, 640, 896],
    "ssds" => [480, 960, 1900, 3800, 7600],
    "hdds" => [960, 1900, 3800, 7600, 4000, 6000, 8000, 10000, 12000],
    "profiles" => [
        ["type" => "task", "name" => "Task Worker", "vcpu" => 1, "ram" => 2, "ratio" => 10, "iops" => 15],
        ["type" => "office", "name" => "Office Worker", "vcpu" => 2, "ram" => 4, "ratio" => 6, "iops" => 30],
        ["type" => "power", "name" => "Power User", "vcpu" => 4, "ram" => 8, "ratio" => 3, "iops" => 80],
        ["type" => "designer", "name" => "3D Designer (GPU)", "vcpu" => 8, "ram" => 32, "ratio" => 1.5, "iops" => 150, "vram" => 4]
    ]
];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>SANGFOR VDI Pro Sizer v2.1</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .glass-card { background: white; border-radius: 1.5rem; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .eds-input { width: 100%; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 0.75rem; font-weight: 600; outline: none; transition: all 0.2s; }
        .eds-input:focus { border-color: #2563eb; ring: 2px rgba(37, 99, 235, 0.1); }
    </style>
</head>
<body class="p-4 md:p-8">

    <nav class="max-w-7xl mx-auto mb-8 flex justify-between items-center bg-white p-4 rounded-2xl shadow-sm border no-print">
        <h1 class="text-2xl font-black text-slate-800 italic uppercase">VDI <span class="text-blue-600 font-black">Pro Sizer</span></h1>
        <button onclick="exportPDF()" class="bg-blue-600 text-white px-6 py-2 rounded-xl font-bold hover:bg-blue-700 transition">📄 Export Specification</button>
    </nav>

    <main class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <div class="lg:col-span-4 space-y-6 no-print">
                <div class="glass-card p-6 border-t-8 border-t-slate-800">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6">📂 01 User Workload</h3>
                    <div class="space-y-5">
                        <?php foreach($vdiData['profiles'] as $p): ?>
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <label class="text-[11px] font-bold text-gray-600 uppercase"><?= $p['name'] ?></label>
                                <span class="text-[9px] text-gray-400">Ratio <?= $p['ratio'] ?>:1</span>
                            </div>
                            <input type="number" id="qty_<?= $p['type'] ?>" 
                                   class="eds-input bg-gray-50" value="0" min="0" 
                                   oninput="calculate()">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="glass-card p-6 border-t-8 border-t-blue-600">
                    <h3 class="text-xs font-black text-blue-600 uppercase tracking-widest mb-6">🛠️ 02 Hardware Base</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="text-[10px] font-bold text-gray-500 uppercase">Server Platform</label>
                            <select id="selModel" onchange="calculate()" class="eds-input bg-blue-50 border-blue-100">
                                <?php foreach($vdiData['servers'] as $s): ?>
                                <option value="<?= $s['name'] ?>" 
                                        data-core="<?= $s['core'] ?>" 
                                        data-socket="<?= $s['socket'] ?>" 
                                        data-freq="<?= $s['freq'] ?>" 
                                        data-base-ram="<?= $s['baseRam'] ?>"
                                        data-max-disk="<?= $s['maxDisk'] ?>">
                                    <?= $s['name'] ?> (Max <?= $s['maxDisk'] ?> Slots)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-gray-500 uppercase">Memory Add-on</label>
                            <select id="selAddRam" onchange="calculate()" class="eds-input">
                                <?php foreach($vdiData['rams'] as $r): ?>
                                <option value="<?= $r ?>">+ <?= $r ?> GB RAM</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-[10px] font-bold text-gray-500 uppercase">Disk Tier</label>
                                <select id="selHDD" onchange="calculate()" class="eds-input">
                                    <?php foreach($vdiData['hdds'] as $h): ?>
                                    <option value="<?= $h ?>"><?= ($h >= 1000) ? ($h/1000)."TB" : $h."GB" ?> HDD</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-gray-500 uppercase">Disks / Node</label>
                                <input type="number" id="qtyHDD" value="4" min="2" oninput="calculate()" class="eds-input text-center">
                            </div>
                        </div>
                        <div class="pt-4 border-t">
                            <label class="text-[10px] font-bold text-gray-500 uppercase">Cluster Nodes (N+1)</label>
                            <input type="number" id="qtyNode" value="3" min="3" oninput="calculate()" class="eds-input text-blue-700 text-lg">
                        </div>
                    </div>
                </div>
            </div>

            <div id="pdf-area" class="lg:col-span-8 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="glass-card p-6 bg-slate-900 text-white border-none">
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mb-1">Compute Capacity</p>
                        <p class="text-4xl font-black text-blue-400" id="resCompute">0 GHz</p>
                    </div>
                    <div class="glass-card p-6 bg-white border">
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mb-1">Cluster Memory</p>
                        <p class="text-4xl font-black text-emerald-600" id="resTotalRAM">0 GB</p>
                    </div>
                    <div class="glass-card p-6 bg-white border">
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mb-1">Usable Storage</p>
                        <p class="text-4xl font-black text-orange-600" id="resUsable">0 TB</p>
                    </div>
                </div>

                <div class="glass-card overflow-hidden">
                    <div class="bg-slate-800 text-white px-6 py-4 font-bold text-sm uppercase tracking-widest flex justify-between">
                        <span>📋 Infrastructure Bill of Materials</span>
                        <span id="resVRAM" class="text-purple-400">3D VRAM: 0 GB</span>
                    </div>
                    <table class="w-full text-sm">
                        <tbody id="bomBody" class="divide-y">
                            </tbody>
                    </table>
                </div>

                <div id="warningBox" class="hidden p-4 bg-red-50 border border-red-100 rounded-2xl text-red-700 text-xs font-bold italic">
                    ⚠️ Warning: Disk quantity exceeds server slot limit!
                </div>
            </div>

        </div>
    </main>

    <script>
        const db = <?php echo json_encode($vdiData); ?>;

        function calculate() {
            let totalvCPU = 0, totalRAM = 0, totalIOPS = 0, totalVRAM = 0;
            
            // 1. Get Workload from Inputs
            db.profiles.forEach(p => {
                const qty = parseInt(document.getElementById('qty_' + p.type).value) || 0;
                totalvCPU += (qty * p.vcpu / p.ratio);
                totalRAM += (qty * p.ram);
                totalIOPS += (qty * p.iops);
                if (p.vram) totalVRAM += (qty * p.vram);
            });

            // 2. Hardware Selection
            const selModel = document.getElementById('selModel');
            const opt = selModel.options[selModel.selectedIndex];
            const hw = {
                name: selModel.value,
                core: parseInt(opt.dataset.core),
                socket: parseInt(opt.dataset.socket),
                freq: parseFloat(opt.dataset.freq),
                baseRam: parseInt(opt.dataset.baseRam),
                maxDisk: parseInt(opt.dataset.maxDisk)
            };

            const nodes = parseInt(document.getElementById('qtyNode').value) || 3;
            const ramAdd = parseInt(document.getElementById('selAddRam').value) || 0;
            const totalRamNode = hw.baseRam + ramAdd;

            const hSize = parseInt(document.getElementById('selHDD').value);
            const hQty = parseInt(document.getElementById('qtyHDD').value);

            // 3. Logic Calculations
            // Capacity with Binary Loss and 2-Copy Protection
            const totalNetUsable = ((hSize * hQty * nodes) / 2 * 0.85) / 1024; 
            
            // Slot Check
            const usedSlots = hQty + 2; // +2 for SSD Cache
            const isOverSlot = usedSlots > hw.maxDisk;

            // 4. Update UI
            document.getElementById('resCompute').innerText = (totalvCPU * hw.freq).toFixed(1) + " GHz";
            document.getElementById('resTotalRAM').innerText = (totalRamNode * nodes).toLocaleString() + " GB";
            document.getElementById('resUsable').innerText = totalNetUsable.toFixed(2) + " TB";
            document.getElementById('resVRAM').innerText = `3D VRAM: ${totalVRAM} GB`;
            
            // Warning Box
            const warn = document.getElementById('warningBox');
            if(isOverSlot) {
                warn.classList.remove('hidden');
                warn.innerText = `⚠️ Warning: ${usedSlots} slots used, but ${hw.name} supports only ${hw.maxDisk} slots!`;
            } else {
                warn.classList.add('hidden');
            }

            // BOM Table Render
            document.getElementById('bomBody').innerHTML = `
                <tr class="bg-slate-50"><td class="p-4 font-bold">Server Chassis</td><td class="p-4 text-right text-blue-700 font-bold">${hw.name} x ${nodes} Units</td></tr>
                <tr><td class="p-4 font-bold">Processors</td><td class="p-4 text-right">${hw.socket}x Intel Xeon per node (Total ${hw.socket * nodes} CPUs)</td></tr>
                <tr class="bg-slate-50"><td class="p-4 font-bold">Memory Setup</td><td class="p-4 text-right">${totalRamNode}GB per node (Base ${hw.baseRam}GB + Addon ${ramAdd}GB)</td></tr>
                <tr><td class="p-4 font-bold">Storage Layout</td><td class="p-4 text-right">${hQty}x ${hSize >= 1000 ? (hSize/1000)+'TB' : hSize+'GB'} Data Disks per node</td></tr>
                <tr class="bg-slate-50"><td class="p-4 font-bold">Network Accessories</td><td class="p-4 text-right text-emerald-600 font-bold">${nodes * 8 + 4}x 10G Modules, ${nodes * 4 + 2}x Fiber Cables</td></tr>
            `;
        }

        function exportPDF() {
            const element = document.getElementById('pdf-area');
            const opt = {
                margin: 0.5,
                filename: 'Sangfor_VDI_Design.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }

        // Initial Run
        window.onload = calculate;
    </script>
</body>
</html>