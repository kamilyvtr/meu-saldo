#!/usr/bin/env bash
#
# Deploy do Meu Saldo em hospedagem compartilhada (cPanel/Hostinger).
#
# Uso:
#   cd /home/uXXX/laravel-app
#   ./deploy.sh
#
# Variáveis opcionais:
#   BRANCH=main                    branch que será publicada
#   PHP_BIN=/usr/bin/php           binário do PHP (padrão: php)
#   SKIP_ASSETS=1                  pula o npm build (use quando os assets já foram enviados)
#   PUBLIC_HTML=/home/uXXX/public_html/financeiro
#                                  pasta pública do subdomínio; se informada,
#                                  sincroniza build/assets nela automaticamente
#
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR"

PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
NPM_BIN="${NPM_BIN:-npm}"
BRANCH="${BRANCH:-main}"
SKIP_ASSETS="${SKIP_ASSETS:-0}"
PUBLIC_HTML="${PUBLIC_HTML:-}"

log() { printf '\n\033[1;36m==> %s\033[0m\n' "$*"; }
fail() { printf '\n\033[1;31mERRO: %s\033[0m\n' "$*" >&2; exit 1; }

# Garante que o modo de manutenção seja sempre desligado, mesmo em caso de erro.
cleanup() {
    "$PHP_BIN" artisan up >/dev/null 2>&1 || true
}
trap cleanup EXIT

log "Verificando pré-requisitos"
[ -f .env ] || fail ".env não encontrado. Copie .env.example para .env e configure antes do deploy."
command -v "$PHP_BIN" >/dev/null 2>&1 || fail "PHP não encontrado. Defina PHP_BIN=/caminho/do/php."
command -v "$COMPOSER_BIN" >/dev/null 2>&1 || fail "Composer não encontrado. Defina COMPOSER_BIN=/caminho/do/composer."
"$PHP_BIN" -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);' || fail "É necessário PHP 8.3 ou superior."

log "Ativando modo de manutenção"
"$PHP_BIN" artisan down --retry=15 --refresh=15 || true

log "Atualizando código (git pull origin $BRANCH)"
git pull --ff-only origin "$BRANCH"

log "Instalando dependências PHP (produção)"
"$COMPOSER_BIN" install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-progress

if [ "$SKIP_ASSETS" != "1" ]; then
    if command -v "$NPM_BIN" >/dev/null 2>&1; then
        log "Instalando dependências JS e compilando assets"
        "$NPM_BIN" ci --no-audit --no-fund
        NODE_OPTIONS="--max-old-space-size=4096" "$NPM_BIN" run build
    else
        log "AVISO: npm não encontrado. Pulando build dos assets."
        log "       Envie a pasta public/build gerada localmente para o servidor."
    fi
else
    log "SKIP_ASSETS=1: usando os assets já presentes em public/build"
fi

log "Executando migrations"
"$PHP_BIN" artisan migrate --force

log "Sincronizando assets públicos"
if [ -n "$PUBLIC_HTML" ]; then
    [ -d "$PUBLIC_HTML" ] || fail "PUBLIC_HTML não existe: $PUBLIC_HTML"
    rm -rf "$PUBLIC_HTML/build" "$PUBLIC_HTML/icons"
    cp -a public/build public/icons "$PUBLIC_HTML"/
    cp -a public/.htaccess "$PUBLIC_HTML"/
    cp -a public/favicon.ico public/favicon.svg public/favicon-32.png \
        public/apple-touch-icon.png public/manifest.webmanifest \
        public/robots.txt public/sw.js "$PUBLIC_HTML"/
    rm -f "$PUBLIC_HTML/storage"
    ln -sfn "$APP_DIR/storage/app/public" "$PUBLIC_HTML/storage"
else
    log "PUBLIC_HTML não definido; assets permanecem apenas em public/."
fi

log "Criando link simbólico de storage"
if [ ! -e public/storage ]; then
    "$PHP_BIN" artisan storage:link || true
else
    log "public/storage já existe, mantendo."
fi

log "Ajustando permissões"
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

log "Limpando e recriando caches de produção"
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache

log "Reiniciando workers de fila (se houver)"
"$PHP_BIN" artisan queue:restart || true

log "Deploy concluído com sucesso."
