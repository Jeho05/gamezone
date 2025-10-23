# Système de Gamification - GameZone

## 📋 Vue d'ensemble

Le système de gamification complet est maintenant implémenté avec :
- ✅ Règles de points automatiques
- ✅ Système de niveaux avec progression
- ✅ Badges et achievements
- ✅ Bonus journaliers et streaks de connexion
- ✅ Multiplicateurs de bonus
- ✅ Statistiques détaillées
- ✅ Calcul correct des points dépensés

## 🚀 Installation

### 1. Appliquer la migration

```powershell
cd c:\xampp\htdocs\projet ismo\api\migrations
php apply_gamification.php
```

Cela va créer toutes les tables nécessaires et initialiser les données.

## 📊 Structure des tables

### Tables créées

1. **badges** - Catalogue des badges disponibles
2. **user_badges** - Badges obtenus par les utilisateurs
3. **levels** - Niveaux de progression (1-10)
4. **points_rules** - Règles d'attribution automatique de points
5. **login_streaks** - Suivi des séries de connexion
6. **bonus_multipliers** - Multiplicateurs de bonus temporaires
7. **user_stats** - Statistiques détaillées par utilisateur

## 🎮 Règles de points

### Actions automatiques

| Action | Points | Endpoint |
|--------|--------|----------|
| Partie jouée | 10 pts | `POST /api/gamification/award_points.php` |
| Événement participé | 50 pts | `POST /api/gamification/award_points.php` |
| Tournoi participé | 100 pts | `POST /api/gamification/award_points.php` |
| Tournoi gagné | 500 pts | `POST /api/gamification/award_points.php` |
| Ami parrainé | 200 pts | `POST /api/gamification/award_points.php` |
| Connexion quotidienne | 5 pts | `POST /api/gamification/login_streak.php` |
| Profil complété | 100 pts | `POST /api/gamification/award_points.php` |
| Premier achat | 150 pts | `POST /api/gamification/award_points.php` |
| Commentaire écrit | 30 pts | `POST /api/gamification/award_points.php` |
| Partage social | 20 pts | `POST /api/gamification/award_points.php` |

### Exemple d'utilisation

```javascript
// Attribuer des points pour une partie jouée
POST /api/gamification/award_points.php
{
  "action_type": "game_played"
}

// Retour:
{
  "message": "Points attribués",
  "points_awarded": 10,
  "new_total": 1250,
  "multiplier": 1.0,
  "leveled_up": false,
  "badges_earned": []
}
```

## 🏆 Système de niveaux

### Progression (10 niveaux)

| Niveau | Nom | Points requis | Bonus |
|--------|-----|---------------|-------|
| 1 | Novice | 0 | 0 |
| 2 | Joueur | 100 | 50 |
| 3 | Passionné | 300 | 100 |
| 4 | Expert | 600 | 150 |
| 5 | Maître | 1000 | 250 |
| 6 | Champion | 1500 | 400 |
| 7 | Légende | 2500 | 600 |
| 8 | Élite | 4000 | 1000 |
| 9 | Titan | 6000 | 1500 |
| 10 | Dieu du Gaming | 10000 | 2500 |

### API Niveaux

```javascript
// Obtenir les niveaux et progression d'un utilisateur
GET /api/gamification/levels.php?user_id=123

// Retour:
{
  "user": {
    "points": 1250,
    "current_level": { "number": 5, "name": "Maître", ... },
    "next_level": { "number": 6, "name": "Champion", ... },
    "progress_percentage": 50,
    "points_to_next": 250
  },
  "all_levels": [...]
}
```

## 🎖️ Système de badges

### Types de badges

- **Points** : Basés sur le total de points
- **Activité** : Basés sur les actions (parties jouées, événements)
- **Social** : Basés sur le parrainage
- **Achievement** : Accomplissements spéciaux

### Raretés

- 🟢 **Common** (Commun)
- 🔵 **Rare** (Rare)
- 🟣 **Epic** (Épique)
- 🟡 **Legendary** (Légendaire)

### Badges par défaut

