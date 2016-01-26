<?php
/**
 * Forum permissions tab
 * read, write, reply, upload
 *
 * @package bbPressKR
 * @subpackage Admin
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 */

if ( !defined('BBPKR_PATH') ) die('HACK');

?>

<input type="hidden" name="bbpkr_save_perms" value="1" />

<p>
	<strong class="label" style="width: 160px;"><?php _e('Custom Permission:', 'bbpresskr') ?></strong>
	<label for="bbpkr_custom_perm" class="screen-reader-text"><?php _e('Custom Permission:', 'bbpresskr') ?></label>
	<input type="checkbox" id="bbpkr_custom_perm" name="bbpkr_custom_perm" value="1"<?php checked($custom_perm); ?> />
</p>

<!-- <p>
	<strong class="label" style="width: 160px;"><?php _e('Allow Anonymous:', 'bbpresskr') ?></strong>
	<label for="bbpkr_allow_anonymous" class="screen-reader-text"><?php _e('Allow Anonymous:', 'bbpresskr') ?></label>
	<input type="checkbox" id="bbpkr_allow_anonymous" name="bbpkr_allow_anonymous" value="1"<?php checked($allow_anonymous); ?> />
</p> -->

<div id="bbp-perms-tabs"<?php echo $custom_perm ? '' : ' style="display:none;"'; ?>>
	<ul class="ui-tabs-nav bbpkr-nav-tabs">
	<?php foreach ( $perms as $key => $name ) { ?>
		<li class="bbpkr-nav-tab"><a href="#bbp-perms-<?php echo $key ?>"><?php echo $name ?></a></li>
	<?php } ?>
	</ul>

	<?php foreach ( $perms as $key => $name ) { ?>
	<?php $selected = !empty( $forum_perms[$key] ) ? $forum_perms[$key] : array(); ?>
	<div id="bbp-perms-<?php echo $key ?>" class="bbpkr-tab-content">
		<strong class="screen-reader-text"><?php _e('Allow to', 'bbpresskr'); ?></strong>
		<ul>
		<?php
		foreach ($_user_roles as $role => $roledata) {
			if ( $role == 'bbpkr_anonymous' && in_array($key, array('upload'/*, 'download'*/)) )
				continue;
		$title = $roledata['name'];
		?>
		<li>
			<label for="bbpkr_roles_<?php echo "{$key}_{$role}"; ?>">
				<input type="checkbox" value="<?php echo $role; ?>" id="bbpkr_roles_<?php echo "{$key}_{$role}"; ?>" name="bbpkr_roles[<?php echo $key ?>][]"<?php checked(in_array($role, $selected)) ?> />
				<?php echo $title; ?>
			</label>
		</li>
		<?php } ?>
		</ul>
	</div>
	<?php } ?>

</div>

<script type="text/javascript">
(function($){
$('#bbpkr_custom_perm').on('change', function(){
	if ( $(this).is(':checked') ) {
		$('#bbp-perms-tabs').show();
	} else {
		$('#bbp-perms-tabs').hide();
	}
})
})(jQuery);
</script>
