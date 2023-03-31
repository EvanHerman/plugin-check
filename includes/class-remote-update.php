<?php
/**
 * Receive remote updates for GitHub.
 *
 * @package WP_Plugin_Check
 * @since 0.0.3
 */
class WP_Plugin_Check_Remote_Updater {

	public $plugin_slug;
	public $version;
	public $cache_key;
	public $cache_allowed;

	/**
	 * Class constructor.
	 */
	public function __construct() {

		$this->plugin_slug   = dirname( plugin_basename( __DIR__ ) );
		$this->version       = '0.0.3';
		$this->cache_key     = 'wp_plugin_check_remote_updater';
		$this->cache_allowed = true;

		add_filter( 'plugins_api', array( $this, 'info' ), 20, 3 );
		add_filter( 'site_transient_update_plugins', array( $this, 'update' ) );
		add_action( 'upgrader_process_complete', array( $this, 'purge' ), 10, 2 );

	}

	/**
	 * Get the remote manifest file data.
	 *
	 * @return array Remote manifest file data.
	 */
	public function request() {

		$remote = get_transient( $this->cache_key );

		if ( false === $remote || ! $this->cache_allowed ) {

			$remote = wp_remote_get(
				'https://raw.githubusercontent.com/EvanHerman/plugin-check/main/remote-update-assets/manifest.json',
				array(
					'timeout' => 10,
					'headers' => array(
						'Accept' => 'application/json',
					),
				)
			);

			if ( is_wp_error( $remote ) || 200 !== wp_remote_retrieve_response_code( $remote ) || empty( wp_remote_retrieve_body( $remote ) ) ) {
				return false;
			}

			if ( $this->cache_allowed ) {

				set_transient( $this->cache_key, $remote, 12 * HOUR_IN_SECONDS );

			}
		}

		$remote = json_decode( wp_remote_retrieve_body( $remote ) );

		return $remote;

	}

	/**
	 * Retreive plugin information.
	 *
	 * @param   object $response Plugin info response object.
	 * @param   string $action   The action being run.
	 * @param   array  $args     The arguments for the action.
	 *
	 * @return  object $response  Plugin info response object.
	 */
	public function info( $response, $action, $args ) {

		// Do nothing if you're not getting plugin information right now.
		if ( 'plugin_information' !== $action ) {
			return $response;
		}

		// Do nothing if it is not our plugin.
		if ( empty( $args->slug ) || $this->plugin_slug !== $args->slug ) {
			return $response;
		}

		// Get updates.
		$remote = $this->request();

		if ( ! $remote ) {
			return $response;
		}

		$response = new \stdClass();

		$response->name           = $remote->name;
		$response->slug           = $remote->slug;
		$response->version        = $remote->version;
		$response->tested         = $remote->tested;
		$response->requires       = $remote->requires;
		$response->author         = $remote->author;
		$response->author_profile = $remote->author_profile;
		$response->donate_link    = $remote->donate_link;
		$response->homepage       = $remote->homepage;
		$response->download_link  = $remote->download_url;
		$response->trunk          = $remote->download_url;
		$response->requires_php   = $remote->requires_php;
		$response->last_updated   = $remote->last_updated;

		$response->sections = array(
			'description'  => $remote->sections->description,
			'installation' => $remote->sections->installation,
			'changelog'    => $remote->sections->changelog,
		);

		if ( ! empty( $remote->banners ) ) {
			$response->banners = array(
				'low'  => $remote->banners->low,
				'high' => $remote->banners->high,
			);
		}

		return $response;

	}

	/**
	 * Check for remote update.
	 *
	 * @param   object $transient Transient object.
	 *
	 * @return  object $transient Transient object.
	 */
	public function update( $transient ) {

		// Prevents our plugin from checking updates with https://wordpress.org/plugins/plugin-check/.
		if ( isset( $transient->response['plugin-check/class-plugin-check.php'] ) ) {
			unset( $transient->response['plugin-check/class-plugin-check.php'] );
		}

		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$remote = $this->request();

		if ( $remote && version_compare( $this->version, $remote->version, '<' ) && version_compare( $remote->requires, get_bloginfo( 'version' ), '<=' ) && version_compare( $remote->requires_php, PHP_VERSION, '<' ) ) {
			$response              = new \stdClass();
			$response->slug        = $this->plugin_slug;
			$response->plugin      = "{$this->plugin_slug}/class-plugin-check.php";
			$response->new_version = $remote->version;
			$response->tested      = $remote->tested;
			$response->package     = $remote->download_url;

			$transient->response[ $response->plugin ] = $response;
		}

		return $transient;

	}

	/**
	 * Run after the upgrade process is complete.
	 *
	 * @param object $upgrader  WP_Upgrader instance.
	 * @param array  $options   Options for the upgrader.
	 *
	 * @return void
	 */
	public function purge( $upgrader, $options ) {

		if ( $this->cache_allowed && 'update' === $options['action'] && 'plugin' === $options['type'] ) {
			// Clean the cache when new plugin version is installed.
			delete_transient( $this->cache_key );
		}

		// Make our .sh files executable.
		exec( 'chmod +x ' . WP_PLUGIN_SCRIPT_DIR . '*.sh' );

	}

}

new WP_Plugin_Check_Remote_Updater();
