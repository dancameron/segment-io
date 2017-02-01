<?php


/**
 * Updates class
 *
 * @package Sprout_Apps
 * @subpackage Updates
 */
class EDD_Segment_Updates extends EDD_Segment_Controller {
	const LICENSE_KEY_OPTION = 'edd_segment_license_key';
	const LICENSE_STATUS = 'edd_segment_license_status';
	protected static $license_key;
	protected static $license_status;

	public static function init() {
		self::$license_key = trim( get_option( self::LICENSE_KEY_OPTION, '' ) );
		self::$license_status = get_option( self::LICENSE_STATUS, false );
		self::register_settings();

		if ( is_admin() ) {
			add_action( 'admin_init', array( __CLASS__, 'init_edd_udpater' ) );

			// AJAX
			add_action( 'wp_ajax_edd_segment_activate_license',  array( __CLASS__, 'maybe_activate_license' ), 10, 0 );
			add_action( 'wp_ajax_edd_segment_deactivate_license',  array( __CLASS__, 'maybe_deactivate_license' ), 10, 0 );
			add_action( 'wp_ajax_edd_segment_check_license',  array( __CLASS__, 'maybe_check_license' ), 10, 0 );
		}
	}

	public static function init_edd_udpater() {

		// setup the updater
		$edd_updater = new EDD_SL_Plugin_Updater_SA_Mod( self::PLUGIN_URL, self::PLUGIN_FILE, array(
				'version' 	=> self::EDD_Segment_VERSION,			// current version number
				'license' 	=> self::$license_key,	 		// license key (used get_option above to retrieve from DB)
				'item_name' => self::PLUGIN_NAME, 			// name of this plugin
				'author' 	=> 'Sprout Apps',// author of this plugin
			)
		);

		// $edd_updater->api_request( 'plugin_latest_version', array( 'slug' => basename( self::PLUGIN_FILE, '.php') ) );

		// uncomment this line for testing
		// set_site_transient( 'update_plugins', null );
	}

	public static function license_key() {
		return self::$license_key;
	}

	public static function license_status() {
		return self::$license_status;
	}

	///////////////
	// Settings //
	///////////////

	/**
	 * Hooked on init add the settings page and options.
	 *
	 */
	public static function register_settings() {
		// Settings
		$settings = array(
			'edd_segment_activation' => array(
				'title' => self::__( 'EDD Segment Activation' ),
				'weight' => 0,
				'callback' => array( __CLASS__, 'update_setting_description' ),
				'settings' => array(
					self::LICENSE_KEY_OPTION => array(
						'label' => self::__( 'License Key' ),
						'option' => array(
							'type' => 'bypass',
							'output' => self::license_key_option(),
							'description' => sprintf( self::__( 'Enter your license key to enable automatic plugin updates. Find your license key in your Sprout Apps Dashboard under the <a href="%s" target="_blank">Downloads</a> section.' ), self::PLUGIN_URL.'/account/' ),
							),
						),
					),
				),
			);
		do_action( 'sprout_settings', $settings, self::SETTINGS_PAGE );

	}

	public static function license_key_option() {
		ob_start(); ?>
			<input type="text" name="<?php echo self::LICENSE_KEY_OPTION ?>" id="<?php echo self::LICENSE_KEY_OPTION ?>" value="<?php echo self::$license_key ?>" class="<?php echo 'license_'.self::$license_status ?>" size="40" class="text-input">
			<?php if ( self::$license_status != false && self::$license_status == 'valid' ) :  ?>
				<button id="edd_segment_activate_license" class="button" disabled="disabled"><?php self::_e( 'Activate License' ) ?></button> 
				<button id="edd_segment_deactivate_license" class="button"><?php self::_e( 'Deactivate License' ) ?></button>
			<?php else : ?>
				<button id="edd_segment_activate_license" class="button button-primary"><?php self::_e( 'Activate License' ) ?></button>
			<?php endif ?>
			<div id="license_message" class="clearfix"></div>
		<?php
		$view = ob_get_clean();
		return $view;
	}

	public static function update_setting_description() {
		// self::_e('TODO Describe the license key and how to purchase.');
	}


	///////////////////
	// API Controls //
	///////////////////

	public static function activate_license() {
		$license_data = self::api( 'activate_license' );

		if ( is_object( $license_data ) ) {
			// $license_data->license will be either "deactivated" or "failed"
			if ( $license_data->license == 'valid' ) {
				update_option( self::LICENSE_STATUS, $license_data->license );
				return true;
			}
		}

		return false;
	}

