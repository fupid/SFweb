<?php
error_reporting(0);
ini_set('display_errors', '0');

// ── Session Timeout (30 minutes of inactivity) ────────────────────────────
const SESSION_TIMEOUT = 1800;
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
session_set_cookie_params(SESSION_TIMEOUT);
session_start();

if (!empty($_SESSION['username'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
    } else {
        $_SESSION['last_activity'] = time();
    }
}

define('DATA_DIR',       __DIR__ . '/data');
define('USERS_FILE',     DATA_DIR . '/UserDir.JSON');
define('PROJECTS_DIR',   DATA_DIR . '/projects');
// Shared by HCISizingTool.php and HCISizingTool-v2.php. It can be maintained
// through the v2 Admin page or edited directly over SSH.
define('INVENTORY_FILE', __DIR__ . '/inventory.json');

// Bootstrap: create directories and default files on first run
if (!is_dir(DATA_DIR))     mkdir(DATA_DIR, 0755, true);
if (!is_dir(PROJECTS_DIR)) mkdir(PROJECTS_DIR, 0755, true);

$htaccess = DATA_DIR . '/.htaccess';
if (!file_exists($htaccess)) file_put_contents($htaccess, "Deny from all\n");

if (!file_exists(USERS_FILE)) {
    file_put_contents(USERS_FILE, json_encode(['users' => [
        ['username' => 'admin', 'password' => password_hash('admin123', PASSWORD_DEFAULT), 'display_name' => 'Administrator', 'role' => 'admin']
    ]], JSON_PRETTY_PRINT));
}

if (!file_exists(INVENTORY_FILE)) {
    $inv = ["servers" => [
        ["name"=>"aServer-X5-2205",  "socket"=>2,"core"=>16,"freq"=>2.8,"baseRam"=>128,"maxDisk"=>12,"maxDimm"=>32,"memoryStripCounts"=>[4,8,12,16,24,32],"memoryEffectiveDate"=>"2026-09-24"],
        ["name"=>"aServer-X5-2305P", "socket"=>2,"core"=>32,"freq"=>2.1,"baseRam"=>128,"maxDisk"=>12,"maxDimm"=>32,"memoryStripCounts"=>[4,8,12,16,24,32],"memoryEffectiveDate"=>"2026-08-25"],
        ["name"=>"VDS-X5-2205",      "socket"=>2,"core"=>16,"freq"=>2.8,"baseRam"=>256,"maxDisk"=>12,"maxDimm"=>32,"memoryStripCounts"=>[4,8,12,16,24,32],"memoryEffectiveDate"=>"2026-09-24"],
        ["name"=>"VDS-X5-2405",      "socket"=>2,"core"=>32,"freq"=>2.8,"baseRam"=>256,"maxDisk"=>12,"maxDimm"=>32,"memoryStripCounts"=>[4,8,12,16,24,32],"memoryEffectiveDate"=>"2026-09-24"],
        ["name"=>"VDS-X5-G2600",     "socket"=>2,"core"=>16,"freq"=>3.6,"baseRam"=>256,"maxDisk"=>10,"maxDimm"=>32,"memoryStripCounts"=>[4,8,12,16,24,32],"memoryEffectiveDate"=>"2026-09-24"],
        ["name"=>"aServer-X5-2405",  "socket"=>2,"core"=>32,"freq"=>2.8,"baseRam"=>128,"maxDisk"=>12,"maxDimm"=>32,"memoryStripCounts"=>[4,8,12,16,24,32],"memoryEffectiveDate"=>"2026-08-25"],
        ["name"=>"aServer-Z4-2405",  "socket"=>2,"core"=>32,"freq"=>3.25,"baseRam"=>256,"maxDisk"=>12,"maxDimm"=>24,"memoryStripCounts"=>[8,12,16,20,24],"memoryEffectiveDate"=>"2026-08-25"],
        ["name"=>"aServer-Z4-2605",  "socket"=>2,"core"=>64,"freq"=>3.1,"baseRam"=>256,"maxDisk"=>12,"maxDimm"=>24,"memoryStripCounts"=>[8,12,16,20,24],"memoryEffectiveDate"=>"2026-08-25"],
        ["name"=>"R770(6507P)",      "socket"=>2,"core"=>8, "freq"=>3.5,"baseRam"=>256,"maxDisk"=>24,"maxDimm"=>32],
        ["name"=>"R770(6520P)",      "socket"=>2,"core"=>24,"freq"=>2.4,"baseRam"=>256,"maxDisk"=>24,"maxDimm"=>32],
        ["name"=>"R770(6530P)",      "socket"=>2,"core"=>32,"freq"=>2.3,"baseRam"=>256,"maxDisk"=>24,"maxDimm"=>32],
        ["name"=>"R770(6740P)",      "socket"=>2,"core"=>48,"freq"=>2.1,"baseRam"=>256,"maxDisk"=>24,"maxDimm"=>32]
    ], "ssds" => [480,960,1600,1900,3200,3800,7600],
       "hdds" => [960,1600,1900,3200,3800,4000,6000,7600,8000,10000,12000]];
    $inv['memory'] = ['dimmSizesGB'=>[16,64,128], 'defaultDimmSizeGB'=>64];
    $inv['availability'] = ['servers'=>[], 'memory'=>[], 'ssds'=>[], 'hdds'=>[]];
    foreach ($inv['servers'] as &$server) {
        $server['baseDimmSizeGB'] = 64;
        $server['baseDimmCount'] = max(1, (int) round($server['baseRam'] / 64));
    }
    unset($server);
    file_put_contents(INVENTORY_FILE, json_encode($inv, JSON_PRETTY_PRINT));
}

// Memory Tiering: NVMe disk specs (Tier 1 — Cold Tier)
define('NVME_INVENTORY_FILE', DATA_DIR . '/nvme-inventory.json');
if (!file_exists(NVME_INVENTORY_FILE)) {
    $nvmeDefault = ["nvmeSizes"=>[800,1600,3200,6400],"specialBayOverhead"=>4,"platformOverheadPerNodeGB"=>27];
    file_put_contents(NVME_INVENTORY_FILE, json_encode($nvmeDefault, JSON_PRETTY_PRINT));
}

// ── API Handler ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input  = json_decode(file_get_contents('php://input'), true) ?: [];
    $action = $input['action'] ?? '';
    header('Content-Type: application/json');

    $requireAuth = function() {
        if (empty($_SESSION['username'])) {
            http_response_code(401);
            echo json_encode(['status'=>'error','message'=>'Not authenticated']);
            exit;
        }
    };
    $requireAdmin = function() {
        if (empty($_SESSION['username'])) { http_response_code(401); echo json_encode(['status'=>'error','message'=>'Not authenticated']); exit; }
        if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); echo json_encode(['status'=>'error','message'=>'Admin only']); exit; }
    };

    switch ($action) {

        // ── Authentication ──────────────────────────────────────────────────
        case 'login':
            $un = trim($input['username'] ?? '');
            $pw = $input['password'] ?? '';
            $usersRaw = file_exists(USERS_FILE) ? (json_decode(file_get_contents(USERS_FILE), true) ?? []) : [];
            foreach ($usersRaw['users'] ?? [] as $u) {
                if ($u['username'] === $un && password_verify($pw, $u['password'])) {
                    session_regenerate_id(true);
                    $_SESSION = ['username'=>$u['username'],'display_name'=>$u['display_name'],'role'=>$u['role']];
                    echo json_encode(['status'=>'ok','username'=>$u['username'],'display_name'=>$u['display_name'],'role'=>$u['role']]);
                    exit;
                }
            }
            http_response_code(401);
            echo json_encode(['status'=>'error','message'=>'Invalid username or password']);
            exit;

        case 'logout':
            session_destroy();
            echo json_encode(['status'=>'ok']);
            exit;

        case 'change_password':
            $requireAuth();
            $oldPw = $input['old_password'] ?? '';
            $newPw = $input['new_password'] ?? '';
            if (strlen($newPw) < 6) { echo json_encode(['status'=>'error','message'=>'Password must be at least 6 characters']); exit; }
            $data = file_exists(USERS_FILE) ? (json_decode(file_get_contents(USERS_FILE), true) ?? ['users'=>[]]) : ['users'=>[]];
            foreach ($data['users'] as &$u) {
                if ($u['username'] === $_SESSION['username']) {
                    if (!password_verify($oldPw, $u['password'])) { echo json_encode(['status'=>'error','message'=>'Current password is incorrect']); exit; }
                    $u['password'] = password_hash($newPw, PASSWORD_DEFAULT);
                    file_put_contents(USERS_FILE, json_encode($data, JSON_PRETTY_PRINT));
                    echo json_encode(['status'=>'ok']);
                    exit;
                }
            }
            echo json_encode(['status'=>'error','message'=>'User not found']); exit;

        // ── Projects ────────────────────────────────────────────────────────
        case 'save_project':
            $requireAuth();
            $name = trim(preg_replace('/[^\w\s\-\(\)\.,]/', '', $input['name'] ?? ''));
            if (!$name) { echo json_encode(['status'=>'error','message'=>'Invalid project name']); exit; }
            $dir = PROJECTS_DIR . '/' . $_SESSION['username'];
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $file = $dir . '/' . $name . '.json';
            $pd = ['name'=>$name,'created'=>date('c'),'updated'=>date('c'),'config'=>($input['config']??[])];
            if (file_exists($file)) {
                $old = json_decode(file_get_contents($file), true);
                $pd['created'] = $old['created'] ?? $pd['created'];
            }
            file_put_contents($file, json_encode($pd, JSON_PRETTY_PRINT));
            echo json_encode(['status'=>'ok','message'=>"Project \"{$name}\" saved"]);
            exit;

        case 'list_projects':
            $requireAuth();
            $dir = PROJECTS_DIR . '/' . $_SESSION['username'];
            $projects = [];
            if (is_dir($dir)) {
                foreach (glob($dir . '/*.json') as $f) {
                    $d = json_decode(file_get_contents($f), true);
                    if ($d) $projects[] = ['name'=>$d['name'],'created'=>$d['created'],'updated'=>$d['updated']];
                }
            }
            usort($projects, fn($a,$b) => strcmp($b['updated'], $a['updated']));
            echo json_encode(['status'=>'ok','projects'=>$projects]);
            exit;

        case 'load_project':
            $requireAuth();
            $name = preg_replace('/[^\w\s\-\(\)\.,]/', '', $input['name'] ?? '');
            $file = PROJECTS_DIR . '/' . $_SESSION['username'] . '/' . $name . '.json';
            if (!file_exists($file)) { echo json_encode(['status'=>'error','message'=>'Project not found']); exit; }
            echo json_encode(['status'=>'ok','project'=>json_decode(file_get_contents($file), true)]);
            exit;

        case 'delete_project':
            $requireAuth();
            $name = preg_replace('/[^\w\s\-\(\)\.,]/', '', $input['name'] ?? '');
            $file = PROJECTS_DIR . '/' . $_SESSION['username'] . '/' . $name . '.json';
            if (file_exists($file)) unlink($file);
            echo json_encode(['status'=>'ok']);
            exit;

        // ── Inventory (admin only) ───────────────────────────────────────────
        case 'save_inventory':
            $requireAdmin();
            file_put_contents(INVENTORY_FILE, json_encode($input['data'] ?? [], JSON_PRETTY_PRINT));
            echo json_encode(['status'=>'ok']);
            exit;

        // ── User Management (admin only) ────────────────────────────────────
        case 'get_directory':
            $requireAuth();
            $data = file_exists(USERS_FILE) ? (json_decode(file_get_contents(USERS_FILE), true) ?? ['users'=>[]]) : ['users'=>[]];
            $users = array_map(function($u) {
                $projDir = PROJECTS_DIR . '/' . $u['username'];
                $projCount = is_dir($projDir) ? count(glob($projDir . '/*.json')) : 0;
                return ['username'=>$u['username'],'display_name'=>$u['display_name'],'role'=>$u['role'],'project_count'=>$projCount];
            }, $data['users'] ?? []);
            echo json_encode(['status'=>'ok','users'=>$users]);
            exit;

        case 'list_users':
            $requireAdmin();
            $data = file_exists(USERS_FILE) ? (json_decode(file_get_contents(USERS_FILE), true) ?? ['users'=>[]]) : ['users'=>[]];
            echo json_encode(['status'=>'ok','users'=>array_map(
                fn($u) => ['username'=>$u['username'],'display_name'=>$u['display_name'],'role'=>$u['role']],
                $data['users'] ?? []
            )]);
            exit;

        case 'add_user':
            $requireAdmin();
            $un   = preg_replace('/[^a-z0-9_]/', '', strtolower($input['username'] ?? ''));
            $pw   = $input['password'] ?? '';
            $dn   = htmlspecialchars(trim($input['display_name'] ?? $un));
            $role = in_array($input['role'] ?? 'user', ['admin','user']) ? $input['role'] : 'user';
            if (!$un || strlen($pw) < 6) { echo json_encode(['status'=>'error','message'=>'Invalid username or password too short (min 6 chars)']); exit; }
            $data = file_exists(USERS_FILE) ? (json_decode(file_get_contents(USERS_FILE), true) ?? ['users'=>[]]) : ['users'=>[]];
            foreach ($data['users'] ?? [] as $u) if ($u['username'] === $un) { echo json_encode(['status'=>'error','message'=>'Username already exists']); exit; }
            $data['users'][] = ['username'=>$un,'password'=>password_hash($pw,PASSWORD_DEFAULT),'display_name'=>$dn,'role'=>$role];
            file_put_contents(USERS_FILE, json_encode($data, JSON_PRETTY_PRINT));
            echo json_encode(['status'=>'ok']);
            exit;

        case 'delete_user':
            $requireAdmin();
            $un = $input['username'] ?? '';
            if ($un === $_SESSION['username']) { echo json_encode(['status'=>'error','message'=>'Cannot delete your own account']); exit; }
            $data = file_exists(USERS_FILE) ? (json_decode(file_get_contents(USERS_FILE), true) ?? ['users'=>[]]) : ['users'=>[]];
            $data['users'] = array_values(array_filter($data['users'] ?? [], fn($u) => $u['username'] !== $un));
            file_put_contents(USERS_FILE, json_encode($data, JSON_PRETTY_PRINT));
            echo json_encode(['status'=>'ok']);
            exit;
    }

    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'Unknown action']);
    exit;
}

$isLoggedIn    = !empty($_SESSION['username']);
$currentUser   = $isLoggedIn ? $_SESSION : null;
$rawInv = file_exists(INVENTORY_FILE) ? file_get_contents(INVENTORY_FILE) : null;
$invDecoded = $rawInv ? json_decode($rawInv, true) : null;
if (!$invDecoded || empty($invDecoded['servers'])) {
    $invDecoded = ["servers"=>[
        ["name"=>"aServer-X5-2405","socket"=>2,"core"=>32,"freq"=>2.8,"baseRam"=>128,"maxDisk"=>12,"maxDimm"=>32],
        ["name"=>"aServer-X5-2205","socket"=>2,"core"=>16,"freq"=>2.8,"baseRam"=>128,"maxDisk"=>12,"maxDimm"=>32],
        ["name"=>"aServer-X6-2205","socket"=>2,"core"=>16,"freq"=>3.2,"baseRam"=>128,"maxDisk"=>12,"maxDimm"=>32]
    ],"ssds"=>[480,960,1920,3840],"hdds"=>[1200,2400,4000,8000,12000]];
}
// Support existing v2 inventories with the current DIMM sizes.
$invDecoded['memory'] = ['dimmSizesGB'=>[16,64,128], 'defaultDimmSizeGB'=>64];
$invDecoded['availability'] = array_merge(
    ['servers'=>[], 'memory'=>[], 'ssds'=>[], 'hdds'=>[]],
    is_array($invDecoded['availability'] ?? null) ? $invDecoded['availability'] : []
);
foreach ($invDecoded['servers'] as &$server) {
    $size = $server['baseDimmSizeGB'] ?? 64;
    if (!in_array($size, [16,64,128])) $size = 64;
    $server['baseDimmSizeGB'] = $size;
    $server['baseDimmCount'] = max(1, (int) round(($server['baseRam'] ?? 128) / $size));
}
unset($server);
$inventoryData = json_encode($invDecoded, JSON_HEX_TAG | JSON_HEX_AMP)
    ?: '{"servers":[{"name":"aServer-X5-2405","socket":2,"core":32,"freq":2.8,"baseRam":128,"maxDisk":12,"maxDimm":32}],"ssds":[480,960,1920,3840],"hdds":[1200,2400,4000,8000,12000]}';

$rawNvme = file_exists(NVME_INVENTORY_FILE) ? file_get_contents(NVME_INVENTORY_FILE) : null;
$nvmeDecoded = $rawNvme ? json_decode($rawNvme, true) : null;
if (!$nvmeDecoded || empty($nvmeDecoded['nvmeSizes'])) {
    $nvmeDecoded = ["nvmeSizes"=>[800,1600,3200,6400],"specialBayOverhead"=>4,"platformOverheadPerNodeGB"=>27];
}
$nvmeData = json_encode($nvmeDecoded, JSON_HEX_TAG | JSON_HEX_AMP)
    ?: '{"nvmeSizes":[800,1600,3200,6400],"specialBayOverhead":4,"platformOverheadPerNodeGB":27}';

