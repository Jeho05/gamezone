# 🔒 Système de Transactions Sécurisées

## 🎯 Objectif

**Garantir que les joueurs ne perdent JAMAIS de points ou d'argent en cas d'erreur**, tout en empêchant la fraude.

---

## 🏗️ Architecture du Système

### Principe Fondamental: **TRANSACTION ATOMIQUE**

```
Tout ou Rien
├── ✅ Soit TOUT fonctionne → Points débités + Achat créé
└── ❌ Soit RIEN ne se passe → Points NON débités + Rollback automatique
```

Aucun état intermédiaire où:
- ❌ Points débités MAIS pas d'achat
- ❌ Achat créé MAIS points non débités
- ❌ Erreur et pas de remboursement

---

## 🔄 Flow Complet

### Étape par Étape

```
1. [PENDING] Initialisation de la transaction
   ├── Création d'un enregistrement avec status="pending"
   ├── Génération d'une clé d'idempotence (éviter les doublons)
   └── Aucun débit à ce stade

2. [VERIFICATION] Vérifications sans débit
   ├── Vérifier que la récompense existe
   ├── Vérifier que l'utilisateur a assez de points
   ├── Vérifier le stock disponible
   └── ⚠️ AUCUN POINT N'EST DÉBITÉ ICI

3. [PROCESSING] Passage en mode traitement
   ├── Status change à "processing"
   └── Point de décision: GO or NO-GO

4. [DEBIT] Débit des points (Point de non-retour)
   ├── UPDATE users SET points = points - X WHERE points >= X
   ├── Vérification: rowCount > 0 ? (race condition check)
   ├── Log de la transaction de points
   └── Si échec → ROLLBACK immédiat

5. [CREATE] Création de l'achat
   ├── INSERT INTO purchases (...)
   ├── Lien avec la transaction
   └── Si échec → ROLLBACK (points restaurés)

6. [STOCK] Mise à jour du stock
   ├── Décrémentation du stock
   ├── Incrémentation du compteur
   └── Si échec → ROLLBACK (points restaurés)

7. [COMPLETED] Transaction complétée
   ├── Status = "completed"
   ├── COMMIT de toute la transaction SQL
   └── ✅ Succès garanti
```

---

## 🛡️ Mécanismes de Sécurité

### 1. **Idempotence**

**Problème:** L'utilisateur clique 2 fois → Débité 2 fois

**Solution:**
```php
idempotency_key = md5(user_id + reward_id + timestamp)

// Si la clé existe déjà
if (transaction exists avec cette clé) {
    if (status == 'completed') {
        return "Déjà traité";
    }
}
```

**Résultat:** Une transaction ne peut être exécutée qu'UNE SEULE FOIS

---

### 2. **Transaction SQL (BEGIN...COMMIT/ROLLBACK)**

**Problème:** Erreur entre le débit et la création de l'achat

**Solution:**
```sql
BEGIN TRANSACTION;

-- Toutes les opérations ici
UPDATE users SET points = points - 50...;
INSERT INTO purchases...;
UPDATE game_packages...;

-- Si TOUT est OK
COMMIT; 

-- Si UNE SEULE erreur
ROLLBACK; -- Tout est annulé
```

**Résultat:** Atomicité garantie

---

### 3. **FOR UPDATE Lock**

**Problème:** 2 requêtes simultanées vérifient le solde en même temps

**Solution:**
```sql
SELECT points FROM users 
WHERE id = ? 
FOR UPDATE; -- ← Verrouille la ligne

-- Maintenant on est sûr que personne d'autre ne peut modifier
```

**Résultat:** Pas de race condition

---

### 4. **Double-Check sur UPDATE**

**Problème:** Les points ont changé entre le SELECT et le UPDATE

**Solution:**
```sql
UPDATE users 
SET points = points - 50
WHERE id = ? AND points >= 50; -- ← Vérification dans le WHERE

-- Vérifier rowCount
if (rowCount == 0) {
    throw "Points insuffisants (race condition)";
}
```

**Résultat:** Impossible de débiter si pas assez de points

---

### 5. **États Intermédiaires Trackés**

À chaque étape, on enregistre où on en est:

```sql
UPDATE purchase_transactions 
SET step = 'points_verified' WHERE id = X;

UPDATE purchase_transactions 
SET step = 'debiting_points' WHERE id = X;

UPDATE purchase_transactions 
SET step = 'purchase_created' WHERE id = X;
```

**Résultat:** En cas d'erreur, on sait exactement où ça a planté

---

### 6. **Cleanup Automatique**

**Problème:** Transaction bloquée en "processing" (crash serveur, etc.)

