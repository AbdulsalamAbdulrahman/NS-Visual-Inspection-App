# Deploying KENS

Push to `main` and the site updates itself within a few minutes.

```
git push origin main
  └─ GitHub Actions (.github/workflows/deploy.yml)
       1. tests.yml: lint, PHPStan, svelte-check, Pest (incl. MariaDB row-locking tests)
       2. build assets (no Node on the server) → publish to the `deploy` branch
  └─ Server cron, every minute (deploy/server-pull.sh)
       3. git fetch `deploy`; if new: build releases/<id>, composer install,
          migrate, cache, then switch app → releases/<id> in one step
```

The host blocks inbound SSH, so GitHub can't push to the server. Instead the
server pulls with a **read-only deploy key**. A failed build never reaches the
server, and a failed release never replaces the live one.

## Server layout

All under `~/domains/kens.buildingelectcert.com.ng/` (called `$BASE` below):

| Path | What |
|---|---|
| `repo.git/` | bare clone of the `deploy` branch |
| `releases/<date>-<sha>/` | one folder per release; the last 3 are kept |
| `shared/.env` | production settings; never in git |
| `shared/storage/` | uploads, logs, sessions; shared by all releases |
| `app` → `releases/…` | the live release (symlink, switched by the script) |
| `public_html` → `app/public` | web root (symlink) |
| `deploy.log` | output of every deploy |

## First-time setup

Do these once, in this order.

### 1. Hosting prerequisites

- **DNS**: `kens.buildingelectcert.com.ng` must resolve to the server (102.68.98.184).
- **SSL**: DirectAdmin → SSL Certificates → Let's Encrypt for the subdomain.
- **PHP**: DirectAdmin → PHP version selector, PHP **8.4 or newer** for the domain
  (the command line already defaults to 8.5). Needed extensions: pdo_mysql, mbstring,
  intl, bcmath, gd, fileinfo, openssl.
- **Database**: DirectAdmin → MySQL Management → create database `buildin1_kens`
  and user `buildin1_kens` with a strong password.
- **Mail**: DirectAdmin → E-mail Accounts → create `no-reply@kens.buildingelectcert.com.ng`.

### 2. Read-only deploy key (server → GitHub)

On the server:

```bash
ssh-keygen -t ed25519 -N "" -C "kens-server-da38" -f ~/.ssh/kens_github
cat >> ~/.ssh/config <<'EOF'
Host github.com
  IdentityFile ~/.ssh/kens_github
  IdentitiesOnly yes
EOF
chmod 600 ~/.ssh/config
cat ~/.ssh/kens_github.pub
```

GitHub → repo → **Settings → Deploy keys → Add deploy key**: paste the `.pub`
line and leave **Allow write access unticked**. Check from the server with
`ssh -T git@github.com`; it should greet the repository.

### 3. First release

Push `main` and wait for the **deploy** workflow to go green (Actions tab). That
creates the `deploy` branch.

### 4. Server setup

In the DirectAdmin terminal:

```bash
BASE=~/domains/kens.buildingelectcert.com.ng
cd "$BASE"

git clone --bare --single-branch --branch deploy \
  git@github.com:AbdulsalamAbdulrahman/NS-Visual-Inspection-App.git repo.git

mkdir -p shared
git -C repo.git show deploy:deploy/env.production.example > shared/.env
chmod 600 shared/.env
php -r "echo 'base64:'.base64_encode(random_bytes(32)), PHP_EOL;"   # → APP_KEY
nano shared/.env      # APP_KEY, DB_PASSWORD, MAIL_PASSWORD, Monnify sandbox keys

# app/ must not exist yet: the script manages it as a symlink.
# (An empty placeholder can go: rmdir app/public app)

# First deploy (afterwards cron does this). Needs the database in shared/.env.
git -C repo.git show deploy:deploy/server-pull.sh > /tmp/kens-pull.sh
PHP_BIN=/usr/local/php85/bin/php bash /tmp/kens-pull.sh

# Serve the live release
mv public_html public_html.default
ln -s app/public public_html
```

Open https://kens.buildingelectcert.com.ng. You should see the sign-in page.

### 5. Cron jobs

DirectAdmin → Advanced Features → **Cron Jobs**, both "every minute" (`* * * * *`):

```
/usr/local/php85/bin/php /home/buildin1/domains/kens.buildingelectcert.com.ng/app/artisan schedule:run >> /dev/null 2>&1
PHP_BIN=/usr/local/php85/bin/php /bin/bash /home/buildin1/domains/kens.buildingelectcert.com.ng/app/deploy/server-pull.sh >> /home/buildin1/domains/kens.buildingelectcert.com.ng/deploy.log 2>&1
```

