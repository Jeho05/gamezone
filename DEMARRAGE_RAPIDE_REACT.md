# 🚀 Démarrage Rapide - Système Boutique React

## ✅ Ce qui a été fait

Le système complet de vente de temps de jeu a été **intégré dans votre application React** sur `http://localhost:4000/`.

---

## 📦 Fichiers Créés (React)

### Pages Utilisateur
1. ✅ `src/app/player/shop/page.jsx` - Boutique des jeux
2. ✅ `src/app/player/shop/[gameId]/page.jsx` - Détails du jeu et achat
3. ✅ `src/app/player/my-purchases/page.jsx` - Historique des achats

### Page Admin
4. ✅ `src/app/admin/shop/page.jsx` - Gestion complète de la boutique

### Backend (PHP - déjà créé)
- ✅ 10 fichiers API dans `api/shop/` et `api/admin/`
- ✅ Migration SQL avec tables et données de démo
- ✅ 8 jeux préchargés avec packages

---

## 🎯 Installation en 3 Minutes

### 1️⃣ Installer la Base de Données (30 secondes)

Ouvrez cette URL dans votre navigateur:
```
http://localhost/projet%20ismo/api/run_migration.php?file=add_game_purchase_system.sql
```

✅ **Résultat:** Message "Migration réussie"

### 2️⃣ Démarrer React (1 minute)

```bash
# Ouvrir PowerShell dans le dossier du projet
cd "c:\xampp\htdocs\projet ismo\createxyz-project\_\apps\web"

# Démarrer le serveur de développement
npm run dev
```

✅ **Résultat:** Application disponible sur `http://localhost:4000`

### 3️⃣ Tester (1 minute)

**Option A - Test Utilisateur:**
```
http://localhost:4000/player/shop
```
- Parcourez les jeux
- Cliquez sur un jeu → Voir packages
- Sélectionnez un package → Choisissez paiement → Confirmer

**Option B - Test Admin:**
```
http://localhost:4000/admin/shop
```
- Consultez les 4 onglets (Jeux, Packages, Paiements, Achats)
- Confirmez les paiements en attente

---

## 🎮 URLs Disponibles

### Pour les Joueurs
| URL | Description |
|-----|-------------|
| `http://localhost:4000/player/shop` | Boutique - Catalogue des jeux |
| `http://localhost:4000/player/shop/1` | Détails d'un jeu (ID=1) |
| `http://localhost:4000/player/my-purchases` | Historique des achats |

### Pour les Admins
| URL | Description |
|-----|-------------|
| `http://localhost:4000/admin/shop` | Gestion boutique (4 onglets) |

### Backend (APIs)
| URL | Description |
|-----|-------------|
| `http://localhost/projet%20ismo/api/shop/games.php` | API publique des jeux |
| `http://localhost/projet%20ismo/test_shop_system.php` | Page de test/diagnostic |

---

## 🎯 Parcours Complet de Test

### Scénario: Acheter 1h de FIFA 2024

**Étape 1:** Ouvrir la boutique
```
http://localhost:4000/player/shop
```
→ Vous voyez 8 jeux avec images et prix

**Étape 2:** Cliquer sur "FIFA 2024"
```
http://localhost:4000/player/shop/1
```
→ Vous voyez les détails + 4 packages disponibles

**Étape 3:** Cliquer sur "1 heure - 5.00 XOF"
→ Modal de paiement s'ouvre

**Étape 4:** Sélectionner "Espèces"
→ Instructions s'affichent

**Étape 5:** Cliquer "Confirmer l'Achat"
→ Achat créé avec statut "pending"
→ Redirection vers "Mes Achats"

**Étape 6:** En tant qu'admin, confirmer le paiement
```
http://localhost:4000/admin/shop
```
→ Onglet "Achats"
→ Cliquer "Confirmer" sur l'achat

**Étape 7:** Vérifier que les points sont crédités
```
http://localhost:4000/player/shop
```
→ Le compteur de points en haut a augmenté de +15 pts

✅ **Succès !** L'achat est complété et la session est créée.

---

## 🎨 Ce que vous verrez

