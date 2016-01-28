<?php
/**
 * Admin interface for bbPressKR
 *
 * @package bbPressKR
 * @subpackage Admin
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 */

namespace bbPressKR;

if ( !defined('BBPKR_PATH') ) die('HACK');

class Admin {

	static function init() {
		self::setup_actions();
	}

	private function includes() {
		// require_once( BBPKR_LIB . '/view.php' );

		// require_once( BBPKR_PATH . '/admin/forum.php' );
		// require_once( BBPKR_PATH . '/admin/meta.php' );
		// Admin\Forum::init();
		// Admin\Meta::init();

		// require( dirname(__FILE__) . '/settings.php' );
		// Admin\Settings::init();
	}

	private static function setup_actions() {
		add_action( 'admin_init', array(__CLASS__, 'admin_init') );

		Admin\Settings::init();

		do_action( 'bbpkr_admin_setup_actions' );
	}

	static function admin_init() {
		Admin\Forum::init();

		Admin\Meta::init();
	}

}

// $bbpkr_admin = Admin::instance();
