#!/bin/bash
# Double-click this file on a Mac to stop the Records Management System.
cd "$(dirname "$0")" || exit 1
echo "Stopping the Records Management System..."
docker compose down
echo "Stopped. (Your data is kept for next time.)"
read -r -p "Press Enter to close this window."