$setupRequired = !file_exists(USERS_FILE);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sangfor HCI Central Sizer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .usable-cell { color: #16a34a; font-weight: 700; background-color: #f0fdf4; }
        .fail-cell { color: #dc2626; font-weight: 700; background-color: #fef2f2; }
        .pool-card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 8px; background: white; margin-bottom: 6px; }
        .pool-card.has-error { border-color: #fca5a5; background-color: #fff5f5; }
        .pool-stat { font-size: 9px; color: #6b7280; font-style: italic; }
        .err-badge { font-size: 9px; color: #dc2626; font-weight: 700; margin-top: 2px; }
        .proj-card { transition: box-shadow .15s; }
        .proj-card:hover { box-shadow: 0 4px 16px rgba(30,64,175,.12); }
        #toast { pointer-events: none; }
        .workflow-guide { margin-bottom: 20px; color: #1e3a8a; }
        .workflow-guide strong { font-size: 22px; }
        .workflow-guide p { margin: 6px 0; font-size: 14px; }
        .workflow-guide small, .memory-help { font-size: 12px; color: #475569; }
        #exportStatus { color: #166534; margin-bottom: 12px; font-size: 13px; }
        nav { gap: 12px; flex-wrap: wrap; }
        button:disabled { opacity: .6; cursor: wait; }
        input:focus-visible, select:focus-visible, button:focus-visible, a:focus-visible {
            outline: 3px solid #60a5fa; outline-offset: 2px;
        }
        .no-print input:not([type="checkbox"]), .no-print select { min-height: 36px; font-size: 12px; }
        .no-print input[type="checkbox"] { flex-shrink: 0; }
        #pdf-area { min-width: 0; }
        #pdf-area > .overflow-hidden { overflow-x: auto; }
        #pdf-area table { min-width: 560px; }
        .pdf-stage { position: absolute; left: 0; top: 0; z-index: -100; pointer-events: none; }
        .pdf-page { width: 718px; height: 1035px; padding: 24px; box-sizing: border-box;
            background: white; color: #1f2937; display: flex; flex-direction: column; gap: 12px;
            font: 11px/1.4 Inter, sans-serif; }
        .pdf-header { flex: 0 0 44px; border-bottom: 2px solid #1e40af; color: #1e3a8a; font-weight: 700; }
        .pdf-content { flex: 1; min-height: 0; }
        .pdf-footer { flex: 0 0 24px; padding-top: 6px; border-top: 1px solid #cbd5e1; color: #64748b; text-align: right; }
        .pdf-content h2 { background: #1e40af; color: white; font-size: 13px; font-weight: 700; padding: 8px 10px; margin-top: 12px; }
        .pdf-content > :first-child { margin-top: 0; }
        .pdf-table { width: 100%; table-layout: fixed; border-collapse: collapse; }
        .pdf-table td, .pdf-table th { padding: 6px 8px !important; font-size: 10px; line-height: 1.4;
            overflow-wrap: anywhere; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
        .pdf-table thead { background: #eff6ff; }
        .pdf-table tr { break-inside: avoid; }
        @media (max-width: 640px) {
            nav > div { flex-wrap: wrap; }
            #exportPdfButton { width: 100%; }
            .workflow-guide strong { font-size: 19px; }
        }
    </style>
</head>
<body class="p-4 md:p-6">

<?php if (!$isLoggedIn): ?>
<!-- ═══════════════════════════ LOGIN PAGE ═══════════════════════════════════ -->
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-950 via-blue-900 to-slate-800 -m-4 md:-m-6 p-6">
    <div class="bg-white rounded-3xl w-full max-w-sm p-10 shadow-2xl text-center">
        <div class="w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">🔐</div>
        <h1 class="text-2xl font-bold text-gray-800 mb-1">HCI Sizing Tool</h1>
        <p class="text-[10px] text-gray-400 uppercase tracking-widest mb-8">Sangfor Enterprise — Sign In Required</p>
        <?php if ($setupRequired): ?>
        <div class="bg-red-50 border border-red-200 rounded-2xl p-3 mb-4 text-left">
            <p class="text-red-700 font-bold text-[11px]">⚠️ System not initialized</p>
            <p class="text-red-600 text-[10px] mt-1">Please run <a href="hci-setup.php" class="underline font-bold">hci-setup.php</a> first to set up the system.</p>
        </div>
        <?php endif; ?>
        <div class="space-y-3 text-left mb-3">
            <div>
                <label class="text-[9px] font-bold text-gray-400 ml-1 uppercase">Username</label>
                <input type="text" id="loginUser" autocomplete="username"
                    class="w-full border p-3.5 rounded-2xl bg-gray-50 outline-none focus:ring-2 focus:ring-blue-300 mt-1 text-sm"
                    placeholder="Enter username">
            </div>
            <div>
                <label class="text-[9px] font-bold text-gray-400 ml-1 uppercase">Password</label>
                <input type="password" id="loginPass" autocomplete="current-password"
                    class="w-full border p-3.5 rounded-2xl bg-gray-50 outline-none focus:ring-2 focus:ring-blue-300 mt-1 text-sm"
                    placeholder="••••••••"
                    onkeydown="if(event.key==='Enter')doLogin()">
            </div>
        </div>
        <p id="loginError" class="text-[11px] text-red-500 font-bold mb-3 min-h-[18px]"></p>
        <button onclick="doLogin()" id="loginBtn"
            class="w-full bg-blue-600 text-white py-4 rounded-2xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-200 transition active:scale-95">
            Sign In
        </button>
    </div>
</div>
<script>
async function doLogin() {
    const btn = document.getElementById('loginBtn');
    const errEl = document.getElementById('loginError');
    btn.disabled = true; btn.innerText = 'Signing in…';
    errEl.innerText = '';
    try {
        const r = await fetch(location.href, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({action:'login', username: document.getElementById('loginUser').value, password: document.getElementById('loginPass').value})
        });
        const d = await r.json();
        if (d.status === 'ok') { location.reload(); }
        else { errEl.innerText = d.message || 'Login failed'; }
    } catch(e) { errEl.innerText = 'Network error — please try again'; }
    btn.disabled = false; btn.innerText = 'Sign In';
}
</script>

<?php else: ?>
<!-- ═══════════════════════════ MAIN APP ════════════════════════════════════ -->

<!-- Toast notifications -->
<div id="toast" class="fixed top-4 right-4 z-[200] space-y-2 no-print"></div>

<!-- ── Navbar ── -->
<nav class="max-w-7xl mx-auto mb-6 flex justify-between items-center bg-white p-3 rounded-xl shadow-sm border no-print">
    <div class="flex space-x-2">
        <a href="index.html" class="px-4 py-2 rounded-lg font-medium text-sm text-gray-500 hover:bg-gray-100 transition flex items-center gap-1.5" style="text-decoration:none; color:inherit;">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
            Menu
        </a>
        <button onclick="switchTab('sizing')"    id="navSizing"     class="nav-btn active px-5 py-2 rounded-lg font-bold text-sm bg-blue-600 text-white shadow-md">Sizing Tool</button>
        <button onclick="switchTab('projects')"  id="navProjects"   class="nav-btn px-5 py-2 rounded-lg font-medium text-sm text-gray-600 hover:bg-gray-100 transition">💾 My Projects</button>
        <button onclick="switchTab('directory')" id="navDirectory"  class="nav-btn px-5 py-2 rounded-lg font-medium text-sm text-gray-600 hover:bg-gray-100 transition">👥 Directory</button>
        <?php if ($currentUser['role'] === 'admin'): ?>
        <button onclick="switchTab('admin')"     id="navAdmin"      class="nav-btn px-5 py-2 rounded-lg font-medium text-sm text-gray-600 hover:bg-gray-100 transition">⚙️ Inventory Admin</button>
        <?php endif; ?>
    </div>
    <div class="flex items-center gap-3">
        <!-- User menu -->
        <div class="relative">
            <button onclick="toggleUserMenu()" id="userMenuBtn"
                class="flex items-center gap-2 text-sm font-semibold text-gray-700 bg-gray-50 hover:bg-gray-100 px-3 py-2 rounded-lg border transition">
                <span class="w-7 h-7 bg-blue-600 text-white rounded-full flex items-center justify-center text-[10px] font-bold">
                    <?php echo strtoupper(function_exists('mb_substr') ? mb_substr($currentUser['display_name'], 0, 2) : substr($currentUser['display_name'], 0, 2)); ?>
                </span>
                <span class="max-w-[120px] truncate"><?php echo htmlspecialchars($currentUser['display_name']); ?></span>
                <?php if ($currentUser['role'] === 'admin'): ?>
                <span class="text-[8px] bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded font-bold">ADMIN</span>
                <?php endif; ?>
                <span class="text-[9px] text-gray-400">▼</span>
            </button>
            <div id="userMenu" class="hidden absolute right-0 top-full mt-1 bg-white border rounded-xl shadow-lg z-50 py-1 w-48">
                <div class="px-4 py-2 text-[10px] text-gray-400 border-b">
                    Logged in as <strong>@<?php echo htmlspecialchars($currentUser['username']); ?></strong>
                </div>
                <button onclick="openChangePwModal(); toggleUserMenu();" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">🔑 Change Password</button>
                <button onclick="doLogout()" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">⏻ Sign Out</button>
            </div>
        </div>
        <button id="exportPdfButton" onclick="exportPDF()" class="bg-green-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-green-700 shadow-sm transition text-sm no-print">📄 Export PDF</button>
    </div>
</nav>

<main class="max-w-7xl mx-auto">

    <!-- ── Stats Bar ── -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 no-print">
        <div class="bg-white p-4 rounded-xl border shadow-sm text-center">
            <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Nodes</p>
            <p class="text-2xl font-bold text-blue-700" id="smNodes">-</p>
        </div>
        <div class="bg-white p-4 rounded-xl border shadow-sm text-center">
            <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Compute (GHz)</p>
            <p class="text-2xl font-bold text-blue-700" id="smCPU">-</p>
        </div>
        <div class="bg-white p-4 rounded-xl border shadow-sm text-center">
            <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">VM Memory (GB)</p>
            <p class="text-2xl font-bold text-green-600" id="smRAM">-</p>
        </div>
        <div class="bg-white p-4 rounded-xl border shadow-sm text-center">
            <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Storage (TB)</p>
            <p class="text-2xl font-bold text-orange-600" id="smStore">-</p>
        </div>
    </div>

    <!-- ═══ SIZING TOOL TAB ═══════════════════════════════════════════════════ -->
    <div id="content-sizing" class="tab-content active">
    <div class="workflow-guide no-print"><strong>Configure your HCI cluster</strong><p>1. Select server &amp; memory → 2. Set nodes &amp; storage → 3. Review capacity &amp; export PDF</p><small>Results update automatically. Memory shown as installed RAM and VM-usable capacity after system overhead.</small></div>
    <p id="exportStatus" role="status" aria-live="polite" class="no-print"></p>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- LEFT PANEL -->
            <div class="lg:col-span-4 no-print">
                <div class="bg-white p-5 rounded-xl border shadow-sm">

                    <!-- Project indicator -->
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-dashed">
                        <div class="text-[10px] min-w-0 mr-2">
                            <span class="text-gray-400">Project: </span>
                            <span id="currentProjectLabel" class="font-bold text-gray-400 italic truncate">Unsaved</span>
                        </div>
                        <div class="flex gap-1 shrink-0">
                            <button onclick="promptSaveProject(false)" title="Save"
                                class="text-[10px] bg-blue-600 text-white px-2.5 py-1 rounded-lg font-bold hover:bg-blue-700 transition">💾 Save</button>
                            <button onclick="promptSaveProject(true)" title="Save As new name"
                                class="text-[10px] bg-gray-100 text-gray-600 px-2 py-1 rounded-lg font-bold hover:bg-gray-200 transition">As…</button>
                            <button onclick="newConfig()" title="Clear / New config"
                                class="text-[10px] bg-gray-100 text-gray-400 px-2 py-1 rounded-lg hover:bg-gray-200 transition">✕</button>
                        </div>
                    </div>

                    <h3 class="text-blue-700 font-bold border-b pb-3 mb-4 text-xs uppercase flex justify-between">
                        ⚙️ Cluster Setup
                        <span class="text-[9px] bg-blue-50 text-blue-500 px-2 py-1 rounded">Connected</span>
                    </h3>

                    <div class="space-y-4 text-sm">

                        <!-- Server Model -->
                        <div>
                            <label class="text-[10px] font-bold text-gray-500 uppercase">Server Model</label>
                            <select id="selModel" onchange="handleModelChange()" class="w-full border p-2 rounded-md font-bold bg-blue-50 border-blue-200 outline-none mt-1"></select>
                            <div id="inventoryAvailabilityWarning" class="hidden mt-2 p-2 rounded-lg border border-amber-200 bg-amber-50 text-[9px] font-bold text-amber-700">
                                ⚠️ This server model is currently unavailable. It is shown because it belongs to a saved project.
                            </div>
                        </div>

                        <!-- Manual Config -->
                        <div id="manualArea" class="hidden p-3 bg-orange-50 rounded-xl border border-orange-200">
                            <div class="text-[9px] font-bold text-orange-600 uppercase mb-2">Custom Configuration</div>
                            <div class="grid grid-cols-2 gap-2 text-[10px]">
                                <div>Sockets<input type="number" id="mSocket" value="2" min="1" max="2" onchange="calculate()" class="w-full border p-1 rounded mt-1"></div>
                                <div>Cores/Socket<input type="number" id="mCore" value="16" onchange="calculate()" class="w-full border p-1 rounded mt-1"></div>
                                <div>Base GHz<input type="number" id="mFreq" step="0.1" value="2.8" onchange="calculate()" class="w-full border p-1 rounded mt-1"></div>
                                <div>Base RAM (GB)<input type="number" id="mBaseRam" value="128" min="1" oninput="calculate()" onchange="calculate()" class="w-full border p-1 rounded mt-1"></div>
                                <div>Max Disk Slots<input type="number" id="mMaxDisk" value="12" onchange="calculate()" class="w-full border p-1 rounded mt-1"></div>
                                <div>Max DIMM Slots<input type="number" id="mMaxDimm" value="24" onchange="updateRamOptions()" class="w-full border p-1 rounded mt-1"></div>
                            </div>
                        </div>

                        <!-- Memory Configuration: model default + final installed target -->
                        <div id="memoryConfiguration" class="bg-blue-50/60 p-3 rounded-lg border border-blue-100 space-y-2">
                            <div class="font-bold text-blue-800 uppercase text-[10px] border-b border-blue-100 pb-1">Memory · per node</div>
                            <p class="memory-help">The shipped RAM is fixed by the selected model. Choose the final installed RAM and the tool calculates the required 64 GB expansion DIMMs. <b>Rule:</b> 64 GB DIMMs require at least 4 DIMMs per node — the 2 × 64 GB default alone is not offered.</p>

                            <div class="bg-white rounded border border-blue-100 p-2.5">
                                <div class="flex items-start justify-between gap-2">
                                    <div><div class="text-[9px] font-bold text-gray-500 uppercase">Default RAM · included</div><div id="lblBaseRamConfig" class="text-[11px] text-blue-800 font-bold mt-1"></div></div>
                                    <span id="lblMemoryEffectiveDate" class="text-[8px] whitespace-nowrap bg-slate-100 text-slate-500 px-2 py-1 rounded-full"></span>
                                </div>
                            </div>

                            <div class="bg-white rounded border border-blue-200 p-2.5">
                                <label for="selMemoryTarget" class="block text-[9px] font-bold text-gray-500 uppercase mb-1">Final installed RAM</label>
                                <select id="selMemoryTarget" onchange="applyMemoryTarget()" class="w-full border border-blue-200 p-2 rounded bg-blue-50 font-bold text-[10px]"></select>
                                <div id="lblAdditionalRamSummary" class="text-[9px] text-emerald-700 font-bold mt-2"></div>
                                <div id="lblRemainSlots" class="text-[9px] text-gray-400 mt-1"></div>
                            </div>

                            <!-- Compatibility controls retained for saved projects and calculation logic. -->
                            <div class="hidden" aria-hidden="true">
                                <select id="selBaseDimmSize" onchange="updateRamOptions()"></select>
                                <input type="number" id="inpBaseDimmCount" value="2" min="1" onchange="updateRamOptions()">
                                <select id="selExpDimmSize" onchange="updateRamOptions()"></select>
                                <select id="selExpDimmCount" onchange="updateRamOptions()"></select>
                            </div>
                            <div class="text-[9px] font-bold text-blue-900 bg-blue-100 rounded p-1.5" id="lblTotalRamSummary"></div>
                        </div>

                        <!-- Memory Tiering (DRAM + NVMe) -->
                        <div class="border-t pt-3">
                            <label class="flex items-center gap-2 cursor-pointer mb-2">
                                <input type="checkbox" id="chkMemTiering" onchange="toggleMemTiering()" class="w-4 h-4 accent-purple-600">
                                <span class="text-[10px] font-bold text-purple-700 uppercase">🧠 Enable Memory Tiering (DRAM + NVMe)</span>
                            </label>
                            <div id="memTieringPanel" class="hidden p-3 bg-purple-50 rounded-lg border border-purple-200 space-y-2 text-[10px]">
                                <div class="text-[9px] text-gray-500 italic">VM memory capacity is derived automatically from this cluster's HW config (DIMM RAM + NVMe below) — no manual entry needed.</div>

                                <div class="border-t border-purple-200 pt-2">
                                    <div class="text-[9px] font-bold text-purple-700 uppercase mb-1">NVMe Disk (Tier 1 — Cold Tier)</div>
                                    <div class="grid grid-cols-2 gap-1.5">
                                        <div>
                                            <div class="text-[9px] text-gray-400 mb-0.5">NVMe Size</div>
                                            <select id="selNvmeSize" onchange="calculate()"
                                                class="w-full border p-1 rounded bg-white font-bold text-[10px]"></select>
                                        </div>
                                        <div>
                                            <div class="text-[9px] text-gray-400 mb-0.5">Qty / Node</div>
                                            <input type="number" id="inpNvmeQty" value="1" min="0"
                                                onchange="calculate()" class="w-full border p-1 rounded font-bold text-center bg-white">
                                        </div>
                                    </div>
                                    <div class="text-[9px] text-amber-600 font-bold mt-1">⚠️ NVMe special bay reserves <span id="lblNvmeBayOverhead">4</span> disk slot(s)/node on aServer platforms</div>
                                </div>

                                <div id="mtResults" class="bg-white rounded p-2 border border-purple-100"></div>

                                <div class="text-[9px] text-red-600 font-bold bg-red-50 border border-red-100 rounded p-1.5">
                                    ⚠️ Memory Over-commit (Over-rate) is NOT supported on nodes with Memory Tiering enabled.
                                </div>
                            </div>
                        </div>

                        <!-- Total Nodes -->
                        <div class="border-t pt-3 text-[10px]">
                            <label class="font-bold text-gray-500 uppercase block mb-1">
                                Total Nodes in Cluster
                                <span class="text-gray-400 font-normal ml-1">(min 1)</span>
                            </label>
                            <input type="number" id="qtyNode" value="3" min="1"
                                onchange="renderPools()" oninput="renderPools()"
                                class="w-full border p-3 rounded-md font-bold text-blue-800 text-lg border-blue-200">
                            <div id="oneNodeWarning" class="hidden mt-2 p-2 bg-amber-50 border border-amber-200 rounded text-[9px] text-amber-700 font-bold">
                                ⚠️ Single-Node mode: No server failover. Storage uses RAID instead of multi-copy redundancy.
                            </div>
                        </div>

                        <!-- Storage Pools -->
                        <div class="border-t pt-3">
                            <div class="flex justify-between items-center mb-1.5">
                                <label class="text-[10px] font-bold text-gray-600 uppercase">Storage Pools (Datastores)</label>
                                <div class="flex items-center gap-1.5">
                                    <span id="diskSlotBadge" class="text-[9px] px-2 py-0.5 rounded font-bold bg-gray-100 text-gray-500">0/12 slots/node</span>
                                    <button onclick="addPool()" class="text-blue-600 text-[10px] font-bold hover:underline px-2 py-1 bg-blue-50 rounded">+ Add Pool</button>
                                </div>
                            </div>
                            <div id="poolGlobalError" class="hidden mb-2 p-2 bg-red-50 border border-red-200 rounded text-[9px] text-red-700 font-bold space-y-1"></div>
                            <div id="poolsContainer" class="space-y-1"></div>
                        </div>

                        <button onclick="calculate()" class="w-full bg-blue-600 text-white py-4 rounded-xl font-bold shadow-lg hover:bg-blue-700 transition active:scale-95 text-sm">🚀 Calculate Results</button>
                    </div>
                </div>
            </div>

            <!-- RIGHT PANEL -->
            <div id="pdf-area" class="lg:col-span-8 space-y-6">
                <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-200">
                    <h4 class="text-emerald-800 font-bold text-xs mb-3">📐 Sizing Formulas Reference</h4>
                    <div class="space-y-2 text-[10px] text-emerald-900/80">
                        <div class="bg-white p-2 rounded border border-emerald-100 shadow-sm" id="f-cpu"></div>
                        <div class="bg-white p-2 rounded border border-emerald-100 shadow-sm" id="f-ram"></div>
                        <div class="bg-white p-2 rounded border border-emerald-100 shadow-sm font-bold text-blue-800" id="f-storage"></div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                    <div class="bg-[#1e40af] text-white px-5 py-3 font-bold text-sm">📊 Resource Capacity Summary</div>
                    <table class="w-full text-xs text-center border-collapse">
                        <thead class="bg-gray-50 text-gray-400 font-bold text-[9px] uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3 text-left border-b">Resource Component</th>
                                <th class="px-6 py-3 border-b">Total Cluster</th>
                                <th class="px-6 py-3 border-b">Usable</th>
                                <th class="px-6 py-3 border-b">After Node Failure</th>
                            </tr>
                        </thead>
                        <tbody id="summaryBody" class="divide-y text-gray-700 italic"></tbody>
                    </table>
                </div>

                <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                    <div class="bg-[#1e40af] text-white px-5 py-3 font-bold text-sm">📋 Detailed Specification Breakdown</div>
                    <table class="w-full text-xs text-left divide-y">
                        <tbody id="detailBody" class="divide-y"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ MY PROJECTS TAB ══════════════════════════════════════════════════ -->
    <div id="content-projects" class="tab-content">
        <div class="bg-white p-6 rounded-xl border shadow-sm">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="font-bold text-gray-700 text-sm">💾 My Projects</h3>
                    <p class="text-[10px] text-gray-400 mt-0.5">Your saved sizing configurations — visible only to you</p>
                </div>
                <div class="flex gap-2">
                    <button onclick="switchTab('sizing')" class="text-[11px] bg-gray-100 text-gray-600 px-3 py-2 rounded-lg font-bold hover:bg-gray-200 transition">+ New Config</button>
                    <button onclick="promptSaveProject(true)" class="text-[11px] bg-blue-600 text-white px-3 py-2 rounded-lg font-bold hover:bg-blue-700 transition">💾 Save Current Config</button>
                </div>
            </div>
            <div id="projectsList" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="text-center text-gray-400 col-span-3 py-16 text-sm">Loading projects…</div>
            </div>
        </div>
    </div>

    <!-- ═══ DIRECTORY TAB ══════════════════════════════════════════════════ -->
    <div id="content-directory" class="tab-content">
        <div class="bg-white p-6 rounded-xl border shadow-sm">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="font-bold text-gray-700 uppercase text-xs tracking-widest">👥 User Directory</h3>
                    <p class="text-[10px] text-gray-400 mt-0.5">Team members with access to this tool</p>
                </div>
                <?php if ($currentUser['role'] === 'admin'): ?>
                <button onclick="openAddUserModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold text-xs shadow-md hover:bg-indigo-700 transition">+ Add User</button>
                <?php endif; ?>
            </div>
            <div id="directoryList" class="overflow-x-auto border rounded-xl">
                <div class="text-center text-gray-400 py-8 text-sm">Loading…</div>
            </div>
        </div>
    </div>

    <!-- ═══ INVENTORY ADMIN TAB ══════════════════════════════════════════════ -->
    <?php if ($currentUser['role'] === 'admin'): ?>
    <div id="content-admin" class="tab-content">
        <!-- Inventory Management -->
        <div class="bg-white p-6 rounded-xl border shadow-sm mb-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-bold text-gray-700 uppercase text-xs tracking-widest">🗄️ Shared Inventory Admin (JSON Sync)</h3>
                <button onclick="openModal('server')" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-bold text-xs shadow-md">+ Add Model</button>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 overflow-x-auto border rounded-xl"><table class="w-full text-xs" id="adminServerTable"></table></div>
                <div class="space-y-4" id="adminCompPanel"></div>
            </div>
        </div>

    </div>
    <?php endif; ?>

</main>

<!-- ═══════════════════════════ MODALS ═══════════════════════════════════════ -->

<!-- Admin: Add Server / Disk -->
<div id="adminModal" class="fixed inset-0 bg-black/60 hidden flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-[2rem] w-full max-w-md p-8 shadow-2xl">
        <h3 id="modalTitle" class="text-lg font-bold mb-4 text-blue-700 border-b pb-2">Add Item</h3>
        <div id="modalBody" class="space-y-4"></div>
        <div class="flex space-x-3 mt-8">
            <button onclick="saveAdminItem()" class="flex-1 bg-blue-700 text-white py-3 rounded-2xl font-bold hover:bg-blue-800">Save to Server</button>
            <button onclick="closeModal()" class="flex-1 bg-gray-100 py-3 rounded-2xl">Cancel</button>
        </div>
    </div>
</div>

<!-- Save Project -->
<div id="saveProjectModal" class="fixed inset-0 bg-black/60 hidden flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-[2rem] w-full max-w-sm p-8 shadow-2xl">
        <h3 class="text-lg font-bold mb-2 text-blue-700">Save Project</h3>
        <p class="text-[10px] text-gray-400 mb-6">Give this sizing configuration a name to save it</p>
        <input type="text" id="saveProjectName" placeholder="e.g. Customer XYZ — HCI Proposal"
            class="w-full border p-3.5 rounded-2xl bg-gray-50 outline-none focus:ring-2 focus:ring-blue-300 text-sm"
            onkeydown="if(event.key==='Enter')doSaveProject()">
        <p id="saveProjectError" class="text-[10px] text-red-500 font-bold mt-2 min-h-[16px]"></p>
        <div class="flex space-x-3 mt-4">
            <button onclick="doSaveProject()" class="flex-1 bg-blue-700 text-white py-3 rounded-2xl font-bold hover:bg-blue-800">💾 Save</button>
            <button onclick="closeSaveModal()" class="flex-1 bg-gray-100 py-3 rounded-2xl text-gray-600">Cancel</button>
        </div>
    </div>
</div>

<!-- Add User (Admin) -->
<div id="addUserModal" class="fixed inset-0 bg-black/60 hidden flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-[2rem] w-full max-w-sm p-8 shadow-2xl">
        <h3 class="text-lg font-bold mb-5 text-indigo-700 border-b pb-2">Add New User</h3>
        <div class="space-y-3 text-sm">
            <div>
                <label class="text-[9px] font-bold text-gray-400 uppercase ml-1">Display Name</label>
                <input type="text" id="au_dn" placeholder="e.g. John Doe"
                    class="w-full border p-3 rounded-2xl bg-gray-50 outline-none mt-1">
            </div>
            <div>
                <label class="text-[9px] font-bold text-gray-400 uppercase ml-1">Username (login)</label>
                <input type="text" id="au_un" placeholder="e.g. johndoe (a-z, 0-9, _)"
                    class="w-full border p-3 rounded-2xl bg-gray-50 outline-none mt-1">
            </div>
            <div>
                <label class="text-[9px] font-bold text-gray-400 uppercase ml-1">Password (min 6 chars)</label>
                <input type="password" id="au_pw" placeholder="••••••••"
                    class="w-full border p-3 rounded-2xl bg-gray-50 outline-none mt-1">
            </div>
            <div>
                <label class="text-[9px] font-bold text-gray-400 uppercase ml-1">Role</label>
                <select id="au_role" class="w-full border p-3 rounded-2xl bg-gray-50 outline-none mt-1">
                    <option value="user">User (Sizing only)</option>
                    <option value="admin">Admin (Full access)</option>
                </select>
            </div>
        </div>
        <p id="addUserError" class="text-[10px] text-red-500 font-bold mt-2 min-h-[16px]"></p>
        <div class="flex space-x-3 mt-4">
            <button onclick="doAddUser()" class="flex-1 bg-indigo-700 text-white py-3 rounded-2xl font-bold hover:bg-indigo-800">Create User</button>
            <button onclick="closeAddUserModal()" class="flex-1 bg-gray-100 py-3 rounded-2xl text-gray-600">Cancel</button>
        </div>
    </div>
</div>

<!-- Change Password -->
<div id="changePwModal" class="fixed inset-0 bg-black/60 hidden flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-[2rem] w-full max-w-sm p-8 shadow-2xl">
        <h3 class="text-lg font-bold mb-5 text-gray-700 border-b pb-2">🔑 Change Password</h3>
        <div class="space-y-3 text-sm">
            <div>
                <label class="text-[9px] font-bold text-gray-400 uppercase ml-1">Current Password</label>
                <input type="password" id="cp_old" placeholder="••••••••" class="w-full border p-3 rounded-2xl bg-gray-50 outline-none mt-1">
            </div>
            <div>
                <label class="text-[9px] font-bold text-gray-400 uppercase ml-1">New Password (min 6 chars)</label>
                <input type="password" id="cp_new" placeholder="••••••••" class="w-full border p-3 rounded-2xl bg-gray-50 outline-none mt-1">
            </div>
            <div>
                <label class="text-[9px] font-bold text-gray-400 uppercase ml-1">Confirm New Password</label>
                <input type="password" id="cp_cfm" placeholder="••••••••" class="w-full border p-3 rounded-2xl bg-gray-50 outline-none mt-1"
                    onkeydown="if(event.key==='Enter')doChangePw()">
            </div>
        </div>
        <p id="changePwError" class="text-[10px] text-red-500 font-bold mt-2 min-h-[16px]"></p>
        <div class="flex space-x-3 mt-4">
            <button onclick="doChangePw()" class="flex-1 bg-blue-700 text-white py-3 rounded-2xl font-bold hover:bg-blue-800">Update Password</button>
            <button onclick="closeChangePwModal()" class="flex-1 bg-gray-100 py-3 rounded-2xl text-gray-600">Cancel</button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════ JAVASCRIPT ══════════════════════════════════ -->
<script>
// ── State ─────────────────────────────────────────────────────────────────────
let db           = <?php echo $inventoryData; ?>;
let nvmeDb       = <?php echo $nvmeData; ?>;
const defaultDimmSizeGB = db.memory?.defaultDimmSizeGB || 64;
let restoredDimmTotal = null; // Keep a saved DIMM population when restoring a project.
let currentType  = null;
let resourceChart = null; // Chart.js instance for the Resource Capacity Summary chart
let storagePools = [];
let nextPoolId   = 1;
let currentProject = null; // null = unsaved | { name: string }
let _lastOneNode   = null; // tracks 1-node mode for UI re-render trigger

const IS_ADMIN = <?php echo $currentUser['role'] === 'admin' ? 'true' : 'false'; ?>;

// ── API helper ────────────────────────────────────────────────────────────────
async function api(body) {
    const r = await fetch(location.href, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body)});
    return r.json();
}

// ── Toast ─────────────────────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
    const colors = { success: 'bg-green-600', error: 'bg-red-600', info: 'bg-blue-600' };
    const el = document.createElement('div');
    el.className = `${colors[type] || colors.success} text-white text-[11px] font-bold px-4 py-2.5 rounded-xl shadow-lg`;
    el.innerText = msg;
    document.getElementById('toast').appendChild(el);
    setTimeout(() => el.remove(), 3500);
}

// ── Utility ───────────────────────────────────────────────────────────────────
function fmtDisk(gb) {
    if (gb >= 1000) { const tb = gb / 1000; return (tb === Math.floor(tb)) ? tb + ' TB' : tb.toFixed(1) + ' TB'; }
    return gb + ' GB';
}

function isInventoryAvailable(type, value) {
    return db.availability?.[type]?.[String(value)] !== false;
}

function availableInventoryValues(type, values) {
    return values.filter(value => isInventoryAvailable(type, value));
}

function availableServers() {
    return db.servers.filter(server => isInventoryAvailable('servers', server.name));
}

function fmtDate(iso) {
    if (!iso) return '—';
    const d = new Date(iso);
    return d.toLocaleDateString('th-TH', {day:'2-digit',month:'short',year:'numeric'}) + ' ' + d.toLocaleTimeString('th-TH',{hour:'2-digit',minute:'2-digit'});
}

function getValidStripeCounts(sockets, maxDimm, configuredCounts = []) {
    if (Array.isArray(configuredCounts) && configuredCounts.length) {
        return [...new Set(configuredCounts.map(Number))]
            .filter(count => Number.isInteger(count) && count > 0 && count <= (maxDimm || 32))
            .sort((a, b) => a - b);
    }
    const one = [4, 8, 12, 16];
    const two = [4, 8, 12, 16, 24, 32];
    return (sockets === 1 ? one : two).filter(c => c <= (maxDimm || 32));
}

// ─── 64 GB DIMM population rule ───────────────────────────────────────────────
// The factory default of 2 × 64 GB per node is not a usable production config.
// Whenever any 64 GB DIMM is installed in a node, that node needs at least
// 4 DIMMs installed in total.
const MIN_DIMMS_WITH_64GB = 4;
function uses64GbDimm(baseSz, baseCnt, expSz, expCnt) {
    return (baseCnt > 0 && Number(baseSz) === 64) || (expCnt > 0 && Number(expSz) === 64);
}
function meets64GbDimmRule(baseSz, baseCnt, expSz, expCnt) {
    return !uses64GbDimm(baseSz, baseCnt, expSz, expCnt) || (baseCnt + expCnt) >= MIN_DIMMS_WITH_64GB;
}

function formatMemoryEffectiveDate(iso) {
    if (!iso) return 'Current inventory';
    const date = new Date(`${iso}T00:00:00`);
    return `Effective ${date.toLocaleDateString('en-US', {month:'short', day:'numeric', year:'numeric'})}`;
}

function getCurrentHW() {
    const mVal = document.getElementById('selModel').value;
    if (mVal === 'custom') {
        return {
            name:    'Custom',
            socket:  Number(document.getElementById('mSocket').value)  || 2,
            core:    Number(document.getElementById('mCore').value)    || 16,
            freq:    Number(document.getElementById('mFreq').value)    || 2.8,
            baseRam: Number(document.getElementById('mBaseRam').value) || 128,
            maxDisk: Number(document.getElementById('mMaxDisk').value) || 12,
            maxDimm: Number(document.getElementById('mMaxDimm').value) || 24
        };
    }
    return db.servers.find(x => x.name === mVal) || availableServers()[0] || db.servers[0];
}

// ── Memory Tiering (DRAM + NVMe) ────────────────────────────────────────────────
function isAserverFamily(hw) {
    return (hw?.name || '').toLowerCase().startsWith('aserver');
}
function toggleMemTiering() {
    const on = document.getElementById('chkMemTiering').checked;
    document.getElementById('memTieringPanel').classList.toggle('hidden', !on);
    calculate();
}

// ── Memory / DIMM ─────────────────────────────────────────────────────────────
function updateRamOptions() {
    if (document.getElementById('selModel').value === 'custom') {
        calculate();
        return;
    }
    const hw      = getCurrentHW();
    const maxDimm = hw.maxDimm || 32;

    const baseSz  = Number(document.getElementById('selBaseDimmSize').value) || defaultDimmSizeGB;
    let   baseCnt = parseInt(document.getElementById('inpBaseDimmCount').value) || hw.baseDimmCount || Math.max(1, Math.round(hw.baseRam / baseSz));
    const expSz   = Number(document.getElementById('selExpDimmSize').value)  || defaultDimmSizeGB;

    // Clamp base count to [1, maxDimm]
    baseCnt = Math.max(1, Math.min(baseCnt, maxDimm));
    document.getElementById('inpBaseDimmCount').value = baseCnt;
    document.getElementById('inpBaseDimmCount').max = maxDimm;

    const baseRam     = baseCnt * baseSz;
    const remainSlots = maxDimm - baseCnt;

    // Valid stripe totals >= baseCnt and <= maxDimm
    const allStripes  = getValidStripeCounts(hw.socket, maxDimm, hw.memoryStripCounts);
    // A shipped base configuration (e.g. 2 × 64 GB) always permits no expansion.
    const savedTotals = restoredDimmTotal >= baseCnt && restoredDimmTotal <= maxDimm ? [restoredDimmTotal] : [];
    let validTotals = [...new Set([baseCnt, ...allStripes.filter(t => t >= baseCnt), ...savedTotals])].sort((a, b) => a - b);
    // 64 GB rule: drop any total below 4 DIMMs/node when a 64 GB DIMM is in use.
    const ruleTotals = validTotals.filter(t => meets64GbDimmRule(baseSz, baseCnt, expSz, t - baseCnt));
    if (ruleTotals.length) validTotals = ruleTotals;

    // Build expansion options
    const expSel  = document.getElementById('selExpDimmCount');
    const prevExp = Number(expSel.value) || 0;

    expSel.innerHTML = validTotals.map(total => {
        const ec       = total - baseCnt;
        const totalRam = baseRam + ec * expSz;
        const label    = ec === 0
            ? `No expansion → Total: ${baseRam} GB`
            : `+${ec} DIMMs (+${ec * expSz} GB) → ${totalRam} GB (${total}/${maxDimm} slots)`;
        return `<option value="${ec}" ${ec === prevExp ? 'selected' : ''}>${label}</option>`;
    }).join('');

    // Fall back to the smallest valid expansion if the previous one is no longer allowed
    const validExps = validTotals.map(t => t - baseCnt);
    if (!validExps.includes(prevExp)) expSel.value = String(validExps[0] ?? 0);

    const selectedExp = Number(expSel.value) || 0;
    const selectedTotal = baseCnt + selectedExp;
    const targetSel = document.getElementById('selMemoryTarget');
    targetSel.innerHTML = validTotals.map(total => {
        const added = total - baseCnt;
        const totalRam = baseRam + added * expSz;
        return `<option value="${total}">${added === 0
            ? `Default only — ${totalRam} GB (${baseCnt} × ${baseSz} GB)`
            : `${totalRam} GB total — add ${added} × ${expSz} GB (${total} DIMMs installed)`}</option>`;
    }).join('');
    targetSel.value = String(validTotals.includes(selectedTotal) ? selectedTotal : baseCnt);

    document.getElementById('lblBaseRamConfig').innerText = `${baseCnt} × ${baseSz} GB = ${baseRam} GB`;
    document.getElementById('lblMemoryEffectiveDate').innerText = formatMemoryEffectiveDate(hw.memoryEffectiveDate);
    document.getElementById('lblAdditionalRamSummary').innerText = selectedExp > 0
        ? `Add ${selectedExp} × ${expSz} GB = ${selectedExp * expSz} GB additional RAM`
        : 'No additional RAM selected';
    document.getElementById('lblRemainSlots').innerText = hw.memoryStripCounts?.length
        ? `Supported installed DIMM totals: ${hw.memoryStripCounts.join(', ')}`
        : `Supported installed DIMM totals: ${allStripes.join(', ')} · ${remainSlots} slots free at default`;
    if (uses64GbDimm(baseSz, baseCnt, expSz, 1)) {
        document.getElementById('lblRemainSlots').innerText += ` · 64 GB DIMM: min ${MIN_DIMMS_WITH_64GB} DIMMs/node`;
    }

    calculate();
}

function applyMemoryTarget() {
    if (document.getElementById('selModel').value === 'custom') return;
    const baseCnt = Math.max(1, Number(document.getElementById('inpBaseDimmCount').value) || 1);
    const targetTotal = Math.max(baseCnt, Number(document.getElementById('selMemoryTarget').value) || baseCnt);
    document.getElementById('selExpDimmCount').value = String(targetTotal - baseCnt);
    updateRamOptions();
}

// ── RAID Helpers ──────────────────────────────────────────────────────────────

function isOneNode() {
    return (parseInt(document.getElementById('qtyNode')?.value) || 3) === 1;
}
function isInventoryModel() {
    return document.getElementById('selModel')?.value !== 'custom';
}
function getRaidEfficiency(raidType, diskCount) {
    if (!diskCount || diskCount <= 0) return 0;
    if (diskCount === 1) return 1.0;
    switch (raidType) {
        case 'RAID10': return diskCount >= 2 ? 0.5 : 0;
        case 'RAID5':  return diskCount >= 3 ? (diskCount - 1) / diskCount : 0;
        case 'RAID6':  return diskCount >= 4 ? (diskCount - 2) / diskCount : 0;
        case 'RAID0':  return 1.0;
        default:       return 0.5;
    }
}
function raidMinDisks(raidType) {
    return { RAID10: 2, RAID5: 3, RAID6: 4, RAID0: 1 }[raidType] || 2;
}
function getDefaultRaid() { return isInventoryModel() ? 'RAID10' : 'RAID5'; }
function getValidRaidOptions(diskCount) {
    if (diskCount <= 0) return [];
    if (diskCount === 1) return ['RAID0'];
    if (diskCount === 2) return ['RAID10', 'RAID0'];
    if (diskCount === 3) return ['RAID5', 'RAID10', 'RAID0'];
    return ['RAID10', 'RAID5', 'RAID6', 'RAID0'];
}
function resolveRaidType(current, diskCount, isInv) {
    const valid = getValidRaidOptions(diskCount);
    if (valid.length === 0) return 'RAID0';
    if (valid.includes(current)) return current;
    const def = isInv ? 'RAID10' : 'RAID5';
    return valid.includes(def) ? def : valid[0];
}

// ── Storage Pools ─────────────────────────────────────────────────────────────

function buildDiskOptions(arr, selectedVal, type) {
    const inArr = arr.some(v => v == selectedVal);
    let opts = arr.map(v => {
        const selected = v == selectedVal && inArr;
        const available = isInventoryAvailable(type, v);
        return `<option value="${v}" ${selected ? 'selected' : ''} ${!available && !selected ? 'disabled' : ''}>${fmtDisk(v)}${available ? '' : ' — Unavailable'}</option>`;
    }).join('');
    opts += `<option value="custom" ${!inArr ? 'selected' : ''}>✏️ Custom (GB)...</option>`;
    return opts;
}

function renderPool(pool, idx) {
    const hw      = getCurrentHW();
    const nodes   = parseInt(document.getElementById('qtyNode')?.value) || 3;
    const oneNode = nodes === 1;
    const isInv   = isInventoryModel();

    const raidDiskCount  = pool.type === 'hybrid' ? pool.hddQty : pool.ssdQty;
    const raidType       = pool.raidType || getDefaultRaid();
    const validRaids     = getValidRaidOptions(raidDiskCount);
    const effectiveRaid  = resolveRaidType(raidType, raidDiskCount, isInv);
    const isSingleDisk   = raidDiskCount === 1;
    const isNoDisk       = raidDiskCount <= 0;

    const reqSSD      = (!oneNode && pool.type === 'hybrid') ? Math.ceil(pool.hddQty / 7) : 0;
    const hasRatioErr = !oneNode && (pool.type === 'hybrid') && pool.ssdQty < reqSSD;
    const wrongCopies = !oneNode && nodes < pool.copies;
    const unavailableHdd = pool.type === 'hybrid' && !isInventoryAvailable('hdds', pool.hddSize);
    const unavailableSsd = !(oneNode && pool.type === 'hybrid') && !isInventoryAvailable('ssds', pool.ssdSize);
    const minDisks    = raidMinDisks(effectiveRaid);
    const raidDiskErr = oneNode && raidDiskCount > 1 && raidDiskCount < minDisks;

    const hddInArr = db.hdds.some(v => v == pool.hddSize);
    const ssdInArr = db.ssds.some(v => v == pool.ssdSize);
    const poolDisks = oneNode
        ? (pool.type === 'hybrid' ? pool.hddQty : pool.ssdQty)
        : (pool.ssdQty + (pool.type === 'hybrid' ? pool.hddQty : 0));
    const canRemove = storagePools.length > 1;
    const hasAnyErr = hasRatioErr || wrongCopies || raidDiskErr || unavailableHdd || unavailableSsd;

    // Stat line
    let statLine = '';
    if (pool.type === 'hybrid') {
        let usable = pool.hddQty > 0
            ? (oneNode
                ? pool.hddSize * pool.hddQty / 1024 * getRaidEfficiency(effectiveRaid, pool.hddQty) * 0.85
                : (pool.hddSize * pool.hddQty * nodes / pool.copies) * 0.85 / 1024)
            : 0;
        statLine = oneNode
            ? (pool.hddQty === 0 ? 'No HDD configured' : `≈${usable.toFixed(1)} TB usable (${isSingleDisk ? 'Single Disk' : effectiveRaid})`)
            : `≈${usable.toFixed(1)}TB usable | ${pool.ssdSize * pool.ssdQty * nodes}GB cache`;
    } else {
        let usable = pool.ssdQty > 0
            ? (oneNode
                ? pool.ssdSize * pool.ssdQty / 1024 * getRaidEfficiency(effectiveRaid, pool.ssdQty) * 0.85
                : (pool.ssdSize * pool.ssdQty * nodes / pool.copies) * 0.85 / 1024)
            : 0;
        const label = oneNode ? (isSingleDisk ? 'Single Disk' : effectiveRaid) : 'All Flash';
        statLine = pool.ssdQty === 0 ? 'No SSD configured' : `≈${usable.toFixed(1)} TB usable (${label})`;
    }

    // RAID selector (1-node) or Copies (multi-node)
    const raidLabels = { RAID10:'RAID 10 (≥2)', RAID5:'RAID 5 (≥3)', RAID6:'RAID 6 (≥4)', RAID0:'RAID 0 (No Redundancy)' };
    const allRaids   = ['RAID10','RAID5','RAID6','RAID0'];
    let raidCtrlHtml;
    if (isNoDisk) {
        raidCtrlHtml = `<div><div class="text-gray-400" style="font-size:9px">RAID Type</div>
            <select disabled class="w-full border p-1 rounded bg-gray-100 text-gray-400 font-bold" style="font-size:10px"><option>— No Disks —</option></select></div>`;
    } else {
        const raidOpts = allRaids.map(r => `<option value="${r}" ${effectiveRaid===r?'selected':''}>${raidLabels[r]}</option>`).join('');
        raidCtrlHtml = `<div><div class="text-gray-400" style="font-size:9px">RAID Type</div>
            <select onchange="updatePool(${pool.id},'raidType',this.value)" class="w-full border p-1 rounded bg-orange-50 font-bold" style="font-size:10px">${raidOpts}</select></div>`;
    }
    const protectionCtrl = oneNode ? raidCtrlHtml : `<div>
        <div class="text-gray-400" style="font-size:9px">Protection</div>
        <select onchange="updatePool(${pool.id},'copies',this.value)" class="w-full border p-1 rounded bg-yellow-50 font-bold" style="font-size:10px">
            <option value="2" ${pool.copies==2?'selected':''}>2 Copies</option>
            <option value="3" ${pool.copies==3?'selected':''}>3 Copies</option>
        </select></div>`;

    // Pool type options
    const typeOptions = oneNode
        ? `<option value="hybrid"   ${pool.type==='hybrid'  ?'selected':''}>HDD Only</option>
           <option value="allflash" ${pool.type==='allflash'?'selected':''}>SSD / NVMe Only</option>`
        : `<option value="hybrid"   ${pool.type==='hybrid'  ?'selected':''}>Hybrid (HDD+SSD Cache)</option>
           <option value="allflash" ${pool.type==='allflash'?'selected':''}>All Flash (SSD)</option>`;

    // HDD section
    const hddSection = `<div class="mb-2" style="font-size:10px">
        <div class="text-gray-500 font-bold uppercase mb-1" style="font-size:9px">HDD Capacity Tier</div>
        <div class="flex gap-1">
            <div class="flex-1">
                <select onchange="handlePoolDisk(${pool.id},'hdd',this.value)" class="w-full border p-1 rounded bg-white" style="font-size:10px">${buildDiskOptions(db.hdds, pool.hddSize, 'hdds')}</select>
                <input type="number" id="cHDD-${pool.id}" value="${!hddInArr?pool.hddSize:''}" placeholder="Custom GB"
                    onchange="updatePool(${pool.id},'hddSize',this.value)"
                    class="w-full border p-1 rounded mt-1 ${hddInArr?'hidden':''}" style="font-size:10px">
            </div>
            <div style="width:54px">
                <div class="text-[9px] text-center text-gray-400">Qty/Node</div>
                <input type="number" value="${pool.hddQty}" min="0" onchange="updatePool(${pool.id},'hddQty',this.value)"
                    class="w-full border p-1 rounded text-center font-bold" style="font-size:10px">
            </div>
        </div></div>`;

    // SSD section (hidden in 1-node HDD-only mode)
    const showSSD = !(oneNode && pool.type === 'hybrid');
    const ssdLabel = pool.type === 'hybrid' ? 'SSD Cache Tier' : 'SSD Capacity Tier';
    const ssdSection = !showSSD ? '' : `<div style="font-size:10px">
        <div class="text-gray-500 font-bold uppercase mb-1" style="font-size:9px">${ssdLabel}</div>
        <div class="flex gap-1">
            <div class="flex-1">
                <select onchange="handlePoolDisk(${pool.id},'ssd',this.value)" class="w-full border p-1 rounded bg-white" style="font-size:10px">${buildDiskOptions(db.ssds, pool.ssdSize, 'ssds')}</select>
                <input type="number" id="cSSD-${pool.id}" value="${!ssdInArr?pool.ssdSize:''}" placeholder="Custom GB"
                    onchange="updatePool(${pool.id},'ssdSize',this.value)"
                    class="w-full border p-1 rounded mt-1 ${ssdInArr?'hidden':''}" style="font-size:10px">
            </div>
            <div style="width:54px">
                <div class="text-[9px] text-center text-gray-400">Qty/Node</div>
                <input type="number" value="${pool.ssdQty}" min="0" onchange="updatePool(${pool.id},'ssdQty',this.value)"
                    class="w-full border p-1 rounded text-center font-bold" style="font-size:10px">
            </div>
        </div></div>`;

    return `<div class="pool-card ${hasAnyErr ? 'has-error' : ''}">
        <div class="flex justify-between items-center mb-1">
            <span class="font-bold text-blue-700" style="font-size:11px">📦 Pool ${idx + 1}
                <span class="font-normal text-gray-400 text-[9px] ml-1">${poolDisks} disks/node</span>
            </span>
            <span class="pool-stat flex-1 text-center px-1">${statLine}</span>
            ${canRemove ? `<button onclick="removePool(${pool.id})" class="text-red-400 hover:text-red-600 font-bold text-sm ml-1" title="Remove">✕</button>` : '<span class="w-4"></span>'}
        </div>
        <div class="grid grid-cols-2 gap-1 mb-2" style="font-size:10px">
            <div>
                <div class="text-gray-400" style="font-size:9px">Pool Type</div>
                <select onchange="updatePool(${pool.id},'type',this.value)"
                    class="w-full border p-1 rounded ${pool.type==='hybrid'?'bg-yellow-50':'bg-blue-50'}" style="font-size:10px">
                    ${typeOptions}
                </select>
            </div>
            ${protectionCtrl}
        </div>
        ${pool.type === 'hybrid' ? hddSection : ''}
        ${ssdSection}
        ${hasRatioErr  ? `<div class="err-badge mt-1">⚠️ Hybrid needs min ${reqSSD} SSD/node for ${pool.hddQty} HDD (1 per 7)</div>` : ''}
        ${wrongCopies  ? `<div class="err-badge">⚠️ ${pool.copies}-Copy requires ≥${pool.copies} nodes (current: ${nodes})</div>` : ''}
        ${raidDiskErr  ? `<div class="err-badge mt-1">⚠️ ${effectiveRaid} requires min ${minDisks} disks (current: ${raidDiskCount})</div>` : ''}
        ${unavailableHdd ? `<div class="err-badge">⚠️ ${fmtDisk(pool.hddSize)} HDD is currently unavailable</div>` : ''}
        ${unavailableSsd ? `<div class="err-badge">⚠️ ${fmtDisk(pool.ssdSize)} SSD is currently unavailable</div>` : ''}
    </div>`;
}

// Render HTML only (no calculate call — avoids circular calls)
function renderPoolsHtml() {
    const c = document.getElementById('poolsContainer');
    if (!c) return;
    c.innerHTML = storagePools.map((p, i) => renderPool(p, i)).join('');
}

function renderPools() {
    renderPoolsHtml();
    calculate();
}

function addPool() {
    const id = nextPoolId++;
    const availableSsds = availableInventoryValues('ssds', db.ssds);
    const availableHdds = availableInventoryValues('hdds', db.hdds);
    const defaultSsd = availableSsds[1] || availableSsds[0] || 0;
    const defaultHdd = availableHdds[availableHdds.length > 3 ? 3 : 0] || availableHdds[0] || 0;
    storagePools.push({ id, type: 'hybrid', copies: 2, raidType: getDefaultRaid(),
        ssdSize: defaultSsd, ssdQty: defaultSsd ? 1 : 0,
        hddSize: defaultHdd, hddQty: defaultHdd ? 7 : 0 });
    renderPools();
}

function removePool(id) {
    if (storagePools.length <= 1) { showToast('At least 1 storage pool is required.', 'error'); return; }
    storagePools = storagePools.filter(p => p.id !== id);
    renderPools();
}

function updatePool(id, field, value) {
    const pool = storagePools.find(p => p.id === id);
    if (!pool) return;
    if (field === 'copies') value = Math.max(1, parseInt(value) || 1);
    if (['ssdQty','hddQty'].includes(field)) value = Math.max(0, parseInt(value) || 0);
    if (['ssdSize','hddSize'].includes(field)) {
        value = parseFloat(value) || 0;
        const type = field === 'ssdSize' ? 'ssds' : 'hdds';
        if (db[type].some(item => item == value) && !isInventoryAvailable(type, value)) {
            showToast(`${fmtDisk(value)} is currently unavailable in Inventory.`, 'error');
            renderPoolsHtml();
            return;
        }
    }
    pool[field] = value;
    if (field === 'type') renderPools(); else calculate();
}

function handlePoolDisk(poolId, diskType, value) {
    if (value === 'custom') {
        const inp = document.getElementById(`c${diskType.toUpperCase()}-${poolId}`);
        if (inp) inp.classList.remove('hidden');
        return;
    }
    const inp = document.getElementById(`c${diskType.toUpperCase()}-${poolId}`);
    if (inp) inp.classList.add('hidden');
    updatePool(poolId, diskType + 'Size', value);
}

// ── Calculate ─────────────────────────────────────────────────────────────────
function calculate() {
    const nodes   = Math.max(1, Number(document.getElementById('qtyNode').value) || 3);
    const oneNode = nodes === 1;

    // Re-render pool cards when 1-node mode changes (catches button-click without blur)
    if (_lastOneNode !== oneNode) { _lastOneNode = oneNode; renderPoolsHtml(); }

    const warn1 = document.getElementById('oneNodeWarning');
    if (warn1) warn1.classList.toggle('hidden', !oneNode);

    const hw = getCurrentHW();

    // Custom mode uses the entered capacity directly; hidden DIMMs never contribute.
    const isCustomMemory = document.getElementById('selModel').value === 'custom';
    // ── RAM from DIMM config (inventory models only) ──
    const baseSz       = Number(document.getElementById('selBaseDimmSize').value)  || defaultDimmSizeGB;
    const baseCnt      = isCustomMemory ? 0 : Math.max(1, parseInt(document.getElementById('inpBaseDimmCount').value) || hw.baseDimmCount || Math.max(1, Math.round(hw.baseRam / baseSz)));
    const expSz        = Number(document.getElementById('selExpDimmSize').value)   || defaultDimmSizeGB;
    const expCnt       = isCustomMemory ? 0 : Number(document.getElementById('selExpDimmCount').value) || 0;
    const totalDimm    = baseCnt + expCnt;
    const baseRamAmt   = isCustomMemory ? hw.baseRam : baseCnt * baseSz;
    const expRamAmt    = expCnt  * expSz;
    const totalRamNode = baseRamAmt + expRamAmt;

    const maxDimm      = hw.maxDimm || 32;
    const dimmOverLimit= totalDimm > maxDimm;
    const lblSummary   = document.getElementById('lblTotalRamSummary');
    if (lblSummary) {
        lblSummary.innerText = `Total RAM/Node: ${totalRamNode} GB | ${totalDimm}/${maxDimm} DIMM slots${dimmOverLimit ? ' ⚠️ OVER LIMIT' : ''}`;
        lblSummary.className = `text-[9px] font-bold rounded p-1.5 ${dimmOverLimit ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-900'}`;
    }

    const reserved  = 3;
    const nodeFreq  = hw.socket * hw.core * hw.freq * 1.2;
    const uFreqNode = (hw.socket * hw.core - reserved) * hw.freq * 1.2;
    const failNodes = Math.max(0, nodes - 1);

    // effSsd: 0 in 1-node HDD-only mode (SSD cache hidden & not installed)
    const effSsd = (p) => (oneNode && p.type === 'hybrid') ? 0 : p.ssdQty;

    // ── Memory Tiering: read inputs early (NVMe bay overhead affects disk slot count) ──
    const memTieringOn = document.getElementById('chkMemTiering')?.checked || false;
    const isAserver    = isAserverFamily(hw);
    const mtNvmeQty    = Math.max(0, parseInt(document.getElementById('inpNvmeQty')?.value) || 0);
    const nvmeBayOverhead = (memTieringOn && isAserver && mtNvmeQty > 0) ? (nvmeDb.specialBayOverhead || 4) : 0;

    // The NVMe special bay reserves slots out of the chassis first; the remainder
    // is what's left over for Storage Pools (Datastores).
    const poolDisksPerNode      = storagePools.reduce((s, p) => s + effSsd(p) + (p.type === 'hybrid' ? p.hddQty : 0), 0);
    const availDiskSlotsForPools= Math.max(0, hw.maxDisk - nvmeBayOverhead);
    const totalDisksPerNode     = poolDisksPerNode + nvmeBayOverhead;
    const diskOverLimit         = poolDisksPerNode > availDiskSlotsForPools;

    const dsBadge = document.getElementById('diskSlotBadge');
    if (dsBadge) {
        dsBadge.innerText = nvmeBayOverhead > 0
            ? `${poolDisksPerNode}/${availDiskSlotsForPools} datastore slots/node (NVMe bay −${nvmeBayOverhead}, chassis max ${hw.maxDisk})`
            : `${totalDisksPerNode}/${hw.maxDisk} disk slots/node`;
        dsBadge.className = 'text-[9px] px-2 py-0.5 rounded font-bold ' +
            (diskOverLimit ? 'bg-red-100 text-red-600' :
             poolDisksPerNode === availDiskSlotsForPools ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700');
    }

    const globalErrors = [];
    if (!isCustomMemory && !isInventoryAvailable('servers', hw.name)) globalErrors.push(`⚠️ ${hw.name} is currently unavailable`);
    if (!isCustomMemory && !isInventoryAvailable('memory', baseSz)) globalErrors.push(`⚠️ ${baseSz} GB base DIMM is currently unavailable`);
    if (!isCustomMemory && expCnt > 0 && !isInventoryAvailable('memory', expSz)) globalErrors.push(`⚠️ ${expSz} GB expansion DIMM is currently unavailable`);
    storagePools.forEach((pool, i) => {
        if (pool.type === 'hybrid' && !isInventoryAvailable('hdds', pool.hddSize)) globalErrors.push(`⚠️ Pool ${i+1}: ${fmtDisk(pool.hddSize)} HDD is currently unavailable`);
        if (!(oneNode && pool.type === 'hybrid') && !isInventoryAvailable('ssds', pool.ssdSize)) globalErrors.push(`⚠️ Pool ${i+1}: ${fmtDisk(pool.ssdSize)} SSD is currently unavailable`);
    });
    if (dimmOverLimit) globalErrors.push(`⚠️ DIMM count (${totalDimm}) exceeds max: ${maxDimm} slots`);
    if (!isCustomMemory && !meets64GbDimmRule(baseSz, baseCnt, expSz, expCnt)) {
        globalErrors.push(`⚠️ 64 GB DIMM requires at least ${MIN_DIMMS_WITH_64GB} DIMMs/node (currently ${totalDimm}) — 2 × 64 GB is not a usable configuration`);
    }
    if (diskOverLimit) globalErrors.push(nvmeBayOverhead > 0
        ? `⚠️ Datastore disk slots/node (${poolDisksPerNode}) exceeds the ${hw.name || 'this model'} available bays (${availDiskSlotsForPools} = ${hw.maxDisk} chassis − ${nvmeBayOverhead} NVMe special bay)`
        : `⚠️ Disk slots/node (${totalDisksPerNode}) exceeds max: ${hw.maxDisk} bays`);
    if (!oneNode && nodes <= 2 && storagePools.length > 1) globalErrors.push(`⚠️ 2-node: only 1 datastore supported`);
    storagePools.forEach((pool, i) => {
        if (!oneNode) {
            if (pool.type === 'hybrid') { const req = Math.ceil(pool.hddQty/7); if (effSsd(pool) < req) globalErrors.push(`⚠️ Pool ${i+1}: Need ${req} SSD/node`); }
            if (nodes < pool.copies) globalErrors.push(`⚠️ Pool ${i+1}: ${pool.copies}-Copy requires ≥${pool.copies} nodes`);
        } else {
            const rt = pool.raidType || getDefaultRaid();
            const dc = pool.type === 'hybrid' ? pool.hddQty : pool.ssdQty;
            const md = raidMinDisks(rt);
            if (dc < md) globalErrors.push(`⚠️ Pool ${i+1}: ${rt} requires ≥${md} disks (current: ${dc})`);
            if (rt === 'RAID0') globalErrors.push(`⚠️ Pool ${i+1}: RAID 0 has NO redundancy`);
        }
    });

    // ── Memory Tiering: validation + results ──
    const mtResultsEl = document.getElementById('mtResults');
    let mtSummaryRow = '';
    // Hoisted so the Resource Capacity Chart (built later, outside this block) can read them.
    let totalAvailMem = 0, maxTotalVmHW = 0;
    if (memTieringOn) {
        const nvmeSize     = Number(document.getElementById('selNvmeSize').value) || (nvmeDb.nvmeSizes ? nvmeDb.nvmeSizes[0] : 800);
        const platformOverhead = nvmeDb.platformOverheadPerNodeGB || 27;

        // All inputs below come straight from the HW cluster config already set
        // elsewhere on this page — DIMM config (totalRamNode), node count (nodes),
        // and the NVMe Disk selection (nvmeSize × mtNvmeQty). Nothing here is typed
        // in by the user as a memory "target"; it's all read back from the sizing.
        const nvmeCapPerNodeConfigured = nvmeSize * mtNvmeQty;
        // Max 1:1 DRAM:NVMe ratio rule — any NVMe beyond DRAM/node is not countable as
        // usable Tiering capacity, so cap it here instead of just flagging an error.
        const nvmeCapPerNode  = Math.min(nvmeCapPerNodeConfigured, totalRamNode);
        const nvmeCapCluster  = nvmeCapPerNode * nodes;
        const dramCapCluster  = totalRamNode * nodes;
        totalAvailMem   = dramCapCluster + nvmeCapCluster;

        const dramNvmeRatioOk = nvmeCapPerNodeConfigured <= totalRamNode;
        const wastedNvmePerNode = nvmeCapPerNodeConfigured - nvmeCapPerNode;
        // NVMe Headroom vs the 1:1 ratio limit — positive = room left to add more NVMe
        // before hitting the limit; negative = already over (that much/node is unused).
        const nvmeHeadroomPerNode  = totalRamNode - nvmeCapPerNodeConfigured;
        const nvmeHeadroomCluster  = nvmeHeadroomPerNode * nodes;

        if (mtNvmeQty <= 0) {
            globalErrors.push(`⚠️ Memory Tiering enabled: at least 1 NVMe disk per node is required (Tier 1 — Cold Tier)`);
        }
        if (mtNvmeQty > 0 && !dramNvmeRatioOk) {
            globalErrors.push(`⚠️ Memory Tiering: DRAM:NVMe ratio exceeds max 1:1 (DRAM ${totalRamNode} GB/node vs NVMe ${nvmeCapPerNodeConfigured} GB/node) — usable NVMe capped to ${nvmeCapPerNode} GB/node, ${wastedNvmePerNode.toLocaleString()} GB/node not counted`);
        }
        globalErrors.push(`ℹ️ Memory Tiering enabled: Memory Overcommit must be DISABLED on all tiering-enabled nodes`);

        // ── Max usable VM memory this HW config can support, per the SAME two rules
        //    used before (Minimal-DRAM rule and Total-Available rule) — just solved
        //    as a ceiling instead of checked against a manually-typed demand figure.
        const maxVmFromDramRule  = totalRamNode * nodes * 0.8 - platformOverhead * nodes; // Minimal-DRAM rule
        const maxVmFromAvailRule = totalAvailMem - platformOverhead * nodes;              // Total-Available rule
        maxTotalVmHW       = Math.max(0, Math.min(maxVmFromDramRule, maxVmFromAvailRule));
        // Active(hot) memory ceiling per the 50–60% Active-Ratio rule — a worst-case
        // assumption (not a measured value), since real hot-data % depends on workload.
        const maxActiveMemory    = totalAvailMem * 0.6;

        if (mtResultsEl) {
            const row = (label, val, ok) =>
                `<div class="flex justify-between items-center py-0.5"><span class="text-gray-500">${label}</span>` +
                `<span class="font-bold ${ok === undefined ? 'text-gray-700' : (ok ? 'text-green-600' : 'text-red-600')}">${val}</span></div>`;
            mtResultsEl.innerHTML =
                `<div class="font-bold text-purple-700 uppercase text-[9px] mb-1">Memory Tiering — Derived From This Cluster's HW Sizing</div>` +
                row('DRAM Capacity (cluster)', `${dramCapCluster.toLocaleString()} GB`, undefined) +
                row('NVMe Configured / Node', `${nvmeCapPerNodeConfigured.toLocaleString()} GB`, undefined) +
                row('NVMe Usable / Node (capped @ max 1:1)', `${nvmeCapPerNode.toLocaleString()} GB`, dramNvmeRatioOk) +
                row('DRAM : NVMe Ratio / Node', `1 : 1 (Recommended)`, dramNvmeRatioOk) +
                row('NVMe Headroom vs 1:1 Ratio / Node', nvmeHeadroomPerNode >= 0
                        ? `+${nvmeHeadroomPerNode.toLocaleString()} GB (room to add more NVMe)`
                        : `−${Math.abs(nvmeHeadroomPerNode).toLocaleString()} GB (over limit, unused)`,
                    nvmeHeadroomPerNode >= 0) +
                row('NVMe Headroom vs 1:1 Ratio (cluster)', nvmeHeadroomCluster >= 0
                        ? `+${nvmeHeadroomCluster.toLocaleString()} GB`
                        : `−${Math.abs(nvmeHeadroomCluster).toLocaleString()} GB`,
                    nvmeHeadroomCluster >= 0) +
                row('NVMe Capacity (cluster, usable)', `${nvmeCapCluster.toLocaleString()} GB`, undefined) +
                row('Total Available Memory (cluster, usable)', `${totalAvailMem.toLocaleString()} GB`, undefined) +
                row('NVMe Bay Overhead / Node', `${nvmeBayOverhead} disk slot(s)`, undefined) +
                `<div class="border-t border-purple-100 mt-1.5 pt-1.5">` +
                row('Max VM Memory usable w/ Tiering enabled (cluster)', `${maxTotalVmHW.toLocaleString()} GB`, undefined) +
                row('Max Active (hot) Memory @ 60% ceiling (cluster)', `${maxActiveMemory.toLocaleString()} GB`, undefined) +
                `</div>`;
        }

        mtSummaryRow = `
        <tr class="bg-purple-50 font-bold">
            <td class="px-6 py-3 text-left text-purple-800">
                Max VM Memory Usable — Tiering Enabled (GB)
                <div class="text-[9px] font-normal text-gray-500 mt-0.5">DRAM (${totalRamNode}×${nodes}) + NVMe usable@1:1 (${nvmeCapPerNode.toLocaleString()}×${nodes}), capped by Min-DRAM &amp; Avail-Mem rules</div>
            </td>
            <td class="text-center text-purple-700">${totalAvailMem.toLocaleString()}</td>
            <td class="usable-cell">${maxTotalVmHW.toLocaleString()}</td>
            <td class="text-center text-gray-400">cluster total</td>
        </tr>`;
    } else if (mtResultsEl) {
        mtResultsEl.innerHTML = '';
    }

    const errEl = document.getElementById('poolGlobalError');
    if (errEl) {
        if (globalErrors.length) { errEl.innerHTML = globalErrors.map(e => `<div>${e}</div>`).join(''); errEl.classList.remove('hidden'); }
        else errEl.classList.add('hidden');
    }

    let totalCacheGB = 0, totalCacheFailGB = 0, totalUsableTB = 0, totalRawTB = 0;
    let hybridHDDRawTB = 0, hybridSSDCacheGB = 0, diskOverheadPerNode = 0;

    storagePools.forEach(pool => {
        const rt   = pool.raidType || getDefaultRaid();
        const eSsd = effSsd(pool);
        if (pool.type === 'hybrid') {
            diskOverheadPerNode += (0.5 * pool.ssdSize * eSsd / 100) + pool.hddQty;
            const rawHDD   = pool.hddSize * pool.hddQty * nodes;
            const usable   = oneNode
                ? pool.hddSize * pool.hddQty / 1024 * getRaidEfficiency(rt, pool.hddQty) * 0.85
                : (rawHDD / pool.copies) * 0.85 / 1024;
            const cacheAll = pool.ssdSize * eSsd * (oneNode ? 1 : nodes);
            const cacheFail= oneNode ? 0 : pool.ssdSize * eSsd * failNodes;
            totalCacheGB += cacheAll; totalCacheFailGB += cacheFail;
            totalUsableTB += usable;  totalRawTB += rawHDD / 1024;
            hybridHDDRawTB += rawHDD / 1024; hybridSSDCacheGB += cacheAll;
        } else {
            diskOverheadPerNode += eSsd;
            const rawSSD = pool.ssdSize * eSsd * nodes;
            const usable = oneNode
                ? pool.ssdSize * eSsd / 1024 * getRaidEfficiency(rt, eSsd) * 0.85
                : (rawSSD / pool.copies) * 0.85 / 1024;
            totalUsableTB += usable; totalRawTB += rawSSD / 1024;
        }
    });

    const sysConsumePerNode = 21 + diskOverheadPerNode;
    const cacheRatio = hybridHDDRawTB > 0 ? (hybridSSDCacheGB / 1024 / hybridHDDRawTB * 100) : 0;
    const ratioColor = cacheRatio >= 5 ? 'text-green-600' : 'text-red-600';
    const usableRamNode = Math.max(0, totalRamNode - sysConsumePerNode);

    document.getElementById('smNodes').innerText  = nodes;
    document.getElementById('smCPU').innerText    = (uFreqNode * nodes).toFixed(1) + 'G';
    document.getElementById('smRAM').innerText    = (usableRamNode * nodes).toFixed(0);
    document.getElementById('smStore').innerText  = totalUsableTB.toFixed(1) + 'TB';

    document.getElementById('f-cpu').innerHTML =
        `<strong>CPU:</strong> ${hw.socket}S × ${hw.core} cores × ${hw.freq} GHz × 1.2 boost = <strong>${nodeFreq.toFixed(1)} GHz/node</strong>`;
    document.getElementById('f-ram').innerHTML =
        `<strong>RAM:</strong> System Consume = 21 + ${diskOverheadPerNode.toFixed(1)} disk overhead = <strong>${sysConsumePerNode.toFixed(1)} GB</strong> → VM Usable = <strong>${usableRamNode.toFixed(0)} GB/node</strong>`;
    document.getElementById('f-storage').innerHTML = oneNode
        ? `<strong>Storage (1-Node RAID):</strong> ${storagePools.length} Pool(s) | RAW: ${totalRawTB.toFixed(2)} TB → RAID + 15% eff. = <strong>${totalUsableTB.toFixed(2)} TB usable</strong>`
        : `<strong>Storage:</strong> ${storagePools.length} Pool(s) | RAW: ${totalRawTB.toFixed(2)} TB → After copies + 15% eff. = <strong>${totalUsableTB.toFixed(2)} TB usable</strong>`;

    // Pool rows for summary table
    let poolRows = '';
    storagePools.forEach((pool, i) => {
        const rt = pool.raidType || getDefaultRaid();
        const eSsd = effSsd(pool);
        const protLabel = oneNode ? rt : `${pool.copies}-Copy`;
        if (pool.type === 'hybrid') {
            const rawHDD    = pool.hddSize * pool.hddQty * nodes / 1024;
            const usable    = oneNode ? pool.hddSize * pool.hddQty / 1024 * getRaidEfficiency(rt, pool.hddQty) * 0.85 : (rawHDD / pool.copies) * 0.85;
            const cache     = pool.ssdSize * eSsd * (oneNode ? 1 : nodes);
            const ratio     = pool.hddQty > 0 ? (eSsd / pool.hddQty * 100).toFixed(0) : 0;
            const rOk       = Number(ratio) >= (100/7);
            const poolDisks = pool.hddQty + eSsd;
            const diskDesc  = oneNode ? `${pool.hddQty}×${fmtDisk(pool.hddSize)} HDD` : `${pool.hddQty}×${fmtDisk(pool.hddSize)} HDD + ${eSsd}×${fmtDisk(pool.ssdSize)} SSD`;
            const lastCol   = oneNode ? protLabel : `Cache: ${cache} GB | Ratio: <span class="${rOk?'text-green-600':'text-red-600'} font-bold">${ratio}% ${rOk?'✓':'⚠'}</span>`;
            poolRows += `<tr class="bg-orange-50/40">
                <td class="px-6 py-2 font-bold text-left text-[10px] text-orange-700">${oneNode?'HDD Only':'Hybrid'} Pool ${i+1} (${protLabel}) — ${diskDesc}/node [${poolDisks} disks/node]</td>
                <td class="text-center text-[10px]">${rawHDD.toFixed(1)} TB RAW</td>
                <td class="usable-cell text-[10px]">${usable.toFixed(2)} TB</td>
                <td class="text-center text-[10px]">${lastCol}</td></tr>`;
        } else {
            const rawSSD    = pool.ssdSize * eSsd * nodes / 1024;
            const usable    = oneNode ? pool.ssdSize * eSsd / 1024 * getRaidEfficiency(rt, eSsd) * 0.85 : (rawSSD / pool.copies) * 0.85;
            poolRows += `<tr class="bg-blue-50/40">
                <td class="px-6 py-2 font-bold text-left text-[10px] text-blue-700">All Flash Pool ${i+1} (${protLabel}) — ${eSsd}×${fmtDisk(pool.ssdSize)} SSD/node [${eSsd} disks/node]</td>
                <td class="text-center text-[10px]">${rawSSD.toFixed(1)} TB RAW</td>
                <td class="usable-cell text-[10px]">${usable.toFixed(2)} TB</td>
                <td class="usable-cell text-[10px]">${usable.toFixed(2)} TB</td></tr>`;
        }
    });

    document.getElementById('summaryBody').innerHTML = `
        <tr class="bg-gray-100"><td class="px-6 py-1.5 font-bold text-left text-[9px] text-gray-400 uppercase tracking-widest" colspan="4">CPU Resources</td></tr>
        <tr><td class="px-6 py-3 font-bold text-left">CPU Computing (GHz)</td><td>${(nodeFreq*nodes).toFixed(2)}</td><td class="usable-cell">${(uFreqNode*nodes).toFixed(2)}</td><td class="fail-cell">${(uFreqNode*failNodes).toFixed(2)}</td></tr>
        <tr><td class="px-6 py-3 font-bold text-left">CPU Core (Total/VM)</td><td>${hw.socket*hw.core*nodes}</td><td class="usable-cell">${(hw.socket*hw.core-reserved)*nodes}</td><td class="fail-cell">${(hw.socket*hw.core-reserved)*failNodes}</td></tr>
        <tr class="italic"><td class="px-6 py-2 text-left text-gray-500">vCore Limit (1:3)</td><td>≤${hw.socket*hw.core*nodes*3}</td><td class="text-blue-700 font-bold">≤${(hw.socket*hw.core-reserved)*nodes*3}</td><td class="text-red-700 font-bold">≤${(hw.socket*hw.core-reserved)*failNodes*3}</td></tr>
        <tr class="italic border-b-2 border-gray-200"><td class="px-6 py-2 text-left text-gray-500">vCore Limit (1:6)</td><td>≤${hw.socket*hw.core*nodes*6}</td><td class="text-blue-700 font-bold">≤${(hw.socket*hw.core-reserved)*nodes*6}</td><td class="text-red-700 font-bold">≤${(hw.socket*hw.core-reserved)*failNodes*6}</td></tr>
        <tr class="bg-gray-100"><td class="px-6 py-1.5 font-bold text-left text-[9px] text-gray-400 uppercase tracking-widest" colspan="4">Memory Resources</td></tr>
        <tr><td class="px-6 py-3 font-bold text-left">RAM Installed (GB)<div class="text-[9px] font-normal text-gray-400 mt-0.5">${isCustomMemory ? 'Custom Base RAM — ' + totalRamNode + ' GB/node' : `${totalDimm} DIMMs: ${baseCnt}×${baseSz}GB base${expCnt>0?` + ${expCnt}×${expSz}GB exp.`:''}`}</div></td><td class="font-bold">${totalRamNode*nodes}</td><td class="font-bold text-center">${totalRamNode}</td><td class="text-center text-gray-400">per node</td></tr>
        <tr><td class="px-6 py-2 text-left text-gray-500 italic">System Consume<div class="text-[9px] font-normal text-gray-400">= 21 + disk overhead</div></td><td class="text-center font-semibold text-orange-600">${(sysConsumePerNode*nodes).toFixed(0)}</td><td class="text-center text-orange-600">${sysConsumePerNode.toFixed(0)}</td><td class="text-center text-gray-400">per node</td></tr>
        <tr class="border-b-2 border-gray-200"><td class="px-6 py-3 font-bold text-left text-green-800">Memory Capacity — VM Usable (GB)<div class="text-[9px] font-normal text-gray-500 mt-0.5">RAM − Sys Consume = ${totalRamNode} − ${sysConsumePerNode.toFixed(1)}</div></td><td class="font-bold">${totalRamNode*nodes}</td><td class="usable-cell">${(usableRamNode*nodes).toFixed(0)}<div class="text-[8px] font-normal text-green-700">${usableRamNode.toFixed(0)}×${nodes} nodes</div></td><td class="fail-cell">${(usableRamNode*failNodes).toFixed(0)}<div class="text-[8px] font-normal text-red-700">${usableRamNode.toFixed(0)}×${failNodes} nodes</div></td></tr>
        ${mtSummaryRow}
        <tr class="bg-gray-100"><td class="px-6 py-1.5 font-bold text-left text-[9px] text-gray-400 uppercase tracking-widest" colspan="4">Storage Pools</td></tr>
        ${poolRows}
        ${totalCacheGB > 0 ? `<tr class="bg-blue-50 font-bold"><td class="px-6 py-3 text-left text-blue-800">Cache Usable Total (GB)</td><td class="text-center text-blue-700">${totalCacheGB}</td><td class="usable-cell">${totalCacheGB}</td><td class="fail-cell">${totalCacheFailGB}</td></tr>` : ''}
        <tr class="bg-orange-50 font-bold border-b-2 border-orange-200"><td class="px-6 py-3 text-left text-orange-800">Usable Storage Total (TB)</td><td class="text-center">${totalRawTB.toFixed(1)}</td><td class="usable-cell">${totalUsableTB.toFixed(2)}</td><td class="usable-cell">${totalUsableTB.toFixed(2)}</td></tr>
        ${hybridHDDRawTB > 0 ? `<tr class="bg-gray-50"><td class="px-6 py-3 font-bold text-left text-gray-400">SSD Cache Ratio (Hybrid pools)</td><td colspan="3" class="text-right px-6 font-bold ${ratioColor}">${cacheRatio.toFixed(2)}%<span class="text-[9px] text-gray-400 italic ml-2">(Target ≥ 5%)</span></td></tr>` : ''}
    `;

    const mName    = document.getElementById('selModel').value;
    const dispName = mName === 'custom' ? 'Custom Configuration' : mName;

    // Pool detail rows
    let poolDetailRows = '';
    storagePools.forEach((pool, i) => {
        const rt        = pool.raidType || getDefaultRaid();
        const eSsd      = effSsd(pool);
        const protLabel = oneNode ? rt : `${pool.copies}-Copy`;
        const poolDisks = eSsd + (pool.type === 'hybrid' ? pool.hddQty : 0);
        const typeLabel = oneNode && pool.type === 'hybrid' ? 'HDD Only' : (pool.type === 'hybrid' ? 'Hybrid' : 'All Flash');
        const perNode   = pool.type === 'hybrid'
            ? (oneNode ? `${pool.hddQty}× ${fmtDisk(pool.hddSize)} HDD` : `${pool.hddQty}× ${fmtDisk(pool.hddSize)} HDD + ${eSsd}× ${fmtDisk(pool.ssdSize)} SSD`)
            : `${eSsd}× ${fmtDisk(pool.ssdSize)} SSD`;
        const clusterRaw= pool.type === 'hybrid'
            ? (oneNode ? `${(pool.hddSize*pool.hddQty/1024).toFixed(1)} TB HDD` : `${(pool.hddSize*pool.hddQty*nodes/1024).toFixed(1)} TB HDD + ${pool.ssdSize*eSsd*nodes} GB cache`)
            : (oneNode ? `${(pool.ssdSize*eSsd/1024).toFixed(1)} TB` : `${(pool.ssdSize*eSsd*nodes/1024).toFixed(1)} TB`);
        const usable    = pool.type === 'hybrid'
            ? (oneNode ? pool.hddSize*pool.hddQty/1024*getRaidEfficiency(rt,pool.hddQty)*0.85 : (pool.hddSize*pool.hddQty*nodes/pool.copies)*0.85/1024)
            : (oneNode ? pool.ssdSize*eSsd/1024*getRaidEfficiency(rt,eSsd)*0.85 : (pool.ssdSize*eSsd*nodes/pool.copies)*0.85/1024);
        const raidNote  = oneNode
            ? `${rt} (eff. ${(getRaidEfficiency(rt, pool.type==='hybrid'?pool.hddQty:eSsd)*100).toFixed(0)}%) × 0.85`
            : `${pool.copies}-Copy × 0.85 eff.`;
        poolDetailRows += `
        <tr class="bg-orange-50/30"><td class="px-4 py-2 font-bold text-orange-700 text-[10px]">Pool ${i+1} — ${typeLabel} | ${protLabel} | ${poolDisks} disks/node</td><td class="px-4 py-2 text-[10px]">${perNode}</td><td class="text-center font-bold text-[10px]">${poolDisks}</td><td class="text-center font-bold text-blue-700 text-[10px]">${clusterRaw}</td></tr>
        <tr><td class="px-4 py-2 text-gray-400 italic text-[10px]" colspan="2">↳ Usable (${raidNote})</td><td class="usable-cell text-center text-[10px]" colspan="2">${usable.toFixed(2)} TB</td></tr>`;
    });

    // Equipment Summary rows
    let eqRows = `<tr class="bg-slate-50"><td class="px-4 py-2 font-bold text-[10px]">🖥️ Server Node</td><td class="px-4 py-2 text-gray-600 text-[10px]">${dispName}</td><td class="text-center font-bold text-[10px]">1 unit</td><td class="text-center font-bold text-blue-700 text-[10px]">${nodes} units</td></tr>`;
    eqRows += `<tr><td class="px-4 py-2 font-bold text-[10px]">⚙️ CPU</td><td class="px-4 py-2 text-gray-600 text-[10px]">${hw.socket}-Socket × ${hw.core} cores @ ${hw.freq} GHz</td><td class="text-center font-bold text-[10px]">${hw.socket} CPU</td><td class="text-center font-bold text-blue-700 text-[10px]">${hw.socket*nodes} CPUs</td></tr>`;
    eqRows += `<tr class="bg-slate-50"><td class="px-4 py-2 font-bold text-[10px]">💾 ${isCustomMemory ? 'RAM (Custom)' : 'RAM DIMM (Base)'}</td><td class="px-4 py-2 text-gray-600 text-[10px]">${isCustomMemory ? 'Base RAM from Custom Configuration' : `${baseSz} GB × ${baseCnt} slots`}</td><td class="text-center font-bold text-[10px]">${isCustomMemory ? `${baseRamAmt} GB` : `${baseCnt} DIMMs`}</td><td class="text-center font-bold text-blue-700 text-[10px]">${isCustomMemory ? `${baseRamAmt*nodes} GB` : `${baseCnt*nodes} DIMMs`}</td></tr>`;
    if (expCnt > 0) eqRows += `<tr><td class="px-4 py-2 font-bold text-[10px]">💾 RAM DIMM (Exp.)</td><td class="px-4 py-2 text-gray-600 text-[10px]">${expSz} GB × ${expCnt} slots</td><td class="text-center font-bold text-[10px]">${expCnt} DIMMs</td><td class="text-center font-bold text-blue-700 text-[10px]">${expCnt*nodes} DIMMs</td></tr>`;
    storagePools.forEach((pool, i) => {
        const rt = pool.raidType || getDefaultRaid();
        const eSsd = effSsd(pool);
        const protLabel = oneNode ? rt : `${pool.copies}-Copy`;
        const bg = i%2===0 ? 'bg-slate-50' : '';
        if (pool.type === 'hybrid') {
            if (pool.hddQty > 0) eqRows += `<tr class="${bg}"><td class="px-4 py-2 font-bold text-[10px]">🗄️ HDD — Pool ${i+1}</td><td class="px-4 py-2 text-gray-600 text-[10px]">${fmtDisk(pool.hddSize)} HDD (${protLabel})</td><td class="text-center font-bold text-[10px]">${pool.hddQty} disks</td><td class="text-center font-bold text-blue-700 text-[10px]">${pool.hddQty*nodes} disks</td></tr>`;
            if (eSsd > 0)         eqRows += `<tr class="${bg}"><td class="px-4 py-2 font-bold text-[10px]">⚡ SSD Cache — Pool ${i+1}</td><td class="px-4 py-2 text-gray-600 text-[10px]">${fmtDisk(pool.ssdSize)} SSD (Cache)</td><td class="text-center font-bold text-[10px]">${eSsd} disks</td><td class="text-center font-bold text-blue-700 text-[10px]">${eSsd*nodes} disks</td></tr>`;
        } else {
            if (eSsd > 0)         eqRows += `<tr class="${bg}"><td class="px-4 py-2 font-bold text-[10px]">⚡ SSD — Pool ${i+1}</td><td class="px-4 py-2 text-gray-600 text-[10px]">${fmtDisk(pool.ssdSize)} NVMe/SSD (${protLabel})</td><td class="text-center font-bold text-[10px]">${eSsd} disks</td><td class="text-center font-bold text-blue-700 text-[10px]">${eSsd*nodes} disks</td></tr>`;
        }
    });
    eqRows += `<tr class="bg-blue-50 font-bold border-t-2 border-blue-200"><td class="px-4 py-2 text-blue-800 text-[10px]" colspan="2">Total Disk Drives</td><td class="text-center text-blue-700 text-[10px]">${totalDisksPerNode} disks/node</td><td class="text-center text-blue-700 text-[10px]">${totalDisksPerNode*nodes} disks total</td></tr>`;

    document.getElementById('detailBody').innerHTML = `
        <tr class="bg-[#1e3a8a] text-white"><td class="px-4 py-3 font-bold" colspan="2">🖥️ Server: ${dispName}${oneNode?' — Single Node Mode':''}</td><td class="text-center font-bold text-blue-200 text-[10px]">Per Node</td><td class="text-center font-bold text-blue-200 text-[10px]">${oneNode?'1 Node Total':'Cluster (×'+nodes+')'}</td></tr>
        <tr class="bg-gray-100"><td class="px-4 py-1 text-[9px] font-bold text-gray-400 uppercase" colspan="4">CPU</td></tr>
        <tr><td class="px-4 py-2 font-bold">Socket / Cores</td><td class="px-4 py-2 text-gray-600">${hw.socket}-Socket, ${hw.socket}×${hw.core} cores @ ${hw.freq} GHz</td><td class="text-center font-bold">${hw.socket*hw.core}</td><td class="text-center font-bold text-blue-700">${hw.socket*hw.core*nodes}</td></tr>
        <tr><td class="px-4 py-2 font-bold">CPU Frequency</td><td class="px-4 py-2 text-gray-600">${hw.freq} GHz × 1.2 boost factor</td><td class="text-center font-bold">${nodeFreq.toFixed(1)} GHz</td><td class="text-center font-bold text-blue-700">${(nodeFreq*nodes).toFixed(1)} GHz</td></tr>
        <tr class="bg-gray-100"><td class="px-4 py-1 text-[9px] font-bold text-gray-400 uppercase" colspan="4">Memory</td></tr>
        <tr><td class="px-4 py-2 font-bold">Base Config RAM</td><td class="px-4 py-2 text-gray-600">${isCustomMemory ? 'Base RAM from Custom Configuration' : `${baseCnt}× ${baseSz} GB DIMM (${baseCnt}/${maxDimm} slots)`}</td><td class="text-center font-bold">${baseRamAmt} GB</td><td class="text-center font-bold text-blue-700">${baseRamAmt*nodes} GB</td></tr>
        ${expCnt>0?`<tr><td class="px-4 py-2 font-bold">Expansion RAM</td><td class="px-4 py-2 text-gray-600">${expCnt}× ${expSz} GB DIMM (${totalDimm}/${maxDimm} total)</td><td class="text-center font-bold">${expRamAmt} GB</td><td class="text-center font-bold text-blue-700">${expRamAmt*nodes} GB</td></tr>`:''}
        <tr><td class="px-4 py-2 font-bold">Total Installed RAM</td><td class="px-4 py-2 text-gray-600">${isCustomMemory ? 'Custom capacity; DIMM layout not specified' : `${totalDimm} DIMMs (Max: ${maxDimm} slots)`}</td><td class="text-center font-bold">${totalRamNode} GB</td><td class="text-center font-bold text-blue-700">${totalRamNode*nodes} GB</td></tr>
        <tr><td class="px-4 py-2 font-bold text-orange-700">System Consume</td><td class="px-4 py-2 text-gray-600">21 + disk overhead = 21 + ${diskOverheadPerNode.toFixed(1)} GB</td><td class="text-center text-orange-600 font-bold">${sysConsumePerNode.toFixed(1)} GB</td><td class="text-center text-orange-600 font-bold">${(sysConsumePerNode*nodes).toFixed(1)} GB</td></tr>
        <tr><td class="px-4 py-2 font-bold text-green-700">VM-Usable Memory</td><td class="px-4 py-2 text-gray-600">RAM − System Consume = ${totalRamNode} − ${sysConsumePerNode.toFixed(1)}</td><td class="text-center font-bold text-green-600">${usableRamNode.toFixed(0)} GB</td><td class="text-center font-bold text-green-700">${(usableRamNode*nodes).toFixed(0)} GB</td></tr>
        <tr class="bg-gray-100"><td class="px-4 py-1 text-[9px] font-bold text-gray-400 uppercase" colspan="4">Chassis</td></tr>
        <tr><td class="px-4 py-2 font-bold">Max Disk Bays</td><td class="px-4 py-2 text-gray-600">${diskOverLimit?'⚠️ EXCEEDED — ':''}Configured: ${totalDisksPerNode} / ${hw.maxDisk} bays</td><td class="text-center font-bold ${diskOverLimit?'text-red-600':'text-gray-700'}">${hw.maxDisk} bays</td><td class="text-center text-gray-400">–</td></tr>
        <tr><td class="px-4 py-2 font-bold">Max DIMM Slots</td><td class="px-4 py-2 text-gray-600">Physical capacity of this platform</td><td class="text-center font-bold">${maxDimm} slots</td><td class="text-center text-gray-400">–</td></tr>
        <tr class="bg-gray-100"><td class="px-4 py-1 text-[9px] font-bold text-gray-400 uppercase" colspan="4">Storage Pool Configuration</td></tr>
        ${poolDetailRows}
        <tr class="bg-blue-900 text-white font-bold"><td class="px-4 py-2" colspan="2">Total Usable Storage</td><td class="text-center">${totalUsableTB.toFixed(2)} TB</td><td class="text-center text-green-300">${totalUsableTB.toFixed(2)} TB</td></tr>
        <tr class="bg-[#1e3a8a] text-white"><td class="px-4 py-3 font-bold" colspan="2">📦 Equipment Summary — Bill of Materials</td><td class="text-center font-bold text-blue-200 text-[10px]">1 Node Qty</td><td class="text-center font-bold text-blue-200 text-[10px]">Total (×${nodes} Nodes)</td></tr>
        ${eqRows}
        <tr>
            <td colspan="4" class="px-4 py-4 bg-gray-50">
                <div class="text-[9px] font-bold text-gray-400 uppercase mb-2">📊 Resource Capacity Chart — Usable % of Total Cluster</div>
                <canvas id="resourceChartCanvas" height="160"></canvas>
            </td>
        </tr>
    `;

    renderResourceChart({
        cpu:   { total: nodeFreq * nodes,     usable: uFreqNode * nodes,     afterFail: uFreqNode * failNodes },
        ram:   { total: totalRamNode * nodes, usable: usableRamNode * nodes, afterFail: usableRamNode * failNodes },
        storage: { total: totalRawTB,         usable: totalUsableTB,         afterFail: totalUsableTB },
        tiering: memTieringOn ? { total: totalAvailMem, usable: maxTotalVmHW, afterFail: null } : null
    });
}

// ── Resource Capacity Chart ───────────────────────────────────────────────────
function renderResourceChart(data) {
    const canvas = document.getElementById('resourceChartCanvas');
    if (!canvas || typeof Chart === 'undefined') return;

    const pct = (v, total) => (total > 0 ? Math.max(0, Math.min(100, v / total * 100)) : 0);

    const labels      = ['CPU (GHz)', 'RAM (GB)', 'Storage (TB)'];
    const usablePct   = [pct(data.cpu.usable, data.cpu.total), pct(data.ram.usable, data.ram.total), pct(data.storage.usable, data.storage.total)];
    const failPct     = [pct(data.cpu.afterFail, data.cpu.total), pct(data.ram.afterFail, data.ram.total), pct(data.storage.afterFail, data.storage.total)];

    if (data.tiering) {
        labels.push('Mem Tiering (GB)');
        usablePct.push(pct(data.tiering.usable, data.tiering.total));
        failPct.push(null); // no after-node-failure figure tracked for Memory Tiering pool
    }

    if (resourceChart) resourceChart.destroy();
    resourceChart = new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'Usable %',            data: usablePct, backgroundColor: '#16a34a' },
                { label: 'After Node Failure %', data: failPct,   backgroundColor: '#dc2626' }
            ]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            scales: { x: { min: 0, max: 100, ticks: { callback: v => v + '%' } } },
            plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } } }
        }
    });
}

