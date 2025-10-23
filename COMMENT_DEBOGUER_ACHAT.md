# 🔍 Comment Déboguer l'Erreur d'Achat

## Le Problème

L'API retourne : **"Le champ 'game_id' est requis"** même si les données sont envoyées depuis React.

## Cause Probable

La fonction `get_json_input()` ne récupère pas correctement les données JSON envoyées par React.

## 🛠️ SOLUTION IMMÉDIATE

### Étape 1: Vérifier dans le Navigateur

1. **Ouvrir Chrome DevTools** (F12)
2. **Aller dans l'onglet Network**
3. **Faire un achat**
4. **Cliquer sur la requête `create_purchase.php`**
5. **Vérifier**:
   - **Headers** → Content-Type = `application/json`
   - **Payload** → Les données JSON sont présentes
   - **Response** → L'erreur exacte retournée

### Étape 2: Vérifier les Logs PHP

1. Ouvrir: `C:\xampp\php\logs\php_error_log`
2. Chercher: `=== CREATE PURCHASE DEBUG ===`
3. Voir ce qui est reçu réellement

### Étape 3: Tester l'API Directement

Utilisez ce code dans la console navigateur (F12):

```javascript
// Test direct de l'API
fetch('http://localhost/projet%20ismo/api/shop/create_purchase.php', {
  method: 'POST',
  credentials: 'include',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    game_id: 1,
    package_id: 1,
    payment_method_id: 1
  })
})
.then(r => r.json())
.then(data => console.log('Résultat:', data))
.catch(err => console.error('Erreur:', err));
```

## 🔧 Solutions Possibles

### Solution 1: Vérifier que l'Utilisateur est Connecté

L'API nécessite une session active. Dans React:

```javascript
// Avant de faire un achat, vérifier:
const checkAuth = async () => {
  const res = await fetch('http://localhost/projet%20ismo/api/auth/check.php', {
    credentials: 'include'
  });
  const data = await res.json();
  console.log('Auth status:', data);
};
```

### Solution 2: Forcer l'Envoi Correct des Données

Dans votre composant React Shop, assurez-vous que:

```javascript
const handlePurchase = async (gameId, packageId, paymentMethodId) => {
  try {
    const response = await fetch(`${API_BASE}/shop/create_purchase.php`, {
      method: 'POST',
      credentials: 'include', // IMPORTANT pour la session
      headers: {
        'Content-Type': 'application/json' // IMPORTANT
      },
      body: JSON.stringify({
        game_id: parseInt(gameId), // Convertir en nombre
        package_id: parseInt(packageId),
        payment_method_id: parseInt(paymentMethodId)
      })
    });

    const data = await response.json();
    
    if (data.success) {
      console.log('Achat réussi:', data);
      toast.success('Achat créé!');
    } else {
      console.error('Erreur:', data);
      toast.error(data.error || 'Erreur');
    }
  } catch (error) {
    console.error('Exception:', error);
    toast.error('Erreur de connexion');
  }
};
```

### Solution 3: Si get_json_input() Ne Marche Pas

Modifier temporairement `api/shop/create_purchase.php`:

```php
// Essayer de récupérer depuis $_POST en fallback
$data = get_json_input();

// Si vide, essayer $_POST
if (empty($data) && !empty($_POST)) {
    $data = $_POST;
}

// Si toujours vide, lire php://input manuellement
if (empty($data)) {
    $rawInput = file_get_contents('php://input');
    if ($rawInput) {
        $data = json_decode($rawInput, true) ?: [];
    }
}
```

## 📊 Vérifications Rapides

### Dans React - Console Navigateur:

```javascript
// 1. Vérifier la session
fetch('http://localhost/projet%20ismo/api/auth/check.php', {credentials: 'include'})
  .then(r => r.json())
  .then(d => console.log('Session:', d));

// 2. Vérifier les jeux disponibles
fetch('http://localhost/projet%20ismo/api/shop/games.php', {credentials: 'include'})
  .then(r => r.json())
  .then(d => console.log('Jeux:', d));

// 3. Vérifier les packages
fetch('http://localhost/projet%20ismo/api/shop/points_packages.php', {credentials: 'include'})
  .then(r => r.json())
  .then(d => console.log('Packages:', d));
```

## ✅ Checklist

Avant de faire un achat:

- [ ] Je suis connecté (session active)
- [ ] Les jeux sont chargés dans la boutique
- [ ] Les packages sont disponibles
- [ ] Les méthodes de paiement existent
- [ ] La console ne montre pas d'erreur CORS
- [ ] Les logs PHP montrent les données reçues

## 🚨 Si Rien Ne Marche

Envoyez-moi:
1. Le contenu de `php_error_log`
2. Le screenshot du Network tab (requête create_purchase)
3. Le code de votre composant Shop React (partie achat)

---

**Astuce**: Le test en terminal fonctionne, donc le problème vient de React → PHP, pas de la logique PHP elle-même.
