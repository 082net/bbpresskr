<?php
/**
 * Froum admin interface for bbPressKR
 *
 * @package bbPressKR
 * @subpackage Admin Meta
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 */

namespace bbPressKR\Admin;

use bbPressKR\View as View;

use bbPressKR;

if ( !defined('BBPKR_PATH') ) die('HACK');

class Meta {

	static function init() {
		// add_action( 'bbprk_admin_setup_actions', array( __CLASS__, 'setup_actions' ) );
		self::setup_actions();
	}

	static function setup_actions() {
		if ( is_admin() ) {
			add_action( 'add_meta_boxes_' . bbp_get_forum_post_type(), array( __CLASS__, 'add_meta_boxes') );
			add_action( 'wp_ajax_add-bbpmeta', array( __CLASS__, 'ajax_add_bbpmeta' ) );
			add_action( 'wp_ajax_delete-bbpmeta', array( __CLASS__, 'ajax_delete_bbpmeta' ) );
			add_action( 'wp_ajax_bbpmeta-order', array( __CLASS__, 'ajax_bbpmeta_order') );
			// add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );

			add_action( 'save_post_' . bbp_get_forum_post_type(), array( __CLASS__, 'save_meta' ), 10, 3 );
		}

	}

	static function add_meta_boxes() {
		add_meta_box( 'bbpmetafields', __('Meta Fields', 'bbpresskr'), array( __CLASS__, 'metabox_forum_meta_fields'), bbp_get_forum_post_type(), 'normal', 'low' );
	}

	static function save_meta( $post_ID ) {
		// Meta Stuff
		if ( !isset($_POST['bbpmeta_no_js']) )
			return;

		if ( isset($_POST['meta']) && $_POST['meta'] ) {
			foreach ( $_POST['meta'] as $key => $value ) {
				if ( !$meta = get_post_meta_by_id( $key ) )
					continue;
				if ( $meta->post_id != $post_ID )
					continue;
				if ( ! current_user_can( 'edit_post_meta', $post_ID, $value['key'] ) )
					continue;
				update_meta( $key, 'bbpmeta_params', $value );
			}
		}

		if ( isset($_POST['deletemeta']) && $_POST['deletemeta'] ) {
			foreach ( $_POST['deletemeta'] as $key => $value ) {
				if ( !$meta = get_post_meta_by_id( $key ) )
					continue;
				if ( $meta->post_id != $post_ID )
					continue;
				if ( ! current_user_can( 'delete_post_meta', $post_ID, $meta->meta_key ) )
					continue;
				delete_meta( $key );
			}
		}

		self::add_meta($post_ID);
	}

	static function validate_meta( $meta ) {
		if ( empty($meta['key']) )
			return __( 'Please provide a meta field key.', 'bbpresskr' );
		if ( empty($meta['label']) )
			return __( 'Please provide a meta field label.', 'bbpresskr' );
		if ( !preg_match( '/^[a-z0-9]+$/i', $meta['key'] ) )
			return __( 'Only alpabets and numbers are allowed in key.' );
		return true;
	}

	static function ajax_bbpmeta_order() {
		check_ajax_referer( 'bbpmeta-order' );

		$order = isset( $_POST['order'] ) ? $_POST['order'] : false;
		$post_ID = isset( $_POST['post_id'] ) ? (int)$_POST['post_id'] : 0;

		if ( !$post_ID || empty($order) )
			wp_die( 0 );

		$order = preg_replace('|bbpmeta-(\d+)|', '\1', $order);
		$order = explode(',', $order);
		$order = array_filter($order);
		$i = 0;
		foreach ( $order as $mid ) {
			if ( $meta = get_metadata_by_mid( 'post', $mid ) ) {
				if ( $post_ID != $meta->post_id )
					continue;
				$meta->meta_value['order'] = $i;
				update_metadata_by_mid( 'post', $mid, $meta->meta_value );
				$i++;
			}
		}
		wp_die( 1 );
	}

