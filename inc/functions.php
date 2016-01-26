<?php
/**
 * bbPressKR Functions
 * 
 * @package bbPressKR
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 * 
 */
if ( !defined('BBPKR_PATH') ) die('HACK');


function bbpresskr() {
	return bbPressKR\Core::instance();
}

function bbp_get_write_rewrite_id() {
	return bbpress()->write_id;
}

function bbp_is_topic_write() {
	global $wp_query, $pagenow;

	// Assume false
	$retval = false;

	// Check query
	if ( !empty( $wp_query->bbp_is_topic_write ) && ( $wp_query->bbp_is_topic_write === true ) )
		$retval = true;

	return (bool) apply_filters( 'bbp_is_topic_write', $retval );
}

function bbp_get_topic_write_template() {
	$templates = array(
		'single-' . bbp_get_topic_post_type() . '-write.php' // Single Topic Edit
	);
	return bbp_get_query_template( 'topic_write', $templates );
}

function bbp_topic_write_link( $args = '' ) {
	echo bbp_get_topic_write_link( $args );
}

function bbp_get_topic_write_link( $args = '' ) {

	// Parse arguments against default values
	$r = bbp_parse_args( $args, array(
		'id'           => 0,
		'link_before'  => '',
		'link_after'   => '',
		'write_text'    => esc_html__( 'Write', 'bbpress' )
	), 'get_topic_write_link' );

	if ( !bbp_current_user_can_access_create_topic_form() )
		return;

	// Get uri
	$uri = bbp_get_topic_write_url( $r['id'] );

	// Bail if no uri
	if ( empty( $uri ) )
		return;

	$retval = $r['link_before'] . '<a href="' . esc_url( $uri ) . '" class="bbp-topic-write-link">' . $r['write_text'] . '</a>' . $r['link_after'];

	return apply_filters( 'bbp_get_topic_write_link', $retval, $r );
}

function bbp_topic_write_url( $topic_id = 0 ) {
	echo esc_url( bbp_get_topic_write_url( $forum_id ) );
}

	function bbp_get_topic_write_url( $forum_id = 0 ) {
		global $wp_rewrite;

		$bbp = bbpress();

		$forum = bbp_get_forum( bbp_get_forum_id( $forum_id ) );
		if ( empty( $forum ) )
			return;

		// Remove view=all link from edit
		$forum_link = bbp_get_forum_permalink( $forum_id );

		// Pretty permalinks
		if ( $wp_rewrite->using_permalinks() ) {
			$url = trailingslashit( $forum_link ) . $bbp->write_id;
			$url = trailingslashit( $url );

		// Unpretty permalinks
		} else {
			$url = add_query_arg( array( bbp_get_forum_post_type() => $forum->post_name, $bbp->write_id => '1' ), $forum_link );
		}

		return apply_filters( 'bbp_get_topic_write_url', $url, $forum_id );
	}


function bbp_the_no( $topic_id=0 ) {
	echo bbp_get_the_no( $topic_id );
}

function bbp_get_the_no( $topic_id=0 ) {
	$q = bbpress()->topic_query;
	$total = (int) $q->found_posts;
	$max_pages = $q->max_num_pages;
	$per_page = $q->get('posts_per_page');
	$paged = $q->get('paged') - 1;
	$no = $total - ( $paged * $per_page ) - $q->current_post;
	return $no;
}

function bbp_user_can_comment() {
	return bbpresskr()->forum_option('use_comments') && bbp_current_user_can_access_create_reply_form()			
	&& ! bbp_is_topic_closed()
	&& ! bbp_is_forum_closed();
}

function bbpkr_get_cancel_reply_link( $text = '' ) {
	if ( empty($text) )
		$text = __('Click here to cancel reply.', 'bbpresskr');

	$style = isset($_GET['bbp_reply_to']) ? '' : ' style="display:none;"';
	$link = esc_html( remove_query_arg('bbp_reply_to') ) . '#bbpkr-respond';

	$formatted_link = '<a rel="nofollow" id="bbpkr-cancel-eply-link" href="' . $link . '"' . $style . '>' . $text . '</a>';
	return apply_filters( 'bbpkr_cancel_reply_link', $formatted_link, $link, $text );
}

function bbpkr_cancel_reply_link( $text = '' ) {
	echo bbpkr_get_cancel_reply_link($text);
}

