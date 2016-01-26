<?php
/**
 * Forum General Settings
 * forum skin
 * date format, time format
 * 
 * @package bbPressKR
 * @subpackage Admin
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 */

if ( !defined('BBPKR_PATH') ) die('HACK');

extract( $options );
?>

<script type="text/javascript">
//<![CDATA[
	jQuery(document).ready(function($){
		console.log( $("input[name='bbpkr_options[date_format]']").length );
		$("input[name='bbpkr_options[date_format]']").click(function(){
			if ( "date_format_custom_radio" != $(this).attr("id") )
				$("input[name='bbpkr_options[date_format_custom]']").val( $(this).val() ).siblings('.example').text( $(this).siblings('span').text() );
		});
		$("input[name='bbpkr_options[date_format_custom]']").focus(function(){
			$( '#date_format_custom_radio' ).prop( 'checked', true );
		});

		$("input[name='bbpkr_options[time_format]']").click(function(){
			if ( "time_format_custom_radio" != $(this).attr("id") )
				$("input[name='bbpkr_options[time_format_custom]']").val( $(this).val() ).siblings('.example').text( $(this).siblings('span').text() );
		});
		$("input[name='bbpkr_options[time_format_custom]']").focus(function(){
			$( '#time_format_custom_radio' ).prop( 'checked', true );
		});
		$("input[name='bbpkr_options[date_format_custom]'], input[name='bbpkr_options[time_format_custom]']").change( function() {
			var format = $(this);
			format.siblings('.spinner').css('display', 'inline-block'); // show(); can't be used here
			$.post(ajaxurl, {
					action: 'bbpkr_options[date_format_custom]' == format.attr('name') ? 'date_format' : 'time_format',
					date : format.val()
				}, function(d) { format.siblings('.spinner').hide(); format.siblings('.example').text(d); } );
		});

		$('#bbpkr_custom_settings').change(function(){
			if ( $(this).is(':checked') ) {
				$('#bbpkr-general-settings').show();
			} else {
				$('#bbpkr-general-settings').hide();
			}
		});

	});
//]]>
</script>

<div id="bbpkr-general-settings-wrap">

	<p>
		<strong class="label" style="width: 160px;"><?php _e('Custom Settings:', 'bbpresskr') ?></strong>
		<label for="bbpkr_custom_settings" class="screen-reader-text"><?php _e('Custom Permission:', 'bbpresskr') ?></label>
		<input type="checkbox" id="bbpkr_custom_settings" name="bbpkr_custom_settings" value="1"<?php checked($custom_settings); ?> />
	</p>


	<div id="bbpkr-general-settings"<?php echo $custom_settings ? '' : ' style="display:none;"'; ?>>

	<p>
	<strong><?php _e('Date Format', 'bbpresskr') ?></strong><br />
<?php
	$date_formats = array( __( 'F j, Y' ), 'Y-m-d', 'Y/m/d', 'Y.m.d' );

	$custom = true;

	foreach ( $date_formats as $format ) {
		echo "\t<label title='" . esc_attr($format) . "'><input type='radio' name='bbpkr_options[date_format]' value='" . esc_attr($format) . "'";
		if ( $date_format === $format ) { // checked() uses "==" rather than "==="
			echo " checked='checked'";
			$custom = false;
		}
		echo ' /> <span>' . date_i18n( $format ) . "</span></label><br />\n";
	}

	echo '	<label><input type="radio" name="bbpkr_options[date_format]" id="date_format_custom_radio" value="\c\u\s\t\o\m"';
	checked( $custom );
	echo '/> ' . __('Custom:') . ' </label><input type="text" name="bbpkr_options[date_format_custom]" value="' . esc_attr( $date_format ) . '" class="small-text" /> <span class="example"> ' . date_i18n( $date_format ) . "</span> <span class='spinner'></span>\n";
?>
	</p>

	<p>
	<strong><?php _e('Time Format', 'bbpresskr') ?></strong><br />
<?php
	/**
	* Filter the default time formats.
	*
	* @since 2.7.0
	*
	* @param array $default_time_formats Array of default time formats.
	*/
	$time_formats = array( __( 'g:i a' ), 'g:i A', 'H:i' );

	$custom = true;

	foreach ( $time_formats as $format ) {
		echo "\t<label title='" . esc_attr($format) . "'><input type='radio' name='bbpkr_options[time_format]' value='" . esc_attr($format) . "'";
		if ( $time_format === $format ) { // checked() uses "==" rather than "==="
			echo " checked='checked'";
			$custom = false;
		}
		echo ' /> <span>' . date_i18n( $format ) . "</span></label><br />\n";
	}

	echo '	<label><input type="radio" name="bbpkr_options[time_format]" id="time_format_custom_radio" value="\c\u\s\t\o\m"';
	checked( $custom );
	echo '/> ' . __('Custom:') . ' </label><input type="text" name="bbpkr_options[time_format_custom]" value="' . esc_attr( $time_format ) . '" class="small-text" /> <span class="example"> ' . date_i18n( $time_format ) . "</span> <span class='spinner'></span>\n";
	echo "\t</p>";

	echo "\t<p>" . __('<a href="http://codex.wordpress.org/Formatting_Date_and_Time">Documentation on date and time formatting</a>.') . "</p>\n";
?>

	<p>
	<strong><?php _e('Topics per page:', 'bbpresskr'); ?></strong>
	<label for="bbpkr_options_posts_per_page" class="screen-reader-text"><?php _e('Use comments instead of replies:', 'bbpresskr') ?></label>
	<input type="text" class="small-text" id="bbpkr_options_posts_per_page" name="bbpkr_options[posts_per_page]" value="<?php echo $posts_per_page; ?>" />
	</p>

	<p>
	<strong><?php _e('Use comments instead of replies:', 'bbpresskr') ?></strong>
	<label for="bbpkr_options_use_comments" class="screen-reader-text"><?php _e('Use comments instead of replies:', 'bbpresskr') ?></label>
	<input type="checkbox" id="bbpkr_options_use_comments" name="bbpkr_options[use_comments]" value="1"<?php checked($use_comments); ?> />
	</p>

	<p>
	<strong><?php _e('Style:', 'bbpresskr') ?></strong>
	<select name="bbpkr_options[skin]" id="bbpkr_options_type">
	<?php foreach ( $styles as $style ) { ?>
		<option value="<?php echo $style->name ?>"><?php echo $style->label ?></option>
	<?php } ?>
	</select>
	</p>

	<p>
	<strong><?php _e('Forum Moderators:', 'bbpresskr') ?></strong>
	<label for="bbpkr_options_moderators" class="screen-reader-text"><?php _e('Forum Moderators:', 'bbpresskr') ?></label>
	<input type="text" class="small-text" id="bbpkr_options_moderators" name="bbpkr_options[moderators]" value="<?php echo implode(',', $moderators); ?>" />
	</p>

	</div>

</div>
