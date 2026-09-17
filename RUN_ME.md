# FixTrack - Laravel Livewire Starter Kit

## 🚨 COMPLETE TROUBLESHOOTING GUIDE

### "localhost refused to connect" - FIXED

#### Issue 1: Server Not Running
**Problem**: Laravel development server not started
**Fix**: Run in PowerShell (as Administrator):
```powershell
cd D:\GRC\capstone\sys-arch-main
php artisan serve
```
Visit: http://localhost:8000

#### Issue 2: Port 8000 Already in Use
**Problem**: Another app using port 8000
**Fix**: Use alternate port:
```powershell
php artisan serve --port=8080
# Then visit: http://localhost:8080
```

#### Issue 3: XAMPP MySQL Not Running
**Problem**: Apache/MySQL services not started in XAMPP
**Fix**: 
1. Open XAMPP Control Panel
2. Click **Start** on Apache and MySQL
3. Wait for status to change to **Running**

#### Issue 4: Database Not Migrated
**Problem**: Tables not created in database
**Fix**:
```powershell
cd D:\GRC\capstone\sys-arch-main

# Option A: SQLite (default, no server needed)
php artisan migrate --force

# Option B: XAMPP MySQL
# 1. Create database 'fixtrack' in phpmyadmin (http://localhost/phpmyadmin)
# 2. Change .env: DB_CONNECTION=mysql
# 3. Run migrations:
php artisan migrate --force
```

#### Issue 5: Dependencies Not Installed
**Problem**: vendor/ directory missing
**Fix**:
```powershell
composer install
npm install
npm run build
```

#### Issue 6: Cache Needs Clearing
**Problem**: Old config/cache cached
**Fix**:
```powershell
php artisan cache:clear
php artisan config:clear
```

#### Issue 7: Key Not Generated
**Problem**: APP_KEY missing or invalid
**Fix**:
```powershell
php artisan key:generate
```

---

## 📦 QUICK START - ALL IN ONE

Run these commands **in order** in PowerShell:

```powershell
# 1. Install PHP dependencies
composer install

# 2. Copy .env if not exists
copy .env.example .env

# 3. Generate app key
php artisan key:generate

# 4. Run migrations (SQLite by default)
php artisan migrate --force

# 5. Install Node.js dependencies
npm install

# 6. Build frontend
npm run build

# 7. Start server
php artisan serve
# OR with port 8080 if 8000 is busy:
php artisan serve --port=8080
```

---

## 🗄️ DATABASE CONFIGURATION

### Default: SQLite (Recommended - No MySQL needed)
- Edit `.env`: `DB_CONNECTION=sqlite`
- Database file: `database/database.sqlite` (auto-created)
- **No XAMPP MySQL needed!**

### Alternative: XAMPP MySQL
1. Start XAMPP → Start Apache & MySQL
2. Create database `fixtrack` in phpmyadmin
3. Edit `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fixtrack
DB_USERNAME=root
DB_PASSWORD=
```

---

## 🌐 ACCESS THE APP

After running `php artisan serve`:

| URL | Description |
|-----|-------------|
| `http://localhost:8000` | Default Laravel server |
| `http://127.0.0.1:8000` | Alternative address |
| `http://localhost:8080` | If port 8000 is busy |

**First-time login**: Use admin credentials created by `DemoTechniciansSeeder`

---

## 🔧 COMMON FIXES

| Problem | Solution |
|---------|----------|
| "Connection refused" | Run `php artisan serve` |
| Port 8000 busy | Use `php artisan serve --port=8080` |
| Firewall block | Allow port 8000/8080 for private networks |
| White screen | Run `php artisan config:clear` |
| 404 errors | Run `php artisan route:list` |
| Database errors | Run `php artisan migrate --force` |

---

## 📁 PROJECT STRUCTURE

```
sys-arch-main/
├── .env              # Configuration (created)
├── artisan           # Laravel entry point
├── composer.json     # PHP dependencies
├── package.json      # Node.js dependencies
├── database/
│   ├── migrations/   # 27+ migrations
│   └── seeders/      # Demo data
├── app/              # Laravel application code
├── routes/           # URL routes
├── public/           # Web root (index.php + .htaccess)
├── resources/        # Frontend assets (Tailwind, Livewire)
└── config/           # Laravel config files
```

---

## ⚠️ KNOWN ISSUES & FIXES

### 1. "Class XXX not found" errors
**Cause**: `composer install` not run
**Fix**: `composer install --no-interaction`

### 2. "Unable to locate file in Vite manifest"  
**Cause**: `npm run build` not run
**Fix**: `npm run build`

### 3. SQLite database locked
**Cause**: Multiple processes accessing database
**Fix**: Delete `database/database.sqlite` and re-run `php artisan migrate --force`

### 4. Livewire not updating
**Cause**: Browser cache
**Fix**: Clear browser cache, run `npm run build`

### 5. Authentication not working
**Cause**: Sessions not configured
**Fix**: Ensure `SESSION_DRIVER=database` in .env, run migrations

---

## 🆘 NEED MORE HELP?

1. Check XAMPP services are **Running** (not just Started)
2. Ensure port 8000/8080 is not blocked by antivirus
3. Run `php artisan serve` from project root
4. Visit `http://127.0.0.1:8000` (not just localhost)

**If still not working**, run these diagnostics:
```powershell
# Check if server is running
php artisan serve --detailed

# Check routes
php artisan route:list

# Clear all caches
php artisan clear-reset
```