// ── Model Change ──────────────────────────────────────────────────────────────
function handleModelChange() {
    restoredDimmTotal = null;
    const hw       = getCurrentHW();
    const isCustom = document.getElementById('selModel').value === 'custom';
    const selectedModel = document.getElementById('selModel').value;
    const availabilityWarning = document.getElementById('inventoryAvailabilityWarning');
    if (availabilityWarning) {
        availabilityWarning.classList.toggle('hidden', isCustom || isInventoryAvailable('servers', selectedModel));
    }
    document.getElementById('manualArea').classList.toggle('hidden', !isCustom);
    const memoryConfiguration = document.getElementById('memoryConfiguration');
    memoryConfiguration.classList.toggle('hidden', isCustom);
    memoryConfiguration.querySelectorAll('input, select').forEach(control => {
        control.disabled = isCustom;
    });
    if (isCustom) {
        calculate();
        return;
    }

    // Auto-suggest base DIMM config matching the model's base RAM
    const suggestSz  = hw.baseDimmSizeGB === 32 ? 64 : (hw.baseDimmSizeGB || defaultDimmSizeGB);
    const suggestCnt = Math.max(1, Math.min(
        hw.baseDimmCount || Math.round(hw.baseRam / suggestSz),
        hw.maxDimm || 32
    ));
    document.getElementById('selBaseDimmSize').value   = suggestSz;
    document.getElementById('inpBaseDimmCount').value  = suggestCnt;
    document.getElementById('selExpDimmSize').value    = suggestSz;
    document.getElementById('selExpDimmCount').value = 0;

    updateRamOptions();
}

