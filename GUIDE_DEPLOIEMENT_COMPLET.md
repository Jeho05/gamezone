# 🚀 Guide de Déploiement Complet - GameZone

## 📋 Architecture de l'Application

```
┌─────────────────────────────────────────────────────────┐
│                   FRONTEND (React)                       │
│  React Router v7 + SSR (Node.js) + Vite                │
│  Port: 4000 (dev) / 3000 (prod)                        │
└─────────────────┬───────────────────────────────────────┘
                  │ API Calls (fetch)
                  │
┌─────────────────▼───────────────────────────────────────┐
│                   BACKEND (PHP)                          │
│  PHP 8.x + APIs REST                                    │
│  Port: 80 (Apache)                                      │
└─────────────────┬───────────────────────────────────────┘
                  │ SQL Queries
                  │
┌─────────────────▼───────────────────────────────────────┐
│                   BASE DE DONNÉES                        │
│  MySQL 8.x                                              │
│  Port: 3306                                             │
└─────────────────────────────────────────────────────────┘
```

---

## 🎯 Option Recommandée : Serveur VPS avec Docker

### Avantages
✅ Frontend et Backend sur le même domaine (pas de CORS)  
✅ Gestion simplifiée avec Docker  
✅ Scalabilité facile  
✅ Coût maîtrisé (à partir de 5€/mois)  
✅ SSL automatique avec Let's Encrypt  

---

## 📦 ÉTAPE 1 : Préparer les Fichiers

### 1.1 Créer Dockerfile pour le Frontend

```dockerfile
# Dockerfile.frontend
FROM node:20-alpine AS builder

WORKDIR /app

# Copier package.json
COPY createxyz-project/_/apps/web/package*.json ./

# Installer les dépendances
RUN npm ci

# Copier le code source
COPY createxyz-project/_/apps/web ./

# Build l'application
RUN npm run build

# Image de production
FROM node:20-alpine

WORKDIR /app

COPY --from=builder /app/package*.json ./
COPY --from=builder /app/build ./build
COPY --from=builder /app/node_modules ./node_modules

EXPOSE 3000

CMD ["node", "build/server/index.js"]
```

### 1.2 Créer Dockerfile pour le Backend

```dockerfile
# Dockerfile.backend
FROM php:8.2-apache

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Activer mod_rewrite
RUN a2enmod rewrite

# Copier le code PHP
COPY api /var/www/html/api
COPY .htaccess /var/www/html/

# Permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
```

### 1.3 Créer docker-compose.yml

```yaml
version: '3.8'

services:
  # Frontend Node.js
  frontend:
    build:
      context: .
      dockerfile: Dockerfile.frontend
    ports:
      - "3000:3000"
    environment:
      - NODE_ENV=production
      - API_BASE_URL=http://backend/api
    depends_on:
      - backend
    restart: unless-stopped

  # Backend PHP
  backend:
    build:
      context: .
      dockerfile: Dockerfile.backend
    ports:
      - "8080:80"
    environment:
      - DB_HOST=mysql
      - DB_NAME=gamezone
      - DB_USER=gamezone_user
      - DB_PASSWORD=${DB_PASSWORD}
    depends_on:
      - mysql
    volumes:
      - ./uploads:/var/www/html/uploads
    restart: unless-stopped

  # Base de données MySQL
  mysql:
    image: mysql:8.0
    environment:
      - MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
      - MYSQL_DATABASE=gamezone
      - MYSQL_USER=gamezone_user
      - MYSQL_PASSWORD=${DB_PASSWORD}
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
      - ./api/schema.sql:/docker-entrypoint-initdb.d/01-schema.sql
      - ./api/migrations:/docker-entrypoint-initdb.d/migrations
    restart: unless-stopped

  # Nginx (reverse proxy)
  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
      - ./ssl:/etc/nginx/ssl
    depends_on:
      - frontend
      - backend
    restart: unless-stopped

volumes:
  mysql_data:
```