	static function ajax_add_bbpmeta() {
		check_ajax_referer( 'add-bbpmeta', '_ajax_nonce-add-bbpmeta' );
		$c = 0;
		$pid = (int) $_POST['post_id'];
		$post = get_post( $pid );

		if ( isset($_POST['bbpmeta_key']) ) {
			if ( !current_user_can( 'edit_post', $pid ) )
				wp_die( -1 );
			if ( empty($_POST['bbpmeta_key']) )
				wp_die( 1 );
			if ( $post->post_status == 'auto-draft' ) {
				$save_POST = $_POST; // Backup $_POST
				$_POST = array(); // Make it empty for edit_post()
				$_POST['action'] = 'draft'; // Warning fix
				$_POST['post_ID'] = $pid;
				$_POST['post_type'] = $post->post_type;
				$_POST['post_status'] = 'draft';
				$now = current_time('timestamp', 1);
				$_POST['post_title'] = sprintf( __( 'Draft created on %1$s at %2$s' ), date( get_option( 'date_format' ), $now ), date( get_option( 'time_format' ), $now ) );

				if ( $pid = edit_post() ) {
					if ( is_wp_error( $pid ) ) {
						$x = new WP_Ajax_Response( array(
							'what' => 'bbpmeta',
							'data' => $pid
						) );
						$x->send();
					}
					$_POST = $save_POST; // Now we can restore original $_POST again
					if ( !$mid = self::add_meta( $pid ) )
						wp_die( __( 'Please provide a valid key and value set.', 'bbpresskr' ) );
					elseif ( !is_numeric($mid) )
						wp_die( $mid );
				} else {
					wp_die( 0 );
				}
			} elseif ( !$mid = self::add_meta( $pid ) ) {
				wp_die( __( 'Please provide a valid key and value set.', 'bbpresskr' ) );
			} elseif ( !is_numeric($mid) ) {
				wp_die( $mid );
			}

			$meta = get_metadata_by_mid( 'post', $mid );
			$pid = (int) $meta->post_id;
			$meta = $meta->meta_value;
			$x = new WP_Ajax_Response( array(
				'what' => 'meta',
				'id' => $mid,
				'data' => self::_list_meta_row( array_merge($meta, array('meta_id' => $mid)), $c ),
				'position' => 1,
				'supplemental' => array('postid' => $pid)
			) );
		} else { // Update?
			$mid = (int) key( $_POST['bbpmeta'] );
			$new = array_map( 'wp_unslash', $_POST['bbpmeta'][$mid] );
			foreach ( array( 'list'/*, 'adminlist'*/ ) as $what ) {
				$new[$what] = isset($new[$what]);
			}
			extract( $new, EXTR_SKIP );
			// var_dump( $_POST['bbpmeta'], $key, $label);


			if ( true !== $validate = self::validate_meta($new) )
				wp_die( $validate );

			if ( ! $meta = get_metadata_by_mid( 'post', $mid ) )
				wp_die( 0 ); // if meta doesn't exist

			if ( is_protected_meta( $meta->meta_key, 'post' ) || is_protected_meta( $key, 'post' ) ||
				! current_user_can( 'edit_post_meta', $meta->post_id, $meta->meta_key ) ||
				! current_user_can( 'edit_post_meta', $meta->post_id, $key ) )
				wp_die( -1 );

			$meta_value = maybe_unserialize( $meta->meta_value );
			if ( $meta_value != $new ) {
				if ( $meta_value['key'] != $new['key'] ) {
					$current = \bbPressKR\Meta::meta_params($pid);
					foreach ( $current as $param ) {
						if ( $param['key'] == $new['key'] ) {
							wp_die( 'Provided key is already in use.', 'bbpresskr' );
						}
					}
				}

				if ( !$u = update_metadata_by_mid( 'post', $mid, $new ) )
					wp_die( 0 ); // We know meta exists; we also know it's unchanged (or DB error, in which case there are bigger problems).
			}

			$x = new WP_Ajax_Response( array(
				'what' => 'meta',
				'id' => $mid, 'old_id' => $mid,
				'data' => self::_list_meta_row( array_merge($new, array('meta_id' => $mid)), $c ),
				'position' => 0,
				'supplemental' => array('postid' => $meta->post_id)
			) );
		}
		$x->send();
	}

