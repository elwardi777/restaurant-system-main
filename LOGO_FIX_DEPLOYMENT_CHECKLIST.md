# Logo Persistence Fix - Quick Implementation Checklist

## Pre-Deployment Review

- [ ] Review all changes: `LOGO_PERSISTENCE_FIX.md`
- [ ] Test in development environment first
- [ ] Backup database before deployment
- [ ] Backup `storage/` directory before deployment

## Files Changed/Created

- ✅ [NEW] `app/Providers/StorageSymlinkProvider.php` - Automatic symlink creation
- ✅ [UPDATED] `app/Providers/AppServiceProvider.php` - Logo directory initialization
- ✅ [UPDATED] `app/Http/Controllers/SettingsController.php` - Logo path persistence
- ✅ [UPDATED] `app/Http/Controllers/AdminController.php` - Logo path persistence
- ✅ [NEW] `database/seeders/SettingsSeeder.php` - Settings initialization
- ✅ [UPDATED] `database/seeders/DatabaseSeeder.php` - Register SettingsSeeder
- ✅ [UPDATED] `bootstrap/providers.php` - Register StorageSymlinkProvider

## Deployment Steps

### Stage 1: Code Deployment (5 minutes)

```bash
# 1. Pull/deploy code changes
cd /path/to/restaurant-system
git pull origin main  # or your deployment method

# 2. Install/update dependencies (if needed)
composer install

# Verify all files are in place:
ls -la app/Providers/StorageSymlinkProvider.php
ls -la app/Providers/AppServiceProvider.php
ls -la database/seeders/SettingsSeeder.php
```

### Stage 2: Database Migration (5 minutes)

```bash
# 3. Run database seeder (initializes settings)
php artisan db:seed --class=SettingsSeeder

# Verify settings created:
php artisan tinker
>>> DB::table('settings')->where('key', 'restaurant_logo_path')->first();
# Should return: object with 'restaurant-logos/logo.png' or empty value
```

### Stage 3: Application Reset (5 minutes)

```bash
# 4. Clear all caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Restart PHP/Web Server
# For NGINX + PHP-FPM:
systemctl restart php-fpm
systemctl restart nginx

# For Apache + mod_php:
systemctl restart apache2

# For development (artisan serve):
# Stop the current server (Ctrl+C) and restart
php artisan serve
```

### Stage 4: Verification (5 minutes)

```bash
# 6. Verify Symlink
ls -la public/storage
# Should show: public/storage -> ../storage/app/public (or Windows junction)

# 7. Verify Directory
ls -la storage/app/public/restaurant-logos/
# Should exist and be readable

# 8. Verify Database
php artisan tinker
>>> DB::table('settings')->get();
# Look for 'restaurant_logo_path' entry

# 9. Test via Browser
# Navigate to: http://your-domain/storage/restaurant-logos/logo.png
# Should return 404 if logo not uploaded yet (that's OK)

# 10. Upload a Test Logo
# Use the admin panel or API to upload a logo
# Verify it appears in storage/app/public/restaurant-logos/logo.png
# Verify URL works: http://your-domain/storage/restaurant-logos/logo.png
```

## Quick Verification Script

Save this as `verify_logo_fix.sh`:

```bash
#!/bin/bash

echo "=== Logo Persistence Fix Verification ==="
echo ""

echo "1. Checking symlink..."
if [ -L public/storage ]; then
    echo "   ✅ Symlink exists"
    ls -la public/storage | head -1
else
    echo "   ❌ Symlink missing!"
    exit 1
fi

echo ""
echo "2. Checking logo directory..."
if [ -d storage/app/public/restaurant-logos ]; then
    echo "   ✅ Directory exists"
    ls -la storage/app/public/restaurant-logos/
else
    echo "   ❌ Directory missing!"
    exit 1
fi

echo ""
echo "3. Checking database..."
php artisan tinker <<EOF
\$setting = DB::table('settings')->where('key', 'restaurant_logo_path')->first();
if (\$setting) {
    echo "   ✅ Setting exists: " . \$setting->value . "\n";
} else {
    echo "   ⚠️  Setting not found (will be created on first upload)\n";
}
EOF

echo ""
echo "4. Checking provider registration..."
if grep -q "StorageSymlinkProvider" bootstrap/providers.php; then
    echo "   ✅ StorageSymlinkProvider registered"
else
    echo "   ❌ StorageSymlinkProvider not registered!"
    exit 1
fi

echo ""
echo "=== All Checks Passed ✅ ==="
```

Usage:
```bash
chmod +x verify_logo_fix.sh
./verify_logo_fix.sh
```

## Rollback Plan (If Issues Arise)

```bash
# 1. Stop the application
systemctl stop php-fpm nginx

# 2. Restore from backup
git checkout bootstrap/providers.php
git checkout app/Providers/AppServiceProvider.php
git checkout app/Http/Controllers/SettingsController.php
git checkout app/Http/Controllers/AdminController.php

# 3. Clear cache
rm -rf bootstrap/cache/*.php

# 4. Restart
systemctl start php-fpm nginx
```

## Troubleshooting Quick Reference

### Logo Not Displaying

```bash
# Check 1: Symlink exists?
ls -la public/storage

# Check 2: Logo file exists?
ls -la storage/app/public/restaurant-logos/

# Check 3: Database has path?
php artisan tinker
>>> DB::table('settings')->where('key', 'restaurant_logo_path')->first();

# Check 4: URL accessible?
curl -I http://localhost/storage/restaurant-logos/logo.png

# Check 5: Logs for errors?
tail -f storage/logs/laravel.log | grep -i logo
```

### Symlink Issues on Windows

```bash
# Windows may need administrator command prompt
# The code automatically handles this, but if needed manually:

# Option 1: Use junction (recommended for Windows)
mklink /J C:\path\public\storage C:\path\storage\app\public

# Option 2: Use symlink (requires admin)
mklink /D C:\path\public\storage C:\path\storage\app\public
```

### Permissions Errors

```bash
# Fix permissions
chmod 755 storage/
chmod 755 storage/app/
chmod 755 storage/app/public/
chmod 755 storage/app/public/restaurant-logos/

# If using Apache/NGINX, set ownership
chown -R www-data:www-data storage/
```

## Post-Deployment Checklist

- [ ] Symlink verified with `ls -la public/storage`
- [ ] Logo directory verified with `ls -la storage/app/public/restaurant-logos/`
- [ ] Database settings seeded successfully
- [ ] Web server restarted (PHP-FPM, Apache, or Nginx)
- [ ] Cache cleared and regenerated
- [ ] Test admin can upload logo successfully
- [ ] Test logo displays after upload
- [ ] Test logo persists across logout/login cycle
- [ ] Logs checked for errors: `tail storage/logs/laravel.log`
- [ ] Monitor storage usage: `du -sh storage/`

## Support Contacts

- **Laravel Logs**: `storage/logs/laravel.log`
- **Server Logs**: Check NGINX/Apache/PHP-FPM logs
- **Database**: Access MySQL to check settings table

## Key Performance Indicator

After deployment, verify these metrics:

| Metric | Status |
|--------|--------|
| Symlink exists | ✅ Should exist |
| Logo directory exists | ✅ Should exist |
| Database entry present | ✅ After first upload |
| Upload succeeds | ✅ No errors |
| Logo displays | ✅ Accessible via URL |
| Logo persists after restart | ✅ Still displays |
| Logo persists across sessions | ✅ Works for all users |

---

**Estimated Total Time**: 20-30 minutes

**Risk Level**: Low (changes are additive and backward-compatible)

**Rollback Time**: < 5 minutes (if needed)
