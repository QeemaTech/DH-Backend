# PowerShell script to test API endpoints
# Usage: .\test-api.ps1

$baseUrl = "http://multi-vendor-e-commerce.test/api"

Write-Host "Testing Multi-Vendor E-Commerce API" -ForegroundColor Green
Write-Host "Base URL: $baseUrl" -ForegroundColor Yellow
Write-Host ""

# Test 1: Get Products (Public endpoint)
Write-Host "Test 1: Get Products..." -ForegroundColor Cyan
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/products" -Method GET -UseBasicParsing
    Write-Host "✓ Products endpoint working - Status: $($response.StatusCode)" -ForegroundColor Green
    Write-Host "Response: $($response.Content.Substring(0, [Math]::Min(200, $response.Content.Length)))..." -ForegroundColor Gray
} catch {
    Write-Host "✗ Products endpoint failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""

# Test 2: Get Categories
Write-Host "Test 2: Get Categories..." -ForegroundColor Cyan
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/categories" -Method GET -UseBasicParsing
    Write-Host "✓ Categories endpoint working - Status: $($response.StatusCode)" -ForegroundColor Green
} catch {
    Write-Host "✗ Categories endpoint failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""

# Test 3: Get Vendors
Write-Host "Test 3: Get Vendors..." -ForegroundColor Cyan
try {
    $response = Invoke-WebRequest -Uri "$baseUrl/vendors" -Method GET -UseBasicParsing
    Write-Host "✓ Vendors endpoint working - Status: $($response.StatusCode)" -ForegroundColor Green
} catch {
    Write-Host "✗ Vendors endpoint failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "Basic API tests completed!" -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Import Postman collection: Multi-Vendor E-Commerce API.postman_collection.json" -ForegroundColor White
Write-Host "2. Set base_url to: http://multi-vendor-e-commerce.test" -ForegroundColor White
Write-Host "3. Start testing with Register endpoint" -ForegroundColor White
Write-Host "4. See QUICK_TEST_GUIDE.md for detailed instructions" -ForegroundColor White