	static function ajax_delete_bbpmeta() {
		return wp_ajax_delete_meta();
	}

	static function add_meta($post_ID) {
		$post_ID = (int) $post_ID;

		$key = isset($_POST['bbpmeta_key']) ? wp_unslash( trim( $_POST['bbpmeta_key'] ) ) : '';
		$key = sanitize_key( $key );
		$label = isset($_POST['bbpmeta_label']) ? $_POST['bbpmeta_label'] : '';
		$single = isset($_POST['bbpmeta_single']) ? $_POST['bbpmeta_single'] : '';
		$order = isset($_POST['bbpmeta_order']) ? (int) $_POST['bbpmeta_order'] : 0;
		foreach ( array( 'list'/*, 'adminlist'*/ ) as $what ) {
			$$what = isset($_POST["bbpmeta_$what"]);
		}
		if ( is_string( $label ) )
			$metavalue = trim( $label );

		if ( true !== $validate = self::validate_meta( array('key' => $key, 'label' => $label) ) )
			return $validate;

		if ( is_protected_meta( $key, 'post' ) || ! current_user_can( 'add_post_meta', $post_ID, $key ) )
			return false;

		$current = \bbPressKR\Meta::meta_params($post_ID);
		foreach ( $current as $param ) {
			if ( $param['key'] == $key ) {
				return __( 'Provided key is already in use.', 'bbpresskr' );
			}
		}
		return add_post_meta( $post_ID, 'bbpmeta_params', compact( 'key', 'label', 'list', 'single'/*, 'adminlist'*/, 'order', 'type', 'options', 'req' ) );
	}

	static function metabox_forum_meta_fields($post) {
		// require_once( BBPKR_INC . '/meta.php' );
	?>
	<div id="bbpmetafieldsuff">
	<div id="ajax-response"></div>
	<?php
	$metadata = \bbPressKR\Meta::has_meta($post->ID);
	self::list_meta( $metadata );
	self::meta_form( $post ); ?>
	</div>
	<?php
		add_action( 'admin_print_footer_scripts', array(__CLASS__, 'admin_footer_forum'), 30 );
		add_action( 'admin_print_footer_scripts', array(__CLASS__, 'customize_modal'), 20 );
	}

	private static function meta_form( $post = null ) {
		$post = get_post($post);
		$order = count( get_post_meta( $post->ID , 'bbpmeta_params', false ) );
		echo View::factory('admin/meta-form', array('order' => $order));
	}

	/*private static function has_meta( $post_ID ) {
		global $wpdb;

		$meta = $wpdb->get_results( $wpdb->prepare("SELECT meta_value, meta_id
				FROM $wpdb->postmeta WHERE post_id = %d AND meta_key LIKE 'bbpmeta_params'
				ORDER BY meta_id", $post_ID) );
		$_meta = array();
		foreach ( $meta as $m ) {
			$_meta[] = array_merge( array('meta_id' => $m->meta_id), maybe_unserialize( $m->meta_value ) );
		}
		return bbPressKR::bbpmeta_order($post_ID, $_meta);
	}*/

	private static function list_meta( $meta ) {
		// Exit if no meta
		wp_nonce_field( 'bbpmeta-order', 'bbpmeta-order-nonce', false );
		$count = 0;
		$style = ! $meta ? ' style="display: none;"' : '';
	?>
	<table id="list-table-bbpmeta" class="widefat"<?php echo $style ?>>
		<thead>
		<tr>
			<th>&nbsp;</th>
			<th><?php _e( 'Key', 'bbpresskr' ) ?></th>
			<th><?php _e( 'Label', 'bbpresskr' ) ?></th>
			<th><?php _e( 'List', 'bbpresskr' ) ?></th>
			<!-- <th><?php _e( 'Admin', 'bbpresskr' ) ?></th> -->
			<th><?php _e( 'Single', 'bbpresskr' ) ?></th>
			<th><?php _e( 'Required', 'bbpresskr' ) ?></th>
			<!-- <th><?php _e( 'Type', 'bbpresskr' ) ?></th> -->
		</tr>
		</thead>
		<tbody id='the-list-bbpmeta' data-wp-lists='list:bbpmeta'>
	<?php
		if ( ! $meta ) {
			echo '<tr><td></td></tr>';
		} else {
			foreach ( $meta as $entry )
				echo self::_list_meta_row( $entry, $count );
		}
	?>
		</tbody>
	</table>
	<?php
	}

