@echo off
REM Serve the RMS app and expose it on a FREE Cloudflare quick tunnel.
REM Login: admin / Admin@123
REM
REM NOTE: --config cloudflared-quick.yml is REQUIRED. Without it, cloudflared
REM loads %USERPROFILE%\.cloudflared\config.yml, whose "http_status:404"
REM catch-all rule swallows all quick-tunnel traffic and returns 404.

start "RMS PHP Server" cmd /k "cd /d D:\rms && C:\php-8.3.6\php.exe -S 0.0.0.0:8090"

timeout /t 3 /nobreak >nul

start "RMS Cloudflare Tunnel" cmd /k "cloudflared --config D:\rms\cloudflared-quick.yml tunnel --url http://localhost:8090 --no-autoupdate"

echo.
echo ============================================================
echo  RMS starting. In the "RMS Cloudflare Tunnel" window look for:
echo     https://xxxx-xxxx-xxxx.trycloudflare.com
echo  That is your public URL. Login: admin / Admin@123
echo  Close both windows to stop sharing.
echo ============================================================
pause
