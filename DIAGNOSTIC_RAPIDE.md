# 🔍 Diagnostic Rapide - Problème de Motif

## Le motif de désactivation ne s'affiche pas correctement ?

### Étape 1 : Vérifier l'État du Système

**Ouvrir dans votre navigateur :**
```
http://localhost/projet%20ismo/api/diagnostic_deactivation.php
```

Cette page vous montrera :
- ✅ Si la migration a été exécutée
- ✅ Les utilisateurs désactivés et leurs motifs
- ✅ L'historique des désactivations
- ✅ Les problèmes éventuels

---

### Étape 2 : Exécuter la Migration (si nécessaire)

Si le diagnostic montre que la migration n'est PAS exécutée :

#### Via phpMyAdmin :

1. Ouvrir : `http://localhost/phpmyadmin`
2. Sélectionner la base `gamezone`
3. Onglet **"SQL"**
4. Copier/coller :

```sql
ALTER TABLE users ADD COLUMN IF NOT EXISTS deactivation_reason TEXT NULL AFTER status;
ALTER TABLE users ADD COLUMN IF NOT EXISTS deactivation_date DATETIME NULL AFTER deactivation_reason;
ALTER TABLE users ADD COLUMN IF NOT EXISTS deactivated_by INT NULL AFTER deactivation_date;
```

5. Cliquer **"Exécuter"**

---

### Étape 3 : Tester la Désactivation

1. **Se connecter en tant qu'admin**
2. **Aller sur le profil d'un joueur**
3. **Cliquer sur "Désactiver"**
4. **Saisir un motif spécifique**, par exemple :
   ```
   Test de désactivation - Langage inapproprié
   ```
5. **Confirmer**

---

### Étape 4 : Vérifier le Motif Enregistré

**Retourner sur le diagnostic :**
```
http://localhost/projet%20ismo/api/diagnostic_deactivation.php
```

Vous devriez voir :
- Le joueur dans la liste des "Utilisateurs Désactivés"
- Le motif exact que vous avez saisi
- La date de désactivation
- L'ID de l'admin qui a désactivé

---

### Étape 5 : Tester la Connexion

1. **Se déconnecter**
2. **Essayer de se connecter avec le compte désactivé**
3. **Vérifier que le motif s'affiche** :

```
Votre compte a été désactivé.

Motif: Test de désactivation - Langage inapproprié

Date de désactivation: 14/10/2025 à 21:14

Veuillez contacter un administrateur pour plus d'informations.
```

---

## 🐛 Problèmes Courants

### Le motif affiche "Compte désactivé par un administrateur"

**Cause :** La migration n'a pas été exécutée.

**Solution :** Suivre l'Étape 2 ci-dessus.

---

### Le motif affiche "Compte désactivé - Sanction administrative"

**Cause 1 :** La migration n'a pas été exécutée.

**Cause 2 :** Le champ "Motif de désactivation" était vide lors de la désactivation.

**Solution :**
1. Exécuter la migration (Étape 2)
2. Réactiver le compte
3. Le désactiver à nouveau avec un motif rempli

---

### Erreur "JSON.parse: unexpected character..."

**Cause :** Problème de serveur PHP ou migration incomplète.

**Solution :**
1. Vérifier que XAMPP/Apache est démarré
2. Vérifier le diagnostic
3. Regarder les logs d'erreur PHP : `C:\xampp\php\logs\php_error_log`

---

## ✅ Vérification Rapide en SQL

Pour vérifier directement en base de données :

```sql
-- Voir les utilisateurs désactivés avec leurs motifs
SELECT id, username, email, status, deactivation_reason, deactivation_date 
FROM users 
WHERE status = 'inactive';

-- Voir l'historique des désactivations
SELECT user_id, change_amount, reason, created_at 
FROM points_transactions 
WHERE reason LIKE 'Compte désactivé%' 
ORDER BY created_at DESC;
```

---

## 📞 Support

Si le problème persiste après avoir suivi ces étapes :

1. **Ouvrir le diagnostic** et faire une capture d'écran
2. **Vérifier les logs d'erreur PHP**
3. **Vérifier la console du navigateur** (F12)
4. **Partager ces informations** pour un diagnostic plus approfondi

---

## 🎯 Checklist de Dépannage

- [ ] La migration SQL a été exécutée
- [ ] Le diagnostic montre que les colonnes existent
- [ ] Le champ "Motif de désactivation" a été rempli avant de cliquer sur "Confirmer"
- [ ] La page a été actualisée après la désactivation
- [ ] Le cache du navigateur a été vidé (Ctrl+F5)
- [ ] Le motif s'affiche dans le diagnostic
- [ ] Le motif s'affiche lors de la tentative de connexion
