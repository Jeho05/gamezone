# 🔧 Guide de Dépannage - Images et Vidéos

## 🚀 Démarrage Rapide

### **Option 1: Script Automatique (RECOMMANDÉ)**

```powershell
.\REDEMARRER_SERVEUR.ps1
```

Ce script va:
1. ✅ Vérifier qu'Apache est démarré
2. ✅ Arrêter les anciens processus Node
3. ✅ Démarrer le serveur Vite avec la nouvelle config
4. ✅ Afficher les URLs de test

### **Option 2: Manuel**

```powershell
# 1. Aller dans le dossier web
cd "createxyz-project\_\apps\web"

# 2. Arrêter le serveur actuel (Ctrl+C si en cours)

# 3. Redémarrer
npm run dev
```

---

## 🧪 Tests à Effectuer

### **Test 1: Page de Test Dédiée**

Une fois le serveur redémarré:

```
http://localhost:4000/test-images.html
```

**Ce que vous devriez voir:**
- ✅ 3 vidéos avec bordures roses (peuvent être lues)
- ✅ 4 photos admin avec bordures violettes
- ✅ 6 objets gaming avec bordures violettes
- ✅ Status verts: "X/X vidéos OK", "X/X images OK"

**Si vous voyez des ❌ rouges:**
→ Passez à la section "Problèmes Courants" ci-dessous

---

### **Test 2: Page d'Accueil**

```
http://localhost:4000/
```

**Ce que vous devriez voir:**
- ✅ Vidéo animée en arrière-plan (néon cyberpunk)
- ✅ Objets gaming qui flottent (console, personnages)
- ✅ Section "À propos de l'Admin" avec 4 photos
- ✅ Animations fluides

---

### **Test 3: Pages Auth**

**Login:**
```
http://localhost:4000/auth/login
```
→ Vidéo arcade + objets flottants

**Register:**
```
http://localhost:4000/auth/register
```
→ Vidéo animée + objets gaming

---

## 🐛 Problèmes Courants

### **❌ Problème 1: "404 Not Found" pour les images**

**Symptôme:**
- Console navigateur (F12): `GET http://localhost:4000/images/... 404 (Not Found)`
- Images avec bordures rouges sur test-images.html

**Causes possibles:**

#### **A) Apache n'est pas démarré**

**Vérification:**
```powershell
curl http://localhost/projet%20ismo/images/objet/Goku-Blue-PNG-Photo.png
```

**Si erreur "Connection refused":**
1. Ouvrir XAMPP Control Panel
2. Démarrer Apache
3. Vérifier que le port 80 n'est pas utilisé par un autre programme
4. Redémarrer le serveur Vite

---

#### **B) Les fichiers n'existent pas**

**Vérification:**
```powershell
# Vérifier que le dossier images existe
Test-Path "images\video"
Test-Path "images\objet"
Test-Path "images\gaming tof\Boss"
```

**Si False:**
Les fichiers ne sont pas au bon endroit. Ils doivent être dans:
```
c:\xampp\htdocs\projet ismo\images\
```

---

#### **C) Permissions insuffisantes**

**Windows:**
- Clic droit sur dossier `images`
- Propriétés → Sécurité
- Vérifier que "Utilisateurs" a la permission "Lecture"

---

### **❌ Problème 2: Vidéos ne se chargent pas (mais images OK)**

**Symptôme:**
- Photos OK ✅
- Objets OK ✅
- Vidéos ❌

**Cause probable:** Fichiers vidéo trop lourds ou corrompus

**Solution:**
```powershell
# Tester directement via Apache
start http://localhost/projet%20ismo/images/video/Cyber_Arcade_Neon_Ember.mp4
```

Si la vidéo ne se lit pas dans le navigateur → fichier corrompu ou codec non supporté.

**Formats supportés:**
- ✅ MP4 (H.264)
- ✅ WebM
- ❌ AVI, MOV (non supportés par HTML5)

---

### **❌ Problème 3: "Failed to fetch" / CORS Error**

**Symptôme:**
```
Access to video at '...' from origin 'http://localhost:4000' has been blocked by CORS policy
```

**Solution:**
Vérifier que le fichier `.htaccess` dans `projet ismo` contient:

```apache
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "*"
    Header set Access-Control-Allow-Methods "GET, POST, OPTIONS"
    Header set Access-Control-Allow-Headers "Content-Type"
</IfModule>
```

Si absent, créer:
```powershell
# Créer .htaccess
@"
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "*"
    Header set Access-Control-Allow-Methods "GET, POST, OPTIONS"
    Header set Access-Control-Allow-Headers "Content-Type"
</IfModule>
"@ | Out-File -FilePath ".htaccess" -Encoding ASCII
```

Puis redémarrer Apache.

---

### **❌ Problème 4: Images s'affichent mais sont cassées**

**Symptôme:**
- Icône "image cassée" 🖼️
- Ou carré blanc avec bordure

