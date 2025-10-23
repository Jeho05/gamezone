# 🎯 Plan d'Améli

orations Globales

## Demandes de l'Utilisateur

### 1. 📊 Clarifier les Statuts de Session

**Problème actuel**:
- `terminated` = Arrêtée par admin/user avant la fin
- `completed` = Arrivée à la fin normalement
- Confusion: semblent être la même chose

**Solution**:
- **Renommer et clarifier**:
  - `terminated` → `stopped` (Arrêtée manuellement avant la fin)
  - `completed` → `finished` (Terminée naturellement, temps écoulé)
  - `expired` → Reste tel quel (Facture expirée, non utilisée)

- **Affichage clair**:
  - ⏹️ **Arrêtée**: Session stoppée avant la fin (fond orange)
  - ✅ **Terminée**: Session arrivée jusqu'à la fin (fond vert)
  - ⏰ **Expirée**: Jamais utilisée, délai passé (fond gris)

### 2. 📈 Remplacer Activité Hebdomadaire

**Problème actuel**:
- Graphique hebdomadaire des joueurs/points
- Peu pertinent pour la gestion quotidienne

**Solution - Stats Pertinentes**:

```
┌─────────────────────────────────────────────────────────┐
│ REVENUS & PERFORMANCES                                  │
├─────────────────────────────────────────────────────────┤
│ [💰 Revenus Aujourd'hui]  [💳 Revenus Ce Mois]        │
│ [📊 Taux Conversion]       [⏱️ Temps Moyen Session]     │
│ [🎮 Jeu le + Populaire]   [📦 Package le + Vendu]      │
│ [⚠️ Paiements en Attente] [🎯 Points Convertis]        │
└─────────────────────────────────────────────────────────┘
```

**Cartes proposées**:
1. **Revenus Aujourd'hui**: Total des paiements confirmés aujourd'hui
2. **Revenus Ce Mois**: Total du mois en cours
3. **Taux de Conversion**: % de visiteurs qui achètent
4. **Temps Moyen**: Durée moyenne des sessions
5. **Jeu Populaire**: Le jeu le plus joué cette semaine
6. **Package Best-Seller**: Package le plus acheté
7. **Paiements en Attente**: Nombre d'achats à confirmer
8. **Points Convertis**: Combien de points ont été convertis en heures

### 3. 📸 Photos de Profil des Joueurs

**Problème actuel**:
- Avatars génériques ou absents
- Pas de système unifié de photos de profil

**Solution**:
- **API Upload Photo**: `api/users/upload_avatar.php`
- **Stockage**: `uploads/avatars/{user_id}/` avec validation
- **Affichage partout**:
  - Dashboard admin (top joueurs)
  - Gestion sessions (joueurs actifs)
  - Classement (leaderboard)
  - Profil joueur
  - Historique achats
  
- **Fallback**:
  - Si pas de photo: Initiales du nom (style Discord)
  - Ex: "John Doe" → "JD" sur fond coloré

- **Validation**:
  - Formats: JPG, PNG, WebP
  - Taille max: 2MB
  - Dimensions: 500x500px recommandé
  - Redimensionnement automatique

### 4. 💰 Conversion Points → Heures

**Problème actuel**:
- Système existe mais incomplet/non fonctionnel
- Logique backend absente
- Frontend pas intuitif

**Solution Complète**:

#### Backend (API)

**Table: `point_conversion_config`**
```sql
CREATE TABLE point_conversion_config (
  id INT PRIMARY KEY,
  points_per_minute INT NOT NULL,         -- Ex: 10 points = 1 min
  min_conversion_points INT NOT NULL,      -- Min 100 points
  max_conversion_per_day INT NULL,         -- Max 3 conversions/jour
  conversion_fee_percent DECIMAL(5,2),     -- Frais 5%
  is_active TINYINT(1) DEFAULT 1,
  updated_at DATETIME
);
```

**Table: `point_conversions`**
```sql
CREATE TABLE point_conversions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  points_spent INT NOT NULL,
  minutes_gained INT NOT NULL,
  game_id INT NULL,                        -- Jeu choisi (optionnel)
  conversion_rate INT NOT NULL,            -- Rate au moment de conversion
  status ENUM('pending', 'completed', 'cancelled'),
  created_at DATETIME,
  expires_at DATETIME,                     -- Temps de jeu à utiliser sous X jours
  FOREIGN KEY (user_id) REFERENCES users(id)
);
```

**Endpoint: `POST /api/player/convert_points.php`**
```php
Input:
{
  "points_to_convert": 500,
  "game_id": 3  // Optionnel
}

Output (success):
{
  "success": true,
  "minutes_gained": 50,
  "points_spent": 500,
  "new_balance": 1500,
  "conversion_id": 123,
  "expires_at": "2025-11-18",
  "message": "500 points convertis en 50 minutes!"
}

Output (error):
{
  "error": "Points insuffisants",
  "required": 500,
  "available": 300
}
```

#### Frontend (Interface Joueur)

**Page: `/player/convert-points`**

