<?php
/**
 * @package bbPressKR
 * @subpackage Forum
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 */

namespace bbPressKR;

if ( !defined('BBPKR_PATH') ) die('HACK');

class Forum {

	static $defaults = array( 'moderators' => array() );

	function __construct() {

	}

	static function init() {
	}

	static function options($forum_id=0, $admin=false) {
		$forum_id = bbp_get_forum_id($forum_id);
		if ( $cached = wp_cache_get($forum_id, 'forum_options') )
			return $cached;
		$forum_options = self::$defaults;
		if ( get_post_meta( $forum_id, 'bbpkr_custom_settings', true ) ) {
			$forum_options = array_merge( $forum_options, (array) get_post_meta( $forum_id, 'bbpkr_options', true ) );
			// var_dump( $this->options()['skin'], $options);
		} elseif ( !$admin ) {
			// follow closest parent forum custom settings
			$forum_parent = (int) get_post_field( 'post_parent', $forum_id );
			if ( $forum_parent ): while ( $forum_parent ):
			if ( get_post_meta( $forum_parent, 'bbpkr_custom_settings', true ) ) {
				$forum_options = array_merge( $forum_options, (array) get_post_meta( $forum_parent, 'bbpkr_options', true ) );
				break;
			}
			$forum_parent = (int) get_post_field( 'post_parent', $forum_parent );
			endwhile; endif;
		}
		$return = array_merge( bbpresskr()->options(), (array) $forum_options );
		wp_cache_set( $forum_id, $return, 'forum_options' );
		return $return;
	}

	static function option($option, $forum_id=0) {
		$options = self::options($forum_id);
		return isset( $options[$option] ) ? $options[$option] : null;
	}

	static function perms( $forum_id = 0 ) {
		return Permissions::get_forum_perms( $forum_id, 'all' );
	}

	static function perm( $forum_id, $what ) {
		return Permissions::get_forum_perms( $forum_id, $what );
	}

	function setup_forumdata($post, $query) {
		$post->forum_options = self::options($post->ID);
		$post->forum_pers = self::perms($post->ID);
	}

}

// Forum::init();
