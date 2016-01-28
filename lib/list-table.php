<?php
/**
 * @package bbPressKR
 * @subpackage List_Table
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 *
 * @ref wp-admin/includes/class-wp-list-table.php
 */

namespace bbPressKR\Topic;

class List_Table {

	public $query;

	public $items;

	private $_args;

	private $_topic_args = array();

	private $_pagination_args = array();

	protected $forum;

	private $_actions;

	private $_pagination;

	private $post_type;

	private $post_type_object;

	private $forum_op = array();

	private $hierarchical_display = false;

	public static function instance( $r = array() ) {
		// Store the instance locally to avoid private static replication
		static $instance = null;

		// Only run these methods if they haven't been ran previously
		if ( null === $instance ) {
			$instance = new List_Table( $r );
		}

		// Always return the instance
		return $instance;
	}

	public function __construct( $r = array() ) {
		$args = array(
			'plural' => 'topics',
			'singular' => 'topic',
			'ajax' => false,
		);

		// $this->query =& bbpress()->topic_query;
		$this->forum = bbp_get_forum( $r['post_parent'] );

		$this->forum_op = bbpresskr()->forum_options( $r['post_parent'] );

		// override query vars
		$this->_topic_args = array_merge( $r, array(
			// 'meta_key' => null,
			// 'orderby' => 'post_date',
			'posts_per_page' => $this->forum_op['posts_per_page'],
		) );

		$this->post_type = $r['post_type'];
		$this->post_type_object = get_post_type_object( $this->post_type );

		add_filter( "bbpkr_{$this->post_type}_columns", array( $this, 'get_columns' ), 0 );

		if ( !$args['plural'] )
			$args['plural'] = 'topics';

		$args['plural'] = sanitize_key( $args['plural'] );
		$args['singular'] = sanitize_key( $args['singular'] );

		$this->_args = $args;

		add_action( 'bbp_has_topics', array( &$this, 'prepare_items'), 10, 2 );

		// list topics of sub-forum of forum category
		add_filter( 'bbp_after_has_topics_parse_args', array( &$this, 'add_topic_parent_forums' ), 99 );

		/*if ( $args['ajax'] ) {
			// wp_enqueue_script( 'list-table' );
			add_action( 'admin_footer', array( $this, '_js_vars' ) );
		}*/
	}

	public function __get( $name ) {
		return $this->$name;
	}

	public function __set( $name, $value ) {
		return $this->$name = $value;
	}

	public function __isset( $name ) {
		return isset( $this->$name );
	}

	public function __unset( $name ) {
		unset( $this->$name );
	}

	public function __call( $name, $arguments ) {
		return call_user_func_array( array( $this, $name ), $arguments );
	}

	public function ajax_user_can() {
		return current_user_can( $this->post_type_object->cap->edit_posts );
	}

	public function add_topic_parent_forums( $r ) {
		if ( bbp_is_forum_category() ) {
			$forums_args = array(
				'post_type' => bbp_get_forum_post_type(),
				'child_of' => $r['post_parent'],
				'post_status' => bbp_get_public_status_id(),
				'fields' => 'ids',
				'ignore_sticky_posts' => true,
			);
			$children = get_pages($forums_args);
			if ( $children ) {
				$forums = array($r['post_parent']);
				foreach ( $children as $child ) {
					$forums[] = $child->ID;
				}
				$r['post_parent'] = '';
				$r['post_parent__in'] = $forums;
			}
		}
		return $r;
	}

	/**
	 * Prepares the list of items for displaying.
	 * @uses WP_List_Table::set_pagination_args()
	 *
	 * @since 3.1.0
	 * @access public
	 * @abstract
	 */
	public function prepare_items( $bool, $query ) {
		$this->query =& $query;

		$this->items = $this->query->posts;

		/*$this->set_pagination_args( array(
			'total_items' => $total_items,
			'total_pages' => $total_pages,
			'per_page' => $per_page
		) );*/
		return $bool;
	}

	protected function set_pagination_args( $args ) {
		$args = wp_parse_args( $args, array(
			'total_items' => 0,
			'total_pages' => 0,
			'per_page' => 0,
		) );

		if ( !$args['total_pages'] && $args['per_page'] > 0 )
			$args['total_pages'] = ceil( $args['total_items'] / $args['per_page'] );

		$this->_pagination_args = $args;
	}

	public function get_pagination_arg( $key ) {
		if ( 'page' == $key )
			return $this->get_pagenum();

		if ( isset( $this->_pagination_args[$key] ) )
			return $this->_pagination_args[$key];
	}

