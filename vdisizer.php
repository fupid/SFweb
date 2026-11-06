<?php
$vdiData = [
    "servers" => [
        ["name" => "VDS-X5-2205", "socket" => 2, "core" => 16, "freq" => 2.8, "baseRam" => 256, "maxDisk" => 12],
        ["name" => "VDS-X5-2405", "socket" => 2, "core" => 32, "freq" => 2.8, "baseRam" => 256, "maxDisk" => 12],
        ["name" => "VDS-X5-G2200", "socket" => 2, "core" => 16, "freq" => 2.8, "baseRam" => 256, "maxDisk" => 12],
        ["name" => "VDS-X5-G2400", "socket" => 2, "core" => 32, "freq" => 2.8, "baseRam" => 256, "maxDisk" => 10],
        ["name" => "VDS-X5-G2600", "socket" => 2, "core" => 16, "freq" => 3.6, "baseRam" => 128, "maxDisk" => 10]
    ],
    "rams" => [0, 64, 128, 256, 384, 512, 640, 896],
    "ssds" => [480, 960, 1920, 3840],
    "hdds" => [960,1600, 1900, 3200, 3800, 7600, 4000, 6000, 8000, 10000, 12000],
    "iopsAssumptions" => ["ssdPerDisk" => 8000, "hddPerDisk" => 150, "sustainablePct" => 50],
    "profiles" => [
        ["type" => "task", "name" => "Task Worker", "vcpu" => 1, "ram" => 2, "ratio" => 10, "iops" => 15, "disk" => 40],
        ["type" => "office", "name" => "Office Worker", "vcpu" => 2, "ram" => 4, "ratio" => 6, "iops" => 30, "disk" => 60],
        ["type" => "power", "name" => "Power User", "vcpu" => 4, "ram" => 8, "ratio" => 3, "iops" => 80, "disk" => 100],
        ["type" => "designer", "name" => "3D Designer", "vcpu" => 8, "ram" => 32, "ratio" => 1.5, "iops" => 150, "vram" => 4, "disk" => 200]
    ]
];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>SANGFOR VDI Performance Sizer v2.5</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; }
        .glass-card { background: white; border-radius: 1.5rem; border: 1px solid #e2e8f0; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
        .input-mini { width: 100%; border: 1px solid #e2e8f0; border-radius: 0.5rem; padding: 4px; font-size: 11px; font-weight: 700; text-align: center; outline: none; transition: all 0.2s; }
        .input-mini:focus { border-color: #2563eb; background: #f0f7ff; }
        .section-label { font-size: 9px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; display: block; }
        .tab-title { font-size: 11px; font-weight: 900; letter-spacing: 0.1em; color: #1e293b; padding-bottom: 8px; border-bottom: 3px solid #1e293b; display: inline-block; margin-bottom: 20px; }
        .progress-bar { height: 6px; background: #e2e8f0; border-radius: 10px; overflow: hidden; }
        .progress-fill { height: 100%; transition: width 0.8s ease; }
    </style>
</head>
<body class="p-4 md:p-8">

    <main class="max-w-7xl mx-auto">
        <div class="flex justify-between items-end mb-10">
            <div>
                <a href="index.html" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-blue-600 transition mb-3 no-print" style="text-decoration:none;">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                    Back to Menu
                </a>
                <h1 class="text-3xl font-black text-slate-800 italic uppercase tracking-tighter">VDI <span class="text-blue-600 font-black">Performance Analyzer</span></h1>
                <p class="text-slate-400 text-[10px] font-bold uppercase tracking-[0.4em] mt-1">IOPS Load & Hardware Capability v2.5</p>
            </div>
            <button onclick="window.print()" class="bg-slate-900 text-white px-6 py-2 rounded-xl font-bold text-xs no-print">Export Report</button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <div class="lg:col-span-4 space-y-6 no-print">
                <div class="glass-card p-6 border-t-8 border-t-slate-800">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6">📂 01 Workload & IOPS Spec</h3>
                    <div class="space-y-6">
                        <?php foreach($vdiData['profiles'] as $p): ?>
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-xs font-black text-slate-700 italic"><?= $p['name'] ?></span>
                                <div class="w-20"><input type="number" id="qty_<?= $p['type'] ?>" class="input-mini border-blue-200 text-blue-600 !text-sm" value="0" oninput="calculate()"></div>
                            </div>
                            <div class="grid grid-cols-4 gap-2">
                                <div><span class="section-label">vCPU</span><input type="number" id="vcpu_<?= $p['type'] ?>" class="input-mini" value="<?= $p['vcpu'] ?>" oninput="calculate()"></div>
                                <div><span class="section-label">RAM(GB)</span><input type="number" id="ram_<?= $p['type'] ?>" class="input-mini" value="<?= $p['ram'] ?>" oninput="calculate()"></div>
                                <div><span class="section-label">Disk(GB)</span><input type="number" id="disk_<?= $p['type'] ?>" class="input-mini" value="<?= $p['disk'] ?>" oninput="calculate()"></div>
                                <div><span class="section-label <?= $p['type']=='designer'?'text-purple-600':'' ?>"><?= $p['type']=='designer'?'vGPU':'IOPS' ?></span><input type="number" id="<?= $p['type']=='designer'?'vgpu':'iops' ?>_<?= $p['type'] ?>" class="input-mini" value="<?= $p['type']=='designer'?$p['vram']:$p['iops'] ?>" oninput="calculate()"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="glass-card p-6 border-t-8 border-t-blue-600">
                    <h3 class="text-xs font-black text-blue-600 uppercase tracking-widest mb-6">🛠️ 02 Infrastructure Config</h3>
                    <div class="space-y-4">
                        <div>
                            <span class="section-label">Host Platform</span>
                            <select id="selModel" onchange="calculate()" class="w-full border p-2.5 rounded-xl font-bold bg-blue-50 border-blue-100 text-xs outline-none">
                                <?php foreach($vdiData['servers'] as $s): ?>
                                <option value="<?= $s['name'] ?>" data-core="<?= $s['core']*$s['socket'] ?>" data-base-ram="<?= $s['baseRam'] ?>" data-max-disk="<?= $s['maxDisk'] ?>">
                                    <?= $s['name'] ?> (Max <?= $s['maxDisk'] ?> Slots)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><span class="section-label">RAM Add-on</span><select id="selAddRam" onchange="calculate()" class="input-mini !text-left !px-3 !py-2.5"><?php foreach($vdiData['rams'] as $r): ?><option value="<?= $r ?>">+ <?= $r ?> GB</option><?php endforeach; ?></select></div>
                            <div><span class="section-label text-blue-600">Nodes (N+1)</span><input type="number" id="qtyNode" value="3" min="3" oninput="calculate()" class="input-mini !py-2 !text-blue-700 !text-lg"></div>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-4">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Hybrid Storage Configuration</p>
                            <div class="grid grid-cols-2 gap-3">
                                <div><span class="section-label text-blue-500">SSD Cache</span><select id="sizeSSD" onchange="calculate()" class="input-mini mb-1"><?php foreach($vdiData['ssds'] as $s): ?><option value="<?= $s ?>" <?= $s==1920?'selected':'' ?>><?= $s ?>GB</option><?php endforeach; ?></select><input type="number" id="qtySSD" value="2" class="input-mini" oninput="calculate()"></div>
                                <div><span class="section-label text-orange-500">HDD Data</span><select id="sizeHDD" onchange="calculate()" class="input-mini mb-1"><?php foreach($vdiData['hdds'] as $h): ?><option value="<?= $h ?>" <?= $h==8000?'selected':'' ?>><?= ($h/1000) ?>TB</option><?php endforeach; ?></select><input type="number" id="qtyHDD" value="4" class="input-mini" oninput="calculate()"></div>
                            </div>
                            <div class="border-t border-slate-200 pt-4">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-3">IOPS Assumptions</p>
                                <div class="grid grid-cols-3 gap-2">
                                    <div><span class="section-label text-blue-500">SSD / Unit</span><input type="number" id="ssdIopsPerDisk" min="0" value="<?= $vdiData['iopsAssumptions']['ssdPerDisk'] ?>" class="input-mini" oninput="calculate()"></div>
                                    <div><span class="section-label text-orange-500">HDD / Unit</span><input type="number" id="hddIopsPerDisk" min="0" value="<?= $vdiData['iopsAssumptions']['hddPerDisk'] ?>" class="input-mini" oninput="calculate()"></div>
                                    <div><span class="section-label text-emerald-600">Sustainable %</span><input type="number" id="sustainableIopsPct" min="1" max="100" value="<?= $vdiData['iopsAssumptions']['sustainablePct'] ?>" class="input-mini" oninput="calculate()"></div>
                                </div>
                                <p class="text-[9px] text-slate-400 mt-2 leading-relaxed">Disk size affects capacity. Configured IOPS uses disk quantity and the performance assumptions above.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-8 space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="glass-card p-6 border-l-8 border-l-orange-500">
                        <span class="section-label text-slate-400 mb-4">Storage IOPS Performance</span>
                        <div class="space-y-4">
                            <div class="flex justify-between items-end">
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase">Usage Demand</p>
                                    <p id="usageIOPS" class="text-3xl font-black text-slate-800">0</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase">Est. Sustainable</p>
                                    <p id="configIOPS" class="text-3xl font-black text-orange-600">0</p>
                                </div>
                            </div>
                            <div class="progress-bar"><div id="iopsBar" class="progress-fill bg-orange-500" style="width: 0%"></div></div>
                            <p id="iopsStatus" class="text-[9px] font-bold uppercase tracking-widest text-center"></p>
                            <p id="iopsFormula" class="text-[9px] text-slate-400 text-center leading-relaxed"></p>
                        </div>
                    </div>

                    <div class="glass-card p-6 border-l-8 border-l-blue-600">
                        <span class="section-label text-slate-400 mb-4">Compute & Memory Resource</span>
                        <div class="space-y-4">
                            <div class="flex justify-between text-xs">
                                <span class="font-bold text-slate-500">CPU Load (N+1)</span>
                                <span id="cpuStatus" class="font-black text-blue-600">- / - Cores</span>
                            </div>
                            <div class="progress-bar"><div id="cpuBar" class="progress-fill bg-blue-600" style="width: 0%"></div></div>
                            <div class="flex justify-between text-xs pt-2">
                                <span class="font-bold text-slate-500">RAM Load (N+1)</span>
                                <span id="ramStatus" class="font-black text-emerald-600">- / - GB</span>
                            </div>
                            <div class="progress-bar"><div id="ramBar" class="progress-fill bg-emerald-500" style="width: 0%"></div></div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="glass-card overflow-hidden">
                        <div class="bg-slate-800 text-white px-5 py-3 font-bold text-[10px] uppercase tracking-widest">📉 User Usage (Requirements)</div>
                        <table class="w-full text-xs italic font-medium">
                            <tbody class="divide-y divide-slate-100">
                                <tr><td class="p-4">vCPU Peak Load</td><td id="uCPU" class="p-4 text-right font-black">0</td></tr>
                                <tr><td class="p-4">RAM Total (1:1)</td><td id="uRAM" class="p-4 text-right font-black">0 GB</td></tr>
                                <tr><td class="p-4">vGPU VRAM Total</td><td id="uVRAM" class="p-4 text-right font-black">0 GB</td></tr>
                                <tr class="bg-blue-50 text-blue-700"><td class="p-4 font-bold">Total User Disk Usage</td><td id="uDisk" class="p-4 text-right font-black">0 TB</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="glass-card overflow-hidden">
                        <div class="bg-blue-700 text-white px-5 py-3 font-bold text-[10px] uppercase tracking-widest">🏗️ Cluster Config (N+1 Basis)</div>
                        <table class="w-full text-xs italic font-medium">
                            <tbody class="divide-y divide-slate-100">
                                <tr><td class="p-4">Physical Core (N+1)</td><td id="cCPU" class="p-4 text-right font-black">0</td></tr>
                                <tr><td class="p-4">Physical RAM (N+1)</td><td id="cRAM" class="p-4 text-right font-black">0 GB</td></tr>
                                <tr><td class="p-4">SSD:HDD Cache Ratio</td><td id="cRatio" class="p-4 text-right font-black">0%</td></tr>
                                <tr class="bg-emerald-50 text-emerald-700"><td class="p-4 font-bold">Total Net Usable Storage</td><td id="cUsable" class="p-4 text-right font-black">0 TB</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="glass-card overflow-hidden">
                    <div class="bg-slate-900 text-white px-8 py-4 font-black text-xs uppercase tracking-[0.2em]">Final Infrastructure BOM Summary</div>
                    <table class="w-full text-xs italic font-semibold text-slate-600">
                        <tbody id="bomBody" class="divide-y"></tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

<script>
    const db = <?php echo json_encode($vdiData); ?>;

    function calculate() {
        let uCPU = 0, uRAM = 0, uDiskGB = 0, uVRAM = 0, uIOPS = 0;

        // 1. Calculate USAGE
        db.profiles.forEach(p => {
            const qty = parseInt(document.getElementById('qty_' + p.type).value) || 0;
            const v = parseInt(document.getElementById('vcpu_' + p.type).value) || 0;
            const r = parseInt(document.getElementById('ram_' + p.type).value) || 0;
            const d = parseInt(document.getElementById('disk_' + p.type).value) || 0;
            
            uCPU += (qty * v / p.ratio);
            uRAM += (qty * r);
            uDiskGB += (qty * d);
            
            if (p.type === 'designer') {
                uVRAM += (qty * (parseInt(document.getElementById('vgpu_designer').value) || 0));
                uIOPS += (qty * 150); // Designer Fixed High
            } else {
                uIOPS += (qty * (parseInt(document.getElementById('iops_' + p.type).value) || 0));
            }
        });

        // 2. Hardware Config
        const selModel = document.getElementById('selModel');
        const opt = selModel.options[selModel.selectedIndex];
        const hw = {
            core: parseInt(opt.dataset.core),
            baseRam: parseInt(opt.dataset.baseRam),
            maxDisk: parseInt(opt.dataset.maxDisk)
        };

        const nodes = parseInt(document.getElementById('qtyNode').value) || 3;
        const ramAdd = parseInt(document.getElementById('selAddRam').value) || 0;
        const ramNode = hw.baseRam + ramAdd;

        const ssdSize = Math.max(0, parseInt(document.getElementById('sizeSSD').value) || 0);
        const ssdQty = Math.max(0, parseInt(document.getElementById('qtySSD').value) || 0);
        const hddSize = Math.max(0, parseInt(document.getElementById('sizeHDD').value) || 0);
        const hddQty = Math.max(0, parseInt(document.getElementById('qtyHDD').value) || 0);
        const ssdIopsPerDisk = Math.max(0, parseInt(document.getElementById('ssdIopsPerDisk').value) || 0);
        const hddIopsPerDisk = Math.max(0, parseInt(document.getElementById('hddIopsPerDisk').value) || 0);
        const sustainableIopsPct = Math.min(100, Math.max(1,
            parseFloat(document.getElementById('sustainableIopsPct').value) || 1));

        // 3. Performance Capability Logic (Estimate)
        const configCPU = hw.core * (nodes - 1);
        const configRAM = ramNode * (nodes - 1);
        const clusterRawGB = hddSize * hddQty * nodes;
        const clusterUsableTB = ((clusterRawGB / 2) * 0.85) / 1024;
        
        // Estimated sustainable IOPS is based on quantity and explicit per-disk assumptions.
        // Disk capacity cannot reliably indicate IOPS, so size is intentionally excluded.
        const rawConfiguredIOPS = nodes * ((ssdQty * ssdIopsPerDisk) + (hddQty * hddIopsPerDisk));
        const sustainableConfiguredIOPS = rawConfiguredIOPS * (sustainableIopsPct / 100);
        const cacheRatio = hddSize > 0 && hddQty > 0
            ? (ssdSize * ssdQty) / (hddSize * hddQty) * 100
            : 0;

        // 4. Update Dashboard
        document.getElementById('usageIOPS').innerText = uIOPS.toLocaleString();
        document.getElementById('configIOPS').innerText = Math.round(sustainableConfiguredIOPS).toLocaleString();
        document.getElementById('iopsFormula').innerText =
            `${nodes} nodes × ((${ssdQty} SSD × ${ssdIopsPerDisk.toLocaleString()}) + ` +
            `(${hddQty} HDD × ${hddIopsPerDisk.toLocaleString()})) × ${sustainableIopsPct}%`;
        const iopsPct = sustainableConfiguredIOPS > 0
            ? (uIOPS / sustainableConfiguredIOPS) * 100
            : (uIOPS > 0 ? Infinity : 0);
        document.getElementById('iopsBar').style.width = Math.min(iopsPct, 100) + "%";
        document.getElementById('iopsStatus').innerText = iopsPct > 100 ? "⚠ IOPS OVERLOADED" : "✓ Performance Optimized";
        document.getElementById('iopsStatus').style.color = iopsPct > 100 ? "#ef4444" : "#10b981";

        document.getElementById('cpuStatus').innerText = `${Math.ceil(uCPU*1.15)} / ${configCPU} Cores`;
        document.getElementById('cpuBar').style.width = Math.min(((uCPU*1.15)/configCPU)*100, 100) + "%";
        document.getElementById('ramStatus').innerText = `${Math.ceil(uRAM*1.15)} / ${configRAM} GB`;
        document.getElementById('ramBar').style.width = Math.min(((uRAM*1.15)/configRAM)*100, 100) + "%";

        // Table Updates
        document.getElementById('uCPU').innerText = Math.ceil(uCPU * 1.15);
        document.getElementById('uRAM').innerText = Math.ceil(uRAM * 1.15).toLocaleString() + " GB";
        document.getElementById('uVRAM').innerText = uVRAM + " GB";
        document.getElementById('uDisk').innerText = (uDiskGB / 1024).toFixed(2) + " TB";

        document.getElementById('cCPU').innerText = configCPU;
        document.getElementById('cRAM').innerText = configRAM.toLocaleString() + " GB";
        document.getElementById('cRatio').innerText = cacheRatio.toFixed(1) + "%";
        document.getElementById('cUsable').innerText = clusterUsableTB.toFixed(2) + " TB";

        // BOM Render
        document.getElementById('bomBody').innerHTML = `
            <tr class="bg-slate-50/50"><td class="p-4 font-bold">Compute Nodes</td><td class="p-4 text-right text-blue-600 font-bold">${nodes}x ${selModel.value}</td></tr>
            <tr><td class="p-4 font-bold">Memory Upgrade</td><td class="p-4 text-right">${nodes}x +${ramAdd}GB (Total ${ramNode}GB/Node)</td></tr>
            <tr class="bg-slate-50/50"><td class="p-4 font-bold">SSD Cache</td><td class="p-4 text-right">${nodes * ssdQty} Unit x ${ssdSize}GB Enterprise SSD</td></tr>
            <tr><td class="p-4 font-bold">Data Disk</td><td class="p-4 text-right">${nodes * hddQty} Unit x ${(hddSize/1000)}TB Enterprise HDD</td></tr>
        `;
    }

    window.onload = calculate;
</script>
</body>
</html>