// ── Project Config Capture / Restore ─────────────────────────────────────────
function getCurrentConfig() {
    const model = document.getElementById('selModel').value;
    const cfg = {
        memoryConfigVersion: 2,
        model,
        nodes:         parseInt(document.getElementById('qtyNode').value) || 3,
        baseDimmSize:  parseInt(document.getElementById('selBaseDimmSize').value)   || defaultDimmSizeGB,
        baseDimmCount: parseInt(document.getElementById('inpBaseDimmCount').value) || 2,
        expDimmSize:   parseInt(document.getElementById('selExpDimmSize').value)    || defaultDimmSizeGB,
        expDimmCount:  parseInt(document.getElementById('selExpDimmCount').value)   || 0,
        pools:         JSON.parse(JSON.stringify(storagePools)),
        customHW:      null,
        memTiering: {
            enabled: document.getElementById('chkMemTiering')?.checked || false,
            nvmeSize: parseInt(document.getElementById('selNvmeSize')?.value) || (nvmeDb.nvmeSizes ? nvmeDb.nvmeSizes[0] : 800),
            nvmeQty:  Math.max(0, parseInt(document.getElementById('inpNvmeQty')?.value) || 0)
        }
    };
    if (model === 'custom') {
        // Hidden DIMM inputs have no meaning in custom mode and must not be saved.
        Object.assign(cfg, {baseDimmSize: null, baseDimmCount: 0, expDimmSize: null, expDimmCount: 0});
        cfg.customHW = {
            socket:  parseInt(document.getElementById('mSocket').value)  || 2,
            core:    parseInt(document.getElementById('mCore').value)    || 16,
            freq:    parseFloat(document.getElementById('mFreq').value)  || 2.8,
            baseRam: Number(document.getElementById('mBaseRam').value) || 128,
            maxDisk: parseInt(document.getElementById('mMaxDisk').value) || 12,
            maxDimm: parseInt(document.getElementById('mMaxDimm').value) || 24
        };
    }
    return cfg;
}