	public function has_items() {
		return $this->query->have_posts();
	}

	/**
	 * Message to be displayed when there are no items
	 *
	 * @since 3.1.0
	 * @access public
	 */
	public function no_items() {
		_e( 'No items found.' );
	}

	/**
	 * Display the search box.
	 *
	 * @since 3.1.0
	 * @access public
	 *
	 * @param string $text The search button text
	 * @param string $input_id The search input id
	 */
	public function search_box( $text, $input_id ) {
		if ( empty( $_REQUEST['s'] ) && !$this->has_items() )
			return;
		$forum_id = bbp_get_forum_id();
		$s = isset( $_REQUEST['ts'] ) ? $_REQUEST['ts'] : '';
		$type = isset( $_REQUEST['type'] ) ? $_REQUEST['type'] : '';
?>
<div class="bbpkr-search-box">
	<div>
		<form action="" method="get">
			<span class="select-search">
			<select name="type" tabindex="0">
				<option<?php selected($type, 'all'); ?> value="all">전체</option>
				<option<?php selected($type, 'subject'); ?> value="subject"><?php _e('Title', 'bbpresskr') ?></option>
				<option<?php selected($type, 'content'); ?> value="content"><?php _e('Content', 'bbpresskr') ?></option>
				<option<?php selected($type, 'author'); ?> value="author"><?php _e('Author', 'bbpresskr') ?></option>
			</select>
			</span>
			<input type="hidden" name="forum_id" value="<?php echo $forum_id ?>" />
			<input type="text" id="ts" alt="<?php _e('Search', 'bbpresskr') ?>" class="input-search" value="<?php esc_attr_e($s); ?>" name="ts" tabindex="0"><label for="s"></label>
			<input type="submit" class="button-submit" value="<?php _e('Search', 'bbpresskr') ?>" />
		</form>
		<!-- <span class="btstyle3"><a title="RSS" id="notice" class="rssscrapbtn" href="#" tabindex="0">RSS</a></span> -->
	</div>
</div>
<br class="clear" />
<?php
	}

	protected function get_views() {
		global $post;
		$views = array();
		if ( bbp_is_forum_category() && bbp_has_forums() ) {
			$views['all'] = '<a class="current bbp-forum-title" href="' . bbp_get_forum_permalink() . '">' . __('All', 'bbpresskr') . '</a>';
			while ( bbp_forums() ) : bbp_the_forum();
			$views[] = '<a class="bbp-forum-title" href="' . bbp_get_forum_permalink() . '">' . bbp_get_forum_title() . '</a>';
			endwhile;
		} else {
			$forum_id = bbp_get_forum_id();
			$forum = get_post($forum_id);
			if ( $forum->post_parent && bbp_has_forums( array('post_parent' => $forum->post_parent) ) ) {
				$views['all'] = '<a class="bbp-forum-title" href="' . bbp_get_forum_permalink($forum->post_parent) . '">' . __('All', 'bbpresskr') . '</a>';
				while ( bbp_forums() ) : bbp_the_forum();
				$current = ( $forum_id == $post->ID ) ? ' current' : '';
				$views[$post->post_name] = '<a class="bbp-forum-title' . $current . '" href="' . bbp_get_forum_permalink() . '">' . bbp_get_forum_title() . '</a>';
				endwhile;
			}
		}
		return $views;
	}

	public function views() {
		$views = $this->get_views();
		$views = apply_filters( "bbpkr_views_topics", $views );

		if ( empty( $views ) )
			return;

		echo "<ul class='subsubsub forums'>\n";
		foreach ( $views as $class => $view ) {
			$views[ $class ] = "\t<li class='$class'>$view";
		}
		echo implode( "</li>\n", $views ) . "</li>\n";
		echo "</ul>";
	}

	protected function row_actions( $actions, $always_visible = false ) {
		$action_count = count( $actions );
		$i = 0;

		if ( !$action_count )
			return '';

		$out = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions' ) . '">';
		foreach ( $actions as $action => $link ) {
			++$i;
			( $i == $action_count ) ? $sep = '' : $sep = ' | ';
			$out .= "<span class='$action'>$link$sep</span>";
		}
		$out .= '</div>';

		return $out;
	}

	public function get_pagenum() {
		return max( 1, $this->query->get('paged') );
	}

	protected function pagination( $which ) {
		if ( !empty( $this->query->pagination_links ) )
			echo $this->query->pagination_links;
	}

