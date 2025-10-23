# 🎮 Guide Système de Facturation GameZone

## 📋 Vue d'ensemble

Système complet et sécurisé de facturation pour la gestion des achats de temps de jeu avec :
- ✅ Génération automatique de factures avec codes QR
- ✅ Expiration après 2 mois si inutilisé
- ✅ Scan et validation par administrateur
- ✅ Décompte automatique du temps de jeu
- ✅ Historique complet des achats
- ✅ Protection anti-fraude multicouche
- ✅ Audit trail complet

## 🚀 Installation

### 1. Exécuter le script d'installation

```bash
# En CLI (recommandé)
php install_invoice_system.php

# Ou via navigateur
http://localhost/projet%20ismo/install_invoice_system.php?password=GAMEZONE_INSTALL_2025
```

### 2. Configurer le CRON pour le décompte automatique

#### Windows (Task Scheduler)
```powershell
schtasks /create /tn "GameZone Countdown" /tr "php C:\xampp\htdocs\projet ismo\api\cron\countdown_sessions.php" /sc minute /mo 1
```

#### Linux/Mac (crontab)
```bash
* * * * * php /path/to/projet/api/cron/countdown_sessions.php >> /path/to/logs/cron.log 2>&1
```

#### Alternative: Webhook HTTP
```
http://yourdomain.com/api/cron/countdown_sessions.php?token=GAMEZONE_CRON_SECRET_2025
```

## 📊 Architecture

### Tables Principales

1. **invoices** - Factures avec codes de validation
2. **invoice_scans** - Historique des scans (sécurité)
3. **active_game_sessions_v2** - Sessions en temps réel
4. **session_events** - Événements des sessions
5. **invoice_audit_log** - Audit complet
6. **fraud_detection_rules** - Règles anti-fraude

## 🔄 Flux Complet

### 1. Achat par le joueur
- Joueur achète un package via `/api/shop/create_purchase.php`
- Paiement confirmé → Trigger automatique crée la facture
- Code alphanumérique unique (16 chars) généré
- QR code créé avec hash SHA256 pour vérification

### 2. Réception de la facture
- Joueur consulte ses factures: `/user/my_invoices.html`
- Peut afficher le QR code et le code alphanumérique
- Facture valide 2 mois

### 3. Activation à la salle
- Admin scanne le code: `/admin/invoice_scanner.html`
- API vérifie le code: `/api/admin/scan_invoice.php`
- Si valide: Facture activée + Session créée (statut: ready)
- Historique du scan enregistré

### 4. Démarrage du jeu
- Admin démarre la session via interface
- Session passe à "active"
- Décompte automatique activé

### 5. Décompte en temps réel
- CRON exécute toutes les minutes: `/api/cron/countdown_sessions.php`
- Procédure `countdown_active_sessions()` appelée
- Temps décompté automatiquement
- Alertes à 10% du temps restant
- Session auto-terminée à 0 minutes

### 6. Fin de session
- Session complétée → Facture marquée "used"
- Historique conservé dans "mes achats"
- Facture inutilisable

## 🔒 Sécurité Anti-Fraude

### Règles de Détection

1. **Scan rapide multiple**
   - Max 5 tentatives en 5 minutes
   - Action: Blocage

2. **IPs multiples**
   - Max 3 IPs différentes en 1 heure
   - Action: Flagging

3. **Patterns temporels**
   - Détection activité bot
   - Action: Flagging

4. **Code expiré**
   - Tentative scan facture expirée
   - Action: Log

### Mesures de Protection

- Hash SHA256 pour intégrité QR
- Rate limiting sur scans
- Logs détaillés IP/User-Agent
- Audit trail complet
- Factures suspicieuses bloquées

## 📱 APIs Disponibles

### Joueur

#### GET /api/invoices/my_invoices.php
Récupère les factures de l'utilisateur
```javascript
// Params: ?status=pending&limit=20
{
  "invoices": [...],
  "stats": { "total": 10, "active": 2, ... }
}
```

