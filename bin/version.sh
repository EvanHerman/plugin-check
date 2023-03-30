#!/bin/bash

# This script is used to take the version number from package.json and replace it in the plugin file.
VERSION=$(jq -r .version package.json)

echo "Bumping the version to $VERSION"

# Replace the plugin version header.
# Version: 1.0.0
sed -i 's/Version: .*/Version: '"$VERSION"'/' class-plugin-check.php

# Replace the plugin version in the remote update class.
# $this->version       = '0.0.2';
sed -i 's/$this->version .*/$this->version       = '"'$VERSION'"';/' includes/class-remote-update.php

# Replace the plugin version constant.
# define( 'WP_PLUGIN_CHECK_VERSION', '1.0.0' );
sed -i "s/define( 'WP_PLUGIN_CHECK_VERSION', .*/define( 'WP_PLUGIN_CHECK_VERSION', '"$VERSION"' );/" class-plugin-check.php

# Replace the plugin version in the README.md file.
# # WordPress Plugin Check v0.0.1
sed -i "s/# WordPress Plugin Check .*/# WordPress Plugin Check v"$VERSION"/" README.md

# Replace the plugin version in the readme.txt file.
# Stable tag: 0.0.1
sed -i "s/Stable tag: .*/Stable tag: "$VERSION"/" readme.txt