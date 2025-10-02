# PowerShell script to update .env with new ngrok URL
param(
    [Parameter(Mandatory=$true)]
    [string]$NgrokUrl
)

$envFile = ".env"

# Remove https:// if provided
$NgrokUrl = $NgrokUrl -replace '^https?://', ''

Write-Host "Updating .env with ngrok URL: $NgrokUrl" -ForegroundColor Green

# Update all ngrok-related settings
(Get-Content $envFile) | ForEach-Object {
    $_ -replace '^APP_URL=.*', "APP_URL=https://$NgrokUrl" `
       -replace '^ASSET_URL=.*', "ASSET_URL=https://$NgrokUrl" `
       -replace '^SESSION_DOMAIN=.*', "SESSION_DOMAIN=$NgrokUrl" `
       -replace '^SANCTUM_STATEFUL_DOMAINS=.*', "SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,$NgrokUrl" `
       -replace '^WEBAUTHN_RELYING_PARTY_ID=.*', "WEBAUTHN_RELYING_PARTY_ID=$NgrokUrl" `
       -replace '^WEBAUTHN_RELYING_PARTY_ORIGIN=.*', "WEBAUTHN_RELYING_PARTY_ORIGIN=https://$NgrokUrl"
} | Set-Content $envFile

Write-Host "✓ Updated .env file" -ForegroundColor Green
Write-Host "Running: php artisan config:clear" -ForegroundColor Yellow
php artisan config:clear

Write-Host "`n✓ Done! Restart your Laravel server (php artisan serve)" -ForegroundColor Green
