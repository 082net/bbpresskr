<?php
/**
 * @package bbPressKR
 * @subpackage Walker
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 * @poweredby bbPress BBP_Walker_Reply
 */

namespace bbPressKR\Walker;

if ( class_exists( 'Walker' ) ) :
class Reply extends \Walker {

	/**
	 * @see Walker::$tree_type
	 *
	 * @since bbPress (r4944)
	 *
	 * @var string
	 */
	var $tree_type = 'reply';

	/**
	 * @see Walker::$db_fields
	 *
	 * @since bbPress (r4944)
	 *
	 * @var array
	 */
	var $db_fields = array(
		'parent' => 'reply_to',
		'id'     => 'ID'
	);

	/**
	 * @see Walker::start_lvl()
	 *
	 * @since bbPress (r4944)
	 *
	 * @param string $output Passed by reference. Used to append additional content
	 * @param int $depth Depth of reply
	 * @param array $args Uses 'style' argument for type of HTML list
	 */
	public function start_lvl( &$output = '', $depth = 0, $args = array() ) {
		bbpress()->reply_query->reply_depth = $depth + 1;

		switch ( $args['style'] ) {
			case 'div':
				break;
			case 'ol':
				echo "<ol class='bbp-threaded-replies'>\n";
				break;
			case 'ul':
			default:
				echo "<ul class='bbp-threaded-replies'>\n";
				break;
		}
	}

	/**
	 * @see Walker::end_lvl()
	 *
	 * @since bbPress (r4944)
	 *
	 * @param string $output Passed by reference. Used to append additional content
	 * @param int $depth Depth of reply
	 * @param array $args Will only append content if style argument value is 'ol' or 'ul'
	 */
	public function end_lvl( &$output = '', $depth = 0, $args = array() ) {
		bbpress()->reply_query->reply_depth = (int) $depth + 1;

		switch ( $args['style'] ) {
			case 'div':
				break;
			case 'ol':
				echo "</ol>\n";
				break;
			case 'ul':
			default:
				echo "</ul>\n";
				break;
		}
	}

	/**
	 * @since bbPress (r4944)
	 */
	public function display_element( $element = false, &$children_elements = array(), $max_depth = 0, $depth = 0, $args = array(), &$output = '' ) {

		if ( empty( $element ) )
			return;

		// Get element's id
		$id_field = $this->db_fields['id'];
		$id       = $element->$id_field;

		// Display element
		parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );

		// If we're at the max depth and the current element still has children, loop over those
		// and display them at this level to prevent them being orphaned to the end of the list.
		if ( ( $max_depth <= (int) $depth + 1 ) && isset( $children_elements[$id] ) ) {
			foreach ( $children_elements[$id] as $child ) {
				$this->display_element( $child, $children_elements, $max_depth, $depth, $args, $output );
			}
			unset( $children_elements[$id] );
		}
	}

	/**
	 * @see Walker:start_el()
	 *
	 * @since bbPress (r4944)
	 */
	public function start_el( &$output, $object, $depth = 0, $args = array(), $current_object_id = 0 ) {

		// Set up reply
		$depth++;
		bbpress()->reply_query->reply_depth = $depth;
		bbpress()->reply_query->post        = $object;
		bbpress()->current_reply_id         = $object->ID;

		// Check for a callback and use it if specified
		if ( !empty( $args['callback'] ) ) {
			call_user_func( $args['callback'], $object, $args, $depth );
			return;
		}

		// Style for div or list element
		if ( !empty( $args['style'] ) && ( 'div' === $args['style'] ) ) {
			echo "<div>\n";
		} else {
			echo "<li>\n";
		}

		bbp_get_template_part( 'loop', 'single-reply' );
	}

	/**
	 * @since bbPress (r4944)
	 */
	public function end_el( &$output = '', $object = false, $depth = 0, $args = array() ) {

		// Check for a callback and use it if specified
		if ( !empty( $args['end-callback'] ) ) {
			call_user_func( $args['end-callback'], $object, $args, $depth );
			return;
		}

		// Style for div or list element
		if ( !empty( $args['style'] ) && ( 'div' === $args['style'] ) ) {
			echo "</div>\n";
		} else {
			echo "</li>\n";
		}
	}

	/**
	 * Output a single comment.
	 *
	 * @access protected
	 * @since 3.6.0
	 *
	 * @see wp_list_comments()
	 *
	 * @param object $comment Comment to display.
	 * @param int    $depth   Depth of comment.
	 * @param array  $args    An array of arguments.
	 */
	protected function comment( $comment, $depth, $args ) {
		if ( 'div' == $args['style'] ) {
			$tag = 'div';
			$add_below = 'comment';
		} else {
			$tag = 'li';
			$add_below = 'div-comment';
		}
?>
		<<?php echo $tag; ?> <?php comment_class( $this->has_children ? 'parent' : '' ); ?> id="comment-<?php comment_ID(); ?>">
		<?php if ( 'div' != $args['style'] ) : ?>
		<div id="div-comment-<?php comment_ID(); ?>" class="comment-body">
		<?php endif; ?>
		<div class="comment-author vcard">
			<?php if ( 0 != $args['avatar_size'] ) echo get_avatar( $comment, $args['avatar_size'] ); ?>
			<?php printf( __( '<cite class="fn">%s</cite> <span class="says">says:</span>' ), get_comment_author_link() ); ?>
		</div>
		<?php if ( '0' == $comment->comment_approved ) : ?>
		<em class="comment-awaiting-moderation"><?php _e( 'Your comment is awaiting moderation.' ) ?></em>
		<br />
		<?php endif; ?>

		<div class="comment-meta commentmetadata"><a href="<?php echo esc_url( get_comment_link( $comment->comment_ID, $args ) ); ?>">
			<?php
				/* translators: 1: date, 2: time */
				printf( __( '%1$s at %2$s' ), get_comment_date(),  get_comment_time() ); ?></a><?php edit_comment_link( __( '(Edit)' ), '&nbsp;&nbsp;', '' );
			?>
		</div>

		<?php comment_text( get_comment_id(), array_merge( $args, array( 'add_below' => $add_below, 'depth' => $depth, 'max_depth' => $args['max_depth'] ) ) ); ?>

		<?php
		comment_reply_link( array_merge( $args, array(
			'add_below' => $add_below,
			'depth'     => $depth,
			'max_depth' => $args['max_depth'],
			'before'    => '<div class="reply">',
			'after'     => '</div>'
		) ) );
		?>

		<?php if ( 'div' != $args['style'] ) : ?>
		</div>
		<?php endif; ?>
<?php
	}

	/**
	 * Output a comment in the HTML5 format.
	 *
	 * @access protected
	 * @since 3.6.0
	 *
	 * @see wp_list_comments()
	 *
	 * @param object $comment Comment to display.
	 * @param int    $depth   Depth of comment.
	 * @param array  $args    An array of arguments.
	 */
	protected function html5_comment( $comment, $depth, $args ) {
		$tag = ( 'div' === $args['style'] ) ? 'div' : 'li';
?>
		<<?php echo $tag; ?> id="comment-<?php comment_ID(); ?>" <?php comment_class( $this->has_children ? 'parent' : '' ); ?>>
			<article id="div-comment-<?php comment_ID(); ?>" class="comment-body">
				<footer class="comment-meta">
					<div class="comment-author vcard">
						<?php if ( 0 != $args['avatar_size'] ) echo get_avatar( $comment, $args['avatar_size'] ); ?>
						<?php printf( __( '%s <span class="says">says:</span>' ), sprintf( '<b class="fn">%s</b>', get_comment_author_link() ) ); ?>
					</div><!-- .comment-author -->

					<div class="comment-metadata">
						<a href="<?php echo esc_url( get_comment_link( $comment->comment_ID, $args ) ); ?>">
							<time datetime="<?php comment_time( 'c' ); ?>">
								<?php printf( _x( '%1$s at %2$s', '1: date, 2: time' ), get_comment_date(), get_comment_time() ); ?>
							</time>
						</a>
						<?php edit_comment_link( __( 'Edit' ), '<span class="edit-link">', '</span>' ); ?>
					</div><!-- .comment-metadata -->

					<?php if ( '0' == $comment->comment_approved ) : ?>
					<p class="comment-awaiting-moderation"><?php _e( 'Your comment is awaiting moderation.' ); ?></p>
					<?php endif; ?>
				</footer><!-- .comment-meta -->

				<div class="comment-content">
					<?php comment_text(); ?>
				</div><!-- .comment-content -->

				<?php
				comment_reply_link( array_merge( $args, array(
					'add_below' => 'div-comment',
					'depth'     => $depth,
					'max_depth' => $args['max_depth'],
					'before'    => '<div class="reply">',
					'after'     => '</div>'
				) ) );
				?>
			</article><!-- .comment-body -->
<?php
	}
		
}
endif; // class_exists check