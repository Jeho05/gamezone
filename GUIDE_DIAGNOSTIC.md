# 🔍 GUIDE DIAGNOSTIC GAMEZONE

## 🎯 Objectif

Ce guide vous permet de **détecter TOUTES les erreurs** de l'application GameZone déployée sur GitHub Pages et de prendre des **précautions en conséquence**.

---

## 📊 3 OUTILS DE DIAGNOSTIC

### 1️⃣ Diagnostic Web (EN LIGNE)

**URL :** `https://Jeho05.github.io/gamezone/diagnostic.html`

**Quand l'utiliser :**
- ✅ Pour tester l'application déployée
- ✅ Pour vérifier si les assets se chargent
- ✅ Pour capturer les erreurs JavaScript en direct
- ✅ Pour générer un rapport complet

**Comment l'utiliser :**
1. Allez sur : https://Jeho05.github.io/gamezone/diagnostic.html
2. Cliquez sur **"LANCER TOUS LES TESTS"**
3. Attendez la fin des tests (30-60 secondes)
4. Cliquez sur **"COPIER RAPPORT"**
5. Collez le rapport dans un fichier texte ou envoyez-le moi

**Tests effectués :**
- ✅ Environnement navigateur
- ✅ Chargement des assets (JS, CSS)
- ✅ Initialisation React
- ✅ Configuration Router
- ✅ Performance réseau
- ✅ Détection erreurs console
- ✅ Statistiques temps réel

---

### 2️⃣ Diagnostic Local (PowerShell)

**Fichier :** `diagnostic.ps1`

**Quand l'utiliser :**
- ✅ Avant de builder
- ✅ Pour vérifier la structure du projet
- ✅ Pour valider la configuration
- ✅ Pour détecter les fichiers manquants

**Comment l'utiliser :**

```powershell
cd "C:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web"
.\diagnostic.ps1
```

**Tests effectués :**
- ✅ Structure du projet
- ✅ Fichiers critiques
- ✅ Build output
- ✅ Configuration (Vite, React Router)
- ✅ Dépendances npm
- ✅ GitHub Pages accessibilité
- ✅ Git repository

**Résultat :**
- Rapport console avec couleurs
- Fichier `diagnostic-report-YYYYMMDD-HHmmss.txt` généré

---

### 3️⃣ Console du Navigateur (F12)

**Quand l'utiliser :**
- ✅ Pendant que vous naviguez
- ✅ Pour voir les erreurs en temps réel
- ✅ Pour débugger une page spécifique

**Comment l'utiliser :**
1. Ouvrez l'application : https://Jeho05.github.io/gamezone/
2. Appuyez sur **F12** (ou Clic droit → Inspecter)
3. Allez dans l'onglet **"Console"**
4. Naviguez dans l'application
5. Copiez toutes les lignes **ROUGES** (erreurs)

---

## 🚨 TYPES D'ERREURS À SURVEILLER

### ❌ Erreurs Critiques (Bloquantes)

| Erreur | Signification | Solution |
|--------|---------------|----------|
| `Root element not found` | React ne trouve pas `<div id="root">` | Vérifier `index.html` |
| `Cannot read properties of undefined` | Variable non définie | Vérifier les imports |
| `Module not found` | Import incorrect | Vérifier les chemins |
| `404 Not Found` | Asset manquant | Rebuild + redéployer |
| `Uncaught SyntaxError` | Erreur de syntaxe JS | Vérifier le code source |
| `useLoaderData must be used within a data router` | Mauvais router utilisé | Utiliser `BrowserRouter` |

### ⚠️ Avertissements (Non-bloquants mais à surveiller)

| Avertissement | Signification | Action |
|---------------|---------------|--------|
| `React does not recognize prop` | Prop React invalide | Corriger le nom du prop |
| `Warning: Each child in a list should have a unique "key"` | Clé manquante dans liste | Ajouter `key={}`  |
| `Cannot update component while rendering` | Effet secondaire dans render | Utiliser `useEffect` |
| `Memory leak` | Composant non nettoyé | Ajouter cleanup dans `useEffect` |

### 📊 Erreurs Réseau

| Erreur | Signification | Solution |
|--------|---------------|----------|
| `CORS policy` | Backend bloque la requête | Configurer CORS sur backend |
| `Failed to fetch` | Réseau indisponible | Vérifier connexion |
| `403 Forbidden` | Accès refusé | Vérifier permissions |
| `500 Internal Server Error` | Erreur serveur | Vérifier logs backend |

---

## 📋 CHECKLIST DE DIAGNOSTIC

### Avant Build

