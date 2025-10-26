# 🛠️ BACKEND UPDATE GUIDE FOR VERCEL INTEGRATION

## ✅ API ENDPOINTS IDENTIFIED FROM FRONTEND

### Authentication Endpoints
- `/auth/login.php` - User login
- `/auth/register.php` - User registration
- `/auth/me.php` - Get current user info
- `/auth/check.php` - Check authentication status
- `/auth/logout.php` - User logout

### User Management Endpoints
- `/users/profile.php` - Get/update user profile
- `/users/avatar.php` - Upload user avatar

### Player Endpoints
- `/rewards/index.php` - Get available rewards
- `/rewards/redeem.php` - Redeem rewards
- `/points/bonus.php` - Claim daily bonus
- `/points/history.php` - Get points history
- `/leaderboard/index.php` - Get leaderboard data
- `/shop/redeem_with_points.php` - Redeem shop items
- `/transactions/secure_purchase.php` - Process transactions

### Admin Endpoints
- `/admin/statistics.php` - Get admin statistics
- `/admin/transaction_stats.php` - Get transaction stats
- `/admin/security_alerts.php` - Get security alerts

## 📁 BACKEND FILE STRUCTURE TO UPDATE

Based on your frontend code, your backend should have this structure in `backend_infintyfree`:

```
backend_infintyfree/
├── auth/
│   ├── login.php
│   ├── register.php
│   ├── me.php
│   ├── check.php
│   └── logout.php
├── users/
│   ├── profile.php
│   └── avatar.php
├── rewards/
│   ├── index.php
│   └── redeem.php
├── points/
│   ├── bonus.php
│   └── history.php
├── leaderboard/
│   └── index.php
├── shop/
│   └── redeem_with_points.php
├── transactions/
│   └── secure_purchase.php
├── admin/
│   ├── statistics.php
│   ├── transaction_stats.php
│   └── security_alerts.php
└── config/
    └── database.php (or similar)
```

## 🔧 CORS HEADERS TO ADD

### Add to the beginning of EACH PHP file that returns JSON data:

```php
<?php
// Enable CORS for Vercel frontend
header("Access-Control-Allow-Origin: https://gamezoneismo.vercel.app");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Your existing code continues here...
?>
```

## 🎯 SPECIFIC FILES TO UPDATE

### 1. ALL API ENDPOINT FILES
Add CORS headers to these files:
- `auth/login.php`
- `auth/register.php`
- `auth/me.php`
- `auth/check.php`
- `auth/logout.php`
- `users/profile.php`
- `users/avatar.php`
- `rewards/index.php`
- `rewards/redeem.php`
- `points/bonus.php`
- `points/history.php`
- `leaderboard/index.php`
- `shop/redeem_with_points.php`
- `transactions/secure_purchase.php`
- `admin/statistics.php`
- `admin/transaction_stats.php`
- `admin/security_alerts.php`

### 2. SESSION CONFIGURATION
In files that use sessions, update session configuration:

```php
<?php
// Add after CORS headers and before session_start()
session_set_cookie_params([
    'secure' => false, // Set to true if using HTTPS
    'httponly' => true,
    'samesite' => 'None'
]);
session_start();

// Your existing code...
?>
```

## 📋 VERIFICATION CHECKLIST

### Before FileZilla Upload
- [ ] Add CORS headers to all API endpoint files
- [ ] Update session configuration where needed
- [ ] Test locally if possible
- [ ] Create backup of original files

### After FileZilla Upload
- [ ] Verify files uploaded to correct locations
- [ ] Check file permissions (should be 644 for PHP files)
- [ ] Test one endpoint at a time

## 🚨 COMMON BACKEND ISSUES & SOLUTIONS

### Issue 1: Session/Cookie Problems
**Symptoms**: Login works but user gets logged out immediately
**Solution**: 
1. Ensure session cookie settings are correct
2. Check that session files are writable
3. Verify session save path permissions

### Issue 2: File Upload Issues (Avatar)
**Symptoms**: Profile image upload fails
**Solution**:
1. Check upload directory permissions (755)
2. Verify upload directory exists and is writable
3. Check PHP upload limits in php.ini

### Issue 3: Database Connection Issues
**Symptoms**: All API calls fail with database errors
**Solution**:
1. Verify database credentials are correct
2. Check database server is accessible
3. Confirm database tables exist

## 🛡️ SECURITY CONSIDERATIONS

### 1. Input Validation
Ensure all API endpoints validate input:
```php
// Example validation
if (!isset($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email']);
    exit();
}
```

### 2. SQL Injection Prevention
Use prepared statements:
```php
// Good - prepared statement
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);

// Bad - direct interpolation
$stmt = $pdo->query("SELECT * FROM users WHERE email = '$email'");
```

### 3. Rate Limiting
Consider adding rate limiting to prevent abuse:
```php
// Simple rate limiting example
session_start();
if (!isset($_SESSION['last_request'])) {
    $_SESSION['last_request'] = time();
} elseif (time() - $_SESSION['last_request'] < 1) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many requests']);
    exit();
}
$_SESSION['last_request'] = time();
```

## 🧪 TESTING AFTER UPDATE

### 1. Test Authentication Flow
1. Visit https://gamezoneismo.vercel.app/auth/login
2. Try to login with a test account
3. Check browser console for errors
4. Verify successful redirect to dashboard

### 2. Test Registration Flow
1. Visit https://gamezoneismo.vercel.app/auth/register
2. Try to register a new account
3. Check that profile image upload works
4. Verify successful login after registration

### 3. Test Player Features
1. Visit https://gamezoneismo.vercel.app/player/dashboard
2. Check that dashboard loads correctly
3. Try to claim daily bonus
4. Test reward redemption

### 4. Test Admin Features
1. Login as admin user
2. Visit https://gamezoneismo.vercel.app/admin/dashboard
3. Verify admin statistics load
4. Test admin management features

## 🎉 SUCCESS CRITERIA

After updating backend:
- ✅ Login/Register works from Vercel frontend
- ✅ Player dashboard displays real data
- ✅ Admin panel functions correctly
- ✅ No CORS errors in browser console
- ✅ Profile image uploads work
- ✅ All API calls return successful status codes

## ⚠️ IMPORTANT NOTES

1. **Backup First**: Always backup original files before making changes
2. **Test Incrementally**: Update one file at a time and test
3. **Check File Permissions**: PHP files should be 644, directories 755
4. **No Frontend Changes Needed**: Frontend is already configured correctly
5. **Environment Variables**: Will be set in Vercel after backend is working

---

**Status**: ✅ FRONTEND READY, BACKEND NEEDS CORS UPDATES  
**Next Step**: Update backend files with CORS headers and upload via FileZilla  
**Frontend URL**: https://gamezoneismo.vercel.app/  
**API Base**: http://ismo.gamer.gd/api/

Last updated: 2025-10-25 09:00 PM