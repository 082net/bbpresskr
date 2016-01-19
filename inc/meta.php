<?php
/**
 * @package bbPressKR
 * @subpackage meta
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 */

namespace bbPressKR;

class Meta {

	function __construct() {}

	static function init() {
		self::setup_actions();
	}

	static function setup_actions() {
		add_action( 'bbp_theme_after_topic_form_content' , array(__CLASS__, 'print_meta_fields') );

		add_action( 'bbpkr_' . bbp_get_topic_post_type() . '_list_custom_column', array(__CLASS__, 'meta_column'), 10, 2 );
		add_action( 'bbpkr_' . bbp_get_topic_post_type() . '_list_columns', array(__CLASS__, 'meta_columns'), 10, 2 );

		// bbp_new_topic_post_extras, bbp_edit_topic_post_extras
		// add_action( 'bbp_new_topic_post_extras', array(__CLASS__, 'update_bbpmeta') );
		// add_action( 'bbp_edit_topic_post_extras', array(__CLASS__, 'update_bbpmeta') );
		add_action( 'save_post_' . bbp_get_topic_post_type(), array(__CLASS__, 'update_bbpmeta') );

		add_action( 'add_meta_boxes_' . bbp_get_topic_post_type(), array(__CLASS__, 'add_meta_box') );

	}

	static function add_meta_box( $topic ) {
		$forum_id = bbp_get_topic_forum_id( $topic->ID );
		if ( self::meta_params( $forum_id ) ) {
			add_meta_box( 'bbpmetadiv', __('Meta Fields', 'bbpresskr'), array(__CLASS__, 'meta_box') );
		}
	}

	static function meta_box($post, $box) {
		self::print_meta_fields( $post->ID );
	}

	static function update_bbpmeta( $topic_id ) {
		if ( isset($_POST['bbpmeta']) && is_array($_POST['bbpmeta']) ) {
			$newmeta = $_POST['bbpmeta'];
			$forum_id = bbp_get_topic_forum_id( $topic_id );
			$meta_params = self::meta_params( $forum_id );

			if ( empty( $meta_params) )
				return;

			foreach ( $meta_params as $param ) {
				$type = isset($param['type']) ? $param['type'] : 'text';
				$meta_key = 'bbpmeta_' . $param['key'];
				if ( $type == 'checkbox' ) {
					$meta_val = isset( $newmeta[$param['key']] );
				} else {
					$meta_val = isset( $newmeta[$param['key']] ) ? $newmeta[$param['key']] : null;
				}
				$meta_val = self::validate_meta_value($meta_val, $param);
				if ( !empty( $meta_val ) )
					update_post_meta( $topic_id, $meta_key, $meta_val );
				else
					delete_post_meta( $topic_id , $meta_key );
			}
		}
	}

	static function validate_meta_value($value, $param) {
		if ( !isset($param['type']) )
			$param['type'] = 'text';
		switch ( $param['type'] ) {
			case 'textarea':
			break;
			case 'checkbox':
			break;
			case 'radio':
			break;
			case 'date': case 'datetime': case 'time':
			break;
			case 'text': default:
			break;
		}
		return $value;
	}

	private static function fill_meta( $meta ) {
		return array_merge( array( 'type' => 'text', 'options' => array(), 'req' => false ), $meta );
	}

	static function has_meta( $post_ID ) {
		global $wpdb;

		$meta = $wpdb->get_results( $wpdb->prepare("SELECT meta_value, meta_id
				FROM $wpdb->postmeta WHERE post_id = %d AND meta_key LIKE 'bbpmeta_params'
				ORDER BY meta_id", $post_ID) );
		$_meta = array();
		foreach ( $meta as $m ) {
			$_meta[] = array_merge( array('meta_id' => $m->meta_id), maybe_unserialize( $m->meta_value ) );
		}
		$_meta = array_map( array(__CLASS__, 'fill_meta'), $_meta );
		uasort( $_meta, array(__CLASS__, 'meta_order') );
		return $_meta;
	}

	static function meta_params( $forum_id = 0 ) {
		$forum_id = bbp_get_forum_id( $forum_id );
		if ( ! bbp_is_forum($forum_id) )
			return array();

		if ( is_object($forum_id) )
			$forum_id = $forum_id->ID;

		$meta_params = get_post_meta($forum_id, 'bbpmeta_params', false);

		if ( empty($meta_params) ) {
			$forum_parent = (int) get_post_field('post_parent', $forum_id);
			if ( $forum_parent ): while( $forum_parent ):
			if ( $meta_params = get_post_meta($forum_parent, 'bbpmeta_params', false) )
				break;
			$forum_parent = (int) get_post_field('post_parent', $forum_parent);
			endwhile; endif;
			if ( empty($meta_params) )
				return array();
		}

		uasort( $meta_params, array(__CLASS__, 'meta_order') );
		$meta_params = array_map( array(__CLASS__, 'fill_meta'), $meta_params );

		return $meta_params;
	}

	static function meta_order($a, $b) {
		return strcmp($a['order'], $b['order']);
	}

	static function meta_fields( $forum_id = 0, array $args = array() ) {
		if ( bbp_is_single_forum() ) {
			$default = array('list' => true);
		} elseif ( bbp_is_single_topic() ) {
			$default = array('single' => true);
		} else {
			$default = array();
		}

		$args = wp_parse_args( $args, $default );

		$meta = self::meta_params( $forum_id );
		if ( bbp_is_single_forum() ) {
			$meta = wp_list_filter( $meta, $args );
		} elseif ( bbp_is_single_topic() ) {
			$meta = wp_list_filter( $meta, $args );
		}
		return $meta;
	}

	static function meta_column( $name, $post_ID ) {
		$meta = self::meta_fields( $post_ID );
		foreach ( $meta as $m ) {
			if ( $m['key'] == $name ) {
				$value = get_post_meta( $post_ID, "bbpmeta_{$name}_value", true );
				echo $value;
			}
		}
	}

	static function meta_columns( $columns, $forum_id ) {
		$meta = self::meta_fields( $forum_id );
		foreach ( $meta as $m ) {
			$columns[$m['key']] = $m['label'];
		}
		return $columns;
	}

	static function print_meta_fields( $topic_id = 0 ) {
		$topic_id = bbp_get_topic_id( $topic_id );
		$forum_id = bbp_get_forum_id( bbp_get_topic_forum_id($topic_id) );
		$meta = self::meta_params( $forum_id );
		if ( empty( $meta ) )
			return;

		foreach ( $meta as $m ) {
			$value = $topic_id ? get_post_meta( $topic_id, 'bbpmeta_'.$m['key'], true ) : '';
			?>
		<p>
			<label for="bbpmeta_<?php echo $m['key']; ?>"><?php echo $m['label']; ?>:</label><br />
			<input type="text" id="bbpmeta_<?php echo $m['key']; ?>" name="bbpmeta[<?php echo $m['key']; ?>]" tabindex="<?php bbp_tab_index(); ?>" value="<?php esc_attr_e($value); ?>" />
		</p>
		<?php }

	}

	static function meta_field($meta) {
		if ( empty($meta['type']) )
			$meta['type'] = 'text';
		switch( $meta['type'] ) {
			case 'text':
			break;
			case 'textarea':
			break;
			case 'email':
			break;
			case 'url':
			break;
			case 'checkbox':
			break;
			case 'radio':
			break;
			case 'select':
			break;
		}
	}

}

Meta::init();

