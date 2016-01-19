<?php
/**
 * @package bbPressKR
 * @subpackage Theme Compat
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 */

namespace bbPressKR;

if ( !defined('BBPKR_PATH') ) die('HACK');

class Theme {

	static function init() {
		add_action( 'after_setup_theme', array( __CLASS__, 'after_setup_theme' ), 30 );
		add_action( 'bbpkr_setup_actions', array( __CLASS__, 'setup_actions' ) );
	}

	static function after_setup_theme() {
		// Enfold theme
		remove_filter( 'bbp_default_styles', 'avia_bbpress_deregister_default_assets', 10, 1 );
		if ( !is_admin() ) {
			remove_action( 'bbp_enqueue_scripts', 'avia_bbpress_register_assets', 15 );
		}
	}

	static function setup_actions() {
		add_filter( 'bbp_template_stack', array( __CLASS__, 'default_theme_dir' ), 13.99999 );
		add_filter( 'bbp_add_template_stack_locations', array( __CLASS__, 'add_template_locations' ) );

		// print bbpkr wrapper
		add_action( 'bbp_template_before_single_topic', array( __CLASS__, 'template_container_start' ), -999 );
		add_action( 'bbp_template_before_topics_loop', array( __CLASS__, 'template_container_start' ), -999 );
		add_action( 'bbp_template_before_forums_loop', array( __CLASS__, 'template_container_start' ), -999 );
		add_action( 'bbp_template_after_single_topic', array( __CLASS__, 'template_container_end' ), 999 );
		add_action( 'bbp_template_after_topics_loop', array( __CLASS__, 'template_container_end' ), 999 );
		add_action( 'bbp_template_after_forums_loop', array( __CLASS__, 'template_container_end' ), 999 );

	}

	static function default_theme_dir() {
		return BBPKR_PATH . '/templates/default';
	}

	static function add_template_locations($locations) {
		$stylesheet_dir = get_stylesheet_directory();
		$template_dir = get_template_directory();
		$compat_dir = untrailingslashit( bbp_get_theme_compat_dir() );
		foreach ( $locations as $k => $location ) {
			// append 'kr' to all locations
			if ( strpos( $location, $compat_dir ) === 0 || strpos( $location, BBPKR_PATH ) === 0 ) {
				// var_dump($location);
				continue;
			}
			if ( $location == $template_dir || $location == $stylesheet_dir ) {
				// unset( $locations[$k] );
				continue;
			}
			$locations[$k] .= 'kr';
		}

		return $locations;
	}

	public static function template_container_start() {
		$type = bbpresskr()->forum_option('skin');
		echo '<div class="bbpkr bbpskin-'.$type.'">';
	}
	public static function template_container_end() {
		// remove_filter( 'comments_open', '__return_true', 88.88 );
		echo '</div>';
	}

	public static function register_skin( Array $settings ) {
		extract( $settings );
		self::register_skin_directory( $dir );
		self::$skins[$name] = $label;
	}

}

Theme::init();
