# 🎮 Guide : Système Points par Heure - FONCTIONNEL

## ✅ **Status : OPÉRATIONNEL**

Le système de **points par heure** fonctionne maintenant **vraiment** ! Les points sont calculés automatiquement basés sur le temps de jeu **réel**.

---

## 🎯 **Comment ça Fonctionne**

### **Principe Simple**

```
TEMPS RÉELLEMENT JOUÉ × POINTS PAR HEURE = POINTS GAGNÉS
```

**Pas de temps joué = Pas de points !**
**Plus de temps joué = Plus de points !**

---

## 📋 **Processus Complet**

### **Étape 1 : Configuration du Jeu (Admin)**

1. Allez sur `/admin/shop`
2. Créez ou modifiez un jeu
3. Définissez les **"Points par Heure"**

**Exemples de configuration :**
```
FIFA 2024        → 15 pts/h
Call of Duty     → 20 pts/h
GTA V            → 12 pts/h
Forza Horizon    → 18 pts/h
Half-Life Alyx   → 25 pts/h (VR = plus immersif)
```

---

### **Étape 2 : Achat par le Joueur**

Le joueur achète un package :
```
Package : "2 heures de FIFA 2024"
- Durée : 120 minutes
- Prix : 2500 XOF
- Points potentiels : 30 points (SI joue 2h complètes)
```

**Important :** Les points ne sont **PAS** crédités immédiatement !

---

### **Étape 3 : Confirmation du Paiement (Admin)**

1. L'admin confirme le paiement
2. Une **session de jeu** est créée automatiquement
3. Status : "Prêt à jouer"

**Toujours pas de points crédités !**

---

### **Étape 4 : Session de Jeu**

Le joueur commence à jouer et le système **track le temps réel** :

| Temps Joué | Calcul | Points Crédités |
|------------|--------|-----------------|
| 15 min | (15/60) × 15 | **4 points** ✅ |
| 30 min | (30/60) × 15 | **+4 points** ✅ (total: 8) |
| 60 min | (60/60) × 15 | **+8 points** ✅ (total: 15) |
| 90 min | (90/60) × 15 | **+8 points** ✅ (total: 23) |
| 120 min | (120/60) × 15 | **+7 points** ✅ (total: 30) |

**Les points sont crédités progressivement pendant le jeu !**

---

### **Étape 5 : Fin de Session**

**Scénario A : Arrêt anticipé**
```
Acheté : 120 minutes
Joué : 75 minutes seulement

Points gagnés : (75/60) × 15 = 19 points
Points perdus : 11 points (temps non utilisé)
```

**Scénario B : Utilisation complète**
```
Acheté : 120 minutes
Joué : 120 minutes

Points gagnés : (120/60) × 15 = 30 points (MAXIMUM)
```

---

## 🔧 **API Endpoints pour le Tracking**

### **1. Démarrer une Session**

```javascript
POST /api/sessions/start_session.php

{
  "purchase_id": 123
}
```

**Réponse :**
```json
{
  "success": true,
  "session": {
    "id": 456,
    "status": "active",
    "total_minutes": 120,
    "used_minutes": 0,
    "points_per_hour": 15
  },
  "points_calculation": {
    "max_points": 30,
    "formula": "(minutes / 60) × 15"
  }
}
```

---

### **2. Mettre à Jour le Temps (Pendant le Jeu)**

**Cette API est appelée automatiquement toutes les 5-10 minutes :**

```javascript
POST /api/sessions/update_session.php

{
  "session_id": 456,
  "action": "update_time",
  "used_minutes": 45  // Temps écoulé
}
```

**Réponse :**
```json
{
  "success": true,
  "session": {
    "used_minutes": 45,
    "remaining_minutes": 75,
    "calculated_points": 11,
    "points_this_update": 11
  }
}
```

**🎉 Les 11 points sont automatiquement crédités dans le compte du joueur !**

---

### **3. Terminer la Session**

```javascript
POST /api/sessions/update_session.php

{
  "session_id": 456,
  "action": "complete",
  "used_minutes": 100  // Temps final
}
```

**Réponse :**
```json
{
  "success": true,
  "session": {
    "status": "completed",
    "used_minutes": 100,
    "calculated_points": 25,
    "points_this_update": 14  // Points additionnels
  }
}
```

**Total crédité : 25 points pour 100 minutes jouées**

---

### **4. Voir Mes Sessions**

```javascript
GET /api/sessions/my_sessions.php?status=active
```

**Réponse :**
```json
{
  "sessions": [
    {
      "id": 456,
      "game_name": "FIFA 2024",
      "total_minutes": 120,
      "used_minutes": 45,
      "remaining_minutes": 75,
      "calculated_points": 11,
      "points_credited": 11,
      "status": "active"
    }
  ],
  "stats": {
    "total_sessions": 5,
    "total_minutes_played": 380,
    "total_points_calculated": 95
  }
}
```

---

## 💡 **Exemples Concrets**

### **Exemple 1 : Joueur Pressé**

**Configuration :**
- Jeu : GTA V (12 pts/h)
- Acheté : 2h (120 min)

**Utilisation :**
- Joué : 40 minutes seulement

**Résultat :**
```
Points = (40 / 60) × 12 = 8 points
Solde du joueur : +8 points
```

**Équitable !** Le joueur paie pour 2h mais ne reçoit que les points pour 40 min.

