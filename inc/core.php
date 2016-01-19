<?php
/**
 * @package
 * @subpackage core
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 */

namespace bbPressKR;

if ( !defined('BBPKR_PATH') ) die('HACK');

class Core {

	var $write_id;

	var $base_url;

	var $base_path;

	var $topic_list_table;

	var $ver = '1.0.20141218';

	var $defalut_op = array(
		'wpautop' => true,
		'media_buttons' => false,
		'textarea_rows' => '6',
		'tinymce' => true,
		'quicktags' => true,
		'teeny' => true,
		'more_html_tags' => false,
		'topic_orderby' => '',
		);

	var $meta;

	function __construct() {}

	public static function instance() {

		// Store the instance locally to avoid private static replication
		static $instance = null;

		// Only run these methods if they haven't been ran previously
		if ( null === $instance ) {
			$instance = new Core;
			// $instance->load();
			add_action( 'plugins_loaded', array( &$instance, 'load' ) );
		}

		// Always return the instance
		return $instance;
	}

	public function load() {
		// load plugin only if bbPress plugin activated
		if ( function_exists('bbpress') ) {
			$this->setup_globals();
			$this->includes();
			$this->setup_actions();
		} else {
			add_action( 'admin_notices', array(__CLASS__, 'need_bbpress') );
		}
	}

	static function need_bbpress() {
		echo '<div class="error"><p>' . __('We need bbPress to activate bbPressKR', 'bbpresskr') . '</p></div>';
	}

	private function setup_globals() {
		$this->write_id = bbpress()->write_id = apply_filters( 'bbp_write_id',   'write' );
		$this->path = BBPKR_PATH;
		$this->url = wp_rel_url('', dirname(__FILE__));
	}

	private function includes() {
		require( BBPKR_INC . '/theme.php' );
		require( BBPKR_INC . '/forum.php' );
		require( BBPKR_INC . '/topic.php' );
		require( BBPKR_INC . '/reply.php' );

		require( BBPKR_INC . '/editor.php' );
		require( BBPKR_INC . '/attachments.php' );

		require( BBPKR_INC . '/private.php' );
		require( BBPKR_INC . '/permissions.php' );
		require( BBPKR_INC . '/forum-roles.php' );
		require( BBPKR_INC . '/meta.php' );

		// require( BBPKR_LIB . '/meta.php' );
		require( BBPKR_LIB . '/view.php' );

		if ( is_admin() )
			require_once( BBPKR_PATH . '/admin/init.php' );

		/*if ( !defined('GDBBPRESSATTACHMENTS_PATH' ) ) {
			require_once( BBPKR_LIB . '/gd-bbpress-attachments/gd-bbpress-attachments.php');
		}*/
	}

	private function setup_actions() {
		$actions = array(
			// 'setup_theme',              // Setup the default theme compat
			// 'setup_current_user',       // Setup currently logged in user
			// 'register_post_types',      // Register post types (forum|topic|reply)
			// 'register_post_statuses',   // Register post statuses (closed|spam|orphan|hidden)
			// 'register_taxonomies',      // Register taxonomies (topic-tag)
			// 'register_shortcodes',      // Register shortcodes (bbp-login)
			// 'register_views',           // Register the views (no-replies)
			// 'register_theme_packages',  // Register bundled theme packages (bbp-theme-compat/bbp-themes)
			// 'load_textdomain',          // Load textdomain (bbpress)
			'add_rewrite_tags',         // Add rewrite tags (view|user|edit|search)
			'add_rewrite_rules',        // Generate rewrite rules (view|edit|paged|search)
			// 'add_permastructs'          // Add permalink structures (view|user|search)
		);

		// Add the actions
		foreach ( $actions as $class_action )
			add_action( 'bbp_' . $class_action, array( $this, $class_action ), 7 );


		add_action( 'wp_enqueue_scripts', array( &$this, 'wp_enqueue_styles' ), 11 );
		add_action( 'wp_enqueue_scripts', array( &$this, 'wp_enqueue_scripts' ), 11 );

		add_filter( 'bbp_default_styles', array( &$this, 'bbp_default_styles') );

		add_filter( 'bbp_body_class', array( &$this, 'body_class' ), 10, 2 );

		do_action( 'bbpkr_setup_actions' );
	}

	function option($name = '', $group = 'default', $default=null) {
		$options = $this->options();
		if ( isset( $options[$name] ) )
			return $options[$name];
		return $default;
	}

	function options() {
		$defaults = array( 'date_format' => 'Y.m.d', 'time_format' => 'H:i', 'posts_per_page' => get_option('posts_per_page'), 'skin' => 'default', 'use_comments' => false, 'page_title_selector' => '' );
		$options = array_merge( $defaults, get_option( 'bbpkr_options', array()) );
		return $options;
	}

