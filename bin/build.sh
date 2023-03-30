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

# Remove the bash check in the plugin scan (doesn't work locally for me)
sed -i '' '8,14d' 'bin/plugin-scan/plugin-scan.sh'

chmod +x build/plugin-check/bin/plugin-scan/plugin-scan.sh
chmod +x build/plugin-check/bin/plugin-scan/plugin-scan.sh.ignore
chmod +x build/plugin-check/bin/plugin-scan/plugin-scan.sh.xml

# ZIP the plugin
cd build/
zip -r plugin-check.zip plugin-check/