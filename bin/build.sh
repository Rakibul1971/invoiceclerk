#!/usr/bin/env bash

# Exit if any command fails.
set -e

# Change to the expected directory.
cd "$(dirname "$0")"
cd ..
DIR=$(pwd)
BUILD_DIR="$DIR/build/invoiceclerk"

VERSION=$(grep -E '^[[:space:]]*\* Version:' "$DIR/invoiceclerk.php" | head -n1 | sed -E 's/.*Version:[[:space:]]*([^[:space:]]+).*/\1/')
if [ -z "$VERSION" ]; then
    error "Could not determine version from invoiceclerk.php"
    exit 1
fi



# Enable nicer messaging for build status.
BLUE_BOLD='\033[1;34m'
GREEN_BOLD='\033[1;32m'
RED_BOLD='\033[1;31m'
YELLOW_BOLD='\033[1;33m'
COLOR_RESET='\033[0m'

error() {
    echo -e "\n${RED_BOLD}$1${COLOR_RESET}\n"
}
status() {
    echo -e "\n${BLUE_BOLD}$1${COLOR_RESET}\n"
}
success() {
    echo -e "\n${GREEN_BOLD}$1${COLOR_RESET}\n"
}
warning() {
    echo -e "\n${YELLOW_BOLD}$1${COLOR_RESET}\n"
}

status "💃 Time to build the InvoiceClerk ZIP file 🕺"

# remove the build directory if exists and create one
rm -rf "$DIR/build"
mkdir -p "$BUILD_DIR"

# Run the build.


status "Generating build... 👷‍♀️"

# Copy all files
status "Copying files... ✌️"
FILES=(invoiceclerk.php readme.txt dist includes templates assets languages composer.json composer.lock)

for file in ${FILES[@]}; do
    if [ -f "$file" ] || [ -d "$file" ]; then
        cp -R $file $BUILD_DIR
    fi
done

# Remove hidden files and non-production directories from build
find $BUILD_DIR -name ".*" -exec rm -rf {} +
rm -rf "$BUILD_DIR/bin" 

# Install composer dependencies
status "Installing dependencies... 📦"
cd $BUILD_DIR
composer install --optimize-autoloader --no-dev -q

# Remove unnecessary fonts from TCPDF to keep the plugin size small
status "Trimming TCPDF fonts... ✂️"
find vendor/tecnickcom/tcpdf/fonts -type f ! -name "helvetica*" ! -name "times*" ! -name "courier*" ! -name "pdfa*" ! -name "pdfac*" ! -name "uni2cid*" ! -name "index.php" -size +50k -delete

# We are keeping composer.json as required by some plugin checkers when /vendor exists
# rm composer.json composer.lock

# go one up, to the build dir
status "Creating archive... 🎁"
cd ..
zip -r -q invoiceclerk-${VERSION}.zip invoiceclerk

# remove the source directory
rm -rf invoiceclerk

success "Done. You've built InvoiceClerk! 🎉 "
echo -e "\n${BLUE_BOLD}File Path${COLOR_RESET}: ${YELLOW_BOLD}$(pwd)/invoiceclerk-${VERSION}.zip${COLOR_RESET} \n"
