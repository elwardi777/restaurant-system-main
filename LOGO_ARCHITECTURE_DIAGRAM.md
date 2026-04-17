# Logo Persistence Architecture Diagram

## System Architecture Overview

```
┌────────────────────────────────────────────────────────────────┐
│                     RESTAURANT SYSTEM                          │
└────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                        APPLICATION BOOT SEQUENCE                        │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  1️⃣  StorageSymlinkProvider::boot()                                    │
│     ├─ Check: Does public/storage symlink exist?                       │
│     ├─ NO → Create symlink: public/storage → storage/app/public        │
│     └─ YES → Continue (no action needed)                               │
│                                                                         │
│  2️⃣  AppServiceProvider::boot()                                        │
│     ├─ Check: Does storage/app/public/restaurant-logos exist?          │
│     ├─ NO → Create directory with 0755 permissions                     │
│     └─ YES → Continue (no action needed)                               │
│                                                                         │
│  3️⃣  Application Ready ✅                                              │
│     ├─ Storage accessible via public/storage/ URL                      │
│     └─ Logo directory ready for file uploads                           │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                       FILE UPLOAD FLOW                                  │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  User uploads logo file (JPG/PNG/WebP, < 2MB)                          │
│         ↓                                                               │
│  SettingsController::update() or AdminController::updateSettings()      │
│         ↓                                                               │
│  Validate file (image, size, MIME type)                                │
│         ↓                                                               │
│  Check/Create directory: storage/app/public/restaurant-logos/          │
│         ↓                                                               │
│  Save file to disk:                                                    │
│  📁 storage/app/public/restaurant-logos/logo.png                       │
│         ↓                                                               │
│  Store path in database:                                               │
│  🗄️  settings table: restaurant_logo_path = 'restaurant-logos/logo.png'│
│         ↓                                                               │
│  Build and return URL:                                                 │
│  🔗 asset('storage/restaurant-logos/logo.png')                         │
│         ↓                                                               │
│  Response sent to frontend ✅                                          │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                      FILE ACCESS FLOW (Runtime)                         │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  Browser/Client: GET /storage/restaurant-logos/logo.png                │
│         ↓                                                               │
│  Web Server (NGINX/Apache):                                            │
│  └─ Receives request for: storage/restaurant-logos/logo.png            │
│         ↓                                                               │
│  File Resolution:                                                      │
│  ├─ Check: public/storage/restaurant-logos/logo.png                   │
│  └─ Found: symlink public/storage → storage/app/public                 │
│         ↓                                                               │
│  Symlink Traversal:                                                    │
│  ├─ Follow symlink: public/storage/ → storage/app/public/              │
│  ├─ Path becomes: storage/app/public/restaurant-logos/logo.png         │
│  └─ File exists: ✅                                                    │
│         ↓                                                               │
│  Return: 200 OK + image file contents                                  │
│         ↓                                                               │
│  Browser renders: <img src="/storage/restaurant-logos/logo.png" />     │
│         ↓                                                               │
│  🖼️  Logo displays ✅                                                  │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                   PERSISTENCE ACROSS SESSIONS                           │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  Session A:                      Session B:                            │
│  User: Admin A                   User: Admin B                          │
│  Time: 10:00 AM                  Time: 10:30 AM                         │
│                                                                         │
│  Action: Upload Logo             Action: View Page                     │
│     ↓                            ↓                                      │
│  File saved to disk          Database query                            │
│  📁 storage/.../logo.png     SELECT value FROM settings               │
│     ↓                        WHERE key='restaurant_logo_path'          │
│  Path stored in DB           ↓                                         │
│  🗄️  restaurant_logo_path   Returns: 'restaurant-logos/logo.png'      │
│     ↓                        ↓                                         │
│  ✅ Complete                 URL built: asset('storage/...')          │
│                              ↓                                         │
│                              Symlink resolved                          │
│                              📁 File accessed                           │
│                              ↓                                         │
│                              🖼️  Logo displays ✅                      │
│                                                                         │
│  KEY: Database persistence makes logo accessible to ANY user           │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                DIRECTORY STRUCTURE AFTER DEPLOYMENT                     │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  restaurant-system/                                                     │
│  ├─ public/                                                            │
│  │  ├─ storage/ ───────────┐                                           │
│  │  │  ├─ restaurant-logos/│ (accessible via symlink)                 │
│  │  │  │  └─ logo.png ────────┐                                       │
│  │  │  └─ ...             │    │                                       │
│  │  ├─ index.php          │    │                                       │
│  │  └─ ...                │    │                                       │
│  │                        │    │                                       │
│  ├─ storage/             │    │                                       │
│  │  └─ app/              │    │                                       │
│  │     └─ public/        │    │                                       │
│  │        └─ restaurant-logos/  (actual storage location)             │
│  │           └─ logo.png ───────────┘ (persisted here)                │
│  │                                                                     │
│  ├─ bootstrap/                                                         │
│  │  └─ providers.php                                                   │
│  │     ├─ StorageSymlinkProvider (FIRST)                              │
│  │     └─ AppServiceProvider (SECOND)                                 │
│  │                                                                     │
│  ├─ app/                                                               │
│  │  ├─ Providers/                                                     │
│  │  │  ├─ StorageSymlinkProvider.php (NEW)                            │
│  │  │  └─ AppServiceProvider.php (UPDATED)                           │
│  │  └─ Http/Controllers/                                              │
│  │     ├─ SettingsController.php (UPDATED)                           │
│  │     └─ AdminController.php (UPDATED)                              │
│  │                                                                     │
│  └─ database/                                                          │
│     └─ seeders/                                                       │
│        ├─ SettingsSeeder.php (NEW)                                    │
│        └─ DatabaseSeeder.php (UPDATED)                               │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                    DATABASE SCHEMA (settings table)                     │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌─────────────────────────────────────────────────────────┐           │
│  │ settings                                                │           │
│  ├─────────────────────────────────────────────────────────┤           │
│  │ id            │ BIGINT AUTO_INCREMENT PRIMARY KEY       │           │
│  │ key           │ VARCHAR(255) UNIQUE                     │           │
│  │ value         │ LONGTEXT                                │           │
│  │ created_at    │ TIMESTAMP                               │           │
│  │ updated_at    │ TIMESTAMP                               │           │
│  └─────────────────────────────────────────────────────────┘           │
│                                                                         │
│  Example Rows:                                                         │
│  ┌──────────────────────────┬────────────────────────────┐             │
│  │ key                      │ value                      │             │
│  ├──────────────────────────┼────────────────────────────┤             │
│  │ restaurant_name          │ RestauPro                  │             │
│  │ restaurant_logo_path     │ restaurant-logos/logo.png  │ ← KEY!      │
│  │ currency                 │ MAD                        │             │
│  │ tax_rate                 │ 20.0                       │             │
│  │ language                 │ fr                         │             │
│  │ ...                      │ ...                        │             │
│  └──────────────────────────┴────────────────────────────┘             │
│                                                                         │
│  ✅ restaurant_logo_path persists across:                             │
│     ├─ User sessions                                                   │
│     ├─ Logout/Login cycles                                            │
│     ├─ Server restarts                                                │
│     ├─ Browser caches                                                 │
│     └─ Account changes                                                │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                    URL GENERATION & RESOLUTION                          │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  In PHP Controller:                                                    │
│  ───────────────────                                                   │
│  $path = 'restaurant-logos/logo.png'  (from database)                 │
│  $url = asset('storage/' . $path)                                     │
│  // Returns: 'http://localhost/storage/restaurant-logos/logo.png'     │
│                                                                         │
│  In Browser/HTML:                                                      │
│  ─────────────────                                                     │
│  <img src="http://localhost/storage/restaurant-logos/logo.png" />     │
│                                                                         │
│  Web Server Resolution:                                                │
│  ──────────────────────                                                │
│  Request: GET /storage/restaurant-logos/logo.png                      │
│  Root: /var/www/restaurant-system/public/                             │
│  Path: /var/www/restaurant-system/public/storage/restaurant-logos/logo.png
│  Symlink: public/storage → ../storage/app/public                      │
│  Resolved: /var/www/restaurant-system/storage/app/public/restaurant-logos/logo.png
│  File: EXISTS ✅                                                       │
│  Response: 200 OK + file contents                                     │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                    ERROR RECOVERY MECHANISMS                            │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  Scenario 1: Symlink Missing                                           │
│  ──────────────────────────                                            │
│  Detected:  Next application boot                                      │
│  Recovery:  StorageSymlinkProvider::boot() creates it                 │
│  Time:      Automatic, next page load                                 │
│  User Impact: None (transparent)                                       │
│                                                                         │
│  Scenario 2: Logo Directory Missing                                    │
│  ──────────────────────────────────                                    │
│  Detected:  On logo upload or AppServiceProvider::boot()              │
│  Recovery:  Directory created with proper permissions                  │
│  Time:      Automatic                                                  │
│  User Impact: None (transparent)                                       │
│                                                                         │
│  Scenario 3: Settings Table Empty                                      │
│  ─────────────────────────────                                         │
│  Detected:  On SettingsController query                                │
│  Recovery:  Run SettingsSeeder or settings created on first use        │
│  Time:      One-time on deployment                                     │
│  User Impact: Logo doesn't show until uploaded                         │
│                                                                         │
│  Scenario 4: File Upload Fails                                         │
│  ────────────────────────────                                          │
│  Detected:  Exception in controller                                    │
│  Recovery:  Error response returned to frontend                        │
│  Logging:   Error logged to storage/logs/laravel.log                  │
│  User Impact: User sees error message, can retry                       │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                      DEPLOYMENT TIMELINE                                │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  T+0:00    Deploy code                                                 │
│  T+0:05    Run seeder: php artisan db:seed --class=SettingsSeeder     │
│  T+0:10    Clear cache: php artisan cache:clear                       │
│  T+0:15    Restart web server: systemctl restart php-fpm              │
│  T+0:20    Verify: Check symlink, upload test logo                    │
│  T+0:30    Production ready ✅                                         │
│                                                                         │
│  Total Time: 30 minutes                                                │
│  Downtime: 0 minutes (artisan commands don't block web)               │
│  Rollback Time: 5 minutes (if needed)                                 │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                    COMPONENT DEPENDENCIES                               │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  StorageSymlinkProvider                                                │
│  ├─ Depends on: File system, PHP symlink() function                   │
│  ├─ Used by: Laravel bootstrap (auto-loaded)                          │
│  └─ Affects: All file serving through storage/                        │
│                                                                         │
│  AppServiceProvider                                                    │
│  ├─ Depends on: Laravel Storage facade                                │
│  ├─ Used by: Laravel bootstrap (auto-loaded)                          │
│  └─ Affects: File directory permissions                               │
│                                                                         │
│  SettingsController / AdminController                                  │
│  ├─ Depends on: Setting model, Storage facade                         │
│  ├─ Used by: API endpoints                                            │
│  └─ Affects: Logo upload/display operations                           │
│                                                                         │
│  SettingsSeeder                                                        │
│  ├─ Depends on: Setting model, database                               │
│  ├─ Used by: php artisan db:seed                                      │
│  └─ Affects: Initial settings table population                        │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## Before & After State Machine

### BEFORE (Without Fix)

```
                    ┌──────────────────┐
                    │   Application    │
                    │      Starts      │
                    └────────┬─────────┘
                             │
                    ┌────────▼─────────┐
                    │  Symlink exists?  │
                    │  (Manual command) │
                    └────────┬─────────┘
                             │
                    YES ◄────┴────► NO
                      │              │
            ┌─────────▼──┐      ┌────▼─────────┐
            │   Logo OK  │      │  Logo BROKEN │
            │  (Temporary)│      │  (User Error)│
            └─────────┬──┘      └────┬─────────┘
                      │              │
            Server restarts    User runs command
                      │              │
            ┌─────────▼──┐      ┌────▼─────────┐
            │ SYMLINK    │      │   Fixes      │
            │  BROKEN    │◄─────┘  (Temporary) │
            │ AGAIN! ❌  │         └────────────┘
            └────────────┘
