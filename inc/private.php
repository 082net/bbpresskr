<?php
/**
 * @package bbPressKR
 * @subpackage private / protected
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 */
/**
 * @poweredby		bbPress - Private Replies (http://pippinsplugins.com/bbpress-private-replies)
 * Pippin Williamson and Remi Corson (URI: http://pippinsplugins.com)
 */

namespace bbPressKR;

if ( !defined('BBPKR_PATH') ) die('HACK');

class PrivateReply {

	static function init() {
		self::setup_actions();
	}

	static function setup_actions() {
		// add_filter( 'bbp_before_has_topics_parse_args', array(__CLASS__, 'has_topics_args') );

		// Prefix title
		add_filter( 'protected_title_format', array(__CLASS__, 'protected_title_format'), 100, 2 );
		add_filter( 'private_title_format', array(__CLASS__, 'private_title_format'), 100, 2 );

		add_action( 'bbp_theme_before_topic_title', array(__CLASS__, 'bbp_theme_before_topic_title') );


		add_action( 'bbp_theme_before_reply_form_submit_wrapper', array(__CLASS__, 'checkbox') );

		// save the private reply state
		add_action( 'bbp_new_reply',  array(__CLASS__, 'update_reply'), 0, 6 );
		add_action( 'bbp_edit_reply',  array(__CLASS__, 'update_reply'), 0, 6 );

		// hide reply content
		add_filter( 'bbp_get_reply_excerpt', array(__CLASS__, 'hide_reply'), 999, 2 );
		add_filter( 'bbp_get_reply_content', array(__CLASS__, 'hide_reply'), 999, 2 );
		add_filter( 'the_content', array(__CLASS__, 'hide_reply'), 999 );
		add_filter( 'the_excerpt', array(__CLASS__, 'hide_reply'), 999 );

		// prevent private replies from being sent in email subscriptions
		add_filter( 'bbp_subscription_mail_message', array(__CLASS__, 'prevent_subscription_email'), 999999, 3 );

		// add a class name indicating the read status
		add_filter( 'post_class', array(__CLASS__, 'reply_post_class') );

	}

	static function private_title_format($format, $post) {
		if ( in_array( get_post_type($post), array( bbp_get_topic_post_type(), bbp_get_reply_post_type() ) ) )
			return '%s';
			// return __( '<span class="private_title_format">Private:</span> %s', 'bbpresskr' );
		return $format;
	}

	static function protected_title_format($format, $post) {
		if ( in_array( get_post_type($post), array( bbp_get_topic_post_type(), bbp_get_reply_post_type() ) ) )
			return '%s';
			// return __( '<span class="protected_title_format">Protected:</span> %s', 'bbpresskr' );
		return $format;
	}

	static function bbp_theme_before_topic_title() {
		$topic_id = bbp_get_topic_id();
		if ( post_password_required( $topic_id ) ) {
			_e( '<span class="protected_title_format">Protected:</span> ', 'bbpresskr' );
		}
	}

	static function has_topics_args($r) {
		return $r;
	}

	static function checkbox() {
?>
		<p>
			<input name="bbp_private_reply" id="bbp_private_reply" type="checkbox"<?php checked( '1', self::is_private( bbp_get_reply_id() ) ); ?> value="1" tabindex="<?php bbp_tab_index(); ?>" />
			<?php if ( bbp_is_reply_edit() && ( get_the_author_meta( 'ID' ) != bbp_get_current_user_id() ) ) : ?>
				<label for="bbp_private_reply"><?php _e( 'Set author\'s post as private.', 'bbp_private_replies' ); ?></label>
			<?php else : ?>
				<label for="bbp_private_reply"><?php _e( 'Set as private reply', 'bbp_private_replies' ); ?></label>
			<?php endif; ?>
		</p>
<?php
	}


	static function update_reply($reply_id = 0, $topic_id = 0, $forum_id = 0, $anonymous_data = false, $author_id = 0, $is_edit = false) {

		if( isset( $_POST['bbp_private_reply'] ) )
			update_post_meta( $reply_id, '_bbp_reply_is_private', '1' );
		else
			delete_post_meta( $reply_id, '_bbp_reply_is_private' );
	}


