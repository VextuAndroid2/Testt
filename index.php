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
.panel{background:#111;border:1px solid #222;border-radius:12px;padding:32px;width:100%;max-width:480px;box-shadow:0 0 40px rgba(255,50,50,.08)}
h1{font-size:1.4rem;margin-bottom:8px;color:#ff4444;letter-spacing:1px}
.sub{font-size:.75rem;color:#666;margin-bottom:28px}
label{display:block;font-size:.7rem;color:#888;margin-bottom:6px;text-transform:uppercase;letter-spacing:1px}
input,select{width:100%;background:#0a0a0a;border:1px solid #333;color:#eee;padding:12px 14px;border-radius:6px;font-family:inherit;font-size:.9rem;margin-bottom:18px;outline:none;transition:border .2s}
input:focus,select:focus{border-color:#ff4444}
.row{display:flex;gap:12px}
.row>div{flex:1}
.intensity{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:18px}
.intensity label{margin:0}
.intensity input{display:none}
.intensity span{display:block;text-align:center;padding:10px 4px;background:#0a0a0a;border:1px solid #333;border-radius:6px;font-size:.7rem;cursor:pointer;transition:all .15s;user-select:none}
.intensity input:checked+span{background:#ff2222;border-color:#ff4444;color:#fff;font-weight:700}
.btn{width:100%;padding:14px;background:#ff2222;border:none;border-radius:6px;color:#fff;font-family:inherit;font-size:.95rem;font-weight:700;cursor:pointer;letter-spacing:1px;transition:background .2s}
.btn:hover{background:#cc0000}
.btn:disabled{background:#333;color:#666;cursor:not-allowed}
.log{margin-top:20px;background:#0a0a0a;border:1px solid #222;border-radius:6px;padding:14px;font-size:.75rem;min-height:80px;color:#0f0;white-space:pre-wrap;display:none}
.footer{margin-top:20px;font-size:.65rem;color:#444;text-align:center}
</style>
</head>
<body>
<div class="panel">
  <h1>DoS PANEL</h1>
  <div class="sub">UDP · HTTP · HTTPS · SLOWLORIS</div>

  <form id="dosForm" method="POST" action="attack.php">
    <label>Objetivo (IP o URL)</label>
    <input type="text" name="target" placeholder="192.168.1.10 o https://ejemplo.com" required>

    <label>Tipo de ataque</label>
    <select name="type" required>
      <option value="udp">UDP Flood</option>
      <option value="http">HTTP Flood</option>
      <option value="https">HTTPS Flood</option>
      <option value="slow">Slowloris</option>
    </select>

    <label>Intensidad</label>
    <div class="intensity">
      <label><input type="radio" name="intensity" value="suave" checked><span>Suave</span></label>
      <label><input type="radio" name="intensity" value="baja"><span>Baja</span></label>
      <label><input type="radio" name="intensity" value="media"><span>Media</span></label>
      <label><input type="radio" name="intensity" value="alta"><span>Alta</span></label>
      <label><input type="radio" name="intensity" value="bastante"><span>Bastante</span></label>
      <label><input type="radio" name="intensity" value="mortal"><span>Mortal</span></label>
    </div>

    <div class="row">
      <div>
        <label>Duración</label>
        <input type="number" name="duration" value="30" min="1" max="9999" required>
      </div>
      <div>
        <label>Unidad</label>
        <select name="unit">
          <option value="s">Segundos</option>
          <option value="m">Minutos</option>
          <option value="h">Horas</option>
        </select>
      </div>
    </div>

    <button type="submit" class="btn" id="btn">LANZAR ATAQUE</button>
  </form>

  <div class="log" id="log"></div>
  <div class="footer">lab use only · no logs · no mercy</div>
</div>

<script>
document.getElementById('dosForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const btn = document.getElementById('btn');
  const log = document.getElementById('log');
  btn.disabled = true;
  btn.textContent = 'ATACANDO...';
  log.style.display = 'block';
  log.textContent = '[*] Iniciando ataque...\n';

  const form = new FormData(e.target);
  try {
    const res = await fetch('attack.php', { method: 'POST', body: form });
    const text = await res.text();
    log.textContent += text;
  } catch (err) {
    log.textContent += '[!] Error de conexión: ' + err.message;
  }
  btn.disabled = false;
  btn.textContent = 'LANZAR ATAQUE';
});
</script>
</body>
</html>