### 1.4 Créer nginx.conf

```nginx
events {
    worker_connections 1024;
}

http {
    upstream frontend {
        server frontend:3000;
    }

    upstream backend {
        server backend:80;
    }

    server {
        listen 80;
        server_name votredomaine.com;

        # Redirection HTTPS (après configuration SSL)
        # return 301 https://$server_name$request_uri;

        # Frontend
        location / {
            proxy_pass http://frontend;
            proxy_http_version 1.1;
            proxy_set_header Upgrade $http_upgrade;
            proxy_set_header Connection 'upgrade';
            proxy_set_header Host $host;
            proxy_cache_bypass $http_upgrade;
        }

        # Backend API
        location /api/ {
            proxy_pass http://backend/api/;
            proxy_set_header Host $host;
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
            proxy_set_header X-Forwarded-Proto $scheme;
        }

        # Uploads
        location /uploads/ {
            proxy_pass http://backend/uploads/;
        }
    }

    # Configuration HTTPS (à activer après Let's Encrypt)
    # server {
    #     listen 443 ssl http2;
    #     server_name votredomaine.com;
    #
    #     ssl_certificate /etc/nginx/ssl/fullchain.pem;
    #     ssl_certificate_key /etc/nginx/ssl/privkey.pem;
    #
    #     location / {
    #         proxy_pass http://frontend;
    #         # ... (même config que HTTP)
    #     }
    #
    #     location /api/ {
    #         proxy_pass http://backend/api/;
    #         # ... (même config que HTTP)
    #     }
    # }
}
```

---

## 🌐 ÉTAPE 2 : Choisir un Hébergeur VPS

### Options Recommandées

#### 1. DigitalOcean (Recommandé)
- **Prix:** À partir de 6$/mois
- **Avantages:** Interface simple, marketplace Docker
- **Lien:** https://www.digitalocean.com/

#### 2. Contabo
- **Prix:** À partir de 5€/mois
- **Avantages:** Excellent rapport qualité/prix
- **Lien:** https://contabo.com/

#### 3. Hetzner Cloud
- **Prix:** À partir de 4€/mois
- **Avantages:** Data centers en Europe
- **Lien:** https://www.hetzner.com/cloud

#### 4. OVH
- **Prix:** À partir de 7€/mois
- **Avantages:** Français, support en français
- **Lien:** https://www.ovhcloud.com/

### Spécifications Minimales
- **CPU:** 2 vCPU
- **RAM:** 4 GB
- **Stockage:** 80 GB SSD
- **OS:** Ubuntu 22.04 LTS

---

## 🔧 ÉTAPE 3 : Configuration du Serveur

### 3.1 Connexion SSH

```bash
ssh root@VOTRE_IP_SERVEUR
```

### 3.2 Installation de Docker

```bash
# Mettre à jour le système
apt update && apt upgrade -y

# Installer Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh

# Installer Docker Compose
apt install docker-compose -y

# Vérifier l'installation
docker --version
docker-compose --version
```

### 3.3 Configuration du Firewall

```bash
# UFW (Ubuntu Firewall)
ufw allow 22/tcp    # SSH
ufw allow 80/tcp    # HTTP
ufw allow 443/tcp   # HTTPS
ufw enable
```

---

## 📤 ÉTAPE 4 : Déploiement

### 4.1 Transférer les Fichiers

**Option A: Git (Recommandé)**
```bash
# Sur le serveur
git clone https://votre-repo.git /var/www/gamezone
cd /var/www/gamezone
```

**Option B: SFTP**
```bash
# En local
scp -r "c:\xampp\htdocs\projet ismo\*" root@VOTRE_IP:/var/www/gamezone/
```

### 4.2 Configuration des Variables d'Environnement

```bash
# Sur le serveur
cd /var/www/gamezone

# Créer le fichier .env
nano .env
```

