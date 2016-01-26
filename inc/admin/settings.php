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

			'_bbpkr_disable_autop' => array(
				'title'             => __( 'Disable autoP', 'bbpresskr' ),
				'callback'          => array( __CLASS__, 'callback_disable_autop' ),
				'sanitize_callback' => 'intval',
				'args'              => array()
			),

			/*'_bbpkr_media_buttons' => array(
				'title'             => __( 'WordPress media button', 'bbpress' ),
				'callback'          => array( __CLASS__, 'callback_media_buttons' ),
				'sanitize_callback' => 'intval',
				'args'              => array()
			),*/

			'_bbpkr_textarea_rows' => array(
				'title'             => __( 'Editor rows', 'bbpresskr' ),
				'callback'          => array( __CLASS__, 'callback_textarea_rows' ),
				'sanitize_callback' => 'intval',
				'args'              => array()
			),

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

			'_bbpkr_show_recent_topics' => array(
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

	static function callback_disable_autop() {
?>

	<input name="_bbpkr_disable_autop" id="_bbpkr_disable_autop" type="checkbox" value="1" <?php checked( get_option('_bbpkr_disable_autop') ); ?> />
	<label for="_bbpkr_disable_autop"><?php esc_html_e( 'Stop adding the &lt;p&gt; and &lt;br /&gt; tags when saving topics', 'bbpresskr' ); ?></label>

<?php
	}

	static function callback_textarea_rows() {
?>

	<input name="_bbpkr_textarea_rows" id="_bbpkr_textarea_rows" type="number" min="5" step="1" value="<?php bbp_form_option( '_bbpkr_textarea_rows', 20 ); ?>" class="small-text" />
	<label for="_bbpkr_textarea_rows"><?php esc_html_e( 'rows', 'bbpresskr' ); ?></label>

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

	<input name="_bbpkr_show_recent_topics" id="_bbpkr_show_recent_topics" type="checkbox" value="1" <?php checked( get_option('_bbpkr_show_recent_topics') ); ?> />
	<label for="_bbpkr_show_recent_topics"><?php esc_html_e( 'Order topics by the latest, not freshness', 'bbpresskr' ); ?></label>

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

