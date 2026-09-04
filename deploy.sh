#!/usr/bin/env bash

set -Eeuo pipefail

APP_DIR="${APP_DIR:-/var/www/unihup}"
BRANCH="${BRANCH:-main}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
WEB_USER="${WEB_USER:-www-data}"
WEB_GROUP="${WEB_GROUP:-www-data}"

cd "$APP_DIR"

if [[ ! -f artisan || ! -f composer.json || ! -f .env ]]; then
    echo "Error: $APP_DIR is not a configured Laravel application (.env is required)." >&2
    exit 1
fi

maintenance_enabled=false

finish() {
    exit_code=$?

    if [[ "$maintenance_enabled" == true ]]; then
        "$PHP_BIN" artisan up || true
    fi

    if (( exit_code != 0 )); then
        echo "Deployment failed (exit code $exit_code)." >&2
    fi
}

trap finish EXIT

echo "Deploying $BRANCH to $APP_DIR..."

git fetch origin "$BRANCH"
git checkout "$BRANCH"
git merge --ff-only "origin/$BRANCH"

if [[ -f package-lock.json ]]; then
    if ! command -v node >/dev/null 2>&1 || ! command -v npm >/dev/null 2>&1; then
        echo "Error: Node.js and npm are required to build frontend assets." >&2
        echo "Install Node.js 22 LTS on this server, then run ./deploy.sh again." >&2
        exit 1
    fi

    if ! node -e 'const [major, minor] = process.versions.node.split(".").map(Number); process.exit(major > 22 || major === 22 && minor >= 12 || major === 20 && minor >= 19 ? 0 : 1)'; then
        echo "Error: Node.js 20.19+ or 22.12+ is required (found $(node --version))." >&2
        echo "Install Node.js 22 LTS on this server, then run ./deploy.sh again." >&2
        exit 1
    fi
fi

"$PHP_BIN" artisan down --retry=60
maintenance_enabled=true

COMPOSER_ALLOW_SUPERUSER=1 "$COMPOSER_BIN" install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

if [[ -f package-lock.json ]]; then
    npm ci
    npm run build

    # The admin panel's Filament theme is a SEPARATE Tailwind build (v3, its
    # own tailwind.config.js under resources/css/filament/admin) that `npm
    # run build` above never touches — that only compiles resources/css/app.css
    # via Vite. Without this, any new Tailwind class used in a Filament page
    # added since the last theme rebuild (e.g. dark: variants on
    # WhatsAppInbox / SupportChat) silently renders unstyled — the class
    # just isn't in the checked-in public/css/filament/admin/theme.css.
    if [[ -f resources/css/filament/admin/theme.css ]]; then
        npx --yes tailwindcss@3 \
            -i resources/css/filament/admin/theme.css \
            -c resources/css/filament/admin/tailwind.config.js \
            -o public/css/filament/admin/theme.css \
            --minify
    fi
fi

"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan migrate --force

# Regenerates Filament Shield's permissions/policies from whatever
# resources/pages/widgets exist in this deploy — idempotent (safe to run
# every time) and what keeps App\Filament\Pages\WhatsAppInbox's
# `page_WhatsAppInbox` permission in sync as pages are added or renamed.
# Existing role grants are preserved; only the permission set itself is
# regenerated.
"$PHP_BIN" artisan shield:generate --all --panel=admin --no-interaction --minimal

"$PHP_BIN" artisan db:seed --force
"$PHP_BIN" artisan storage:link
"$PHP_BIN" artisan optimize
"$PHP_BIN" artisan queue:restart

# Installs/keeps the persistent queue worker current. queue:restart above
# only signals an ALREADY-RUNNING worker to gracefully restart between
# jobs — it starts nothing on its own, so this is what actually processes
# anything dispatched to the queue (see the queue-worker unit file's own
# comment on why an explicit restart, not just queue:restart, is needed to
# pick up fresh code — same class of issue as php-fpm's opcache holding
# onto a stale build after a deploy).
if [[ -f deploy/unihup-queue-worker.service ]] && command -v systemctl >/dev/null 2>&1; then
    install -m 644 deploy/unihup-queue-worker.service /etc/systemd/system/unihup-queue-worker.service
    systemctl daemon-reload
    systemctl enable unihup-queue-worker
    systemctl restart unihup-queue-worker
fi

# Drives routes/console.php's scheduled tasks — without this timer,
# Schedule::command() entries are registered but nothing ever calls
# schedule:run to fire them.
if [[ -f deploy/unihup-scheduler.service && -f deploy/unihup-scheduler.timer ]] && command -v systemctl >/dev/null 2>&1; then
    install -m 644 deploy/unihup-scheduler.service /etc/systemd/system/unihup-scheduler.service
    install -m 644 deploy/unihup-scheduler.timer /etc/systemd/system/unihup-scheduler.timer
    systemctl daemon-reload
    systemctl enable --now unihup-scheduler.timer
fi

# Reverb WebSocket server — powers live updates on Support Chat and the
# WhatsApp Inbox. Only installed once `composer require laravel/reverb` has
# been run; until then the chat pages fall back to wire:poll and this block
# is skipped. `systemctl restart` on every deploy is deliberate — it drops
# open sockets so clients reconnect against fresh code (same reason the
# queue worker is restarted, not just signalled).
if [[ -f deploy/unihup-reverb.service ]] \
    && grep -q '"laravel/reverb"' composer.json \
    && command -v systemctl >/dev/null 2>&1; then
    install -m 644 deploy/unihup-reverb.service /etc/systemd/system/unihup-reverb.service
    systemctl daemon-reload
    systemctl enable unihup-reverb
    systemctl restart unihup-reverb
elif systemctl list-unit-files 2>/dev/null | grep -q '^unihup-reverb\.service'; then
    # laravel/reverb was removed but the unit is still installed — stop it so
    # a dead `reverb:start` isn't left flapping under Restart=always.
    systemctl disable --now unihup-reverb || true
    rm -f /etc/systemd/system/unihup-reverb.service
    systemctl daemon-reload
fi

# Nightly database backup — see deploy/backup.sh.
if [[ -f deploy/unihup-backup.service && -f deploy/unihup-backup.timer ]] && command -v systemctl >/dev/null 2>&1; then
    install -m 644 deploy/unihup-backup.service /etc/systemd/system/unihup-backup.service
    install -m 644 deploy/unihup-backup.timer /etc/systemd/system/unihup-backup.timer
    systemctl daemon-reload
    systemctl enable --now unihup-backup.timer
fi

if id "$WEB_USER" >/dev/null 2>&1; then
    chown -R "$WEB_USER:$WEB_GROUP" storage bootstrap/cache
    chmod -R ug+rwX storage bootstrap/cache
else
    echo "Error: web-server user '$WEB_USER' does not exist." >&2
    exit 1
fi

nginx -t
systemctl restart nginx

# Reverb needs its own WebSocket location proxied in nginx (see the deploy
# runbook) — this only warns, it never edits nginx config for you.
if grep -q '^BROADCAST_CONNECTION=reverb' .env 2>/dev/null \
    && ! grep -rq 'location /app' /etc/nginx/sites-enabled/ 2>/dev/null; then
    echo "Warning: BROADCAST_CONNECTION=reverb but no 'location /app' WebSocket proxy" >&2
    echo "was found under /etc/nginx/sites-enabled/. Realtime chat will not connect" >&2
    echo "until that's added — see the Reverb section of the deploy notes." >&2
fi

"$PHP_BIN" artisan up
maintenance_enabled=false

echo "Deployment completed successfully."
