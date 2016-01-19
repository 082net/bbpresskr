<?php
/**
 * @package bbPressKR
 * @subpackage Attachments
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 */

namespace bbPressKR;

if ( !defined('BBPKR_PATH') ) die('HACK');

class Editor {
	protected static $fired;

	protected static $op;

	protected static $defaults = array(
		'wpautop' => true,
		'media_buttons' => false,
		'textarea_rows' => '6',
		'tinymce' => true,
		'quicktags' => true,
		'teeny' => true,
		'more_html_tags' => false,
		'topic_orderby' => '',
	);

	public static function init() {
		self::setup_actions();
	}

	private static function setup_actions() {
		if ( isset(self::$fired) )
			return;
		self::$fired = true;
		// order topics by the latest not freshness
		if ( get_option('_bbpkr_topic_order_latest'))
			add_action( 'bbp_init', array(__CLASS__, 'register_topic_order') );

		add_filter( 'bbp_before_get_the_content_parse_args', array(__CLASS__, 'editor_args') );

		// add_action( 'wp_head', array(__CLASS__, 'wp_head') );

		add_filter( 'bbp_get_topic_content', array( __CLASS__, 'do_shortcodes' ) );
		add_filter( 'bbp_get_reply_content', array( __CLASS__, 'do_shortcodes' ) );

		if ( get_option('_bbpkr_more_html_tags') )
			add_filter( 'bbp_kses_allowed_tags', array(__CLASS__, 'allowed_tags') );

		// if ( get_option('_bbpkr_media_buttons') )// bbp_is_edit
			// add_filter( 'media_view_settings', array(__CLASS__, 'media_view_settings'), 10, 2 );

		/*if ( get_option('_bbpkr_editor_teeny') ) {*/
			// add_filter('teeny_mce_plugins', array(__CLASS__, 'teeny_mce_plugins'), 9999);
			// add_action('after_wp_tiny_mce', array(__CLASS__, 'after_wp_tiny_mce'));
			// add_filter('teeny_mce_buttons', array(__CLASS__, 'mce_buttons'), 9999);
		/*} else {
			add_filter('mce_external_plugins', array(__CLASS__, 'mce_external_plugins'), 9999);
			add_filter('mce_buttons', array(__CLASS__, 'mce_buttons'), 9999);
			foreach ( array('mce_buttons_2', 'mce_buttons_3', 'mce_buttons_4') as $mce_buttons_hook )
				add_filter( $mce_buttons_hook, '__return_empty_array', 9999 );
		}*/
		//add_filter( 'bbp_get_template_part', array(__CLASS__, 'code_notice'), 10, 3 );

		/*if ( function_exists('bbp_get_topic_post_type') ) {
		foreach ( array(bbp_get_topic_post_type(), bbp_get_reply_post_type(), bbp_get_forum_post_type()) as $form_hook )
			add_filter( "bbp_theme_before_{$form_hook}_form_content", array(__CLASS__, 'code_notice'), 10 );
		}*/
	}

	static function do_shortcodes( $content ) {
		return do_shortcode( $content );
	}

