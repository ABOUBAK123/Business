#!/bin/bash

# Script de déploiement sur le serveur Ubuntu
# Usage: bash deploy_server.sh

set -e

echo "=== Déploiement sur le serveur e-administration ==="
echo ""

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Configuration
SERVER_USER="root"
SERVER_HOST="gedsante.ci"
SERVER_PATH="/var/www/html/e-administration"
BRANCH="main"

echo -e "${YELLOW}1. Tirage des changements Git${NC}"
ssh "${SERVER_USER}@${SERVER_HOST}" "cd ${SERVER_PATH} && git fetch origin && git pull origin ${BRANCH}" || {
    echo -e "${RED}Erreur lors du git pull${NC}"
    exit 1
}
echo -e "${GREEN}✓ Git pull réussi${NC}"

echo ""
echo -e "${YELLOW}2. Application de la migration${NC}"
ssh "${SERVER_USER}@${SERVER_HOST}" "cd ${SERVER_PATH} && php artisan migrate --force" || {
    echo -e "${RED}Erreur lors de la migration${NC}"
    exit 1
}
echo -e "${GREEN}✓ Migration réussie${NC}"

echo ""
echo -e "${YELLOW}3. Rafraîchissement du cache des routes${NC}"
ssh "${SERVER_USER}@${SERVER_HOST}" "cd ${SERVER_PATH} && php artisan route:cache" || {
    echo -e "${RED}Erreur lors du cache des routes${NC}"
    exit 1
}
echo -e "${GREEN}✓ Cache des routes rafraîchi${NC}"

echo ""
echo -e "${YELLOW}4. Rafraîchissement du cache de configuration${NC}"
ssh "${SERVER_USER}@${SERVER_HOST}" "cd ${SERVER_PATH} && php artisan config:cache" || {
    echo -e "${RED}Erreur lors du cache de configuration${NC}"
    exit 1
}
echo -e "${GREEN}✓ Cache de configuration rafraîchi${NC}"

echo ""
echo -e "${YELLOW}5. Vérification des colonnes de la migration${NC}"
ssh "${SERVER_USER}@${SERVER_HOST}" "mysql e_parapheur -e \"DESCRIBE workflow_executions;\" | grep platform" || {
    echo -e "${YELLOW}⚠ Colonnes non trouvées (peut être normal si pas encore visible)${NC}"
}
echo -e "${GREEN}✓ Vérification terminée${NC}"

echo ""
echo -e "${GREEN}=== Déploiement réussi ! ===${NC}"
echo ""
echo "Prochaines étapes:"
echo "1. Tester un workflow de signature complet"
echo "2. Vérifier que platform_workflow_id est enregistré en DB:"
echo "   mysql e_parapheur -e \"SELECT id, platform_workflow_id FROM workflow_executions LIMIT 1;\""
echo "3. Consulter les logs:"
echo "   tail -50 /var/www/html/e-administration/storage/logs/laravel.log | grep platform_workflow"
echo "4. Accéder au endpoint de diagnostic:"
echo "   curl https://e-administration.gedsante.ci/api/signature/diag/{EXECUTION_ID}"
