# DoS Panel v4 · MAX POWER

- HTTP/HTTPS usa **curl_multi** (verdadero paralelo, no cola)
- Timer fuerza la muerte del proceso al cumplirse el tiempo
- Logs de conexiones reales cada segundo (ok / fail / rate / concurrent)
- Botón DETENER + auto-stop

## Run
```bash
docker compose up -d --build
```
→ http://localhost:8080

## Intensities
| Level     | Threads | Sockets | Size  |
|-----------|---------|---------|-------|
| suave     | 80      | 100     | 2 KB  |
| baja      | 250     | 300     | 4 KB  |
| media     | 700     | 600     | 8 KB  |
| alta      | 1500    | 1200    | 16 KB |
| bastante  | 3500    | 2200    | 32 KB |
| **mortal**| **7000**| **4000**| **64 KB** |
