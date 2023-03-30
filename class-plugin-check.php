<?php
/*
 * Plugin Name: Plugin Check
 * Description: Scan a plugin for various checks when developing a WordPress plugin for the WordPress.org repository.
 * Version: 0.0.3
 * Tested up to: 6.2
 * Author: Evan Herman
 * Author URI: https://evan-herman.com
 *
 * Plugin Check is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * You should have received a copy of the GNU General Public License
 * along with Plugin Check. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package WP_Plugin_Check
 */

final class WP_Plugin_Check {

	public $scan_results;

	/**
	 * Class constructor.
	 *
	 * @since 0.0.1
	 */
	public function __construct() {

		require_once plugin_dir_path( __FILE__ ) . 'includes/class-remote-update.php';

		if ( ! defined( 'WP_PLUGIN_CHECK_VERSION' ) ) {
			define( 'WP_PLUGIN_CHECK_VERSION', '0.0.3' );
		}

		if ( ! defined( 'WP_PLUGIN_SCRIPT_DIR' ) ) {
			define( 'WP_PLUGIN_SCRIPT_DIR', plugin_dir_path( __FILE__ ) . 'bin/plugin-scan/' );
		}

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

	}

	/**
	 * Add 'Plugin Check' to the tools menu.
	 *
	 * @since 0.0.1
	 */
	public function add_admin_menu() {

		add_management_page(
			'Plugin Check',
			'Plugin Check',
			'manage_options',
			'plugin-check',
			array( $this, 'plugin_check' )
		);

	}

	/**
	 * Plugin Check page.
	 *
	 * @since 0.0.1
	 */
	public function plugin_check() {

		if ( ! current_user_can( 'manage_options' ) ) {

			return;

		}

		wp_enqueue_script(
			'plugin-check',
			plugin_dir_url( __FILE__ ) . 'includes/js/plugin-check.js',
			array( 'jquery' ),
			WP_PLUGIN_CHECK_VERSION,
			true
		);

		$default_tab = null;
		$tab         = isset( $_GET['tab'] ) ? htmlspecialchars( $_GET['tab'] ) : $default_tab;

		?>

		<div class="wrap">

			<h1><?php echo esc_html( get_admin_page_title() ); ?>&nbsp;<small><?php printf( 'v%s', WP_PLUGIN_CHECK_VERSION ); ?></small></h1>

			<p class="description"><?php esc_html_e( 'Scan a plugin for various checks when developing a WordPress plugin for the WordPress.org repository.', 'plugin-check' ); ?></p>
			<p class="description"><strong><?php esc_html_e( 'Note:', 'plugin-check' ); ?></strong> <?php echo esc_html_e( 'This does not, cannot, scan for everything. What it does is provide an overview look into the code and outputs in a manner easy to return to a developer.', 'plugin-check' ); ?></p>
			<p class="description">
			<?php
			wp_kses_post(
				printf(
					/* translators: %s: link to the plugin guidelines */
					__( 'When in doubt, please read the <a href="%s" target="_blank" title="WordPress.org Plugin Guidelines">Plugin Guidelines</a> thoroughly before submitting your plugin to the WordPress.org repository. If you still require assistance, you can contact the plugin team via Slack in #pluginreview.', 'plugin-check' ),
					'https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/'
				)
			);
			?>
			</p>

			<nav class="nav-tab-wrapper">
				<a href="?page=plugin-check" class="nav-tab <?php if($tab===null):?>nav-tab-active<?php endif; ?>"><?php esc_html_e( 'Local Plugin', 'plugin-check' ); ?></a>
				<a href="?page=plugin-check&tab=remote-plugin" class="nav-tab<?php if ( 'remote-plugin' === $tab ) {?> nav-tab-active<?php } ?>"><?php esc_html_e( 'Remote Plugin', 'plugin-check' ); ?></a>
			</nav>

			<div class="tab-content">
			<?php
				switch($tab) :
				case 'remote-plugin':
					$this->remote_plugins_tab();
					break;
				default:
					$this->local_plugins_tab();
					break;
				endswitch;
			?>
			</div>
		</div>

		<?php

	}

