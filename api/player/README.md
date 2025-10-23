# API Player Endpoints

Ces endpoints fournissent des informations complètes sur les joueurs et leurs statistiques de gamification.

## Endpoints Disponibles

### 1. `/api/player/leaderboard.php`
**Méthode:** GET  
**Authentification:** Optionnelle (mais recommandée pour voir sa position)

Affiche le classement des joueurs avec des informations détaillées.

**Paramètres:**
- `period` (optionnel): `weekly` | `monthly` | `all` (défaut: `weekly`)
- `limit` (optionnel): 1-100 (défaut: 50)

**Exemple de requête:**
```
GET http://localhost:4000/api/player/leaderboard.php?period=weekly&limit=50
```

**Réponse:**
```json
{
  "success": true,
  "leaderboard": {
    "period": "weekly",
    "period_label": "Semaine du 14/10 au 20/10/2025",
    "start_date": "2025-10-14 00:00:00",
    "end_date": "2025-10-16 14:38:00",
    "total_players": 150,
    "total_points_distributed": 45000,
    "showing_top": 50,
    "rankings": [
      {
        "rank": 1,
        "user": {
          "id": 42,
          "username": "ProGamer123",
          "avatar_url": "https://...",
          "level": 12,
          "level_info": {
            "name": "Master",
            "color": "#FFD700",
            "points_required": 10000
          }
        },
        "points": 2500,
        "total_points": 15000,
        "badges_earned": 15,
        "active_days": 6,
        "recent_activity": 45,
        "rank_change": 2,
        "is_current_user": false
      }
    ]
  },
  "current_user": {
    "rank": 15,
    "user": { ... },
    "points": 1200,
    "total_points": 8500,
    "is_current_user": true
  },
  "generated_at": "2025-10-16 14:38:00"
}
```

**Informations fournies:**
- Rang avec gestion des égalités
- Informations utilisateur complètes
- Points de la période et totaux
- Badges gagnés
- Jours d'activité
- Activité récente (7 derniers jours)
- Changement de rang (comparé à la période précédente)
- Position de l'utilisateur connecté
- Statistiques globales du classement

---

### 2. `/api/player/gamification.php`
**Méthode:** GET  
**Authentification:** Requise

Retourne toutes les informations de gamification d'un joueur.

**Paramètres:**
- `user_id` (optionnel): ID de l'utilisateur (défaut: utilisateur connecté)
  - Les non-admins ne peuvent voir que leurs propres stats

**Exemple de requête:**
```
GET http://localhost:4000/api/player/gamification.php
Authorization: Bearer <token>
```

**Réponse:**
```json
{
  "success": true,
  "user": {
    "id": 42,
    "username": "ProGamer123",
    "email": "player@example.com",
    "avatar_url": "https://...",
    "points": 8500,
    "level": 10,
    "member_since": "2025-01-15 10:00:00",
    "days_active": 90,
    "last_login": "2025-10-16 12:00:00"
  },
  "level_progression": {
    "current": {
      "number": 10,
      "name": "Expert",
      "points_required": 8000,
      "color": "#4CAF50",
      "points_bonus": 50
    },
    "next": {
      "number": 11,
      "name": "Master",
      "points_required": 10000,
      "color": "#FFD700",
      "points_bonus": 75,
      "points_needed": 1500
    },
    "progress_percentage": 33.33
  },
  "statistics": {
    "games_played": 125,
    "events_attended": 15,
    "tournaments_participated": 8,
    "tournaments_won": 2,
    "friends_referred": 5,
    "total_points_earned": 12000,
    "total_points_spent": 3500,
    "net_points": 8500,
    "achievements_unlocked": 20
  },
  "activity": {
    "points_last_7_days": 450,
    "points_last_30_days": 2100,
    "daily_breakdown": [
      {
        "date": "2025-10-16",
        "earned": 120,
        "spent": 50,
        "net": 70,
        "transactions": 8
      }
    ]
  },
  "streak": {
    "current": 15,
    "longest": 30,
    "last_login_date": "2025-10-16"
  },
  "badges": {
    "earned": [ ... ],
    "total_earned": 20,
    "total_available": 50,
    "completion_percentage": 40
  },
  "recent_badges": [ ... ],
  "points_history": [ ... ],
  "active_multipliers": [
    {
      "id": 5,
      "multiplier": 1.5,
      "reason": "Événement spécial",
      "starts_at": "2025-10-16 00:00:00",
      "ends_at": "2025-10-18 23:59:59",
      "time_remaining": "2 heures 21 minutes"
    }
  ],
  "rewards_redeemed": {
    "items": [ ... ],
    "total_count": 10
  },
  "leaderboard": {
    "global_rank": 15,
    "total_players": 150,
    "percentile": 90
  },
  "next_milestones": {
    "points": {
      "threshold": 10000,
      "label": "Maître",
      "remaining": 1500
    },
    "days_active": {
      "threshold": 180,
      "label": "6 mois",
      "remaining": 90
    }
  },
  "generated_at": "2025-10-16 14:38:00"
}
```

**Informations complètes fournies:**
- Profil utilisateur complet
- Progression de niveau avec pourcentage
- Statistiques détaillées (jeux, événements, tournois, etc.)
- Activité récente (7 et 30 jours) avec détails quotidiens
- Séries de connexion (streak)
- Badges gagnés avec progression
- Historique des points (20 dernières transactions)
- Multiplicateurs actifs avec temps restant
- Récompenses échangées
- Position au classement global
- Prochains jalons à atteindre

---

## Authentification

Pour les endpoints nécessitant une authentification, incluez le token dans l'en-tête:

```
Authorization: Bearer YOUR_JWT_TOKEN
```

Ou utilisez le cookie de session si configuré.

---

## Codes d'erreur

- `400` - Paramètres invalides
- `401` - Non authentifié
- `403` - Accès interdit
- `404` - Ressource non trouvée
- `500` - Erreur serveur

---

## Notes importantes

### Leaderboard
- Les rangs égaux sont gérés (même points = même rang)
- Les calculs de changement de rang comparent avec la période précédente
- Le cache n'est pas utilisé pour l'instant mais peut être ajouté
- L'utilisateur voit toujours sa position même s'il n'est pas dans le top

### Gamification
- Les données sont en temps réel (pas de cache)
- L'historique est limité aux 20 dernières transactions
- Les multiplicateurs expirés ne sont pas affichés
- Les statistiques quotidiennes couvrent les 7 derniers jours
- Les jalons sont calculés dynamiquement

---

## Performances

Ces endpoints sont optimisés avec:
- Requêtes SQL efficaces avec index appropriés
- Limitation des résultats
- Calculs groupés
- Pas de données inutiles

Pour de meilleures performances en production, considérez:
- Ajouter du cache Redis pour le leaderboard
- Paginer l'historique des points
- Mettre en cache les badges disponibles
