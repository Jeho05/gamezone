# 🚀 GUIDE D'INSTALLATION - SYSTÈME D'IMAGES BASE64

## 📋 CE QUI A ÉTÉ CRÉÉ

### 1. **Script d'installation automatique** ✅
`api/admin/setup_images_system.php`
- Crée automatiquement les tables `game_images` et `user_avatars`
- Vérifie que tout est correct
- Accessible en un clic

### 2. **Système d'upload amélioré** ✅

**Pour les jeux :**
- `api/admin/upload_image.php` → Upload images de jeux (déjà existant, modifié)
- `api/admin/get_image.php` → Récupération images (déjà existant)

**Pour les avatars :**
- `api/users/avatar_base64.php` → Upload avatars (NOUVEAU)
- `api/users/get_avatar.php` → Récupération avatars (NOUVEAU)

## 🎯 INSTALLATION EN 3 ÉTAPES

### Étape 1: Accéder au script d'installation

**En production (Railway) :**
```
https://overflowing-fulfillment-production-36c6.up.railway.app/api/admin/setup_images_system.php
```

**En local (XAMPP) :**
```
http://localhost/projet%20ismo/api/admin/setup_images_system.php
```

**Login requis :** admin / admin123

### Étape 2: Vérifier le résultat

Vous devriez voir un JSON comme :
```json
{
  "success": true,
  "message": "Installation du système d'images BASE64 terminée",
  "results": {
    "game_images": {
      "status": "success",
      "message": "Table game_images créée ou déjà existante"
    },
    "user_avatars": {
      "status": "success",
      "message": "Table user_avatars créée ou déjà existante"
    }
  },
  "verification": {
    "game_images": {
      "exists": true,
      "count": 0
    },
    "user_avatars": {
      "exists": true,
      "count": 0
    }
  }
}
```

### Étape 3: Mettre à jour le frontend pour les avatars

Le frontend doit utiliser le nouveau endpoint pour les avatars.

**Fichier à modifier :** `gamezone-frontend-clean/src/app/player/profile/page.jsx`

**Remplacer :**
```javascript
const res = await fetch(`${API_BASE}/users/avatar.php`, {
```

**Par :**
```javascript
const res = await fetch(`${API_BASE}/users/avatar_base64.php`, {
```

## 📊 ARCHITECTURE DU SYSTÈME

### Table `game_images`
```sql
CREATE TABLE game_images (
  id INT PRIMARY KEY AUTO_INCREMENT,
  filename VARCHAR(255),
  data LONGTEXT,              -- Image en base64
  mime_type VARCHAR(50),
  created_at DATETIME
);
```

### Table `user_avatars`
```sql
CREATE TABLE user_avatars (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT UNIQUE,         -- Un seul avatar par user
  filename VARCHAR(255),
  data LONGTEXT,              -- Avatar en base64
  mime_type VARCHAR(50),
  created_at DATETIME,
  FOREIGN KEY (user_id) REFERENCES users(id)
);
```

## 🔄 FLUX D'UPLOAD

### Images de jeux (Admin)
```
1. Admin upload image via /admin/shop
2. POST /api/admin/upload_image.php
3. Image → base64 → MySQL game_images
4. Retourne URL: /api/admin/get_image.php?id=X
5. URL stockée dans games.image_url
```

### Avatars (Player)
```
1. Player upload avatar via /player/profile
2. POST /api/users/avatar_base64.php
3. Avatar → base64 → MySQL user_avatars
4. Retourne URL: /api/users/get_avatar.php?id=X
5. URL stockée dans users.avatar_url
```

## ✅ AVANTAGES

1. **Persistance** : Les images survivent aux redéploiements Railway
2. **Simplicité** : Pas de configuration filesystem
3. **Portable** : Fonctionne partout (Railway, Vercel, local)
4. **Backup** : Images sauvegardées avec la BDD
5. **Optimisation** : Images automatiquement compressées
   - Jeux : max 1200px width
   - Avatars : 400x400px (carré, centré)

## 🧪 TESTS

### Test 1: Installation
```bash
curl -u admin:admin123 https://your-domain.com/api/admin/setup_images_system.php
```

### Test 2: Upload image de jeu
1. Aller sur https://gamezoneismo.vercel.app/admin
2. Jeux > Nouveau Jeu
3. Uploader une image
4. ✅ L'image doit s'afficher

### Test 3: Upload avatar
1. Aller sur https://gamezoneismo.vercel.app/player/profile
2. Cliquer sur l'avatar
3. Uploader une photo
4. ✅ L'avatar doit s'afficher

## 🔧 LIMITES ET CONFIGURATION

### Limites actuelles
- **Images de jeux :** Max 5MB
- **Avatars :** Max 2MB
- **Format :** JPG, PNG, GIF, WEBP

### Modifier les limites

**Dans `upload_image.php` (ligne 38) :**
```php
$maxSize = 5 * 1024 * 1024; // Changer ici
```

**Dans `avatar_base64.php` (ligne 34) :**
```php
$maxSize = 2 * 1024 * 1024; // Changer ici
```

## 🚨 DÉPANNAGE

### Erreur: "Table doesn't exist"
**Solution :** Réexécuter le script d'installation

### Erreur: "Unauthorized" ou 401
**Solution :** Se reconnecter en admin

### Image ne s'affiche pas
**Solution :**
1. Vérifier que l'URL est correcte
2. Tester l'URL directement dans le navigateur
3. Vérifier les logs Railway

### Performance lente
**Solution :**
- Les images sont cachées 1 an par le navigateur
- Première charge peut être lente
- Envisager Cloudinary pour de meilleures performances

## 📝 MIGRATION DES ANCIENNES IMAGES

Si vous avez des images existantes dans `/uploads/`, vous pouvez :

**Option 1 :** Les laisser (elles fonctionneront jusqu'au prochain redéploiement)

**Option 2 :** Les re-uploader via l'interface admin

**Option 3 :** Script de migration automatique (à créer si besoin)

## 🎉 C'EST PRÊT !

Après avoir exécuté `setup_images_system.php`, votre système est opérationnel :
- ✅ Tables créées
- ✅ Scripts d'upload prêts
- ✅ Scripts de récupération prêts
- ✅ Optimisation d'images activée
- ✅ Persistance garantie

**Il ne reste plus qu'à tester !**
