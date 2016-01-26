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
		'media_buttons' => false,
		'textarea_rows' => '6',
		'tinymce' => true,
		'quicktags' => true,
		'teeny' => true,
		'more_html_tags' => false,
		'topic_orderby' => '',
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

	public static function has_topics_parse_args($r) {
		if ( get_option('_bbpkr_topic_order_latest') ) {
			$r['orderby'] = NULL;
			$r['meta_key'] = NULL;
		}
		return $r;
	}

	public static function editor_args($args) {
		if ( !isset($args['context']) || $args['context'] == 'topic' ) {
			/*$upload_files = current_user_can('upload_files');
			if ( !$upload_files ) {
				$upload_files = Permissions::upload_files($upload_files);
			}*/
			$settings = array(
				'media_buttons' => current_user_can('upload_files'),
				'textarea_rows' => (int) get_option('_bbpkr_textarea_rows', 20),
				'tinymce' => get_option('_bbpkr_topic_tinymce', true),
				'quicktags' => get_option('_bbpkr_topic_quicktags', true),
				'teeny' => true,
				// 'drag_drop_upload' => true,
			);
		} elseif ( isset($args['context']) && $args['context'] == 'reply' ) {
			$settings = array(
				'media_buttons' => false,
				'textarea_rows' => (int) get_option('_bbpkr_textarea_rows_reply', 6),
				'tinymce' => get_option('_bbpkr_reply_tinymce', false),
				'quicktags' => get_option('_bbpkr_reply_quicktags', false),
				'teeny' => true,
			);
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