	private static function _list_meta_row( $entry, &$count ) {
		static $update_nonce = false;

		if ( !$update_nonce )
			$update_nonce = wp_create_nonce( 'add-bbpmeta' );

		$r = '';
		++ $count;
		if ( $count % 2 )
			$style = 'alternate';
		else
			$style = '';
		$mkey = esc_attr($entry['key']);
		$mlabel = esc_attr( $entry['label'] ); // using a <textarea />
		$list = (bool) $entry['list'];
		$single = $entry['single'];
		// $adminlist = (bool) $entry['adminlist'];
		$type = $entry['type'];
		$options = $entry['options'];
		$req = $entry['req'];
		$order = (int) $entry['order'];
		$mid = (int) $entry['meta_id'];

		$delete_nonce = wp_create_nonce( 'delete-meta_'.$mid );

		return View::factory( 'admin/meta-row', compact( 'update_nonce', 'style', 'mkey', 'mlabel', 'list', 'single', 'req', 'type', 'options', 'order', 'mid', 'delete_nonce' ) );
	}

	static function admin_footer_forum() {
		global $hook_suffix, $pagenow;

		if ( !in_array( $pagenow, array('post.php', 'post-new.php' ) ) || get_post_type() != bbp_get_forum_post_type() )
			return;

		echo View::factory( 'admin/css-meta.php' );
		echo View::factory( 'admin/js-meta.php' );
	}

