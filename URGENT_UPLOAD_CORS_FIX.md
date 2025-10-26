# 🚨 URGENT: Upload CORS Fix Files

## Problem Identified
InfinityFree does NOT have `mod_headers` enabled, so `.htaccess` cannot set CORS headers.

## Solution
Created `cors.php` that MUST be loaded FIRST in every PHP file.

---

## Files to Upload (IN ORDER)

### 1. cors.php (NEW FILE - CRITICAL)
```
Local:  C:\xampp\htdocs\projet ismo\backend_infinityfree\api\cors.php
Remote: /htdocs/api/cors.php
```

### 2. cors-test-simple.php (UPDATED)
```
Local:  C:\xampp\htdocs\projet ismo\backend_infinityfree\api\cors-test-simple.php
Remote: /htdocs/api/cors-test-simple.php
```

### 3. config.php (UPDATED)
```
Local:  C:\xampp\htdocs\projet ismo\backend_infinityfree\api\config.php
Remote: /htdocs/api/config.php
```

### 4. login.php (UPDATED)
```
Local:  C:\xampp\htdocs\projet ismo\backend_infinityfree\api\auth\login.php
Remote: /htdocs/api/auth/login.php
```

---

## Upload Steps

### 1. Open FileZilla

### 2. Connect
- Host: `ftpupload.net`
- Username: `if0_40238088`
- Password: `OTnlRESWse7lVB`
- Port: `21`

### 3. Upload cors.php FIRST
- Navigate to `/htdocs/api/`
- Upload `cors.php`
- **CRITICAL: This file must exist before others load it!**

### 4. Upload Other Files
- Upload `cors-test-simple.php` to `/htdocs/api/`
- Upload `config.php` to `/htdocs/api/`
- Upload `login.php` to `/htdocs/api/auth/`

### 5. Verify Upload
Check file modification dates on server - should be current time.

---

## Test Immediately After Upload

### Test 1: CORS Test
In browser console (F12) on https://gamezoneismo.vercel.app:

```javascript
fetch('https://ismo.gamer.gd/api/cors-test-simple.php')
  .then(r => r.json())
  .then(d => console.log('✅ CORS WORKS:', d))
  .catch(e => console.error('❌ Failed:', e))
```

**Expected:** `✅ CORS WORKS: {status: "CORS Working", ...}`

### Test 2: Login Test
```javascript
fetch('https://ismo.gamer.gd/api/auth/login.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  credentials: 'include',
  body: JSON.stringify({
    email: 'admin@gmail.com',
    password: 'demo123'
  })
})
.then(r => r.json())
.then(d => console.log('✅ LOGIN SUCCESS:', d))
.catch(e => console.error('❌ Login failed:', e))
```

**Expected:** `✅ LOGIN SUCCESS: {message: "Connexion réussie", user: {...}}`

---

## If Still Failing

1. Check FileZilla transfer log - ensure all files uploaded successfully
2. Check file sizes on server match local files
3. Check file modification timestamps are current
4. Clear browser cache completely (Ctrl+Shift+Delete)
5. Hard refresh (Ctrl+Shift+R)

---

## Why This Works

**Previous approach:** Tried to set headers in `.htaccess` (mod_headers) → InfinityFree doesn't support it

**New approach:** `cors.php` is included FIRST in every PHP file using `require_once`, ensuring CORS headers are sent before ANY other code executes.

**Key files:**
- `cors.php` - Sets CORS headers and handles OPTIONS
- `config.php` - Includes cors.php at the top
- `login.php` - Includes cors.php at the top
- All other endpoints will need the same update

---

**UPLOAD THESE 4 FILES NOW IN THE ORDER LISTED ABOVE!** 🚀
