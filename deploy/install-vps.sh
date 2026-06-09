#!/bin/bash
# Enzo Tech — instalação na VPS (aaPanel / /www/wwwroot)
# Uso: sudo bash deploy/install-vps.sh

set -e

DOMAIN="enzotech.tdesksolutions.com.br"
SITE_ROOT="/www/wwwroot/${DOMAIN}"
REPO="https://github.com/douglasmouradev/s.enzotech.git"
DB_NAME="enzo_tech"

echo "=== Enzo Tech — deploy ${DOMAIN} ==="

if [ "$(id -u)" -ne 0 ]; then
    echo "Execute como root: sudo bash deploy/install-vps.sh"
    exit 1
fi

mkdir -p "${SITE_ROOT}"
cd "${SITE_ROOT}"

if [ ! -f "index.php" ]; then
    echo "Clonando ${REPO} em ${SITE_ROOT}..."
    if [ -z "$(ls -A)" ]; then
        git clone "${REPO}" .
    else
        echo "Pasta não vazia. Esvazie ${SITE_ROOT} ou clone manualmente."
        exit 1
    fi
else
    echo "Projeto já presente em ${SITE_ROOT}"
fi

echo "=== Permissões ==="
mkdir -p uploads/documentos logs/rate_limit
chown -R www:www uploads logs 2>/dev/null || chown -R www-data:www-data uploads logs
chmod -R 775 uploads logs

echo "=== Configuração ==="
if [ ! -f config/database.local.php ]; then
    cp config/database.local.example.php config/database.local.php
fi
if [ ! -f config/auth.local.php ]; then
    cp config/auth.local.example.php config/auth.local.php
fi

echo ""
echo "=== Próximos passos ==="
echo "1. aaPanel: Site → ${DOMAIN} → Root = ${SITE_ROOT}"
echo "2. PHP 8.2+ (pdo_mysql, fileinfo, mbstring, zip)"
echo "3. Importar banco:"
echo "   mysql -u USUARIO -p ${DB_NAME} < database.sql"
echo "   mysql -u USUARIO -p ${DB_NAME} < database_v2.sql"
echo "   mysql -u USUARIO -p ${DB_NAME} < database_indexes.sql"
echo "4. Editar config/database.local.php"
echo "5. SSL Let's Encrypt para ${DOMAIN}"
echo "6. https://${DOMAIN}/ — altere senha do admin"
echo ""
echo "Concluído."
