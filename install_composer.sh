#!/bin/bash
# install_composer.sh — Install Composer on Afrihost shared hosting
#
# Usage:
#   chmod +x install_composer.sh
#   ./install_composer.sh
#
# Notes:
#   - Uses curl (NOT php copy) because allow_url_fopen is OFF on Afrihost
#   - Installs composer.phar to ~/bin and creates a wrapper script
#   - Adds ~/bin to PATH permanently via ~/.bashrc

set -e

# 1. Create bin directory
mkdir -p ~/bin

# 2. Download installer via curl (allow_url_fopen is OFF on Afrihost)
curl -sS https://getcomposer.org/installer -o composer-setup.php

# 3. Run installer with allow_url_fopen forced on
php -d allow_url_fopen=On composer-setup.php --install-dir=$HOME/bin --filename=composer.phar

# 4. Clean up
php -r "unlink('composer-setup.php');"

# 5. Create a wrapper so you can type "composer" directly
echo '#!/bin/bash' > ~/bin/composer
echo 'php -d allow_url_fopen=On ~/bin/composer.phar "$@"' >> ~/bin/composer
chmod +x ~/bin/composer

# 6. Add ~/bin to PATH permanently
if ! grep -q 'export PATH="$HOME/bin:$PATH"' ~/.bashrc; then
  echo 'export PATH="$HOME/bin:$PATH"' >> ~/.bashrc
fi
source ~/.bashrc

# 7. Verify
echo ""
echo "Composer installed successfully:"
composer -V