	static function is_private( $reply_id = 0 ) {
		$retval 	= false;

		// Checking a specific reply id
		if ( !empty( $reply_id ) ) {
			$reply     = bbp_get_reply( $reply_id );
			$reply_id = !empty( $reply ) ? $reply->ID : 0;
		// Using the global reply id
		} elseif ( bbp_get_reply_id() ) {
			$reply_id = bbp_get_reply_id();
		// Use the current post id
		} elseif ( !bbp_get_reply_id() ) {
			$reply_id = get_the_ID();
		}

		if ( ! empty( $reply_id ) ) {
			$retval = get_post_meta( $reply_id, '_bbp_reply_is_private', true );
		}

		return (bool) apply_filters( 'bbp_reply_is_private', (bool) $retval, $reply_id );
	}


	static function hide_reply( $content = '', $reply_id = 0 ) {
		if( empty( $reply_id ) )
			$reply_id = bbp_get_reply_id( $reply_id );

		if( self::is_private( $reply_id ) ) {

			$can_view     = false;
			$current_user = is_user_logged_in() ? wp_get_current_user() : false;
			$topic_author = bbp_get_topic_author_id();
			$reply_author = bbp_get_reply_author_id( $reply_id );

			if ( ! empty( $current_user ) && $topic_author === $current_user->ID && user_can( $reply_author, 'moderate' ) ) {
				// Let the thread author view replies if the reply author is from a moderator
				$can_view = true;
			}

			if ( ! empty( $current_user ) && $reply_author === $current_user->ID ) {
				// Let the reply author view their own reply
				$can_view = true;
			}

			if( current_user_can( 'moderate' ) ) {
				// Let moderators view all replies
				$can_view = true;
			}

			if( ! $can_view ) {
				$content = __( 'This reply has been marked as private.', 'bbpresskr' );
			}
		}
		return $content;
	}

	static function prevent_subscription_email( $message, $reply_id, $topic_id ) {
		if( self::is_private( $reply_id ) ) {
			self::subscription_email( $message, $reply_id, $topic_id );
			return false;
		}
		return $message; // message unchanged
	}

	static function subscription_email( $message, $reply_id, $topic_id ) {

		if( ! self::is_private( $reply_id ) ) {
			return false; // reply isn't private so do nothing
		}

		$topic_author      = bbp_get_topic_author_id( $topic_id );
		$reply_author      = bbp_get_reply_author_id( $reply_id );
		$reply_author_name = bbp_get_reply_author_display_name( $reply_id );

		// Strip tags from text and setup mail data
		$topic_title   = strip_tags( bbp_get_topic_title( $topic_id ) );
		$reply_content = strip_tags( bbp_get_reply_content( $reply_id ) );
		$reply_url     = bbp_get_reply_url( $reply_id );
		$blog_name     = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$do_not_reply  = '<noreply@' . ltrim( get_home_url(), '^(http|https)://' ) . '>';

		$subject = apply_filters( 'bbp_subscription_mail_title', '[' . $blog_name . '] ' . $topic_title, $reply_id, $topic_id );

		// Array to hold BCC's
		$headers = array();

		// Setup the From header
		$headers[] = 'From: ' . get_bloginfo( 'name' ) . ' ' . $do_not_reply;

		// Get topic subscribers and bail if empty
		$user_ids = bbp_get_topic_subscribers( $topic_id, true );
		if ( empty( $user_ids ) ) {
			return false;
		}

		// Loop through users
		foreach ( (array) $user_ids as $user_id ) {
			// Don't send notifications to the person who made the post
			if ( ! empty( $reply_author ) && (int) $user_id === (int) $reply_author ) {
				continue;
			}

			if( user_can( $user_id, 'moderate' ) || (int) $topic_author === (int) $user_id ) {
				// Get email address of subscribed user
				$headers[] = 'Bcc: ' . get_userdata( $user_id )->user_email;
			}
		}

		wp_mail( $do_not_reply, $subject, $message, $headers );
	}

	static function reply_post_class( $classes ) {

		$reply_id = bbp_get_reply_id();

		// only apply the class to replies
		if( bbp_get_reply_post_type() != get_post_type( $reply_id ) )
			return $classes;

		if( self::is_private( $reply_id ) )
			$classes[] = 'bbp-private-reply';

		return $classes;
	}

}

PrivateReply::init();
