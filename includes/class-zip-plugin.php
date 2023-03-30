<?php
/**
 * Zip a specified directory.
 *
 * @package WP_Plugin_Check
 * @since 1.0.0
 */
final class WP_Plugin_Check_Zip_Plugin {

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $source, $destination, $include_dir = false, $exclusions = array() ) {

		/**
		 * Default list of excluded files from being included in the .zip
		 *
		 * @var array
		 */
		$default_excluded_files = apply_filters(
			'wp_plugin_check_default_excluded_files',
			array(
				'.github',
				'.gitmodules',
				'.gitignore',
				'.wordpress-org',
				'.distinclude',
				'.editorconfig',
				'.eslintignore',
				'.eslintrc.js',
				'.npmrc',
				'.nvmrc',
				'.stylelintignore',
				'.stylelintrc.json',
				'CODE_OF_CONDUCT.md',
				'CONTRIBUTORS.md',
				'babel.config.json',
				'composer.json',
				'composer.lock',
				'cypress.config.js',
				'node_modules',
				'vendor',
				'.htaccess',
				'Gruntfile.js',
				'gruntfile.js',
				'manifest.xml',
				'package.json',
				'phpcs.xml',
				'phpunit.xml.dist',
				'webpack.config.js',
				'yarn.lock',
			),
		);

		$exclusions = array_merge( $exclusions, $default_excluded_files );

		$this->zip_plugin( $source, $destination, $include_dir, $exclusions );

	}

	/**
	 * ZIP a specific directory.
	 *
	 * @param string $source      The source directory to zip.
	 * @param string $destination The destination of the zip file.
	 * @param bool   $include_dir Whether to include the source directory in the zip file.
	 * @param array  $exclusions  List of files to exclude from the zip file.
	 *
	 * @since 1.0.0
	 */
	public function zip_plugin( $source, $destination, $include_dir = false, $exclusions = array() ) {

		// Remove existing archive
		if ( file_exists( $destination ) ) {

			unlink( $destination );

		}

		if ( ! is_dir( dirname( $destination ) ) ) {

			mkdir( dirname( $destination ), 0777, true );

		}

		$zip = new ZipArchive();

		if ( ! $zip->open( $destination, ZIPARCHIVE::CREATE ) ) {

			return false;

		}

		$source = str_replace( '\\', '/', realpath( $source ) );

		if ( is_dir( $source ) === true ) {

			$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $source ),
				RecursiveIteratorIterator::SELF_FIRST
			);

			if ( $include_dir ) {

				$arr     = explode( "/",$source );
				$maindir = $arr[ count( $arr ) - 1 ];
				$source  = "";

				for ( $i=0; $i < count($arr) - 1; $i++ ) {

					$source .= '/' . $arr[ $i ];

				}

				$source = substr( $source, 1 );
				$zip->addEmptyDir( $maindir );

			}

			foreach ( $files as $file ) {

				// Ignore "." and ".." folders.
				$file = str_replace( '\\', '/', $file );

				if( in_array( substr( $file, strrpos( $file, '/' ) +1 ), array( '.', '..' ) ) ) {

					continue;

				}

				// Add Exclusion.
				if ( ! empty( $exclusions ) ) {
					if ( in_array( str_replace( $source . '/', '', $file ), $exclusions, true ) ) {
						continue;
					}
				}

				$file = realpath( $file );

				if ( is_dir( $file ) === true ) {

					$zip->addEmptyDir( str_replace( $source . '/', '', $file . '/' ) );

				} elseif ( is_file( $file ) === true ) {

					$zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));

				}
			}
		} elseif (is_file($source) === true){

			$zip->addFromString(basename($source), file_get_contents($source));

		}

		return $zip->close();

	}

}
