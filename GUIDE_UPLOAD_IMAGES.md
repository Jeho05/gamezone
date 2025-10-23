# 📸 Guide d'Upload d'Images

## 🎯 Fonctionnalité Ajoutée

Le formulaire d'ajout de jeu permet maintenant de **télécharger des images** directement depuis votre ordinateur avec la fonctionnalité **Drag & Drop** (glisser-déposer) !

---

## ✨ Fonctionnalités

### 1️⃣ **Drag & Drop**
- Glissez une image depuis votre ordinateur
- Déposez-la dans la zone prévue
- L'upload démarre automatiquement

### 2️⃣ **Sélection de Fichier**
- Cliquez sur la zone de drop
- Sélectionnez une image depuis votre explorateur
- L'image est uploadée automatiquement

### 3️⃣ **URL Manuelle (Alternative)**
- Si vous n'avez pas l'image localement
- Entrez l'URL directement dans le champ texte
- L'image s'affichera depuis l'URL externe

### 4️⃣ **Aperçu en Temps Réel**
- Aperçu immédiat de l'image sélectionnée
- Possibilité de supprimer et remplacer
- Indicateur de progression pendant l'upload

---

## 🔧 Spécifications Techniques

### **Formats Acceptés**
- ✅ JPG / JPEG
- ✅ PNG
- ✅ GIF
- ✅ WEBP

### **Limites**
- **Taille maximale** : 5 MB
- **Largeur maximale** : 1200px (auto-redimensionnée)
- **Optimisation** : Automatique lors de l'upload

### **Stockage**
- **Emplacement** : `c:\xampp\htdocs\projet ismo\uploads\games\`
- **Nom de fichier** : Généré automatiquement (unique)
- **Format** : `game_[unique_id].[extension]`

### **Accès**
- **URL** : `http://localhost/projet%20ismo/uploads/games/[nom_fichier]`
- **Permissions** : Accessible publiquement

---

## 📋 Comment Utiliser

### **Méthode 1 : Drag & Drop**

1. Ouvrez le formulaire d'ajout de jeu
2. Faites glisser une image depuis votre ordinateur
3. Déposez-la dans la zone "Image du Jeu"
4. L'image s'upload automatiquement
5. Un aperçu s'affiche une fois terminé

### **Méthode 2 : Sélection**

1. Cliquez sur la zone "Image du Jeu"
2. Sélectionnez un fichier image
3. Cliquez sur "Ouvrir"
4. L'upload démarre automatiquement

### **Méthode 3 : URL Externe**

1. Si vous n'avez pas l'image localement
2. Cliquez sur "ou" sous la zone de drop
3. Entrez l'URL de l'image
4. L'image s'affichera depuis cette URL

---

## 🎨 Exemple d'Utilisation

### **Ajouter FIFA 2024**

1. Allez sur `/admin/shop`
2. Cliquez sur **"Ajouter Jeu"**
3. Remplissez :
   - **Nom** : FIFA 2024
   - **Catégorie** : Sports
   - **Plateforme** : PS5
4. **Pour l'image** :
   - Glissez une image de FIFA depuis votre PC
   - OU recherchez "FIFA 2024 cover" sur Google Images
   - Copiez l'URL et collez-la dans le champ
5. Remplissez les autres champs
6. Cliquez sur **"Créer le Jeu"**

---

## 🔄 Optimisation Automatique

Lors de l'upload, le système :

1. ✅ **Vérifie le format** (JPG, PNG, GIF, WEBP uniquement)
2. ✅ **Vérifie la taille** (max 5MB)
3. ✅ **Redimensionne** si l'image est trop grande (> 1200px)
4. ✅ **Optimise la qualité** (85% pour JPEG/WEBP)
5. ✅ **Préserve la transparence** (PNG, GIF)
6. ✅ **Génère un nom unique** pour éviter les conflits

---

## 🛡️ Sécurité

### **Validations Côté Serveur**

- ✅ Vérification du type MIME
- ✅ Vérification de l'extension
- ✅ Validation avec `getimagesize()`
- ✅ Limite de taille stricte
- ✅ Nom de fichier sécurisé (pas d'injection)

### **Permissions**

- ✅ Seuls les **admins** peuvent uploader
- ✅ Session vérifiée côté serveur
- ✅ Token CSRF (via cookies)

---

## 🐛 Dépannage

### **Erreur : "Erreur lors de l'upload"**

**Causes possibles :**
- Le dossier `uploads/games/` n'existe pas
- Permissions insuffisantes
- PHP `upload_max_filesize` trop petit

**Solution :**
```powershell
# Créer le dossier
New-Item -ItemType Directory -Force -Path "c:\xampp\htdocs\projet ismo\uploads\games"

# Vérifier les permissions (Windows)
icacls "c:\xampp\htdocs\projet ismo\uploads\games" /grant Everyone:F
```

### **Erreur : "Fichier trop volumineux"**

**Solution :**
1. Ouvrez `c:\xampp\php\php.ini`
2. Modifiez :
   ```ini
   upload_max_filesize = 10M
   post_max_size = 10M
   ```
3. Redémarrez Apache dans XAMPP

### **Image ne s'affiche pas**

**Solution :**
- Vérifiez que le fichier existe dans `uploads/games/`
- Vérifiez l'URL dans la console du navigateur (F12)
- Testez l'URL directement dans le navigateur

---

## 📊 Structure des Fichiers

```
projet ismo/
├── api/
│   └── admin/
│       └── upload_image.php        # API d'upload
├── uploads/
│   └── games/
│       ├── README.txt              # Documentation
│       ├── game_xxxxx.jpg          # Images uploadées
│       ├── game_yyyyy.png
│       └── ...
└── createxyz-project/_/apps/web/src/
    └── components/
        └── ImageUpload.jsx         # Composant React
```

---

## ✅ Checklist

Avant d'utiliser l'upload d'images, vérifiez :

- [ ] XAMPP Apache est démarré
- [ ] Le dossier `uploads/games/` existe
- [ ] Le dossier a les permissions d'écriture
- [ ] Vous êtes connecté en tant qu'admin
- [ ] Le serveur React est démarré (`npm run dev`)

---

## 🎯 Résultat Final

Une fois un jeu créé avec une image uploadée :

1. ✅ L'image apparaît dans la liste des jeux (page admin)
2. ✅ L'image apparaît dans la boutique (page player)
3. ✅ L'image apparaît sur la page de détails du jeu
4. ✅ L'image est optimisée et performante

---

## 🚀 Prochaines Étapes

Maintenant vous pouvez :

1. **Ajouter des jeux** avec de belles images
2. **Uploader des logos** pour les jeux
3. **Créer une galerie** attrayante
4. **Améliorer l'expérience** utilisateur

---

**Le système d'upload d'images est maintenant entièrement fonctionnel ! 📸✨**