- [ ] Exécuter `diagnostic.ps1`
- [ ] Vérifier que tous les fichiers critiques existent
- [ ] Vérifier `basename="/gamezone"` dans `FullApp.tsx`
- [ ] Vérifier `base: '/gamezone/'` dans `vite.config.production.ts`
- [ ] S'assurer que `page-minimal.jsx` est utilisé

### Après Build

- [ ] Vérifier que `build/client/` contient des fichiers
- [ ] Vérifier que `build/client/index.html` existe
- [ ] Vérifier que `build/client/assets/` contient des JS et CSS
- [ ] Ouvrir `build/client/index.html` localement et vérifier

### Après Déploiement

- [ ] Attendre 2-3 minutes
- [ ] Aller sur https://Jeho05.github.io/gamezone/diagnostic.html
- [ ] Lancer tous les tests
- [ ] Copier le rapport
- [ ] Vérifier taux de succès > 80%

### Si Page Blanche

- [ ] Ouvrir F12 → Console
- [ ] Copier TOUTES les erreurs rouges
- [ ] Aller sur https://Jeho05.github.io/gamezone/diagnostic.html
- [ ] Copier le rapport
- [ ] Envoyer rapport + erreurs console

---

## 🛠️ ACTIONS CORRECTIVES

### Si Build Échoue

```bash
# Nettoyer
rm -rf node_modules
rm -rf build

# Réinstaller
npm install --legacy-peer-deps

# Rebuild
npm run build
```

### Si Assets 404

```bash
# Vérifier la config Vite
cat vite.config.production.ts | grep "base"
# Doit afficher: base: '/gamezone/',

# Vérifier FullApp
cat src/FullApp.tsx | grep "basename"
# Doit afficher: <BrowserRouter basename="/gamezone">

# Rebuild
npm run build
.\deploy-manual.bat
```

### Si React Ne Monte Pas

1. Ouvrir `diagnostic.html`
2. Vérifier section "TEST REACT APPLICATION"
3. Si "Root Element: ❌" → Problème `index.html`
4. Si "Root Children: 0" → Problème JavaScript
5. Copier toutes les erreurs et me les envoyer

### Si Router Ne Fonctionne Pas

```tsx
// Vérifier src/FullApp.tsx
<BrowserRouter basename="/gamezone">  // ← DOIT être là
  <Routes>
    <Route path="/" element={<HomePage />} />
  </Routes>
</BrowserRouter>
```

---

## 📊 EXEMPLE DE RAPPORT DIAGNOSTIC

```
=== RAPPORT DIAGNOSTIC GAMEZONE ===

Date: 24/10/2025 20:00:00
URL: https://Jeho05.github.io/gamezone/diagnostic.html

STATISTIQUES:
- Tests réussis: 25
- Tests échoués: 2
- Avertissements: 3

ERREURS (2):
[20:00:05] FETCH ERROR: /gamezone/assets/missing-file.js - 404
[20:00:07] CONSOLE ERROR: Cannot read property 'map' of undefined

RÉSULTATS DÉTAILLÉS:
[SUCCESS] Navigateur: Chrome 120
[SUCCESS] Root Element: ✅ Trouvé
[ERROR] Asset: /gamezone/assets/missing-file.js - 404
[WARNING] React: Non détecté dans window (normal avec modules)
[SUCCESS] Page Load Time: 1234ms
...
```

---

## 🎯 PRIORITÉS DE CORRECTION

### 🔴 Priorité HAUTE (Corriger immédiatement)

- Root element not found
- Module not found
- 404 errors sur assets critiques
- Uncaught errors

### 🟡 Priorité MOYENNE (Corriger bientôt)

- Warnings React
- Performance < 3s
- CORS errors (si backend utilisé)

### 🟢 Priorité BASSE (Optionnel)

- Console logs
- Optimisations CSS
- Refactoring code

---

## 📞 SUPPORT

**Si vous avez des erreurs :**

1. **Exécutez `diagnostic.ps1`** et copiez le résultat
2. **Allez sur `diagnostic.html`** et copiez le rapport
3. **Ouvrez F12** et copiez les erreurs console
4. **Envoyez-moi :**
   - Les 3 rapports
   - Ce que vous voyez à l'écran
   - À quelle étape ça bloque

**Je pourrai alors :**
- Identifier le problème exact
- Proposer une correction précise
- Tester la solution

---

## ✅ CHECKLIST FINALE AVANT VALIDATION

- [ ] `diagnostic.ps1` → 0 erreurs
- [ ] `diagnostic.html` → > 80% succès
- [ ] Console F12 → 0 erreurs rouges
- [ ] Page d'accueil s'affiche
- [ ] Navigation fonctionne
- [ ] Backend connecté (optionnel)

---

**Créé le :** 24/10/2025  
**Version :** 1.0  
**Projet :** GameZone  
