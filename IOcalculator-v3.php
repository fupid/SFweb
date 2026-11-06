<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>IOcalculator v3 | Sangfor HCI</title>
<style>
:root{color-scheme:light;--navy:#12253e;--blue:#1b58c7;--ink:#17283f;--muted:#607187;--line:#dce5f0;--bg:#f3f6fb;--green:#16735b;--amber:#94630d;--red:#a43b3b}*{box-sizing:border-box}html,body{max-width:100%;overflow-x:hidden}body{margin:0;background:var(--bg);color:var(--ink);font:14px/1.55 -apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif}a{color:var(--blue)}header{background:var(--navy);color:#fff}.topbar{max-width:1380px;margin:auto;padding:17px 28px;display:flex;align-items:center;justify-content:space-between}.brand{color:#fff;text-decoration:none;font-size:19px;font-weight:800;letter-spacing:1px}.brand span{margin-left:12px;color:#b9c9db;font-size:12px;font-weight:500;letter-spacing:0}.back{color:#d7e4f5;text-decoration:none;font-size:12px}main{max-width:1380px;margin:auto;padding:30px 28px 40px}.hero{display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:22px}.eyebrow{margin:0 0 5px;color:var(--blue);font-size:10px;font-weight:800;letter-spacing:1.5px}.hero h1{margin:0;font-size:36px;line-height:1.15;letter-spacing:-1px}.hero h1 span{display:block;margin-top:7px;color:var(--muted);font-size:16px;font-weight:500;letter-spacing:0}.hero p:last-child{margin:10px 0 0;color:var(--muted)}.badge{padding:7px 12px;border:1px solid var(--line);border-radius:999px;background:#fff;color:var(--muted);font-size:11px;white-space:nowrap}.notice{margin-bottom:22px;padding:13px 17px;border:1px solid #e9ca8b;border-left:4px solid #d29324;border-radius:10px;background:#fff9ed;color:#755211}.notice p{margin:3px 0 0;font-size:12px}.layout{display:grid;grid-template-columns:350px minmax(0,1fr);gap:25px;align-items:start}.panel{border:1px solid var(--line);border-radius:15px;background:#fff}.controls{padding:21px}.panel-title{margin:0 0 17px;font-size:19px}.mode-tabs{display:grid;grid-template-columns:1fr 1fr;padding:3px;border-radius:9px;background:#edf2f8}.mode-tabs label{margin:0}.mode-tabs input{position:absolute;opacity:0;pointer-events:none}.mode-tabs span{display:block;padding:9px 7px;border-radius:7px;text-align:center;color:var(--muted);font-size:12px;font-weight:650;cursor:pointer}.mode-tabs input:checked+span{background:#fff;color:var(--blue);box-shadow:0 1px 5px #bdcadb}.group{margin:22px 0 0;padding-top:18px;border-top:1px solid var(--line)}.group h3{display:flex;align-items:center;margin:0 0 15px;font-size:13px}.group h3 b{margin-right:7px;color:var(--blue)}label{display:block;margin:13px 0 5px;font-size:12px;font-weight:650}label small{float:right;color:var(--muted);font-weight:400}select,input{width:100%;min-height:42px;padding:8px 10px;border:1px solid #b9c9dc;border-radius:8px;background:#fff;color:var(--ink);font:inherit}select:focus-visible,input:focus-visible,a:focus-visible,summary:focus-visible{outline:3px solid #78a7f3;outline-offset:2px}.two{display:grid;grid-template-columns:1fr 1fr;gap:11px}.hint{margin:7px 0 0;color:var(--muted);font-size:11px}.reference{margin-top:10px;padding:10px;border-radius:8px;background:#eff4fb;color:#4c6078;font-size:11px}.reference strong{display:block;color:var(--blue);font-size:12px}.workload-inputs[hidden]{display:none}.results{min-width:0}.results-header{display:flex;justify-content:space-between;align-items:flex-end;gap:15px}.results-header h2{margin:0;font-size:21px}.live{color:var(--green);font-size:11px}.summary{margin:8px 0 15px;color:var(--muted);font-size:12px}.warnings{display:grid;gap:8px;margin-bottom:15px}.warning,.error-note{margin:0;padding:10px 12px;border-radius:8px;background:#fff8ea;border:1px solid #ecd9b0;color:#775310;font-size:11px}.error-note{background:#fff1f1;border-color:#ecc5c5;color:#8a3535}.kpis{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin:15px 0}.kpi{padding:17px;border:1px solid var(--line);border-radius:12px;background:#fff}.kpi h3{margin:0;color:var(--muted);font-size:11px;font-weight:650}.kpi strong{display:block;margin-top:5px;font-size:27px;line-height:1.25;font-variant-numeric:tabular-nums}.kpi small{color:var(--muted)}.status{display:inline-block;margin-top:7px;padding:3px 8px;border-radius:5px;background:#edf3fd;color:#31588e;font-size:10px}.status.good{background:#eaf7f2;color:var(--green)}.status.bad{background:#fff0f0;color:var(--red)}.status.warn{background:#fff5df;color:var(--amber)}.table-panel{overflow:hidden}.table-scroll{overflow-x:auto}table{width:100%;border-collapse:collapse;font-size:12px}caption{text-align:left;padding:16px 17px 8px;color:var(--muted)}th,td{padding:11px 12px;border-bottom:1px solid var(--line);text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums}th{background:#eef3f9;color:#4b5f78;font-size:10px;text-transform:uppercase;letter-spacing:.5px}th:first-child,td:first-child{text-align:left}tbody tr:last-child td{border-bottom:0}.metric-name strong{display:block}.metric-name small{color:var(--muted)}.limit{display:inline-block;padding:3px 7px;border-radius:5px;background:#eef4fd;color:#31598e;font-size:10px}.fit-card{padding:20px}.fit-card h3{margin:0 0 4px}.fit-card>p{margin:0;color:var(--muted)}.fit-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-top:16px}.fit-cell{padding:13px;background:#f5f8fc;border-radius:9px}.fit-cell span{display:block;color:var(--muted);font-size:10px;text-transform:uppercase;letter-spacing:.5px}.fit-cell strong{font-size:17px}.method{margin-top:25px;padding:21px}.method h2{margin:0 0 8px;font-size:19px}.method>p{margin:0 0 10px;color:var(--muted);font-size:12px}details{padding:12px 0;border-top:1px solid var(--line)}summary{font-weight:650;cursor:pointer}details ul{margin:9px 0 0;padding-left:20px;color:var(--muted);font-size:12px}details li{margin:6px 0}code{padding:2px 5px;border-radius:4px;background:#eef2f7;font-size:11px}footer{display:flex;justify-content:space-between;margin-top:20px;color:var(--muted);font-size:11px}@media(min-width:1080px){.controls{position:sticky;top:16px}}@media(max-width:900px){.layout{grid-template-columns:310px minmax(0,1fr)}.kpis{grid-template-columns:1fr}.fit-grid{grid-template-columns:1fr 1fr}}@media(max-width:720px){main{padding:23px 14px}.topbar{padding:14px}.brand span,.badge{display:none}.layout{grid-template-columns:1fr}.hero h1{font-size:31px}.controls{padding:18px}.results{margin-top:4px}.results-header{align-items:center}.fit-grid{grid-template-columns:1fr 1fr}footer{display:block}footer span{display:block}}@media(max-width:380px){.two,.fit-grid{grid-template-columns:1fr}.mode-tabs{grid-template-columns:1fr}}
.mode-tabs input{width:1px;height:1px;min-height:1px;margin:0}
.table-scroll{max-width:100%;contain:inline-size paint}
</style>
</head>
<body>
<header><div class="topbar"><a class="brand" href="index.html">SANGFOR <span>Presale Tools</span></a><a class="back" href="index.html">← Back to Tools</a></div></header>
<main>
<section class="hero"><div><p class="eyebrow">HYPER-CONVERGED INFRASTRUCTURE</p><h1>IOcalculator v3<span>Evidence-based Cluster Performance Estimator</span></h1><p>Estimate standard cluster capability or compare a known workload with safe Healthy and N−1 capacity.</p></div><span class="badge">HCI 6.10 benchmark model</span></section>
<aside class="notice"><strong>Measured baseline with explicit assumptions</strong><p>The source benchmark does not record its host count or data-copy policy. Version 3 assumes a 6-host reference cluster and treats measured results as already including storage overhead. Review proposals with a 3-copy policy; no copy multiplier is added to performance.</p></aside>
<div class="layout">
<form id="config" class="panel controls">
<h2 class="panel-title">Estimate Configuration</h2>
<div class="mode-tabs" role="radiogroup" aria-label="Calculation mode">
<label><input type="radio" name="calculationMode" value="capability" checked><span>Cluster Capability</span></label>
<label><input type="radio" name="calculationMode" value="workload"><span>Workload Fit</span></label>
</div>
<section class="group"><h3><b>01</b> Cluster</h3>
<div class="two"><div><label for="hosts">Solution Hosts</label><select id="hosts" name="hosts"></select></div><div><label for="storage">Storage Profile</label><select id="storage" name="storage"><option value="hybrid">NVMe + HDD</option><option value="allnvme">All-NVMe*</option></select></div></div>
<label for="cpu">CPU Model <small>ratio vs Gold 6226R</small></label><select id="cpu" name="cpu"></select>
<div id="cpu-info" class="reference"></div>
</section>
<section class="group"><h3><b>02</b> Software and Network</h3>
<div class="two"><div><label for="performanceMode">Performance Mode</label><select id="performanceMode" name="performanceMode"><option value="normal">Normal</option><option value="turbo">Turbo</option></select></div><div><label for="rdma">RDMA</label><select id="rdma" name="rdma"><option value="off">Disabled</option><option value="on">Enabled</option></select></div></div>
<label for="network">Storage Network <small>usable per Host</small></label><select id="network" name="network"><option value="1100">10G · 1,100 MB/s per Host</option><option value="2200">10G LACP · 2,200 MB/s per Host</option><option value="5500" selected>25G LACP · 5,500 MB/s per Host</option><option value="unknown">25G Single · no source value</option></select>
<p class="hint">The network ceiling scales with active Hosts. It can reduce the benchmark estimate but never increase it.</p>
</section>
<section class="group"><h3><b>03</b> Safety Policy</h3>
<label for="safety">Design Ceiling</label><select id="safety" name="safety"><option value="0.60">Conservative · 60% of estimate</option><option value="0.70">Standard · 70% of estimate</option><option value="0.80">Lab-aligned · 80% of estimate</option></select>
<details><summary>Advanced assumptions</summary>
<div class="two"><div><label for="referenceHosts">Reference Hosts</label><input id="referenceHosts" name="referenceHosts" type="number" min="3" max="24" step="1" value="6"></div><div><label for="scaling">Scale Efficiency</label><input id="scaling" name="scaling" type="number" min="50" max="100" step="1" value="90"></div></div>
<p class="hint">Scale efficiency applies when the active host count differs from the assumed reference cluster.</p>
</details>
</section>
<section id="workload-inputs" class="group workload-inputs" hidden><h3><b>04</b> Customer Workload</h3>
<label for="workload">Workload Profile</label><select id="workload" name="workload"></select>
<div class="two"><div><label for="required">Required <small id="required-unit">IOPS</small></label><input id="required" name="required" type="number" min="0" step="1" value="100000"></div><div><label for="growth">Growth</label><input id="growth" name="growth" type="number" min="0" max="500" step="1" value="20"></div></div>
<label for="latency">Maximum Average Latency <small>ms</small></label><input id="latency" name="latency" type="number" min="0.1" max="1000" step="0.1" value="5">
<p class="hint">The source contains average latency, not p95/p99 latency. The tool flags an unvalidated target but does not interpolate latency.</p>
</section>
</form>
<section class="results" aria-labelledby="results-title">
<div class="results-header"><div><p class="eyebrow">ESTIMATED PERFORMANCE</p><h2 id="results-title">Cluster Capability</h2></div><span class="live">● Live calculation</span></div>
<p id="summary" class="summary" aria-live="polite"></p><div id="warnings" class="warnings" role="status"></div>
<div id="kpis" class="kpis"></div>
<div id="capability" class="panel table-panel"><div class="table-scroll"><table><caption>Six workload profiles are shown when no customer workload is available.</caption><thead><tr><th>Workload</th><th>Measured Reference</th><th>Raw Estimate</th><th>Safe Healthy</th><th>Safe N−1</th><th>Peak Avg. Latency</th><th>Limit</th></tr></thead><tbody id="capability-body"></tbody></table></div></div>
<div id="fit" class="panel fit-card" hidden></div>
</section>
</div>
<section class="panel method"><h2>Model Basis and Limitations</h2><p>Version 3 uses measured HCI 6.10 results as the primary baseline. The two older calculators provide context for workload categories and per-VM/per-node guardrails, but are not treated as independent validation.</p>
<details open><summary>Calculation Summary</summary><ul>
<li>Reference values are the highest measured result for each workload and mode across the available VM, virtual-disk, queue-depth, and thread combinations.</li>
<li><code>Scaled benchmark = Reference × Hosts ÷ Reference hosts × CPU ratio × Scale efficiency</code>. Scale efficiency is 100% at the reference host count and applies only during host-count extrapolation.</li>
<li><code>Network ceiling = Usable MB/s per Host × Active Hosts</code>, converted by block size for IOPS workloads. It can reduce the estimate but never increase the measured benchmark.</li>
<li><code>Safe Healthy = Raw estimate × Safety factor</code>. N−1 is recalculated with one fewer active host, then the same safety factor is applied.</li>
<li>Faster CPU ratios can extrapolate above the measured Gold 6226R baseline. This lowers confidence and must be validated with a matching benchmark.</li>
</ul></details>
<details><summary>Source Quality</summary><ul>
<li>Primary source: HCI 6.10.0 performance data 202404.xlsx. Reference hardware: Gold 6226R, 512 GB RAM, P5620 NVMe, 25G LACP, HCI 6.10.0, and 8-vCPU/8-GB CentOS 7.9 test VMs.</li>
<li>The source does not state the number of cluster hosts, copy count, test duration, p95 latency, or failure-state performance.</li>
<li>Per-Host network coefficients come from the older calculators and act only as screening ceilings. Validate them when the proposed NIC, bonding, or traffic path differs.</li>
<li>The sheet named “all nvme” contains the same “2 × NVMe + 6 × 4TB HDD” configuration text as the hybrid sheet. All-NVMe results remain selectable but are marked as requiring source validation.</li>
<li>Average latency shown here belongs to the measured peak record. Reducing IOPS with a safety factor does not prove that a particular latency target will be met.</li>
</ul></details>
<details><summary>When a Result Must Not Be Used</summary><ul>
<li>Do not present the estimate as a guaranteed customer result.</li><li>Do not combine IOPS profiles with different block sizes or read/write mixes.</li><li>Do not use an unavailable CPU ratio or Network coefficient as zero.</li><li>Confirm the reference host count before using Host scaling in a proposal.</li><li>Use a matching POC or measured benchmark for latency-sensitive workloads.</li>
</ul></details>
</section>
<footer>IOcalculator v3 · Sangfor HCI <span>Evidence-based estimate · HCI 6.10 source</span></footer>
</main>
<script id="calculator-engine">
(function(root){'use strict';
const profiles={
 write4k:{label:'4 KB · 100% Random Write',unit:'IOPS',blockKB:4},mixed4k:{label:'4 KB · 70% Read / 30% Write',unit:'IOPS',blockKB:4},read4k:{label:'4 KB · 100% Random Read',unit:'IOPS',blockKB:4},
 write1m:{label:'1 MB · 100% Sequential Write',unit:'MB/s',blockKB:1024},mixed1m:{label:'1 MB · 50% Read / 50% Write',unit:'MB/s',blockKB:1024},read1m:{label:'1 MB · 100% Sequential Read',unit:'MB/s',blockKB:1024}
};
const P=(value,latency)=>({value,latency});
const benchmarks={
 allnvme:{
  turbo_rdma:{write4k:P(505213,.77),mixed4k:P(882415,1.74),read4k:P(1551322,.99),write1m:P(9685,40.59),mixed1m:P(19304,20.36),read1m:P(31204,12.61)},
  turbo_standard:{write4k:P(428969,3.58),mixed4k:P(806118,1.91),read4k:P(1570016,.98),write1m:P(4529,86.92),mixed1m:P(8445,46.61),read1m:P(31655,12.41)},
  normal_standard:{write4k:P(326371,10.58),mixed4k:P(577218,5.98),read4k:P(1138124,3.09),write1m:P(4628,191.06),mixed1m:P(8673,102.23),read1m:P(23561,37.36)},
  normal_rdma:{write4k:P(339640,10.16),mixed4k:P(601022,5.73),read4k:P(1122967,3.07),write1m:P(8353,105.95),mixed1m:P(16756,52.81),read1m:P(23252,38.43)}},
 hybrid:{
  turbo_rdma:{write4k:P(445092,3.45),mixed4k:P(975139,1.57),read4k:P(1619207,.95),write1m:P(5952,66.03),mixed1m:P(9052,43.31),read1m:P(19305,20.38)},
  turbo_standard:{write4k:P(445869,3.45),mixed4k:P(888867,1.75),read4k:P(1650245,.93),write1m:P(5692,69.16),mixed1m:P(9104,10.75),read1m:P(19300,20.37)},
  normal_standard:{write4k:P(339234,10.19),mixed4k:P(530158,6.51),read4k:P(1095337,3.11),write1m:P(5810,152.46),mixed1m:P(8933,10.79),read1m:P(17356,51)},
  normal_rdma:{write4k:P(367150,9.41),mixed4k:P(596259,5.8),read4k:P(1098853,3.14),write1m:P(5773,17.15),mixed1m:P(8914,11.01),read1m:P(17662,50.1)}}};
const cpus=[
 {id:'gold6226r',label:'Intel Xeon Gold 6226R · benchmark reference',ratio:1,source:'HCI 6.10 benchmark'},
 {id:'gold6526y',label:'Intel Xeon Gold 6526Y',ratio:1.44,source:'Excel CPU ratio table'},
 {id:'gold6530',label:'Intel Xeon Gold 6530',ratio:1.49,source:'Excel CPU ratio table'},
 {id:'gold6548n',label:'Intel Xeon Gold 6548N',ratio:1.5,source:'Excel CPU ratio table'},
 {id:'silver4510',label:'Intel Xeon Silver 4510 · ratio unavailable',ratio:null,source:'CPU datasheet only'},
 {id:'amd9354',label:'AMD Genoa 9354 · ratio unavailable',ratio:null,source:'CPU datasheet only'},
 {id:'amd9554',label:'AMD Genoa 9554 · ratio unavailable',ratio:null,source:'CPU datasheet only'}];
function modeKey(input){return `${input.performanceMode}_${input.rdma==='on'?'rdma':'standard'}`}
function calculate(input){
 if(!Number.isInteger(input.hosts)||input.hosts<3||input.hosts>12)throw Error('Invalid host count');
 if(!Number.isInteger(input.referenceHosts)||input.referenceHosts<3||input.referenceHosts>24)throw Error('Invalid reference host count');
 if(!Number.isFinite(input.scaling)||input.scaling<.5||input.scaling>1)throw Error('Invalid scaling efficiency');
 if(!Number.isFinite(input.safety)||input.safety<=0||input.safety>1)throw Error('Invalid safety factor');
 const cpu=cpus.find(x=>x.id===input.cpu),set=benchmarks[input.storage]?.[modeKey(input)];
 if(!cpu||!set)throw Error('Invalid source selection');
 const network=input.network==='unknown'?null:Number(input.network);
 function one(id,activeHosts){
  const p=profiles[id],ref=set[id],ratio=cpu.ratio;
  if(ratio===null)return{id,...p,reference:ref.value,latency:ref.latency,error:'CPU ratio required'};
  const scale=activeHosts/input.referenceHosts*(activeHosts===input.referenceHosts?1:input.scaling);
  const benchmarkLimit=ref.value*scale*ratio;
  const networkMBps=network===null?null:network*activeHosts;
  const networkLimit=networkMBps===null?null:(p.unit==='IOPS'?networkMBps*1024/p.blockKB:networkMBps);
  if(network===null)return{id,...p,reference:ref.value,latency:ref.latency,benchmarkLimit,networkLimit,error:'Network value required'};
  const raw=Math.min(benchmarkLimit,networkLimit),limit=networkLimit<benchmarkLimit?'Network':'Measured benchmark';
  return{id,...p,reference:ref.value,latency:ref.latency,benchmarkLimit,networkLimit,raw,safe:raw*input.safety,limit};
 }
 return Object.keys(profiles).map(id=>{const healthy=one(id,input.hosts),n1=one(id,input.hosts-1);return{...healthy,n1:n1.safe??null,n1Error:n1.error??null};});
}
root.IOCalculatorV3={profiles,benchmarks,cpus,calculate};
})(window);
</script>
<script id="calculator-ui">
(function(){'use strict';const api=window.IOCalculatorV3,form=document.getElementById('config'),el=id=>document.getElementById(id),fmt=n=>new Intl.NumberFormat('en-US',{maximumFractionDigits:0}).format(n);
for(let n=3;n<=12;n++)el('hosts').add(new Option(String(n),String(n)));api.cpus.forEach(cpu=>el('cpu').add(new Option(cpu.label,cpu.id)));Object.entries(api.profiles).forEach(([id,p])=>el('workload').add(new Option(p.label,id)));
el('hosts').value='6';el('cpu').value='gold6226r';
function input(){const data=Object.fromEntries(new FormData(form));return{...data,hosts:Number(data.hosts),referenceHosts:Number(data.referenceHosts),scaling:Number(data.scaling)/100,safety:Number(data.safety),network:data.network,required:Number(data.required),growth:Number(data.growth),latency:Number(data.latency)}}
function statusClass(text){return text==='Meets N−1'?'good':text==='Healthy only'?'warn':'bad'}
function render(){const x=input(),cpu=api.cpus.find(c=>c.id===x.cpu),rows=api.calculate(x),workloadMode=x.calculationMode==='workload';el('workload-inputs').hidden=!workloadMode;el('capability').hidden=workloadMode;el('fit').hidden=!workloadMode;el('results-title').textContent=workloadMode?'Workload Fit':'Cluster Capability';
 el('cpu-info').innerHTML=`<strong>${cpu.label}</strong>Performance ratio: ${cpu.ratio??'Unavailable'} · ${cpu.source}`;
 el('summary').textContent=`${x.hosts} Hosts · ${x.storage==='hybrid'?'NVMe + HDD':'All-NVMe*'} · ${x.performanceMode==='turbo'?'Turbo':'Normal'} · RDMA ${x.rdma==='on'?'Enabled':'Disabled'} · ${(x.safety*100).toFixed(0)}% design ceiling · N−1 = ${x.hosts-1} active Hosts`;
 const warnings=[];if(x.referenceHosts===6)warnings.push('Reference host count is assumed to be 6 because the source workbook does not record it. Confirm this before proposal use.');if(x.storage==='allnvme')warnings.push('The “all nvme” sheet has conflicting configuration text that lists HDDs. Treat this profile as unverified.');if(cpu.ratio===null)warnings.push('The selected CPU has no validated ratio. Results remain unavailable.');else if(cpu.ratio>1)warnings.push('CPU uplift is extrapolated above the measured Gold 6226R baseline and requires a matching benchmark.');if(x.hosts!==x.referenceHosts)warnings.push(`Host scaling uses ${(x.scaling*100).toFixed(0)}% efficiency because the solution differs from the reference host count.`);if(x.network==='unknown')warnings.push('The source provides no 25G Single-port throughput value. Results remain unavailable.');
 el('warnings').replaceChildren(...warnings.map(t=>{const p=document.createElement('p');p.className=t.includes('remain unavailable')?'error-note':'warning';p.textContent=t;return p;}));
 el('capability-body').innerHTML=rows.map(r=>`<tr><td class="metric-name"><strong>${r.label}</strong><small>${r.unit}</small></td><td>${fmt(r.reference)}</td><td>${r.error?'Unavailable':fmt(r.raw)}</td><td>${r.error?'Unavailable':fmt(r.safe)}</td><td>${r.error?'Unavailable':fmt(r.n1)}</td><td>${r.latency.toFixed(2)} ms</td><td><span class="limit">${r.error||r.limit}</span></td></tr>`).join('');
 const selected=rows.find(r=>r.id===x.workload),p=api.profiles[x.workload];el('required-unit').textContent=p.unit;
 if(!workloadMode){el('kpis').innerHTML=`<article class="kpi"><h3>Reference Dataset</h3><strong>6 profiles</strong><small>Measured IOPS, throughput and latency</small></article><article class="kpi"><h3>Healthy Design Ceiling</h3><strong>${(x.safety*100).toFixed(0)}%</strong><small>Applied after bottleneck selection</small></article><article class="kpi"><h3>Failure State</h3><strong>N−1</strong><small>${x.hosts-1} active Hosts recalculated</small></article>`;return}
 const need=x.required*(1+x.growth/100);let fitStatus='Unavailable';if(!selected.error)fitStatus=need<=selected.n1?'Meets N−1':need<=selected.safe?'Healthy only':'Below requirement';const utilization=selected.error?null:need/selected.safe*100,latencyOkay=selected.latency<=x.latency;
 el('kpis').innerHTML=`<article class="kpi"><h3>Required with Growth</h3><strong>${fmt(need)}</strong><small>${p.unit}</small></article><article class="kpi"><h3>Safe Healthy Capacity</h3><strong>${selected.error?'Unavailable':fmt(selected.safe)}</strong><small>${p.unit}</small></article><article class="kpi"><h3>Safe N−1 Capacity</h3><strong>${selected.error?'Unavailable':fmt(selected.n1)}</strong><small>${p.unit}</small><span class="status ${statusClass(fitStatus)}">${fitStatus}</span></article>`;
 el('fit').innerHTML=`<h3>${p.label}</h3><p>${selected.error?'A required source value is missing.':'Requirement is compared with the safe measured envelope and one-host failure state.'}</p><div class="fit-grid"><div class="fit-cell"><span>Healthy utilization</span><strong>${utilization===null?'Unavailable':utilization.toFixed(1)+'%'}</strong></div><div class="fit-cell"><span>Reference peak latency</span><strong>${selected.latency.toFixed(2)} ms</strong></div><div class="fit-cell"><span>Latency target</span><strong>${x.latency.toFixed(1)} ms</strong></div><div class="fit-cell"><span>Latency evidence</span><strong>${latencyOkay?'Within reference':'Not validated'}</strong></div></div>`;
}
form.addEventListener('input',render);form.addEventListener('change',render);render();
})();
</script>
</body>
</html>