	static function wp_head() {
?>
<style type="text/css">
div.code-main .noselect { cursor: pointer; -webkit-user-select: none;-khtml-user-select: none;-moz-user-select: none;-o-user-select: none; user-select: none; }
span.code-inline { background-color: #f5f5f5; font-family: monospace; font-size: 0.9em; white-space: pre-wrap; padding: 2px 3px; }
div.code-main {
	font-size: 0.9em;
	-webkit-border-radius: 3px;-khtml-border-radius: 3px;-moz-border-radius: 3px;-o-border-radius: 3px; border-radius: 3px;
	border: solid 1px #e5e5e5;
	padding: 0; margin: 1em;
	background-color: #f5f5f5;
}
div.code-main div.code-title {
	font-family: monospace;
	height: 1.70em; line-height: 1.70em; padding: 0; margin: 0;
	border-bottom: solid 1px #e5e5e5;
	background-color: #f3f3f3;
}
div.code-main div.code-num {
	background-color: #f5f5f5;
	border: none;
	font-family: monospace;
	white-space: nowrap;
	line-height: 1.4em; padding: 0.5em 0.2em; margin: 0;
	float: left;
	overflow: hidden;
}
div.code-main div.code-content {
	font-family: monospace;
	white-space: nowrap;
	background-color: #f9f9f9;
	line-height: 1.4em; padding: 0.5em; margin: 0;
	border: none;
	overflow-y: auto; overflow-x: auto;
}
div.code-main div.code-content,
div.code-main div.code-num {
	max-height: 400px;
	/*padding-bottom: 1.4em;*/
}
</style>
<script type="text/javascript">
function fnSelect(objId) {
	fnDeSelect();
	if (document.selection) {
	var range = document.body.createTextRange();
		range.moveToElementText(document.getElementById(objId));
	range.select();
	}
	else if (window.getSelection) {
	var range = document.createRange();
	range.selectNode(document.getElementById(objId));
	window.getSelection().addRange(range);
	}
}

function fnDeSelect() {
	if (document.selection) document.selection.empty();
	else if (window.getSelection)
		window.getSelection().removeAllRanges();
}
</script>
<?php
	}

	public static function register_topic_order() {
		add_filter( 'bbp_before_has_topics_parse_args', array(__CLASS__, 'has_topics_parse_args') );
	}

	public static function has_topics_parse_args($r) {
		if ( get_option('_bbpkr_topic_order_latest') ) {
			$r['orderby'] = NULL;
			$r['meta_key'] = NULL;
		}
		return $r;
	}

	public static function editor_args($args) {
		if ( !isset($args['context']) || $args['context'] == 'topic' ) {
			$settings = array(
				'media_buttons' => current_user_can('upload_files'),
				'textarea_rows' => (int) get_option('_bbpkr_textarea_rows', 20),
				'tinymce' => get_option('_bbpkr_topic_tinymce', true),
				'quicktags' => get_option('_bbpkr_topic_quicktags', true),
				'teeny' => true,
				// 'drag_drop_upload' => true,
			);
		} elseif ( isset($args['context']) && $args['context'] == 'reply' ) {
			$settings = array(
				'media_buttons' => false,
				'textarea_rows' => (int) get_option('_bbpkr_textarea_rows_reply', 6),
				'tinymce' => get_option('_bbpkr_reply_tinymce', false),
				'quicktags' => get_option('_bbpkr_reply_quicktags', false),
				'teeny' => true,
			);
		}

		$args = array_merge($settings, $args);// do not override given arguments

		return $args;
	}

	// fix:: wp_ajax_upload_attachment() : bypass current_user_can('edit_post', $post_id)
	// we are not editing current topic or page with bbpress shortcode and so on... ;-)
	public static function media_view_settings($settings, $post) {
		if ( !is_admin() && is_bbpress() && !bbp_is_edit() && !empty($settings['post']['id']) ) {
			$settings['post']['id'] = null;
			unset($settings['post']['nonce']);
		}
		return $settings;
	}

	public static function allowed_tags($tags) {
		global $allowedposttags;
		if ( is_array($allowedposttags) && !empty($allowedposttags) )
			return $allowedposttags;
		return $tags;
	}

	public static function mce_external_plugins($plugins) {
		$plugins['bbpcode'] = bbpresskr()->url . '/assets/js/tinymce/code/editor_plugin.js';
		return $plugins;
	}

	public static function teeny_mce_plugins($plugins) {
		$plugins['bbpcode'] = '-bbpcode';
		return $plugins;
	}

	public static function mce_buttons($buttons) {
		array_push( $buttons, 'bbpcode' );
		return $buttons;
	}

	public static function after_wp_tiny_mce() {
?>
<script type="text/javascript">
/* <![CDATA[ */
tinyMCEPreInit.load_ext("<?php echo bbpresskr()->url ?>/assets/js/tinymce/code", "en");
tinymce.PluginManager.load("bbpcode", "<?php echo bbpresskr()->url ?>/assets/js/tinymce/code/editor_plugin.js");
/* ]]> */
</script>
<?php
	}

}

Editor::init();