The first runs the scheduler, which sends queued email. The second picks up new
releases. Both use the full PHP 8.5 path: cron runs with a minimal environment
where plain `php` may be another version (`which php` in the terminal shows
`/usr/local/php85/bin/php` on da38).

### 6. Reference data and first admin

Service areas and the launch fee (₦15,000) come from the production-safe
seeder. It only adds areas that don't exist and only sets a fee when there is
none; it never creates demo users:

```bash
/usr/local/php85/bin/php ~/domains/kens.buildingelectcert.com.ng/app/artisan db:seed --force
```

The area list in `database/seeders/ServiceAreaSeeder.php` is a placeholder until
Kaduna Electric confirms the official one; admins can add, rename and
deactivate areas under **Service areas** at any time. Then create the first admin:

```bash
/usr/local/php85/bin/php ~/domains/kens.buildingelectcert.com.ng/app/artisan app:create-admin you@example.com "Your Name"
```

It prints a temporary password; you set your own at first sign-in.
`DemoSeeder` refuses to run in production.

### 7. Monnify (sandbox)

Monnify dashboard → Settings → Webhook URL:
`https://kens.buildingelectcert.com.ng/webhooks/monnify`. Keep
`MONNIFY_VERIFY_SIGNATURE=false` on the sandbox; set it to `true` with the live keys.

### 8. Certificate signatory

Sign in as admin → More → **Certificate**: the Head of NSD's name, title and a
scanned signature. Approving reports is blocked until this is set.

## What runs on its own

The scheduler (first cron line) runs:

| When | What |
|---|---|
| Every minute | Sends queued email (login details, password resets, submission, approval and changes-requested emails) |
| Hourly | `payments:abandon-stale`: payments pending for 24 h are checked with Monnify once more; paid ones are finalised (ticket issued), the rest marked Abandoned. Nothing changes while Monnify is unreachable. |
| Daily | Prunes failed queue jobs older than 30 days and expired password-reset tokens |

The second cron line deploys new releases (see above).

## Going live with Monnify

1. Get live keys from Monnify (API key, secret key, contract code).
2. In `shared/.env` set `MONNIFY_BASE_URL=https://api.monnify.com`, the three
   keys, and `MONNIFY_VERIFY_SIGNATURE=true`.
3. `php $BASE/app/artisan optimize`, then set the live webhook URL in the
   Monnify dashboard (same path: `/webhooks/monnify`).
4. Make one real payment and check it on Admin → Payments.

## Backups

Not automatic in the app. In DirectAdmin → **Create/Restore Backups**, schedule
a daily backup that includes the **database** `buildin1_kens` and the folder
`domains/kens.buildingelectcert.com.ng/shared` (`.env` plus uploads,
signatures and certificate signatures under `storage/app/private`). Keep at
least one copy off the server. The releases themselves don't need backing up:
they're rebuilt from GitHub.

## Keeping an eye on it

| Check | How |
|---|---|
| App errors | `$BASE/shared/storage/logs/laravel-YYYY-MM-DD.log` (30 days kept). Lines with `Lazy loading` point to a slow list worth fixing. |
| Emails not arriving | `php $BASE/app/artisan queue:failed` lists failed sends; `queue:retry all` resends them once mail settings are fixed |
| Deploys | `tail -n 30 $BASE/deploy.log` |
| Health | `https://kens.buildingelectcert.com.ng/up` returns 200 when the app boots |

## Day to day

| Task | How |
|---|---|
| Deploy | Push to `main`. Watch the Actions tab, then `tail -n 30 $BASE/deploy.log`. |
| What's live | `readlink $BASE/app` (release) and `cat $BASE/app/REVISION` (commit on main) |
| Roll back | `bash $BASE/app/deploy/server-pull.sh --rollback` (code only; migrations stay) |
| Retry a failed release | `bash $BASE/app/deploy/server-pull.sh --force` |
| Change settings | Edit `$BASE/shared/.env`, then `php $BASE/app/artisan optimize` |
| App logs | `$BASE/shared/storage/logs/laravel-YYYY-MM-DD.log` |

## When something goes wrong

- **The Actions run is red**: nothing was deployed. Open the failed step, fix it and push.
- **`deploy.log` shows `ERROR … failed during <stage>`**: the live site kept the
  previous release. The script won't retry that commit by itself; push a fix,
  or rerun with `--force` once the cause (e.g. database credentials) is fixed.
- **`git fetch failed`**: the deploy key or `~/.ssh/config` entry is missing;
  `ssh -T git@github.com` shows why.
- **Site shows a 500 after a settings change**: run `php $BASE/app/artisan optimize`
  (config is cached), then check the app log.
