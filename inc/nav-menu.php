<?php
/**
 * @package bbPressKR
 * @subpackage Nav Menu
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 */

namespace bbPressKR;

class Nav_Menu {

  static function init() {
    self::setup_actions();
  }

  static function setup_actions() {
    add_filter( 'nav_menu_css_class', array(__CLASS__, 'nav_menu_css_class'), 10, 4 );
  }

  static function nav_menu_css_class( $classes, $item, $args, $depth ) {
    if ( $item->object == bbp_get_forum_post_type() && is_bbpress() ) {
      if ( bbp_is_topic_edit() || bbp_is_single_topic() ) {
        $forum_id = bbp_get_topic_forum_id();
        if ( $forum_id == $item->object_id ) {
          $classes[] = 'current-menu-parent';
          $classes[] = 'current-' . bbp_get_topic_post_type() . '-parent';
        } elseif ( ($ancestors = self::forum_ancestors($forum_id, array())) ) {
          if ( in_array($item->object_id, $ancestors) ) {
          $classes[] = 'current-menu-ancestor';
          $classes[] = 'current-' . bbp_get_topic_post_type() . '-ancestor';
          }
        }
      } elseif ( bbp_is_reply_edit() || bbp_is_single_reply() ) {
        $forum_id = bbp_get_reply_forum_id();
      }
    }
    return $classes;
  }

  static function forum_ancestors( $forum_id, Array $forums ) {
      // follow closest parent forum custom perms
      $forum_parent = (int) get_post_field( 'post_parent', $forum_id );
      if ( $forum_parent )
        return self::forum_ancestors($forum_parent, array_merge($forums, array($forum_parent)));
      return $forums;
    return $forum_id;
  }


}
