# 📝 Code à Intégrer dans Admin Shop

## Étape 1 : Ajouter les imports

En haut du fichier `/apps/web/src/app/admin/shop/page.jsx`, après les autres imports :

```jsx
import PackageModal from '../../../components/admin/PackageModal';
import PaymentMethodModal from '../../../components/admin/PaymentMethodModal';
```

---

## Étape 2 : Ajouter les fonctions de gestion des modals

Après la fonction `handleUpdateGame`, ajoutez :

```jsx
// ============ PACKAGE FUNCTIONS ============
const handleOpenPackageModal = (pkg = null) => {
  setEditingPackage(pkg);
  setShowPackageModal(true);
};

const handleClosePackageModal = () => {
  setShowPackageModal(false);
  setEditingPackage(null);
};

// ============ PAYMENT METHOD FUNCTIONS ============
const handleOpenPaymentModal = (payment = null) => {
  setEditingPayment(payment);
  setShowPaymentModal(true);
};

const handleClosePaymentModal = () => {
  setShowPaymentModal(false);
  setEditingPayment(null);
};
```

---

## Étape 3 : Remplacer le bouton "Ajouter Package"

Trouvez cette ligne (environ ligne 412) :

```jsx
<button
  onClick={() => toast.info('Formulaire à implémenter')}
  className="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
>
  <Plus className="w-5 h-5" />
  Ajouter Package
</button>
```

Remplacez par :

```jsx
<button
  onClick={() => handleOpenPackageModal()}
  className="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
>
  <Plus className="w-5 h-5" />
  Ajouter Package
</button>
```

---

## Étape 4 : Remplacer le bouton "Modifier" des packages

Trouvez cette ligne (environ ligne 450) :

```jsx
<button
  onClick={() => toast.info('Édition à implémenter')}
  className="text-blue-600 hover:underline text-sm mr-2"
>
  Modifier
</button>
```

Remplacez par :

```jsx
<button
  onClick={() => handleOpenPackageModal(pkg)}
  className="text-blue-600 hover:underline text-sm mr-2"
>
  Modifier
</button>
```

---

## Étape 5 : Remplacer le bouton "Ajouter Méthode"

Trouvez cette ligne (environ ligne 469) :

```jsx
<button
  onClick={() => toast.info('Formulaire à implémenter')}
  className="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
>
  <Plus className="w-5 h-5" />
  Ajouter Méthode
</button>
```

Remplacez par :

```jsx
<button
  onClick={() => handleOpenPaymentModal()}
  className="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
>
  <Plus className="w-5 h-5" />
  Ajouter Méthode
</button>
```

---

## Étape 6 : Remplacer les boutons "Modifier" des méthodes de paiement

Dans la table des payment methods, remplacez les boutons "Modifier" par :

```jsx
<button
  onClick={() => handleOpenPaymentModal(method)}
  className="text-blue-600 hover:underline text-sm mr-2"
>
  Modifier
</button>
```

---

## Étape 7 : Ajouter les modals à la fin du composant

Juste avant la balise fermante `</div>` finale (environ ligne 789), ajoutez :

```jsx
      {/* Package Modal */}
      <PackageModal
        isOpen={showPackageModal}
        onClose={handleClosePackageModal}
        editingPackage={editingPackage}
        games={games}
        onSuccess={() => {
          loadPackages();
          handleClosePackageModal();
        }}
      />

      {/* Payment Method Modal */}
      <PaymentMethodModal
        isOpen={showPaymentModal}
        onClose={handleClosePaymentModal}
        editingPayment={editingPayment}
        onSuccess={() => {
          loadPaymentMethods();
          handleClosePaymentModal();
        }}
      />
    </div>
  );
}
```

---

## ✅ Vérification

Après ces modifications, vous devriez avoir :

1. ✅ Bouton "Ajouter Package" qui ouvre le modal
2. ✅ Bouton "Modifier" sur chaque package qui ouvre le modal avec les données
3. ✅ Bouton "Ajouter Méthode" qui ouvre le modal de paiement
4. ✅ Bouton "Modifier" sur chaque méthode qui ouvre le modal avec les données
5. ✅ Les modals se ferment proprement après enregistrement
6. ✅ Les listes se rafraîchissent automatiquement après modification

---

## 🐛 Dépannage

### Erreur : "Cannot find module PackageModal"

**Solution :** Vérifiez que les fichiers existent :
- `/apps/web/src/components/admin/PackageModal.jsx`
- `/apps/web/src/components/admin/PaymentMethodModal.jsx`

### Les modals ne s'ouvrent pas

**Solution :** Vérifiez que vous avez bien déclaré les états en haut du composant :
```jsx
const [showPackageModal, setShowPackageModal] = useState(false);
const [editingPackage, setEditingPackage] = useState(null);
const [showPaymentModal, setShowPaymentModal] = useState(false);
const [editingPayment, setEditingPayment] = useState(null);
```

### Erreur lors de l'enregistrement

**Solution :** Vérifiez que l'API backend est accessible :
- `/api/admin/game_packages.php`
- `/api/admin/payment_methods.php`

Testez avec cURL :
```bash
curl -X GET http://localhost/projet%20ismo/api/admin/game_packages.php
```

---

**Une fois ces modifications faites, la gestion des packages et méthodes de paiement sera complètement fonctionnelle ! 🎉**
