#!/bin/bash

rm -rf build/*

# This script is used to build the project.
mkdir -p build/plugin-check/ >/dev/null 2>&1

# Copy the plugin files to the build directory
cp class-plugin-check.php build/plugin-check/class-plugin-check.php
cp -r test-results/ build/plugin-check/test-results/
cp readme.txt build/plugin-check/readme.txt
cp LICENSE build/plugin-check/LICENSE

cp -r includes/ build/plugin-check/includes/
cp -r bin/ build/plugin-check/bin/

# Remove what we don't need
rm -f build/plugin-check/bin/plugin-scan/README.md
rm -f build/plugin-check/bin/plugin-scan/.git
rm -rf build/plugin-check/test-results/*
rm -f build/plugin-check/bin/*.sh
rm -f build/plugin-check/**/.DS_Store

# Remove the PHPCS line so we can replace it
sed -i '1343d' 'build/plugin-check/bin/plugin-scan/plugin-scan.sh'
sed -i '1343i\
phpcs --colors --extensions=php --report-width=200 --standard=$0.xml current_plugin --report=code >> "$tempfile"' build/plugin-check/bin/plugin-scan/plugin-scan.sh

# Remove the bash check in the plugin scan (doesn't work locally for me)
sed -i '8,14d' 'build/plugin-check/bin/plugin-scan/plugin-scan.sh'

# Add an exclusion for PHP 8+ to the plugin-scan phpcs.xml file at line 32
sed -i '32i\
<ini name="error_reporting" value="E_ALL &#38; ~E_DEPRECATED" />' build/plugin-check/bin/plugin-scan/plugin-scan.sh.xml

chmod +x build/plugin-check/bin/plugin-scan/plugin-scan.sh
chmod +x build/plugin-check/bin/plugin-scan/plugin-scan.sh.ignore
chmod +x build/plugin-check/bin/plugin-scan/plugin-scan.sh.xml

# ZIP the plugin
cd build/
zip -r plugin-check.zip plugin-check/