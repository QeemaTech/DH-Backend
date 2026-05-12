# 🚀 How to Run Automated Tests

## Quick Start

### Option 1: Using PowerShell Script (Windows)

**Open PowerShell in your project directory and run:**
```powershell
.\run-automated-tests.ps1
```

**Or if you get execution policy error:**
```powershell
powershell -ExecutionPolicy Bypass -File .\run-automated-tests.ps1
```

---

### Option 2: Direct PHP Artisan Commands

**Run all automated tests:**
```bash
php artisan test --filter=ApiAutomatedTest
php artisan test --filter=ApiEndpointsTest
```

**Run all tests together:**
```bash
php artisan test --filter=ApiAutomatedTest --filter=ApiEndpointsTest
```

---

### Option 3: Run Specific Test

**Run specific test method:**
```bash
php artisan test --filter=test_complete_api_flow_automated
php artisan test --filter=test_products_api_filters_automated
```

---

## 📋 Step-by-Step Instructions

### Windows (PowerShell)

1. **Open PowerShell**
   - Press `Win + X`
   - Select "Windows PowerShell" or "Terminal"

2. **Navigate to project directory**
   ```powershell
   cd C:\laragon\www\multi-vendor-e-commerce
   ```

3. **Run the script**
   ```powershell
   .\run-automated-tests.ps1
   ```

4. **If you get execution policy error:**
   ```powershell
   Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
   .\run-automated-tests.ps1
   ```

---

### Command Line (CMD)

**Open CMD and run:**
```cmd
cd C:\laragon\www\multi-vendor-e-commerce
php artisan test --filter=ApiAutomatedTest
php artisan test --filter=ApiEndpointsTest
```

---

## 🎯 What Will Be Tested

✅ **Authentication** - Register, Login, Logout  
✅ **Products** - List, Search, Filter, Details  
✅ **Cart** - Add, Update, Remove, Clear  
✅ **Orders** - Create, View, List  
✅ **Categories & Vendors**  
✅ **Rate Limiting**  
✅ **Protected Routes**  
✅ **Query Parameters**  

---

## 📊 Expected Output

You should see:
```
Starting Automated API Tests...

Running PHPUnit Tests...
✓ Tests passed

Running API Endpoints Tests...
✓ Tests passed

Test Summary:
Tests: X passed
Duration: X.XXs
```

---

## 🔧 Troubleshooting

### Error: "Execution policy"
**Solution:**
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

### Error: "php: command not found"
**Solution:** Make sure PHP is in your PATH or use full path:
```powershell
C:\laragon\bin\php\php-8.3.19-Win32-vs16-x64\php.exe artisan test
```

### Error: "Role does not exist"
**Solution:** Tests create roles automatically, but if it fails:
```bash
php artisan db:seed --class=RolesSeeder
```

---

## 📚 More Commands

### Run with verbose output:
```bash
php artisan test --filter=ApiAutomatedTest -v
```

### Run with coverage:
```bash
php artisan test --coverage
```

### Run all tests:
```bash
php artisan test
```

---

**Ready to test? Run this now:**

```powershell
.\run-automated-tests.ps1
```
