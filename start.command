#!/bin/bash
# Double-click this file on a Mac to start the Records Management System.
cd "$(dirname "$0")" || exit 1

echo "=================================================="
echo " UPHSD College of Engineering"
echo " Records Management System - starting up..."
echo "=================================================="

# Try to launch Docker Desktop if it isn't running yet
if ! docker info >/dev/null 2>&1; then
  echo "Starting Docker Desktop (this can take ~30 seconds)..."
  open -a Docker 2>/dev/null
  # wait for the Docker engine to be ready
  for i in $(seq 1 60); do
    if docker info >/dev/null 2>&1; then break; fi
    sleep 2
  done
fi

if ! docker info >/dev/null 2>&1; then
  echo "ERROR: Docker is not running. Please install/open Docker Desktop, then run this again."
  echo "Download: https://www.docker.com/products/docker-desktop/"
  read -r -p "Press Enter to close."
  exit 1
fi

echo "Building and launching (first run downloads images, ~1-3 min)..."
docker compose up -d --build

echo "Waiting for the site to come up..."
sleep 8
open "http://localhost:8080"

echo ""
echo "=================================================="
echo " Running at:  http://localhost:8080"
echo " Login:       admin  /  123"
echo ""
echo " To stop it later, double-click stop.command"
echo "=================================================="
read -r -p "Press Enter to close this window."
