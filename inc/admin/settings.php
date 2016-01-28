<?php
/**
 * @package bbPressKR
 * @subpackage Admin Settings
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 */

namespace bbPressKR\Admin;

use bbPressKR\Attachments as Attachments;

use bbPressKR;

if ( !defined('BBPKR_PATH') ) die('HACK');

class Settings {

	static function init() {
		// add_filter( 'bbp_admin_get_settings_sections', array( __CLASS__, 'settings_sections' ) );
		add_filter( 'bbp_admin_get_settings_fields', array( __CLASS__, 'settings_fields' ) );
		// add_filter( 'bbp_map_settings_meta_caps', array( __CLASS__, 'settings_meta_caps' ), 10, 2 );
	}

	static function settings_meta_caps( $caps, $cap ) {
		switch ( $cap ) {
			default:
			$caps = array( bbpress()->admin->minimum_capability );
			break;
		}

		return $caps;
	}

	static function settings_fields( $fields ) {
		$features = array(

			'_bbpkr_topic_editor_settings' => array(
				'title'							=> __( 'Topic Editor settings', 'bbpresskr' ),
				'callback'					=> array(__CLASS__, 'callback_editor_settings'),
				'sanitize_callback'	=> array(__CLASS__, 'sanitize_editor_settings'),
				'args'							=> 'topic',
			),

			'_bbpkr_reply_editor_settings' => array(
				'title'							=> __( 'Rely editor settings', 'bbpresskr' ),
				'callback'					=> array(__CLASS__, 'callback_editor_settings'),
				'sanitize_callback'	=> array(__CLASS__, 'sanitize_editor_settings'),
				'args'							=> 'reply',
			),

			/*'_bbpkr_disable_autop' => array(
				'title'             => __( 'Disable autoP', 'bbpresskr' ),
				'callback'          => array( __CLASS__, 'callback_disable_autop' ),
				'sanitize_callback' => 'intval',
				'args'              => array()
			),*/

			/*'_bbpkr_media_buttons' => array(
				'title'             => __( 'WordPress media button', 'bbpress' ),
				'callback'          => array( __CLASS__, 'callback_media_buttons' ),
				'sanitize_callback' => 'intval',
				'args'              => array()
			),*/

			/*'_bbpkr_textarea_rows' => array(
				'title'             => __( 'Editor rows', 'bbpresskr' ),
				'callback'          => array( __CLASS__, 'callback_textarea_rows' ),
				'sanitize_callback' => 'intval',
				'args'              => array()
			),*/

			/*'_bbpkr_tinymce' => array(
				'title'             => __( 'Use visual editor', 'bbpresskr' ),
				'callback'          => array( __CLASS__, 'callback_tinymce' ),
				'sanitize_callback' => 'intval',
				'args'              => array()
			),*/

			/*'_bbpkr_quicktags' => array(
				'title'             => __( 'Show text editor', 'bbpresskr' ),
				'callback'          => array( __CLASS__, 'callback_quicktags' ),
				'sanitize_callback' => 'intval',
				'args'              => array()
			),*/

			'_bbpkr_more_html_tags' => array(
				'title'             => __( 'More HTML tags', 'bbpresskr' ),
				'callback'          => array( __CLASS__, 'callback_more_html_tags' ),
				'sanitize_callback' => 'intval',
				'args'              => array()
			),

			'_bbpkr_topic_order_latest' => array(
				'title'             => __( 'Topics order by date', 'bbpresskr' ),
				'callback'          => array( __CLASS__, 'callback_show_recent_topics' ),
				'sanitize_callback' => 'intval',
				'args'              => array()
			),

			'_bbpkr_upload_perms' => array(
				'title'             => __( 'Upload Permission', 'bbpresskr' ),
				'callback'          => array( __CLASS__, 'callback_upload_perms' ),
				'sanitize_callback' => 'array_filter',
				'args'              => array()
			),

			'_bbpkr_download_perms' => array(
				'title'             => __( 'Download Permission', 'bbpresskr' ),
				'callback'          => array( __CLASS__, 'callback_download_perms' ),
				'sanitize_callback' => 'array_filter',
				'args'              => array()
			),

		);

		$fields['bbp_settings_features'] = array_merge( $fields['bbp_settings_features'], $features );

		return $fields;
	}

	static function sanitize_editor_settings($value) {
		$value['textarea_rows'] = (int) $value['textarea_rows'];
		foreach ( array('tinymce', 'quicktags', 'teeny') as $chk ) {
			if ( !isset($value[$chk]) )
				$value[$chk] = false;
		}
		return $value;
	}

	static function field_id($option, $type='topic', $echo=true) {
		$name = "_bbpkr_{$type}_editor_settings";
		$retval = $name . '_' . $option;
		if ( $echo )
			echo $retval;
		return $retval;
	}

	static function field_name($option, $type='topic', $echo=true) {
		$name = "_bbpkr_{$type}_editor_settings";
		$retval = $name . '[' . $option . ']';
		if ( $echo )
			echo $retval;
		return $retval;
	}

