<?php
/**
 * Count topic views, not forum/reply
 * @package bbPressKR
 * @subpackage bbPressKR_Topic_Views_Counter
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 */

namespace bbPressKR\Topic;

if ( !defined('BBPKR_PATH') ) die('HACK');

class Views_Counter {

	function __construct() {}

	static function init() {
		self::setup_actions();
	}

	private static function setup_actions() {
		add_action( 'bbp_template_after_single_topic' , array(__CLASS__, 'update_views'), 99 );
	}

	static function update_views() {
		if ( bbp_is_single_topic() && ($topic_id = bbp_get_topic_id()) ) {
			$count = self::get_views( $topic_id );
			$count++;
			update_post_meta( $topic_id, 'topic_view_count', $count );
		}
	}

	static function get_views( $topic_id = 0) {
		if ( ! $topic_id = bbp_get_topic_id( $topic_id ) )
			return 0;
		return (int) get_post_meta( $topic_id, 'topic_view_count', true );
	}

}

