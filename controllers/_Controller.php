<?php

/**
 * A base class from which all other controllers should be derived
 *
 * @package EDD_Segment
 * @subpackage Controller
 */
abstract class EDD_Segment_Controller extends EDD_Segment {
	const CRON_HOOK = 'edd_segment_cron';
	const DAILY_CRON_HOOK = 'edd_segment_daily_cron';
	const SETTINGS_PAGE = 'edd_segment';
	const NONCE = 'edd_segments_controller_nonce';
	const NEW_USER_ROLE = 'sa_segmented';
	const DEFAULT_TEMPLATE_DIRECTORY = 'edd_segment_templates';

	private static $template_path = self::DEFAULT_TEMPLATE_DIRECTORY;


	public static function init() {
		if ( is_admin() ) {
			// On Activation
			add_action( 'edd_segment_plugin_activation_hook', array( __CLASS__, 'edd_segments_activated' ) );
		}

		// Enqueue
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_resources' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_resources' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'frontend_enqueue' ), 20 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue' ), 20 );

		// Cron
		add_filter( 'cron_schedules', array( __CLASS__, 'edd_segment_cron_schedule' ) );
		add_action( 'init', array( __CLASS__, 'set_schedule' ), 10, 0 );

		// Add the role.
		add_action( 'edd_segment_plugin_activation_hook',  array( __CLASS__, 'segmented_role' ), 10, 0 );
		add_action( 'edd_segment_new_user_role', array( __CLASS__, 'new_user_role' ) );
	}

	/**
	 * Fire actions based on plugin being updated.
	 * @return
	 */
	public static function edd_segments_activated() {
		add_option( 'edd_segment_do_activation_redirect', true );
		// Get the previous version number
		$edd_segment_version = get_option( 'edd_segment_current_version', self::EDD_Segment_VERSION );
		if ( version_compare( $edd_segment_version, self::EDD_Segment_VERSION, '<' ) ) { // If an upgrade create some hooks
			do_action( 'edd_segment_version_upgrade', $edd_segment_version );
			do_action( 'edd_segment_version_upgrade_'.$edd_segment_version );
		}
		// Set the new version number
		update_option( 'edd_segment_current_version', self::EDD_Segment_VERSION );
	}



	public static function register_resources() {
		// Templates
		wp_register_script( 'edd_segment', EDD_SEGMENT_URL . '/resources/front-end/js/edd-segment.js', array( 'jquery', 'redactor' ), self::EDD_Segment_VERSION );
		wp_register_style( 'edd_segment', EDD_SEGMENT_URL . '/resources/front-end/css/edd-segment.style.css', array( 'redactor' ), self::EDD_Segment_VERSION );

		// Admin
		wp_register_script( 'edd_segment_admin_js', EDD_SEGMENT_URL . '/resources/admin/js/edd-segment.js', array( 'jquery' ), self::EDD_Segment_VERSION );
		wp_register_style( 'edd_segment_admin_css', EDD_SEGMENT_URL . '/resources/admin/css/edd-segment.css', array(), self::EDD_Segment_VERSION );

	}

	public static function frontend_enqueue() {
		$edd_segment_js_object = array(
			'admin_ajax' => admin_url( 'admin-ajax.php' ),
			'sec' => wp_create_nonce( self::NONCE ),
			'post_id' => get_the_ID(),
		);
		wp_localize_script( 'edd_segment', 'edd_segment_js_object', apply_filters( 'edd_segment_scripts_localization', $edd_segment_js_object ) );

	}

	public static function admin_enqueue() {
		wp_enqueue_script( 'edd_segment_admin_js' );
		wp_enqueue_style( 'edd_segment_admin_css' );
		$edd_segment_js_object = array(
			'sec' => wp_create_nonce( self::NONCE ),
		);
		wp_localize_script( 'edd_segment_admin_js', 'edd_segment_js_object', apply_filters( 'edd_segment_scripts_localization', $edd_segment_js_object ) );
	}

	/**
	 * Filter WP Cron schedules
	 * @param  array $schedules
	 * @return array
	 */
	public static function edd_segment_cron_schedule( $schedules ) {
		$schedules['minute'] = array(
			'interval' => 60,
			'display' => __( 'Once a Minute' ),
		);
		$schedules['quarterhour'] = array(
			'interval' => 900,
			'display' => __( '15 Minutes' ),
		);
		$schedules['halfhour'] = array(
			'interval' => 1800,
			'display' => __( 'Twice Hourly' ),
		);
		return $schedules;
	}

	/**
	 * schedule wp events for wpcron.
	 */
	public static function set_schedule() {
		if ( self::DEBUG ) {
			wp_clear_scheduled_hook( self::CRON_HOOK );
		}
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			$interval = apply_filters( 'edd_segment_set_schedule', 'halfhour' );
			wp_schedule_event( time(), $interval, self::CRON_HOOK );
		}
		if ( ! wp_next_scheduled( self::DAILY_CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::DAILY_CRON_HOOK );
		}
	}

	/**
	 * Display the template for the given view
	 *
	 * @static
	 * @param string  $view
	 * @param array   $args
	 * @param bool    $allow_theme_override
	 * @return void
	 */
	public static function load_view( $view, $args, $allow_theme_override = true ) {
		// whether or not .php was added
		if ( substr( $view, -4 ) != '.php' ) {
			$view .= '.php';
		}
		$file = EDD_SEGMENT_PATH.'/views/'.$view;
		if ( $allow_theme_override && defined( 'TEMPLATEPATH' ) ) {
			$file = self::locate_template( array( $view ), $file );
		}
		$file = apply_filters( 'edd_segment_template_'.$view, $file );
		$args = apply_filters( 'load_view_args_'.$view, $args, $allow_theme_override );
		if ( ! empty( $args ) ) { extract( $args ); }
		if ( self::DEBUG ) {
			include $file;
		} else {
			include $file;
		}
	}

	/**
	 * Return a template as a string
	 *
	 * @static
	 * @param string  $view
	 * @param array   $args
	 * @param bool    $allow_theme_override
	 * @return string
	 */
	protected static function load_view_to_string( $view, $args, $allow_theme_override = true ) {
		ob_start();
		self::load_view( $view, $args, $allow_theme_override );
		return ob_get_clean();
	}

	/**
	 * Locate the template file, either in the current theme or the public views directory
	 *
	 * @static
	 * @param array   $possibilities
	 * @param string  $default
	 * @return string
	 */
	protected static function locate_template( $possibilities, $default = '' ) {
		$possibilities = apply_filters( 'edd_segment_template_possibilities', $possibilities );
		$possibilities = array_filter( $possibilities );
		// check if the theme has an override for the template
		$theme_overrides = array();
		foreach ( $possibilities as $p ) {
			$theme_overrides[] = self::get_template_path().'/'.$p;
		}
		if ( $found = locate_template( $theme_overrides, false ) ) {
			return $found;
		}

		// check for it in the templates directory
		foreach ( $possibilities as $p ) {
			if ( file_exists( EDD_SEGMENT_PATH.'/views/templates/'.$p ) ) {
				return EDD_SEGMENT_PATH.'/views/templates/'.$p;
			}
		}

		// we don't have it
		return $default;
	}


	public static function segmented_role() {
		add_role( self::NEW_USER_ROLE, self::__( 'Segmented' ), array( 'read' => true, 'level_0' => true ) );
	}

	public static function new_user_role() {
		return self::NEW_USER_ROLE;
	}

	//////////////
	// Utility //
	//////////////

	/**
	 * Template path for templates/views, default to 'invoices'.
	 *
	 * @return string self::$template_path the folder
	 */
	public static function get_template_path() {
		return apply_filters( 'edd_segment_template_path', self::$template_path );
	}

	public static function login_required( $redirect = '' ) {
		if ( ! get_current_user_id() && apply_filters( 'edd_segment_login_required', true ) ) {
			if ( ! $redirect && self::using_permalinks() ) {
				$schema = is_ssl() ? 'https://' : 'http://';
				$redirect = $schema.$_SERVER['SERVER_NAME'].htmlspecialchars( $_SERVER['REQUEST_URI'] );
				if ( isset( $_REQUEST ) ) {
					$redirect = urlencode( add_query_arg( $_REQUEST, $redirect ) );
				}
			}
			wp_redirect( wp_login_url( $redirect ) );
			exit();
		}
		return true; // explicit return value, for the benefit of the router plugin
	}

	/**
	 * Is current site using permalinks
	 * @return bool
	 */
	public static function using_permalinks() {
		return get_option( 'permalink_structure' ) != '';
	}

	/**
	 * Tell caching plugins not to cache the current page load
	 */
	public static function do_not_cache() {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();
	}

	/**
	 * Tell caching plugins to clear their caches related to a post
	 *
	 * @static
	 * @param int $post_id
	 */
	public static function clear_post_cache( $post_id ) {
		if ( function_exists( 'wp_cache_post_change' ) ) {
			// WP Super Cache

			$GLOBALS['super_cache_enabled'] = 1;
			wp_cache_post_change( $post_id );

		} elseif ( function_exists( 'w3tc_pgcache_flush_post' ) ) {
			// W3 Total Cache

			w3tc_pgcache_flush_post( $post_id );

		}
	}

	public static function ajax_fail( $message = '', $json = true ) {
		if ( $message == '' ) {
			$message = self::__( 'Something failed.' );
		}
		if ( $json ) { header( 'Content-type: application/json' ); }
		if ( self::DEBUG ) { header( 'Access-Control-Allow-Origin: *' ); }
		if ( $json ) {
			echo json_encode( array( 'error' => 1, 'response' => $message ) );
		} else {
			echo $message;
		}
		exit();
	}

	/**
	 * Comparison function
	 */
	public static function sort_by_weight( $a, $b ) {
		if ( ! isset( $a['weight'] ) || ! isset( $b['weight'] ) ) {
			return 0; }

		if ( $a['weight'] == $b['weight'] ) {
			return 0;
		}
		return ( $a['weight'] < $b['weight'] ) ? -1 : 1;
	}
}