	static function callback_editor_settings($type = 'topic') {
		$settings = (array) get_option("_bbpkr_{$type}_editor_settings", bbPressKr\Editor::defaults($type));
		?>
	<p>
		<label for="<?php self::field_id('textarea_rows', $type) ?>"><?php _e( 'Editor rows:', 'bbpresskr' ) ?></label>
		<input name="<?php self::field_name('textarea_rows', $type) ?>" id="<?php self::field_id('textarea_rows', $type) ?>" type="number" min="5" step="1" value="<?php echo (int) $settings['textarea_rows']; ?>" class="small-text" />
		<span><?php esc_html_e( 'rows', 'bbpresskr' ); ?></span>
	</p>

	<p>
		<label for="<?php self::field_id('tinymce', $type) ?>"><?php _e( 'Use visual editor:', 'bbpresskr' ) ?></label>
		<input name="<?php self::field_name('tinymce', $type) ?>" id="<?php self::field_id('tinymce', $type) ?>" type="checkbox" value="1" <?php checked( $settings['tinymce'] ); ?> />
		<span class="description"><?php esc_html_e( '(Show visual editor[TinyMCE] durning write topic)', 'bbpresskr' ); ?></span>
	</p>

	<p>
		<label for="<?php self::field_id('teeny', $type) ?>"><?php _e( 'Teeny buttons:', 'bbpresskr' ) ?></label>
		<input name="<?php self::field_name('teeny', $type) ?>" id="<?php self::field_id('teeny', $type) ?>" type="checkbox" value="1" <?php checked( $settings['teeny'] ); ?> />
		<span class="description"><?php esc_html_e( '(Teeny buttons on visual editor)', 'bbpresskr' ); ?></span>
	</p>

	<p>
		<label for="<?php self::field_id('quicktags', $type) ?>"><?php _e( 'Use text editor:', 'bbpresskr' ) ?></label>
		<input name="<?php self::field_name('quicktags', $type) ?>" id="<?php self::field_id('quicktags', $type) ?>" type="checkbox" value="1" <?php checked( $settings['quicktags'] ); ?> />
		<span class="description"><?php esc_html_e( '(Show visual editor durning write reply)', 'bbpresskr' ); ?></span>
	</p>
		<?php
	}

	static function callback_disable_autop() {
?>
	<input name="_bbpkr_disable_autop" id="_bbpkr_disable_autop" type="checkbox" value="1" <?php checked( get_option('_bbpkr_disable_autop') ); ?> />
	<label for="_bbpkr_disable_autop"><?php esc_html_e( 'Stop adding the &lt;p&gt; and &lt;br /&gt; tags when saving topics', 'bbpresskr' ); ?></label>

<?php
	}

	static function callback_more_html_tags() {
?>

	<input name="_bbpkr_more_html_tags" id="_bbpkr_more_html_tags" type="checkbox" value="1" <?php checked( get_option('_bbpkr_more_html_tags') ); ?> />
	<label for="_bbpkr_more_html_tags"><?php esc_html_e( 'Allow HTML tags for topics same as posts', 'bbpresskr' ); ?></label>

<?php
	}

	static function callback_show_recent_topics() {
?>

	<input name="_bbpkr_topic_order_latest" id="_bbpkr_topic_order_latest" type="checkbox" value="1" <?php checked( get_option('_bbpkr_topic_order_latest') ); ?> />
	<label for="_bbpkr_topic_order_latest"><?php esc_html_e( 'Order topics by the latest, not freshness', 'bbpresskr' ); ?></label>

<?php
	}

	static function callback_upload_perms() {
		self::callback_attachment_perms('upload', Attachments::$upload_perms);
	}

	static function callback_download_perms() {
		self::callback_attachment_perms('download', Attachments::$download_perms);
	}

	static function callback_attachment_perms( $key, $default = array() ) {
		$_user_roles = bbpresskr()->get_user_roles();
		$allowed = (array) get_option( "_bbpkr_{$key}_perms", $default );
		$field_name = "_bbpkr_{$key}_perms[]";
?>
	<div id="bbp-perms-<?php echo $key ?>">
		<strong class="screen-reader-text"><?php printf( __('Allow %s to:', 'bbpresskr'), $key ); ?></strong>
		<ul>
		<?php
		foreach ($_user_roles as $role => $roledata) {
			if ( $role == 'bbpkr_anonymous' && in_array($key, array('upload'/*, 'download'*/)) )
				continue;
			$field_id = "_bbpkr_{$key}_perms_{$role}";
			$title = $roledata['name'];
			?>
			<li>
				<label for="<?php echo $field_id; ?>">
					<input type="checkbox" value="<?php echo $role; ?>" id="<?php echo $field_id; ?>" name="<?php echo $field_name; ?>"<?php checked(in_array($role, $allowed)) ?> />
					<?php echo $title; ?>
				</label>
			</li>
		<?php } ?>
		</ul>
	</div>
<?php
	}

}

