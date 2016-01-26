<?php
/**
 * bbPressKR functions compat
 * 
 * @package bbPressKR
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 * 
 */

/*if ( !function_exists('submit_button') ):
function submit_button( $text = null, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = null ) {
	echo get_submit_button( $text, $type, $name, $wrap, $other_attributes );
}
endif;*/

/*if ( !function_exists('get_submit_button') ):
function get_submit_button( $text = null, $type = 'primary large', $name = 'submit', $wrap = true, $other_attributes = null ) {
	if ( ! is_array( $type ) )
		$type = explode( ' ', $type );

	$button_shorthand = array( 'primary', 'small', 'large' );
	$classes = array( 'button' );
	foreach ( $type as $t ) {
		if ( 'secondary' === $t || 'button-secondary' === $t )
			continue;
		$classes[] = in_array( $t, $button_shorthand ) ? 'button-' . $t : $t;
	}
	$class = implode( ' ', array_unique( $classes ) );

	if ( 'delete' === $type )
		$class = 'button-secondary delete';

	$text = $text ? $text : __( 'Save Changes' );

	// Default the id attribute to $name unless an id was specifically provided in $other_attributes
	$id = $name;
	if ( is_array( $other_attributes ) && isset( $other_attributes['id'] ) ) {
		$id = $other_attributes['id'];
		unset( $other_attributes['id'] );
	}

	$attributes = '';
	if ( is_array( $other_attributes ) ) {
		foreach ( $other_attributes as $attribute => $value ) {
			$attributes .= $attribute . '="' . esc_attr( $value ) . '" '; // Trailing space is important
		}
	} else if ( !empty( $other_attributes ) ) { // Attributes provided as a string
		$attributes = $other_attributes;
	}

	$button = '<input type="submit" name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '" class="' . esc_attr( $class );
	$button	.= '" value="' . esc_attr( $text ) . '" ' . $attributes . ' />';

	if ( $wrap ) {
		$button = '<p class="submit">' . $button . '</p>';
	}

	return $button;
}
endif;*/

if ( !function_exists('themes_url') ):
function themes_url($path = '', $template = false, $scheme = null) {
	if ( $template )
		$url = get_template_directory_uri();
	else
		$url = get_stylesheet_directory_uri();

	if ( !in_array( $scheme, array( 'http', 'https' ) ) )
		$scheme = is_ssl() && !is_admin() ? 'https' : 'http';

	if ( 'http' != $scheme )
		$url = str_replace( 'http://', "$scheme://", $url );

	if ( !empty( $path ) && is_string( $path ) && strpos( $path, '..' ) === false )
		$url .= '/' . ltrim( $path, '/' );

	return $url;
}
endif;
if ( !function_exists('wp_rel_url') ):
function wp_rel_url( $path = '', $file = '', $scheme = null ) {
	$file = wp_normalize_path( dirname($file) );
	if ( '' == $file )
		$url = site_url();
	elseif ( 0 === strpos( $file, wp_normalize_path( WP_CONTENT_DIR ) ) )
		$url = content_url( str_replace( wp_normalize_path( WP_CONTENT_DIR ), '', $file ) );
	elseif ( 0 === strpos( $file, wp_normalize_path( ABSPATH ) ) )
		$url = site_url( str_replace( wp_normalize_path( ABSPATH ), '', $file ) );
	elseif ( 0 === strpos( $file, wp_normalize_path( WP_PLUGIN_DIR ) ) || 0 === strpos( $file, wp_normalize_path( WPMU_PLUGIN_DIR ) ) )
		$url = plugins_url( basename( $file ), $file );
	else
		$url = $file;

	if ( !in_array( $scheme, array( 'http', 'https' ) ) )
		$scheme = is_ssl() && !is_admin() ? 'https' : 'http';

	if ( 'http' != $scheme )
		$url = str_replace( 'http://', "$scheme://", $url );

	if ( !empty( $path ) && is_string( $path ) && strpos( $path, '..' ) === false )
		$url .= '/' . ltrim( $path, '/' );
	return $url;
}
endif;
