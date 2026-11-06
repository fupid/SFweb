<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IOcalculator v2 | Sangfor HCI</title>
    <style>
        :root{color-scheme:light;--ink:#15263e;--muted:#5b6c80;--blue:#1955ca;--line:#dfe6ef;--bg:#f3f6fb}
        *{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font:15px/1.6 -apple-system,BlinkMacSystemFont,"Segoe UI",Tahoma,sans-serif}a{color:var(--blue)}header{background:#11243e;color:white}.header-inner{max-width:1320px;margin:auto;padding:18px 28px;display:flex;align-items:center;justify-content:space-between;gap:16px}header a{color:#dbe8ff;text-decoration:none;font-size:13px}.brand{color:white;font-size:20px;font-weight:800;letter-spacing:1px}.brand span{font-size:12px;font-weight:400;letter-spacing:0;margin-left:14px;color:#bbcbe0}main{max-width:1320px;padding:32px 28px;margin:auto}.intro{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:24px}.eyebrow{font-size:11px;font-weight:750;letter-spacing:1.5px;color:var(--blue);margin:0 0 8px}h1{font-size:38px;line-height:1.15;letter-spacing:-1px;margin:0}h1 span{display:block;font-size:17px;font-weight:500;letter-spacing:0;color:var(--muted);margin-top:10px}.intro p:last-child{margin-bottom:0;color:var(--muted)}.version{padding:7px 12px;border:1px solid var(--line);border-radius:30px;background:white;font-size:12px;white-space:nowrap}.notice{border:1px solid #eacb86;border-left:4px solid #c88d21;background:#fff9ec;border-radius:10px;padding:14px 18px;margin-bottom:24px;color:#715015}.notice p{margin:3px 0 0;font-size:13px}.notice a{color:#78530b;text-underline-offset:3px}.workspace{display:grid;grid-template-columns:340px minmax(0,1fr);gap:28px;align-items:start}.panel{border:1px solid var(--line);border-radius:16px;background:white}.configuration{padding:22px}.panel-heading,.result-heading{display:flex;align-items:center;justify-content:space-between;gap:8px}h2{font-size:20px;margin:0}select,input{font:inherit}fieldset{border:0;padding:22px 0 0;margin:24px 0 0;border-top:1px solid var(--line);min-width:0}legend{font-size:14px;font-weight:700;padding:0 10px 0 0}legend span{color:var(--blue);margin-right:6px}label{display:block;font-size:13px;font-weight:600;margin:0 0 6px}label:not(:first-child){margin-top:16px}label small{font-weight:400;color:var(--muted);float:right}select,input{display:block;width:100%;min-height:44px;border:1px solid #bac8da;background:#fff;border-radius:8px;padding:9px 28px 9px 10px;color:var(--ink);font-size:13px}input[readonly]{background:#f2f5f9;color:#40536b}select:focus-visible,input:focus-visible,a:focus-visible,summary:focus-visible{outline:3px solid #7ba7f6;outline-offset:3px}.field-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:16px}.hint{font-size:12px;color:var(--muted);margin:9px 0 0}.cpu-details{background:#f1f5fc;border-radius:8px;padding:10px 12px;margin-top:12px;font-size:12px}.cpu-details strong{display:block;color:var(--blue);font-size:14px}.result-heading{margin-bottom:10px}.result-heading .eyebrow{margin-bottom:2px}.live{font-size:12px;color:#236d59}.summary{margin:0 0 18px;color:var(--muted);font-size:13px}.scope{display:flex;align-items:center;justify-content:space-between;gap:8px;margin:22px 0 12px}.scope h3{font-size:16px;margin:0}.scope span{font-size:12px;color:var(--muted)}.metric-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.metric{border:1px solid var(--line);border-radius:13px;background:white;padding:18px 20px}.metric h4{font-size:13px;color:var(--muted);font-weight:600;margin:0 0 10px}.metric .value{font-size:31px;font-weight:750;letter-spacing:-.6px;line-height:1.3;font-variant-numeric:tabular-nums}.metric .unit{display:block;font-size:12px;font-weight:500;color:var(--muted);letter-spacing:0}.secondary{font-size:12px;color:var(--muted);margin:3px 0 14px}.bottleneck{display:inline-block;background:#edf3fd;color:#254f8e;font-size:11px;padding:3px 8px;border-radius:5px}.metric.error{border-color:#eacb86}.metric.error .value{color:#926219}.result-note,.unsupported{font-size:12px;color:var(--muted);margin:16px 0 0}.unsupported{border-top:1px dashed #ccd7e6;padding-top:13px}.warning{background:#fff8e9;border:1px solid #eddbb6;border-radius:8px;padding:10px 13px;font-size:12px;color:#785415;margin-bottom:10px}.method{margin-top:30px;padding:24px}.method>p{font-size:13px;color:var(--muted);max-width:960px}details{border-top:1px solid var(--line);padding:14px 0}details:last-child{padding-bottom:0}summary{font-size:14px;font-weight:600;cursor:pointer}details li{font-size:13px;margin:8px 0;color:var(--muted)}details ul{padding-left:23px}.table-wrap{overflow-x:auto}table{border-collapse:collapse;width:100%;font-size:12px;margin-top:12px}caption{text-align:left;color:var(--muted);padding-bottom:8px}th,td{text-align:right;padding:10px;border-bottom:1px solid var(--line);white-space:nowrap;font-variant-numeric:tabular-nums}th:first-child,td:first-child{text-align:left}th{background:#f3f6fb}footer{display:flex;justify-content:space-between;gap:12px;font-size:12px;color:var(--muted);padding:24px 0 0}.results{min-width:0}
        @media(min-width:1100px){.configuration{position:sticky;top:20px}}@media(max-width:960px){.workspace{grid-template-columns:300px minmax(0,1fr);gap:18px}.metric{padding:15px}.metric .value{font-size:26px}.version{display:none}}@media(max-width:740px){main{padding:24px 16px}.header-inner{padding:15px 16px}.brand span{display:none}.workspace{grid-template-columns:1fr}.intro{margin-bottom:20px}h1{font-size:34px}.configuration{padding:18px}.metric .value{font-size:24px}.method{padding:18px}.scope{margin-top:20px}footer{display:block}footer span{display:block}}@media(max-width:380px){.metric-grid{grid-template-columns:1fr}.live{font-size:10px}}
    </style>
</head>
<body>
<header><div class="header-inner"><a href="index.html" class="brand">SANGFOR <span>Presale Tools</span></a><a href="index.html">← Back to Tools</a></div></header>
<main>
    <div class="intro"><div><p class="eyebrow">HYPER-CONVERGED INFRASTRUCTURE</p><h1>IOcalculator v2<span>HCI IOPS & Throughput</span></h1><p>Select a system configuration to view estimated Virtual Disk and Cluster performance.</p></div><span class="version">Corrected formula model · v2</span></div>
    <aside class="notice"><strong>Formula model rebuilt from the workbook descriptions</strong><p>CPU model, VM mode, host count, cache and data disk ratio, network, and copy count now affect the applicable limits. The original workbook contains incomplete and inconsistent formulas, so this version states every correction and assumption below. <a href="#method">View the calculation method ↓</a></p></aside>
    <noscript><p class="notice">JavaScript must be enabled to use this calculator.</p></noscript>
    <div class="workspace">
        <form id="config" class="panel configuration">
            <div class="panel-heading"><h2>System Configuration</h2></div>
            <fieldset><legend><span>01</span> Server & CPU</legend>
                <label for="hosts">Number of Hosts</label><select id="hosts" name="hosts"></select>
                <label for="model">Server Model <small>PDF reference</small></label><select id="model" name="model" aria-describedby="cpu-details"><option value="">No model selected</option></select>
                <div id="cpu-details" class="cpu-details">Select a server model to view its CPU and socket count.</div>
                <label for="cpu-ratio">CPU Performance Ratio <small>Excel L4:M18</small></label><input id="cpu-ratio" name="cpuRatio" type="number" min="0.1" max="5" step="0.01" inputmode="decimal">
                <p id="ratio-hint" class="hint">Known CPU ratios are filled automatically. Enter a ratio when the workbook does not provide one.</p>
            </fieldset>
            <fieldset><legend><span>02</span> Storage · Per Host</legend>
                <label for="disk">Cache SSD interface</label><select id="disk" name="disk"><option>SATA SSD</option><option>NVMe SSD</option></select>
                <div class="field-grid"><div><label for="cache">Cache disks</label><select id="cache" name="cache"></select></div><div><label for="data">Data disks</label><select id="data" name="data" aria-describedby="data-hint"></select></div></div>
                <p id="data-hint" class="hint">Data disks = 0 represents All-flash. Hybrid disk performance is reduced linearly below the recommended 1:3 Cache-to-Data ratio.</p>
            </fieldset>
            <fieldset><legend><span>03</span> Network & VM</legend>
                <label for="network">Storage network</label><select id="network" name="network"></select>
                <div class="field-grid"><div><label for="copies">Data copies</label><select id="copies" name="copies"><option value="2">2 copies</option><option value="3">3 copies</option></select></div><div><label for="mode">VM mode</label><select id="mode" name="mode"><option>Normal mode</option><option>Turbo Mode</option></select></div></div>
                <p class="hint">Write calculations use the selected copy count. Network replication overhead is divided by Copies − 1, following the workbook's described 2/3-copy behavior.</p>
            </fieldset>
        </form>
        <section class="results" aria-labelledby="results-heading">
            <div class="result-heading"><div><p class="eyebrow">ESTIMATED PERFORMANCE</p><h2 id="results-heading">Calculation Results</h2></div><span class="live">● Live calculation</span></div>
            <p id="config-summary" class="summary" aria-live="polite"></p>
            <div id="warnings" role="status"></div>
            <div class="scope"><h3>Cluster · All Hosts</h3><span id="host-count"></span></div><div id="cluster-results" class="metric-grid"></div>
            <div class="scope"><h3>Single Virtual Disk</h3><span>1 VM · 1 Virtual Disk</span></div><div id="single-results" class="metric-grid"></div>
            <p class="result-note">Both IOPS and MB/s are shown. Values are rounded for display only. The workbook states that actual performance may vary by approximately 20%; validate the design with measured results.</p>
            <p class="unsupported">Mixed workloads—4 KB Read/Write 70/30 and 1 MB Read/Write 50/50—<strong>have no formulas in the workbook</strong>, so no estimates are shown.</p>
        </section>
    </div>
    <section id="method" class="panel method"><h2>Calculation Method and Scope</h2>
        <p>Each result is the lowest applicable CPU, Disk, and Network limit. Version 2 rebuilds the inputs described in column H instead of preserving the workbook's fixed CPU cells and copy-count text error.</p>
        <details><summary>Inspect Bottlenecks and Source Cells</summary><div class="table-wrap"><table><caption>Units by test type: 4 KB = IOPS · 1 MB = MB/s</caption><thead><tr><th>Result / Row</th><th>CPU (E)</th><th>Disk (F)</th><th>Network (G)</th><th>MIN (C/D)</th></tr></thead><tbody id="limits"></tbody></table></div></details>
        <details open><summary>Version 2 Formula Corrections</summary><ul>
            <li>CPU limit = CPU performance ratio × the Normal or Turbo baseline. Cluster CPU limits are also multiplied by the host count, following the descriptions in column H.</li>
            <li>Single Virtual Disk uses the workbook's Single baseline rows. Cluster uses its Cluster baseline rows. CPU socket count is informational and is not an additional multiplier.</li>
            <li>Cluster write Disk limit is divided by the selected copy count. Sequential write Network limit is divided by Copies − 1. This repairs the source formula's Chinese/English text mismatch.</li>
            <li>For hybrid storage, Disk limit factor = MIN(1, Data disks ÷ (Cache disks × 3)). This is a transparent linear assumption because the workbook asks for a reduction below 1:3 but does not provide the reduction curve. All-flash, where Data disks = 0, uses a factor of 1.</li>
            <li>25G Single port / Master–slave still has no reference throughput in the workbook. Sequential write remains unavailable rather than using an invented value.</li>
            <li>Gold 6526Y, Gold 6530, and Gold 6548N use the matching ratios in Excel L4:M18. Silver 4510 and AMD models have no matching ratio and require a manual CPU ratio. No ratio is inferred from cores, GHz, or sockets.</li>
        </ul></details>
        <details><summary>Test Conditions and Sources</summary><ul>
            <li>Formulas: Sangfor HCI IOPS Calculator (2025.03.06).xlsx · Sheet “ x86-IO performance calculation” · Inputs B3:B10 · Results C27:G37 · Baselines K39:Q53</li>
            <li>CPU: sangfor-hci-cpu-models-datasheet.pdf · Intel page 1 / AMD page 2</li>
            <li>SSD references: SATA Intel S4610 and NVMe Intel P5620</li>
            <li>HCI 6.10 or later · VM with 8 vCPU / 8 GB RAM · 80 GB system disk / 20 GB preallocated test disk</li>
            <li>Iometer on Windows or Fio on Linux · IO depth 128 · 20 GB test range · 3–5 minutes per metric; Cluster tests distribute VMs evenly with at least 2 VMs per host.</li>
            <li>4 KB: MB/s = IOPS × 4 ÷ 1024; 1 MB: IOPS = MB/s, following the workbook's formulas and unit labels.</li>
            <li>Initial values use aServer-X5-1200 with the Excel ratio for Gold 6526Y, plus 3 Hosts, NVMe, 5 Cache / 10 Data disks, 10G aggregation, 3 copies, and Normal mode.</li>
        </ul></details>
    </section>
    <footer>IOcalculator v2 · Sangfor HCI <span>Corrected formula model</span></footer>
</main>
<script id="calculator-engine">
(function (root) {
    'use strict';
    const models = [
        {id:'aServer-X5-1200', cpu:'Intel Xeon Gold 6526Y', ghz:2.8, cores:16, sockets:1, page:1, ratio:1.44},
        {id:'aServer-X5-2105', cpu:'Intel Xeon Silver 4510', ghz:2.4, cores:12, sockets:2, page:1, ratio:null},
        {id:'aServer-X5-2205', cpu:'Intel Xeon Gold 6526Y', ghz:2.8, cores:16, sockets:2, page:1, ratio:1.44},
        {id:'aServer-X5-2305P', cpu:'Intel Xeon Gold 6530', ghz:2.1, cores:32, sockets:2, page:1, ratio:1.49},
        {id:'aServer-X5-2305P(12N)', cpu:'Intel Xeon Gold 6530', ghz:2.1, cores:32, sockets:2, page:1, ratio:1.49},
        {id:'aServer-X5-2205F', cpu:'Intel Xeon Gold 6526Y', ghz:2.8, cores:16, sockets:2, page:1, ratio:1.44},
        {id:'aServer-X5-2405', cpu:'Intel Xeon Gold 6548N', ghz:2.8, cores:32, sockets:2, page:1, ratio:1.5},
        {id:'aServer-X5-G2200', cpu:'Intel Xeon Gold 6526Y', ghz:2.8, cores:16, sockets:2, page:1, ratio:1.44},
        {id:'aServer-Z4-2405', cpu:'AMD Genoa 9354', ghz:3.25, cores:32, sockets:2, page:2, ratio:null},
        {id:'aServer-Z4-2605', cpu:'AMD Genoa 9554', ghz:3.1, cores:64, sockets:2, page:2, ratio:null}
    ];
    const disks = {
        'SATA SSD': {read:80000, write:75000, readMB:530, writeMB:470},
        'NVMe SSD': {read:700000, write:200000, readMB:3000, writeMB:1900}
    };
    const networks = {
        '10g-single': {label:'10G · Single port / Master–slave', mb:1100},
        '10g-lag': {label:'10G · Layer 4 aggregation', mb:2200},
        '25g-single': {label:'25G · Single port / Master–slave (no reference value)', mb:null},
        '25g-lag': {label:'25G · Layer 4 aggregation', mb:5500}
    };
    const baselines = {
        'Normal mode': {single:[222000,113000,4100,4000], cluster:[1050000,300000,24000,24000]},
        'Turbo Mode': {single:[287000,186000,8100,8000], cluster:[1650000,445000,33000,33000]}
    };
    const defaults = {hosts:3, cache:5, disk:'NVMe SSD', data:10, network:'10g-lag', copies:3, mode:'Normal mode', model:'aServer-X5-1200', cpuRatio:1.44};
    function calculate(input) {
        for (const [key,min,max] of [['hosts',3,12],['cache',1,12],['data',0,12]]) {
            if (!Number.isInteger(input[key]) || input[key]<min || input[key]>max) throw new Error(`Invalid ${key}`);
        }
        if (!Object.hasOwn(disks,input.disk) || !Object.hasOwn(networks,input.network) || ![2,3].includes(input.copies)
            || !Object.hasOwn(baselines,input.mode)) throw new Error('Invalid configuration');
        if (input.cpuRatio!==null && (!Number.isFinite(input.cpuRatio) || input.cpuRatio<0.1 || input.cpuRatio>5)) throw new Error('Invalid CPU ratio');
        const d=disks[input.disk], n=networks[input.network].mb, h=input.hosts, c=input.cache;
        const ratio=input.cpuRatio, baseline=baselines[input.mode];
        const diskFactor=input.data===0 ? 1 : Math.min(1,input.data/(c*3));
        const limits = [
            ['single-read4k',27,'single','4 KB · Random read',ratio===null?null:baseline.single[0]*ratio,c*d.read*0.9*diskFactor,null,true],
            ['single-write4k',28,'single','4 KB · Random write',ratio===null?null:baseline.single[1]*ratio,c*d.write*0.9*diskFactor,null,true],
            ['single-read1m',29,'single','1 MB · Sequential read',ratio===null?null:baseline.single[2]*ratio,c*d.readMB*diskFactor,null,false],
            ['single-write1m',31,'single','1 MB · Sequential write',ratio===null?null:baseline.single[3]*ratio,c*d.writeMB*diskFactor,n===null?null:n/(input.copies-1),false],
            ['cluster-read4k',32,'cluster','4 KB · Random read',ratio===null?null:baseline.cluster[0]*ratio*h,h*c*d.read*0.9*diskFactor,null,true],
            ['cluster-write4k',34,'cluster','4 KB · Random write',ratio===null?null:baseline.cluster[1]*ratio*h,d.write*c*h/input.copies*0.9*diskFactor,null,true],
            ['cluster-read1m',35,'cluster','1 MB · Sequential read',ratio===null?null:baseline.cluster[2]*ratio*h,c*h*d.readMB*diskFactor,null,false],
            ['cluster-write1m',37,'cluster','1 MB · Sequential write',ratio===null?null:baseline.cluster[3]*ratio*h,d.writeMB*h*c/input.copies*diskFactor,n===null?null:n*h/(input.copies-1),false]
        ];
        return limits.map(([id,row,scope,label,cpu,disk,network,small]) => {
            const error = ratio===null ? 'CPU ratio required' : id.endsWith('write1m') && n===null ? '#N/A' : null;
            const value = error ? null : Math.min(cpu,disk,...(network===null?[]:[network]));
            const bottlenecks = error ? [] : Object.entries({CPU:cpu,Disk:disk,Network:network}).filter(([,v])=>v!==null && v===value).map(([k])=>k);
            return {id,row,scope,label,cpu,disk,network,small,error,value,bottlenecks,
                iops:value, mb:value===null?null:(small?value*4/1024:value)};
        });
    }
    root.IOCalculator={models,disks,networks,baselines,defaults,calculate};
})(window);
</script>
<script id="calculator-ui">
(function () {
    'use strict';
    const api=window.IOCalculator, form=document.getElementById('config');
    const el=id=>document.getElementById(id);
    const fmt=n=>new Intl.NumberFormat('en-US',{maximumFractionDigits:2}).format(n);
    const show=n=>n===null?'N/A':fmt(n);
    for (const [id,start,end] of [['hosts',3,12],['cache',1,12],['data',0,12]]) {
        for (let n=start;n<=end;n++) el(id).add(new Option(id==='data'&&n===0?'0 · All-flash':String(n),String(n)));
    }
    api.models.forEach(m=>el('model').add(new Option(m.id,m.id)));
    Object.entries(api.networks).forEach(([id,n])=>el('network').add(new Option(n.label,id)));
    function setModelRatio() {
        const model=api.models.find(m=>m.id===el('model').value), ratio=el('cpu-ratio');
        if (model && model.ratio!==null) {
            ratio.value=String(model.ratio); ratio.readOnly=true; ratio.placeholder='';
            el('ratio-hint').textContent='Ratio populated from the matching CPU entry in Excel L4:M18.';
        } else {
            ratio.value=''; ratio.readOnly=false; ratio.placeholder='Required';
            el('ratio-hint').textContent='No matching ratio exists in the workbook. Enter a validated CPU performance ratio to calculate results.';
        }
    }
    function render() {
        const config=Object.fromEntries(new FormData(form));
        for (const key of ['hosts','cache','data','copies']) config[key]=Number(config[key]);
        config.cpuRatio=config.cpuRatio===''?null:Number(config.cpuRatio);
        const rows=api.calculate(config), model=api.models.find(m=>m.id===config.model);
        el('cpu-details').replaceChildren();
        if (model) {
            const name=document.createElement('strong'); name.textContent=model.cpu;
            el('cpu-details').append(name,`${model.sockets} Socket × ${model.cores} Core · ${model.ghz} GHz · PDF page ${model.page}${model.ratio===null?' · Manual ratio required':' · Excel ratio '+model.ratio}`);
        } else el('cpu-details').textContent='Select a server model to view its CPU and socket count.';
        el('config-summary').textContent=`${config.hosts} Hosts · ${config.disk} · ${config.cache} Cache / ${config.data} Data per Host · ${config.copies} copies · ${config.mode}${model?' · '+model.id:''}${config.cpuRatio===null?' · CPU ratio required':' · CPU ratio '+config.cpuRatio}`;
        el('host-count').textContent=`${config.hosts} Hosts`;
        const warnings=[];
        if (config.network==='25g-single') warnings.push('25G Single port: the workbook has no lookup value. Sequential write returns #N/A for both Single Virtual Disk and Cluster.');
        if (config.cpuRatio===null) warnings.push('CPU performance ratio is required for this server model because the workbook has no matching CPU ratio. Results remain unavailable until a validated ratio is entered.');
        if (config.data>0 && config.data<config.cache*3) warnings.push(`The Cache-to-Data ratio is below 1:3. Version 2 applies a linear Disk limit factor of ${fmt(config.data/(config.cache*3)*100)}%.`);
        el('warnings').replaceChildren(...warnings.map(text=>{const p=document.createElement('p');p.className='warning';p.textContent=text;return p;}));
        for (const scope of ['cluster','single']) {
            el(`${scope}-results`).innerHTML=rows.filter(r=>r.scope===scope).map(r=>`<article class="metric${r.error?' error':''}" data-metric="${r.id}"><h4>${r.label}</h4><div class="value">${r.error||fmt(r.small?r.iops:r.mb)}${r.error?'':`<span class="unit">${r.small?'IOPS':'MB/s'}</span>`}</div><p class="secondary">${r.error==='CPU ratio required'?'Enter a validated CPU performance ratio':r.error?'No Network value in the workbook lookup table':fmt(r.small?r.mb:r.iops)+' '+(r.small?'MB/s':'IOPS')}</p><span class="bottleneck">${r.error||r.bottlenecks.join(' + ')+' limit'}</span></article>`).join('');
        }
        el('limits').innerHTML=rows.map(r=>`<tr><td>${r.scope==='cluster'?'Cluster':'Single'} · ${r.label} (${r.row})</td><td>${show(r.cpu)}</td><td>${show(r.disk)}</td><td>${r.id.endsWith('write1m')&&config.network==='25g-single'?'#N/A':r.network===null?'N/A · Unrestricted':fmt(r.network)}</td><td>${r.error||fmt(r.value)}</td></tr>`).join('');
    }
    function initialize() {Object.entries(api.defaults).forEach(([key,value])=>el(key==='cpuRatio'?'cpu-ratio':key).value=String(value));setModelRatio();render();}
    form.addEventListener('change',event=>{if(event.target.id==='model')setModelRatio();render();});
    el('cpu-ratio').addEventListener('input',render);
    form.addEventListener('submit',event=>event.preventDefault());
    initialize();
})();
</script>
</body>
</html>
