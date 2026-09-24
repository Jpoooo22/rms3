# UPHSD College of Engineering — Records Management System

This folder is a self-contained package. Anyone on **macOS or Windows** can run it
without installing PHP or MySQL — they only need **Docker Desktop** (free).

There is no ".exe" because this is a web application (PHP + MySQL database), not a
desktop program. Docker bundles the app, the web server, and the database together
so it runs the same on every computer.

---

## For the person you send this to (Mac)

### 1. Install Docker Desktop (one time)
Download and install from: https://www.docker.com/products/docker-desktop/
Open it once so the whale icon appears in the menu bar.

### 2. Start the system
- Double-click **`start.command`** in this folder.
  - If macOS blocks it: right-click `start.command` → **Open** → **Open**.
  - Or, if it won't run, open **Terminal**, drag this folder in, press Enter, then type:
    ```
    docker compose up -d --build
    ```
- The first run downloads a few things and takes ~1–3 minutes.
- Your browser opens at **http://localhost:8080**

### 3. Log in
- **Username:** `admin`
- **Password:** `123`

### 4. Stop it
- Double-click **`stop.command`** (or run `docker compose down` in Terminal).
- Data is preserved for next time.

---

## Notes
- The site runs at **http://localhost:8080** on the machine that started it.
- To let others on the **same Wi‑Fi** reach it, share `http://<that-mac's-IP>:8080`.
- Everyone using it over the network shares one database.
- To completely reset the database to the shipped state:
  ```
  docker compose down -v
  docker compose up -d --build
  ```
  (`-v` deletes the saved data volume.)

## Troubleshooting
- **"port 8080 already in use"** → edit `docker-compose.yml`, change `"8080:80"` to
  e.g. `"8090:80"`, and use http://localhost:8090.
- **Blank page / DB errors on first load** → the database was still starting; wait
  ~20 seconds and refresh.
- **Apple Silicon (M1/M2/M3)** → works as-is; the images are multi-architecture.