	public function get_columns() {
		$columns = array(
			'no'                    => _x('No', 'column no', 'bbpresskr'),
			'title'                 => __( 'Title',    'bbpresskr' ),
			// 'bbp_topic_forum'       => __( 'Forum',     'bbpresskr' ),
			'reply_count' => __( 'Replies',   'bbpresskr' ),
			// 'bbp_topic_voice_count' => __( 'Voices',    'bbpresskr' ),
			'author'      => __( 'Author',    'bbpresskr' ),
			'date'     => __( 'Date',   'bbpresskr' ),
			// 'bbp_topic_freshness'   => __( 'Freshness', 'bbpresskr' )
		);

		if ( $hide = get_post_meta( $this->forum, 'bbpkr_hide_columns', true ) ) {
			foreach ( $hide as $h ) {
				if ( isset($columns[$h]) ) {
					unset( $columns[$h] );
				}
			}
		}

		$columns = apply_filters( "bbpkr_{$this->post_type}_list_columns", $columns, $this->forum );

		return $columns;
	}

	protected function get_sortable_columns() {
		return array();
	}

	protected function get_column_info() {
		if ( isset( $this->_column_headers ) )
			return $this->_column_headers;

		$columns = apply_filters( "bbpkr_{$this->post_type}_columns", array() );
		$hidden = array();
		$sortable = array();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		return $this->_column_headers;
	}

	public function get_column_count() {
		list ( $columns, $hidden ) = $this->get_column_info();
		$hidden = array_intersect( array_keys( $columns ), array_filter( $hidden ) );
		return count( $columns ) - count( $hidden );
	}

	public function print_column_headers( $with_id = true ) {
		list( $columns, $hidden, $sortable ) = $this->get_column_info();

		$i = 0;
		foreach ( $columns as $column_key => $column_display_name ) {
			$class = array( 'bbptb-column', "column-$column_key" );
			if ( $i == 0 )
				$class[] = 'first';
			$i++;

			$style = '';
			if ( in_array( $column_key, $hidden ) )
				$style = 'display:none;';

			$style = ' style="' . $style . '"';

			if ( in_array( $column_key, array( 'no', 'posts', 'comments', 'links' ) ) )
				$class[] = 'num-column';

			$id = $with_id ? "id='column-$column_key'" : '';

			if ( !empty( $class ) )
				$class = "class='" . join( ' ', $class ) . "'";

			echo "<th scope='col' $id $class $style>$column_display_name</th>";
		}
	}

	/**
	 * Display the table
	 *
	 * @since 3.1.0
	 * @access public
	 */
	public function display() {
		$singular = $this->_args['singular'];

		$this->display_tablenav( 'top' );

		$forum_description = bbp_get_forum_content();

		$type = 'list';

?>
<table class="<?php echo implode( ' ', $this->get_table_classes() ); ?>" summary="<?php echo esc_attr(bbp_get_forum_content()); ?>">
	<thead>
	<tr>
		<?php $this->print_column_headers(); ?>
	</tr>
	</thead>

	<!-- <tfoot>
	<tr>
		<?php //$this->print_column_headers( false ); ?>
	</tr>
	</tfoot> -->

	<tbody id="bbpkr-the-list"<?php
		if ( $singular ) {
			echo " data-wp-lists='list:$singular'";
		} ?>>
		<?php $this->display_rows_or_placeholder(); ?>
	</tbody>
</table>
<?php
		$this->display_tablenav( 'bottom' );
	}

