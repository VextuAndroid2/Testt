# DoS Panel v3 · MAX POWER

Background attack · live connection logs · stop button · survives reload

## Run
```bash
docker compose up -d --build
```
→ http://localhost:8080

## Intensities (threads / sockets / pkt size)
| Level     | Threads | Sockets | Size   |
|-----------|---------|---------|--------|
| suave     | 50      | 80      | 2 KB   |
| baja      | 150     | 200     | 4 KB   |
| media     | 400     | 500     | 8 KB   |
| alta      | 900     | 900     | 16 KB  |
| bastante  | 1800    | 1600    | 32 KB  |
| **mortal**| **3500**| **2500**| **64 KB** |

Logs show real ok/fail connection counts + rate every few seconds.
