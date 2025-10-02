# PowerShell script to switch between local development and ngrok
param(
    [Parameter(Mandatory=$true)]
    [ValidateSet("local", "ngrok")]
    [string]$Mode
)

$envFile = ".env"

Write-Host "Switching to $Mode configuration..." -ForegroundColor Green

if ($Mode -eq "local") {
    # Local development
    $content = Get-Content $envFile
    $content = $content -replace '^APP_URL=.*', 'APP_URL=http://localhost:8000'
    $content = $content -replace '^ASSET_URL=.*', '# ASSET_URL='
    $content = $content -replace '^FORCE_HTTPS=.*', '# FORCE_HTTPS=false'
    $content = $content -replace '^SESSION_DOMAIN=.*', 'SESSION_DOMAIN=null'
    $content = $content -replace '^SESSION_SECURE_COOKIE=.*', '# SESSION_SECURE_COOKIE=true'
    $content = $content -replace '^SANCTUM_STATEFUL_DOMAINS=.*', 'SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1'
    $content = $content -replace '^WEBAUTHN_RELYING_PARTY_ID=.*', 'WEBAUTHN_RELYING_PARTY_ID=localhost'
    $content = $content -replace '^WEBAUTHN_RELYING_PARTY_ORIGIN=.*', 'WEBAUTHN_RELYING_PARTY_ORIGIN=http://localhost'
    $content | Set-Content $envFile
} elseif ($Mode -eq "ngrok") {
    # ngrok configuration
    $ngrokUrl = "herma-authorisable-persuadably.ngrok-free.dev"
    $content = Get-Content $envFile
    $content = $content -replace '^APP_URL=.*', "APP_URL=https://$ngrokUrl"
    $content = $content -replace '^# ASSET_URL=.*', "ASSET_URL=https://$ngrokUrl"
    $content = $content -replace '^# FORCE_HTTPS=.*', 'FORCE_HTTPS=true'
    $content = $content -replace '^SESSION_DOMAIN=.*', "SESSION_DOMAIN=$ngrokUrl"
    $content = $content -replace '^# SESSION_SECURE_COOKIE=.*', 'SESSION_SECURE_COOKIE=true'
    $content = $content -replace '^SANCTUM_STATEFUL_DOMAINS=.*', "SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,$ngrokUrl"
    $content = $content -replace '^WEBAUTHN_RELYING_PARTY_ID=.*', "WEBAUTHN_RELYING_PARTY_ID=$ngrokUrl"
    $content = $content -replace '^WEBAUTHN_RELYING_PARTY_ORIGIN=.*', "WEBAUTHN_RELYING_PARTY_ORIGIN=https://$ngrokUrl"
    $content | Set-Content $envFile
}

Write-Host "✓ Configuration updated" -ForegroundColor Green
Write-Host "Running: php artisan config:clear" -ForegroundColor Yellow
php artisan config:clear
php artisan cache:clear

Write-Host "`n✓ Done! Restart your servers:" -ForegroundColor Green
Write-Host "  - Laravel: php artisan serve" -ForegroundColor Cyan
Write-Host "  - Vite: npm run dev" -ForegroundColor Cyan
Write-Host "  - ngrok: ngrok http 8000" -ForegroundColor Cyan
