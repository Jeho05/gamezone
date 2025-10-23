# 🔧 Solution: "Votre clé d'api est incorrecte"

## ❌ Problème Rencontré

Lors du test du widget Kkiapay, l'erreur suivante apparaît:
```
Votre clé d'api est incorrecte
Veuillez vérifier l'environnement dans lequel vous êtes.
Merci d'utiliser la clé adéquate à votre environnement (live ou sandbox) actuel
```

**Clé utilisée:** `b2f64170af2111f093307bbda24d6bac`

## 🎯 Cause Probable

Cette erreur arrive quand:
1. Le paramètre `sandbox` est manquant ou incorrect
2. La clé est en mode **LIVE** mais on force `sandbox="true"`
3. La clé est en mode **SANDBOX** mais on force `sandbox="false"`
4. Des espaces dans les attributs du widget (déjà corrigé)

## ✅ Solution Appliquée

### Changements dans shop.html

**AVANT (problématique):**
```html
<kkiapay-widget 
    amount="1" 
    key="b2f64170af2111f093307bbda24d6bac" 
    callback="https://kkiapay-redirect.com">
</kkiapay-widget>
```

**APRÈS (corrigé):**
```html
<kkiapay-widget 
    id="kkiapay-widget"
    amount="1"
    key="b2f64170af2111f093307bbda24d6bac"
    sandbox="true"
    data="Test Achat">
</kkiapay-widget>
```

**Changements:**
- ✅ Ajout de `sandbox="true"` (force mode sandbox)
- ✅ Ajout de `id="kkiapay-widget"` (pour manipulation JS)
- ✅ Ajout de `data="Test Achat"` (référence)
- ✅ Suppression de `callback` (pas nécessaire pour le widget)
- ✅ Suppression des espaces parasites

### Configuration Dynamique (JavaScript)

Dans `shop.html`, lignes 384-387:
```javascript
widget.setAttribute('amount', String(amt));
widget.setAttribute('key', 'b2f64170af2111f093307bbda24d6bac');
widget.setAttribute('sandbox', 'true');  // ← AJOUTÉ
widget.setAttribute('data', 'Achat #' + (data.purchase_id || ''));
```

## 🧪 Page de Test Créée

**Fichier:** `test_kkiapay_debug.html`

Cette page teste **6 configurations différentes** pour trouver celle qui fonctionne:

| Test | Configuration | Objectif |
|------|--------------|----------|
| 1️⃣ | `sandbox="true"` | Force mode sandbox |
| 2️⃣ | Sans sandbox | Détection automatique |
| 3️⃣ | `sandbox="false"` | Force mode live/production |
| 4️⃣ | API JS `sandbox: true` | Test programmatique sandbox |
| 5️⃣ | API JS `sandbox: false` | Test programmatique live |
| 6️⃣ | API JS sans sandbox | Test programmatique auto |

## 📋 Comment Tester (3 minutes)

### Étape 1: Ouvrir la page de test

**Double-cliquez sur:**
```
TESTER_CLE_KKIAPAY.bat
```

OU ouvrez directement:
```
http://localhost/projet%20ismo/test_kkiapay_debug.html
```

### Étape 2: Tester chaque widget

1. **Cliquez sur chaque widget** (Tests 1, 2, 3)
2. **Cliquez sur chaque bouton** (Tests 4, 5, 6)
3. **Notez quel test ne montre PAS l'erreur "clé incorrecte"**

### Étape 3: Identifier la bonne configuration

**Si Test 1 ou 4 fonctionne** → Votre clé est en mode **SANDBOX**
- ✅ Utilisez `sandbox="true"` partout
- ✅ shop.html est déjà configuré correctement

**Si Test 3 ou 5 fonctionne** → Votre clé est en mode **LIVE**
- ⚠️ Changez `sandbox="true"` en `sandbox="false"`
- ⚠️ Ou retirez complètement le paramètre sandbox

**Si Test 2 ou 6 fonctionne** → Mode auto-détection
- ✅ Retirez le paramètre sandbox
- Kkiapay détecte automatiquement selon la clé

### Étape 4: Appliquer la configuration

Une fois que vous savez quelle configuration fonctionne, dites-moi et je mettrai à jour `shop.html` en conséquence.

## 🔍 Vérifier la Clé dans Dashboard Kkiapay

1. Connectez-vous: https://app.kkiapay.me
2. Allez dans **Paramètres** → **API Keys**
3. Vérifiez si votre clé est marquée:
   - **SANDBOX** (Test) → Utilisez `sandbox="true"`
   - **LIVE** (Production) → Utilisez `sandbox="false"` ou retirez le paramètre

## 📊 Diagnostic Actuel

### Tests Réussis (3/5)
- ✅ **SCRIPT** - Le script k.js se charge correctement
- ✅ **BACKEND** - La méthode Kkiapay est configurée
- ✅ **FLOW** - Le flow d'achat est opérationnel

### Tests Échoués (2/5)
- ❌ **WIDGET** - Erreur "clé incorrecte"
- ❌ **API** - Probablement même erreur

**Raison:** Paramètre `sandbox` incorrect ou manquant

## 🎯 Solutions Possibles

### Solution A: Force Sandbox (Actuel)
```html
<kkiapay-widget sandbox="true" ...>
```
**Quand l'utiliser:** Si votre clé est en mode SANDBOX/TEST

### Solution B: Force Production
```html
<kkiapay-widget sandbox="false" ...>
```
**Quand l'utiliser:** Si votre clé est en mode LIVE/PRODUCTION

### Solution C: Auto-détection
```html
<kkiapay-widget ...>
<!-- Pas de paramètre sandbox -->
```
**Quand l'utiliser:** Si vous voulez que Kkiapay détecte automatiquement

### Solution D: Clé Différente
Si aucune solution ne fonctionne, il faudra peut-être utiliser une autre clé:
- Clé sandbox pour les tests
- Clé live pour la production

## 📁 Fichiers Modifiés

1. **shop.html** (modifié)
   - Ajout `sandbox="true"` au widget
   - Configuration dynamique du sandbox en JS

2. **test_kkiapay_debug.html** (créé)
   - 6 tests pour trouver la bonne configuration
   - Logs détaillés
   - Interface de debug

3. **test_kkiapay_direct.html** (modifié)
   - Ajout `sandbox="true"` au widget

4. **TESTER_CLE_KKIAPAY.bat** (créé)
   - Script pour lancer les tests rapidement

5. **VERIFIER_CLE_KKIAPAY.ps1** (créé)
   - Script PowerShell de vérification

## 🚀 Actions Immédiates

1. **Exécutez:** `TESTER_CLE_KKIAPAY.bat`
2. **Testez** les 6 configurations dans la page qui s'ouvre
3. **Notez** quel test fonctionne (pas d'erreur "clé incorrecte")
4. **Revenez** me dire quel test a fonctionné
5. Je mettrai à jour `shop.html` avec la bonne configuration

## 📞 Support Kkiapay

Si aucun test ne fonctionne:
- **Email:** support@kkiapay.me
- **Documentation:** https://docs.kkiapay.me
- **Dashboard:** https://app.kkiapay.me

Posez la question:
> "Ma clé b2f64170af2111f093307bbda24d6bac est-elle en mode SANDBOX ou LIVE ?"

---

**Date:** 2025-01-23  
**Statut:** En attente des résultats de test  
**Prochaine étape:** Tester avec test_kkiapay_debug.html