	static function customize_modal() {
		$type = 'text';
		?>
		<div id="bbp-mcf-backdrop" style="display: none"></div>
		<div style="display: none;" class="wp-core-ui" id="bbp-mcf-wrap">
			<form tabindex="-1" id="bbp-mcf">
			<?php wp_nonce_field( 'internal-linking', '_ajax_linking_nonce', false ); ?>
			<div id="mcf-modal-title">
				<?php _e( 'Customize Field', 'bbpresskr' ) ?>
				<button id="bbp-mcf-close" type="button"><span class="screen-reader-text"><?php _e( 'Close', 'bbpresskr' ); ?></span></button>
		 	</div>
			<div id="bbp-mcf-selector">
				<div id="bbp-mcf-settings">
					<p class="howto"><?php _e('Select Field Type', 'bbpresskr') ?></p>
					<div>
					<label class='screen-reader-text'><?php _e( 'Type', 'bbpresskr' ) ?></label>
					<select name='bbp-mcf-type' id='bbp-mcf-type'>
						<option value=''<?php selected($type, 'textarea') ?>><?php _e('Text', 'bbpresskr') ?></option>
						<option value='textarea'<?php selected($type, 'textarea') ?>><?php _e('Textarea', 'bbpresskr') ?></option>
						<option value='email'<?php selected($type, 'email') ?>><?php _e('Email', 'bbpresskr') ?></option>
						<option value='url'<?php selected($type, 'url') ?>><?php _e('URL', 'bbpresskr') ?></option>
						<option value='checkbox'<?php selected($type, 'checkbox') ?>><?php _e('Checkbox', 'bbpresskr') ?></option>
						<option value='radio'<?php selected($type, 'radio') ?>><?php _e('Radio', 'bbpresskr') ?></option>
						<option value='select'<?php selected($type, 'radio') ?>><?php _e('Select', 'bbpresskr') ?></option>
						<option value='phone'<?php selected($type, 'radio') ?>><?php _e('Phone', 'bbpresskr') ?></option>
						<option value='date'<?php selected($type, 'radio') ?>><?php _e('Date', 'bbpresskr') ?></option>
						<option value='number'<?php selected($type, 'radio') ?>><?php _e('Number', 'bbpresskr') ?></option>
					</select>
					</div>

					<div id="bbp-mcf-opt-wrap">
						<div id="bbp-mcf-opt-radio" class="bbp-mcf-opt" style="display:none;">
						Radio Options ADD/REMOVE/EDIT
						</div>
						<div id="bbp-mcf-opt-select" class="bbp-mcf-opt" style="display:none;">
						SELECT Options ADD/REMOVE/EDIT
						</div>
						<div id="bbp-mcf-opt-phone" class="bbp-mcf-opt" style="display:none;">
						Phone Options... PREFIX/SUFFIX/TYPE
						</div>
						<div id="bbp-mcf-opt-date" class="bbp-mcf-opt" style="display:none;">
						DATE Options format, datepicker, default?
						</div>
						<div id="bbp-mcf-opt-number" class="bbp-mcf-opt" style="display:none;">
						Number Options: min, max, step, default
						</div>
					</div>

				</div>
			</div>

			<div class="submitbox">
				<div id="bbp-mcf-cancel">
					<a class="submitdelete deletion" href="#"><?php _e( 'Cancel' ); ?></a>
				</div>
				<div id="bbp-mcf-update">
					<input type="submit" value="<?php esc_attr_e( 'Save' ); ?>" class="button button-primary" id="bbp-mcf-submit" name="bbp-mcf-submit">
				</div>
			</div>

			</form>
		</div>

		<script type="text/javascript">
var bbpMCF = {};
(function($) {
	$('.button-bbpmcf').click(function(){
		var mid = $(this).data('mid');
		bbpMCF.open(mid);
	});

	var mcf = {};
	bbpMCF = {
		wrap: null, selector: null,
		init: function() {
			mcf.wrap = $('#bbp-mcf-wrap');
			mcf.backdrop = $( '#bbp-mcf-backdrop' );
			mcf.close = $( '#bbp-mcf-close' );
			mcf.submit = $( '#bbp-mcf-submit' );
			mcf.selector = $('#bbp-mcf-type');
			mcf.selector.change(function(){
				var selected = $( '#bbp-mcf-opt-' + $(this).val() );
				if ( selected.length ) {
					selected.siblings().hide();
					selected.show();
				}
			});

			mcf.submit.click( function( event ) {
				event.preventDefault();
				bbpMCF.update();
			});
			mcf.close.add( mcf.backdrop ).add( '#bbp-mcf-cancel a' ).click( function( event ) {
				event.preventDefault();
				bbpMCF.close();
			});

		},

		open: function( mid ) {
			$( document.body ).addClass( 'modal-open' );

			mcf.type = $('bbpmeta-'+mid+'-type').val();
			mcf.options = $('bbpmeta-'+mid+'-options').val();

			mcf.backdrop.show();
			mcf.wrap.show();

			$( document ).trigger( 'bbp-mcf-open', mcf.wrap );
		},

		close: function() {
			$( document.body ).removeClass( 'modal-open' );

			mcf.selector.focus();

			mcf.backdrop.hide();
			mcf.wrap.hide();

			$( document ).trigger( 'bbp-mcf-close', mcf.wrap );
		},

		update: function() {

		}
	}

	$( document ).ready( bbpMCF.init );

})(jQuery);
		</script>
<style type="text/css">
/*------------------------------------------------------------------------------
 bbp-mcf
------------------------------------------------------------------------------*/

#bbp-mcf-wrap {
	display: none;
	background-color: #fff;
	-webkit-box-shadow: 0 3px 6px rgba( 0, 0, 0, 0.3 );
	box-shadow: 0 3px 6px rgba( 0, 0, 0, 0.3 );
	width: 500px;
	overflow: hidden;
	margin-left: -250px;
	margin-top: -125px;
	position: fixed;
	top: 50%;
	left: 50%;
	z-index: 100105;
	-webkit-transition: height 0.2s, margin-top 0.2s;
	transition: height 0.2s, margin-top 0.2s;
}

#bbp-mcf-backdrop {
	display: none;
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	min-height: 360px;
	background: #000;
	opacity: 0.7;
	filter: alpha(opacity=70);
	z-index: 100100;
}

#bbp-mcf {
	position: relative;
	height: 100%;
}

