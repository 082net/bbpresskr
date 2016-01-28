<?php
/**
 * @package bbPressKR
 * @subpackage Attachments
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 */

namespace bbPressKR;

if ( !defined('BBPKR_PATH') ) die('HACK');

class Editor {
	protected static $fired;

	protected static $op;

	protected static $defaults = array(
		'wpautop' => true,
		// 'media_buttons' => false,
		'textarea_rows' => '20',
		'tinymce' => true,
		'quicktags' => true,
		'teeny' => true,
		'more_html_tags' => false,
		'topic_orderby' => '',
	);

	protected static $reply_defaults = array(
		// 'media_buttons' => false,
		'textarea_rows' => '6',
		'tinymce' => false,
		'quicktags' => false,
		'teeny' => true,
	);

	public static function init() {
		self::setup_actions();
	}

	private static function setup_actions() {
		if ( isset(self::$fired) )
			return;
		self::$fired = true;

		add_filter( 'bbp_before_get_the_content_parse_args', array(__CLASS__, 'editor_args') );

		add_filter( 'bbp_get_topic_content', array( __CLASS__, 'do_shortcodes' ) );
		add_filter( 'bbp_get_reply_content', array( __CLASS__, 'do_shortcodes' ) );

		if ( get_option('_bbpkr_more_html_tags') )
			add_filter( 'bbp_kses_allowed_tags', array(__CLASS__, 'allowed_tags') );

	}

	static function do_shortcodes( $content ) {
		return do_shortcode( $content );
	}

	static function defaults($type='topic') {
		if ( $type == 'reply' )
			return self::$reply_defaults;
		return self::$defaults;
	}

	public static function editor_args($args) {
		if ( !isset($args['context']) || $args['context'] == 'topic' ) {
			$settings = array_merge( self::$defaults, (array) get_option('_bbpkr_topic_editor_settings') );
			$settings['media_buttons'] = current_user_can('upload_files');
		} elseif ( isset($args['context']) && $args['context'] == 'reply' ) {
			$settings = array_merge( self::$reply_defaults, (array) get_option('_bbpkr_reply_editor_settings') );
			$settings['media_buttons'] = false;
		}

		$args = array_merge($settings, $args);// do not override given arguments

		return $args;
	}

	public static function allowed_tags($tags) {
		global $allowedposttags;
		if ( is_array($allowedposttags) && !empty($allowedposttags) )
			return $allowedposttags;
		return $tags;
	}

}

// Editor::init();