function bbpkr_get_reply_link( $args = array(), $comment = null, $post = null ) {

	$defaults = array(
		'add_below'     => 'comment',
		'respond_id'    => 'respond',
		'reply_text'    => __( 'Reply' ),
		'reply_to_text' => __( 'Reply to %s' ),
		'login_text'    => __( 'Log in to Reply' ),
		'depth'         => 0,
		'before'        => '',
		'after'         => ''
	);

	$args = wp_parse_args( $args, $defaults );

	if ( 0 == $args['depth'] || $args['max_depth'] <= $args['depth'] ) {
		return;
	}

	$comment = get_comment( $comment );

	if ( empty( $post ) ) {
		$post = $comment->comment_post_ID;
	}

	$post = get_post( $post );

	if ( ! comments_open( $post->ID ) ) {
		return false;
	}

	$args = apply_filters( 'comment_reply_link_args', $args, $comment, $post );

	if ( get_option( 'comment_registration' ) && ! is_user_logged_in() ) {
		$link = sprintf( '<a rel="nofollow" class="comment-reply-login" href="%s">%s</a>',
			esc_url( wp_login_url( get_permalink() ) ),
			$args['login_text']
		);
	} else {
		$onclick = sprintf( 'return addComment.moveForm( "%1$s-%2$s", "%2$s", "%3$s", "%4$s" )',
			$args['add_below'], $comment->comment_ID, $args['respond_id'], $post->ID
		);

		$link = sprintf( "<a class='comment-reply-link' href='%s' onclick='%s' aria-label='%s'>%s</a>",
			esc_url( add_query_arg( 'replytocom', $comment->comment_ID ) ) . "#" . $args['respond_id'],
			$onclick,
			esc_attr( sprintf( $args['reply_to_text'], $comment->comment_author ) ),
			$args['reply_text']
		);
	}
	return apply_filters( 'comment_reply_link', $args['before'] . $link . $args['after'], $args, $comment, $post );
}

function bbpkr_reply_link($args = array(), $comment = null, $post = null) {
	echo bbpkr_get_reply_link($args, $comment, $post);
}

function bbpkr_reply_form( $args = array(), $post_id = null ) {
	if ( null === $post_id )
		$post_id = get_the_ID();

	$commenter = wp_get_current_commenter();
	$user = wp_get_current_user();
	$user_identity = $user->exists() ? $user->display_name : '';

	$args = wp_parse_args( $args );
	if ( ! isset( $args['format'] ) )
		$args['format'] = current_theme_supports( 'html5', 'comment-form' ) ? 'html5' : 'xhtml';

	$req      = get_option( 'require_name_email' );
	$aria_req = ( $req ? " aria-required='true'" : '' );
	$html5    = 'html5' === $args['format'];
	$fields   =  array(
		'author' => '<p class="comment-form-author">' . '<label for="author">' . __( 'Name' ) . ( $req ? ' <span class="required">*</span>' : '' ) . '</label> ' .
		            '<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '" size="30"' . $aria_req . ' /></p>',
		'email'  => '<p class="comment-form-email"><label for="email">' . __( 'Email' ) . ( $req ? ' <span class="required">*</span>' : '' ) . '</label> ' .
		            '<input id="email" name="email" ' . ( $html5 ? 'type="email"' : 'type="text"' ) . ' value="' . esc_attr(  $commenter['comment_author_email'] ) . '" size="30" aria-describedby="email-notes"' . $aria_req . ' /></p>',
		'url'    => '<p class="comment-form-url"><label for="url">' . __( 'Website' ) . '</label> ' .
		            '<input id="url" name="url" ' . ( $html5 ? 'type="url"' : 'type="text"' ) . ' value="' . esc_attr( $commenter['comment_author_url'] ) . '" size="30" /></p>',
	);

	$required_text = sprintf( ' ' . __('Required fields are marked %s'), '<span class="required">*</span>' );

	$fields = apply_filters( 'comment_form_default_fields', $fields );
	$defaults = array(
		'fields'               => $fields,
		'comment_field'        => '<p class="comment-form-comment"><label for="comment">' . _x( 'Comment', 'noun' ) . '</label> <textarea id="comment" name="comment" cols="45" rows="8" aria-describedby="form-allowed-tags" aria-required="true"></textarea></p>',
		/** This filter is documented in wp-includes/link-template.php */
		'must_log_in'          => '<p class="must-log-in">' . sprintf( __( 'You must be <a href="%s">logged in</a> to post a comment.' ), wp_login_url( apply_filters( 'the_permalink', get_permalink( $post_id ) ) ) ) . '</p>',
		/** This filter is documented in wp-includes/link-template.php */
		'logged_in_as'         => '<p class="logged-in-as">' . sprintf( __( 'Logged in as <a href="%1$s">%2$s</a>. <a href="%3$s" title="Log out of this account">Log out?</a>' ), get_edit_user_link(), $user_identity, wp_logout_url( apply_filters( 'the_permalink', get_permalink( $post_id ) ) ) ) . '</p>',
		'comment_notes_before' => '<p class="comment-notes"><span id="email-notes">' . __( 'Your email address will not be published.' ) . '</span>'. ( $req ? $required_text : '' ) . '</p>',
		'comment_notes_after'  => '<p class="form-allowed-tags" id="form-allowed-tags">' . sprintf( __( 'You may use these <abbr title="HyperText Markup Language">HTML</abbr> tags and attributes: %s' ), ' <code>' . allowed_tags() . '</code>' ) . '</p>',
		'id_form'              => 'commentform',
		'id_submit'            => 'submit',
		'class_submit'         => 'submit',
		'name_submit'          => 'submit',
		'title_reply'          => __( 'Leave a Reply' ),
		'title_reply_to'       => __( 'Leave a Reply to %s' ),
		'cancel_reply_link'    => __( 'Cancel reply' ),
		'label_submit'         => __( 'Post Comment' ),
		'format'               => 'xhtml',
	);

	$args = wp_parse_args( $args, apply_filters( 'comment_form_defaults', $defaults ) );

	?>
		<?php if ( comments_open( $post_id ) ) : ?>
			<?php
			/**
			 * Fires before the comment form.
			 *
			 * @since 3.0.0
			 */
			do_action( 'comment_form_before' );
			?>
			<div id="respond" class="comment-respond">
				<h3 id="reply-title" class="comment-reply-title"><?php comment_form_title( $args['title_reply'], $args['title_reply_to'] ); ?> <small><?php cancel_comment_reply_link( $args['cancel_reply_link'] ); ?></small></h3>
				<?php if ( get_option( 'comment_registration' ) && !is_user_logged_in() ) : ?>
					<?php echo $args['must_log_in']; ?>
					<?php
					do_action( 'comment_form_must_log_in_after' );
					?>
				<?php else : ?>
					<form action="<?php echo site_url( '/wp-comments-post.php' ); ?>" method="post" id="<?php echo esc_attr( $args['id_form'] ); ?>" class="comment-form"<?php echo $html5 ? ' novalidate' : ''; ?>>
						<?php
						do_action( 'comment_form_top' );
						?>
						<?php if ( is_user_logged_in() ) : ?>
							<?php
							echo apply_filters( 'comment_form_logged_in', $args['logged_in_as'], $commenter, $user_identity );
							?>
							<?php
							do_action( 'comment_form_logged_in_after', $commenter, $user_identity );
							?>
						<?php else : ?>
							<?php echo $args['comment_notes_before']; ?>
							<?php
							do_action( 'comment_form_before_fields' );
							foreach ( (array) $args['fields'] as $name => $field ) {
								echo apply_filters( "comment_form_field_{$name}", $field ) . "\n";
							}
							do_action( 'comment_form_after_fields' );
							?>
						<?php endif; ?>
						<?php
						echo apply_filters( 'comment_form_field_comment', $args['comment_field'] );
						?>
						<?php echo $args['comment_notes_after']; ?>
						<p class="form-submit">
							<input name="<?php echo esc_attr( $args['name_submit'] ); ?>" type="submit" id="<?php echo esc_attr( $args['id_submit'] ); ?>" class="<?php echo esc_attr( $args['class_submit'] ); ?>" value="<?php echo esc_attr( $args['label_submit'] ); ?>" />
							<?php comment_id_fields( $post_id ); ?>
						</p>
						<?php
						do_action( 'comment_form', $post_id );
						?>
					</form>
				<?php endif; ?>
			</div><!-- #respond -->
			<?php
			do_action( 'comment_form_after' );
		else :
			do_action( 'comment_form_comments_closed' );
		endif;
}