	function forum_options($forum_id=0, $admin=false) {
		return Forum::options($forum_id, $admin);
	}

	function forum_option($option, $forum_id=0) {
		return Forum::option( $option, $forum_id );
	}

	function get_user_roles() {
		$bbp_roles = bbp_get_dynamic_roles();
		$keymaster = bbp_get_keymaster_role();
		$moderator = bbp_get_moderator_role();
		$blocked = bbp_get_blocked_role();

		// double check if the role exists
		if ( isset( $bbp_roles[$keymaster] ) )
			unset( $bbp_roles[$keymaster] );
		if ( isset( $bbp_roles[$moderator] ) )
			unset( $bbp_roles[$moderator] );
		if ( isset( $bbp_roles[$blocked] ) )
			unset( $bbp_roles[$blocked] );

		$bbp_roles['bbpkr_anonymous'] = array(
			'name' => __('Anonymous', 'bbpresskr'),
			'caps' => array( 'spectate' => true ),
			);

		return $bbp_roles;
	}

	function get_user_role( $user_id = 0 ) {
		static $roles = array();
		if ( !$user_id && is_user_logged_in() ) {
			$user_id = get_current_user_id();
		}
		if ( !$user_id ) {
			return 'bbpkr_anonymous';
		}
		if ( isset($roles[$user_id]) )
			return $roles[$user_id];

		$user_role = bbp_get_user_role( $user_id );

		return $user_role;
	}

	public static function add_rewrite_rules() {
		$priority = 'top';

		$forum_slug = bbp_get_forum_slug();
		$write_id = bbp_get_write_rewrite_id();

		$write_slug = 'write';

		$write_rule    = '/(.+?)/' . $write_slug  . '/?$';

		add_rewrite_rule( $forum_slug . $write_rule, 'index.php?' . bbp_get_forum_post_type()  . '=$matches[1]&' . $write_id . '=1', $priority );
	}

	public static function add_rewrite_tags() {
		add_rewrite_tag( '%' . bbp_get_write_rewrite_id()               . '%', '([1]{1,})' ); // Edit Page tag
	}

	public function after_setup_theme() {
	}

	public static function meta_params($forum_id, $admin=false) {
		if ( ! bbp_is_forum($forum_id) )
			return array();

		$meta_params = get_post_meta($forum_id, 'bbpmeta_params', false);
		if ( empty($meta_params) )
			return array();
		uasort( $meta_params, array(__CLASS__, 'meta_order') );
		return $meta_params;
		return self::bbpmeta_order($forum_id, $meta_params);
	}

	static function meta_order($a, $b) {
		return strcmp($a['order'], $b['order']);
	}

	public static function bbpmeta_order($forum_id, $meta) {
		if ( $order = get_post_meta( $forum_id, 'bbpmeta_order', true ) ) {
			$_meta = array();
			$j = 1000;
			foreach ( $meta as $m ) {
				if ( false !== $i = array_search($m['key'], $order) ) {
					$_meta[$i] = $m;
				} else {
					$j++;
					$_meta[$j] = $m;
				}
			}
			$meta = $_meta;
			ksort($meta);
		}
		return $meta;
	}

	public function wp_enqueue_styles() {
		if ( wp_style_is( 'fontawesome' ) )
			$fontawesome = 'fontawesome';
		elseif ( wp_style_is( 'font-awesome' ) )
			$fontawesome = 'font-awesome';
		else {
			wp_register_style( 'font-awesome', $this->url . '/assets/font-awesome/css/font-awesome.min.css', array(), '4.3' );
			$fontawesome = 'font-awesome';
		}

		wp_enqueue_style( $fontawesome );

		if ( wp_style_is( 'genericons' ) )
			wp_enqueue_style( 'genericons' );
		else
			wp_enqueue_style( 'genericons', $this->url . '/assets/genericons/genericons.css', array(), '3.2' );
		// wp_enqueue_style( 'bbpresskr', )
	}

	public function wp_enqueue_scripts() {

	}

	public function bbp_default_styles( $styles ) {
		$styles['bbp-default'] = array(
			// 'file'         => 'css/bbpresskr.css',
			'file'         => 'css/default.css',
			'dependencies' => array('buttons')//array( 'bbp-default' )
		);
		return $styles;
	}

	public static function body_class( $classes, $bbp_classes ) {
		if ( in_array('bbpress', $bbp_classes) ) {
			$classes[] = 'bbpresskr';
		}
		return $classes;
	}

}