```

### AFTER (With Fix)

```
                    ┌──────────────────┐
                    │   Application    │
                    │      Starts      │
                    └────────┬─────────┘
                             │
                    ┌────────▼──────────────┐
                    │ StorageSymlinkProvider│
                    │   .boot()             │
                    └────────┬──────────────┘
                             │
                    ┌────────▼──────────────┐
                    │ Symlink exists?       │
                    │ (Automatic check)     │
                    └────────┬──────────────┘
                             │
                    YES ◄────┴────► NO
                      │              │
            ┌─────────▼──┐      ┌────▼──────────────┐
            │   Skip     │      │  Create symlink   │
            │  (Fast)    │      │  automatically    │
            └─────────┬──┘      └────┬──────────────┘
                      │              │
            ┌─────────▼──────────────────┐
            │  AppServiceProvider::boot()│
            └────────┬──────────────────┘
                     │
            ┌────────▼──────────────┐
            │ Directory ready?       │
            └────────┬──────────────┘
                     │
            YES ◄────┴────► NO
              │              │
    ┌─────────▼──┐      ┌────▼──────────┐
    │   Skip     │      │   Create dir   │
    │  (Fast)    │      │   w/ perms     │
    └─────┬──────┘      └────┬───────────┘
          │                  │
    ┌─────▼──────────────────▼──┐
    │  ✅ LOGO OK (Permanent)    │
    │  - Symlink exists          │
    │  - Directory ready         │
    │  - Path in database        │
    │  - Persists across sessions│
    │  - Works after restart     │
    └────────────────────────────┘
