<?php
/**
 * Froum admin interface for bbPressKR
 *
 * @package bbPressKR
 * @subpackage Admin Forum
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 */

namespace bbPressKR\Admin;

use bbPressKR\View as View;

if ( !defined('BBPKR_PATH') ) die('HACK');

class Forum {

	static function init() {
		// add_action( 'bbprk_admin_setup_actions', array( __CLASS__, 'setup_actions' ) );
		self::setup_actions();
	}

	static function setup_actions() {
		add_action( 'add_meta_boxes_' . bbp_get_forum_post_type(), array( __CLASS__, 'add_meta_boxes_forum') );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );

		add_action( 'save_post_' . bbp_get_forum_post_type(), array( __CLASS__, 'save' ), 10, 3 );
	}

	static function add_meta_boxes_forum() {
		add_meta_box( 'bbppermission', __('Forum Permissions', 'bbpresskr'), array( __CLASS__, 'metabox_forum_permission'), bbp_get_forum_post_type(), 'normal', 'low' );
		add_meta_box( 'bbpgeneral', __('General Settings', 'bbpresskr'), array( __CLASS__, 'meta_box_forum_general'), bbp_get_forum_post_type(), 'side', 'high' );
	}

	static function save( $post_ID, $post, $update ) {
		self::save_perms($post_ID);
		self::save_options($post_ID);
	}

	protected static function save_perms( $post_ID ) {
		if ( isset( $_POST['bbpkr_save_perms']) ) {
			$new = !empty( $_POST['bbpkr_roles'] ) ? (array) $_POST['bbpkr_roles'] : array();
			update_post_meta( $post_ID, 'bbpkr_perms', $new );
			update_post_meta( $post_ID, 'bbpkr_custom_perm', isset($_POST['bbpkr_custom_perm']) );
		}
	}

	protected static function save_options( $post_ID ) {
		if ( isset( $_POST['bbpkr_options'] ) ) {
			$new = !empty( $_POST['bbpkr_options'] ) ? (array) $_POST['bbpkr_options'] : array();
			$old = get_post_meta( $post_ID, 'bbpkr_options', true );

			update_post_meta( $post_ID, 'bbpkr_custom_settings', isset($_POST['bbpkr_custom_settings']) );


			if ( !empty($new['date_format']) && isset($new['date_format_custom']) && '\c\u\s\t\o\m' == wp_unslash( $new['date_format'] ) )
				$new['date_format'] = $new['date_format_custom'];
			if ( !empty($new['time_format']) && isset($new['time_format_custom']) && '\c\u\s\t\o\m' == wp_unslash( $new['time_format'] ) )
				$new['time_format'] = $new['time_format_custom'];
			if ( isset( $new['date_format_custom'] ) ) {
				unset( $new['date_format_custom'] );
			}

			$new['posts_per_page'] = intval( $new['posts_per_page'] );
			if ( !$new['posts_per_page'] ) {
				unset( $new['posts_per_page'] );
			}

			// TODO: Currently supports asign moderators only. We could asign other roles soon.
			$new['moderators'] = explode(',', $new['moderators']);
			$new['moderators'] = array_filter( array_map('intval', $new['moderators'] ) );

			/*$to_remove = array_diff( $old['moderators'], $new['moderators'] );
			$to_add = array_diff( $new['moderators'], $old['moderators'] );

			if ( $to_remove ) {
				foreach ( $to_remove as $rem ) {
					remove_user_meta( $rem, "forum_role_{$post_ID}" );
				}
			}

			if ( $to_add ) {
				foreach ( $to_add as $add ) {
					update_user_meta( $rem, "forum_role_{$post_ID}", bbp_get_moderator_role() );
				}
			}*/

			update_post_meta( $post_ID, 'bbpkr_options', $new );
		}
	}

	static function metabox_forum_permission($post) {
		global $gdbbpress_attachments;
		$perms = array(
				'read' => __('Read', 'bbpresskr'),
				'write' => __('Write', 'bbpresskr'),
				'reply' => __('Reply', 'bbpresskr'),
				'upload' => __('Upload', 'bbpresskr'),
				'download' => __('Download', 'bbpresskr'),
				);

		echo View::factory( 'admin/forum-permission', array(
			'options' => bbpresskr()->forum_options($post->ID),
			'_user_roles' => bbpresskr()->get_user_roles(),
			'forum_perms' => get_post_meta( $post->ID, 'bbpkr_perms', true ),
			'custom_perm' => get_post_meta( $post->ID, 'bbpkr_custom_perm', true ),
			'perms' => $perms
			)
		);
		add_action( 'admin_print_footer_scripts', array(__CLASS__, 'admin_footer_forum'), 30 );
	}

	static function meta_box_forum_general($post) {
		// TODO: Skins API...
		$styles = array( 'default' => (object) array( 'core' => true, 'name' => 'default', 'label' => __('Default', 'bbpresskr') ) );
		$styles = apply_filters( 'bbpkr_forum_skins', $styles );
		$options = bbpresskr()->forum_options($post->ID);
		$custom_settings = get_post_meta( $post->ID, 'bbpkr_custom_settings', true );

		echo View::factory( 'admin/forum-settings', compact( 'styles', 'options', 'custom_settings' ) );
	}

	static function admin_enqueue_scripts() {
		return;
		global $pagenow;
		if ( !in_array( $pagenow, array('post.php', 'post-new.php' ) ) || get_post_type() != bbp_get_forum_post_type() )
			return;
		wp_enqueue_style( 'bbpkr-admin', bbpresskr()->url . '/assets/css/admin.css', false, bbpresskr()->ver );
		wp_enqueue_script( 'bbpkr-admin', bbpresskr()->url . '/assets/js/admin.js', array('jquery-ui-tabs'), bbpresskr()->ver, true );
	}

	static function admin_footer_forum() {
		global $hook_suffix, $pagenow;

		if ( !in_array( $pagenow, array('post.php', 'post-new.php' ) ) || get_post_type() != bbp_get_forum_post_type() )
			return;

		echo View::factory( 'admin/css.php' );
		echo View::factory( 'admin/js.php' );
	}

}
