<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DoS Panel · MAX</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap');
*{box-sizing:border-box;margin:0;padding:0}
body{background:#050505;color:#e0e0e0;font-family:'JetBrains Mono',monospace;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px}
.panel{background:#0d0d0d;border:1px solid #1f1f1f;border-radius:10px;padding:24px;width:100%;max-width:560px;box-shadow:0 0 50px rgba(255,30,30,.12)}
h1{font-size:1.3rem;margin-bottom:4px;color:#ff3030;letter-spacing:1px}
.sub{font-size:.7rem;color:#555;margin-bottom:18px}
label{display:block;font-size:.65rem;color:#777;margin-bottom:4px;text-transform:uppercase;letter-spacing:1px}
input,select{width:100%;background:#080808;border:1px solid #2a2a2a;color:#eee;padding:10px 12px;border-radius:5px;font-family:inherit;font-size:.85rem;margin-bottom:12px;outline:none}
input:focus,select:focus{border-color:#ff3030}
.row{display:flex;gap:8px}
.row>div{flex:1}
.intensity{display:grid;grid-template-columns:repeat(3,1fr);gap:6px;margin-bottom:12px}
.intensity label{margin:0}
.intensity input{display:none}
.intensity span{display:block;text-align:center;padding:8px 2px;background:#080808;border:1px solid #2a2a2a;border-radius:5px;font-size:.65rem;cursor:pointer;user-select:none}
.intensity input:checked+span{background:#ff1a1a;border-color:#ff4040;color:#fff;font-weight:700}
.btns{display:flex;gap:8px}
.btn{flex:1;padding:12px;border:none;border-radius:5px;font-family:inherit;font-size:.88rem;font-weight:700;cursor:pointer;letter-spacing:1px}
.btn-start{background:#ff1a1a;color:#fff}
.btn-start:hover{background:#cc0000}
.btn-stop{background:#1a1a1a;color:#ff5555;border:1px solid #333}
.btn-stop:hover{background:#330000}
.btn:disabled{opacity:.4;cursor:not-allowed}
.status{margin:10px 0 0;font-size:.7rem;color:#555}
.status.on{color:#0f0}
.log{margin-top:12px;background:#030303;border:1px solid #1a1a1a;border-radius:5px;padding:10px;font-size:.68rem;height:260px;overflow-y:auto;color:#0f0;white-space:pre-wrap;line-height:1.4}
.footer{margin-top:12px;font-size:.6rem;color:#333;text-align:center}
</style>
</head>
<body>
<div class="panel">
  <h1>DoS PANEL · MAX</h1>
  <div class="sub">background · live connection logs · kill switch</div>

  <form id="form">
    <label>Objetivo</label>
    <input type="text" name="target" id="target" placeholder="IP o https://site.com" required>

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
        <input type="number" name="duration" id="duration" value="5" min="1" max="9999">
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

  <div class="status" id="status">● idle</div>
  <div class="log" id="log">[*] listo — esperando orden\n</div>
  <div class="footer">max power · connection level logs</div>
</div>

<script>
const logEl = document.getElementById('log');
const statusEl = document.getElementById('status');
const btnStart = document.getElementById('btnStart');
const btnStop = document.getElementById('btnStop');
let polling = null;

function setLog(txt) {
  logEl.textContent = txt || '';
  logEl.scrollTop = logEl.scrollHeight;
}

async function poll() {
  try {
    const r = await fetch('attack.php?action=status');
    const d = await r.json();
    setLog(d.log || '');
    if (d.running) {
      statusEl.textContent = '● ATTACKING  pid ' + d.pid;
      statusEl.className = 'status on';
      btnStart.disabled = true;
      btnStop.disabled = false;
    } else {
      statusEl.textContent = '● idle';
      statusEl.className = 'status';
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
      if (!polling) polling = setInterval(poll, 600);
      poll();
    } else {
      setLog('[!] ' + (d.msg || 'error') + '\n');
      btnStart.disabled = false;
    }
  } catch(err) {
    setLog('[!] ' + err.message + '\n');
    btnStart.disabled = false;
  }
});

btnStop.addEventListener('click', async () => {
  btnStop.disabled = true;
  await fetch('attack.php', { method: 'POST', body: new URLSearchParams({action:'stop'}) });
  poll();
});

poll();
polling = setInterval(poll, 1000);
</script>
</body>
</html>
