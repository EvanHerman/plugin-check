#!/bin/bash

# This script is used to take the version number from package.json and replace it in the plugin file.
VERSION=$(jq -r .version package.json)

echo "Bumping the version to $VERSION"

# Replace the plugin version header.
# Version: 1.0.0
# perl -i.bak -pe 's{(Version:\s+)\d+[.]\d+[.]\d+}{$1$ENV{VERSION}}g' class-plugin-check.php
sed -i '' 's/Version: .*/Version: '"$VERSION"'/' class-plugin-check.php

# Replace the plugin version constant.
# define( 'WP_PLUGIN_CHECK_VERSION', '1.0.0' );
sed -i '' "s/define( 'WP_PLUGIN_CHECK_VERSION', .*/define( 'WP_PLUGIN_CHECK_VERSION', '"$VERSION"' );/" class-plugin-check.php

# Replace the plugin version in the README.md file.
# # WordPress Plugin Check v0.0.1
sed -i '' "s/# WordPress Plugin Check .*/# WordPress Plugin Check v"$VERSION"/" README.md