function applyConfig(cfg) {
    if (!cfg) return false;
    const model = cfg.model || db.servers[0]?.name;
    const hw = db.servers.find(server => server.name === model);
    if (model !== 'custom' && !hw) {
        showToast('This saved server model is no longer in inventory.', 'error');
        return false;
    }
    let baseSize, baseCount, expSize, expCount;
    if (model !== 'custom') {
        // Old saves without a schema version used 32 GB DIMMs by default.
        const fallbackSize = cfg.memoryConfigVersion ? defaultDimmSizeGB : 32;
        baseSize = Number(cfg.baseDimmSize ?? fallbackSize);
        baseCount = Number(cfg.baseDimmCount ?? Math.round(hw.baseRam / baseSize));
        expSize = Number(cfg.expDimmSize ?? fallbackSize);
        expCount = Number(cfg.expDimmCount ?? 0);
        if (baseSize === 32) { baseSize = 64; baseCount /= 2; }
        if (expSize === 32) { expSize = 64; expCount /= 2; }
        const sizes = db.memory?.dimmSizesGB || [16,64,128];
        if (!sizes.includes(baseSize) || !sizes.includes(expSize)
            || !Number.isInteger(baseCount) || baseCount < 1
            || !Number.isInteger(expCount) || expCount < 0
            || baseCount + expCount > (hw.maxDimm || 32)) {
            showToast('Saved RAM cannot be represented by the available DIMMs without changing capacity. Please review its memory configuration; the current sizing has not changed.', 'error');
            return false;
        }
    }
    document.getElementById('selModel').value = model;
    if (model === 'custom' && cfg.customHW) {
        for (const [id, key] of [['mSocket','socket'],['mCore','core'],['mFreq','freq'],
            ['mBaseRam','baseRam'],['mMaxDisk','maxDisk'],['mMaxDimm','maxDimm']]) {
            document.getElementById(id).value = cfg.customHW[key];
        }
    }
    document.getElementById('qtyNode').value = cfg.nodes || 3;
    handleModelChange();
    if (model !== 'custom') {
        document.getElementById('selBaseDimmSize').value = baseSize;
        document.getElementById('inpBaseDimmCount').value = baseCount;
        document.getElementById('selExpDimmSize').value = expSize;
        // Retain saved populations such as 6 DIMMs (formerly 12 × 32 GB).
        restoredDimmTotal = baseCount + expCount;
        updateRamOptions();
        document.getElementById('selExpDimmCount').value = expCount;
        updateRamOptions();
        if (!meets64GbDimmRule(baseSize, baseCount, expSize, expCount)) {
            const fixedExp = Number(document.getElementById('selExpDimmCount').value) || 0;
            showToast(`Saved RAM (${baseCount + expCount} DIMMs) was below the 64 GB minimum of ${MIN_DIMMS_WITH_64GB} DIMMs/node — upgraded to ${baseCount + fixedExp} DIMMs. Please re-save the project.`, 'info');
        }
    }
    storagePools = cfg.pools ? JSON.parse(JSON.stringify(cfg.pools)) : [];
    nextPoolId = storagePools.length ? Math.max(...storagePools.map(p => p.id)) + 1 : 1;
    if (storagePools.length === 0) addPool(); else renderPools();
    const mt = cfg.memTiering || {};
    document.getElementById('chkMemTiering').checked = !!mt.enabled;
    document.getElementById('memTieringPanel').classList.toggle('hidden', !mt.enabled);
    if (mt.nvmeSize) document.getElementById('selNvmeSize').value = mt.nvmeSize;
    document.getElementById('inpNvmeQty').value = mt.nvmeQty ?? 1;
    calculate();
    return true;
}

