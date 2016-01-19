<?php
/**
 * @package bbPressKR
 * @subpackage permissions
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 */

namespace bbPressKR;

if ( !defined('BBPKR_PATH') ) die('HACK');

class Permissions {

	static function init() {
		add_action( 'init', array( __CLASS__, 'wp_init' ) );
		// add_filter( 'bbp_include_all_forums', '__return_true' );
		// remove_action( 'pre_get_posts', 'bbp_pre_get_posts_normalize_forum_visibility', 4 );

	}

	static function wp_init() {
		if ( bbp_is_user_keymaster() ) {
			return;
		}

		add_filter( 'bbp_allow_anonymous', array( __CLASS__, 'allow_anonymous' ) );

		add_filter( 'bbp_current_user_can_publish_replies', array( __CLASS__, 'publish_replies' ) );
		add_filter( 'bbp_current_user_can_access_create_reply_form', array( __CLASS__, 'publish_replies' ) );

		add_filter( 'bbp_current_user_can_publish_topics', array( __CLASS__, 'publish_topics' ) );
		add_filter( 'bbp_current_user_can_access_create_topic_form', array( __CLASS__, 'publish_topics' ) );

		add_filter( 'bbp_before_user_can_view_forum_parse_args', array( __CLASS__, 'view_forum_args' ) );
		add_filter( 'bbp_user_can_view_forum', array( __CLASS__, 'view_forum' ), 10, 3 );

		add_filter( 'bbp_is_forum_private', array( __CLASS__, 'bbp_is_forum_private' ), 10, 3 );
		add_filter( 'bbp_template_include_theme_compat', array( __CLASS__, 'template_no_access' ) );

		// 업로드 권한 체크는 Attachments 에서(attachments.php)

		// add_action( 'pre_get_posts', array( __CLASS__, 'include_private_forums' ), 5 );

	}

	public static function get_forum_perms( $forum_id = 0, $what = 'all' ) {
		$forum_id = bbp_get_forum_id( $forum_id );
		$perms = array();
		if ( ! $forum_id || ! get_post_meta( $forum_id, 'bbpkr_custom_perm', true ) )
			return $perms;
		$perms = get_post_meta( $forum_id, 'bbpkr_perms', true );
		if ( $what != 'all' && isset($perms[$what]) ) {
			$perms = (array) $perms[$what];
		}
		return $perms;
	}

	public static function allow_anonymous( $allow ) {
		$forum_id = bbp_get_forum_id();
		if ( !is_user_logged_in() && $forum_id ) {
			// TODO: forum specific option to allow anonymous
			// $allow = false;
			if ( bbp_is_single_forum() || bbp_is_topic_edit() ) {
				$allow = self::publish_topics( $allow );
			} elseif ( bbp_is_single_topic() || bbp_is_reply_edit() ) {
				$allow = self::publish_replies( $allow );
			}
		}
		return $allow;
	}

	public static function publish_topics($can) {
		$forum_id = bbp_get_forum_id();
		if ( ! $forum_id || ! get_post_meta( $forum_id, 'bbpkr_custom_perm', true ) )
			return $can;

		$user_id = get_current_user_id();
		if ( user_can( $user_id, 'moderate' ) ) {
			return $can;
		}

		$perms = self::get_forum_perms(0, 'write');
		$user_role = bbpresskr()->get_user_role($user_id);
		$can = in_array($user_role, $perms);

		return $can;
	}

	public static function publish_replies($can) {
		$forum_id = bbp_get_forum_id();
		if ( ! $forum_id || ! get_post_meta( $forum_id, 'bbpkr_custom_perm', true ) )
			return $can;

		$user_id = get_current_user_id();
		if ( user_can( $user_id, 'moderate' ) ) {
			return $can;
		}

		$perms = self::get_forum_perms(0, 'reply');
		$user_role = bbpresskr()->get_user_role($user_id);
		$can = in_array($user_role, $perms);

		return $can;
	}