#mcf-modal-title {
	background: #fcfcfc;
	border-bottom: 1px solid #dfdfdf;
	height: 36px;
	font-size: 18px;
	font-weight: 600;
	line-height: 36px;
	padding: 0 36px 0 16px;
	top: 0;
	right: 0;
	left: 0;
}

#bbp-mcf-close {
	color: #666;
	padding: 0;
	position: absolute;
	top: 0;
	right: 0;
	width: 36px;
	height: 36px;
	text-align: center;
	background: none;
	border: none;
	cursor: pointer;
}

#bbp-mcf-close:before {
	font: normal 20px/36px 'dashicons';
	vertical-align: top;
	speak: none;
	-webkit-font-smoothing: antialiased;
	-moz-osx-font-smoothing: grayscale;
	width: 36px;
	height: 36px;
	content: '\f158';
}

#bbp-mcf-close:hover,
#bbp-mcf-close:focus {
	color: #00a0d2;
}

#bbp-mcf-close:focus {
	outline: none;
	-webkit-box-shadow:
		0 0 0 1px #5b9dd9,
		0 0 2px 1px rgba(30, 140, 190, .8);
	box-shadow:
		0 0 0 1px #5b9dd9,
		0 0 2px 1px rgba(30, 140, 190, .8);
}

#bbp-mcf-selector {
	padding: 0 16px 50px;
}

#bbp-mcf ol,
#bbp-mcf ul {
	list-style: none;
	margin: 0;
	padding: 0;
}

#bbp-mcf input[type="text"] {
	-webkit-box-sizing: border-box;
	-moz-box-sizing: border-box;
	box-sizing: border-box;
}

#bbp-mcf #bbp-mcf-settings {
	padding: 8px 0 12px;
}

#bbp-mcf p.howto {
	margin: 3px 0;
}

#bbp-mcf p.howto a {
	text-decoration: none;
	color: inherit;
}

#bbp-mcf label input[type="text"] {
	margin-top: 5px;
	width: 70%;
}

#bbp-mcf #bbp-mcf-settings label span {
	display: inline-block;
	width: 80px;
	text-align: right;
	padding-right: 5px;
	max-width: 24%;
	vertical-align: middle;
	word-wrap: break-word;
}

#bbp-mcf .bbp-mcf-opt {
	padding: 24px 0 12px 0;
}

#bbp-mcf li {
	clear: both;
	margin-bottom: 0;
	border-bottom: 1px solid #f1f1f1;
	color: #32373c;
	padding: 4px 6px 4px 10px;
	cursor: pointer;
	position: relative;
}

#bbp-mcf li:hover {
	background: #eaf2fa;
	color: #151515;
}

#bbp-mcf li:last-child {
	border: none;
}

#bbp-mcf .submitbox {
	padding: 8px 16px;
	background: #fcfcfc;
	border-top: 1px solid #dfdfdf;
	position: absolute;
	bottom: 0;
	left: 0;
	right: 0;
}

#bbp-mcf-cancel {
	line-height: 25px;
	float: left;
}

#bbp-mcf-update {
	line-height: 23px;
	float: right;
}

#bbp-mcf-submit {
	float: right;
	margin-bottom: 0;
}

@media screen and ( max-width: 782px ) {
	#bbp-mcf-wrap {
		margin-top: -140px;
	}

	#bbp-mcf-selector {
		padding: 0 16px 60px;
	}

	#bbp-mcf-cancel {
		line-height: 32px;
	}
}

@media screen and ( max-width: 520px ) {
	#bbp-mcf-wrap {
		width: auto;
		margin-left: 0;
		left: 10px;
		right: 10px;
		max-width: 500px;
	}
}

@media screen and ( max-height: 520px ) {
	#bbp-mcf-wrap {
		-webkit-transition: none;
		transition: none;
	}
}

@media screen and ( max-height: 290px ) {
	#bbp-mcf-wrap {
		height: auto;
		margin-top: 0;
		top: 10px;
		bottom: 10px;
	}

	#bbp-mcf-selector {
		overflow: auto;
		height: -webkit-calc(100% - 92px);
		height: calc(100% - 92px);
		padding-bottom: 2px;
	}
}
</style>
		<?php
	}

}
