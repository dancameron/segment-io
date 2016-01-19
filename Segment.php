<?php


/**
 * A fundamental class from which all other classes in the plugin should be derived.
 * The purpose of this class is to hold data useful to all classes.
 * @package SI
 */

if ( ! defined( 'EDD_Segment_FREE_TEST' ) ) {
	define( 'EDD_Segment_FREE_TEST', false ); }

if ( ! defined( 'EDD_SEGMENT_DEV' ) ) {
	define( 'EDD_SEGMENT_DEV', false ); }

abstract class EDD_Segment {

	/**
	 * Application text-domain
	 */
	const TEXT_DOMAIN = 'sprout-apps';
	/**
	 * Application text-domain
	 */
	const PLUGIN_URL = 'https://sproutapps.co';
	/**
	 * Current version. Should match sprout-invoices.php plugin version.
	 */
	const EDD_Segment_VERSION = '1.2';
	/**
	 * DB Version
	 */
	const DB_VERSION = 1;
	/**
	 * Application Name
	 */
	const PLUGIN_NAME = 'EDD Segment';
	const PLUGIN_FILE = EDD_SEGMENT_PLUGIN_FILE;
	/**
	 * EDD_SEGMENT_DEV constant within the wp-config to turn on SI debugging
	 * <code>
	 * define( 'EDD_SEGMENT_DEV', TRUE/FALSE )
	 * </code>
	 */
	const DEBUG = EDD_SEGMENT_DEV;

	/**
	 * A wrapper around WP's __() to add the plugin's text domain
	 *
	 * @param string  $string
	 * @return string|void
	 */
	public static function __( $string ) {
		return __( apply_filters( 'edd_segment_string_'.sanitize_title( $string ), $string ), self::TEXT_DOMAIN );
	}

	/**
	 * A wrapper around WP's _e() to add the plugin's text domain
	 *
	 * @param string  $string
	 * @return void
	 */
	public static function _e( $string ) {
		return _e( apply_filters( 'edd_segment_string_'.sanitize_title( $string ), $string ), self::TEXT_DOMAIN );
	}

	/**
	 * Wrapper around esc_attr__
	 * @param  string $string
	 * @return
	 */
	public static function esc__( $string ) {
		return esc_attr__( $string, self::TEXT_DOMAIN );
	}

	/**
	 * Wrapper around esc_attr__
	 * @param  string $string
	 * @return
	 */
	public static function esc_e( $string ) {
		return esc_attr_e( $string, self::TEXT_DOMAIN );
	}
}