// ── Project Save / Load ───────────────────────────────────────────────────────
function updateProjectLabel() {
    const lbl = document.getElementById('currentProjectLabel');
    if (currentProject) {
        lbl.innerText   = currentProject;
        lbl.className   = 'font-bold text-blue-700 truncate';
    } else {
        lbl.innerText   = 'Unsaved';
        lbl.className   = 'font-bold text-gray-400 italic';
    }
}

function promptSaveProject(saveAs) {
    const modal = document.getElementById('saveProjectModal');
    const input = document.getElementById('saveProjectName');
    document.getElementById('saveProjectError').innerText = '';
    input.value = (!saveAs && currentProject) ? currentProject : '';
    modal.classList.remove('hidden');
    setTimeout(() => input.focus(), 50);
}

function closeSaveModal() {
    document.getElementById('saveProjectModal').classList.add('hidden');
}

async function doSaveProject() {
    const name = document.getElementById('saveProjectName').value.trim();
    const errEl = document.getElementById('saveProjectError');
    if (!name) { errEl.innerText = 'Please enter a project name'; return; }

    const d = await api({ action: 'save_project', name, config: getCurrentConfig() });
    if (d.status === 'ok') {
        currentProject = name;
        updateProjectLabel();
        closeSaveModal();
        showToast(d.message || 'Project saved');
        // Refresh projects list if it's loaded
        if (document.getElementById('content-projects').classList.contains('active')) {
            loadProjectsList();
        }
    } else {
        errEl.innerText = d.message || 'Save failed';
    }
}

