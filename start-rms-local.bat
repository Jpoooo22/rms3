@echo off
REM ============================================================
REM  Serve the RMS app on THIS MACHINE ONLY.
REM
REM  No Cloudflare tunnel, no public URL. The server binds to
REM  127.0.0.1, so nothing outside this computer can reach it.
REM  (Use start-rms-public.bat only when you deliberately want
REM  to share it over the internet.)
REM ============================================================

cd /d "%~dp0"

echo.
echo  Starting RMS locally...
echo  Open:  http://localhost:8090
echo.
echo  Close this window to stop the server.
echo.

"C:\php-8.3.6\php.exe" -S 127.0.0.1:8090
