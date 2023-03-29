<?php
/*
 * Plugin Name: Plugin Check
 * Description: Scan a plugin for various checks when developing a WordPress plugin for the WordPress.org repository.
 * Version: 1.0.0
 * Author: Evan Herman
*/

final class WP_Plugin_Check {

	private $scan_results;

	public function __construct() {

		define( 'WP_PLUGIN_CHECK_VERSION', '1.0.0' );
		define( 'WP_PLUGIN_SCRIPT_DIR', plugin_dir_path( __FILE__ ) . '/bin/plugin-scan/' );

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

	}
	
	public function add_admin_menu() {

		add_management_page(
			'Plugin Check',
			'Plugin Check',
			'manage_options',
			'plugin-check',
			array( $this, 'plugin_check' )
		);

	}
	
	public function plugin_check() {

		$checked_plugin = '';

		if ( isset( $_POST['plugin-to-check'] ) ) {

			$checked_plugin = htmlspecialchars( $_POST['plugin-to-check'] );

			$this->scan_plugin( $checked_plugin );

		}

		?>

			<h2><?php esc_html_e( 'Plugin Check, Yo!', 'plugin-check' ); ?></h2>

			<form action="?page=plugin-check&check_plugin=true" method="post">
					<select name="plugin-to-check">
						<?php foreach ( $this->get_plugins() as $plugin_path => $name ) : ?>
							<option value="<?php echo esc_attr( $plugin_path ); ?>" <?php selected( $checked_plugin, $plugin_path ); ?>><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
					<input type="checkbox" id="preserve-scan-results" name="preserve-scan-results" value="true" <?php checked( $_POST['preserve-scan-results'] ?? 'false', 'true' ); ?> />
					<label for="preserve-scan-results"><?php esc_html_e( 'Preserve Scan Results', 'plugin-check' ); ?></label>
					<br />
					<br />
					<input type="submit" onclick="function disablebutton(button){button.disabled=true;}disablebutton(this);this.closest('form').submit();" class="button primary" value="<?php esc_attr_e( 'Check Plugin', 'plugin-check' ); ?>" />
			</form>

		<?php

		if ( isset( $this->scan_results ) ) {

			wp_enqueue_script(
				'scan-results',
				plugin_dir_url( __FILE__ ) . 'includes/js/scan-results.js',
				array( 'jquery', 'wp-theme-plugin-editor' ),
				WP_PLUGIN_CHECK_VERSION,
				true
			);

			wp_localize_script(
				'scan-results',
				'scan_result_settings',
				array(
					'codeEditor' => wp_enqueue_code_editor(
						array(
							'type' => 'text/css'
						)
					)
				)
			);

			wp_enqueue_script( 'wp-theme-plugin-editor' );
			wp_enqueue_style( 'wp-codemirror' );

			printf(
				'<h2>%s</h2>',
				esc_html__( 'Scan Results', 'plugin-check')
			);

			echo '<textarea style="height: 100%;" class="scan-results widefat">' . esc_textarea( $this->scan_results ) . '</textarea>';

		}

	}

	public function get_plugins() {

		$plugins = get_plugins();

		$plugin_data = array_combine( array_keys( $plugins ), wp_list_pluck( $plugins, 'Name' ) );

		return $plugin_data;

	}

	public function remove_directory( $path ) {

		if ( ! is_dir( dirname( $path ) ) ) {

			return;

		}

		$files = glob( $path . '/*' );

		foreach ( $files as $file ) {

			is_dir( $file ) ? removeDirectory( $file ) : unlink( $file );

		}

		rmdir( $path );

	}

	public function scan_plugin( $plugin_dir = '' ) {

		if ( empty( $plugin_dir ) ) {

			echo 'Plugin path is empty.';

			return;

		}

		$plugin_path = trailingslashit( WP_PLUGIN_DIR ) . dirname( $plugin_dir );

		// Check if plugin exists
		if ( ! file_exists( $plugin_path ) ) {

			echo 'Plugin does not exist.';

			return;

		}

		$zip_destination = plugin_dir_path( __FILE__ ) . 'test-results/' . dirname( $plugin_dir ) . '/' . dirname( $plugin_dir ) . '.zip';

		// Includes
		require plugin_dir_path( __FILE__ ) . 'includes/zip-plugin.php';

		new WP_Plugin_Check_Zip_Plugin( $plugin_path . '/', $zip_destination );

		// Pass the path to that .zip to the shell script
		if ( ! file_exists( $zip_destination ) ) {

			echo "{$zip_destination} doesn't exist.";

			return;

		}

		$old_path = getcwd();

		chdir( WP_PLUGIN_SCRIPT_DIR );

		$output = shell_exec( "./plugin-scan.sh {$zip_destination}" );

		chdir( $old_path );

		$plugin_base = str_replace( '.zip', '', basename( $zip_destination ) );

		$results = dirname( $zip_destination ) . '/' . $plugin_base . '-review-default.php';

		if ( ! file_exists( $results ) ) {

			echo 'Test results file not found.';

			return;

		}

		$this->scan_results = file_get_contents( $results );

		if ( ! filter_input( INPUT_POST, 'preserve-scan-results', FILTER_VALIDATE_BOOLEAN ) ) {

			$this->remove_directory( dirname( $zip_destination ) );

		}

	}

}

new WP_Plugin_Check();