async function loadProjectsList() {
    const container = document.getElementById('projectsList');
    container.innerHTML = '<div class="col-span-3 text-center text-gray-400 py-12 text-sm">Loading…</div>';

    const d = await api({ action: 'list_projects' });
    if (d.status !== 'ok') { container.innerHTML = `<div class="col-span-3 text-center text-red-500 py-12">${d.message}</div>`; return; }

    if (!d.projects || d.projects.length === 0) {
        container.innerHTML = `
            <div class="col-span-3 text-center py-16">
                <div class="text-4xl mb-3">📂</div>
                <p class="text-gray-500 font-semibold">No saved projects yet</p>
                <p class="text-[11px] text-gray-400 mt-1">Configure a sizing and click <strong>Save</strong> to create your first project</p>
            </div>`;
        return;
    }

    container.innerHTML = d.projects.map(p => `
        <div class="proj-card border rounded-2xl p-4 bg-white">
            <div class="flex items-start justify-between gap-2 mb-3">
                <div class="min-w-0">
                    <p class="font-bold text-gray-800 text-sm truncate" title="${p.name}">📁 ${p.name}</p>
                    <p class="text-[9px] text-gray-400 mt-0.5">Saved: ${fmtDate(p.updated)}</p>
                </div>
                <button onclick="deleteProjectConfirm('${p.name.replace(/'/g,'\\\'')}')"
                    class="text-red-300 hover:text-red-500 font-bold text-lg leading-none mt-0.5 shrink-0" title="Delete">×</button>
            </div>
            <button onclick="doLoadProject('${p.name.replace(/'/g,'\\\'')}')"
                class="w-full bg-blue-600 text-white py-2 rounded-xl text-[11px] font-bold hover:bg-blue-700 transition">
                📂 Load Project
            </button>
        </div>
    `).join('');
}

async function doLoadProject(name) {
    const d = await api({ action: 'load_project', name });
    if (d.status !== 'ok') { showToast(d.message || 'Load failed', 'error'); return; }
    if (!applyConfig(d.project.config)) return;
    currentProject = name;
    updateProjectLabel();
    switchTab('sizing');
    showToast(`Loaded: ${name}`);
}

async function deleteProjectConfirm(name) {
    if (!confirm(`Delete project "${name}"? This cannot be undone.`)) return;
    const d = await api({ action: 'delete_project', name });
    if (d.status === 'ok') { showToast(`Deleted: ${name}`, 'info'); loadProjectsList(); if (currentProject === name) { currentProject = null; updateProjectLabel(); } }
    else showToast(d.message || 'Delete failed', 'error');
}

function newConfig() {
    if (!confirm('Clear current configuration and start fresh?')) return;
    currentProject = null;
    updateProjectLabel();
    document.getElementById('selModel').value         = availableServers()[0]?.name ?? 'custom';
    document.getElementById('qtyNode').value          = 3;
    document.getElementById('selBaseDimmSize').value  = defaultDimmSizeGB;
    document.getElementById('inpBaseDimmCount').value = 4;
    document.getElementById('selExpDimmSize').value   = defaultDimmSizeGB;
    storagePools = []; nextPoolId = 1;
    document.getElementById('chkMemTiering').checked = false;
    document.getElementById('memTieringPanel').classList.add('hidden');
    document.getElementById('inpNvmeQty').value = 1;
    handleModelChange();
    addPool();
}

// ── Admin: Inventory ──────────────────────────────────────────────────────────
function refreshUI() {
    db.availability ||= {servers:{}, memory:{}, ssds:{}, hdds:{}};
    for (const type of ['servers', 'memory', 'ssds', 'hdds']) db.availability[type] ||= {};
    for (const id of ['selBaseDimmSize', 'selExpDimmSize']) {
        const sizes = (db.memory?.dimmSizesGB || [16, 64, 128]).filter(size => size !== 32);
        document.getElementById(id).innerHTML = [
            ...sizes.filter(size => isInventoryAvailable('memory', size)),
            ...sizes.filter(size => !isInventoryAvailable('memory', size))
        ].map(size => `<option value="${size}" ${isInventoryAvailable('memory', size) ? '' : 'disabled'}>${size} GB${isInventoryAvailable('memory', size) ? '' : ' — Unavailable'}</option>`).join('');
    }
    const sortedServers = [...availableServers(), ...db.servers.filter(server => !isInventoryAvailable('servers', server.name))];
    document.getElementById('selModel').innerHTML =
        sortedServers.map(s => `<option value="${s.name}" ${isInventoryAvailable('servers', s.name) ? '' : 'disabled'}>${s.name}${isInventoryAvailable('servers', s.name) ? '' : ' — Unavailable'}</option>`).join('') +
        `<option value="custom">✨ Customization Mode</option>`;

    // Build NVMe size dropdown from nvme-inventory.json
    const selNvme = document.getElementById('selNvmeSize');
    if (selNvme) {
        const prevNvme = selNvme.value;
        selNvme.innerHTML = (nvmeDb.nvmeSizes || [800,1600,3200,6400])
            .map(v => `<option value="${v}">${fmtDisk(v)}</option>`).join('');
        if (prevNvme) selNvme.value = prevNvme;
    }
    const lblBay = document.getElementById('lblNvmeBayOverhead');
    if (lblBay) lblBay.innerText = nvmeDb.specialBayOverhead || 4;

    if (IS_ADMIN) {
        const sTable = document.getElementById('adminServerTable');
        if (sTable) {
            sTable.innerHTML = `<tr class="bg-gray-100 text-[9px] font-bold uppercase text-left">
                <th class="px-3 py-3">Platform</th><th class="px-3 py-3 text-center">Soc</th>
                <th class="px-3 py-3 text-center">Cores</th><th class="px-3 py-3 text-center">GHz</th>
                <th class="px-3 py-3 text-center">Default RAM</th><th class="px-3 py-3 text-center">Supported DIMMs</th>
                <th class="px-3 py-3 text-center">Effective</th><th class="px-3 py-3 text-center">Max Disk</th>
                <th class="px-3 py-3 text-center">Max DIMM</th><th class="px-3 py-3 text-center">Availability</th><th class="px-3 py-3"></th></tr>`;
            db.servers.forEach((s, i) => {
                const available = isInventoryAvailable('servers', s.name);
                sTable.innerHTML += `<tr class="border-t hover:bg-blue-50 text-xs ${available ? '' : 'opacity-60 bg-gray-50'}">
                    <td class="px-3 py-2 font-bold text-gray-700">${s.name}</td>
                    <td class="text-center">${s.socket}</td><td class="text-center">${s.core}</td>
                    <td class="text-center">${s.freq}</td>
                    <td class="text-center font-semibold text-blue-600">${s.baseDimmCount || Math.round(s.baseRam/(s.baseDimmSizeGB||64))} × ${s.baseDimmSizeGB || 64} GB<br><span class="text-[8px] text-gray-400">${s.baseRam} GB</span></td>
                    <td class="text-center text-[9px] text-gray-500">${s.memoryStripCounts?.join(' / ') || 'Standard'}</td>
                    <td class="text-center text-[9px] text-gray-500">${s.memoryEffectiveDate || '—'}</td>
                    <td class="text-center font-bold text-orange-600">${s.maxDisk}</td>
                    <td class="text-center font-bold text-purple-600">${s.maxDimm || '–'}</td>
                    <td class="text-center"><button type="button" aria-pressed="${available}" onclick="toggleAvailability('servers',decodeURIComponent('${encodeURIComponent(s.name)}'))" class="px-2.5 py-1 rounded-full font-bold ${available ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-500'}">${available ? 'Available' : 'Unavailable'}</button></td>
                    <td class="text-right px-3"><button onclick="deleteItem('servers',${i})" class="text-red-400 hover:text-red-600 font-bold">✕</button></td>
                </tr>`;
            });
        }

        const cPanel = document.getElementById('adminCompPanel');
        if (cPanel) {
            cPanel.innerHTML = '';
            [['memory','RAM DIMM Sizes'],['ssds','SSD Options'],['hdds','HDD Options']].forEach(([key, label]) => {
                const values = key === 'memory' ? (db.memory?.dimmSizesGB || []) : db[key];
                cPanel.innerHTML += `<div class="bg-white rounded-xl border p-4 shadow-sm text-[10px]">
                    <div class="flex justify-between items-center font-bold text-gray-400 uppercase mb-3">
                        <span>${label}</span>${key === 'memory' ? '' : `<button onclick="openModal('${key}')" class="text-blue-600 font-bold hover:underline">+ ADD</button>`}
                    </div>
                    <div class="flex flex-wrap gap-2">
                        ${values.map((v, i) => { const available = isInventoryAvailable(key, v); return `<span class="border p-1 px-2 rounded-lg font-bold flex items-center gap-1 ${available ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-100 text-gray-400'}">
                            ${key === 'memory' ? v + ' GB' : fmtDisk(v)}
                            <button type="button" aria-label="Mark ${v} ${available ? 'unavailable' : 'available'}" aria-pressed="${available}" onclick="toggleAvailability('${key}','${v}')" class="px-1 rounded ${available ? 'text-green-700' : 'text-gray-400'}">${available ? '● Available' : '○ Unavailable'}</button>
                            ${key === 'memory' ? '' : `<button onclick="deleteItem('${key}',${i})" class="text-red-300 hover:text-red-500">✕</button>`}
                        </span>`; }).join('')}
                    </div>
                </div>`;
            });
        }
    }

    handleModelChange();
    if (storagePools.length === 0) addPool(); else renderPools();
}

async function toggleAvailability(type, value) {
    db.availability ||= {};
    db.availability[type] ||= {};
    const key = String(value);
    const previous = isInventoryAvailable(type, value);
    db.availability[type][key] = !previous;
    if (!await saveToServer()) {
        db.availability[type][key] = previous;
        refreshUI();
    }
}

function openModal(t) {
    currentType = t;
    const b = document.getElementById('modalBody');
    if (t === 'server') {
        document.getElementById('modalTitle').innerText = 'Add Server Model';
        b.innerHTML = `
        <input type="text" id="a_name" placeholder="Model Name" class="w-full border p-3 rounded-xl bg-gray-50">
        <div class="grid grid-cols-3 gap-2">
            <div class="text-[10px]">Sockets<input type="number" id="a_soc" value="2" class="w-full border p-2 rounded-xl mt-1"></div>
            <div class="text-[10px]">Cores/Socket<input type="number" id="a_core" value="16" class="w-full border p-2 rounded-xl mt-1"></div>
            <div class="text-[10px]">Base GHz<input type="number" id="a_freq" step="0.1" value="2.8" class="w-full border p-2 rounded-xl mt-1"></div>
        </div>
        <div class="grid grid-cols-3 gap-2">
            <div class="text-[10px]">Base RAM (GB · auto)<input type="number" id="a_base" value="128" readonly class="w-full border p-2 rounded-xl mt-1 bg-gray-100"></div>
            <div class="text-[10px]">Max Disk Slots<input type="number" id="a_max" value="12" class="w-full border p-2 rounded-xl mt-1"></div>
            <div class="text-[10px]">Max DIMM Slots<input type="number" id="a_maxdimm" value="24" class="w-full border p-2 rounded-xl mt-1"></div>
        </div>
        <div class="grid grid-cols-2 gap-2">
            <div class="text-[10px]">DIMM Size (GB)<input type="number" id="a_dimmsize" value="64" oninput="syncAdminBaseRam()" class="w-full border p-2 rounded-xl mt-1"></div>
            <div class="text-[10px]">Default DIMM Count<input type="number" id="a_dimmcount" value="2" oninput="syncAdminBaseRam()" class="w-full border p-2 rounded-xl mt-1"></div>
            <div class="text-[10px]">Supported DIMM Totals<input type="text" id="a_stripes" value="4,8,12,16,24" placeholder="4,8,12,16,24" class="w-full border p-2 rounded-xl mt-1"><span class="text-[8px] text-gray-400">Comma-separated installed DIMM counts</span></div>
            <div class="text-[10px]">Memory Effective Date<input type="date" id="a_memdate" class="w-full border p-2 rounded-xl mt-1"></div>
        </div>`;
    } else {
        document.getElementById('modalTitle').innerText = 'Add Disk Size';
        b.innerHTML = `<input type="number" id="a_val" placeholder="Size in GB (e.g. 7680)" class="w-full border p-4 rounded-2xl bg-gray-50 text-center font-bold text-xl">
        <p class="text-[10px] text-gray-400 text-center">Enter in GB (1000 GB = 1 TB)</p>`;
    }
    document.getElementById('adminModal').classList.remove('hidden');
}