**Solution:**
```sql
-- Event automatique toutes les 5 minutes
UPDATE purchase_transactions
SET status = 'failed', failure_reason = 'Timeout'
WHERE status = 'processing' 
  AND created_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE);
```

**Résultat:** Pas de transactions zombie

---

### 7. **Système de Refund**

**Problème:** Erreur APRÈS le débit (rare mais possible)

**Solution:**
```php
CALL refund_transaction(transaction_id, reason, admin_id);

// Automatiquement:
// - Rembourse les points
// - Annule l'achat
// - Log l'action
// - Marque comme "refunded"
```

**Résultat:** Remboursement facile et tracé

---

## 📊 Table `purchase_transactions`

### Structure

| Colonne | Type | Description |
|---------|------|-------------|
| id | INT | ID unique |
| user_id | INT | Utilisateur |
| reward_id | INT | Récompense achetée |
| purchase_id | INT | Achat créé (si succès) |
| idempotency_key | VARCHAR(255) | Clé anti-doublon |
| **status** | ENUM | **pending, processing, completed, failed, refunded** |
| step | VARCHAR(50) | Étape actuelle |
| points_amount | INT | Montant en points |
| failure_reason | TEXT | Raison de l'échec |
| refund_reason | TEXT | Raison du remboursement |
| created_at | DATETIME | Date de création |
| completed_at | DATETIME | Date de complétion |
| failed_at | DATETIME | Date d'échec |
| refunded_at | DATETIME | Date de remboursement |

### États Possibles

```
pending      → Transaction initialisée, rien n'est débité
processing   → Vérifications passées, débit en cours
completed    → ✅ Tout est OK, points débités, achat créé
failed       → ❌ Erreur, ROLLBACK effectué, rien n'est débité
refunded     → 💰 Remboursement effectué par admin
```

---

## 🔧 Installation

### Étape 1: Appliquer la Migration

```bash
# Via PhpMyAdmin ou ligne de commande
mysql -u root -p gamezone < api/migrations/add_secure_transactions.sql
```

Ou ouvre:
```
http://localhost/phpmyadmin
```
Et exécute le fichier `add_secure_transactions.sql`

### Étape 2: Vérifier les Tables

```sql
SHOW TABLES LIKE 'purchase_transactions';
-- Devrait retourner 1 ligne

SELECT * FROM purchase_transactions LIMIT 1;
-- Devrait fonctionner (même si vide)
```

---

## 🎮 Utilisation

### Frontend - Échange de Points

```javascript
// Au lieu de l'ancienne API
const response = await fetch('/api/shop/redeem_with_points.php', {
    method: 'POST',
    body: JSON.stringify({ reward_id: 5 })
});

// Utiliser la NOUVELLE API sécurisée
const response = await fetch('/api/transactions/secure_purchase.php', {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ 
        reward_id: 5,
        idempotency_key: generateUniqueKey() // Optionnel
    })
});

const data = await response.json();

if (data.success) {
    // ✅ Achat réussi
    console.log('Purchase ID:', data.purchase_id);
    console.log('Points restants:', data.new_balance);
} else {
    // ❌ Échec
    console.log('Erreur:', data.message);
    console.log('Safe:', data.safe); // true = aucun point perdu
}
```

---

## 🔍 Admin - Gestion des Transactions

### Voir les Transactions

```
http://localhost/projet%20ismo/admin/transactions
```

### Rembourser une Transaction

```javascript
fetch('/api/admin/refund_transaction.php', {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        transaction_id: 123,
        reason: 'Erreur technique, remboursement demandé'
    })
});
```

---

## 📈 Monitoring

### Requêtes Utiles

```sql
-- Statistiques globales
SELECT * FROM transaction_stats;

-- Transactions échouées récentes
SELECT * FROM purchase_transactions
WHERE status = 'failed'
ORDER BY failed_at DESC
LIMIT 20;

-- Transactions bloquées (à nettoyer)
SELECT * FROM purchase_transactions
WHERE status = 'processing'
  AND created_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE);

-- Remboursements effectués
SELECT * FROM purchase_transactions
WHERE status = 'refunded'
ORDER BY refunded_at DESC;

-- Transactions par utilisateur
SELECT * FROM user_transaction_history
WHERE user_id = 15
ORDER BY created_at DESC;
```

---

## 🐛 Scénarios de Test

### Test 1: Achat Normal (Tout OK)

```javascript
// Utilisateur avec 100 points achète récompense de 50 points
POST /api/transactions/secure_purchase.php
{
    "reward_id": 5
}

// Résultat attendu:
{
    "success": true,
    "transaction_id": 123,
    "purchase_id": 456,
    "points_spent": 50,
    "new_balance": 50
}
```

