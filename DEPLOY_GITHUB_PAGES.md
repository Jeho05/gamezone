# 🚀 Déploiement sur GitHub Pages (Solution Simple)

## ✅ Pourquoi GitHub Pages

- Gratuit et illimité
- Build statique pur (pas de SSR)
- Configuration simple
- Fonctionne parfaitement avec React SPA

---

## 📝 Étapes (5 minutes)

### 1. Installer gh-pages

```bash
cd "C:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web"
npm install --save-dev gh-pages
```

### 2. Modifier package.json

Ajouter ces lignes dans `package.json` :

```json
{
  "homepage": "https://Jeho05.github.io/gamezone",
  "scripts": {
    "predeploy": "npm run build",
    "deploy": "gh-pages -d build/client"
  }
}
```

### 3. Déployer

```bash
npm run deploy
```

### 4. Configurer GitHub

1. Allez sur : https://github.com/Jeho05/gamezone/settings/pages
2. Source : Sélectionnez "gh-pages" branch
3. Cliquez "Save"

### 5. Attendez 2 minutes

Votre site sera accessible à :
```
https://Jeho05.github.io/gamezone
```

---

## 🎯 Avantages

- ✅ Déploiement en 1 commande : `npm run deploy`
- ✅ Pas de configuration Vercel/Netlify
- ✅ Fonctionne avec React SPA
- ✅ Totalement gratuit

---

## 🔄 Mises à jour

À chaque modification :

```bash
npm run deploy
```

C'est tout ! 2 minutes de déploiement.
