# Deployment — CodeIgniter 4 API to cPanel Shared Hosting

You are deploying the **CI4 PHP API** from `client-api/` to cPanel shared hosting.

## Working Directory Rule
Server commands run from `~/client-api/` (home dir, outside `public_html`). The `public/` folder is symlinked into `public_html/api/`.

## Pre-flight Checklist (check before touching the server)
- [ ] `.env` on server is up to date (never committed — managed manually on server)
- [ ] If `composer.json` changed: `composer install --no-dev` needed
- [ ] If new migrations added: `php spark migrate` needed
- [ ] `writable/` exists and is writable (`chmod -R 775 writable/`)

## Standard Deployment (no schema changes, no new dependencies)
```bash
ssh user@clientdomain.com
cd ~/client-api
git pull origin main
# Done — CI4 has no build step
```

## When `composer.json` Changed
```bash
cd ~/client-api
git pull origin main
composer install --no-dev --optimize-autoloader
```

## When New Migrations Were Added
```bash
cd ~/client-api
git pull origin main
php spark migrate
php spark migrate:status   # verify all ran
```

## Installing Composer on Afrihost Shared Hosting

> Afrihost disables `allow_url_fopen` by default, so `php -r "copy(...)"` fails. Use `curl` instead.

Run the included script (one-time, per hosting account):

```bash
chmod +x ~/client-api/install_composer.sh
./~/client-api/install_composer.sh
```

Or manually:

```bash
mkdir -p ~/bin

# Download via curl (NOT php copy — allow_url_fopen is OFF)
curl -sS https://getcomposer.org/installer -o composer-setup.php

# Install with allow_url_fopen forced on
php -d allow_url_fopen=On composer-setup.php --install-dir=$HOME/bin --filename=composer.phar

# Clean up
php -r "unlink('composer-setup.php');"

# Create wrapper script so "composer" works from anywhere
echo '#!/bin/bash' > ~/bin/composer
echo 'php -d allow_url_fopen=On ~/bin/composer.phar "$@"' >> ~/bin/composer
chmod +x ~/bin/composer

# Add ~/bin to PATH permanently
echo 'export PATH="$HOME/bin:$PATH"' >> ~/.bashrc
source ~/.bashrc

# Verify
composer -V
```

---

## First Deployment (fresh server)

### Assumptions
- Repo is already cloned (`git clone … && cd client-api`)
- Composer is installed (run `install_composer.sh` first if not)
- Running on a clean `main` branch

### Option A — Automated (recommended)

Run the included setup script from inside the repo root. It handles composer install, writable dirs, .env, file permissions, and .user.ini:

```bash
chmod +x setup_server.sh
./setup_server.sh
```

After it finishes, complete the remaining manual steps it prints:
- Fill in `.env` with production values
- Symlink `public/` → `public_html/api/`
- Add `.htaccess`
- Run migrations + seed

---

### Option B — Manual

### 1. Clone the Repo
```bash
cd ~
git clone https://github.com/youruser/client-api.git client-api
cd client-api
```

### 2. Symlink `public/` into `public_html/api/`
```bash
ln -s ~/client-api/public ~/public_html/api
```

### 3. Create `.env` on the Server (NEVER put in git)
```bash
cd ~/client-api
cp .env.example .env
nano .env
```
Production values:
```ini
CI_ENVIRONMENT = production
app.baseURL        = https://clientdomain.com/api/
app.allowedOrigins = https://clientdomain.com
database.default.hostname = localhost
database.default.database = {slug}_cms
database.default.username = {slug}_user
database.default.password = strongpassword
database.default.DBDriver = MySQLi
RESEND_API_KEY = re_...
RESEND_FROM    = noreply@contact.clientdomain.com
```

### 4. Install Dependencies
```bash
composer install --no-dev --optimize-autoloader
```

### 5. Set File Permissions
```bash
find . -type d -not -path "./vendor/*" -exec chmod 755 {} \;
find . -type f -not -path "./vendor/*" -exec chmod 644 {} \;
chmod 755 spark
chmod -R 775 writable/
chmod 600 .env
```

### 6. Create `writable/` Subdirs (not in git)
```bash
mkdir -p writable/{cache,logs,session,uploads}
chmod -R 777 writable/
```

### 7. Fix `session.gc_divisor` (do once per hosting account)
```bash
echo "session.gc_divisor = 100" > ~/public_html/api/.user.ini
```
This prevents PHP warnings being prepended to JSON responses.

### 8. Add API `.htaccess` (rewrites to `public/`)
```bash
cat > ~/public_html/api/.htaccess << 'EOF'
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
EOF
```

### 9. Run Migrations and Seed
```bash
php spark migrate
php spark db:seed MainSeeder
composer dump-autoload --optimize
```

### 10. Smoke Test
```bash
curl -s https://clientdomain.com/api/content/settings
# Expected: {"ok":true,"settings":{...}}

curl -s https://clientdomain.com/api/admin/me
# Expected: 401 {"error":"Unauthorized"}
```

## Debugging on the Server
```bash
# CI4 application errors
cat ~/client-api/writable/logs/log-$(date +%Y-%m-%d).php 2>/dev/null

# PHP/Apache errors (500 with empty CI4 log)
cat ~/public_html/error_log 2>/dev/null

# Check DB tables
mysql -u {user} -p {db} -e "SHOW TABLES;"

# Clear CI4 cache
rm -rf ~/client-api/writable/cache/*
```

## Common Issues
| Symptom | Cause | Fix |
|---------|-------|-----|
| 500, CI4 log empty | `vendor/` missing | `composer install --no-dev` |
| API JSON response starts with PHP warning | `session.gc_divisor` host config | Create `.user.ini` in `public_html/api/` |
| CORS error from browser | `app.allowedOrigins` not set | Edit `.env` → add production frontend domain |
| 401 on admin login (correct password) | Password hash not seeded | Generate hash + UPDATE `settings` table |
| `git pull` fails "not a git repo" | Wrong directory | `cd ~/client-api && git status` |
| `composer` not found | Not installed / PATH not set | Run `chmod +x install_composer.sh && ./install_composer.sh` (uses curl, forces `allow_url_fopen=On`) |
| Migration already run error | Running migrate twice | `php spark migrate:status` to check; safe to ignore "no migrations to run" |
