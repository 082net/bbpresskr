<?php

class BBPKR_Default {

  private static $url;

  private static $fired;

  static function init() {
    if ( isset(self::$fired) )
      return;

    self::setup_globals();
    add_action('template_redirect', array(__CLASS__, 'setup_actions') );

    self::$fired = true;
  }

  private static function setup_globals() {
    self::$url = wp_rel_url('', __DIR__);
  }

  static function setup_actions() {
    add_action( 'wp_enqueue_scripts', array( __CLASS__, 'wp_enqueue_styles' ), 11 );
    add_action( 'wp_enqueue_scripts', array( __CLASS__, 'wp_enqueue_scripts' ), 11 );

    add_filter( 'bbp_default_styles', array( __CLASS__, 'bbp_default_styles') );

    add_filter( 'bbp_body_class', array( __CLASS__, 'body_class' ), 10, 2 );
  }

  public static function wp_enqueue_styles() {
    if ( wp_style_is( 'fontawesome' ) )
      $fontawesome = 'fontawesome';
    elseif ( wp_style_is( 'font-awesome' ) )
      $fontawesome = 'font-awesome';
    else {
      wp_register_style( 'font-awesome', self::$url . '/default/css/font-awesome/css/font-awesome.min.css', array(), '4.3' );
      $fontawesome = 'font-awesome';
    }

    wp_enqueue_style( $fontawesome );

    /*if ( wp_style_is( 'genericons' ) )
      wp_enqueue_style( 'genericons' );
    else
      wp_enqueue_style( 'genericons', self::$url . '/default/css/genericons/genericons.css', array(), '3.2' );*/
    // wp_enqueue_style( 'bbpresskr', )
  }

  public static function wp_enqueue_scripts() {

  }

  public static function bbp_default_styles( $styles ) {
    $styles['bbp-default'] = array(
      // 'file'         => 'css/bbpresskr.css',
      'file'         => 'css/default.css',
      'dependencies' => array('buttons')//array( 'bbp-default' )
    );
    return $styles;
  }

  public static function body_class( $classes, $bbp_classes ) {
    if ( in_array('bbpress', $bbp_classes) ) {
      $classes[] = 'bbpresskr';
    }
    return $classes;
  }

}

BBPKR_Default::init();
