<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DoS Panel</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap');
*{box-sizing:border-box;margin:0;padding:0}
body{background:#0a0a0a;color:#e0e0e0;font-family:'JetBrains Mono',monospace;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.panel{background:#111;border:1px solid #222;border-radius:12px;padding:28px;width:100%;max-width:520px;box-shadow:0 0 40px rgba(255,50,50,.08)}
h1{font-size:1.35rem;margin-bottom:6px;color:#ff4444;letter-spacing:1px}
.sub{font-size:.72rem;color:#666;margin-bottom:22px}
label{display:block;font-size:.68rem;color:#888;margin-bottom:5px;text-transform:uppercase;letter-spacing:1px}
input,select{width:100%;background:#0a0a0a;border:1px solid #333;color:#eee;padding:11px 13px;border-radius:6px;font-family:inherit;font-size:.88rem;margin-bottom:14px;outline:none}
input:focus,select:focus{border-color:#ff4444}
.row{display:flex;gap:10px}
.row>div{flex:1}
.intensity{display:grid;grid-template-columns:repeat(3,1fr);gap:7px;margin-bottom:14px}
.intensity label{margin:0}
.intensity input{display:none}
.intensity span{display:block;text-align:center;padding:9px 3px;background:#0a0a0a;border:1px solid #333;border-radius:6px;font-size:.68rem;cursor:pointer;user-select:none}
.intensity input:checked+span{background:#ff2222;border-color:#ff4444;color:#fff;font-weight:700}
.btns{display:flex;gap:10px;margin-top:4px}
.btn{flex:1;padding:13px;border:none;border-radius:6px;font-family:inherit;font-size:.9rem;font-weight:700;cursor:pointer;letter-spacing:1px}
.btn-start{background:#ff2222;color:#fff}
.btn-start:hover{background:#cc0000}
.btn-stop{background:#333;color:#ff6666;border:1px solid #555}
.btn-stop:hover{background:#440000;border-color:#ff4444}
.btn:disabled{opacity:.45;cursor:not-allowed}
.status{margin:12px 0 0;font-size:.72rem;color:#888}
.status.on{color:#0f0}
.status.off{color:#666}
.log{margin-top:14px;background:#050505;border:1px solid #222;border-radius:6px;padding:12px;font-size:.72rem;height:220px;overflow-y:auto;color:#0f0;white-space:pre-wrap;line-height:1.45}
.footer{margin-top:16px;font-size:.62rem;color:#444;text-align:center}
</style>
</head>
<body>
<div class="panel">
  <h1>DoS PANEL</h1>
  <div class="sub">background · live logs · kill switch</div>

  <form id="form">
    <label>Objetivo (IP o URL)</label>
    <input type="text" name="target" id="target" placeholder="192.168.1.10 o https://ejemplo.com" required>

    <label>Tipo</label>
    <select name="type" id="type">
      <option value="udp">UDP Flood</option>
      <option value="http">HTTP Flood</option>
      <option value="https">HTTPS Flood</option>
      <option value="slow">Slowloris</option>
    </select>

    <label>Intensidad</label>
    <div class="intensity">
      <label><input type="radio" name="intensity" value="suave"><span>Suave</span></label>
      <label><input type="radio" name="intensity" value="baja"><span>Baja</span></label>
      <label><input type="radio" name="intensity" value="media" checked><span>Media</span></label>
      <label><input type="radio" name="intensity" value="alta"><span>Alta</span></label>
      <label><input type="radio" name="intensity" value="bastante"><span>Bastante</span></label>
      <label><input type="radio" name="intensity" value="mortal"><span>Mortal</span></label>
    </div>

    <div class="row">
      <div>
        <label>Duración</label>
        <input type="number" name="duration" id="duration" value="60" min="1" max="9999">
      </div>
      <div>
        <label>Unidad</label>
        <select name="unit" id="unit">
          <option value="s">Segundos</option>
          <option value="m" selected>Minutos</option>
          <option value="h">Horas</option>
        </select>
      </div>
    </div>

    <div class="btns">
      <button type="submit" class="btn btn-start" id="btnStart">LANZAR</button>
      <button type="button" class="btn btn-stop" id="btnStop" disabled>DETENER</button>
    </div>
  </form>

  <div class="status off" id="status">● idle</div>
  <div class="log" id="log">[*] listo\n</div>
  <div class="footer">php · docker · background process</div>
</div>

<script>
const logEl = document.getElementById('log');
const statusEl = document.getElementById('status');
const btnStart = document.getElementById('btnStart');
const btnStop = document.getElementById('btnStop');
let polling = null;

function appendLog(txt) {
  logEl.textContent = txt || logEl.textContent;
  logEl.scrollTop = logEl.scrollHeight;
}

async function poll() {
  try {
    const r = await fetch('attack.php?action=status');
    const d = await r.json();
    appendLog(d.log || '');
    if (d.running) {
      statusEl.textContent = '● ATTACKING  pid ' + d.pid;
      statusEl.className = 'status on';
      btnStart.disabled = true;
      btnStop.disabled = false;
    } else {
      statusEl.textContent = '● idle';
      statusEl.className = 'status off';
      btnStart.disabled = false;
      btnStop.disabled = true;
      if (polling) { clearInterval(polling); polling = null; }
    }
  } catch(e) {}
}

document.getElementById('form').addEventListener('submit', async (e) => {
  e.preventDefault();
  btnStart.disabled = true;
  const fd = new FormData(e.target);
  fd.append('action', 'start');
  try {
    const r = await fetch('attack.php', { method: 'POST', body: fd });
    const d = await r.json();
    if (d.ok) {
      appendLog('[*] ataque lanzado en background\n');
      if (!polling) polling = setInterval(poll, 800);
      poll();
    } else {
      appendLog('[!] ' + (d.msg || 'error') + '\n');
      btnStart.disabled = false;
    }
  } catch(err) {
    appendLog('[!] ' + err.message + '\n');
    btnStart.disabled = false;
  }
});

btnStop.addEventListener('click', async () => {
  btnStop.disabled = true;
  try {
    await fetch('attack.php', { method: 'POST', body: new URLSearchParams({action:'stop'}) });
    appendLog('\n[!] detenido\n');
    poll();
  } catch(e) {}
});

// on load: check if already running
poll();
polling = setInterval(poll, 1200);
</script>
</body>
</html>
