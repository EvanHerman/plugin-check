#!/bin/bash

git submodule update --init

chmod +x bin/plugin-scan/plugin-scan.sh
chmod +x bin/plugin-scan/plugin-scan.sh.ignore
chmod +x bin/plugin-scan/plugin-scan.sh.xml

# Remove the bash check in the plugin scan (doesn't work locally for me)
sed -i '' '7,13d' 'bin/plugin-scan/plugin-scan.sh'