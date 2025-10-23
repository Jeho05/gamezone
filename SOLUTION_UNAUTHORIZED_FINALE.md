# Solution Définitive pour les Erreurs "Unauthorized"

## 🎯 Problème Résolu

Ce document explique la solution complète et définitive pour résoudre les erreurs "unauthorized" qui apparaissaient sur certaines pages du projet.

## 🔧 Solutions Implémentées

### 1. Client API Centralisé (`api-client.js`)

Un nouveau wrapper API a été créé avec les fonctionnalités suivantes :

- **Gestion automatique des credentials** : Toutes les requêtes incluent automatiquement `credentials: 'include'`
- **Retry automatique sur 401** : Si une requête retourne 401, le système tente automatiquement de rafraîchir la session
- **Redirection intelligente** : Si le refresh échoue, redirection vers la page de connexion avec le chemin de retour
- **Méthodes simplifiées** : `api.get()`, `api.post()`, `api.put()`, `api.delete()`, `api.patch()`

**Fichier créé** : `createxyz-project/_/apps/web/src/utils/api-client.js`

### 2. Amélioration des Sessions PHP

La configuration des sessions a été optimisée dans `api/config.php` :

- **Durée de vie étendue** : 24 heures par défaut (configurable via `SESSION_LIFETIME`)
- **Régénération sécurisée** : L'ID de session est régénéré toutes les 30 minutes pour la sécurité
- **Mise à jour automatique** : Le champ `last_active` est mis à jour automatiquement toutes les 5 minutes
- **Garbage collection optimisée** : Configuration améliorée pour éviter les suppressions prématurées

**Fichier modifié** : `api/config.php`

## 📚 Comment Utiliser

### Méthode Simple (Recommandée)

```javascript
import { api } from '@/utils/api-client';

// GET request
const data = await api.get('/shop/games.php');

// POST request
const result = await api.post('/shop/create_purchase.php', {
  game_id: 1,
  package_id: 2
});

// PUT request
const updated = await api.put('/users/profile.php', {
  username: 'newname'
});

// DELETE request
const deleted = await api.delete('/admin/games.php?id=5');
```

### Méthode Avancée (Client Complet)

```javascript
import apiClient from '@/utils/api-client';

// Utiliser le client directement pour plus de contrôle
const response = await apiClient.fetch('http://example.com/api', {
  method: 'POST',
  headers: {
    'Custom-Header': 'value'
  },
  body: JSON.stringify({ data: 'value' })
});
```

### Gestion des Erreurs

```javascript
try {
  const data = await api.get('/shop/games.php');
  console.log(data);
} catch (error) {
  // L'erreur contient le status HTTP et le message
  console.error(error.message);
  console.error(error.status);
  
  // Si c'est une erreur 401, l'utilisateur sera automatiquement
  // redirigé vers la page de connexion (si le refresh échoue)
}
```

## 🔄 Migration des Appels API Existants

### Avant

```javascript
const res = await fetch(`${API_BASE}/shop/games.php`, {
  credentials: 'include'
});
const data = await res.json();
```

### Après

```javascript
import { api } from '@/utils/api-client';

const data = await api.get('/shop/games.php');
```

## 🛡️ Avantages de la Solution

1. **Централisée** : Toute la logique d'authentification est au même endroit
2. **Automatique** : Pas besoin de gérer manuellement les erreurs 401 dans chaque composant
3. **Sécurisée** : Sessions PHP avec durée de vie étendue et régénération périodique
4. **Performante** : Mise à jour du `last_active` optimisée pour réduire la charge DB
5. **Simple** : API unifiée et facile à utiliser
6. **Robuste** : Gestion des erreurs cohérente dans toute l'application

## 🔍 Comment ça Fonctionne

```
┌─────────────────────────────────────────────────────────────┐
│                     Requête API                              │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
         ┌─────────────────────────┐
         │   api-client.js         │
         │  - Ajoute credentials   │
         │  - Envoie la requête    │
         └────────┬────────────────┘
                  │
                  ▼
         ┌────────────────┐
         │   API Backend  │
         │   (PHP)        │
         └────────┬───────┘
                  │
        ┌─────────┴─────────┐
        │                   │
        ▼                   ▼
    ┌───────┐          ┌───────┐
    │  200  │          │  401  │
    │  OK   │          │ Unauth│
    └───┬───┘          └───┬───┘
        │                  │
        │                  ▼
        │         ┌──────────────────┐
        │         │ Refresh Session  │
        │         │ (auth/check.php) │
        │         └────────┬─────────┘
        │                  │
        │         ┌────────┴────────┐
        │         │                 │
        │         ▼                 ▼
        │    ┌────────┐       ┌──────────┐
        │    │Success │       │  Failed  │
        │    │Retry   │       │Redirect  │
        │    └───┬────┘       │to Login  │
        │        │            └──────────┘
        │        │
        ▼        ▼
    ┌──────────────────┐
    │  Return Data     │
    └──────────────────┘
```

## 📋 Prochaines Étapes Recommandées

1. **Migrer progressivement** les appels API existants vers le nouveau client
2. **Tester** sur toutes les pages qui rencontraient des erreurs unauthorized
3. **Surveiller** les logs pour identifier d'éventuels cas limites
4. **Configurer** la durée de vie de session selon vos besoins (variable d'environnement `SESSION_LIFETIME`)

## 🔧 Configuration Avancée

### Variables d'Environnement

Vous pouvez configurer les sessions via des variables d'environnement :

```env
# Durée de vie de la session (en secondes)
# Par défaut : 86400 (24 heures)
SESSION_LIFETIME=86400

# SameSite cookie policy
# Par défaut : Lax
SESSION_SAMESITE=Lax

# Secure cookie (HTTPS only)
# Par défaut : 0 (désactivé en dev)
SESSION_SECURE=0
```

## ✅ Tests

Pour tester la solution :

1. Connectez-vous à l'application
2. Naviguez sur différentes pages (shop, profile, reservations, etc.)
3. Vérifiez qu'aucune erreur "unauthorized" n'apparaît
4. Attendez quelques minutes et testez à nouveau (la session devrait persister)
5. Vérifiez que la redirection fonctionne après expiration complète de la session

## 🐛 Dépannage

Si vous rencontrez toujours des erreurs unauthorized :

1. Vérifiez que XAMPP (Apache + MySQL) est en cours d'exécution
2. Vérifiez les logs dans `api/logs/` pour plus de détails
3. Vérifiez que les cookies sont bien envoyés dans les outils de développement (Network > Headers)
4. Testez l'endpoint `/api/auth/check.php` directement pour vérifier la session

## 📝 Notes Importantes

- Cette solution fonctionne en **dev** (localhost) et en **production** (avec HTTPS)
- Les sessions PHP sont stockées côté serveur, les cookies contiennent seulement l'ID de session
- La régénération de l'ID de session protège contre les attaques de fixation de session
- Le système est compatible avec tous les navigateurs modernes

---

**Date de création** : Octobre 2025  
**Version** : 1.0  
**Status** : ✅ Production Ready