	public static function deactivate_license() {
		$license_data = self::api( 'deactivate_license' );

		if ( is_object( $license_data ) ) {
			// $license_data->license will be either "deactivated" or "failed"
			if ( $license_data->license == 'deactivated' ) {
				delete_option( self::LICENSE_STATUS );
				return true;
			}
		}
		return false;
	}

	public static function check_license() {
		$license_data = self::api( 'check_license' );
		return ( $license_data->license == 'valid' );
	}

	///////////
	// AJAX //
	///////////

	public static function maybe_activate_license() {
		if ( ! isset( $_REQUEST['security'] ) ) {
			self::ajax_fail( 'Forget something?' ); }

		$nonce = $_REQUEST['security'];
		if ( ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			self::ajax_fail( 'Not going to fall for it!' ); }

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return; }

		if ( ! isset( $_REQUEST['license'] ) ) {
			self::ajax_fail( 'No license key submitted' );
		}

		update_option( self::LICENSE_KEY_OPTION, $_REQUEST['license'] );
		self::$license_key = $_REQUEST['license'];

		$activated = self::activate_license();
		$message = ( $activated ) ? self::__( 'Thank you for supporting the future of EDD Segment and Sprout Apps.' ) : self::__( 'License is not active.' );
		$response = array(
				'activated' => $activated,
				'response' => $message,
				'error' => ! $activated,
			);

		header( 'Content-type: application/json' );
		echo json_encode( $response );
		exit();
	}

	public static function maybe_deactivate_license() {
		if ( ! isset( $_REQUEST['security'] ) ) {
			self::ajax_fail( 'Forget something?' ); }

		$nonce = $_REQUEST['security'];
		if ( ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			self::ajax_fail( 'Not going to fall for it!' ); }

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return; }

		$deactivated = self::deactivate_license();
		$message = ( $deactivated ) ? self::__( 'License is deactivated.' ) : self::__( 'Something went wrong. Contact support for help.' );
		$response = array(
				'valid' => $deactivated,
				'response' => $message,
				'error' => ! $deactivated,
			);

		header( 'Content-type: application/json' );
		echo json_encode( $response );
		exit();
	}

	public static function maybe_check_license() {
		if ( ! isset( $_REQUEST['security'] ) ) {
			self::ajax_fail( 'Forget something?' ); }

		$nonce = $_REQUEST['security'];
		if ( ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			self::ajax_fail( 'Not going to fall for it!' ); }

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return; }

		$is_valid = self::check_license();
		$message = ( $is_valid ) ? self::__( 'Thank you for supporting the future of EDD Segment and Sprout Apps.' ) : self::__( 'License is not valid.' );
		$response = array(
				'valid' => $is_valid,
				'response' => $message,
			);

		header( 'Content-type: application/json' );
		echo json_encode( $response );
		exit();
	}



	//////////////
	// Utility //
	//////////////


	public static function api( $action = 'activate_license', $api_args = array() ) {
		// data to send in our API request
		$api_params_defaults = array(
			'edd_action' => $action,
			'license' => self::$license_key,
			'item_name' => urlencode( self::PLUGIN_NAME ),
			'url'       => home_url(),
		);
		$api_params = wp_parse_args( $api_args, $api_params_defaults );

		// Call the custom API.
		$response = wp_remote_get( add_query_arg( $api_params, self::PLUGIN_URL ), array( 'timeout' => 15, 'sslverify' => false ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) ) {
			return false; }

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		return $license_data;
	}
}

