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
FILES=(invoiceclerk.php uninstall.php readme.txt includes templates assets languages composer.json composer.lock)

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

# Remove unnecessary fonts from mPDF to keep the plugin size small
status "Trimming mPDF fonts... ✂️"
if [ -d "vendor/mpdf/mpdf/ttfonts" ]; then
    find vendor/mpdf/mpdf/ttfonts -type f ! -name "DejaVuSans*" ! -name "DejaVuSerif*" -delete
fi

# Remove mPDF temporary/cache directories if they exist
rm -rf vendor/mpdf/mpdf/tmp
rm -rf vendor/mpdf/mpdf/ttfontdata

# Remove composer.lock (build state) but keep composer.json (metadata) for checkers
rm -f composer.lock

# Clean up vendor folder - remove tests, documentation, and other non-production files
status "Cleaning up vendor directory... 🧹"
find vendor/ -type d -name "tests" -exec rm -rf {} +
find vendor/ -type d -name "test" -exec rm -rf {} +
find vendor/ -type d -name "docs" -exec rm -rf {} +
find vendor/ -type d -name ".github" -exec rm -rf {} +
find vendor/ -type f -name ".gitignore" -delete
find vendor/ -type f -name ".gitattributes" -delete
find vendor/ -type f -name "composer.json" -delete
find vendor/ -type f -name "composer.lock" -delete
find vendor/ -type f -name "README*" -delete
find vendor/ -type f -name "CHANGELOG*" -delete
find vendor/ -type f -name "CONTRIBUTING*" -delete
find vendor/ -type f -name "LICENSE*" -delete
find vendor/ -type f -name "*.sh" -delete
find vendor/ -type f -name "*.bat" -delete

# go one up, to the build dir
status "Creating archive... 🎁"
cd ..
zip -r -q invoiceclerk-${VERSION}.zip invoiceclerk

# remove the source directory
rm -rf invoiceclerk

success "Done. You've built InvoiceClerk! 🎉 "
echo -e "\n${BLUE_BOLD}File Path${COLOR_RESET}: ${YELLOW_BOLD}$(pwd)/invoiceclerk-${VERSION}.zip${COLOR_RESET} \n"
