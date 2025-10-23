# ✅ Kkiapay - Configuration MODE LIVE (Production)

## ❌ Problème Identifié

**Erreur:** `401 Unauthorized` sur `https://inspector.kkiapay.me/session/v2/init`

**Cause:** 
- Vous utilisez une **clé LIVE** (production) : `072b361d25546db0aee3d69bf07b15331c51e39f`
- Mais le code envoyait `sandbox: true` (mode test)
- Kkiapay refuse car une clé LIVE ne peut pas fonctionner en mode sandbox → **401**

---

## ✅ Solution Appliquée

### 1. Projet React (.env.local) ✅

**Fichier:** `createxyz-project/_/apps/web/.env.local`

**Avant (❌):**
```env
NEXT_PUBLIC_KKIAPAY_SANDBOX=1  # ← Mode test
```

**Après (✅):**
```env
NEXT_PUBLIC_KKIAPAY_SANDBOX=0  # ← Mode LIVE
```

### 2. Projet PHP (shop.html) ✅

**Avant (❌):**
```html
<kkiapay-widget sandbox="true" ...>  ← Mode test
```

**Après (✅):**
```html
<kkiapay-widget ...>  ← Pas de sandbox = Mode LIVE
```

**JavaScript (ligne 384):**
```javascript
// Ligne sandbox="true" SUPPRIMÉE
widget.setAttribute('amount', String(amt));
widget.setAttribute('key', '072b361d25546db0aee3d69bf07b15331c51e39f');
widget.setAttribute('callback', 'https://kkiapay-redirect.com');
// ✅ Pas de sandbox = détection auto par Kkiapay
```

---

## 🔄 IMPORTANT : Redémarrer le Serveur React

**Le serveur DOIT être redémarré pour lire le nouveau `.env.local` !**

```powershell
# Dans le terminal du serveur React:
Ctrl+C  # Arrêter

# Puis redémarrer:
cd "c:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web"
npm run dev
```

---

## 🧪 Test Après Redémarrage

### 1. Console Browser (F12)

**Messages attendus:**
```javascript
✅ KkiaPay script loaded successfully
🔵 Button clicked - handlePayment called
🚀 Opening KkiaPay widget with config: 
   { amount: 500, apiKey: "072b361d25...", sandbox: false }  // ← false maintenant
✅ openKkiapayWidget called successfully
```

### 2. Network (F12 → Network)

**Requête qui devrait RÉUSSIR maintenant:**
```
POST https://inspector.kkiapay.me/session/v2/init
Status: 200 OK  ✅  (plus de 401)
```

### 3. Widget

**Le widget devrait:**
- ✅ S'ouvrir sans erreur
- ✅ Afficher le montant correct
- ✅ Accepter les **VRAIS numéros de téléphone** (plus de numéros test)

---

## ⚠️ MODE LIVE = VRAIS PAIEMENTS

**Attention:** Vous êtes maintenant en **PRODUCTION** !

### Numéros de Téléphone

**❌ Plus de numéros test:**
```
97000000  ← Ne fonctionne plus
96000000  ← Ne fonctionne plus
```

**✅ Vrais numéros uniquement:**
```
Utilisez de vrais numéros de téléphone mobiles
Ex: 90123456, 91234567, etc.
```

### Paiements

- ✅ **Vrais débits** sur les comptes Mobile Money
- ✅ **Vraies transactions** avec les opérateurs (MTN, Orange, Moov, Wave)
- ✅ **Frais réels** appliqués

### OTP

- ✅ **Vrais codes OTP** envoyés par SMS
- ❌ Plus de code "123456" universel

---

## 📊 Différences Sandbox vs Live

| Aspect | Sandbox (Test) | Live (Production) |
|--------|----------------|-------------------|
| **Numéros** | 97* (succès), 96* (échec) | Vrais numéros mobiles |
| **OTP** | 123456 (universel) | Code reçu par SMS |
| **Paiements** | Simulés (gratuit) | Réels (débités) |
| **Frais** | Aucun | Frais réels |
| **Clé** | Clé sandbox | Clé live (actuelle) |

