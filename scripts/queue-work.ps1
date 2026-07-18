param(
    [int]$tries = 3
)

Write-Host "Starting Laravel queue worker (database) — press Ctrl+C to stop"
while ($true) {
    php artisan queue:work database --tries=$tries --sleep=3 --timeout=60
    Write-Host "Queue worker exited; restarting in 2s..."
    Start-Sleep -Seconds 2
}