### Page Boutique (`/player/shop`)
```
┌─────────────────────────────────────────┐
│  🎮 Boutique de Jeux                    │
│  Achetez du temps de jeu et gagnez!     │
│  [⭐ 150 points]                         │
├─────────────────────────────────────────┤
│  [Rechercher un jeu...]                 │
│  [Tous] [Action] [Sports] [VR]...       │
│  [Mes Achats]                            │
├─────────────────────────────────────────┤
│  ┌────────┐  ┌────────┐  ┌────────┐    │
│  │ FIFA   │  │  COD   │  │  GTA   │    │
│  │ 2024   │  │  MW3   │  │   V    │    │
│  │ ⭐ 15  │  │ ⭐ 20  │  │ ⭐ 18  │    │
│  │ 5 XOF  │  │ 6 XOF  │  │ 5.5XOF │    │
│  └────────┘  └────────┘  └────────┘    │
└─────────────────────────────────────────┘
```

### Page Détails Jeu (`/player/shop/[gameId]`)
```
┌─────────────────────────────────────────┐
│  [← Retour]                              │
│  ╔═══════════════════════════════════╗  │
│  ║  [Grande Image du Jeu]            ║  │
│  ║  FIFA 2024                        ║  │
│  ║  [Sports] [PS5]                   ║  │
│  ╚═══════════════════════════════════╝  │
│  ┌────┐ ┌────┐ ┌────┐ ┌────┐          │
│  │⭐15│ │👥4 │ │🎮PS5│ │🔞3+│          │
│  │pts │ │max │ │    │ │    │          │
│  └────┘ └────┘ └────┘ └────┘          │
│  Description: Le jeu de foot...        │
│                                         │
│  📦 Choisissez votre Package            │
│  ┌──────────────────────────────────┐  │
│  │ 1 heure                          │  │
│  │ ⏱️ 60 min                        │  │
│  │ 5.00 XOF      +15 pts ⭐         │  │
│  └──────────────────────────────────┘  │
│  ┌──────────────────────────────────┐  │
│  │ Pack Soirée - 3h  [PROMO -20%]  │  │
│  │ ⏱️ 180 min                       │  │
│  │ 15.00 12.00   +50 pts ⭐         │  │
│  └──────────────────────────────────┘  │
└─────────────────────────────────────────┘
```

### Modal de Paiement
```
┌─────────────────────────────────────────┐
│  💳 Paiement              [✕]           │
├─────────────────────────────────────────┤
│  📋 Récapitulatif                        │
│  Jeu: FIFA 2024                         │
│  Package: 1 heure                       │
│  Durée: 60 min                          │
│  Points: +15 ⭐                          │
│  Total: 5.00 XOF                        │
├─────────────────────────────────────────┤
│  Méthode de Paiement                    │
│  ⭕ Espèces (Sur place)                  │
│  ⭕ Carte Bancaire (En ligne)            │
│  ⭕ PayPal (En ligne)                    │
├─────────────────────────────────────────┤
│  📋 Instructions:                        │
│  Payez à la réception...                │
├─────────────────────────────────────────┤
│  [Confirmer l'Achat]                    │
└─────────────────────────────────────────┘
```

### Page Mes Achats (`/player/my-purchases`)
```
┌─────────────────────────────────────────┐
│  🛒 Mes Achats                          │
│  [Tous] [Complétés] [En attente] [🔄]  │
├─────────────────────────────────────────┤
│  ┌─────────────────────────────────────┐│
│  │ [IMG] FIFA 2024                     ││
│  │       1 heure                       ││
│  │       15 Jan 2025                   ││
│  │ ⏱️60min 💰5XOF ⭐+15 📋Espèces      ││
│  │                       [✅ Complété]  ││
│  └─────────────────────────────────────┘│
│  ┌─────────────────────────────────────┐│
│  │ [IMG] COD MW3                       ││
│  │       Pack Gaming - 2h              ││
│  │       14 Jan 2025                   ││
│  │ ⏱️120min 💰10XOF ⭐+45 📋Espèces    ││
│  │                  [⏳ En attente]     ││
│  └─────────────────────────────────────┘│
│                                         │
│  📊 Récapitulatif                       │
│  Total: 2 achats                        │
│  Montant: 15.00 XOF                     │
│  Points: +60                            │
└─────────────────────────────────────────┘
```

