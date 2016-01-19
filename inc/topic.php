<?php
/**
 * @package bbPressKR
 * @subpackage Theme Compat
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 */

namespace bbPressKR;

if ( !defined('BBPKR_PATH') ) die('HACK');

class Topic {

	public static function init() {
		add_action( 'bbpkr_setup_actions', array( __CLASS__, 'setup_actions' ) );
		require( BBPKR_INC . '/topic-views-counter.php' );
		Topic\Views_Counter::init();
	}

	public static function setup_actions() {
		add_action( 'parse_query', array( __CLASS__, 'parse_query' ), 3 ); // Early for overrides

		// add_post_type_support( bbp_get_forum_post_type(), 'custom-fields' );
		add_action( 'save_post_' . bbp_get_topic_post_type(), array( __CLASS__, 'set_topic_no' ), 10, 2 );

		// setup topic list table
		add_filter( 'bbp_after_has_topics_parse_args', array( __CLASS__, 'setup_topic_list_table' ), 3 );

		add_filter( 'bbp_show_lead_topic', '__return_true' );

		// append forum list link to topic admin links
		add_filter( 'bbp_get_topic_admin_links', array( __CLASS__, 'topic_admin_links' ), 10, 3 );

		add_filter( 'bbp_before_get_user_subscribe_link_parse_args', array( __CLASS__, 'user_subscribe_link_parse_args' ) );

		add_action( 'bbp_init', array( __CLASS__, 'remove_attachments_embed' ), 88.88 );

	}

	public static function parse_query( $posts_query ) {

		// Bail if $posts_query is not the main loop
		if ( ! $posts_query->is_main_query() )
			return;

		// Bail if filters are suppressed on this query
		if ( true === $posts_query->get( 'suppress_filters' ) )
			return;

		// Bail if in admin
		if ( is_admin() )
			return;

		// Get query variables
		$is_write  = $posts_query->get( bbp_get_write_rewrite_id() );

		// Write?
		if ( !empty( $is_write ) ) {

			// Get the post type from the main query loop
			$post_type = $posts_query->get( 'post_type' );

			// Check which post_type we are editing, if any
			if ( !empty( $post_type ) ) {
				switch( $post_type ) {
					// We are editing a forum
					case bbp_get_forum_post_type() :
						$posts_query->bbp_is_topic_write = true;
						$posts_query->bbp_is_write       = true;
						break;
				}
			}
		}
	}

	public static function setup_topic_list_table( $r ) {
		if ( is_main_query() && !isset(bbpresskr()->topic_list_table) /*&& is_numeric($r['post_parent'])*/ ) {
			require_once( BBPKR_LIB . '/list-table.php' );
			bbpresskr()->topic_list_table = Topic\List_Table::instance( $r );
			return bbpresskr()->topic_list_table->_topic_args;
		}
		return $r;
	}

	public static function set_topic_no($post_ID, $post) {
		global $wpdb;
		if ( $post->menu_order > 0 || $post->post_status != 'publish' )
			return;

		$forum_id = bbp_get_topic_forum_id($post_ID);
		$nextNo = self::get_next_topic_no($forum_id);
		if ( $nextNo )
			$wpdb->update( $wpdb->posts, array('menu_order' => $nextNo), array('ID' => $post_ID) );
	}

	public static function get_next_topic_no( $forum_id=0 ) {
		global $wpdb;
		$forum_id = bbp_get_forum_id( $forum_id );
		if ( !$forum_id )
			return false;

		$nextQ = $wpdb->prepare("SELECT MAX(menu_order) FROM $wpdb->posts WHERE post_type LIKE %s AND post_parent=%d", bbp_get_topic_post_type(), $forum_id);
		$nextNo = (int) $wpdb->get_var($nextQ);
		return $nextNo + 1;
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
			return BBPKR_PATH . '/inc/blank-comments.php';
		return $file;
	}

	public static function remove_attachments_embed() {
		global $gdbbpress_attachments_front;
		if ( ! is_a( $gdbbpress_attachments_front, 'gdbbAtt_Front' ) )
			return;

		remove_filter('bbp_get_topic_content', array(&$gdbbpress_attachments_front, 'embed_attachments'), 100, 2);
	}

	public static function topic_admin_links( $retval, $r, $args ) {
		// $retval = $r['before'] . $links . $r['after'];
		if ( !empty( $args['links'] ) )
			return $retval;

		if ( !empty( $r['after'] ) ) {
			$retval = preg_replace( '!' . preg_quote($r['after']) . '$!', '', $retval );
		}
		$retval .= $r['sep'] . bbp_get_topic_forum_link( $r );
		if ( !empty( $r['after'] ) ) {
			$retval .= $r['after'];
		}

		return $retval;
	}

	public static function user_subscribe_link_parse_args($args) {
		$args['before'] = '';
		return $args;
	}

	public static function topic_top() {
		// bbp_get_template_part();
	}

	public static function favorites_counts($topic_id=0) {
		global $wpdb;
		if ( !$topic_id = bbp_get_topic_id() ) {
			return $topic_id;
		}

		$key = $wpdb->get_blog_prefix() . '_bbp_favorites';
		$query = $wpdb->prepare("SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_key LIKE %s", $key);

		$where = [];
		$where[] = $wpdb->prepare("meta_value LIKE %s", $topic_id);
		$where[] = $wpdb->prepare("meta_value LIKE %s", '%%,' . $topic_id . ',%%' );
		$where[] = $wpdb->prepare("meta_value LIKE %s", $topic_id . ',%%' );
		$where[] = $wpdb->prepare("meta_value LIKE %s", '%%,' . $topic_id );

		$query .= "AND (" . implode(" OR ", $where) . ")";

		$counts = (int) $wpdb->get_var($query);

		return $counts;
	}

}

Topic::init();