	/**
	 * Remote Plugin Check tab.
	 *
	 * @since 0.0.1
	 */
	private function remote_plugins_tab() {

		if ( isset( $_POST['remote-plugin-url'] ) ) {

			$plugin_url = filter_input( INPUT_POST, 'remote-plugin-url', FILTER_SANITIZE_URL );

			$this->scan_remote_plugin( $plugin_url );

		}

		?>

		<h2><?php esc_html_e( 'Remote Plugin Check', 'plugin-check' ); ?></h2>

		<p class="description"><?php esc_html_e( 'Enter a URL to the remote location of your plugin .zip.', 'plugin-check' ); ?></p>

		<form class="scan-plugin" action="?page=plugin-check&tab=remote-plugin" method="post">

			<input type="text" required name="remote-plugin-url" value="<?php echo esc_attr( $plugin_url ?? '' ); ?>" style="width: 50%;" />

			<input type="checkbox" id="preserve-scan-results" name="preserve-scan-results" value="true" <?php checked( $_POST['preserve-scan-results'] ?? 'false', 'true' ); ?> />
			<label for="preserve-scan-results"><?php esc_html_e( 'Preserve Scan Results', 'plugin-check' ); ?></label>

			<br />
			<br />

			<input type="submit" class="button primary check-plugin" value="<?php esc_attr_e( 'Check Plugin', 'plugin-check' ); ?>" />
			<img class="spinner" style="display: inline-block;float: none;" src="<?php echo esc_url( admin_url( 'images/spinner.gif' ) ); ?>" class="spinner" />

		</form>

		<?php

		if ( isset( $this->scan_results ) ) {

			$this->show_scan_results();

		}

	}

	/**
	 * Local Plugin Check tab.
	 *
	 * @since 0.0.1
	 */
	private function local_plugins_tab() {

		$checked_plugin = '';

		if ( isset( $_POST['plugin-to-check'] ) ) {

			$checked_plugin = htmlspecialchars( $_POST['plugin-to-check'] );

			$this->scan_local_plugin( $checked_plugin );

		}

		?>

		<h2><?php esc_html_e( 'Local Plugin Check', 'plugin-check' ); ?></h2>

		<p class="description"><?php esc_html_e( 'Select your plugin from the dropdown to begin the plugin check.', 'plugin-check' ); ?></p>

		<form class="scan-plugin" action="?page=plugin-check&check_plugin=true" method="post">

			<select name="plugin-to-check">
				<?php foreach ( $this->get_plugins() as $plugin_path => $name ) : ?>
					<option value="<?php echo esc_attr( $plugin_path ); ?>" <?php selected( $checked_plugin, $plugin_path ); ?>><?php echo esc_html( $name ); ?></option>
				<?php endforeach; ?>
			</select>

			<input type="checkbox" id="preserve-scan-results" name="preserve-scan-results" value="true" <?php checked( $_POST['preserve-scan-results'] ?? 'false', 'true' ); ?> />
			<label for="preserve-scan-results"><?php esc_html_e( 'Preserve Scan Results', 'plugin-check' ); ?></label>

			<br />
			<br />

			<input type="submit" class="button primary check-plugin" value="<?php esc_attr_e( 'Check Plugin', 'plugin-check' ); ?>" />
			<img class="spinner" style="display: inline-block;float: none;" src="<?php echo esc_url( admin_url( 'images/spinner.gif' ) ); ?>" class="spinner" />

		</form>

		<?php

		if ( isset( $this->scan_results ) ) {

			$this->show_scan_results();

		}

	}

