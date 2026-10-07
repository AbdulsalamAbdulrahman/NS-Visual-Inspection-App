#!/usr/bin/env bash
#
# KENS pull deploy, run on the server by cron every minute (docs/DEPLOY.md):
#
#   * * * * * /bin/bash $HOME/domains/kens.buildingelectcert.com.ng/app/deploy/server-pull.sh >> $HOME/domains/kens.buildingelectcert.com.ng/deploy.log 2>&1
#
# GitHub Actions publishes a ready-to-run build to the `deploy` branch (the
# host blocks inbound SSH, so the server pulls). When that branch has a new
# commit, this builds a release next to the live one and only switches over
# once Composer, migrations and caching have all succeeded:
#
#   <base>/repo.git               bare clone of the deploy branch (read-only deploy key)
#   <base>/releases/<id>/         one directory per release; the last 3 are kept
#   <base>/shared/.env            production settings (never in git)
#   <base>/shared/storage/        uploads, logs, sessions — shared by every release
#   <base>/app -> releases/<id>   the live release
#   <base>/public_html -> app/public   web root (one-time setup)
#
# Usage:
#   server-pull.sh             deploy when there's a new commit (silent otherwise)
#   server-pull.sh --force     deploy the latest commit even if it's live or failed before
#   server-pull.sh --rollback  point app/ back at the previous release (migrations are not undone)
#
# Environment overrides: KENS_BASE, PHP_BIN, COMPOSER_BIN.

set -Eeuo pipefail

BASE="${KENS_BASE:-$HOME/domains/kens.buildingelectcert.com.ng}"
PHP="${PHP_BIN:-$(command -v php || true)}"
COMPOSER="${COMPOSER_BIN:-$HOME/bin/composer}"
KEEP=3

REPO="$BASE/repo.git"
RELEASES="$BASE/releases"
SHARED="$BASE/shared"
LIVE="$BASE/app"
FAILED="$BASE/.deploy-failed"
LOCK="$BASE/.deploy.lock"

MODE="deploy"
case "${1:-}" in
    --force) MODE="force" ;;
    --rollback) MODE="rollback" ;;
    "") ;;
    *) echo "Usage: $0 [--force|--rollback]" >&2; exit 2 ;;
esac