```

---

## Session Persistence Proof

```
Timeline of Events:

T1 [10:00 AM] - Admin A (Session 1)
├─ Login to system
├─ Navigate to Settings
├─ Upload logo file (logo.png)
│  ├─ File saved: storage/app/public/restaurant-logos/logo.png
│  ├─ Path stored: settings.restaurant_logo_path = 'restaurant-logos/logo.png'
│  └─ ✅ Logo displays
└─ Logout

T2 [10:05 AM] - Admin B (Session 2)
├─ Login to system
├─ Navigate to Dashboard
│  ├─ Query database: SELECT * FROM settings
│  ├─ Find: restaurant_logo_path = 'restaurant-logos/logo.png'
│  ├─ Build URL: asset('storage/restaurant-logos/logo.png')
│  ├─ Follow symlink: public/storage/ → storage/app/public/
│  ├─ File found: storage/app/public/restaurant-logos/logo.png (from T1!)
│  └─ ✅ SAME LOGO displays (uploaded by Admin A)
└─ Logout

T3 [10:10 AM] - Admin A (Session 3)
├─ Login to system again
├─ Navigate to Dashboard
│  ├─ Query database: SELECT * FROM settings
│  ├─ Find: restaurant_logo_path = 'restaurant-logos/logo.png'
│  ├─ Build URL: asset('storage/restaurant-logos/logo.png')
│  ├─ File found: storage/app/public/restaurant-logos/logo.png (original!)
│  └─ ✅ SAME LOGO displays (still there)
└─ Works perfectly ✅

KEY: Database stores path, not session/user-specific data
     Any user/session can query and use the same path
```

---

This architecture ensures:
✅ **Automatic Symlink**: No manual commands needed
✅ **Database Persistence**: Logo works across all sessions
✅ **Fault Recovery**: Components recreate themselves on boot
✅ **Cross-User Consistency**: All users see same logo
✅ **Multi-Server Support**: Each server independently ensures symlink
✅ **Platform Compatible**: Works on Windows, Linux, macOS
