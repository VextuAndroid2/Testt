# DoS Panel v2 · PHP + Docker

Background attack · live logs · stop button · survives page reload

## Run
```bash
docker compose up -d --build
```

Open http://localhost:8080

## Features
- Attack runs in background (worker.php)
- Logs stream live even if you reload
- Stop button kills the process
- Intensities: suave → mortal

## Stop container
```bash
docker compose down
```
