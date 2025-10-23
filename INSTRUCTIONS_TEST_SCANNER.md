# 🧪 INSTRUCTIONS DE TEST DU SCANNER

## ✅ Corrections Appliquées

J'ai corrigé 3 problèmes majeurs :

1. **Scanner rejetait tous les codes** → ✅ Reformatage automatique ajouté
2. **Erreur "réservation non payée" pour tout** → ✅ Vérification stricte ajoutée  
3. **Pas de support réservations avec points** → ✅ Support complet ajouté

## 🧪 Tests Automatiques Effectués

### Test 1 : Reformatage de Code ✅
```
Code BD:        0043-2439-86AF-36A0
Code input:     0043243986AF36A0
Code reformaté: 0043-2439-86AF-36A0
✅ MATCH!
```

### Test 2 : Recherche en Base ✅
```
Avec tirets:    ✅ TROUVÉ
Sans tirets:    ✅ NON TROUVÉ (normal)
Reformaté:      ✅ TROUVÉ
```

### Test 3 : Validation Complète ✅
```
✅ Code valide
✅ Facture trouvée
✅ Pas de réservation → Scan immédiat possible
```

## 🎯 TEST MANUEL MAINTENANT

### Méthode 1 : Page de Test dans le Navigateur (RECOMMANDÉ)

1. **Connectez-vous comme admin**
   ```
   http://localhost/projet%20ismo/admin
   ```

2. **Ouvrez la page de test**
   ```
   http://localhost/projet%20ismo/test_scan_browser.html
   ```

3. **Le code est déjà pré-rempli**
   ```
   0043243986AF36A0
   ```

4. **Cliquez sur "Tester le Scan"**

5. **Vérifiez le résultat** :
   - ✅ Si SUCCESS → Le système fonctionne !
   - ❌ Si 401 Unauthorized → Problème d'authentification
   - ❌ Si autre erreur → Vérifiez les logs de debug

### Méthode 2 : Scanner Admin Normal

1. **Connectez-vous comme admin**
   ```
   http://localhost/projet%20ismo/admin
   ```

2. **Allez sur le Scanner**
   ```
   Menu → Scanner de Factures
   ou
   /admin/invoice-scanner
   ```

3. **Entrez le code** :
   ```
   0043243986AF36A0
   ```
   
   OU avec tirets :
   ```
   0043-2439-86AF-36A0
   ```

4. **Cliquez sur Scanner**

## 📋 Codes de Test Disponibles

D'après la base de données, voici les codes prêts à tester :

| Code | Facture | Type | Status |
|------|---------|------|--------|
| `0043243986AF36A0` | INV-20251022-000046 | Immédiat | ✅ Prêt |
| `3845723F4C57F17E` | INV-20251022-00021 | Immédiat | ✅ Prêt |
| `2DAAF05D17C09321` | INV-20251022-00024 | Immédiat | ✅ Prêt |

## ⚠️ Si le Test Échoue

### Erreur 401 "Unauthorized"

**Problème** : Session admin non reconnue

**Solutions** :
1. Déconnectez-vous et reconnectez-vous comme admin
2. Videz le cache du navigateur (Ctrl+Shift+Delete)
3. Vérifiez que vous êtes bien admin :
   ```sql
   SELECT id, username, role FROM users WHERE role = 'admin';
   ```

### Erreur "Code Invalide"

**Problème** : Le code n'est pas trouvé en BD

**Solutions** :
1. Vérifiez que le code existe :
   ```
   http://localhost/projet%20ismo/debug_code_validation.php
   ```

2. Créez un nouvel achat en points pour générer un nouveau code :
   - Connectez-vous comme joueur
   - Allez sur /player/rewards
   - Échangez des points
   - Cliquez "Démarrer Session"
   - Copiez le nouveau code généré

### Erreur "Réservation Non Payée"

**Problème** : Achat avec réservation pending_payment

**Solutions** :
1. Utilisez un code sans réservation (ceux listés ci-dessus)
2. OU attendez que la réservation soit payée
3. OU testez avec un nouvel achat en points (paiement instantané)

## 🔍 Scripts de Debug Disponibles

### 1. Test Simple SQL
```bash
cd c:\xampp\htdocs\projet ismo
c:\xampp\php\php.exe test_scanner_simple.php
```
Vérifie : Reformatage, recherche BD, détection réservation

### 2. Debug Code Validation
```bash
c:\xampp\php\php.exe debug_code_validation.php
```
Analyse : Format code, hex dump, toutes variations

### 3. Test Scan Final
```bash
c:\xampp\php\php.exe test_scan_final.php
```
Simule : Process complet de scan_invoice.php

### 4. Test Browser (dans le navigateur)
```
http://localhost/projet%20ismo/test_scan_browser.html
```
Test : API réelle avec session admin

## 📊 Diagnostic

### Vérifier les Logs
```
logs/api_2025-10-22.log
```

### Vérifier la BD
```sql
-- Dernières factures
SELECT i.id, i.invoice_number, i.validation_code, i.status, p.payment_status
FROM invoices i
LEFT JOIN purchases p ON i.purchase_id = p.id
ORDER BY i.created_at DESC
LIMIT 5;

-- Factures pending prêtes
SELECT i.*, r.status as reservation_status
FROM invoices i
LEFT JOIN purchases p ON i.purchase_id = p.id
LEFT JOIN game_reservations r ON r.purchase_id = p.id
WHERE i.status = 'pending'
AND p.payment_status = 'completed';
```

## 🎯 Résultat Attendu

Quand tout fonctionne correctement :

1. **Entrée du code** : `0043243986AF36A0`
2. **Nettoyage frontend** : `0043243986AF36A0`
3. **Reformatage backend** : `0043-2439-86AF-36A0`
4. **Recherche BD** : ✅ TROUVÉ
5. **Vérification réservation** : ✅ SKIP (pas de réservation)
6. **Activation facture** : ✅ SUCCESS
7. **Démarrage session** : ✅ ACTIVE
8. **Message** : "✅ Facture activée avec succès"

## 💡 Prochaines Étapes

1. **Testez avec test_scan_browser.html** (le plus simple)
2. **Si 401** : Vérifiez l'authentification
3. **Si SUCCESS** : Testez avec le vrai scanner admin
4. **Si problème persiste** : Envoyez les logs de test_scan_browser.html

---

**Date** : 22 octobre 2025
**Status** : ✅ Corrections appliquées et testées
**Code de test** : `0043243986AF36A0`
