# DoS Panel · PHP + Docker

## Run
```bash
docker compose up -d --build
```

Open → http://localhost:8080

## Stop
```bash
docker compose down
```

## Intensity
| Level     | Threads | Sockets | Packet size |
|-----------|---------|---------|-------------|
| suave     | 8       | 30      | 512         |
| baja      | 20      | 80      | 1024        |
| media     | 50      | 150     | 2048        |
| alta      | 120     | 300     | 4096        |
| bastante  | 250     | 500     | 8192        |
| mortal    | 500     | 900     | 16384       |

UDP · HTTP · HTTPS · Slowloris
