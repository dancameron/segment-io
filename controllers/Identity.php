<?php


/**
 * Integrations need to create a uid/identity, the purpose of this class
 * is to create a unique id based on the email received. The email is encrypted,
 * so that you may use it in a something not so private, e.g. a cookie.
 * uids can also be unencrypted so that the email can be accessible.
 *
 * Before you ask, "why not use a user id?"...I originally went that route
 * and found that creating a bunch of users was a hassle, especially for those
 * that hadn't explicitly registered, imagine a user trying to checkout and not
 * knowing their login because this class created it for them automatically.
 */
class EDD_Segment_Identity {
	const COOKIE = '_seg_uid';

	public static function init() {
		add_action( 'parse_request',  array( __CLASS__, 'maybe_store_uid' ) );
	}

	public static function get_uid( $email = '' ) {
		if ( ! is_email( $email ) ) {
			return 0;
		}
		// uids are always the email address,
		// it's the only constant before and after purchase.
		$uid = self::encrypt( $email );
		return $uid;
	}

	public static function get_uid_from_user_id( $user_id = 0 ) {
		if ( ! $user_id && is_user_logged_in() ) {
			$user_id = get_current_user_id();
		}
		$uid = 0;
		// attempt to find user_id
		$user = get_user_by( 'id', $user_id );
		if ( is_a( $user, 'WP_User' ) ) {
			$user_email = $user->user_email;
			$uid = self::get_uid( $user_email );
		}
		return $uid;
	}

	public static function get_email_from_uid( $uid = '' ) {
		$email = self::decrypt( $uid );
		if ( ! is_email( $email ) ) {
			return 0;
		}
		return $email;
	}

	/////////////
	// Cookie //
	/////////////

	public static function get_current_visitor_uid() {
		$uid = 0;
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			$uid = self::get_uid_from_user_id( get_current_user_id() );
		}
		if ( ! $uid && self::has_uid() ) {
			$uid = $_COOKIE[ self::COOKIE ];
		}
		return $uid;
	}

	public static function maybe_store_uid() {
		if ( isset( $_REQUEST['suid'] ) ) {
			$uid = $_REQUEST['suid'];
			self::store_uid( $uid );
		}
	}

	public static function store_uid( $uid = '' ) {
		if ( ! headers_sent() ) {
			setrawcookie( self::COOKIE, $uid, time() + ( 60 * 60 * 24 * 30 ), COOKIEPATH, COOKIE_DOMAIN );
			do_action( 'edd_segment_uid_stored', $uid );
		}
		return $uid;
	}

	public static function has_uid() {
		return isset( $_COOKIE[ self::COOKIE ] );
	}

	////////////////////////
	// Encrypt functions //
	////////////////////////

	public static function encrypt( $input ) {
		$crypt = base64_encode( mcrypt_encrypt( MCRYPT_RIJNDAEL_256, hash( 'sha256', AUTH_KEY . SECURE_AUTH_KEY, true ), $input, MCRYPT_MODE_ECB, mcrypt_create_iv( 32 ) ) );
		$id = strtr( $crypt, '+/=', '-_~' );
		return $id;
	}

	public static function decrypt( $id ) {
		$crypt = strtr( $id, '-_~', '+/=' );
		$decrypt = trim( mcrypt_decrypt( MCRYPT_RIJNDAEL_256, hash( 'sha256', AUTH_KEY . SECURE_AUTH_KEY, true ), base64_decode( $crypt ), MCRYPT_MODE_ECB, mcrypt_create_iv( 32 ) ) );
		return $decrypt;
	}
}
