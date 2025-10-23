# 🎫 Flow Complet de Génération et Affichage des Factures

## ✅ Status: Système Opérationnel

Dernière mise à jour: 21 octobre 2025

---

## 🔄 Flow Complet - Étape par Étape

### 1️⃣ Échange de Points (Joueur)

**Page:** `/player/rewards`

**Actions:**
1. Joueur voit les récompenses disponibles
2. Clique sur "Échanger" (ex: 50 points)
3. API `shop/redeem_with_points.php` est appelée

**Résultat:**
```sql
INSERT INTO purchases (
    user_id, game_id, game_name, package_name,
    price, currency, payment_method,
    payment_status = 'completed',  ← Déjà confirmé (payé en points)
    paid_with_points = 1,
    session_status = 'pending'
)
```

**Important:** ❗ **Aucune facture n'est créée à cette étape !**

---

### 2️⃣ Confirmation et Génération de Facture (Joueur)

**Page:** `/player/my-purchases`

**Actions:**
1. Joueur voit son achat avec statut "En attente"
2. Clique sur **"Démarrer la Session"**
3. Modal de confirmation apparaît
4. Clique sur **"Confirmer"**

**API appelée:** `api/shop/confirm_my_purchase.php`

**Ce qui se passe:**

```php
// 1. Vérifier que l'achat existe
$purchase = SELECT * FROM purchases WHERE id = ? AND user_id = ?;

// 2. Si payment_status = 'completed' (déjà payé en points)
$alreadyCompleted = ($purchase['payment_status'] === 'completed');

// 3. Vérifier si facture existe déjà
$existingInvoice = SELECT id FROM invoices WHERE purchase_id = ?;

// 4. Si pas de facture, la créer
if (!$existingInvoice) {
    $invoiceNumber = 'INV-20251021-000038';
    $validationCode = 'ABCD-EFGH-IJKL-MNOP'; // 16 caractères formatés
    
    INSERT INTO invoices (
        purchase_id, user_id, invoice_number, 
        validation_code, status, created_at
    );
}

// 5. Retourner succès
return {
    success: true,
    has_invoice: true,
    purchase: {...}
};
```

---

### 3️⃣ Récupération et Affichage (Frontend)

**Après l'appel API réussi:**

```javascript
// 1. Modal de succès
showSuccess('Session Activée', 'Récupération de votre facture...');

// 2. Indicateur de chargement
setLoadingInvoice(true);
// → Affiche un beau modal "Génération de votre facture..."

// 3. Tentatives de récupération (max 3 fois)
for (let i = 0; i < 3; i++) {
    // Appeler API invoices/my_invoices.php
    const invoices = await fetch('/api/invoices/my_invoices.php');
    
    // Chercher la facture pour ce purchase_id
    const invoice = invoices.find(inv => inv.purchase_id == purchaseId);
    
    if (invoice) {
        // ✅ Facture trouvée !
        setLoadingInvoice(false);
        setSelectedPurchase({ ...purchase, invoice });
        setShowInvoiceModal(true);
        toast.success('✅ Facture prête !');
        return;
    }
    
    // Attendre 1.5s avant de réessayer
    await sleep(1500);
}

// ❌ Pas de facture après 3 tentatives
setLoadingInvoice(false);
showInfo('Facture en Cours', 'Actualisez la page dans quelques secondes');
```

---

### 4️⃣ Affichage du QR Code

**Composant:** `InvoiceModal.jsx`

**Quand le modal s'ouvre:**

```javascript
useEffect(() => {
    if (invoice?.id) {
        loadQRCode();
    }
}, [invoice?.id]);

const loadQRCode = async () => {
    // Appeler l'API pour générer le QR
    const data = await fetch(
        `/api/invoices/generate_qr.php?invoice_id=${invoice.id}`
    );
    
    setQrData(data);
    // data contient: {
    //     invoice: { validation_code, invoice_number, ... },
    //     qr_text: "ABCDEFGHIJKLMNOP",
    //     qr_url: "https://api.qrserver.com/..."
    // }
};
```

**Affichage:**
- 🔳 QR Code scannable (280x280px)
- 🔢 Code formaté: `ABCD-EFGH-IJKL-MNOP`
- 📋 Bouton de copie
- 📄 Détails de la facture

---

## 🐛 Problèmes Résolus

### Problème 1: Facture Apparaît puis Disparaît ❌

**Cause:**
```javascript
// AVANT
await loadPurchases();  // Recharge la liste
// Le filtre exclut l'achat car son statut a changé
// → selectedPurchase devient null
// → Modal se ferme
```

**Solution:**
```javascript
// APRÈS
// 1. Afficher le modal AVANT de recharger
setShowInvoiceModal(true);

// 2. Recharger 500ms APRÈS
setTimeout(() => loadPurchases(), 500);

// 3. Condition du modal assouplie
{showInvoiceModal && selectedPurchase && ( // ← Pas besoin de invoice
```

### Problème 2: Code de 8 vs 16 Caractères ❌

**Cause:** Anciennes factures avec 8 chars, nouvelles avec 16 chars

**Solution:** Compatibilité des deux formats
```php
// API Backend
if (!preg_match('/^[A-Z0-9]{8}$/', $code) && 
    !preg_match('/^[A-Z0-9]{16}$/', $code)) {
    return error;
}

// Frontend Scanner
const cleanCode = code.replace(/[-\s]/g, '');  // Nettoie les tirets
if (!/^[A-Z0-9]{8}$/.test(cleanCode) && 
    !/^[A-Z0-9]{16}$/.test(cleanCode)) {
    return error;
}
```

