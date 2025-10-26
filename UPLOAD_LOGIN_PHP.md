# Upload login.php to InfinityFree

## File to Upload

**Local:**  
```
C:\xampp\htdocs\projet ismo\backend_infinityfree\api\auth\login.php
```

**Remote:**  
```
/htdocs/api/auth/login.php
```

## Steps

### 1. Open FileZilla

### 2. Connect to Server
- Host: `ftpupload.net`
- Username: `if0_40238088`
- Password: `OTnlRESWse7lVB`
- Port: `21`

### 3. Navigate
- **Left Panel (Local):** `C:\xampp\htdocs\projet ismo\backend_infinityfree\api\auth`
- **Right Panel (Remote):** `/htdocs/api/auth`

### 4. Upload
- Right-click `login.php` in left panel
- Select "Upload"
- Confirm overwrite when asked
- Wait for green checkmark ✅

### 5. Verify
Open: https://ismo.gamer.gd/api/test-login-headers.php

Should show CORS headers in first positions.

---

**After Vercel finishes deploying (1-2 minutes), login will work!**