---

## 🔍 Vérification de la Configuration

### Test Console (F12)

**Copiez-collez dans la console:**
```javascript
console.log('=== CONFIGURATION KKIAPAY ===');
console.log('Clé:', import.meta.env.NEXT_PUBLIC_KKIAPAY_PUBLIC_KEY);
console.log('Sandbox:', import.meta.env.NEXT_PUBLIC_KKIAPAY_SANDBOX);
console.log('Mode:', import.meta.env.NEXT_PUBLIC_KKIAPAY_SANDBOX === '1' ? 'TEST' : 'LIVE');
console.log('=============================');
```

**Résultat attendu:**
```
=== CONFIGURATION KKIAPAY ===
Clé: 072b361d25546db0aee3d69bf07b15331c51e39f
Sandbox: 0
Mode: LIVE  ✅
=============================
```

---

## ✅ Checklist de Validation

### Configuration
- [x] `.env.local`: `NEXT_PUBLIC_KKIAPAY_SANDBOX=0`
- [x] `shop.html`: `sandbox` retiré du widget
- [x] `shop.html`: JavaScript sans `sandbox="true"`

### Tests (Après Redémarrage)
- [ ] Serveur React redémarré
- [ ] Console affiche `sandbox: false`
- [ ] Requête `/session/v2/init` retourne `200 OK`
- [ ] Widget s'ouvre sans erreur 401
- [ ] Test avec vrai numéro de téléphone
- [ ] OTP reçu par SMS
- [ ] Paiement test réel fonctionnel

---

## 🚨 Sécurité & Production

### Recommandations

1. **Webhook de Confirmation**
   - Configurez le webhook Kkiapay vers votre API
   - URL: `https://votre-domaine.com/api/shop/payment_callback.php`
   - Permet la validation automatique des paiements

2. **HTTPS Obligatoire**
   - En production, utilisez **HTTPS** (pas HTTP)
   - Les paiements réels nécessitent une connexion sécurisée

3. **Variables d'Environnement**
   - Créez un fichier `.env.production` pour la prod
   - Ne commitez JAMAIS les clés API dans Git

4. **Tests Avant Mise en Production**
   - Testez avec de petits montants réels (100-500 XOF)
   - Vérifiez les callbacks
   - Testez tous les opérateurs (MTN, Orange, Moov, Wave)

---

## 🔄 Retour en Mode Sandbox (Si Besoin)

**Si vous voulez revenir en mode test:**

### React (.env.local)
```env
NEXT_PUBLIC_KKIAPAY_SANDBOX=1  # Retour en sandbox
```

### PHP (shop.html)
```html
<kkiapay-widget sandbox="true" ...>
```

```javascript
widget.setAttribute('sandbox', 'true');
```

**Puis redémarrer le serveur React.**

---

## 📞 Support Kkiapay

### Dashboard
- **URL:** https://app.kkiapay.me
- **Section:** API Keys
- **Vérification:** Type de clé (Sandbox/Live)

### Documentation
- **Docs:** https://docs.kkiapay.me
- **API Reference:** https://docs.kkiapay.me/v1/api
- **Webhook:** https://docs.kkiapay.me/v1/webhook

### Contact
- **Email:** support@kkiapay.me
- **Sujet:** "Clé live - Erreur 401"

---

## 📝 Résumé

**Problème:** Clé LIVE utilisée en mode sandbox → 401 Unauthorized

**Solution:** 
- ✅ Mode sandbox désactivé (`.env.local`: `SANDBOX=0`)
- ✅ Attribut `sandbox` retiré de `shop.html`
- ⚠️ **Serveur React à redémarrer**

**Résultat attendu:** 
- ✅ Widget s'ouvre sans erreur
- ✅ Requête API retourne 200 OK
- ✅ Paiements réels fonctionnels

---

**Date:** 2025-01-23  
**Mode:** LIVE (Production)  
**Clé:** 072b361d25546db0aee3d69bf07b15331c51e39f  
**Sandbox:** Désactivé (0/false)  
**Statut:** ✅ Configuration corrigée - Serveur à redémarrer