1. **Première Connexion** 🎮 - Se connecter pour la première fois (10 pts)
2. **Débutant** 🌟 - Atteindre 100 points (25 pts)
3. **Collectionneur** 💎 - Atteindre 500 points (50 pts)
4. **Maître des Points** 👑 - Atteindre 1000 points (100 pts)
5. **Légende** 🏆 - Atteindre 5000 points (500 pts)
6. **Joueur Actif** 🎯 - Jouer 10 parties (50 pts)
7. **Accro du Gaming** 🔥 - Jouer 50 parties (150 pts)
8. **Participant Assidu** 🎪 - Assister à 5 événements (100 pts)
9. **Série de 7** 📅 - Se connecter 7 jours d'affilée (200 pts)
10. **Série de 30** 🔥 - Se connecter 30 jours d'affilée (1000 pts)
11. **Social** 👥 - Parrainer 3 amis (300 pts)
12. **Recruteur** 🌐 - Parrainer 10 amis (1500 pts)

### API Badges

```javascript
// Obtenir tous les badges avec progression
GET /api/gamification/badges.php?user_id=123

// Vérifier et attribuer les badges
POST /api/gamification/check_badges.php
```

## 🔥 Système de streaks

### Bonus de série de connexion

| Série | Bonus |
|--------|-------|
| 3 jours | +5 pts |
| 7 jours | +10 pts |
| 14 jours | +25 pts |
| 30 jours | +50 pts |

### API Streak

```javascript
// Enregistrer une connexion quotidienne
POST /api/gamification/login_streak.php

// Retour:
{
  "message": "Connexion enregistrée",
  "current_streak": 7,
  "longest_streak": 10,
  "points_awarded": 15,
  "streak_bonus": 10,
  "is_new_streak": false,
  "badges_earned": []
}
```

## ⚡ Multiplicateurs de bonus

### Création (Admin uniquement)

```javascript
// Créer un multiplicateur x2 pour 24h
POST /api/gamification/bonus_multiplier.php
{
  "user_id": 123,
  "multiplier": 2.0,
  "reason": "Événement spécial",
  "duration_hours": 24
}
```

### Effet

Tous les points gagnés pendant la période active sont multipliés :
- Points base : 10
- Avec multiplicateur x2 : 20
- Avec multiplicateur x1.5 : 15

### API

```javascript
// Obtenir les multiplicateurs actifs
GET /api/gamification/bonus_multiplier.php?user_id=123

// Supprimer un multiplicateur
DELETE /api/gamification/bonus_multiplier.php?id=456
```

## 📈 Statistiques détaillées

### API Statistiques

```javascript
// Obtenir les stats complètes d'un utilisateur
GET /api/gamification/user_stats.php?user_id=123

// Retour:
{
  "user": { "id": 123, "username": "Player1", "points": 1250, "level": "Maître" },
  "statistics": {
    "games_played": 45,
    "events_attended": 8,
    "tournaments_participated": 3,
    "tournaments_won": 1,
    "friends_referred": 2,
    "total_points_earned": 2500,
    "total_points_spent": 1250,
    "net_points": 1250,
    "badges_earned": 6,
    "badges_total": 12,
    "rewards_redeemed": 3
  },
  "streak": {
    "current": 7,
    "longest": 15,
    "last_login": "2025-01-14"
  },
  "level_progression": { ... },
  "recent_achievements": [ ... ]
}
```

## 🔧 Correction du calcul des points dépensés

### Problème corrigé

Le calcul des points dépensés affichés à l'admin n'était pas toujours exact car il se basait uniquement sur `points_transactions` avec des montants négatifs.

### Solution

1. Ajout d'une table `user_stats` avec `total_points_spent`
2. Mise à jour automatique lors de chaque échange de récompense
3. Double vérification dans `admin_profile.php` entre les deux sources
4. Prise de la valeur maximale entre les deux calculs pour garantir la précision

### Code modifié

- `api/rewards/redeem.php` : Mise à jour de `user_stats.total_points_spent`
- `api/users/admin_profile.php` : Double vérification des points dépensés

## 🎯 Endpoints disponibles

### Points
- `POST /api/gamification/award_points.php` - Attribuer des points
- `GET /api/points/history.php` - Historique des points
- `POST /api/points/adjust.php` - Ajuster les points (admin)
- `POST /api/points/bonus.php` - Bonus journalier

