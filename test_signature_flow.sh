#!/bin/bash

# Script de test complet du flux de signature
# À exécuter après le déploiement sur le serveur

set -e

echo "=========================================="
echo "Test Complet du Flux de Signature"
echo "=========================================="
echo ""

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Configuration
APP_PATH="/var/www/html/e-administration"
LOG_FILE="$APP_PATH/storage/logs/laravel.log"

echo -e "${YELLOW}1. Update du code depuis GitHub${NC}"
cd "$APP_PATH"
git pull origin main
echo -e "${GREEN}✓ Git pull réussi${NC}"
echo ""

echo -e "${YELLOW}2. Application de la migration${NC}"
php artisan migrate --force
echo -e "${GREEN}✓ Migration appliquée${NC}"
echo ""

echo -e "${YELLOW}3. Cache des routes${NC}"
php artisan route:cache && php artisan config:cache
echo -e "${GREEN}✓ Cache rafraîchi${NC}"
echo ""

echo -e "${YELLOW}4. Vérification des colonnes BD${NC}"
COLS=$(mysql e_parapheur -e "DESCRIBE workflow_executions;" | grep platform | wc -l)
if [ "$COLS" -ge 2 ]; then
    echo -e "${GREEN}✓ Colonnes existent${NC}"
    mysql e_parapheur -e "DESCRIBE workflow_executions;" | grep platform
else
    echo -e "${RED}✗ Colonnes manquantes!${NC}"
    exit 1
fi
echo ""

echo -e "${YELLOW}5. État avant test${NC}"
echo "Nombre d'exécutions avec platform_workflow_id non-NULL:"
mysql e_parapheur -e "SELECT COUNT(*) as count FROM workflow_executions WHERE platform_workflow_id IS NOT NULL;"
echo ""
echo "État du log avant:"
tail -5 "$LOG_FILE" || echo "(aucun log)"
echo ""

echo -e "${YELLOW}6. Effacement des logs avant test${NC}"
echo "" >> "$LOG_FILE"
echo "[$(date '+%Y-%m-%d %H:%M:%S')] === DÉBUT TEST SIGNATURE ===" >> "$LOG_FILE"
echo -e "${GREEN}✓ Marqueur inséré dans les logs${NC}"
echo ""

echo -e "${YELLOW}=========================================="
echo "Prêt pour tester!"
echo "=========================================="
echo ""
echo "Prochaines étapes:"
echo "1. Accéder à https://e-administration.gedsante.ci/signatures"
echo "2. Créer/lancer un test de signature"
echo "3. Cliquer sur 'Signer'"
echo "4. Revenir ici et exécuter: tail -100 storage/logs/laravel.log | grep -i platform"
echo ""
echo "Commandes utiles pendant le test:"
echo ""
echo "  # Voir les logs en direct"
echo "  tail -f storage/logs/laravel.log | grep -i 'platform\\|sunnystamp'"
echo ""
echo "  # Vérifier l'enregistrement"
echo "  mysql e_parapheur -e \"SELECT id, platform_workflow_id, platform_status FROM workflow_executions ORDER BY started_at DESC LIMIT 1;\""
echo ""
echo "  # Tester l'endpoint diagnostic (remplacer {ID})"
echo "  curl https://e-administration.gedsante.ci/api/signature/diag/{ID}"
