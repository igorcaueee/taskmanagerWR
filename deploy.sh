#!/bin/bash

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

step() { echo -e "\n${YELLOW}▶ $1${NC}"; }
ok()   { echo -e "${GREEN}✔ $1${NC}"; }
fail() { echo -e "${RED}✘ Erro: $1${NC}"; exit 1; }

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}         DEPLOY - Task Manager          ${NC}"
echo -e "${GREEN}========================================${NC}"

step "Atualizando código (git pull)"
git fetch origin master || fail "git fetch falhou"
git reset --hard origin/master || fail "git reset falhou"
git clean -fd || fail "git clean falhou"
ok "Código atualizado"

step "Instalando dependências PHP"
composer install --no-dev --optimize-autoloader --no-interaction || fail "composer install falhou"
ok "Dependências PHP instaladas"

step "Rodando migrations"
php artisan migrate --force || fail "migrate falhou"
ok "Migrations executadas"

step "Buildando assets frontend"
npm ci --silent || fail "npm ci falhou"
npm run build || fail "npm run build falhou"
ok "Assets gerados"

step "Otimizando a aplicação"
php artisan optimize || fail "optimize falhou"
ok "Cache de config, rotas e views gerado"

step "Reiniciando filas"
php artisan queue:restart
ok "Filas reiniciadas"

echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}      Deploy concluído com sucesso!      ${NC}"
echo -e "${GREEN}========================================${NC}\n"
