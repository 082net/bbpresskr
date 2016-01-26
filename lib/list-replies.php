<?php
/**
 * List Replies
 *
 * List replies like replies.
 *
 * @package bbPressKR
 * @subpackage Replies
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 *
 */

function bbpkr_list_replies( $args = array(), $replies = null ) {
	global $wp_query, $reply_alt, $reply_depth, $reply_thread_alt, $overridden_cpage, $in_reply_loop;

	$in_reply_loop = true;

	$reply_alt = $reply_thread_alt = 0;
	$reply_depth = 1;

	$defaults = array(
		'walker'            => null,
		'max_depth'         => '',
		'style'             => 'ul',
		'callback'          => null,
		'end-callback'      => null,
		'type'              => 'all',
		'page'              => '',
		'per_page'          => '',
		'avatar_size'       => 32,
		'reverse_top_level' => null,
		'reverse_children'  => '',
		'format'            => current_theme_supports( 'html5', 'reply-list' ) ? 'html5' : 'xhtml',
		'short_ping'        => false,
		'echo'              => true,
	);

	$r = wp_parse_args( $args, $defaults );

	/**
	 * Filter the arguments used in retrieving the reply list.
	 *
	 * @since 4.0.0
	 *
	 * @see wp_list_replies()
	 *
	 * @param array $r An array of arguments for displaying replies.
	 */
	$r = apply_filters( 'wp_list_replies_args', $r );

	// Figure out what replies we'll be looping through ($_replies)
	if ( null !== $replies ) {
		$replies = (array) $replies;
		if ( empty($replies) )
			return;
		if ( 'all' != $r['type'] ) {
			$replies_by_type = separate_replies($replies);
			if ( empty($replies_by_type[$r['type']]) )
				return;
			$_replies = $replies_by_type[$r['type']];
		} else {
			$_replies = $replies;
		}
	} else {
		if ( empty($wp_query->replies) )
			return;
		if ( 'all' != $r['type'] ) {
			if ( empty($wp_query->replies_by_type) )
				$wp_query->replies_by_type = separate_replies($wp_query->replies);
			if ( empty($wp_query->replies_by_type[$r['type']]) )
				return;
			$_replies = $wp_query->replies_by_type[$r['type']];
		} else {
			$_replies = $wp_query->replies;
		}
	}

	if ( '' === $r['per_page'] && get_option('page_replies') )
		$r['per_page'] = get_query_var('replies_per_page');

	if ( empty($r['per_page']) ) {
		$r['per_page'] = 0;
		$r['page'] = 0;
	}

	if ( '' === $r['max_depth'] ) {
		if ( get_option('thread_replies') )
			$r['max_depth'] = get_option('thread_replies_depth');
		else
			$r['max_depth'] = -1;
	}

	if ( '' === $r['page'] ) {
		if ( empty($overridden_cpage) ) {
			$r['page'] = get_query_var('cpage');
		} else {
			$threaded = ( -1 != $r['max_depth'] );
			$r['page'] = ( 'newest' == get_option('default_replies_page') ) ? get_reply_pages_count($_replies, $r['per_page'], $threaded) : 1;
			set_query_var( 'cpage', $r['page'] );
		}
	}
	// Validation check
	$r['page'] = intval($r['page']);
	if ( 0 == $r['page'] && 0 != $r['per_page'] )
		$r['page'] = 1;

	if ( null === $r['reverse_top_level'] )
		$r['reverse_top_level'] = ( 'desc' == get_option('reply_order') );

	if ( empty( $r['walker'] ) ) {
		$walker = new bbPressKR\Walker\reply;
	} else {
		$walker = $r['walker'];
	}

	$output = $walker->paged_walk( $_replies, $r['max_depth'], $r['page'], $r['per_page'], $r );
	$wp_query->max_num_reply_pages = $walker->max_pages;

	$in_reply_loop = false;

	if ( $r['echo'] ) {
		echo $output;
	} else {
		return $output;
	}
}
