# 📤 FileZilla Upload Guide - Fix CORS Headers

## 🎯 Current Issue
Your backend on `https://ismo.gamer.gd` is missing CORS headers. The updated files need to be uploaded via FileZilla.

## 🔑 InfinityFree FTP Credentials

1. **Login to InfinityFree Control Panel**
   - URL: https://controlpanel.infinityfree.com/
   
2. **Get FTP Credentials**
   - Go to: **Account Settings** → **FTP Details**
   - Note down:
     - **FTP Hostname**: (usually `ftpupload.net`)
     - **FTP Username**: (your account username)
     - **FTP Password**: (set/view in control panel)

## 📁 Files to Upload (Priority Order)

### ⚡ CRITICAL FILES (Upload First)

These files contain the CORS configuration:

```
Local Path                                          → Remote Path
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
backend_infinityfree/api/.htaccess                  → /htdocs/api/.htaccess
backend_infinityfree/api/config.php                 → /htdocs/api/config.php
backend_infinityfree/api/utils.php                  → /htdocs/api/utils.php
```

### 🔐 SECURITY FILES (Upload Second)

```
backend_infinityfree/api/middleware/security.php    → /htdocs/api/middleware/security.php
backend_infinityfree/api/middleware/error_handler.php → /htdocs/api/middleware/error_handler.php
backend_infinityfree/api/middleware/logger.php      → /htdocs/api/middleware/logger.php
backend_infinityfree/api/middleware/cache.php       → /htdocs/api/middleware/cache.php
```

### 🔑 AUTHENTICATION FILES (Upload Third)

```
backend_infinityfree/api/auth/login.php             → /htdocs/api/auth/login.php
backend_infinityfree/api/auth/register.php          → /htdocs/api/auth/register.php
backend_infinityfree/api/auth/logout.php            → /htdocs/api/auth/logout.php
backend_infinityfree/api/auth/verify-session.php    → /htdocs/api/auth/verify-session.php
```

### 🎮 ALL OTHER FILES (Optional - Upload if time permits)

Upload the entire `backend_infinityfree/api/` directory to `/htdocs/api/`

## 🚀 Step-by-Step Upload Process

### Step 1: Connect to InfinityFree via FileZilla

1. **Open FileZilla**

2. **Fill Connection Details** (top bar):
   ```
   Host: ftpupload.net (or your FTP hostname)
   Username: [your FTP username]
   Password: [your FTP password]
   Port: 21
   ```

3. **Click "Quickconnect"**

4. **Accept Certificate** if prompted

### Step 2: Navigate to Correct Directory

**Left Panel** (Local - Your Computer):
```
Navigate to: C:\xampp\htdocs\projet ismo\backend_infinityfree\api
```

**Right Panel** (Remote - InfinityFree Server):
```
Navigate to: /htdocs/api
```

Should look like:
```
/htdocs/
  └── api/
      ├── .htaccess          ← Upload here
      ├── config.php         ← Upload here
      ├── utils.php          ← Upload here
      ├── auth/
      │   ├── login.php      ← Upload here
      │   └── ...
      ├── middleware/
      │   ├── security.php   ← Upload here
      │   └── ...
      └── ...
```

### Step 3: Upload Critical Files

**IMPORTANT**: Upload files one by one to avoid issues.

1. **Right-click** on local file (left panel)
2. **Select**: "Upload"
3. **Wait** for transfer to complete (green checkmark)
4. **Verify** file appears in right panel

**Upload in this exact order:**

1. ✅ `.htaccess`
2. ✅ `config.php`
3. ✅ `utils.php`
4. ✅ `middleware/security.php`
5. ✅ `auth/login.php`

### Step 4: Test CORS Headers

After uploading, **test in browser**:

**Open this URL:**
```
https://ismo.gamer.gd/api/test.php
```

**Should return JSON with CORS headers visible in Network tab:**
```json
{
  "status": "ok",
  "timestamp": "..."
}
```

**Check Response Headers** (F12 → Network → test.php → Headers):
```
Access-Control-Allow-Origin: https://gamezoneismo.vercel.app
Access-Control-Allow-Credentials: true
Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS
```

### Step 5: Test Login from Vercel App

1. **Open**: https://gamezoneismo.vercel.app

2. **Open Console** (F12)

3. **Try Login** with test account

4. **Should see in Console**:
   ```
   ✅ Login successful
   ```

5. **No CORS errors!**

## 🔧 FileZilla Settings (If Upload Fails)

### Fix: "Connection Timeout"

**Edit → Settings → Connection**:
```
Timeout: 60 seconds
Number of retries: 5
```

### Fix: "Transfer Failed"

**Transfer → Transfer Type → Binary**

Or change in:
**Edit → Settings → Transfers → File Types**:
```
Transfer mode: Binary
```

### Fix: "Permission Denied"

**Right-click file in remote panel → File permissions**:
```
.htaccess: 644
config.php: 644
*.php: 644
directories: 755
```

## ✅ Verification Checklist

After upload, verify:

- [ ] `.htaccess` uploaded successfully
- [ ] `config.php` uploaded successfully
- [ ] `utils.php` uploaded successfully
- [ ] `middleware/security.php` uploaded successfully
- [ ] `auth/login.php` uploaded successfully
- [ ] Test URL works: `https://ismo.gamer.gd/api/test.php`
- [ ] CORS headers visible in Network tab
- [ ] Login works on Vercel app
- [ ] No CORS errors in console

## 🚨 Common Issues

### Issue: "CORS header still missing"

**Solution**: Clear browser cache
```
Ctrl + Shift + Delete → Clear cached images and files
```

**Or**: Hard refresh
```
Ctrl + Shift + R
```

### Issue: "500 Internal Server Error"

**Cause**: Syntax error in uploaded file

**Solution**: Check error logs
```
InfinityFree Control Panel → Error Logs
```

### Issue: "403 Forbidden"

**Cause**: .htaccess blocking access

**Solution**: Check file permissions (should be 644)

## 📝 Quick Reference

**FTP Connection:**
```
Host: ftpupload.net
Port: 21
Protocol: FTP
Encryption: Use if available
```

**Remote Directory:**
```
/htdocs/api/
```

**Critical Files:**
```
.htaccess
config.php
utils.php
middleware/security.php
auth/login.php
```

---

## 🎯 After Upload

Once files are uploaded:

1. **Clear browser cache**
2. **Hard refresh** Vercel app (Ctrl + Shift + R)
3. **Test login** - should work!
4. **Check console** - no CORS errors!

**Your app will be fully functional! 🚀**
