# 🚀 Déploiement Rapide GameZone

## 📋 Prérequis

- **VPS/Serveur** avec Ubuntu 22.04+ (4GB RAM minimum)
- **Domaine** pointant vers l'IP du serveur
- **Accès SSH** au serveur

## ⚡ Déploiement en 10 Minutes

### 1. Se connecter au serveur

```bash
ssh root@VOTRE_IP_SERVEUR
```

### 2. Installer Docker

```bash
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh
apt install docker-compose -y
```

### 3. Cloner ou transférer les fichiers

**Option A: Via Git**
```bash
cd /var/www
git clone https://votre-repo.git gamezone
cd gamezone
```

**Option B: Via SFTP**
```bash
# Sur votre machine locale
scp -r "c:\xampp\htdocs\projet ismo\*" root@VOTRE_IP:/var/www/gamezone/
```

### 4. Configuration

```bash
cd /var/www/gamezone

# Copier le fichier .env
cp .env.example .env

# Éditer le .env
nano .env
```

**Modifier au minimum:**
```env
DB_PASSWORD=votre_mot_de_passe_securise_ici
MYSQL_ROOT_PASSWORD=votre_root_password_ici
SESSION_SECRET=une_longue_chaine_aleatoire_de_64_caracteres
```

Pour générer un secret sécurisé:
```bash
openssl rand -hex 32
```

### 5. Rendre le script de déploiement exécutable

```bash
chmod +x deploy.sh
```

### 6. Déployer l'application

```bash
./deploy.sh init
```

**Le script va:**
- ✅ Créer les dossiers nécessaires
- ✅ Build les images Docker
- ✅ Démarrer tous les services
- ✅ Initialiser la base de données

### 7. Vérifier que tout fonctionne

```bash
./deploy.sh status
```

**Vous devriez voir:**
```
✓ Frontend: OK
✓ Backend: OK
✓ MySQL: OK
✓ Nginx: OK
```

### 8. Configurer le pare-feu

```bash
ufw allow 22/tcp   # SSH
ufw allow 80/tcp   # HTTP
ufw allow 443/tcp  # HTTPS
ufw enable
```

### 9. Accéder à l'application

**HTTP (temporaire):**
```
http://VOTRE_IP
```

**Ou avec votre domaine:**
```
http://votredomaine.com
```

---

## 🔐 Configurer HTTPS (Let's Encrypt)

### 1. Installer Certbot

```bash
apt install certbot -y
```

### 2. Arrêter temporairement Nginx

```bash
docker-compose stop nginx
```

### 3. Obtenir le certificat SSL

```bash
certbot certonly --standalone -d votredomaine.com -d www.votredomaine.com
```

### 4. Copier les certificats

```bash
mkdir -p ssl
cp /etc/letsencrypt/live/votredomaine.com/fullchain.pem ssl/
cp /etc/letsencrypt/live/votredomaine.com/privkey.pem ssl/
```

### 5. Activer HTTPS dans nginx.conf

```bash
nano nginx.conf
```

Décommenter la section HTTPS (lignes avec `# server {` pour le port 443)

### 6. Redémarrer Nginx

```bash
docker-compose start nginx
```

### 7. Configurer le renouvellement automatique

```bash
crontab -e
```

Ajouter:
```
0 3 * * * certbot renew --quiet --deploy-hook "cp /etc/letsencrypt/live/votredomaine.com/*.pem /var/www/gamezone/ssl/ && docker-compose restart nginx"
```

---

## 🔄 Commandes Utiles

### Voir les logs en temps réel
```bash
./deploy.sh logs
```

### Redémarrer les services
```bash
./deploy.sh restart
```

### Faire un backup de la DB
```bash
./deploy.sh backup
```

### Mettre à jour l'application
```bash
./deploy.sh update
```

### Vérifier l'état des services
```bash
./deploy.sh status
```

### Arrêter tous les services
```bash
./deploy.sh stop
```

---

## 📊 Monitoring

### Voir l'utilisation des ressources

```bash
docker stats
```

### Voir l'espace disque

```bash
df -h
```

### Voir les logs d'un service spécifique

```bash
docker-compose logs frontend
docker-compose logs backend
docker-compose logs mysql
```

---

## 🐛 Dépannage Rapide

### Frontend ne démarre pas
```bash
docker-compose logs frontend
docker-compose restart frontend
```

### Backend erreur 500
```bash
docker-compose logs backend
docker-compose exec backend bash
# Vérifier les permissions
chown -R www-data:www-data /var/www/html
```

### MySQL ne se connecte pas
```bash
docker-compose restart mysql
# Attendre 30 secondes
sleep 30
docker-compose exec mysql mysqladmin ping
```

### Tout réinitialiser (⚠️ ATTENTION: perte de données)
```bash
./deploy.sh clean
./deploy.sh init
```

---

## 📈 Optimisations Post-Déploiement

### 1. Activer le cache Redis (optionnel)

Ajouter au `docker-compose.yml`:
```yaml
redis:
  image: redis:alpine
  restart: unless-stopped
  networks:
    - gamezone_network
```

### 2. Configurer les backups automatiques

Le script crée des backups dans `./backups/`

Pour les envoyer sur un stockage externe:
```bash
# Installer rclone
curl https://rclone.org/install.sh | bash

# Configurer votre cloud (Google Drive, Dropbox, etc.)
rclone config

# Ajouter au crontab
0 4 * * * rclone sync /var/www/gamezone/backups moncloud:/gamezone-backups
```

### 3. Monitoring avec Uptime Kuma (optionnel)

```bash
docker run -d \
  --name uptime-kuma \
  -p 3001:3001 \
  -v uptime-kuma:/app/data \
  --restart=always \
  louislam/uptime-kuma:1
```

Accès: `http://VOTRE_IP:3001`

---

## 🎯 Résumé des URLs

| Service | URL | Port |
|---------|-----|------|
| **Frontend** | http://votredomaine.com | 80/443 |
| **Backend API** | http://votredomaine.com/api | - |
| **Admin** | http://votredomaine.com/admin | - |
| **MySQL** | localhost:3306 | 3306 |
| **Uploads** | http://votredomaine.com/uploads | - |

---

## 💰 Coût Estimé

| Service | Prix |
|---------|------|
| VPS (4GB RAM) | 6-10€/mois |
| Domaine | 10€/an |
| SSL | Gratuit (Let's Encrypt) |
| **Total** | **~7€/mois** |

---

## 📞 Support

**Documentation complète:** `GUIDE_DEPLOIEMENT_COMPLET.md`

**Vérifier les logs:**
```bash
./deploy.sh logs
```

**Vérifier l'état:**
```bash
./deploy.sh status
```

---

## ✅ Checklist Post-Déploiement

- [ ] Application accessible sur le domaine
- [ ] HTTPS configuré et fonctionnel
- [ ] Base de données initialisée
- [ ] Compte admin créé
- [ ] Backups automatiques configurés
- [ ] Monitoring en place
- [ ] Firewall configuré
- [ ] DNS configuré correctement

---

**🎉 Félicitations ! Votre application GameZone est en production !**

**Frontend:** https://votredomaine.com  
**Admin:** https://votredomaine.com/admin  
**API:** https://votredomaine.com/api