	public static function view_forum($can, $forum_id, $user_id) {
		$forum_id = bbp_get_forum_id( $forum_id );
		if ( ! $forum_id || ! get_post_meta( $forum_id, 'bbpkr_custom_perm', true ) )
			return $can;

		if ( user_can( $user_id, 'moderate' ) ) {
			return $can;
		}

		if ( bbp_is_forum_public( $forum_id, true ) ) {
			// only filter public forums
			$perms = self::get_forum_perms(0, 'read');
			$user_role = bbpresskr()->get_user_role($user_id);
			$can = in_array($user_role, $perms);
		}

		return $can;
	}

	public static function view_forum_args($args) {
		// Set defaults to check ancestors of the forum
		if ( !isset( $args['check_ancestors'] ) ) {
			$args['check_ancestors'] = true;
		}

		return $args;
	}

	public static function bbp_is_forum_private( $retval, $forum_id, $check_ancestors ) {
		if ( $retval /*|| bbp_is_user_keymaster() || current_user_can( 'moderate' )*/ ) {
			return $retval;
		}
		return $retval;

		// only filter public forums
		$perms = self::get_forum_perms($forum_id, 'read');
		$user_id = get_current_user_id();
		$user_role = bbpresskr()->get_user_role($user_id);
		$retval = ! in_array($user_role, $perms);

		return $retval;
	}

	public static function include_private_forums($posts_query) {
		// Bail if $posts_query is not an object or of incorrect class
		if ( !is_object( $posts_query ) || !is_a( $posts_query, 'WP_Query' ) ) {
			return;
		}

		// Get query post types array .
		$post_types = (array) $posts_query->get( 'post_type' );

		// Forums
		if ( bbp_get_forum_post_type() === implode( '', $post_types ) ) {

			// Prevent accidental wp-admin post_row override
			if ( is_admin() && isset( $_REQUEST['post_status'] ) ) {
				return;
			}

			/** Default ***********************************************************/

			// Get any existing post status
			$post_stati = (array) $posts_query->get( 'post_status' );
			if ( !in_array( bbp_get_private_status_id(), $post_stati) ) {
				$post_stati[] = bbp_get_private_status_id();
				// Add the statuses
				$posts_query->set( 'post_status', array_unique( array_filter( $post_stati ) ) );
			}
		}
	}

	public static function template_no_access($template) {
		global $wp_query, $post;
		$check_perm = false;
		if ( bbp_is_single_user_edit() || bbp_is_single_user() ) {
		} elseif ( bbp_is_forum_archive() ) {
		} elseif ( bbp_is_forum_edit() ) {
			$forum_id = bbp_get_forum_id();
			if ( bbp_is_forum( $forum_id ) && !bbp_user_can_view_forum( array( 'forum_id' => $forum_id ) ) && !bbp_is_forum_private( $forum_id, false ) )
				$check_perm = true;
		} elseif ( bbp_is_single_forum() ) {
			$forum_id = bbp_get_forum_id();
			if ( bbp_is_forum( $forum_id ) && !bbp_user_can_view_forum( array( 'forum_id' => $forum_id ) ) && !bbp_is_forum_private( $forum_id, false ) )
				$check_perm = true;
		} elseif ( bbp_is_topic_archive() ) {
		} elseif ( bbp_is_topic_edit() || bbp_is_single_topic() ) {
			$check_perm = true;
		} elseif ( is_post_type_archive( bbp_get_reply_post_type() ) ) {
		} elseif ( bbp_is_reply_edit() || bbp_is_single_reply() ) {
			$check_perm = true;
		} elseif ( bbp_is_single_view() ) {
		} elseif ( bbp_is_search() ) {
		} elseif ( bbp_is_topic_tag_edit() || bbp_is_topic_tag() ) {
		}

		if ( $check_perm && empty($post->post_content) ) {
			$user_id = get_current_user_id();
			$forum_id = bbp_get_forum_id();
			if ( ! self::view_forum( false, $forum_id, $user_id ) ) {
				ob_start();
				bbp_get_template_part( 'feedback', 'no-access' );
				$content = ob_get_clean();
				$post->post_content = "\n{$content}\n";
				$wp_query->post       = $post;
				$wp_query->posts      = array( $post );
			}
		}

		return $template;
	}

}

Permissions::init();