```
┌─────────────────────────────────────────────────────┐
│ 💰 Convertir Mes Points en Temps de Jeu            │
├─────────────────────────────────────────────────────┤
│ Solde actuel: [2,450 points] 💎                    │
│                                                      │
│ ┌─────────────────────────────────────────────┐    │
│ │ Combien de points voulez-vous convertir ?  │    │
│ │                                              │    │
│ │ [────●────────────────] (Slider)            │    │
│ │     100        500        1000       2000    │    │
│ │                                              │    │
│ │ Points à convertir: [500]                   │    │
│ │ Temps de jeu gagné: [50 minutes] ⏱️         │    │
│ │                                              │    │
│ │ Taux de conversion: 10 points = 1 minute    │    │
│ └─────────────────────────────────────────────┘    │
│                                                      │
│ Choisir un jeu (optionnel):                        │
│ [▼ Sélectionner un jeu]                            │
│                                                      │
│ ⚠️ Le temps converti expire dans 30 jours          │
│                                                      │
│ [Convertir Maintenant] [Annuler]                   │
└─────────────────────────────────────────────────────┘

HISTORIQUE DES CONVERSIONS
┌──────────────────────────────────────────────────┐
│ 15/10/2025 - 300 pts → 30 min (FIFA)   [Utilisé]│
│ 12/10/2025 - 500 pts → 50 min (GTA)    [Actif]  │
│ 08/10/2025 - 200 pts → 20 min (COD)    [Expiré] │
└──────────────────────────────────────────────────┘
```

**Fonctionnalités**:
1. **Slider interactif**: Choisir combien de points convertir
2. **Calcul en temps réel**: Afficher les minutes gagnées
3. **Validation**:
   - Points suffisants ?
   - Minimum atteint ?
   - Limite quotidienne respectée ?
4. **Confirmation**: Modal avec récapitulatif
5. **Feedback**: Toast de succès avec détails
6. **Historique**: Liste des conversions passées

---

## 🚀 Ordre d'Implémentation

### Phase 1: Statuts Sessions (1h)
1. Migration DB: Renommer statuts si nécessaire
2. Backend: Mettre à jour API manage_session.php
3. Frontend: Clarifier affichage (couleurs, labels, icônes)
4. Tests: Vérifier tous les statuts

### Phase 2: Dashboard Stats (2h)
1. Backend: Créer endpoint `/api/admin/dashboard_stats.php`
2. Requêtes SQL: Revenus, conversions, popularité
3. Frontend: Remplacer graphique hebdomadaire
4. Design: 8 cartes claires et colorées

### Phase 3: Photos de Profil (2h)
1. Migration: Table avatar si nécessaire
2. Backend: API upload_avatar.php avec validation
3. Dossier uploads: Structure et permissions
4. Frontend: Upload + affichage partout
5. Fallback: Initiales colorées

### Phase 4: Conversion Points (3h)
1. Migration: Tables conversion
2. Backend: API convert_points.php complète
3. Frontend: Page dédiée avec slider
4. Validation: Toutes les règles métier
5. Tests: Scénarios complets

---

## 📋 Checklist Finale

### Statuts Sessions
- [ ] Migration DB exécutée
- [ ] API mise à jour
- [ ] Frontend coloré et clair
- [ ] Documentation utilisateur

### Dashboard
- [ ] 8 nouvelles stats implémentées
- [ ] API performante
- [ ] Design moderne
- [ ] Responsive mobile

### Photos Profil
- [ ] Upload fonctionnel
- [ ] Validation robuste
- [ ] Affichage partout
- [ ] Fallback élégant

### Conversion Points
- [ ] Tables créées
- [ ] API complète et testée
- [ ] Interface intuitive
- [ ] Historique visible
- [ ] Règles métier respectées

---

## 🧪 Tests Requis

### Tests Statuts
1. Session arrêtée manuellement → Statut "Arrêtée" (orange)
2. Session terminée naturellement → Statut "Terminée" (vert)
3. Facture non utilisée → Statut "Expirée" (gris)

### Tests Dashboard
1. Calcul revenus aujourd'hui
2. Calcul revenus mois
3. Identification jeu populaire
4. Taux de conversion correct

### Tests Photos
1. Upload JPG → Succès
2. Upload > 2MB → Erreur
3. Photo affichée dans dashboard
4. Photo affichée dans sessions
5. Fallback initiales si pas de photo

### Tests Conversion
1. 500 points → 50 minutes
2. Points insuffisants → Erreur
3. Minimum non atteint → Erreur
4. Limite quotidienne atteinte → Erreur
5. Historique affiché correctement

---

## 📊 Résultats Attendus

### Avant
- ❌ Statuts confus
- ❌ Stats peu pertinentes
- ❌ Pas de photos
- ❌ Conversion non fonctionnelle

### Après
- ✅ Statuts clairs et colorés
- ✅ Stats utiles pour la gestion
- ✅ Photos partout, système professionnel
- ✅ Conversion fluide et intuitive

---

**Temps Total Estimé**: 8 heures
**Priorité**: Haute
**Impact**: Majeur sur l'expérience admin et joueur
