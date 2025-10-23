# 🔧 Guide des Corrections: Gestion des Sessions et Achats

## 📋 Vue d'ensemble

Ce document présente les **corrections professionnelles** apportées au système de gestion des sessions de jeu en relation avec les achats. Les améliorations garantissent une **synchronisation automatique et fiable** entre les tables `purchases` et `active_game_sessions_v2`.

---

## 🎯 Problèmes Corrigés

### 1. **Incohérence des statuts**
- **Avant**: Le champ `purchases.session_status` n'était pas toujours synchronisé avec `active_game_sessions_v2.status`
- **Après**: Synchronisation automatique via trigger `sync_session_to_purchase`

### 2. **Mise à jour manuelle redondante**
- **Avant**: Chaque endpoint devait manuellement mettre à jour `purchases.session_status`
- **Après**: Le trigger gère automatiquement la synchronisation

### 3. **Gestion des réservations**
- **Avant**: Logique de réservation incomplète dans le callback de paiement
- **Après**: Gestion complète avec distinction achat standard vs réservation

### 4. **Doublons de tables**
- **Avant**: Confusion entre `game_sessions` (ancienne) et `active_game_sessions_v2` (nouvelle)
- **Après**: Utilisation exclusive de `active_game_sessions_v2`

---

## 🚀 Installation

### Étape 1: Appliquer la migration SQL

Exécutez le fichier de migration dans votre base de données MySQL:

```bash
mysql -u root -p gamezone < api/migrations/fix_session_purchase_sync.sql
```

Ou via phpMyAdmin:
1. Ouvrez phpMyAdmin
2. Sélectionnez la base de données `gamezone`
3. Cliquez sur l'onglet "SQL"
4. Copiez-collez le contenu de `api/migrations/fix_session_purchase_sync.sql`
5. Cliquez sur "Exécuter"

### Étape 2: Vérification

Après l'exécution, vous verrez un rapport de migration indiquant:
- ✅ Procédures créées
- ✅ Triggers créés
- ✅ Vues créées
- ✅ Synchronisation initiale effectuée

---

## 📊 Composants Créés

### 🔄 Procédures Stockées

#### 1. `sync_purchase_session_status()`
**Utilité**: Synchronise manuellement tous les statuts incohérents

```sql
CALL sync_purchase_session_status();
```

Cette procédure:
- Synchronise `purchases.session_status` avec `active_game_sessions_v2.status`
- Marque comme `pending` les achats complétés sans session
- Marque comme `cancelled` les achats échoués
- Retourne un rapport de synchronisation

#### 2. `activate_invoice()` (Améliorée)
**Changements**:
- ✅ Crée automatiquement une session dans `active_game_sessions_v2`
- ✅ Met à jour `purchases.session_status` à `ready`
- ✅ Enregistre tous les événements et audits

#### 3. `start_session()` (Améliorée)
**Changements**:
- ✅ Met à jour automatiquement `purchases.session_status` à `active`
- ✅ Synchronise le statut dans la table purchases

#### 4. `countdown_active_sessions()` (Améliorée)
**Changements**:
- ✅ S'appuie sur le trigger pour la synchronisation automatique
- ✅ Gère correctement l'expiration des sessions

### 🎯 Triggers

#### 1. `after_purchase_completed` (Amélioré)
**Déclenchement**: Après qu'un achat passe à `payment_status = 'completed'`

**Actions**:
- Crée automatiquement une facture avec code QR
- Initialise `session_status` à `pending`
- Distingue les réservations des achats standards

#### 2. `sync_session_to_purchase` (Nouveau) ⭐
**Déclenchement**: Après chaque modification de `active_game_sessions_v2.status`

**Action**:
- Synchronise automatiquement `purchases.session_status` avec le nouveau statut

**Impact**: Plus besoin de mise à jour manuelle dans le code PHP!

### 📈 Vues

#### `purchase_session_overview`
Vue consolidée pour surveiller la cohérence du système:

```sql
SELECT * FROM purchase_session_overview 
WHERE sync_status = 'MISMATCH';
```

**Colonnes importantes**:
- `purchase_session_status`: Statut dans la table purchases
- `actual_session_status`: Statut réel dans active_game_sessions_v2
- `sync_status`: `SYNCED`, `MISMATCH`, ou `NO_SESSION`

---

## 🔍 Vérifications

### Vérifier la cohérence globale

```sql
-- Vérifier les incohérences
SELECT 
  p.id,
  p.session_status as purchase_status,
  s.status as session_status,
  p.game_name
FROM purchases p
INNER JOIN active_game_sessions_v2 s ON p.id = s.purchase_id
WHERE p.session_status != s.status;
```

**Résultat attendu**: 0 lignes (aucune incohérence)

### Vérifier le trigger de synchronisation

```sql
-- Tester le trigger
UPDATE active_game_sessions_v2 
SET status = 'paused' 
WHERE id = 1;

-- Vérifier que purchases a été mis à jour
SELECT p.session_status 
FROM purchases p
INNER JOIN active_game_sessions_v2 s ON p.id = s.purchase_id
WHERE s.id = 1;
```

**Résultat attendu**: `session_status = 'paused'`

### Vérifier les statistiques

```sql
-- Statistiques de synchronisation
SELECT 
  sync_status,
  COUNT(*) as count
FROM purchase_session_overview
GROUP BY sync_status;
```

