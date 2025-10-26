# 🔍 Frontend Debugging - Login Still Fails

## 📋 Current Status

**Backend:**
- ✅ CORS headers correct on server
- ✅ `config.php` working
- ✅ All files uploaded to InfinityFree

**Frontend:**
- ❓ Still getting CORS errors
- ❓ API calls not working

---

## 🧪 Critical Tests to Run

### Test 1: Check Frontend Environment Variable

**Open Vercel app:** https://gamezoneismo.vercel.app

**Open Console (F12)** and run:

```javascript
console.log('API_BASE:', import.meta.env.NEXT_PUBLIC_API_BASE);
```

**Expected:**
```
API_BASE: https://ismo.gamer.gd/api
```

**If undefined or wrong:**
- Vercel environment variable not set correctly
- Need to update in Vercel dashboard

---

### Test 2: Test Direct API Call from Console

**In Console (F12) at https://gamezoneismo.vercel.app:**

```javascript
fetch('https://ismo.gamer.gd/api/test.php', {
  credentials: 'include'
})
.then(r => r.json())
.then(d => console.log('✅ Success:', d))
.catch(e => console.error('❌ Error:', e));
```

**Expected:**
```
✅ Success: {method: "GET", content_type: "NONE", ...}
```

**If CORS error:**
- Check Response Headers in Network tab
- Must have: `Access-Control-Allow-Origin: https://gamezoneismo.vercel.app`

---

### Test 3: Test Login Call from Console

```javascript
fetch('https://ismo.gamer.gd/api/auth/login.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  credentials: 'include',
  body: JSON.stringify({
    email: 'admin@gmail.com',
    password: 'demo123'
  })
})
.then(r => r.json())
.then(d => console.log('✅ Login Success:', d))
.catch(e => console.error('❌ Login Error:', e));
```

**Expected:**
```
✅ Login Success: {message: "Connexion réussie", user: {...}}
```

---

## 🔧 Possible Issues & Fixes

### Issue 1: Vercel Environment Variable Not Set

**Problem:** `NEXT_PUBLIC_API_BASE` not configured in Vercel

**Fix:**
1. Go to: https://vercel.com/jeho05/gamezoneismo/settings/environment-variables
2. Add variable:
   ```
   Name:  NEXT_PUBLIC_API_BASE
   Value: https://ismo.gamer.gd/api
   ```
3. **Redeploy** the app (required for env vars to take effect)

---

### Issue 2: Old Deployment Cached

**Problem:** Vercel serving old build without env vars

**Fix:**
1. Go to: https://vercel.com/jeho05/gamezoneismo/deployments
2. Click "..." on latest deployment
3. Select "Redeploy"
4. Wait for deployment to complete
5. Test again

---

### Issue 3: Browser Cache

**Problem:** Browser caching old API calls

**Fix:**
1. **Hard refresh:** `Ctrl + Shift + R`
2. **Clear cache:** `Ctrl + Shift + Delete`
   - Select "Cached images and files"
   - Click "Clear data"
3. **Reload page**
4. **Try login again**

---

### Issue 4: CORS Headers Not Matching Origin

**Problem:** Backend sending wrong origin in header

**Fix:**

Check in Network tab (F12 → Network → login.php → Headers):

**Response Headers should show:**
```
Access-Control-Allow-Origin: https://gamezoneismo.vercel.app
```

**If showing:**
```
Access-Control-Allow-Origin: http://localhost:4000
```

**Then backend is still using fallback!**

**Solution:** 
- Backend config.php needs the request to have `HTTP_ORIGIN` header
- Vercel should send this automatically
- Check if request has `Origin: https://gamezoneismo.vercel.app` in Request Headers

---

## 🎯 Step-by-Step Debug Process

### Step 1: Open Vercel App
```
https://gamezoneismo.vercel.app
```

### Step 2: Open DevTools (F12)

### Step 3: Go to Console Tab

### Step 4: Run This Complete Test:

```javascript
console.log('=== FRONTEND DEBUG ===');
console.log('1. Current URL:', window.location.href);
console.log('2. Origin:', window.location.origin);

// Test if env var is loaded
if (typeof import.meta !== 'undefined') {
  console.log('3. API_BASE env:', import.meta.env.NEXT_PUBLIC_API_BASE);
} else {
  console.log('3. import.meta not available (might be in built version)');
}

// Test API call with full logging
console.log('\\n4. Testing API call...');
fetch('https://ismo.gamer.gd/api/test.php', {
  credentials: 'include'
})
.then(response => {
  console.log('   - Status:', response.status);
  console.log('   - Headers:', [...response.headers.entries()]);
  return response.json();
})
.then(data => {
  console.log('   - Data:', data);
  console.log('\\n✅ API WORKING!');
})
.catch(error => {
  console.error('   - Error:', error);
  console.log('\\n❌ API FAILED!');
});

// Test login call
console.log('\\n5. Testing LOGIN call...');
fetch('https://ismo.gamer.gd/api/auth/login.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  credentials: 'include',
  body: JSON.stringify({
    email: 'admin@gmail.com',
    password: 'demo123'
  })
})
.then(response => {
  console.log('   - Status:', response.status);
  return response.json();
})
.then(data => {
  console.log('   - Data:', data);
  console.log('\\n✅ LOGIN WORKING!');
})
.catch(error => {
  console.error('   - Error:', error);
  console.log('\\n❌ LOGIN FAILED!');
});
```

### Step 5: Copy ALL Console Output

Send me the complete output from the console!

---

## 📝 What to Check in Network Tab

**Go to:** Network tab in DevTools

**Refresh** page and try login

**Click on:** `login.php` request

**Check:**

**Request Headers:**
```
✅ Origin: https://gamezoneismo.vercel.app  ← MUST be present!
✅ Content-Type: application/json
✅ (other headers...)
```

**Response Headers:**
```
✅ Access-Control-Allow-Origin: https://gamezoneismo.vercel.app  ← MUST match Origin!
✅ Access-Control-Allow-Credentials: true
✅ Content-Type: application/json
```

**If Response Headers missing or wrong origin → Backend issue**
**If Request Headers missing Origin → Frontend issue**

---

## 🚨 Common Frontend Issues

### 1. Environment Variable Not Loaded

**Symptom:** API calls to wrong URL or `undefined/api/auth/login.php`

**Fix:** Update Vercel environment variables + redeploy

### 2. Build Caching Issue

**Symptom:** Old code running on Vercel

**Fix:** Redeploy from Vercel dashboard

### 3. CORS Preflight Failing

**Symptom:** Network tab shows OPTIONS request failing

**Fix:** Check backend handles OPTIONS requests (already done in config.php)

### 4. Credentials Not Sent

**Symptom:** Cookies not sent with request

**Fix:** Ensure `credentials: 'include'` in fetch (already done in code)

---

## ✅ Next Actions

**Please run the debug script above and send me:**

1. **Console output** (all lines)
2. **Network tab screenshot** showing:
   - Request URL
   - Request Headers (especially `Origin`)
   - Response Headers (especially `Access-Control-Allow-Origin`)
3. **Exact error message** from console

This will tell me exactly what's wrong! 🔍