	/**
	 * Display the plugin scan results.
	 *
	 * @since 0.0.1
	 */
	public function show_scan_results() {

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

	/**
	 * Get a list of all plugins.
	 *
	 * @since 0.0.1
	 */
	public function get_plugins() {

		$plugins = get_plugins();

		$plugin_data = array_combine( array_keys( $plugins ), wp_list_pluck( $plugins, 'Name' ) );

		// Remove Plugin Check from the list.
		unset( $plugin_data['plugin-check/class-plugin-check.php'] );

		return $plugin_data;

	}

	/**
	 * Remove a directory and all of its contents.
	 *
	 * @since 0.0.1
	 */
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

	/**
	 * Scan a remote plugin.
	 *
	 * @since 0.0.1
	 */
	public function scan_remote_plugin( $plugin_url = '' ) {

		$plugin_url = filter_var( $plugin_url, FILTER_VALIDATE_URL );

		if ( ! $plugin_url ) {

			$this->print_notice( __( 'Invalid URL. Enter a valid URL to a plugin .zip.', 'plugin-check' ), 'error' );

			return;

		}

		$old_path = getcwd();

		chdir( WP_PLUGIN_SCRIPT_DIR );

		$output = shell_exec( "./plugin-scan.sh {$plugin_url}" );

		chdir( $old_path );

		$plugin_name = str_replace( '.zip', '', basename( $plugin_url ) );

		$zip     = WP_PLUGIN_SCRIPT_DIR . $plugin_name . '.zip';
		$results = WP_PLUGIN_SCRIPT_DIR . $plugin_name . '-review-default.php';
		$phpcs   = WP_PLUGIN_SCRIPT_DIR . $plugin_name . '-phpcs.txt';

		$destination_zip = plugin_dir_path( __FILE__ ) . 'test-results/' . $plugin_name . '/' . $plugin_name . '.zip';
		$destination_results = plugin_dir_path( __FILE__ ) . 'test-results/' . $plugin_name . '/' . $plugin_name . '-review-default.php';
		$destination_phpcs = plugin_dir_path( __FILE__ ) . 'test-results/' . $plugin_name . '/' . $plugin_name . '-phpcs.txt';

		if ( ! file_exists( plugin_dir_path( __FILE__ ) . 'test-results/' . $plugin_name ) ) {

			mkdir( plugin_dir_path( __FILE__ ) . 'test-results/' . $plugin_name, 0777, true );

		}

		// Move the scan results into our temp directory.
		rename( $zip, $destination_zip );
		rename( $results, $destination_results );
		rename( $phpcs, $destination_phpcs );

		if ( ! file_exists( $destination_results ) ) {

			$this->print_notice( __( 'Test results file not found.', 'plugin-check' ), 'error' );

			return;

		}

		$this->scan_results = file_get_contents( $destination_results );

		if ( ! filter_input( INPUT_POST, 'preserve-scan-results', FILTER_VALIDATE_BOOLEAN ) ) {

			$this->remove_directory( plugin_dir_path( __FILE__ ) . 'test-results/' . $plugin_name );

		}

	}

	/**
	 * Scan a local plugin.
	 *
	 * @since 0.0.1
	 */
	public function scan_local_plugin( $plugin_dir = '' ) {

		if ( empty( $plugin_dir ) ) {

			$this->print_notice( __( 'Plugin path is empty.', 'plugin-check' ), 'error' );

			return;

		}

		$plugin_path = trailingslashit( WP_PLUGIN_DIR ) . dirname( $plugin_dir );

		// Check if plugin exists.
		if ( ! file_exists( $plugin_path ) ) {

			$this->print_notice( __( 'Plugin does not exist.', 'plugin-check' ), 'error' );

			return;

		}

		require plugin_dir_path( __FILE__ ) . 'includes/class-zip-plugin.php';

		$zip_destination = plugin_dir_path( __FILE__ ) . 'test-results/' . dirname( $plugin_dir ) . '/' . dirname( $plugin_dir ) . '.zip';

		new WP_Plugin_Check_Zip_Plugin( $plugin_path . '/', $zip_destination );

		if ( ! file_exists( $zip_destination ) ) {

			$this->print_notice(
				sprintf(
					/* translators: %s: The .zip desitnation path. */
					__( "%s doesn't exist.", 'plugin-check' ),
					$zip_destination
				),
				'error'
			);

			return;

		}

		$old_path = getcwd();

		chdir( WP_PLUGIN_SCRIPT_DIR );

		$output = shell_exec( "./plugin-scan.sh {$zip_destination}" );

		chdir( $old_path );

		$plugin_base = str_replace( '.zip', '', basename( $zip_destination ) );

		$results = dirname( $zip_destination ) . '/' . $plugin_base . '-review-default.php';

		if ( ! file_exists( $results ) ) {

			$this->print_notice( __( 'Test results file not found.', 'plugin-check' ), 'error' );

			return;

		}

		$this->scan_results = file_get_contents( $results );

		if ( ! filter_input( INPUT_POST, 'preserve-scan-results', FILTER_VALIDATE_BOOLEAN ) ) {

			$this->remove_directory( dirname( $zip_destination ) );

		}

	}

	/**
	 * Display a notice.
	 *
	 * @param string $message     The message to display.
	 * @param string $notice_type The type of notice to display. Default is 'error'.
	 *
	 * @since 0.0.1
	 */
	private function print_notice( $message = '', $notice_type = 'error' ) {

		if ( empty( $message ) ) {

			return;

		}

		printf(
			'<div class="notice notice-%1$s">
				<p>%2$s</p>
			</div>',
			esc_attr( $notice_type ),
			esc_html( $message )
		);

	}

}

new WP_Plugin_Check();
