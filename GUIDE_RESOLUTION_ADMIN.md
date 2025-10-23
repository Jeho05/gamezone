# 🔧 Guide de Résolution - Problèmes Admin

## 📋 Diagnostic Rapide

### Étape 1: Lancer le diagnostic automatique

```powershell
.\LANCER_DIAGNOSTIC_ADMIN.ps1
```

Cela ouvrira une page web de diagnostic qui testera automatiquement **tous les endpoints admin**.

---

## 🔍 Problèmes Courants et Solutions

### ❌ Problème 1: "Erreur 401 Unauthorized"

**Symptôme**: Les pages admin affichent "Unauthorized" ou redirigent vers login

**Causes possibles**:
- ✅ Session expirée
- ✅ Non connecté avec un compte admin
- ✅ Cookies bloqués

**Solutions**:

1. **Vérifier la session**:
   ```javascript
   // Dans la console navigateur
   fetch('http://localhost/projet%20ismo/api/auth/check.php', {
     credentials: 'include'
   }).then(r => r.json()).then(console.log)
   ```

2. **Se reconnecter**:
   - Aller sur `/auth/login`
   - Utiliser: `admin@gamezone.com` / `Admin123!`

3. **Vérifier les cookies**:
   - F12 → Application → Cookies
   - Doit avoir `PHPSESSID` pour `localhost`

---

### ❌ Problème 2: "Erreur 403 Forbidden"

**Symptôme**: Connecté mais accès refusé

**Cause**: Utilisateur non-admin essaie d'accéder aux pages admin

**Solution**:

Vérifier le rôle de l'utilisateur dans la base de données:

```sql
-- Vérifier le rôle
SELECT id, username, email, role FROM users WHERE email = 'admin@gamezone.com';

-- Si nécessaire, mettre à jour en admin
UPDATE users SET role = 'admin' WHERE email = 'admin@gamezone.com';
```

---

### ❌ Problème 3: "Erreur 500 Internal Server Error"

**Symptôme**: Erreur serveur sur certains endpoints

**Causes possibles**:
- ✅ Erreur PHP dans le backend
- ✅ Base de données inaccessible
- ✅ Table manquante

**Solutions**:

1. **Vérifier les logs Apache**:
   ```
   C:\xampp\apache\logs\error.log
   ```

2. **Vérifier les logs API**:
   ```
   C:\xampp\htdocs\projet ismo\logs\api_[date].log
   ```

3. **Tester la connexion DB**:
   ```php
   // Aller sur: http://localhost/projet%20ismo/api/admin/test_direct.php
   ```

4. **Vérifier les tables**:
   ```sql
   SHOW TABLES FROM gamezone;
   ```

---

### ❌ Problème 4: "NetworkError when attempting to fetch"

**Symptôme**: Erreur réseau lors des appels API

**Causes**:
- ✅ Apache non démarré
- ✅ Mauvaise URL d'API
- ✅ Problème CORS

**Solutions**:

1. **Vérifier Apache**:
   - Ouvrir XAMPP Control Panel
   - Apache doit être "Running" sur port 80

2. **Vérifier l'URL API**:
   ```javascript
   // Console navigateur
   console.log(API_BASE); // Doit afficher: http://localhost/projet%20ismo/api
   ```

3. **Test direct**:
   ```
   http://localhost/projet%20ismo/api/auth/check.php
   ```

---

### ❌ Problème 5: "Page blanche" ou "Loading infini"

**Symptôme**: La page admin ne charge jamais

**Causes**:
- ✅ Erreur JavaScript
- ✅ API ne répond pas
- ✅ Erreur de rendu React

**Solutions**:

1. **Ouvrir la console navigateur** (F12):
   - Regarder les erreurs rouges
   - Vérifier les requêtes réseau (onglet Network)

2. **Vérifier que le serveur React tourne**:
   ```powershell
   cd createxyz-project/_/apps/web
   npm run dev
   ```
   Doit être sur http://localhost:4000

3. **Vérifier les endpoints**:
   - Utiliser le diagnostic automatique
   - Tester manuellement dans Postman

---

## 🎯 Endpoints Admin Critiques

| Endpoint | Description | Méthode | Page concernée |
|----------|-------------|---------|----------------|
| `/admin/statistics.php` | Stats dashboard | GET | Dashboard |
| `/admin/games.php` | Gestion jeux | GET/POST/PUT/DELETE | Shop |
| `/admin/game_packages.php` | Packages | GET/POST/PUT/DELETE | Shop |
| `/admin/purchases.php` | Achats | GET/PATCH | Shop |
| `/admin/manage_session.php` | Sessions | GET/POST | Sessions |
| `/users/index.php` | Liste joueurs | GET | Players |
| `/points/adjust.php` | Ajuster points | POST | Players |

---

## 🔧 Vérifications Système

### 1. Apache & MySQL

```powershell
# Vérifier si Apache tourne
netstat -ano | findstr :80

# Vérifier si MySQL tourne
netstat -ano | findstr :3306
```