#### GET /api/invoices/generate_qr.php?invoice_id=123
Génère le QR code pour une facture
```javascript
{
  "qr_data": "{...}",
  "qr_image_url": "https://...",
  "validation_code": "ABC123DEF456..."
}
```

### Admin

#### POST /api/admin/scan_invoice.php
Scanner et activer une facture
```javascript
// Body: { "validation_code": "ABC123..." }
{
  "success": true,
  "invoice": {...},
  "next_action": "start_session"
}
```

#### POST /api/admin/manage_session.php
Gérer une session
```javascript
// Actions: start, pause, resume, terminate
{ "session_id": 123, "action": "start" }
```

#### GET /api/admin/invoice_dashboard.php
Statistiques complètes
```javascript
{
  "invoice_stats": {...},
  "session_stats": {...},
  "active_sessions": [...],
  "suspicious_invoices": [...]
}
```

## 🎯 Interfaces Utilisateur

### Admin
- **Scanner**: `/admin/invoice_scanner.html`
  - Scan codes QR/alphanumérique
  - Activation factures
  - Démarrage sessions

### Joueur
- **Mes Factures**: `/user/my_invoices.html`
  - Liste factures
  - Affichage QR codes
  - Historique complet

## ⚙️ Configuration

### Variables d'environnement (recommandé)

```php
// api/config.php
$SECRET_SALT = getenv('GAMEZONE_SECRET_SALT') ?: 'GAMEZONE_SECRET_2025';
$CRON_TOKEN = getenv('GAMEZONE_CRON_TOKEN') ?: 'GAMEZONE_CRON_SECRET_2025';
```

### Personnalisation

```sql
-- Modifier durée d'expiration (défaut: 2 mois)
ALTER TABLE invoices MODIFY expires_at DATETIME DEFAULT DATE_ADD(NOW(), INTERVAL 3 MONTH);

-- Modifier règles anti-fraude
UPDATE fraud_detection_rules SET rule_config = JSON_OBJECT('max_attempts', 10) WHERE rule_name = 'Tentatives multiples';
```

## 🧪 Tests

### 1. Test complet du flux
```bash
php api/test_invoice_system.php
```

### 2. Test scan manuel
```bash
curl -X POST http://localhost/api/admin/scan_invoice.php \
  -H "Content-Type: application/json" \
  -d '{"validation_code":"ABC123DEF456..."}'
```

### 3. Test décompte
```bash
php api/cron/countdown_sessions.php
```

## 📈 Monitoring

### Logs
- `/logs/countdown_YYYY-MM-DD.log` - Logs décompte automatique
- Table `invoice_audit_log` - Audit complet
- Table `invoice_scans` - Historique scans

### Indicateurs à surveiller
- Taux d'activation (factures créées vs activées)
- Scans frauduleux
- Sessions expirées sans utilisation
- Performance décompte automatique

## 🛠️ Maintenance

### Nettoyage données anciennes
```sql
-- Supprimer factures expirées > 1 an
DELETE FROM invoices WHERE status = 'expired' AND expires_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- Archiver logs anciens
DELETE FROM invoice_audit_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);
```

### Optimisation
```sql
-- Analyser performances
ANALYZE TABLE invoices, active_game_sessions_v2;

-- Reconstruire index
OPTIMIZE TABLE invoices, invoice_scans;
```

## 🆘 Dépannage

### Facture non créée après paiement
- Vérifier trigger `after_purchase_completed`
- Vérifier statut paiement = 'completed'
- Consulter logs audit

### Décompte ne fonctionne pas
- Vérifier CRON actif
- Vérifier procédure `countdown_active_sessions`
- Consulter `/logs/countdown_*.log`

### Code QR invalide
- Vérifier hash d'intégrité
- Vérifier expiration
- Consulter `invoice_scans` pour tentatives

## 📞 Support

Pour toute question ou problème:
1. Consulter les logs d'audit
2. Vérifier dashboard admin
3. Analyser table `invoice_scans`
4. Contacter support technique

---

**Version**: 1.0  
**Date**: 2025-01-17  
**Auteur**: GameZone Team
