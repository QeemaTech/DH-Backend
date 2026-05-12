# PowerShell Script for Automated API Testing
# Usage: .\run-automated-tests.ps1

Write-Host "Starting Automated API Tests..." -ForegroundColor Green
Write-Host ""

# Run PHPUnit tests
Write-Host "Running PHPUnit Tests..." -ForegroundColor Yellow
php artisan test --filter=ApiAutomatedTest

if ($LASTEXITCODE -eq 0) {
    Write-Host "PHPUnit Tests Passed" -ForegroundColor Green
} else {
    Write-Host "PHPUnit Tests Failed" -ForegroundColor Red
}

Write-Host ""
Write-Host "Running API Endpoints Tests..." -ForegroundColor Yellow
php artisan test --filter=ApiEndpointsTest

if ($LASTEXITCODE -eq 0) {
    Write-Host "API Endpoints Tests Passed" -ForegroundColor Green
} else {
    Write-Host "API Endpoints Tests Failed" -ForegroundColor Red
}

Write-Host ""
Write-Host "Test Summary:" -ForegroundColor Cyan
php artisan test tests/Feature/ApiAutomatedTest.php tests/Feature/ApiEndpointsTest.php --compact

Write-Host ""
Write-Host "Automated Testing Complete!" -ForegroundColor Green
