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

	function __construct() {}

	public static function instance() {
		static $instance = null;
		if ( $instance === null ) {
			$instance = new Admin;
			$instance->includes();
			$instance->setup_actions();
		}
		return $instance;
	}

	private function includes() {
		require_once( BBPKR_LIB . '/view.php' );

		require_once( BBPKR_PATH . '/admin/forum.php' );
		require_once( BBPKR_PATH . '/admin/meta.php' );
		Admin\Forum::init();
		Admin\Meta::init();

		require( dirname(__FILE__) . '/settings.php' );
		Admin\Settings::init();
	}

	private function setup_actions() {
		do_action( 'bbpkr_admin_setup_actions' );
	}

}

$bbpkr_admin = Admin::instance();