Contenu du `.env`:
```env
# Base de données
DB_PASSWORD=votre_password_securise
MYSQL_ROOT_PASSWORD=votre_root_password_securise

# Frontend
NODE_ENV=production
API_BASE_URL=http://localhost:8080/api

# Backend PHP
DB_HOST=mysql
DB_NAME=gamezone
DB_USER=gamezone_user

# Session
SESSION_SECRET=votre_secret_aleatoire_tres_long
```

### 4.3 Créer les Fichiers Docker

```bash
# Créer Dockerfile.frontend
nano Dockerfile.frontend
# (Copier le contenu de 1.1)

# Créer Dockerfile.backend
nano Dockerfile.backend
# (Copier le contenu de 1.2)

# Créer docker-compose.yml
nano docker-compose.yml
# (Copier le contenu de 1.3)

# Créer nginx.conf
nano nginx.conf
# (Copier le contenu de 1.4)
```

### 4.4 Lancer l'Application

```bash
# Build et démarrer les conteneurs
docker-compose up -d --build

# Vérifier que tout fonctionne
docker-compose ps

# Voir les logs
docker-compose logs -f
```

---

## 🔐 ÉTAPE 5 : Configuration SSL (HTTPS)

### 5.1 Installer Certbot

```bash
apt install certbot python3-certbot-nginx -y
```

### 5.2 Obtenir un Certificat SSL

```bash
# Arrêter temporairement nginx
docker-compose stop nginx

# Obtenir le certificat
certbot certonly --standalone -d votredomaine.com -d www.votredomaine.com

# Redémarrer nginx
docker-compose start nginx
```

### 5.3 Configuration Auto-Renouvellement

```bash
# Tester le renouvellement
certbot renew --dry-run

# Ajouter un cron job
crontab -e

# Ajouter cette ligne (renouvellement quotidien)
0 3 * * * certbot renew --quiet --post-hook "docker-compose restart nginx"
```

---

## 🗄️ ÉTAPE 6 : Configuration de la Base de Données

### 6.1 Importer le Schéma

```bash
# Attendre que MySQL soit prêt (30 secondes)
sleep 30

# Importer les migrations
docker-compose exec mysql mysql -u root -p${MYSQL_ROOT_PASSWORD} gamezone < api/migrations/add_game_purchase_system.sql
docker-compose exec mysql mysql -u root -p${MYSQL_ROOT_PASSWORD} gamezone < api/migrations/add_reward_game_packages.sql
```

### 6.2 Créer un Utilisateur Admin

```bash
# Se connecter au conteneur backend
docker-compose exec backend bash

# Exécuter le script de création d'admin
php /var/www/html/api/admin/create_admin.php
```

---

## 🔍 ÉTAPE 7 : Vérification

### 7.1 Tests de Base

```bash
# Frontend
curl http://votredomaine.com

# Backend API
curl http://votredomaine.com/api/auth/check.php

# Base de données
docker-compose exec mysql mysql -u root -p${MYSQL_ROOT_PASSWORD} -e "SHOW DATABASES;"
```

### 7.2 Vérifier les Services

```bash
# Statut des conteneurs
docker-compose ps

# Logs en temps réel
docker-compose logs -f frontend
docker-compose logs -f backend
docker-compose logs -f mysql
```

---

## 🔄 ÉTAPE 8 : Mises à Jour

### 8.1 Déployer une Nouvelle Version

```bash
# Pull les nouveaux changements
git pull origin main

# Rebuild et redémarrer
docker-compose down
docker-compose up -d --build

# Vérifier
docker-compose ps
```

### 8.2 Rollback en Cas de Problème

```bash
# Revenir à la version précédente
git checkout HEAD~1

# Redéployer
docker-compose down
docker-compose up -d --build
```

---

## 🛠️ ÉTAPE 9 : Monitoring et Maintenance

### 9.1 Monitoring Simple

```bash
# Espace disque
df -h

# Utilisation mémoire
free -h

# Logs Docker
docker-compose logs --tail=100 -f
```

