#!/bin/bash

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

step() { echo -e "\n${YELLOW}▶ $1${NC}"; }
ok()   { echo -e "${GREEN}✔ $1${NC}"; }
fail() { echo -e "${RED}✘ Erro: $1${NC}"; exit 1; }

cd "$(dirname "$0")"

RETENTION_DAYS=30
BACKUP_DIR="backups"

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}      BACKUP - Task Manager DB          ${NC}"
echo -e "${GREEN}========================================${NC}"

step "Lendo credenciais do .env"
[ -f .env ] || fail ".env nao encontrado"
DB_HOST=$(grep -E '^DB_HOST=' .env | cut -d '=' -f2-)
DB_PORT=$(grep -E '^DB_PORT=' .env | cut -d '=' -f2-)
DB_DATABASE=$(grep -E '^DB_DATABASE=' .env | cut -d '=' -f2-)
DB_USERNAME=$(grep -E '^DB_USERNAME=' .env | cut -d '=' -f2-)
DB_PASSWORD=$(grep -E '^DB_PASSWORD=' .env | cut -d '=' -f2-)
ok "Credenciais carregadas (DB: $DB_DATABASE)"

step "Gerando dump do banco"
mkdir -p "$BACKUP_DIR"
FILE="$BACKUP_DIR/${DB_DATABASE}_$(date +%Y%m%d_%H%M%S).sql.gz"
mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" \
  | gzip > "$FILE" || fail "mysqldump falhou"
ok "Backup criado: $FILE ($(du -h "$FILE" | cut -f1))"

step "Removendo backups com mais de $RETENTION_DAYS dias"
find "$BACKUP_DIR" -name "${DB_DATABASE}_*.sql.gz" -mtime +"$RETENTION_DAYS" -delete
ok "Rotacao concluida"

echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}      Backup concluido com sucesso!      ${NC}"
echo -e "${GREEN}========================================${NC}\n"