### Badges
- `GET /api/gamification/badges.php` - Liste des badges
- `GET /api/gamification/badges.php?user_id=X` - Badges d'un utilisateur
- `POST /api/gamification/check_badges.php` - Vérifier les badges

### Niveaux
- `GET /api/gamification/levels.php` - Liste des niveaux
- `GET /api/gamification/levels.php?user_id=X` - Progression d'un utilisateur

### Streaks
- `POST /api/gamification/login_streak.php` - Enregistrer une connexion

### Multiplicateurs
- `GET /api/gamification/bonus_multiplier.php?user_id=X` - Multiplicateurs actifs
- `POST /api/gamification/bonus_multiplier.php` - Créer (admin)
- `DELETE /api/gamification/bonus_multiplier.php?id=X` - Supprimer (admin)

### Statistiques
- `GET /api/gamification/user_stats.php` - Stats utilisateur
- `GET /api/gamification/user_stats.php?user_id=X` - Stats d'un autre utilisateur (admin)

### Récompenses
- `GET /api/rewards/index.php` - Liste des récompenses
- `POST /api/rewards/redeem.php` - Échanger une récompense

## 🧪 Tests

### Test manuel

```powershell
# 1. Appliquer la migration
cd c:\xampp\htdocs\projet ismo\api\migrations
php apply_gamification.php

# 2. Tester l'attribution de points
curl -X POST http://localhost/api/gamification/award_points.php \
  -H "Content-Type: application/json" \
  -d '{"action_type": "game_played"}'

# 3. Tester le streak
curl -X POST http://localhost/api/gamification/login_streak.php

# 4. Voir les badges
curl http://localhost/api/gamification/badges.php?user_id=1

# 5. Voir les stats
curl http://localhost/api/gamification/user_stats.php?user_id=1
```

## 🎨 Intégration frontend

### Afficher les points en temps réel

```javascript
// Lors d'une action
async function playGame() {
  const response = await fetch('/api/gamification/award_points.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action_type: 'game_played' })
  });
  
  const data = await response.json();
  
  // Afficher notification
  if (data.leveled_up) {
    showNotification(`Niveau supérieur! ${data.new_level.name}`);
  }
  
  if (data.badges_earned.length > 0) {
    data.badges_earned.forEach(badge => {
      showBadgeNotification(badge);
    });
  }
  
  updateUserPoints(data.new_total);
}

// Connexion quotidienne
async function dailyLogin() {
  const response = await fetch('/api/gamification/login_streak.php', {
    method: 'POST'
  });
  
  const data = await response.json();
  updateStreakDisplay(data.current_streak);
}
```

## 📝 Notes importantes

1. **Performance** : Les vérifications de badges sont optimisées et exécutées uniquement après attribution de points
2. **Sécurité** : Seuls les admins peuvent créer des multiplicateurs et ajuster manuellement les points
3. **Atomicité** : Toutes les opérations utilisent des transactions pour garantir la cohérence
4. **Extensibilité** : Facile d'ajouter de nouveaux badges ou règles de points

## ✅ Checklist de vérification

- [x] Tables créées
- [x] Données initiales insérées (10 niveaux, 12 badges, 10 règles)
- [x] API d'attribution de points fonctionnelle
- [x] Système de niveaux automatique
- [x] Badges vérifiés automatiquement
- [x] Streaks de connexion
- [x] Multiplicateurs de bonus
- [x] Calcul correct des points dépensés
- [x] Statistiques détaillées
- [x] Intégration avec récompenses

## 🐛 Dépannage

### Les badges ne s'attribuent pas

Vérifier que `user_stats` est bien mis à jour :
```sql
SELECT * FROM user_stats WHERE user_id = X;
```

### Les niveaux ne changent pas

Vérifier les points de l'utilisateur vs les niveaux :
```sql
SELECT u.points, l.name, l.points_required 
FROM users u, levels l 
WHERE u.id = X AND l.points_required <= u.points 
ORDER BY l.points_required DESC LIMIT 1;
```

### Points dépensés incorrects

Recalculer manuellement :
```php
php api/migrations/apply_gamification.php
```
