<?php
// ════════════════════════════════════════════════════════
//  HCI Sizing Tool — One-Time Setup Script
//  Run this ONCE then DELETE the file from your server
// ════════════════════════════════════════════════════════

$dataDir     = __DIR__ . '/data';
$projectsDir = $dataDir . '/projects';
$usersFile   = $dataDir . '/UserDir.JSON';
$invFile     = $dataDir . '/inventory.json';
$htaFile     = $dataDir . '/.htaccess';

$log  = [];
$ok   = true;

function logLine($msg, $status = 'ok') {
    global $log;
    $log[] = ['msg' => $msg, 'status' => $status];
}

// ── Diagnostics ──────────────────────────────────────────
$phpUser = function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : get_current_user();
logLine("PHP Version: " . PHP_VERSION . " (required: 7.4+)", version_compare(PHP_VERSION, '7.4.0', '>=') ? 'ok' : 'error');
logLine("PHP running as user: " . $phpUser, 'info');
logLine("Script directory: " . __DIR__, 'info');
logLine("Data directory: " . $dataDir, 'info');
logLine("Parent dir writable: " . (is_writable(__DIR__) ? 'YES' : 'NO — this is the problem'), is_writable(__DIR__) ? 'ok' : 'error');

// ── PHP Extension checks ──────────────────────────────────
$exts = [
    'json'     => ['json_encode',       'JSON encode/decode (API responses)'],
    'mbstring' => ['mb_substr',          'Multi-byte string (user avatars) — REQUIRED'],
    'session'  => ['session_start',      'PHP sessions (login persistence) — REQUIRED'],
    'openssl'  => ['openssl_random_pseudo_bytes', 'OpenSSL (optional, improves session security)'],
];
foreach ($exts as $ext => [$fn, $desc]) {
    $has = function_exists($fn);
    logLine("Extension php-{$ext}: " . ($has ? 'OK' : "MISSING — {$desc}"), $has ? 'ok' : ($ext === 'openssl' ? 'info' : 'error'));
}

// ── Session write test ────────────────────────────────────
$sessPath = session_save_path() ?: sys_get_temp_dir();
logLine("Session save path: {$sessPath} — " . (is_writable($sessPath) ? 'writable' : 'NOT WRITABLE (login will fail)'),
    is_writable($sessPath) ? 'ok' : 'error');

// ── Create data/ ─────────────────────────────────────────
if (!is_dir($dataDir)) {
    $r = @mkdir($dataDir, 0755, true);
    if ($r) {
        logLine("Created: data/", 'ok');
    } else {
        $err = error_get_last();
        logLine("FAILED to create data/ — " . ($err['message'] ?? 'unknown error'), 'error');
        $ok = false;
    }
} else {
    logLine("data/ already exists", 'ok');
}

// ── Create data/projects/ ────────────────────────────────
if ($ok || is_dir($dataDir)) {
    if (!is_dir($projectsDir)) {
        $r = @mkdir($projectsDir, 0755, true);
        logLine("Created: data/projects/", $r ? 'ok' : 'error');
        if (!$r) $ok = false;
    } else {
        logLine("data/projects/ already exists", 'ok');
    }
}

// ── Create .htaccess ─────────────────────────────────────
if (is_dir($dataDir) && !file_exists($htaFile)) {
    $r = @file_put_contents($htaFile, "Deny from all\nOptions -Indexes\n");
    logLine("Created: data/.htaccess (HTTP access block)", $r !== false ? 'ok' : 'error');
}

// ── Create users.json ────────────────────────────────────
if (is_dir($dataDir)) {
    if (!file_exists($usersFile)) {
        $users = ['users' => [[
            'username'     => 'admin',
            'password'     => password_hash('admin123', PASSWORD_DEFAULT),
            'display_name' => 'Administrator',
            'role'         => 'admin'
        ]]];
        $r = @file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
        logLine("Created: data/UserDir.JSON  (admin / admin123)", $r !== false ? 'ok' : 'error');
        if ($r === false) $ok = false;
    } else {
        logLine("data/UserDir.JSON already exists — NOT overwritten", 'ok');
    }
}