**Vérification BD:**
```sql
SELECT * FROM purchase_transactions WHERE id = 123;
-- status: completed ✅

SELECT points FROM users WHERE id = X;
-- 50 points ✅

SELECT * FROM purchases WHERE id = 456;
-- payment_status: completed ✅
```

---

### Test 2: Points Insuffisants

```javascript
// Utilisateur avec 30 points achète récompense de 50 points
POST /api/transactions/secure_purchase.php
{
    "reward_id": 5
}

// Résultat attendu:
{
    "error": "Points insuffisants",
    "message": "Requis: 50, Disponible: 30",
    "safe": true,
    "can_retry": true
}
```

**Vérification BD:**
```sql
SELECT * FROM purchase_transactions WHERE user_id = X ORDER BY id DESC LIMIT 1;
-- status: failed ✅
-- failure_reason: "Points insuffisants..." ✅

SELECT points FROM users WHERE id = X;
-- 30 points (INCHANGÉ) ✅
```

---

### Test 3: Double Clic (Idempotence)

```javascript
// Clic 1
POST /api/transactions/secure_purchase.php
{ "reward_id": 5, "idempotency_key": "ABC123" }
// ✅ success

// Clic 2 (même clé)
POST /api/transactions/secure_purchase.php
{ "reward_id": 5, "idempotency_key": "ABC123" }
// ✅ already_processed: true (pas de deuxième débit)
```

**Vérification BD:**
```sql
SELECT COUNT(*) FROM purchase_transactions 
WHERE idempotency_key = 'ABC123';
-- 1 seule transaction ✅
```

---

### Test 4: Crash Serveur (Rollback)

```javascript
// Simuler un crash en tuant le processus PHP au milieu

// Avant le test
SELECT points FROM users WHERE id = X;
-- 100 points

// Pendant le test (crash simulé)
// ...

// Après le crash
SELECT points FROM users WHERE id = X;
-- 100 points (INCHANGÉ car rollback) ✅

SELECT * FROM purchase_transactions WHERE user_id = X ORDER BY id DESC LIMIT 1;
-- status: failed (nettoyé par event automatique) ✅
```

---

### Test 5: Remboursement Admin

```javascript
POST /api/admin/refund_transaction.php
{
    "transaction_id": 123,
    "reason": "Erreur technique"
}

// Résultat:
{
    "success": true,
    "message": "Remboursement effectué"
}
```

**Vérification BD:**
```sql
SELECT * FROM purchase_transactions WHERE id = 123;
-- status: refunded ✅
-- refund_reason: "Erreur technique" ✅

SELECT * FROM points_transactions 
WHERE user_id = X 
ORDER BY created_at DESC LIMIT 1;
-- type: 'refund', change_amount: 50 ✅

SELECT points FROM users WHERE id = X;
-- Points restaurés ✅
```

---

## ⚠️ Cas Limites Gérés

### 1. Race Condition (2 achats simultanés)
✅ **FOR UPDATE** + **double-check dans UPDATE**

### 2. Crash Serveur
✅ **Transaction SQL** rollback automatique

### 3. Timeout/Blocage
✅ **Event automatique** nettoie après 5 minutes

### 4. Double Clic
✅ **Idempotency key** une seule exécution

### 5. Stock épuisé
✅ **Vérification** avant débit

### 6. Erreur après débit
✅ **Rollback SQL** + **Procédure refund**

---

## 📋 Checklist de Migration

- [ ] Appliquer `add_secure_transactions.sql`
- [ ] Vérifier que la table `purchase_transactions` existe
- [ ] Vérifier que l'event automatique est actif
- [ ] Tester un achat avec la nouvelle API
- [ ] Tester un achat avec points insuffisants
- [ ] Tester l'idempotence (double clic)
- [ ] Tester un remboursement admin
- [ ] Vérifier les logs dans la console
- [ ] Documenter pour l'équipe

---

## 🎉 Résultat Final

### Garanties

✅ **Aucun joueur ne peut perdre de points sans recevoir son achat**  
✅ **Aucun achat ne peut être créé sans débit de points**  
✅ **Rollback automatique en cas d'erreur**  
✅ **Impossible d'acheter 2 fois en cliquant 2 fois**  
✅ **Impossible d'acheter avec points insuffisants**  
✅ **Remboursement facile par admin**  
✅ **Audit trail complet** (toutes les actions sont loggées)  
✅ **Nettoyage automatique** des transactions bloquées  

---

**Date:** 21 octobre 2025  
**Version:** 1.0 - Système Transactions Sécurisées  
**Status:** ✅ PRÊT À DÉPLOYER
