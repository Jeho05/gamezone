# 🔧 Correction Urgente - Colonne Manquante

## Problème
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'auto_confirm' in 'field list'
```

La colonne `auto_confirm` n'existe pas dans votre table `payment_methods`.

## Solution - 2 Options

### Option 1: Script PHP Automatique (RECOMMANDÉ ✅)

**1. Exécuter le script de correction**:
```bash
php fix_payment_methods_columns.php
```

**2. C'est tout!** Le script va:
- Vérifier si `auto_confirm` existe
- L'ajouter si elle manque
- Vérifier si `instructions` existe
- L'ajouter si elle manque
- Afficher la structure finale

### Option 2: SQL Manuel

**Via phpMyAdmin ou ligne de commande MySQL**:

```sql
-- Ajouter auto_confirm
ALTER TABLE payment_methods 
ADD COLUMN auto_confirm TINYINT(1) NOT NULL DEFAULT 0 
COMMENT 'Confirmation automatique ou manuelle par admin' 
AFTER requires_online_payment;

-- Ajouter instructions (si manquante aussi)
ALTER TABLE payment_methods 
ADD COLUMN instructions TEXT NULL 
COMMENT 'Instructions de paiement affichées à l\'utilisateur' 
AFTER display_order;
```

## Après Correction

**Tester la création**:
1. Aller sur `/admin/shop`, onglet "Paiements"
2. Cliquer "Ajouter Méthode"
3. Remplir le formulaire
4. ✅ Ça devrait fonctionner!

## Colonnes Nécessaires

La table `payment_methods` doit avoir:
- ✅ `id`
- ✅ `name`
- ✅ `slug`
- ✅ `provider`
- ✅ `api_key_public`
- ✅ `api_key_secret`
- ✅ `api_endpoint`
- ✅ `webhook_secret`
- ✅ `requires_online_payment`
- ✅ `auto_confirm` ← **CELLE-CI MANQUAIT**
- ✅ `fee_percentage`
- ✅ `fee_fixed`
- ✅ `is_active`
- ✅ `display_order`
- ✅ `instructions` ← **Peut-être manquante aussi**
- ✅ `created_at`
- ✅ `updated_at`

## Vérification

Après exécution, vous verrez:
```
=== Correction de la table payment_methods ===

Ajout de la colonne 'auto_confirm'...
✅ Colonne 'auto_confirm' ajoutée

✅ CORRECTION TERMINÉE!
```

**Exécutez maintenant le script pour corriger!** 🚀