---

## 📝 Flux de Travail Amélioré

### 1. **Création d'un achat**

```
Utilisateur achète
    ↓
create_purchase.php crée l'achat avec payment_status='pending'
    ↓
payment_callback.php reçoit confirmation
    ↓
payment_status='completed' + session_status='pending'
    ↓
[TRIGGER] after_purchase_completed
    ↓
Facture créée automatiquement
```

### 2. **Activation via scan**

```
Admin scanne le code QR
    ↓
scan_invoice.php appelle activate_invoice()
    ↓
[PROCÉDURE] activate_invoice
    - Crée session dans active_game_sessions_v2
    - Met status='ready'
    - Met purchases.session_status='ready'
    ↓
[PROCÉDURE] start_session est appelée
    - Met session.status='active'
    ↓
[TRIGGER] sync_session_to_purchase
    - Met purchases.session_status='active' AUTOMATIQUEMENT
```

### 3. **Gestion de la session**

```
Admin pause/reprend/termine
    ↓
manage_session.php modifie active_game_sessions_v2.status
    ↓
[TRIGGER] sync_session_to_purchase
    ↓
purchases.session_status synchronisé AUTOMATIQUEMENT
```

### 4. **Décompte automatique**

```
Cron exécute countdown_active_sessions()
    ↓
Sessions actives mises à jour
    ↓
Si temps écoulé: status='completed'
    ↓
[TRIGGER] sync_session_to_purchase
    ↓
purchases.session_status='completed' AUTOMATIQUEMENT
```

---

## 🎯 Avantages des Corrections

### ✅ Cohérence Garantie
- Synchronisation automatique via trigger
- Plus de risque d'oubli de mise à jour manuelle
- Code PHP simplifié

### ✅ Performance
- Moins de requêtes SQL dans le code PHP
- Logique centralisée dans la base de données
- Exécution atomique avec transactions

### ✅ Maintenabilité
- Un seul point de vérité pour la synchronisation (le trigger)
- Code plus propre et lisible
- Facilite les évolutions futures

### ✅ Fiabilité
- Impossible d'avoir des incohérences entre tables
- Synchronisation même en cas d'erreur dans le code PHP
- Audit et traçabilité complète

---

## 🛠️ Maintenance

### Vérification quotidienne

Ajoutez cette requête à votre tableau de bord admin:

```sql
SELECT * FROM purchase_session_overview 
WHERE sync_status = 'MISMATCH'
LIMIT 10;
```

### Synchronisation de secours

Si vous détectez des incohérences (ce qui ne devrait pas arriver), exécutez:

```sql
CALL sync_purchase_session_status();
```

### Logs et monitoring

Surveillez les tables:
- `session_events`: Tous les événements de session
- `invoice_audit_log`: Tous les audits de factures
- `invoice_scans`: Historique des scans

---

## 📊 Statistiques Utiles

### Achats par statut de session

```sql
SELECT 
  session_status,
  COUNT(*) as count,
  SUM(price) as total_amount
FROM purchases
WHERE payment_status = 'completed'
GROUP BY session_status
ORDER BY count DESC;
```

### Sessions actives actuellement

```sql
SELECT 
  COUNT(*) as total_active,
  SUM(remaining_minutes) as total_minutes_remaining
FROM active_game_sessions_v2
WHERE status = 'active';
```

### Taux de conversion achat → session active

```sql
SELECT 
  COUNT(CASE WHEN payment_status='completed' THEN 1 END) as total_paid,
  COUNT(CASE WHEN session_status='active' THEN 1 END) as active_sessions,
  ROUND(
    COUNT(CASE WHEN session_status='active' THEN 1 END) * 100.0 / 
    COUNT(CASE WHEN payment_status='completed' THEN 1 END),
    2
  ) as conversion_rate_percent
FROM purchases;
```

---

## 🚨 Troubleshooting

### Problème: "Trigger déjà existant"
**Solution**: Le script DROP automatiquement les anciens triggers. Si erreur, supprimez manuellement:
```sql
DROP TRIGGER IF EXISTS sync_session_to_purchase;
```

### Problème: "Incohérences détectées"
**Solution**: Exécutez la procédure de synchronisation:
```sql
CALL sync_purchase_session_status();
```

### Problème: "Session ne démarre pas"
**Vérifications**:
1. Vérifier que la facture est bien activée (`invoices.status='active'`)
2. Vérifier que la session existe (`active_game_sessions_v2.status='ready'`)
3. Vérifier les logs dans `session_events`

---

## 📞 Support

En cas de problème:
1. Vérifiez les logs dans `session_events` et `invoice_audit_log`
2. Exécutez la requête de diagnostic:
   ```sql
   SELECT * FROM purchase_session_overview WHERE sync_status != 'SYNCED';
   ```
3. Consultez les statistiques de la vue pour identifier les patterns

---

## 🎉 Résumé

Les corrections apportées garantissent:
- ✅ **100% de cohérence** entre achats et sessions
- ✅ **Synchronisation automatique** via triggers
- ✅ **Code PHP simplifié** et maintenable
- ✅ **Performance optimisée** avec moins de requêtes
- ✅ **Traçabilité complète** de tous les changements

Le système est maintenant **professionnel, robuste et évolutif**! 🚀