function closeModal() { document.getElementById('adminModal').classList.add('hidden'); }

function syncAdminBaseRam() {
    const size = Math.max(1, Number(document.getElementById('a_dimmsize')?.value) || 64);
    const count = Math.max(1, Number(document.getElementById('a_dimmcount')?.value) || 2);
    if (document.getElementById('a_base')) document.getElementById('a_base').value = size * count;
}

function saveAdminItem() {
    if (currentType === 'server') {
        const dimmSize = Math.max(1, parseInt(document.getElementById('a_dimmsize').value) || 64);
        const dimmCount = Math.max(1, parseInt(document.getElementById('a_dimmcount').value) || 2);
        const maxDimm = parseInt(document.getElementById('a_maxdimm').value) || 24;
        const stripCounts = [...new Set(document.getElementById('a_stripes').value.split(',').map(v => parseInt(v.trim())).filter(v => v >= dimmCount && v <= maxDimm))].sort((a,b) => a-b);
        db.servers.push({
            name:    document.getElementById('a_name').value || 'New Server',
            socket:  parseInt(document.getElementById('a_soc').value)    || 2,
            core:    parseInt(document.getElementById('a_core').value)   || 16,
            freq:    parseFloat(document.getElementById('a_freq').value) || 2.4,
            baseRam: dimmSize * dimmCount,
            maxDisk: parseInt(document.getElementById('a_max').value)    || 12,
            maxDimm,
            baseDimmSizeGB: dimmSize,
            baseDimmCount: dimmCount,
            memoryStripCounts: stripCounts,
            memoryEffectiveDate: document.getElementById('a_memdate').value || ''
        });
        delete db.availability?.servers?.[document.getElementById('a_name').value || 'New Server'];
    } else {
        const v = Number(document.getElementById('a_val').value);
        if (v > 0) {
            db[currentType].push(v);
            db[currentType].sort((a, b) => a - b);
            delete db.availability?.[currentType]?.[String(v)];
        }
    }
    saveToServer(); closeModal();
}

function deleteItem(t, i) {
    if (confirm('Remove this item and sync to server?')) {
        const removed = db[t][i];
        db[t].splice(i, 1);
        const key = t === 'servers' ? removed?.name : String(removed);
        if (key != null) delete db.availability?.[t]?.[key];
        saveToServer();
    }
}

async function saveToServer() {
    try {
        const r = await api({ action: 'save_inventory', data: db });
        if (r.status === 'ok') {
            showToast('✓ Inventory saved to server');
            refreshUI();
            return true;
        }
        showToast(r.message || 'Sync failed', 'error');
    } catch(e) { showToast('❌ Network error', 'error'); }
    return false;
}

// ── Admin: User Management ────────────────────────────────────────────────────
function openAddUserModal() {
    document.getElementById('au_dn').value = '';
    document.getElementById('au_un').value = '';
    document.getElementById('au_pw').value = '';
    document.getElementById('au_role').value = 'user';
    document.getElementById('addUserError').innerText = '';
    document.getElementById('addUserModal').classList.remove('hidden');
    setTimeout(() => document.getElementById('au_dn').focus(), 50);
}
function closeAddUserModal() { document.getElementById('addUserModal').classList.add('hidden'); }

async function doAddUser() {
    const errEl = document.getElementById('addUserError');
    errEl.innerText = '';
    const d = await api({
        action: 'add_user',
        display_name: document.getElementById('au_dn').value,
        username:     document.getElementById('au_un').value,
        password:     document.getElementById('au_pw').value,
        role:         document.getElementById('au_role').value
    });
    if (d.status === 'ok') { closeAddUserModal(); showToast('User created'); loadDirectory(); }
    else errEl.innerText = d.message || 'Failed to create user';
}

async function loadDirectory() {
    const container = document.getElementById('directoryList');
    if (!container) return;
    const d = await api({ action: 'get_directory' });
    if (d.status !== 'ok') { container.innerHTML = `<p class="p-4 text-red-500">${d.message}</p>`; return; }
    const me = '<?php echo addslashes($currentUser['username']); ?>';
    container.innerHTML = `<table class="w-full text-xs">
        <tr class="bg-gray-100 text-[9px] font-bold uppercase text-left">
            <th class="px-4 py-3">Name</th>
            <th class="px-4 py-3">Username</th>
            <th class="px-4 py-3 text-center">Role</th>
            <th class="px-4 py-3 text-center">Projects</th>
            ${IS_ADMIN ? '<th class="px-4 py-3"></th>' : ''}
        </tr>
        ${d.users.map(u => `
        <tr class="border-t hover:bg-gray-50">
            <td class="px-4 py-2.5">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-full flex items-center justify-center text-[9px] font-bold ${u.role==='admin'?'bg-purple-100 text-purple-700':'bg-blue-50 text-blue-700'}">
                        ${u.display_name.substring(0,2).toUpperCase()}
                    </span>
                    <span class="font-bold text-gray-800">${u.display_name}${u.username===me?' <span class="text-[9px] text-gray-400 font-normal">(You)</span>':''}</span>
                </div>
            </td>
            <td class="px-4 py-2.5 text-gray-500 font-mono">@${u.username}</td>
            <td class="px-4 py-2.5 text-center">
                <span class="px-2 py-0.5 rounded text-[9px] font-bold ${u.role==='admin'?'bg-purple-100 text-purple-700':'bg-blue-50 text-blue-600'}">
                    ${u.role==='admin'?'Admin':'User'}
                </span>
            </td>
            <td class="px-4 py-2.5 text-center text-gray-500">${u.project_count}</td>
            ${IS_ADMIN ? `<td class="px-4 py-2.5 text-right">
                ${u.username !== me
                    ? `<button onclick="deleteUserConfirm('${u.username}')" class="text-red-400 hover:text-red-600 font-bold text-[11px]">✕ Remove</button>`
                    : '<span class="text-[9px] text-gray-300">You</span>'
                }
            </td>` : ''}
        </tr>`).join('')}
    </table>`;
}

async function loadUsersList() {
    const container = document.getElementById('usersList');
    if (!container) return;
    const d = await api({ action: 'list_users' });
    if (d.status !== 'ok') { container.innerHTML = `<p class="p-4 text-red-500">${d.message}</p>`; return; }

    container.innerHTML = `<table class="w-full text-xs">
        <tr class="bg-gray-100 text-[9px] font-bold uppercase text-left">
            <th class="px-4 py-3">Display Name</th>
            <th class="px-4 py-3">Username</th>
            <th class="px-4 py-3 text-center">Role</th>
            <th class="px-4 py-3 text-center">Projects</th>
            <th class="px-4 py-3"></th>
        </tr>
        ${d.users.map(u => `
        <tr class="border-t hover:bg-gray-50">
            <td class="px-4 py-2.5 font-bold text-gray-800">${u.display_name}</td>
            <td class="px-4 py-2.5 text-gray-500">@${u.username}</td>
            <td class="px-4 py-2.5 text-center">
                <span class="px-2 py-0.5 rounded text-[9px] font-bold ${u.role==='admin'?'bg-purple-100 text-purple-700':'bg-blue-50 text-blue-600'}">
                    ${u.role==='admin'?'Admin':'User'}
                </span>
            </td>
            <td class="px-4 py-2.5 text-center text-gray-400">—</td>
            <td class="px-4 py-2.5 text-right">
                ${u.username !== '<?php echo $currentUser['username']; ?>'
                    ? `<button onclick="deleteUserConfirm('${u.username}')" class="text-red-400 hover:text-red-600 font-bold">✕ Remove</button>`
                    : '<span class="text-[9px] text-gray-300">You</span>'
                }
            </td>
        </tr>`).join('')}
    </table>`;
}

async function deleteUserConfirm(username) {
    if (!confirm(`Remove user "@${username}"? Their projects will remain on disk.`)) return;
    const d = await api({ action: 'delete_user', username });
    if (d.status === 'ok') { showToast(`User @${username} removed`, 'info'); loadDirectory(); }
    else showToast(d.message || 'Delete failed', 'error');
}

// ── Change Password ───────────────────────────────────────────────────────────
function openChangePwModal() {
    document.getElementById('cp_old').value = '';
    document.getElementById('cp_new').value = '';
    document.getElementById('cp_cfm').value = '';
    document.getElementById('changePwError').innerText = '';
    document.getElementById('changePwModal').classList.remove('hidden');
    setTimeout(() => document.getElementById('cp_old').focus(), 50);
}
function closeChangePwModal() { document.getElementById('changePwModal').classList.add('hidden'); }

async function doChangePw() {
    const errEl = document.getElementById('changePwError');
    const newPw = document.getElementById('cp_new').value;
    const cfm   = document.getElementById('cp_cfm').value;
    errEl.innerText = '';
    if (newPw !== cfm) { errEl.innerText = 'New passwords do not match'; return; }
    const d = await api({ action: 'change_password', old_password: document.getElementById('cp_old').value, new_password: newPw });
    if (d.status === 'ok') { closeChangePwModal(); showToast('Password updated successfully'); }
    else errEl.innerText = d.message || 'Failed to update password';
}

// ── Auth / Nav ────────────────────────────────────────────────────────────────
async function doLogout() {
    await api({ action: 'logout' });
    location.reload();
}

function toggleUserMenu() {
    document.getElementById('userMenu').classList.toggle('hidden');
}
document.addEventListener('click', e => {
    const btn = document.getElementById('userMenuBtn');
    const menu = document.getElementById('userMenu');
    if (btn && menu && !btn.contains(e.target) && !menu.contains(e.target)) menu.classList.add('hidden');
});

function switchTab(t) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.nav-btn').forEach(el => {
        el.classList.remove('bg-blue-600','text-white','shadow-md');
        el.classList.add('text-gray-600','hover:bg-gray-100');
    });

    const content = document.getElementById(`content-${t}`);
    const navBtn  = document.getElementById(`nav${t.charAt(0).toUpperCase()+t.slice(1)}`);
    if (content) content.classList.add('active');
    if (navBtn) {
        navBtn.classList.add('bg-blue-600','text-white','shadow-md');
        navBtn.classList.remove('text-gray-600','hover:bg-gray-100');
    }

    if (t === 'sizing')    renderPools();
    if (t === 'projects')  loadProjectsList();
    if (t === 'directory') loadDirectory();
    if (t === 'admin')     refreshUI();
}

// Build bounded A4 pages before rasterizing: no single, arbitrarily tall canvas.
function buildPdfPages(stage) {
    const pages = [];
    let content;
    const model = getCurrentHW().name;
    const date = new Date().toLocaleDateString('en-GB');
    function newPage() {
        const page = document.createElement('section');
        page.className = 'pdf-page';
        const header = document.createElement('header');
        header.className = 'pdf-header';
        const headerText = document.createElement('div');
        headerText.textContent = `SANGFOR HCI Sizing Report | ${model} | ${document.getElementById('qtyNode').value} nodes | ${date}`;
        header.append(headerText);
        content = document.createElement('div');
        content.className = 'pdf-content';
        const footer = document.createElement('footer');
        footer.className = 'pdf-footer';
        page.append(header, content, footer);
        stage.append(page);
        pages.push(page);
    }
    function overflows() { return content.scrollHeight > content.clientHeight; }
    function cloneForPdf(source) {
        const copy = source.cloneNode(true);
        copy.removeAttribute('id');
        copy.querySelectorAll('[id]').forEach(el => el.removeAttribute('id'));
        // cloneNode does not copy a canvas bitmap. Freeze the finished chart as an image.
        const originals = source.querySelectorAll('canvas');
        copy.querySelectorAll('canvas').forEach((canvas, index) => {
            const img = document.createElement('img');
            img.src = originals[index].toDataURL('image/png');
            img.alt = 'Resource capacity chart';
            img.style.cssText = 'width:100%;height:auto;max-height:280px;object-fit:contain';
            canvas.replaceWith(img);
        });
        return copy;
    }
    newPage();
    for (const section of document.getElementById('pdf-area').children) {
        const sourceTable = section.querySelector('table');
        if (!sourceTable) {
            const block = cloneForPdf(section);
            content.append(block);
            if (overflows()) { block.remove(); newPage(); content.append(block); }
            if (overflows()) throw new Error('A report section is too tall for A4.');
            continue;
        }
        const title = section.firstElementChild.textContent;
        let table, body, heading;
        let repeatedHeader = sourceTable.tHead?.cloneNode(true);
        const rows = [...sourceTable.tBodies[0].rows];
        // The detail table stores its column labels in its first body row.
        if (!repeatedHeader && rows.length) {
            repeatedHeader = document.createElement('thead');
            repeatedHeader.append(cloneForPdf(rows.shift()));
        }
        function startTable(continued) {
            heading = document.createElement('h2');
            heading.textContent = title + (continued ? ' — continued' : '');
            table = document.createElement('table');
            table.className = 'pdf-table';
            // Keep widths identical across continuation pages.
            const colgroup = document.createElement('colgroup');
            for (const width of (sourceTable.tHead ? [43, 19, 19, 19] : [28, 36, 18, 18])) {
                const col = document.createElement('col'); col.style.width = width + '%'; colgroup.append(col);
            }
            table.append(colgroup);
            if (repeatedHeader) table.append(repeatedHeader.cloneNode(true));
            body = document.createElement('tbody'); table.append(body);
            content.append(heading, table);
        }
        startTable(false);
        for (let i = 0; i < rows.length; i++) {
            // Keep section labels with the next row, including the BOM header.
            const group = [rows[i]];
            if (rows[i].cells[0]?.colSpan > 1 && i + 1 < rows.length) group.push(rows[++i]);
            const copies = group.map(cloneForPdf);
            copies.forEach(row => body.append(row));
            if (overflows()) {
                copies.forEach(row => row.remove());
                const continued = body.rows.length > 0;
                if (!continued) { table.remove(); heading.remove(); }
                newPage(); startTable(continued);
                copies.forEach(row => body.append(row));
                if (overflows()) throw new Error('A report row is too tall for A4. Reduce its content and try again.');
            }
            // Once BOM begins, repeat its quantity labels instead of the specification labels.
            if (group[0].textContent.includes('Equipment Summary')) {
                repeatedHeader = document.createElement('thead');
                repeatedHeader.append(cloneForPdf(group[0]));
            }
        }
    }
    pages.forEach((page, index) => {
        page.querySelector('.pdf-footer').textContent = `Sangfor HCI • Capacity estimate | Page ${index + 1} of ${pages.length}`;
    });
    return pages;
}

async function exportPDF() {
    const button = document.getElementById('exportPdfButton');
    const status = document.getElementById('exportStatus');
    if (button.disabled) return;
    button.disabled = true;
    button.textContent = 'Preparing PDF…';
    status.textContent = 'Preparing A4 pages…';
    let stage;
    try {
        if (typeof html2pdf === 'undefined') throw new Error('PDF library is unavailable. Check your connection and retry.');
        switchTab('sizing');
        calculate();
        if (resourceChart) { resourceChart.stop(); resourceChart.update('none'); }
        await document.fonts.ready;
        stage = document.createElement('div');
        stage.className = 'pdf-stage';
        stage.setAttribute('aria-hidden', 'true');
        document.body.append(stage);
        const pages = buildPdfPages(stage);
        let pdf;
        for (let i = 0; i < pages.length; i++) {
            // Capture each page at the same origin; off-screen text can otherwise disappear.
            stage.replaceChildren(pages[i]);
            status.textContent = `Exporting page ${i + 1} of ${pages.length}…`;
            const worker = html2pdf().set({
                margin: 10,
                html2canvas: { scale: 2, backgroundColor: '#ffffff', scrollY: 0 },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak: { mode: [] }
            }).from(pages[i]).toCanvas();
            const canvas = await worker.get('canvas');
            if (!pdf) {
                // Obtain the bundled jsPDF instance; use our measured page image below.
                pdf = await worker.toPdf().get('pdf');
                while (pdf.getNumberOfPages() > 1) pdf.deletePage(pdf.getNumberOfPages());
            } else pdf.addPage();
            pdf.addImage(canvas.toDataURL('image/jpeg', 0.96), 'JPEG', 10, 10, 190, canvas.height * 190 / canvas.width);
            canvas.width = canvas.height = 0;
        }
        pdf.save('Sangfor_HCI_Sizing.pdf');
        status.textContent = `PDF ready — ${pages.length} pages.`;
    } catch (error) {
        console.error('PDF export failed:', error);
        status.textContent = `Export failed: ${error.message}`;
    } finally {
        stage?.remove();
        button.disabled = false;
        button.textContent = '📄 Export PDF';
    }
}

// ── Session Timeout Timer ─────────────────────────────────────────────────────
(function() {
    const TIMEOUT_MS  = <?php echo SESSION_TIMEOUT; ?> * 1000;
    const WARN_BEFORE = 60 * 1000; // warn 60s before expiry
    let warnTimer, logoutTimer;

    function resetTimers() {
        clearTimeout(warnTimer);
        clearTimeout(logoutTimer);
        warnTimer   = setTimeout(showWarning,  TIMEOUT_MS - WARN_BEFORE);
        logoutTimer = setTimeout(forceLogout,  TIMEOUT_MS);
    }

    function showWarning() {
        showToast('⚠️ Session หมดใน 1 นาที — กรุณาบันทึกงาน', 'error');
    }

    async function forceLogout() {
        await api({ action: 'logout' });
        document.body.innerHTML = `
            <div style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f8fafc">
                <div style="text-align:center;font-family:Inter,sans-serif">
                    <div style="font-size:48px;margin-bottom:16px">⏱️</div>
                    <h2 style="font-size:20px;font-weight:700;color:#1e293b;margin-bottom:8px">Session หมดอายุ</h2>
                    <p style="color:#64748b;margin-bottom:24px;font-size:14px">ไม่มี activity นาน <?php echo SESSION_TIMEOUT / 60; ?> นาที</p>
                    <button onclick="location.reload()"
                        style="background:#2563eb;color:#fff;padding:10px 28px;border-radius:12px;font-weight:700;border:none;cursor:pointer;font-size:14px">
                        Login อีกครั้ง
                    </button>
                </div>
            </div>`;
    }

    ['click','keydown','mousemove','scroll','touchstart'].forEach(evt =>
        document.addEventListener(evt, resetTimers, { passive: true })
    );

    resetTimers();
})();

// ── Init ──────────────────────────────────────────────────────────────────────
try {
    refreshUI();
} catch(e) {
    document.body.insertAdjacentHTML('afterbegin',
        `<div style="position:fixed;top:0;left:0;right:0;z-index:9999;background:#dc2626;color:#fff;padding:12px 16px;font-family:monospace;font-size:12px">
            <strong>JS Error:</strong> ${e.message} &nbsp;|&nbsp; <em>${e.stack?.split('\n')[1]?.trim() || ''}</em>
        </div>`);
}
</script>

<?php endif; ?>
</body>
</html>