### 2. Base de données

```sql
-- Vérifier la connexion
SELECT 1;

-- Vérifier les tables critiques
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM games;
SELECT COUNT(*) FROM game_sessions;
```

### 3. Sessions PHP

```php
// Créer test-session.php dans api/
<?php
session_start();
echo "Session ID: " . session_id() . "\n";
echo "Session data: " . print_r($_SESSION, true);
?>
```

---

## 🛠️ Actions de Maintenance

### Nettoyer les sessions expirées

```sql
-- Terminer les sessions qui devraient être terminées
UPDATE game_sessions 
SET status = 'expired' 
WHERE status = 'active' 
  AND remaining_minutes <= 0;
```

### Régénérer les statistiques

```sql
-- Recalculer les points
UPDATE users u
SET points = (
    SELECT COALESCE(SUM(change_amount), 0)
    FROM points_transactions
    WHERE user_id = u.id
);
```

### Vider le cache

```powershell
# Supprimer les logs anciens
Remove-Item "logs\api_*.log" -Exclude "api_$(Get-Date -Format 'yyyy-MM-dd').log"
```

---

## 📊 Tests Manuels

### Test 1: Authentification Admin

```bash
curl -X POST http://localhost/projet%20ismo/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@gamezone.com","password":"Admin123!"}'
```

### Test 2: Récupération Stats

```bash
curl http://localhost/projet%20ismo/api/admin/statistics.php \
  -H "Cookie: PHPSESSID=your_session_id"
```

### Test 3: Liste Jeux

```bash
curl http://localhost/projet%20ismo/api/admin/games.php \
  -H "Cookie: PHPSESSID=your_session_id"
```

---

## 🚀 Redémarrage Complet

Si rien ne fonctionne, procédure de redémarrage complet:

### Backend (Apache/PHP)

```powershell
# Dans XAMPP Control Panel
1. Stop Apache
2. Stop MySQL
3. Attendre 5 secondes
4. Start MySQL
5. Start Apache
```

### Frontend (React)

```powershell
cd createxyz-project/_/apps/web

# Arrêter le serveur (Ctrl+C)
# Nettoyer
Remove-Item -Recurse -Force node_modules\.vite

# Relancer
npm run dev
```

### Base de données

```sql
-- Vérifier l'intégrité
REPAIR TABLE users;
REPAIR TABLE game_sessions;
OPTIMIZE TABLE points_transactions;
```

---

## 📞 Checklist de Diagnostic

- [ ] Apache est démarré (port 80)
- [ ] MySQL est démarré (port 3306)
- [ ] Base de données `gamezone` existe
- [ ] Compte admin existe avec role='admin'
- [ ] Session PHP fonctionne (test avec auth/check.php)
- [ ] CORS configuré correctement
- [ ] Frontend React tourne sur port 4000
- [ ] Pas d'erreurs dans la console navigateur
- [ ] Pas d'erreurs dans les logs Apache
- [ ] Cookies activés dans le navigateur

---

## 🔐 Compte Admin par Défaut

Si le compte admin n'existe pas:

```sql
-- Créer un compte admin
INSERT INTO users (username, email, password_hash, role, points, status, created_at, updated_at, join_date, last_active)
VALUES (
    'Admin',
    'admin@gamezone.com',
    '$2y$10$YourHashedPassword', -- Hash de 'Admin123!'
    'admin',
    0,
    'active',
    NOW(),
    NOW(),
    CURDATE(),
    NOW()
);

-- Ou mettre à jour un compte existant
UPDATE users 
SET role = 'admin' 
WHERE email = 'admin@gamezone.com';
```

Pour hasher le mot de passe, créer `hash-password.php`:

```php
<?php
echo password_hash('Admin123!', PASSWORD_DEFAULT);
?>
```

---

## 📝 Logs Utiles

### Activer les logs détaillés

Dans `api/config.php`, ajouter:

```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
```

### Voir les logs en temps réel

```powershell
Get-Content "logs\api_$(Get-Date -Format 'yyyy-MM-dd').log" -Wait -Tail 50
```

---

## ✅ Validation Finale

Une fois les corrections appliquées:

1. ✅ Lancez `LANCER_DIAGNOSTIC_ADMIN.ps1`
2. ✅ Connectez-vous avec le compte admin
3. ✅ Testez tous les endpoints
4. ✅ Vérifiez que 100% des tests passent
5. ✅ Naviguez dans toutes les pages admin
6. ✅ Testez une action (ex: créer un jeu, ajuster des points)

---

## 🆘 Contact Support

Si le problème persiste:

1. **Exportez le diagnostic** (bouton dans la page de diagnostic)
2. **Collectez les logs**:
   - `logs/api_[date].log`
   - `C:\xampp\apache\logs\error.log`
3. **Notez**:
   - Version PHP (dans phpinfo)
   - Version MySQL
   - Messages d'erreur exacts

---

**Date de création**: $(Get-Date -Format 'yyyy-MM-dd HH:mm')
**Version**: 1.0