**Causes:**
1. **Fichier corrompu:** Ouvrir l'image directement avec un viewer
2. **Extension incorrecte:** Vérifier que `.png` est bien PNG, `.jpg` est JPEG
3. **Caractères spéciaux dans nom:** Éviter espaces, accents, caractères spéciaux

**Solution:**
Renommer si nécessaire:
```powershell
# Exemple: enlever les espaces
Rename-Item "gaming tof" "gaming-tof"
```

Puis mettre à jour les chemins dans le code.

---

### **❌ Problème 5: Proxy ne fonctionne pas**

**Symptôme:**
- Console Vite ne montre AUCUN log "Sending Request for Image..."
- 404 immédiat sans tentative de proxy

**Solution A: Vérifier vite.config.ts**

Ouvrir `createxyz-project\_\apps\web\vite.config.ts` et chercher:
```typescript
'/images': {
  target: 'http://localhost',
  changeOrigin: true,
  secure: false,
  rewrite: (path) => path.replace(/^\/images/, '/projet%20ismo/images'),
  ...
}
```

Si absent → Le fichier n'a pas été sauvegardé correctement.

**Solution B: Forcer le rechargement de la config**

```powershell
# Supprimer le cache Vite
Remove-Item "createxyz-project\_\apps\web\.react-router" -Recurse -Force
Remove-Item "createxyz-project\_\apps\web\node_modules\.vite" -Recurse -Force -ErrorAction SilentlyContinue

# Redémarrer
npm run dev
```

---

## 🔍 Logs de Débogage

### **Console Navigateur (F12)**

**Ce que vous devez voir:**
```javascript
🎮 Test des assets GameZone
📹 Vidéos: Array(3) [...]
👤 Photos admin: Array(4) [...]
🎮 Objets gaming: Array(6) [...]
```

**Ce que vous NE devez PAS voir:**
```
GET http://localhost:4000/images/... 404 (Not Found)
net::ERR_CONNECTION_REFUSED
CORS policy error
```

---

### **Console Terminal (Serveur Vite)**

**Ce que vous devez voir:**
```
Sending Request for Image: GET /images/video/Cyber_Arcade_Neon_Ember.mp4
Received Image Response: 200 /images/video/Cyber_Arcade_Neon_Ember.mp4
```

**Ce que vous NE devez PAS voir:**
```
images proxy error
404 Not Found
500 Internal Server Error
```

---

## 📋 Checklist de Vérification

Avant de demander de l'aide, vérifiez:

- [ ] Apache est démarré (XAMPP)
- [ ] Le dossier `images` existe dans `c:\xampp\htdocs\projet ismo\`
- [ ] Les fichiers vidéos/images sont présents
- [ ] Le serveur Vite a été redémarré APRÈS la modification de vite.config.ts
- [ ] Pas d'erreurs dans la console navigateur (F12)
- [ ] Test avec test-images.html effectué
- [ ] Cache navigateur vidé (Ctrl+F5)

---

## 🛠️ Solution de Secours: Copie Locale

Si le proxy refuse obstinément de fonctionner, copiez les images directement:

```powershell
# Copier toutes les images dans public
xcopy "images" "createxyz-project\_\apps\web\public\images" /E /I /Y
```

**Avantages:**
- ✅ Fonctionne immédiatement
- ✅ Pas de dépendance Apache
- ✅ Chargement plus rapide

**Inconvénients:**
- ❌ Duplication ~45 MB
- ❌ Dois synchroniser si images changent

---

## 📞 Support

Si rien ne fonctionne:

1. **Faire un screenshot de:**
   - test-images.html (avec status erreurs)
   - Console navigateur F12 (onglet Console)
   - Console terminal (logs Vite)

2. **Noter:**
   - Version Windows
   - XAMPP démarré? (oui/non)
   - Quelle étape échoue?

3. **Vérifier les bases:**
   ```powershell
   # Test Apache
   curl http://localhost/projet%20ismo/images/objet/Goku-Blue-PNG-Photo.png
   
   # Test fichier existe
   Test-Path "images\objet\Goku-Blue-PNG-Photo.png"
   ```

---

## ✅ Si Tout Fonctionne

Une fois les images visibles:

1. ✅ **Tester les 3 pages principales:**
   - Home: http://localhost:4000/
   - Login: http://localhost:4000/auth/login
   - Register: http://localhost:4000/auth/register

2. ✅ **Vérifier les effets:**
   - Vidéos en background animées
   - Objets qui flottent
   - Photos admin dans la section dédiée
   - Objets parallaxe qui suivent la souris

3. ✅ **Continuer la modernisation:**
   - Dashboard Player
   - Shop
   - Leaderboard
   - Etc.

---

**Dernière mise à jour:** 22 Octobre 2025  
**Version:** 1.0  
**Status:** Configuration proxy appliquée ✅