### Page Admin (`/admin/shop`)
```
┌─────────────────────────────────────────┐
│  🎮 Gestion Boutique de Jeux            │
│  [Jeux] [Packages] [Paiements] [Achats] │
├─────────────────────────────────────────┤
│  Onglet: Achats                         │
│  ┌─────────────────────────────────────┐│
│  │ User  │ Jeu  │ Durée│ Prix│ Status  ││
│  ├───────┼──────┼──────┼─────┼─────────┤│
│  │Player │FIFA  │60min │5XOF │[Confirm]││
│  │User2  │COD   │120min│10XOF│✅Done   ││
│  └─────────────────────────────────────┘│
└─────────────────────────────────────────┘
```

---

## 🎨 Fonctionnalités Visuelles

### Animations
- ✨ Hover sur cartes de jeux → Zoom léger
- 🎭 Modal → Fade in/out
- 🔄 Boutons → Effets de transition
- 📱 Layout → Responsive automatique

### Badges et Icônes
- ⭐ Points en jaune
- 💰 Prix en vert
- ⏱️ Durée avec horloge
- ✅ Statut complété en vert
- ⏳ Statut en attente en jaune
- 🔥 Badge promo en dégradé orange

### Notifications (Toast)
- ✅ Succès → Toast vert
- ❌ Erreur → Toast rouge
- ℹ️ Info → Toast bleu
- Apparaissent en bas à droite

---

## 🔧 Configuration

### Variable d'Environnement API

Le système utilise automatiquement:
```javascript
window.APP_API_BASE = 'http://localhost/projet%20ismo/api'
```

Défini dans `src/app/root.tsx` ligne 378.

### CORS

Le backend PHP est configuré pour accepter les requêtes de `localhost:4000` dans `api/config.php`.

---

## 📊 Données de Démo Disponibles

### 8 Jeux Préchargés
1. FIFA 2024 (Sports) - 4 packages
2. Call of Duty MW3 (Action) - 4 packages
3. GTA V (Action) - 3 packages
4. Forza Horizon 5 (Racing)
5. Street Fighter 6 (Fighting)
6. Beat Saber VR (VR) - 3 packages
7. Pac-Man CE (Retro)
8. Mortal Kombat 11 (Fighting)

### 5 Méthodes de Paiement
1. Espèces (actif par défaut)
2. Carte Bancaire (à configurer)
3. PayPal (à configurer)
4. MTN Mobile Money (à configurer)
5. Orange Money (à configurer)

---

## 🐛 Résolution de Problèmes

### "Unauthorized" ou "Forbidden"
➡️ **Solution:** Connectez-vous d'abord avec un compte utilisateur valide

### Les jeux ne s'affichent pas
➡️ **Solution:** Exécutez la migration SQL (Étape 1)

### Erreur CORS
➡️ **Solution:** Vérifiez que XAMPP est démarré et que `api/config.php` accepte localhost:4000

### Modal ne s'ouvre pas
➡️ **Solution:** Vérifiez la console (F12) pour erreurs JavaScript

---

## 📚 Documentation Complète

Pour plus de détails, consultez:

1. **`INTEGRATION_REACT_SHOP.md`** - Architecture et détails techniques
2. **`INSTALLER_SYSTEME_BOUTIQUE.md`** - Guide complet backend
3. **`SYSTEME_BOUTIQUE_COMPLETE.md`** - Récapitulatif général
4. **`test_shop_system.php`** - Page de test et diagnostic

---

## ✅ Checklist de Vérification

Avant de commencer, vérifiez:

- [ ] XAMPP est démarré (Apache + MySQL)
- [ ] Migration SQL exécutée
- [ ] React dev server tourne sur :4000
- [ ] Vous avez un compte utilisateur
- [ ] Les cookies sont activés dans le navigateur

---

## 🎉 Vous êtes Prêt !

Le système est **100% opérationnel** et prêt à être utilisé.

**Commencez maintenant:**
```
1. http://localhost:4000/player/shop
2. Parcourez les jeux
3. Faites un achat test
4. Vérifiez dans /player/my-purchases
5. Confirmez en tant qu'admin dans /admin/shop
```

**Bon gaming ! 🎮🚀**
