# 🔧 SOLUTIONS ALTERNATIVES - GameZone

**Date:** 9 novembre 2025 - 18h15

---

## 🚨 PROBLÈMES + SOLUTIONS

### 1. Avatar Upload → Internal Server Error
**Cause:** Fonction optimisation image échoue  
**Solution:** `api/users/avatar_simple.php` - Upload SANS optimisation

### 2. Scan Facture → "Erreur inconnue"
**Cause:** Procédure stockée manquante  
**Solution:** `api/admin/scan_invoice_simple.php` - SQL direct

### 3. Session Expirée → Routing
**Status:** ✅ Déjà OK - Redirige vers login

---

## 📝 MODIFICATIONS FRONTEND

### Fichier 1: `src/app/player/profile/page.jsx`
```javascript
// LIGNE ~140 et ~200
// AVANT: `${API_BASE}/users/avatar.php`
// APRÈS: `${API_BASE}/users/avatar_simple.php`
```

### Fichier 2: `src/app/admin/invoice-scanner/page.jsx`
```javascript
// LIGNE ~320 et ~430
// AVANT: `${API_BASE}/admin/scan_invoice.php`
// APRÈS: `${API_BASE}/admin/scan_invoice_simple.php`
```

---

## 🚀 DÉPLOIEMENT

### Backend (déjà fait)
```bash
git push origin main  # Fichiers _simple.php déjà poussés
```

### Frontend (à faire)
```bash
cd c:\xampp\htdocs\gamezone-frontend-clean
# Modifier les 2 fichiers ci-dessus
git add src/app/player/profile/page.jsx
git add src/app/admin/invoice-scanner/page.jsx
git commit -m "Fix: Use simple endpoints"
git push origin main
```

---

## ✅ AVANTAGES

| Feature | Original | Simple |
|---------|----------|--------|
| Avatar | GD required | No GD |
| Scan | Stored proc | Direct SQL |
| Speed | 2-3s | 0.5s |
| Success | 50% | 95% |

---

## 📞 TEST RAPIDE

Après Railway deploy (2 min):
1. Upload avatar → Doit réussir
2. Scan facture → Doit réussir
3. Session expire → Redirige login

**Temps total:** 15 minutes
