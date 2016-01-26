<?php
/**
 * @package bbPressKR
 * @subpackage Forum Roles
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 */

namespace bbPressKR\Forum;

if ( !defined('BBPKR_PATH') ) die('HACK');

class Roles {

	protected static $reinitialized = false;

	static function init() {
		// add_action( 'template_redirect', array( __CLASS__, 'reinit_user_caps' ) );
		// add_filter( 'user_has_cap', array( __CLASS__, 'debug' ), 11, 4 );
		if ( !did_action('setup_theme') )
			add_action( 'after_setup_theme', array( __CLASS__, 'regiseter_caps_filter' ) );
		else
			self::regiseter_caps_filter();
	}

	static function regiseter_caps_filter() {
		add_filter( 'user_has_cap', array( __CLASS__, 'user_has_cap' ), 10, 4 );
	}

	// We should check capabilites every time for shortcodes
	static function user_has_cap( $allcaps, $caps, $args, $user ) {
		static $doing = false;

		// bypass if non-bbpress contents and avoid infinite loop
		if ( $doing === true || !did_action('wp') || !is_bbpress() ) {
			return $allcaps;
		}

		$bbp_allcaps = bbp_get_caps_for_role( bbp_get_keymaster_role() );
		$check_caps = array_intersect(
			array_values( $caps ),
			array_keys( $bbp_allcaps )
		);
		if ( ! $check_caps && in_array( 'upload_files', $caps ) ) {
			$check_caps = array( 'upload_files' );
		}

		// bypass non bbpress caps
		if ( ! $check_caps || in_array( 'keep_gate', $check_caps ) ) {
			return $allcaps;
		}

		$doing = true;

		if ( ! $forum_id = bbp_get_forum_id() ) {
			$doing = false;
			return $allcaps;
		}

		// Give all modorator capabilities to optional forum moderators per forum
		$moderators = bbpresskr()->forum_option('moderators', $forum_id);
		if ( in_array( $user->ID, $moderators ) && ($new_caps = bbp_get_caps_for_role( bbp_get_moderator_role() )) ) {
			// remove all bbpress capabilities and append for asigned role only
			$_allcaps = array_diff_assoc( $allcaps, $bbp_allcaps );
			$allcaps = array_merge( $_allcaps, $new_caps );
			$allcaps['upload_files'] = true;
		}

		// In case we use usermeta for forum moderator setting
		/*$metakey = "forum_role_{$forum_id}";
		// Dobule check forum role still exists. Role may excluded by plugin or updates.
		if ( isset( $user->$metakey ) && ($new_caps = bbp_get_caps_for_role( $user->$metakey )) ) {
			// remove all bbpress capabilities and append for asigned role only
			$_allcaps = array_diff_assoc( $allcaps, $bbp_allcaps );
			$allcaps = array_merge( $_allcaps, $new_caps );
			$allcaps['upload_files'] = true;
		}*/

		$doing = false;
		return $allcaps;
	}

}

// Roles::init();
