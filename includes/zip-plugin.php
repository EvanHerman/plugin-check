<?php
/**
 * Zip a specified directory.
 */
final class WP_Plugin_Check_Zip_Plugin {

	public function __construct( $source, $destination, $include_dir = false, $exclusions = array() ) {

		/**
		 * Default list of excluded files from being included in the .zip
		 *
		 * @var array
		 */
		$default_excluded_files = apply_filters(
			'wp_plugin_check_default_excluded_files',
			array(
				'node_modules',
				'vendor',
				'.htaccess',
				'Gruntfile.js',
			),
		);

		$exclusions = array_merge( $exclusions, $default_excluded_files );

		$this->zip_plugin( $source, $destination, $include_dir, $exclusions );

	}

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

			$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $source ), RecursiveIteratorIterator::SELF_FIRST );

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

				// Ignore "." and ".." folders
				$file = str_replace( '\\', '/', $file );

				if( in_array( substr( $file, strrpos( $file, '/' ) +1 ), array( '.', '..' ) ) ) {

					continue;

				}

				// Add Exclusion
				if ( ! empty( $exclusions ) ) {
					if ( in_array( str_replace( $source . '/', '', $file ), $exclusions ) ) {
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