	/**
	 * Get a list of CSS classes for the <table> tag
	 *
	 * @since 3.1.0
	 * @access protected
	 *
	 * @return array
	 */
	protected function get_table_classes() {
		$skin = bbpresskr()->forum_option('skin');
		return array( 'bbpkr-list-table', "bbptb-{$skin}", 'widefat', 'striped', 'fixed', $this->_args['plural'] );
	}

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @since 3.1.0
	 * @access protected
	 */
	protected function display_tablenav( $which ) {
?>
	<div class="tablenav <?php echo esc_attr( $which ); ?>">

		<div class="alignleft actions bulkactions">
			<?php //$this->bulk_actions( $which ); ?>
		</div>
<?php
		$this->extra_tablenav( $which );
		$this->pagination( $which );
?>

		<br class="clear" />
	</div>
<?php
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination
	 *
	 * @since 3.1.0
	 * @access protected
	 */
	protected function extra_tablenav( $which ) {
		/*if ( current_user_can('') ) {

		}*/
	}

	/**
	 * Generate the <tbody> part of the table
	 *
	 * @since 3.1.0
	 * @access public
	 */
	public function display_rows_or_placeholder() {
		if ( $this->has_items() ) {
			$this->display_rows();
		} else {
			echo '<tr class="no-items"><td class="colspanchange" colspan="' . $this->get_column_count() . '">';
			$this->no_items();
			echo '</td></tr>';
		}
	}

	/**
	 * Generate the table rows
	 *
	 * @since 3.1.0
	 * @access public
	 */
	public function display_rows($level = 0) {
		// Create array of post IDs.
		// $post_ids = array();
		// $posts = $this->query->posts;

		// foreach ( $posts as $a_post )
		// 	$post_ids[] = $a_post->ID;

		// $this->comment_pending_count = 0;//get_pending_comments_num( $post_ids );

		while ( $this->query->have_posts() ): $this->query->the_post();
			$this->single_row( /*$this->query->post,*/ $level );
		endwhile;
		wp_reset_postdata();
	}

	public function single_row( /*$post,*/ $level = 0 ) {
		global $mode, $post;
		static $alternate;

		$edit_link = '#';get_edit_post_link( $post->ID );
		$title = get_the_title();
		$post_type_object = get_post_type_object( $post->post_type );
		$can_edit_post = false;// current_user_can( 'edit_post', $post->ID );

		$alternate = 'alternate' == $alternate ? '' : 'alternate';
		$classes = array( $alternate, 'level-0' );

	?>
		<tr id="topic-row-<?php echo $post->ID; ?>" <?php bbp_topic_class( $post->ID, $classes ) ?>>
	<?php

		$forum_id = bbp_get_topic_forum_id( $post->ID );
		$topic_id = $post->ID;

		list( $columns, $hidden ) = $this->get_column_info();

		foreach ( $columns as $column_name => $column_display_name ) {
			$class = "class=\"column-$column_name\"";

			$style = '';
			if ( in_array( $column_name, $hidden ) )
				$style = ' style="display:none;"';

			$attributes = "$class$style";

			switch ( $column_name ) {

			case 'no':
			?>
			<td <?php echo $attributes ?>>
			<?php bbp_the_no(); ?>
			</td>
			<?php
			break;

			case 'title':
				$pad = str_repeat( '&#8212; ', $level );
				$title = '<a class="bbp-topic-permalink row-title" href="' . bbp_get_topic_permalink() . '">' . $title . '</a>';
				echo "<td $attributes>";
				do_action( 'bbp_theme_before_topic_title' );
				echo $pad . $title;
				do_action( 'bbp_theme_after_topic_title' );
				echo '</td>';
			break;

			// Forum
			case 'forum' :
				// Output forum name
				if ( !empty( $forum_id ) ) {
					// Forum Title
					$forum_title = bbp_get_forum_title( $forum_id );
					if ( empty( $forum_title ) ) {
						$forum_title = esc_html__( 'No Forum', 'bbpress' );
					}
					// Output the title
					echo $forum_title;
				} else {
					esc_html_e( '(No Forum)', 'bbpress' );
				}
				break;

			// Reply Count
			case 'reply_count' :
				echo "<td $attributes>";
				echo '<div class="post-com-count-wrapper"><a class="post-com-count" href="#"><span class="comment-count">';
				bbp_topic_reply_count( $topic_id );
				echo '</span></a></div>';
				echo "</td>";
				break;

			// Reply Count
			case 'voice_count' :
				echo "<td $attributes>";
				bbp_topic_voice_count( $topic_id );
				break;

			// Author
			case 'author' :
				echo "<td $attributes>";
				bbp_topic_author_display_name( $topic_id );
				echo "</td>";
				break;

			// Freshness
			case 'date':
				echo "<td $attributes>";
				echo get_the_date( 'Y.m.d' );
				echo "</td>";
				break;

			// Freshness
			case 'freshness' :
				echo "<td $attributes>";
				$last_active = bbp_get_topic_last_active_time( $topic_id, false );
				if ( !empty( $last_active ) ) {
					echo esc_html( $last_active );
				} else {
					esc_html_e( 'No Replies', 'bbpress' ); // This should never happen
				}
				echo "</td>";
				break;

			default:
			?>
			<td <?php echo $attributes ?>><?php
				do_action( "bbpkr_{$post->post_type}_list_custom_column", $column_name, $post->ID );
			?></td>
			<?php
			break;
			}
		}
	?>
		</tr>
	<?php
	}

}
