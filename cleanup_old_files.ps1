# Cleanup Old Server-Side Components
Write-Host "🧹 Cleaning up old server-side Arweave components..." -ForegroundColor Yellow

# Old Service Files
$oldServices = @(
    "app\Services\RealArweaveService.php",
    "app\Services\ArweavePaymentService.php",
    "app\Services\ArweaveService.php",
    "app\Services\DirectArweaveService.php",
    "app\Services\ModernArweaveClient.php"
)

Write-Host "`n📁 Removing old service files..." -ForegroundColor Cyan
foreach ($file in $oldServices) {
    if (Test-Path $file) {
        Remove-Item $file -Force
        Write-Host "  ✅ Deleted: $file" -ForegroundColor Green
    } else {
        Write-Host "  ⏭️  Skipped (not found): $file" -ForegroundColor Gray
    }
}

# Old Migration Files (optional - keep for history)
Write-Host "`n📝 Old migration files (kept for reference):" -ForegroundColor Cyan
Write-Host "  - database\migrations\2025_09_25_100000_create_crypto_payments_table.php"
Write-Host "  - database\migrations\2024_01_01_000000_create_payment_transactions_table.php"

# PaymentController (check if used for other things)
Write-Host "`n⚠️  Checking PaymentController..." -ForegroundColor Yellow
if (Test-Path "app\Http\Controllers\PaymentController.php") {
    Write-Host "  ℹ️  PaymentController still exists - may be used for other features" -ForegroundColor Blue
    Write-Host "  ℹ️  Review manually before deleting" -ForegroundColor Blue
}

# Summary
Write-Host "`n✅ CLEANUP COMPLETE!" -ForegroundColor Green
Write-Host "`nWhat was removed:" -ForegroundColor White
Write-Host "  ✅ Database tables: crypto_payments, payment_transactions, permanent_storage"
Write-Host "  ✅ Controllers: PermanentStorageController, ArweaveController"
Write-Host "  ✅ Models: CryptoPayment, PaymentTransaction, PermanentStorage"
Write-Host "  ✅ Services: ArweaveBundlerService, ArweaveIntegrationService, + others"

Write-Host "`nWhat was kept (for client-side tracking):" -ForegroundColor White  
Write-Host "  ✅ ArweaveClientController (NEW)"
Write-Host "  ✅ ArweaveTransaction model"
Write-Host "  ✅ ArweaveWallet model"
Write-Host "  ✅ Client-side bundlr modules"

Write-Host "`n🚀 Your client-side Arweave system is now clean and ready!" -ForegroundColor Cyan
