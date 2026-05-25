#!/bin/bash
# setup_server.sh — First-time server setup for CI4 API on Afrihost shared hosting
#
# Usage (run from inside the cloned repo):
#   chmod +x setup_server.sh
#   ./setup_server.sh
#
# Assumes:
#   - Repo is already cloned and you are running this from its root
#   - Composer is installed (run install_composer.sh first if not)
#   - Running on a clean main branch

set -e

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'
info()    { echo -e "${CYAN}[info]${NC}  $1"; }
success() { echo -e "${GREEN}[ok]${NC}    $1"; }
warn()    { echo -e "${YELLOW}[warn]${NC}  $1"; }
error()   { echo -e "${RED}[error]${NC} $1"; exit 1; }

REPO_ROOT="$(pwd)"

# ── Sanity check ──────────────────────────────────────────────────────────────
if [ ! -f "spark" ] || [ ! -f "composer.json" ]; then
  error "Run this script from the root of the CI4 repo (spark and composer.json must exist here)."
fi

echo ""
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${CYAN}  CI4 Server Setup — $(basename "$REPO_ROOT")${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# ── Step 1: Composer install ──────────────────────────────────────────────────
echo ""
info "Running composer install…"
if ! command -v composer &>/dev/null; then
  error "composer not found. Run install_composer.sh first:\n  chmod +x install_composer.sh && ./install_composer.sh"
fi
composer install --no-dev --optimize-autoloader
success "Dependencies installed."

# ── Step 2: writable/ directories ────────────────────────────────────────────
echo ""
info "Creating writable/ subdirectories…"
mkdir -p writable/{cache,logs,session,uploads}
chmod -R 775 writable/
success "writable/ created with 775."

# ── Step 3: .env ─────────────────────────────────────────────────────────────
echo ""
info "Setting up .env…"
if [ -f ".env" ]; then
  warn ".env already exists — skipping."
elif [ -f ".env.example" ]; then
  cp .env.example .env
  chmod 600 .env
  success ".env created from .env.example — fill in production values before running migrations."
else
  warn "No .env.example found — create .env manually."
fi

# ── Step 4: File permissions ──────────────────────────────────────────────────
echo ""
info "Setting file permissions…"
find . -type d -not -path "./vendor/*" -exec chmod 755 {} \;
find . -type f -not -path "./vendor/*" -exec chmod 644 {} \;
chmod 755 spark
chmod -R 775 writable/
[ -f ".env" ] && chmod 600 .env
success "Permissions set."

# ── Step 5: .user.ini (session.gc_divisor fix) ───────────────────────────────
echo ""
info "Writing .user.ini to public_html/api/…"
API_DIR="$HOME/public_html/api"
if [ ! -d "$HOME/public_html" ]; then
  warn "~/public_html not found — write .user.ini manually:\n  echo 'session.gc_divisor = 100' > ~/public_html/api/.user.ini"
else
  mkdir -p "$API_DIR"
  echo "session.gc_divisor = 100" > "$API_DIR/.user.ini"
  success ".user.ini written to $API_DIR/.user.ini"
fi

# ── Done ─────────────────────────────────────────────────────────────────────
echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}  Setup complete.${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo "  Remaining manual steps:"
echo ""
echo "  1. Fill in .env with production values:"
echo "       nano $REPO_ROOT/.env"
echo ""
echo "  2. Symlink public/ into public_html/api/ (once per server):"
echo "       ln -s $REPO_ROOT/public $HOME/public_html/api"
echo ""
echo "  3. Add .htaccess to public_html/api/ (once per server):"
cat << 'HTACCESS'
       cat > ~/public_html/api/.htaccess << 'EOF'
       <IfModule mod_rewrite.c>
         RewriteEngine On
         RewriteRule ^(.*)$ public/$1 [L]
       </IfModule>
       EOF
HTACCESS
echo ""
echo "  4. Run migrations + seed:"
echo "       php spark migrate && php spark db:seed MainSeeder"
echo ""
echo "  5. Smoke test:"
echo "       curl -s https://yourdomain.com/api/content/settings"
echo ""