---

### **Exemple 2 : Joueur Marathon**

**Configuration :**
- Jeu : Call of Duty (20 pts/h)
- Acheté : 5h (300 min)

**Utilisation :**
- Joué : 300 minutes complètes !

**Résultat :**
```
Points = (300 / 60) × 20 = 100 points
Solde du joueur : +100 points
```

**Récompensé !** Le joueur utilise tout son temps et obtient le maximum.

---

### **Exemple 3 : Session en Plusieurs Fois**

**Configuration :**
- Jeu : Forza Horizon (18 pts/h)
- Acheté : 3h (180 min)

**Utilisation :**
- Jour 1 : 60 min → **18 points** crédités
- Jour 2 : 50 min → **+15 points** (total: 33)
- Jour 3 : 70 min → **+21 points** (total: 54)

**Total : 54 points pour 180 minutes**

---

## 🛠️ **Intégration Frontend**

### **Composant React : Timer avec Calcul de Points**

```jsx
import { useEffect, useState } from 'react';
import API_BASE from '../utils/apiBase';

function GameSessionTimer({ session }) {
  const [elapsedMinutes, setElapsedMinutes] = useState(0);
  const [earnedPoints, setEarnedPoints] = useState(0);
  
  useEffect(() => {
    const interval = setInterval(() => {
      setElapsedMinutes(prev => prev + 1);
      
      // Calculer les points en temps réel
      const points = Math.round(
        (elapsedMinutes / 60) * session.points_per_hour
      );
      setEarnedPoints(points);
      
      // Mettre à jour toutes les 5 minutes
      if (elapsedMinutes % 5 === 0) {
        updateSessionTime(elapsedMinutes);
      }
    }, 60000); // Toutes les minutes
    
    return () => clearInterval(interval);
  }, [elapsedMinutes]);
  
  const updateSessionTime = async (minutes) => {
    try {
      const res = await fetch(`${API_BASE}/sessions/update_session.php`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          session_id: session.id,
          action: 'update_time',
          used_minutes: minutes
        })
      });
      
      const data = await res.json();
      if (data.success) {
        console.log(`✅ Points crédités : ${data.session.points_this_update}`);
      }
    } catch (err) {
      console.error('Erreur update session:', err);
    }
  };
  
  return (
    <div className="p-4 bg-gray-800 text-white rounded-lg">
      <h3 className="text-xl font-bold mb-2">Session en Cours</h3>
      <div className="space-y-2">
        <p>⏱️ Temps écoulé : <strong>{elapsedMinutes} min</strong></p>
        <p>⏳ Temps restant : <strong>{session.total_minutes - elapsedMinutes} min</strong></p>
        <p>⭐ Points gagnés : <strong>{earnedPoints} pts</strong></p>
        <p className="text-sm text-gray-400">
          Calcul : ({elapsedMinutes}/60) × {session.points_per_hour} pts/h
        </p>
      </div>
    </div>
  );
}

export default GameSessionTimer;
```

---

## 📊 **Traçabilité Complète**

### **Table `points_transactions`**

Toutes les attributions de points sont enregistrées :

```sql
SELECT 
  pt.created_at,
  pt.change_amount as points,
  pt.reason,
  gs.used_minutes,
  g.name as game_name
FROM points_transactions pt
INNER JOIN game_sessions gs ON pt.reference_id = gs.id
INNER JOIN purchases p ON gs.purchase_id = p.id
INNER JOIN games g ON p.game_id = g.id
WHERE pt.user_id = 123
  AND pt.reference_type = 'game_session'
ORDER BY pt.created_at DESC;
```

**Résultat :**
```
| Date            | Points | Raison                         | Minutes | Jeu    |
|-----------------|--------|--------------------------------|---------|--------|
| 2025-10-16 14:30| +11    | Temps de jeu: FIFA (45 min...) | 45      | FIFA   |
| 2025-10-16 15:00| +8     | Temps de jeu: FIFA (30 min...) | 75      | FIFA   |
| 2025-10-16 15:30| +6     | Temps de jeu: FIFA (25 min...) | 100     | FIFA   |
```

---

## ✅ **Checklist de Fonctionnement**

Pour vérifier que tout fonctionne :

- [ ] Admin peut définir **points_per_hour** pour chaque jeu
- [ ] Joueur achète un package
- [ ] Admin confirme le paiement
- [ ] Session créée automatiquement
- [ ] Joueur démarre la session
- [ ] Timer compte le temps réel
- [ ] API `update_session` est appelée régulièrement
- [ ] Points calculés : `(minutes / 60) × points_per_hour`
- [ ] Points crédités automatiquement dans le compte
- [ ] Transaction enregistrée dans `points_transactions`
- [ ] Statistiques mises à jour dans `user_stats`

---

## 🎉 **Résumé**

### **Le système fonctionne maintenant VRAIMENT !**

✅ **Points calculés** en temps réel  
✅ **Formule simple** : (temps en heures) × points/h  
✅ **Crédit automatique** pendant le jeu  
✅ **Traçabilité complète** de chaque point  
✅ **Équitable** pour tous les joueurs  
✅ **Motivant** : plus tu joues, plus tu gagnes  

---

**Les joueurs sont récompensés proportionnellement au temps qu'ils investissent réellement dans les jeux ! 🎮⏱️✨**
