# WordPress Plugin Check
Scan your WordPress plugin for common errors before submitting it to WordPress.org for review


### Intentions
This plugins intention is to minimize the number of common errors developers encounter when submitting plugins to WordPress.org for review. When submitting a plugin for admission into [WordPress.org](https://www.wordpress.org/plugins) there are a number of [guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) that must be followed, and many developers miss steps or checks that prolong the review process. Developing a plugin in tandem with Plugin Check will allow developers to periodically check their code for missteps or invalid code.

Developers should use this plugin during development to check for any errors in their code, or to check their plugin before submitting it for review on [WordPress.org](https://www.wordpress.org/plugins).

### Ignored Files When Scanning
If this plugin is used during development, there may be many files in the working copy of your plugin. Plugin Check works by zipping up your local plugin directory, and then scanning that against a set of rules. Some files might get included in the .zip that shouldn't be, so Plugin Check does it's best to exclude a default set of files that are generally used during development that will throw an error during plugin scans.

The following files *will not* be included in the .zip that is scanned.

- `.github`
- `.wordpress-org`
- `.distinclude`
- `.editorconfig`
- `.eslintignore`
- `.eslintrc.js`
- `.gitignore`
- `.npmrc`
- `.nvmrc`
- `.stylelintignore`
- `.stylelintrc.json`
- `CODE_OF_CONDUCT.md`
- `CONTRIBUTORS.md`
- `babel.config.json`
- `composer.json`
- `composer.lock`
- `cypress.config.js`
- `node_modules`
- `vendor`
- `.htaccess`
- `Gruntfile.js`
- `gruntfile.js`
- `manifest.xml`
- `package.json`
- `phpcs.xml`
- `phpunit.xml.dist`
- `webpack.config.js`
- `yarn.lock`

### Setup

To run Plugin Check repository locally:
- Clone this repository into your `wp-content/plugins` directory
- From the Plugin Check root, run `npm run setup`
- Activate 'Plugin Check' from the WordPress dashboard
- Head to 'Tools > Plugin Check'
- Select your plugin from the dropdown menu and click on 'Check Plugin'
- Check the results of the plugin scan in the text field below