### Problème 3: Colonnes SQL Incorrectes ❌

**Corrections:**
```php
// AVANT ❌
INSERT INTO invoices (total_amount, currency, ...)
INSERT INTO points_transactions (transaction_type, points, description, reference_type, ...)

// APRÈS ✅
INSERT INTO invoices (purchase_id, user_id, invoice_number, validation_code, status, ...)
INSERT INTO points_transactions (user_id, type, change_amount, reason, ...)
```

---

## 🎨 Interface Utilisateur

### État 1: Bouton "Démarrer la Session"
```html
<button class="bg-green-600">
    <Play /> Démarrer la Session
</button>
```

### État 2: Modal de Confirmation
```html
<div class="modal">
    <h2>Confirmer le démarrage ?</h2>
    <p>Votre facture sera générée</p>
    <button>Annuler</button>
    <button>Confirmer</button>
</div>
```

### État 3: Modal de Chargement ⏳
```html
<div class="modal">
    <div class="spinner"></div>
    <h3>Génération de votre facture...</h3>
    <p>Veuillez patienter quelques instants</p>
    <div class="dots-bounce"></div>
</div>
```

### État 4: Modal de Facture 🎫
```html
<div class="modal-invoice">
    <!-- QR Code -->
    <img src="qr-code-280x280" />
    
    <!-- Code de Validation -->
    <div class="code">ABCD-EFGH-IJKL-MNOP</div>
    <button>📋 Copier</button>
    
    <!-- Détails -->
    <table>
        <tr><td>Jeu:</td><td>Call of Duty</td></tr>
        <tr><td>Durée:</td><td>60 min</td></tr>
        <tr><td>Points/h:</td><td>50 pts</td></tr>
    </table>
</div>
```

---

## 📊 Base de Données

### Tables Impliquées

**purchases**
```sql
id | user_id | game_id | game_name | payment_status | paid_with_points | session_status
38 | 15      | 5       | CoD       | completed      | 1                | pending
```

**invoices**
```sql
id | purchase_id | user_id | invoice_number     | validation_code      | status
12 | 38          | 15      | INV-20251021-000038| ABCD-EFGH-IJKL-MNOP | pending
```

**points_transactions**
```sql
id | user_id | type  | change_amount | reason
45 | 15      | spend | -50           | Échange pour Package (Purchase #38)
```

---

## 🧪 Tests

### Test Complet End-to-End

```bash
# 1. Ouvrir http://localhost:4000/player/rewards
# 2. Échanger une récompense (50 points)
# 3. Aller sur http://localhost:4000/player/my-purchases
# 4. Cliquer "Démarrer la Session"
# 5. Confirmer
# 6. RÉSULTAT ATTENDU:
#    - ✅ Modal de chargement (spinner)
#    - ✅ Modal de facture avec QR code
#    - ✅ Code: XXXX-XXXX-XXXX-XXXX
#    - ✅ Bouton copier fonctionne
```

### Console Logs Attendus

```javascript
confirmStartSession appelé avec purchaseId: 38
Réponse confirm_my_purchase: 200
Response text: {"success":true,"has_invoice":true,...}
Tentative 1/3 de récupération de facture...
Factures récupérées: 1
Facture trouvée pour purchase: 38
✅ Affichage de la facture
```

---

## 🔍 Débogage

### Si la Facture N'Apparaît Pas

**1. Vérifier la Console (F12)**
```javascript
// Cherche ces messages:
✅ "Facture trouvée pour purchase: 38"
✅ "Affichage de la facture"

// Ou ces erreurs:
❌ "Facture non trouvée après 3 tentatives"
❌ "Response status: 400"
```

**2. Vérifier la Base de Données**
```sql
-- La facture existe ?
SELECT * FROM invoices WHERE purchase_id = 38;

-- L'achat est completed ?
SELECT payment_status, session_status FROM purchases WHERE id = 38;
```

**3. Vérifier l'API**
```bash
# Test direct
curl -X POST http://localhost/projet%20ismo/api/shop/confirm_my_purchase.php \
  -H "Content-Type: application/json" \
  -d '{"purchase_id": 38}'
  
# Devrait retourner:
# {"success":true,"has_invoice":true,...}
```

---

## 📝 Checklist Finale

Avant de considérer le système complet:

- [x] Échange de points crée un purchase
- [x] Purchase avec payment_status = 'completed'
- [x] Bouton "Démarrer la Session" visible
- [x] Modal de confirmation fonctionne
- [x] API confirm_my_purchase génère la facture
- [x] Code de validation 16 caractères formaté
- [x] Modal de chargement s'affiche
- [x] Tentatives de récupération (3x)
- [x] Modal de facture s'affiche
- [x] QR Code visible et scannable
- [x] Code copiable
- [x] Modal ne disparaît pas tout seul
- [x] Scanner admin accepte le code
- [x] Session démarre correctement

---

## 🎉 Résultat Final

**Le système fonctionne de bout en bout:**

1. ✅ **Joueur** échange des points → Purchase créé
2. ✅ **Joueur** démarre la session → Facture générée
3. ✅ **Joueur** voit la facture avec QR → Code formaté et copiable
4. ✅ **Admin** scanne le QR ou tape le code → Session activée
5. ✅ **Joueur** joue pendant X minutes → Temps décompté
6. ✅ **Système** crédite les points bonus → Transaction enregistrée

---

**Date:** 21 octobre 2025  
**Version:** 3.0 - Flow Complet Documenté  
**Status:** ✅ OPÉRATIONNEL