log() { printf '[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*"; }
fail() { log "ERROR: $*"; exit 1; }

# One run at a time: cron fires every minute and a deploy can take longer.
# (mkdir is atomic and needs no extra tools inside CageFS.)
if ! mkdir "$LOCK" 2>/dev/null; then
    if [ -n "$(find "$LOCK" -maxdepth 0 -mmin +30 2>/dev/null)" ]; then
        log "Removing a stale lock left by an interrupted run."
        rmdir "$LOCK" && mkdir "$LOCK"
    else
        exit 0
    fi
fi
trap 'rmdir "$LOCK" 2>/dev/null || true' EXIT

# Point app/ at a release in one rename, so requests never see a half-switched state.
switch_to() {
    ln -sfn "releases/$1" "$BASE/app.next"
    mv -Tf "$BASE/app.next" "$LIVE"
}

live_release() {
    local target
    target="$(readlink "$LIVE" 2>/dev/null || true)"
    printf '%s' "${target##*/}"
}

if [ -e "$LIVE" ] && [ ! -L "$LIVE" ]; then
    fail "$LIVE is a real directory; it must be a symlink managed by this script. Move it aside (see docs/DEPLOY.md)."
fi

if [ "$MODE" = "rollback" ]; then
    current="$(live_release)"
    previous="$(ls -1 "$RELEASES" 2>/dev/null | sort | awk -v c="$current" '$0 < c' | tail -n 1)"
    [ -n "$previous" ] || fail "No earlier release to roll back to."
    switch_to "$previous"
    # Hold back the newest commit on the branch so cron doesn't redeploy it.
    git -C "$REPO" rev-parse deploy > "$FAILED"
    log "Rolled back from $current to $previous. Push a fix (or run --force) to deploy again."
    exit 0
fi

[ -d "$REPO" ] || fail "Missing $REPO. Run the first-time setup in docs/DEPLOY.md."
[ -n "$PHP" ] || fail "php not found. Set PHP_BIN in the cron line."

git -C "$REPO" fetch --quiet origin '+refs/heads/deploy:refs/heads/deploy' \
    || fail "git fetch failed. Check the deploy key: ssh -T git@github.com"

commit="$(git -C "$REPO" rev-parse deploy)"
short="${commit:0:7}"
live_commit="$(cat "$LIVE/.deploy-commit" 2>/dev/null || true)"
failed_commit="$(cat "$FAILED" 2>/dev/null || true)"

if [ "$MODE" != "force" ]; then
    # Nothing new, or this commit already failed: stay quiet until the next push.
    [ "$commit" = "$live_commit" ] && exit 0
    [ "$commit" = "$failed_commit" ] && exit 0
fi

"$PHP" -r 'exit(PHP_VERSION_ID >= 80400 ? 0 : 1);' \
    || fail "$PHP is PHP $("$PHP" -r 'echo PHP_VERSION;'); KENS needs 8.4+. Set PHP_BIN (e.g. /usr/local/php85/bin/php) in the cron line."
[ -f "$SHARED/.env" ] || fail "Missing $SHARED/.env. Run the first-time setup in docs/DEPLOY.md."

id="$(date '+%Y%m%d%H%M%S')-$short"
release="$RELEASES/$id"
stage="checkout"

on_error() {
    log "ERROR: deploy of $short failed during $stage (line $1). The live site was not switched."
    if [ "$stage" = "caches" ]; then
        log "Migrations for $short had already run; the previous code is still serving."
    fi
    printf '%s\n' "$commit" > "$FAILED"
    rm -rf "$release"
    log "Fix and push again, or retry with: bash $LIVE/deploy/server-pull.sh --force"
}
trap 'on_error $LINENO' ERR

log "Deploying $short as release $id ($("$PHP" -r 'echo PHP_VERSION;'))"

mkdir -p "$release"
git -C "$REPO" archive "$commit" | tar -x -C "$release"
printf '%s\n' "$commit" > "$release/.deploy-commit"

# Shared state lives outside the releases.
mkdir -p "$SHARED/storage/app/private" \
    "$SHARED/storage/framework/cache/data" \
    "$SHARED/storage/framework/sessions" \
    "$SHARED/storage/framework/views" \
    "$SHARED/storage/logs"
rm -rf "$release/storage"
ln -s "$SHARED/storage" "$release/storage"
ln -sfn "$SHARED/.env" "$release/.env"
mkdir -p "$release/bootstrap/cache"

stage="composer"
# Start from the live vendor/ so Composer only fetches what changed.
if [ -d "$LIVE/vendor" ]; then
    cp -a "$LIVE/vendor" "$release/vendor"
fi
"$PHP" -d memory_limit=-1 "$COMPOSER" install --working-dir="$release" \
    --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress --quiet

stage="migrations"
"$PHP" "$release/artisan" migrate --force --no-interaction

stage="caches"
"$PHP" "$release/artisan" optimize --no-interaction

stage="switch"
switch_to "$id"
trap - ERR
rm -f "$FAILED"

# Queue workers started by the scheduler pick up the new code on their next run.
"$PHP" "$LIVE/artisan" queue:restart --no-interaction >/dev/null 2>&1 || true

# Keep the newest releases (the live one is always the newest).
ls -1 "$RELEASES" | sort | head -n "-$KEEP" | while read -r old; do
    rm -rf "${RELEASES:?}/$old"
done
git -C "$REPO" gc --auto --quiet || true

log "Live: $short ($(cat "$release/REVISION" 2>/dev/null | cut -c1-7 || echo '?') on main)"