if ( ! class_exists( 'EDD_SL_Plugin_Updater_SA_Mod' ) ) :


	/**
	 * Allows plugins to use their own update API.
	 *
	 * @author Easy Digital Downloads
	 * @version 1.6.9 // with "// Modification"
	 */
	class EDD_SL_Plugin_Updater_SA_Mod {

		private $api_url     = '';
		private $api_data    = array();
		private $name        = '';
		private $slug        = '';
		private $version     = '';
		private $wp_override = false;
		private $cache_key   = '';

		/**
		 * Class constructor.
		 *
		 * @uses plugin_basename()
		 * @uses hook()
		 *
		 * @param string  $_api_url     The URL pointing to the custom API endpoint.
		 * @param string  $_plugin_file Path to the plugin file.
		 * @param array   $_api_data    Optional data to send with API calls.
		 */
		public function __construct( $_api_url, $_plugin_file, $_api_data = null ) {

			global $edd_plugin_data;

			$this->api_url     = trailingslashit( $_api_url );
			$this->api_data    = $_api_data;
			$this->name        = plugin_basename( $_plugin_file );
			$this->slug        = basename( $_plugin_file, '.php' );
			$this->version     = $_api_data['version'];
			$this->wp_override = isset( $_api_data['wp_override'] ) ? (bool) $_api_data['wp_override'] : false;

			$this->cache_key   = md5( serialize( $this->slug . $this->api_data['license'] ) );

			$edd_plugin_data[ $this->slug ] = $this->api_data;

			// Set up hooks.
			$this->init();

		}

		/**
		 * Set up WordPress filters to hook into WP's update process.
		 *
		 * @uses add_filter()
		 *
		 * @return void
		 */
		public function init() {

			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
			add_filter( 'plugins_api', array( $this, 'plugins_api_filter' ), 10, 3 );
			remove_action( 'after_plugin_row_' . $this->name, 'wp_plugin_update_row', 10 );
			add_action( 'after_plugin_row_' . $this->name, array( $this, 'show_update_notification' ), 10, 2 );
			add_action( 'admin_init', array( $this, 'show_changelog' ) );

		}

		/**
		 * Check for Updates at the defined API endpoint and modify the update array.
		 *
		 * This function dives into the update API just when WordPress creates its update array,
		 * then adds a custom API call and injects the custom plugin data retrieved from the API.
		 * It is reassembled from parts of the native WordPress plugin update code.
		 * See wp-includes/update.php line 121 for the original wp_update_plugins() function.
		 *
		 * @uses api_request()
		 *
		 * @param array   $_transient_data Update array build by WordPress.
		 * @return array Modified update array with custom plugin data.
		 */
		public function check_update( $_transient_data ) {

			global $pagenow;

			if ( ! is_object( $_transient_data ) ) {
				$_transient_data = new stdClass;
			}

			if ( 'plugins.php' == $pagenow && is_multisite() ) {
				return $_transient_data;
			}

			if ( ! empty( $_transient_data->response ) && ! empty( $_transient_data->response[ $this->name ] ) && false === $this->wp_override ) {
				return $_transient_data;
			}

			$version_info = $this->get_cached_version_info();

			if ( false === $version_info ) {
				$version_info = $this->api_request( 'plugin_latest_version', array( 'slug' => $this->slug, 'beta' => ! empty( $this->api_data['beta'] ) ) );

				$this->set_version_info_cache( $version_info );

			}

			if ( false !== $version_info && is_object( $version_info ) && isset( $version_info->new_version ) ) {

				if ( version_compare( $this->version, $version_info->new_version, '<' ) ) {

					$_transient_data->response[ $this->name ] = $version_info;

				}

				$_transient_data->last_checked           = current_time( 'timestamp' );
				$_transient_data->checked[ $this->name ] = $this->version;

			}

			return $_transient_data;
		}

		/**
		 * show update nofication row -- needed for multisite subsites, because WP won't tell you otherwise!
		 *
		 * @param string  $file
		 * @param array   $plugin
		 */
		public function show_update_notification( $file, $plugin ) {

			if ( is_network_admin() ) {
				return;
			}

			if ( ! current_user_can( 'update_plugins' ) ) {
				return;
			}

			if ( ! is_multisite() ) {
				return;
			}

			if ( $this->name != $file ) {
				return;
			}

			// Remove our filter on the site transient
			remove_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ), 10 );

			$update_cache = get_site_transient( 'update_plugins' );

			$update_cache = is_object( $update_cache ) ? $update_cache : new stdClass();

			if ( empty( $update_cache->response ) || empty( $update_cache->response[ $this->name ] ) ) {

				$version_info = $this->get_cached_version_info();

				if ( false === $version_info ) {
					$version_info = $this->api_request( 'plugin_latest_version', array( 'slug' => $this->slug, 'beta' => ! empty( $this->api_data['beta'] ) ) );

					$this->set_version_info_cache( $version_info );
				}

				if ( ! is_object( $version_info ) ) {
					return;
				}

				if ( version_compare( $this->version, $version_info->new_version, '<' ) ) {

					$update_cache->response[ $this->name ] = $version_info;

				}

				$update_cache->last_checked = current_time( 'timestamp' );
				$update_cache->checked[ $this->name ] = $this->version;

				set_site_transient( 'update_plugins', $update_cache );

			} else {

				$version_info = $update_cache->response[ $this->name ];

			}

			// Restore our filter
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );

			if ( ! empty( $update_cache->response[ $this->name ] ) && version_compare( $this->version, $version_info->new_version, '<' ) ) {

				// build a plugin list row, with update notification
				$wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );
				# <tr class="plugin-update-tr"><td colspan="' . $wp_list_table->get_column_count() . '" class="plugin-update colspanchange">
				echo '<tr class="plugin-update-tr" id="' . $this->slug . '-update" data-slug="' . $this->slug . '" data-plugin="' . $this->slug . '/' . $file . '">';
				echo '<td colspan="3" class="plugin-update colspanchange">';
				echo '<div class="update-message notice inline notice-warning notice-alt">';

				$changelog_link = self_admin_url( 'index.php?edd_sl_action=view_plugin_changelog&plugin=' . $this->name . '&slug=' . $this->slug . '&TB_iframe=true&width=772&height=911' );

				if ( empty( $version_info->download_link ) ) {
					printf(
						__( 'There is a new version of %1$s available. %2$sView version %3$s details%4$s.', 'easy-digital-downloads' ),
						esc_html( $version_info->name ),
						'<a target="_blank" class="thickbox" href="' . esc_url( $changelog_link ) . '">',
						esc_html( $version_info->new_version ),
						'</a>'
					);
				} else {
					printf(
						__( 'There is a new version of %1$s available. %2$sView version %3$s details%4$s or %5$supdate now%6$s.', 'easy-digital-downloads' ),
						esc_html( $version_info->name ),
						'<a target="_blank" class="thickbox" href="' . esc_url( $changelog_link ) . '">',
						esc_html( $version_info->new_version ),
						'</a>',
						'<a href="' . esc_url( wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $this->name, 'upgrade-plugin_' . $this->name ) ) .'">',
						'</a>'
					);
				}

				do_action( "in_plugin_update_message-{$file}", $plugin, $version_info );

				echo '</div></td></tr>';
			}
		}

		/**
		 * Updates information on the "View version x.x details" page with custom data.
		 *
		 * @uses api_request()
		 *
		 * @param mixed   $_data
		 * @param string  $_action
		 * @param object  $_args
		 * @return object $_data
		 */
		public function plugins_api_filter( $_data, $_action = '', $_args = null ) {

			if ( $_action != 'plugin_information' ) {

				return $_data;

			}

			if ( ! isset( $_args->slug ) || ( $_args->slug != $this->slug ) ) {

				return $_data;

			}

			$to_send = array(
				'slug'   => $this->slug,
				'is_ssl' => is_ssl(),
				'fields' => array(
					'banners' => array(),
					'reviews' => false,
				),
			);

			$cache_key = 'edd_api_request_' . md5( serialize( $this->slug . $this->api_data['license'] ) );

			// Get the transient where we store the api request for this plugin for 24 hours
			$edd_api_request_transient = $this->get_cached_version_info( $cache_key );

			//If we have no transient-saved value, run the API, set a fresh transient with the API value, and return that value too right now.
			if ( empty( $edd_api_request_transient ) ) {

				$api_response = $this->api_request( 'plugin_information', $to_send );

				// Expires in 3 hours
				$this->set_version_info_cache( $api_response, $cache_key );

				if ( false !== $api_response ) {
					$_data = $api_response;
				}
			} else {
				$_data = $edd_api_request_transient;
			}

			return $_data;
		}

		/**
		 * Disable SSL verification in order to prevent download update failures
		 *
		 * @param array   $args
		 * @param string  $url
		 * @return object $array
		 */
		public function http_request_args( $args, $url ) {
			// If it is an https request and we are performing a package download, disable ssl verification
			if ( strpos( $url, 'https://' ) !== false && strpos( $url, 'edd_action=package_download' ) ) {
				$args['sslverify'] = false;
			}
			return $args;
		}

		/**
		 * Calls the API and, if successfull, returns the object delivered by the API.
		 *
		 * @uses get_bloginfo()
		 * @uses wp_remote_post()
		 * @uses is_wp_error()
		 *
		 * @param string  $_action The requested action.
		 * @param array   $_data   Parameters for the API action.
		 * @return false|object
		 */
		private function api_request( $_action, $_data ) {

			global $wp_version;

			$data = array_merge( $this->api_data, $_data );

			if ( $data['slug'] != $this->slug ) {
				return;
			}

			if ( $this->api_url == trailingslashit( home_url() ) ) {
				return false; // Don't allow a plugin to ping itself
			}

			// Modification
			if ( ! isset( $data['item_id'] ) && isset( $data['download_id'] ) ) {
				$data['item_id'] = $data['download_id'];
			}

			$api_params = array(
				'edd_action' => 'get_version',
				'license'    => ! empty( $data['license'] ) ? $data['license'] : '',
				'item_name'  => isset( $data['item_name'] ) ? $data['item_name'] : false,
				'item_id'    => isset( $data['item_id'] ) ? $data['item_id'] : false,
				'version'    => isset( $data['version'] ) ? $data['version'] : false,
				'slug'       => $data['slug'],
				'author'     => $data['author'],
				'url'        => home_url(),
				'beta'       => ! empty( $data['beta'] ),
			);

			$request = wp_remote_post( $this->api_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

			if ( ! is_wp_error( $request ) ) {
				$request = json_decode( wp_remote_retrieve_body( $request ) );
			}

			if ( $request && isset( $request->sections ) ) {
				$request->sections = maybe_unserialize( $request->sections );
			} else {
				$request = false;
			}

			if ( $request && isset( $request->banners ) ) {
				$request->banners = maybe_unserialize( $request->banners );
			}

			if ( ! empty( $request ) ) {
				foreach ( $request->sections as $key => $section ) {
					$request->$key = (array) $section;
				}
			}

			return $request;
		}

		public function show_changelog() {

			global $edd_plugin_data;

			if ( empty( $_REQUEST['edd_sl_action'] ) || 'view_plugin_changelog' != $_REQUEST['edd_sl_action'] ) {
				return;
			}

			if ( empty( $_REQUEST['plugin'] ) ) {
				return;
			}

			if ( empty( $_REQUEST['slug'] ) ) {
				return;
			}

			if ( ! current_user_can( 'update_plugins' ) ) {
				wp_die( __( 'You do not have permission to install plugin updates', 'easy-digital-downloads' ), __( 'Error', 'easy-digital-downloads' ), array( 'response' => 403 ) );
			}

			$data         = $edd_plugin_data[ $_REQUEST['slug'] ];
			$cache_key    = md5( 'edd_plugin_' . sanitize_key( $_REQUEST['plugin'] ) . '_version_info' );
			$version_info = $this->get_cached_version_info( $cache_key );

			if ( false === $version_info ) {

				$api_params = array(
					'edd_action' => 'get_version',
					'item_name'  => isset( $data['item_name'] ) ? $data['item_name'] : false,
					'item_id'    => isset( $data['item_id'] ) ? $data['item_id'] : false,
					'slug'       => $_REQUEST['slug'],
					'author'     => $data['author'],
					'url'        => home_url(),
					'beta'       => ! empty( $data['beta'] ),
				);

				$request = wp_remote_post( $this->api_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

				if ( ! is_wp_error( $request ) ) {
					$version_info = json_decode( wp_remote_retrieve_body( $request ) );
				}

				if ( ! empty( $version_info ) && isset( $version_info->sections ) ) {
					$version_info->sections = maybe_unserialize( $version_info->sections );
				} else {
					$version_info = false;
				}

				if ( ! empty( $version_info ) ) {
					foreach ( $version_info->sections as $key => $section ) {
						$version_info->$key = (array) $section;
					}
				}

				$this->set_version_info_cache( $version_info, $cache_key );

			}

			if ( ! empty( $version_info ) && isset( $version_info->sections['changelog'] ) ) {
				echo '<div style="background:#fff;padding:10px;">' . $version_info->sections['changelog'] . '</div>';
			}

			exit;
		}

		public function get_cached_version_info( $cache_key = '' ) {

			if ( empty( $cache_key ) ) {
				$cache_key = $this->cache_key;
			}

			$cache = get_option( $cache_key );

			if ( empty( $cache['timeout'] ) || current_time( 'timestamp' ) > $cache['timeout'] ) {
				return false; // Cache is expired
			}

			return json_decode( $cache['value'] );

		}

		public function set_version_info_cache( $value = '', $cache_key = '' ) {

			if ( empty( $cache_key ) ) {
				$cache_key = $this->cache_key;
			}

			$data = array(
				'timeout' => strtotime( '+3 hours', current_time( 'timestamp' ) ),
				'value'   => json_encode( $value ),
			);

			update_option( $cache_key, $data );

		}
	}
endif;