### 9.2 Backups Automatiques

Créer un script de backup:

```bash
nano /root/backup.sh
```

Contenu:
```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups"

# Créer le dossier de backup
mkdir -p $BACKUP_DIR

# Backup de la base de données
docker-compose exec -T mysql mysqldump -u root -p${MYSQL_ROOT_PASSWORD} gamezone > $BACKUP_DIR/db_$DATE.sql

# Backup des uploads
tar -czf $BACKUP_DIR/uploads_$DATE.tar.gz /var/www/gamezone/uploads/

# Garder seulement les 7 derniers backups
find $BACKUP_DIR -name "db_*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "uploads_*.tar.gz" -mtime +7 -delete

echo "Backup completed: $DATE"
```

Rendre exécutable et ajouter au cron:
```bash
chmod +x /root/backup.sh
crontab -e
# Ajouter: 0 2 * * * /root/backup.sh
```

---

## 📊 ÉTAPE 10 : Optimisations Production

### 10.1 Configuration PHP (Backend)

Créer `php.ini`:
```ini
upload_max_filesize = 50M
post_max_size = 50M
memory_limit = 256M
max_execution_time = 300
```

### 10.2 Configuration Node.js (Frontend)

Ajouter au `Dockerfile.frontend`:
```dockerfile
ENV NODE_ENV=production
ENV NODE_OPTIONS="--max-old-space-size=4096"
```

### 10.3 Cache et Compression

Nginx activera automatiquement gzip pour les assets.

---

## 🚨 Dépannage

### Problème : Frontend ne démarre pas
```bash
docker-compose logs frontend
# Vérifier les erreurs de build
docker-compose build frontend --no-cache
```

### Problème : Backend 500 errors
```bash
docker-compose logs backend
# Vérifier les permissions
docker-compose exec backend chown -R www-data:www-data /var/www/html
```

### Problème : MySQL ne se connecte pas
```bash
# Vérifier que MySQL est prêt
docker-compose exec mysql mysqladmin ping -h localhost

# Recréer la base de données
docker-compose down -v
docker-compose up -d
```

---

## 💰 Coûts Estimés

### Hébergement VPS
- **Serveur:** 5-10€/mois
- **Domaine:** 10€/an
- **SSL:** Gratuit (Let's Encrypt)
- **Total:** ~7€/mois

### Alternative Cloud
- **Frontend (Vercel):** Gratuit
- **Backend (Hostinger):** 5€/mois
- **Base de données:** Inclus
- **Total:** ~5€/mois

---

## 📞 Support

### Ressources
- Documentation Docker: https://docs.docker.com/
- Documentation Nginx: https://nginx.org/en/docs/
- Tutoriels DigitalOcean: https://www.digitalocean.com/community/tutorials

### Commandes Utiles

```bash
# Redémarrer tous les services
docker-compose restart

# Voir l'utilisation des ressources
docker stats

# Nettoyer les images inutilisées
docker system prune -a

# Accéder au shell d'un conteneur
docker-compose exec frontend sh
docker-compose exec backend bash
docker-compose exec mysql mysql -u root -p
```

---

## ✅ Checklist de Déploiement

- [ ] VPS commandé et configuré
- [ ] Docker et Docker Compose installés
- [ ] Firewall configuré
- [ ] Domaine pointé vers le serveur IP
- [ ] Fichiers Docker créés
- [ ] Variables d'environnement configurées
- [ ] Application déployée (`docker-compose up -d`)
- [ ] Base de données initialisée
- [ ] SSL configuré (Certbot)
- [ ] Backup automatique configuré
- [ ] Tests de fonctionnement OK
- [ ] Monitoring en place

---

**🎉 Votre application GameZone est maintenant en production !**

Domaine : https://votredomaine.com  
Admin : https://votredomaine.com/admin  
API : https://votredomaine.com/api