// ── Create inventory.json ─────────────────────────────────
if (is_dir($dataDir) && !file_exists($invFile)) {
    $inv = ["servers" => [
        ["name"=>"aServer-X5-1200",  "socket"=>2,"core"=>16,"freq"=>2.8,"baseRam"=>128,"maxDisk"=>7, "maxDimm"=>16],
        ["name"=>"aServer-X5-2105",  "socket"=>2,"core"=>12,"freq"=>2.4,"baseRam"=>128,"maxDisk"=>12,"maxDimm"=>32],
        ["name"=>"aServer-X5-2205",  "socket"=>2,"core"=>16,"freq"=>2.8,"baseRam"=>128,"maxDisk"=>12,"maxDimm"=>32],
        ["name"=>"aServer-X5-2305P", "socket"=>2,"core"=>32,"freq"=>2.1,"baseRam"=>128,"maxDisk"=>12,"maxDimm"=>32],
        ["name"=>"aServer-X5-2305P(12N)","socket"=>2,"core"=>32,"freq"=>2.1,"baseRam"=>128,"maxDisk"=>12,"maxDimm"=>32],
        ["name"=>"aServer-X5-2405",  "socket"=>2,"core"=>32,"freq"=>2.8,"baseRam"=>128,"maxDisk"=>12,"maxDimm"=>32],
        ["name"=>"aServer-X5-G2200", "socket"=>2,"core"=>16,"freq"=>2.8,"baseRam"=>128,"maxDisk"=>10,"maxDimm"=>24],
        ["name"=>"aServer-Z4-2605",  "socket"=>2,"core"=>64,"freq"=>3.1,"baseRam"=>256,"maxDisk"=>12,"maxDimm"=>32],
        ["name"=>"aServer-X6-2205",  "socket"=>2,"core"=>16,"freq"=>3.2,"baseRam"=>128,"maxDisk"=>12,"maxDimm"=>32],
        ["name"=>"aServer-X6-2305P", "socket"=>2,"core"=>32,"freq"=>2.3,"baseRam"=>128,"maxDisk"=>12,"maxDimm"=>32],
        ["name"=>"VDS-X6-2205",      "socket"=>2,"core"=>16,"freq"=>3.2,"baseRam"=>256,"maxDisk"=>12,"maxDimm"=>32],
        ["name"=>"R770(6507P)",      "socket"=>2,"core"=>8, "freq"=>3.5,"baseRam"=>256,"maxDisk"=>24,"maxDimm"=>32],
        ["name"=>"R770(6520P)",      "socket"=>2,"core"=>24,"freq"=>2.4,"baseRam"=>256,"maxDisk"=>24,"maxDimm"=>32],
        ["name"=>"R770(6530P)",      "socket"=>2,"core"=>32,"freq"=>2.3,"baseRam"=>256,"maxDisk"=>24,"maxDimm"=>32],
        ["name"=>"R770(6740P)",      "socket"=>2,"core"=>48,"freq"=>2.1,"baseRam"=>256,"maxDisk"=>24,"maxDimm"=>32]
    ], "ssds" => [480,960,1600,1900,3200,3800,7600],
       "hdds" => [960,1600,1900,3200,3800,4000,6000,7600,8000,10000,12000]];
    $r = @file_put_contents($invFile, json_encode($inv, JSON_PRETTY_PRINT));
    logLine("Created: data/inventory.json", $r !== false ? 'ok' : 'error');
}

// ── Verify ────────────────────────────────────────────────
$allFilesOk = is_dir($dataDir) && is_dir($projectsDir) && file_exists($usersFile);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HCI Sizing Tool — Setup</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body{font-family:'Inter',sans-serif;}</style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-6">
<div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-8">

    <div class="flex items-center gap-3 mb-6 pb-4 border-b">
        <span class="text-3xl"><?php echo $allFilesOk ? '✅' : '❌'; ?></span>
        <div>
            <h1 class="text-xl font-bold text-gray-800">HCI Sizing Tool — Setup</h1>
            <p class="text-[11px] text-gray-400 mt-0.5">
                <?php echo $allFilesOk ? 'Setup complete! Delete this file now.' : 'Setup encountered errors — see details below.'; ?>
            </p>
        </div>
    </div>

    <div class="space-y-2 mb-6">
        <?php foreach ($log as $entry): ?>
        <?php
            $color = match($entry['status']) {
                'ok'    => 'bg-green-50 border-green-200 text-green-800',
                'error' => 'bg-red-50 border-red-200 text-red-700 font-bold',
                default => 'bg-blue-50 border-blue-200 text-blue-700',
            };
            $icon = match($entry['status']) {
                'ok'    => '✓',
                'error' => '✗',
                default => 'ℹ',
            };
        ?>
        <div class="<?php echo $color; ?> border rounded-lg px-3 py-2 text-[11px] font-mono flex gap-2">
            <span class="font-bold"><?php echo $icon; ?></span>
            <span><?php echo htmlspecialchars($entry['msg']); ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($allFilesOk): ?>
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-4">
        <p class="text-green-800 font-bold text-sm mb-2">✅ Setup successful!</p>
        <p class="text-green-700 text-[11px]">Default login credentials:</p>
        <p class="text-green-900 font-bold text-sm mt-1">Username: <code>admin</code> &nbsp;|&nbsp; Password: <code>admin123</code></p>
        <p class="text-[10px] text-green-600 mt-2">⚠️ Change the password after your first login.</p>
    </div>
    <div class="bg-red-50 border border-red-200 rounded-xl p-3 mb-4">
        <p class="text-red-700 font-bold text-[11px]">🗑️ Security: Delete this file from your server now!</p>
        <p class="text-red-600 text-[10px] mt-1">File: <code>hci-setup.php</code> — anyone can access it and see credentials.</p>
    </div>
    <a href="HCISizingTool-v2.php"
        class="block text-center bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-700 transition">
        → Go to HCI Sizing Tool
    </a>

    <?php else: ?>
    <div class="bg-orange-50 border border-orange-200 rounded-xl p-4 text-[11px] text-orange-800">
        <p class="font-bold mb-2">Setup failed — possible solutions:</p>
        <ul class="space-y-1 list-disc pl-4">
            <li>Create the <code>data/</code> folder manually via <strong>cPanel File Manager</strong> or FTP, then set permission to <strong>755</strong></li>
            <li>Create <code>data/projects/</code> folder inside it</li>
            <li>Reload this page after creating the folders</li>
        </ul>
    </div>
    <button onclick="location.reload()" class="mt-4 w-full bg-orange-500 text-white py-3 rounded-xl font-bold hover:bg-orange-600 transition">
        🔄 Retry Setup
    </button>
    <?php endif; ?>

</div>
</body>
</html>
