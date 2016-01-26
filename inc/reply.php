<?php
/**
 * @package bbPressKR
 * @subpackage Reply
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 */

namespace bbPressKR;

if ( !defined('BBPKR_PATH') ) die('HACK');

class Reply {

	static $topic_list_table;

	public static function init() {
		if ( !did_action('init') )
			add_action( 'bbpkr_setup_actions', array( __CLASS__, 'setup_actions' ) );
		else
			self::setup_actions();
	}

	public static function setup_actions() {
		add_filter( 'comments_open', array( __CLASS__, 'comments_open' ), 10, 2 );
		// add_filter( 'get_comments_number', array( __CLASS__, 'get_comments_number' ), 10, 2 );
		add_filter( 'comments_template', array( __CLASS__, 'comments_template' ), 88.88 );

		// add_action( 'bbp_init', array( __CLASS__, 'remove_attachments_for_reply' ), 88.88 );

		Reply\Secret::init();
	}

	public static function comments_open($open, $post_id) {
		// if ( $open )
		// 	return $open;
		if ( get_post_type($post_id) != bbp_get_topic_post_type() )
			return $open;

		$use_comments = (bool) bbpresskr()->forum_option('use_comments', $post_id);

		if ( strpos( $_SERVER['SCRIPT_FILENAME'], 'wp-comments-post.php' ) !== false && ! empty( $_POST['comment_post_ID']) && ! empty( $_POST['comment'] ) ) {
			return $use_comments;
		}

		if ( bbp_is_single_topic() /*&& bbpress()->topic_query->in_the_loop*/ ) {
			return $use_comments;
		}

		return $open;
	}

	public static function get_comments_number($count, $post_id) {
		if ( get_post_type($post_id) != bbp_get_topic_post_type() )
			return $count;

		$open = comments_open($post_id);

		if ( !$open )
			return 0;
		return $count;
	}

	public static function comments_template($file) {
		global $post;
		if ( get_post_type($post->ID) != bbp_get_topic_post_type() )
			return $file;
		$open = comments_open($post->ID);
		if ( !$open )
			return BBPKR_PATH . '/templates/blank-comments.php';
		return $file;
	}

	public static function remove_attachments_for_reply() {
		global $gdbbpress_attachments_front;
		if ( ! is_a( $gdbbpress_attachments_front, 'gdbbAtt_Front' ) )
			return;
		remove_action('bbp_theme_before_reply_form_submit_wrapper', array(&$gdbbpress_attachments_front, 'embed_form'));
		remove_action('bbp_edit_reply', array(&$gdbbpress_attachments_front, 'save_reply'), 10, 5);
		remove_action('bbp_new_reply', array(&$gdbbpress_attachments_front, 'save_reply'), 10, 5);
		remove_filter('bbp_get_reply_content', array(&$gdbbpress_attachments_front, 'embed_attachments'), 100, 2);
	}

}

// Reply::init();
