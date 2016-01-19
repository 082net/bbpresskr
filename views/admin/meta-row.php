<?php
/**
 * New meta fields form
 *
 * @package bbPressKR
 * @subpackage Admin
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 */
?>
<tr id='bbpmeta-<?php echo "{$mid}" ?>' class='<?php echo $style ?>'>

	<!-- <td><i class='dashicons dashicons-menu hndle'></i></td> -->
	<td class="column-order">
		<!-- <span class='hndle bbpmeta-order'><?php echo $order; ?></span> -->
		<i class="dashicons dashicons-menu hndle"></i>
		<input type="hidden" name="bbpmeta[<?php echo $mid ?>][order]" id="bbpmeta-<?php echo $mid ?>-order" value="<?php echo $order; ?>" />
	</td>
	<td class='left'>
		<label class='screen-reader-text' for='bbpmeta-<?php echo $mid ?>-key'><?php _e( 'Key', 'bbpresskr' ) ?></label>
		<input class='widefat' name='bbpmeta[<?php echo $mid ?>][key]' id='bbpmeta-<?php echo $mid ?>-key' type='text' value='<?php echo $mkey ?>' />

		<div class='submit'>
		<?php submit_button( __( 'Delete', 'bbpresskr' ), 'deletebbpmeta small', "deletebbpmeta[{$mid}]", false, array( 'data-wp-lists' => "delete:the-list-bbpmeta:bbpmeta-{$mid}::_ajax_nonce=$delete_nonce" ) ); ?>

		<?php submit_button( __( 'Update', 'bbpresskr' ), 'updatebbpmeta small', "bbpmeta-{$mid}-submit", false, array( 'data-wp-lists' => "add:the-list-bbpmeta:bbpmeta-{$mid}::_ajax_nonce-add-bbpmeta=$update_nonce" ) ); ?>

		 <input type="button" class="button button-bbpmcf button-small" data-mid="<?php echo $mid ?>" value="<?php _e( 'Customize', 'bbpresskr' ) ?>" />
		 <input type="hidden" name="bbpmeta[<?php echo $mid ?>][type]" id='bbpmeta-<?php echo $mid ?>-type' value="<?php echo $type; ?>" />
		 <input type="hidden" name="bbpmeta[<?php echo $mid ?>][options]" id='bbpmeta-<?php echo $mid ?>-options' value="<?php echo json_encode($options); ?>" />
		</div>

		<?php wp_nonce_field( 'change-bbpmeta', '_ajax_nonce', false ); ?>
	</td>

	<td>
		<label class='screen-reader-text' for='bbpmeta-<?php echo $mid ?>-label'><?php _e( 'Label', 'bbpresskr' ) ?></label>
		<input type='text' class='widefat' name='bbpmeta[<?php echo $mid ?>][label]' id='bbpmeta-<?php echo $mid ?>-label' value='<?php echo $mlabel ?>' />
	</td>

	<td>
		<label class='screen-reader-text' for='bbpmeta-<?php echo $mid ?>-list'><?php _e( 'List', 'bbpresskr' ) ?></label>
		<input type='checkbox' name='bbpmeta[<?php echo $mid ?>][list]' id='bbpmeta-<?php echo $mid ?>-list' value='1'<?php checked($list, true) ?> />
	</td>

	<?php /* ?><td>
		<label class='screen-reader-text' for='bbpmeta-<?php echo $mid ?>-adminlist'><?php _e( 'Admin List', 'bbpresskr' ) ?></label>
		<input type='checkbox' name='bbpmeta[<?php echo $mid ?>][adminlist]' id='bbpmeta-<?php echo $mid ?>-adminlist' value='1'<?php checked($list, true) ?> />
	</td><?php */ ?>

	<td>
		<label class='screen-reader-text' for='bbpmeta-<?php echo $mid ?>-single'><?php _e( 'Single', 'bbpresskr' ) ?></label>
		<select name='bbpmeta[<?php echo $mid ?>][single]' id='bbpmeta-<?php echo $mid ?>-single'>
			<option value=''><?php _e('None', 'bbpresskr') ?></option>
			<option value='top'<?php selected($single, 'top') ?>><?php _e('Top', 'bbpresskr') ?></option>
			<option value='bottom'<?php selected($single, 'bottom') ?>><?php _e('Bottom', 'bbpresskr') ?></option>
		</select>
	</td>

	<td>
		<label class='screen-reader-text' for='bbpmeta-<?php echo $mid ?>-req'><?php _e( 'Required', 'bbpresskr' ) ?></label>
		<input type='checkbox' name='bbpmeta[<?php echo $mid ?>][req]' id='bbpmeta-<?php echo $mid ?>-req' value='1'<?php checked($req, true) ?> />
	</td>

	<!-- <td>
		<label class='screen-reader-text' for='bbpmeta-<?php echo $mid ?>-type'><?php _e( 'Type', 'bbpresskr' ) ?></label>
		<select name='bbpmeta[<?php echo $mid ?>][type]' id='bbpmeta-<?php echo $mid ?>-type'>
			<option value=''><?php _e('Text', 'bbpresskr') ?></option>
			<option value='textarea'<?php selected($single, 'textarea') ?>><?php _e('Textarea', 'bbpresskr') ?></option>
			<option value='email'<?php selected($single, 'email') ?>><?php _e('Email', 'bbpresskr') ?></option>
			<option value='url'<?php selected($single, 'url') ?>><?php _e('URL', 'bbpresskr') ?></option>
			<option value='checkbox'<?php selected($single, 'checkbox') ?>><?php _e('Checkbox', 'bbpresskr') ?></option>
			<option value='radio'<?php selected($single, 'radio') ?>><?php _e('Radio', 'bbpresskr') ?></option>
			<option value='select'><?php _e('Select', 'bbpresskr') ?></option>
			<option value='multiselect'><?php _e('Select Multiple', 'bbpresskr') ?></option>
		</select>
	</td> -->
		<!--
		datetime, date, email, url, number, radio, select, textarea,
		 -->

</tr>