function bbp_topic_forum_link() {
	echo bbp_get_topic_forum_link();
}

function bbp_get_topic_forum_link( $args = '' ) {
	// Parse arguments against default values
	$r = bbp_parse_args( $args, array(
		'id'           => 0,
		'link_before'  => '',
		'link_after'   => '',
		'edit_text'    => esc_html__( 'List', 'bbpress' )
	), 'get_topic_forum_link' );

	// Get uri
	$uri = bbp_get_forum_permalink( bbp_get_topic_forum_id($r['id']) );

	// Bail if no uri
	if ( empty( $uri ) )
		return;

	$retval = $r['link_before'] . '<a href="' . esc_url( $uri ) . '" class="bbp-topic-forum-link">' . $r['edit_text'] . '</a>' . $r['link_after'];

	return apply_filters( 'bbp_get_topic_forum_link', $retval, $r );
}

/**
 * Topic View Counter
 **************************************************/

function bbpkr_topic_views( $topic_id = 0 ) {
	echo bbpkr_get_topic_views( $topic_id );
}

function bbpkr_get_topic_views( $topic_id = 0 ) {
	return bbPressKR\Topic\Views_Counter::get_views( $topic_id );
}

/**
 * Attachments
 **************************************************/

function bbpkr_topic_has_attachments($topic_id = 0) {
  $topic_id = bbp_get_topic_id();
  if ( $topic_id )
    return get_post_meta( $topic_id, 'attached_files', true );
  return array();
}

function bbpkr_reply_has_attachments($reply_id = 0) {
  $reply_id = bbp_get_reply_id();
  if ( $reply_id )
    return get_post_meta( $reply_id, 'attached_files', true );
  return array();
}

function bbpkr_embed_attachments($post_id = 0) {
  echo bbPressKR\Attachments::embed_attachments($post_id);
}


/**
 * Meta
 **************************************************/

function bbpkr_has_meta( $forum_id = 0 ) {
	return bbPressKR\Meta::has_meta( $forum_id );
}

function bbpkr_meta_params( $forum_id = 0 ) {
	return bbPressKR\Meta::meta_params( $forum_id );
}

function bbpkr_meta_fields( $forum_id = 0, $args = '' ) {
	return bbPressKR\Meta::meta_fields( $forum_id, $args );
}
