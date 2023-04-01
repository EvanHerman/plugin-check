#!/bin/bash

git submodule update --init

chmod +x bin/plugin-scan/plugin-scan.sh
chmod +x bin/plugin-scan/plugin-scan.sh.ignore
chmod +x bin/plugin-scan/plugin-scan.sh.xml

# Remove the PHPCS line so we can replace it
sed -i '1343d' 'bin/plugin-scan/plugin-scan.sh'
sed -i '1343i\
phpcs --colors --extensions=php --report-width=200 --standard=$0.xml current_plugin --report=code >> "$tempfile"' bin/plugin-scan/plugin-scan.sh

# Remove the bash check in the plugin scan (doesn't work locally for me)
sed -i '8,14d' 'bin/plugin-scan/plugin-scan.sh'

# Add an exclusion for PHP 8+ to the plugin-scan phpcs.xml file at line 32
sed -i '32i\
<ini name="error_reporting" value="E_ALL &#38; ~E_DEPRECATED" />' bin/plugin-scan/plugin-scan.sh.xml