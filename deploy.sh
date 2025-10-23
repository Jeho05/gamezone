#!/bin/bash

# Script de déploiement automatisé GameZone
# Usage: ./deploy.sh [init|update|restart|logs|backup]

set -e

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Fonctions d'affichage
info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
    exit 1
}

# Vérifier que Docker est installé
check_docker() {
    if ! command -v docker &> /dev/null; then
        error "Docker n'est pas installé. Installez-le d'abord: https://docs.docker.com/get-docker/"
    fi
    
    if ! command -v docker-compose &> /dev/null; then
        error "Docker Compose n'est pas installé."
    fi
    
    info "Docker et Docker Compose sont installés ✓"
}

# Initialisation (premier déploiement)
init_deploy() {
    info "🚀 Initialisation du déploiement..."
    
    # Vérifier le fichier .env
    if [ ! -f .env ]; then
        warn "Fichier .env manquant. Copie de .env.example..."
        cp .env.example .env
        warn "⚠️  Veuillez éditer le fichier .env avec vos vraies valeurs avant de continuer!"
        read -p "Appuyez sur Entrée une fois le .env configuré..."
    fi
    
    # Créer les dossiers nécessaires
    info "Création des dossiers..."
    mkdir -p uploads ssl backups
    
    # Build et démarrage des conteneurs
    info "Build des images Docker..."
    docker-compose build --no-cache
    
    info "Démarrage des conteneurs..."
    docker-compose up -d
    
    info "Attente du démarrage de MySQL (30 secondes)..."
    sleep 30
    
    # Vérifier l'état des services
    info "Vérification de l'état des services..."
    docker-compose ps
    
    info "✅ Déploiement initial terminé!"
    info "🌐 Frontend: http://localhost:80"
    info "🔌 Backend: http://localhost:80/api"
    info "📊 Voir les logs: ./deploy.sh logs"
}

# Mise à jour (redéploiement)
update_deploy() {
    info "🔄 Mise à jour de l'application..."
    
    # Pull les changements (si Git)
    if [ -d .git ]; then
        info "Pull des derniers changements Git..."
        git pull origin main || warn "Pas de changements Git"
    fi
    
    # Backup de la base de données avant update
    info "Backup de la base de données..."
    backup_database
    
    # Rebuild et redémarrage
    info "Rebuild des conteneurs..."
    docker-compose down
    docker-compose build --no-cache
    docker-compose up -d
    
    info "✅ Mise à jour terminée!"
}

# Redémarrage des services
restart_services() {
    info "♻️  Redémarrage des services..."
    docker-compose restart
    info "✅ Services redémarrés!"
}

# Afficher les logs
show_logs() {
    info "📋 Logs des services (Ctrl+C pour quitter)..."
    docker-compose logs -f --tail=100
}

# Backup de la base de données
backup_database() {
    BACKUP_DIR="./backups"
    DATE=$(date +%Y%m%d_%H%M%S)
    BACKUP_FILE="$BACKUP_DIR/db_backup_$DATE.sql"
    
    info "💾 Backup de la base de données..."
    mkdir -p $BACKUP_DIR
    
    docker-compose exec -T mysql mysqldump -u root -p${MYSQL_ROOT_PASSWORD} ${DB_NAME} > $BACKUP_FILE 2>/dev/null || {
        error "Erreur lors du backup de la base de données"
    }
    
    # Compresser le backup
    gzip $BACKUP_FILE
    
    info "✅ Backup créé: $BACKUP_FILE.gz"
    
    # Garder seulement les 7 derniers backups
    find $BACKUP_DIR -name "db_backup_*.sql.gz" -mtime +7 -delete
}

# Arrêt des services
stop_services() {
    info "🛑 Arrêt des services..."
    docker-compose down
    info "✅ Services arrêtés!"
}

# Nettoyage complet (attention: supprime les données)
clean_all() {
    read -p "⚠️  ATTENTION: Ceci va supprimer TOUS les conteneurs et volumes. Continuer? (y/N) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        info "🧹 Nettoyage complet..."
        docker-compose down -v
        docker system prune -a -f
        info "✅ Nettoyage terminé!"
    else
        info "Annulé."
    fi
}

# Vérifier l'état des services
check_status() {
    info "📊 État des services:"
    docker-compose ps
    
    echo ""
    info "🏥 Health checks:"
    
    # Frontend
    if curl -f http://localhost:3000 &>/dev/null; then
        echo -e "${GREEN}✓${NC} Frontend: OK"
    else
        echo -e "${RED}✗${NC} Frontend: DOWN"
    fi
    
    # Backend
    if curl -f http://localhost:8080/api/health.php &>/dev/null; then
        echo -e "${GREEN}✓${NC} Backend: OK"
    else
        echo -e "${RED}✗${NC} Backend: DOWN"
    fi
    
    # MySQL
    if docker-compose exec -T mysql mysqladmin ping -h localhost -u root -p${MYSQL_ROOT_PASSWORD} &>/dev/null; then
        echo -e "${GREEN}✓${NC} MySQL: OK"
    else
        echo -e "${RED}✗${NC} MySQL: DOWN"
    fi
    
    # Nginx
    if curl -f http://localhost:80/health &>/dev/null; then
        echo -e "${GREEN}✓${NC} Nginx: OK"
    else
        echo -e "${RED}✗${NC} Nginx: DOWN"
    fi
}

# Menu principal
main() {
    check_docker
    
    case "$1" in
        init)
            init_deploy
            ;;
        update)
            update_deploy
            ;;
        restart)
            restart_services
            ;;
        logs)
            show_logs
            ;;
        backup)
            backup_database
            ;;
        stop)
            stop_services
            ;;
        clean)
            clean_all
            ;;
        status)
            check_status
            ;;
        *)
            echo "Usage: ./deploy.sh {init|update|restart|logs|backup|stop|clean|status}"
            echo ""
            echo "Commandes:"
            echo "  init    - Initialisation du déploiement (première fois)"
            echo "  update  - Mise à jour de l'application"
            echo "  restart - Redémarrer les services"
            echo "  logs    - Afficher les logs en temps réel"
            echo "  backup  - Backup de la base de données"
            echo "  stop    - Arrêter tous les services"
            echo "  clean   - Nettoyage complet (⚠️ supprime les données)"
            echo "  status  - Vérifier l'état des services"
            exit 1
            ;;
    esac
}

# Exécuter la fonction principale
main "$@"
