<?php
/**
 * @package bbPressKR
 * @subpackage permissions
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 */

namespace bbPressKR;

class Attachments {

  static $defaults = array(
    'show_msgs' => true,
    'exclude_images_from_list' => false,
    'delete_visible_to_admins' => 'both',
    'delete_visible_to_moderators' => 'detach',
    'delete_visible_to_author' => 'detach',
  );

  static $failure_title;

  static $conf;

  static $upload_perms, $download_perms, $wp_download_level;

  static function init() {
    // self::includes();
    self::setup();
    self::setup_actions();
  }

  private static function includes() {
    if ( !class_exists('WP_DMGR') )
      require_once( BBPKR_LIB . '/download-mgr/download-mgr.php' );
  }

  private static function setup() {
    self::$failure_title = __('Failed to download', 'download-mgr');
    self::$upload_perms = array( bbp_get_participant_role() );
    self::$download_perms = array( bbp_get_participant_role() );
    self::$wp_download_level = 'public';

    self::$defaults['wrong_level_msg'] = __('Sorry, you don\'t have the right user level for downloads.', 'bbpresskr');
    self::$defaults['no_login_msg'] = sprintf( __('You must be a %s user and logged in to download.', 'bbpresskr'), get_option('blogname') );

    self::$conf = array_merge( self::$defaults, get_option('bbpkr_attachment_settings', array()) );
  }

  private static function setup_actions() {
    add_filter( 'ajax_query_attachments_args', array(__CLASS__, 'ajax_query_attachments_args' ) );
    add_filter( 'media_view_settings', array( __CLASS__, 'media_view_settings' ), 10, 2 );
    // upload permission
    add_filter( 'user_has_cap', array( __CLASS__, 'user_has_cap' ), 11, 4 );

    // add_filter('bbp_get_reply_content', array( __CLASS__, 'embed_attachments' ), 100, 2 );
    // add_filter('bbp_get_topic_content', array( __CLASS__, 'embed_attachments' ), 100, 2 );

    if ( isset($_GET['dl']) && sizeof($_GET) === 1 && is_numeric($_GET['dl']) )
      add_action( 'init', array( __CLASS__, 'download' ) );

    // add_filter('bbp_get_topic_content', array(__CLASS__, '_auto_append'), 8, 2);
    // add_filter('bbp_get_reply_content', array(__CLASS__, '_auto_append'), 8, 2);

    add_action('edit_attachment', array(__CLASS__, 'collect_attachments'));
    add_action('add_attachment', array(__CLASS__, 'collect_attachments'));
    // add_action('publish_post', array(__CLASS__, 'collect_attachments'));
    add_action('trashed_post', array(__CLASS__, 'collect_attachments'));
    add_action('untrashed_post', array(__CLASS__, 'collect_attachments'));

    add_action('delete_attachment', array(__CLASS__, 'delete_attachment'));

    if ( isset($_GET['bbpkraction']) )
      add_action('init', array(__CLASS__, 'delete_attachments'));

    add_shortcode('file', array(__CLASS__, '_content'));

    if ( is_admin() && strpos( $_SERVER['PHP_SELF'], 'wp-admin/upload.php' ) !== false ) {
      add_action('wp_redirect', array(__CLASS__, 'admin_collect_attachments'));
    }

  }

  static function ajax_query_attachments_args( $query ) {
    $forum_id = 0;
    $post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;

    if ( $post_id ) {
      $post_type = get_post_type( $post_id );
      if ( $post_type == bbp_get_topic_post_type() ) {
        $forum_id = (int) bbp_get_topic_forum_id( $post_id );
      } elseif ( $post_type == bbp_get_reply_post_type() ) {
        $forum_id = (int) bbp_get_reply_forum_id( $post_id );
      } elseif ( $post_type == bbp_get_forum_post_type() ) {
        $forum_id = $post_id;
      }
    }

    if ( $forum_id ) {
      // TODO: post_id 값이 전달되지 않을 때, 포럼을 찾는 방법을 알아봐야함.
      add_filter( 'bbp_get_forum_id', create_function('$a', 'return '. $forum_id.';') );
      if ( ! current_user_can( 'edit_others_topics' ) ) {
        $query['author'] = get_current_user_id();
      }

    }

    return $query;
  }

  public static function user_has_cap( $allcaps, $caps, $args, $user ) {
    if ( !in_array( 'upload_files', $caps ) ) {
      return $allcaps;
    }

    $can = isset($allcaps['upload_files']) ? $allcaps['upload_files'] : false;

    // async-upload.php, admin-ajax.php 를 통해서 업로드할 때 권한 체크가 어려움
    // 쿠키에 현재 포럼값이 저장되어 있을경우에는 진행
    $ajax_attachment_actions = apply_filters( 'bbpkr_ajax_attachment_actions', array( 'upload-attachment', 'query-attachments' ) );
    if (
      ! empty($_COOKIE['_bbp_forum_id'] ) &&
      empty( $allcaps['upload_files'] ) &&
      is_user_logged_in() &&
      ( defined( 'DOING_AJAX' ) && true === DOING_AJAX ) &&
        ( isset( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], $ajax_attachment_actions ) )
      ) {
      $forum_id = (int) $_COOKIE['_bbp_forum_id'];
      // unset post_id to avoid edit_post check( DO NOT UNSET $_POST['post_id'] )
      $_REQUEST['post_id'] = null;
      $allcaps['upload_files'] = self::can_upload($can, $forum_id);
      return $allcaps;
    }

    $allcaps['upload_files'] = self::can_upload($can);

    return $allcaps;
  }

  public static function can_upload($can = false, $forum_id = 0) {
    $forum_id = bbp_get_forum_id( $forum_id );
    if ( ! $forum_id )
      return $can;

    if ( current_user_can( 'moderate' ) )
      return true;

    $allowed_roles = (array) get_option( '_bbpkr_upload_perms', self::$upload_perms );
    if ( get_post_meta( $forum_id, 'bbpkr_custom_perm', true ) )
      $allowed_roles = Permissions::get_forum_perms($forum_id, 'upload');
    $can = in_array( bbpresskr()->get_user_role(), $allowed_roles );

    return $can;
  }

  static function can_download( $attachment ) {
    $attachment = get_post( $attachment );
    if ( !$attachment || $attachment->post_type != 'attachment' )
      return false;

    $post = get_post($attachment->post_parent);
    $post_type = get_post_type($post);
    $bbp_types = array( bbp_get_topic_post_type(), bbp_get_reply_post_type() );
    if ( in_array( $post_type, $bbp_types ) ) {
      $forum_id = $post_type == bbp_get_topic_post_type() ? bbp_get_topic_forum_id() : bbp_get_reply_forum_id();
      $allowed_roles = (array) get_option( '_bbpkr_download_perms', self::$download_perms );
      if ( get_post_meta( $forum_id, 'bbpkr_custom_perm', true ) )
        $allowed_roles = Permissions::get_forum_perms($forum_id, 'download');

      $can = in_array( bbpresskr()->get_user_role(), $allowed_roles );
    } else {
      // TODO: default user level for download, currently for all
      $can = self::check_user( self::$wp_download_level );
    }
    return $can;
  }

  static function media_view_settings( $settings, $post ) {
    $forum_id = 0;
    if ( is_admin() ) {
    } elseif ( bbp_is_single_user_edit() || bbp_is_single_user() ) {
    } elseif ( bbp_is_forum_archive() ) {
    } elseif ( bbp_is_forum_edit() ) {
      $forum_id = bbp_get_forum_id();
    } elseif ( bbp_is_single_forum() ) {
      $forum_id = bbp_get_forum_id();
    } elseif ( bbp_is_topic_archive() ) {
    } elseif ( bbp_is_topic_edit() || bbp_is_single_topic() ) {
      $forum_id = bbp_get_forum_id();
    } elseif ( is_post_type_archive( bbp_get_reply_post_type() ) ) {
    } elseif ( bbp_is_reply_edit() || bbp_is_single_reply() ) {
      $forum_id = bbp_get_forum_id();
    } elseif ( bbp_is_single_view() ) {
    } elseif ( bbp_is_search() ) {
    } elseif ( bbp_is_topic_tag_edit() || bbp_is_topic_tag() ) {
      $forum_id = bbp_get_forum_id();
    }

    if ( $forum_id ) {
      @setcookie('_bbp_forum_id', $forum_id, 0, COOKIEPATH, COOKIE_DOMAIN);
      if ( empty( $settings['post'] ) ) {
        $settings['post'] = array(
          'id' => $forum_id,
          'nonce' => wp_create_nonce( 'update-post_' . $forum_id ),
        );
      }
    } else {
      @setcookie('_bbp_forum_id', ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
    }
    return $settings;
  }

  static function check_user( $level ) {
    global $user_level;
    if( 'public' == $level )
      return true;
    return current_user_can('level_'.$level);
  }

  static function get_post_attachments( $post_id ) {
      $args = array('post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null, 'post_parent' => $post_id);
      return get_posts($args);
  }

   // if 'dl' GET query, start downloadin'!
  static function download() {
    global $user_login;

    $file_id = intval($_GET['dl']);

    if(!$file_id)
      return; // no file specified, so end gracefully

    $attachment = get_post( $file_id );

    if ( self::can_download($attachment) ) {
      if(!self::$conf['show_msgs'])
        return; // just go to blog home
      if( is_user_logged_in() ) { // is user but wrong level
        wp_die( stripslashes(self::$conf['wrong_level_msg']), self::$failure_title );
      } else { // is not a user
        wp_die( stripslashes(self::$conf['no_login_msg']), self::$failure_title );
      }
    }
    
    // user info
    $ip = $_SERVER['REMOTE_ADDR'];
    $now = date('Y-m-d H:i:s');

    $file_path = get_attached_file($attachment->ID);
    // $ext = pathinfo($file_path, PATHINFO_EXTENSION);
    // $filename = pathinfo($file_path, PATHINFO_BASENAME);
    $file_url = wp_get_attachment_url($attachment->ID);

    if ( ! file_exists( $file_path ) ) {// provide 404 error and exit
      wp_die(__('The file you requested is not found on this server.', 'bbpresskr'), self::$failure_title, 404);
    }

    if ( !empty($_GET['directdl']) ) {
      wp_redirect($file_url);
      exit;
    }

    $download_name = self::filename_from_postid($file_id, $file_path);
    $download_name = rawurlencode($download_name);

    // now start downloading.
    @ignore_user_abort();
    @set_time_limit(0);

    if ( !$mimetype = self::get_mime_type($file_path) ) {
      $mimetype = "application/force-download";
      //$mimetype = 'application/octet-stream';  // set mime-type
    }

    $handle = fopen($file_path, "rb"); // now let's get the file!
    if(!$handle) // if cannot read file.
      return;
    header("Pragma: "); // Leave blank for issues with IE
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Content-Type: $mimetype");
    header("Content-Disposition: attachment; filename=" . $download_name);
    header("Content-Length: " . (filesize($file_path)));
    fpassthru($handle);
    die();
  }

  // To avoid problems with SAFE_MODE, we will not use is_file
  // of file_exists, but a loop through current directory
  static function is_file_exists($file) {
    $file_name = self::_basename($file);
    $file_dir = dirname($file) . '/';
    $subfolder = str_replace(self::$conf['path'], '', $file_dir);
    if(!is_dir($file_dir))
      return false;
    $file_exists = false;
    $blog_is_utf8 = strtoupper(get_option('blog_charset')) == 'UTF-8';
    if($dh = opendir($file_dir)) {
      while( ($_file = readdir($dh)) !== false) {
        if($_file == '.' || $_file == '..' )
          continue;
        if($_file == $file_name) {
          $file_exists = $_file;
          break;
        } elseif ($blog_is_utf8 && !seems_utf8($_file)) { // fix for international file name
          if (function_exists('mb_convert_encoding'))
            $_file_c = mb_convert_encoding($_file, 'UTF-8', 'ASCII, UTF-8, EUC-KR, JIS, EUC-JP, SJIS, ISO-8859-1');
          else 
            continue;
          if($_file_c == $file_name) {
            $file_exists = $_file; // return real file name
            break;
          }
        }
      }
      closedir($dh);
    }
    $file_exists = $subfolder . '/' . $file_exists;
    return $file_exists;
  }

  static function get_mime_type( $file_path ) { 
    if ( function_exists( 'mime_content_type' ) ) {
      $file_mime_type = @mime_content_type( $file_path );
    } elseif ( function_exists( 'finfo_file' ) ) {
      $finfo = @finfo_open(FILEINFO_MIME);
      $file_mime_type = @finfo_file($finfo, $file_path);
      finfo_close($finfo);  
    } else {
      $file_mime_type = false;
    }
    if ( !$file_mime_type ) {
      $extension = pathinfo( $file_path, PATHINFO_EXTENSION );
      foreach ( get_allowed_mime_types( ) as $exts => $mime ) {
        if ( preg_match( '!^(' . $exts . ')$!i', $extension ) ) {
          $file_mime_type = $mime;
          break;
        }
      }
    }
    return $file_mime_type;  
  }

  //Added by 082net
  static function _auto_append($content, $id = 0) {
    global $post;
    if ( !$id )
      $id = $post->ID;
    if ( $files = get_post_meta($id, 'attached_files', true) ) {
      $content .= "\n\n[file]" . implode(',', $files) . "[/file]\n";
    }
    return $content;
  }

  static function collect_attachments($post_parent, $exclude = array()) {
    global $wpdb;
    $post = get_post($post_parent);
    $post_types = array( bbp_get_topic_post_type(), bbp_get_reply_post_type() );
    if ( $post->post_type == 'attachment' ) {
      if ( self::$conf['exclude_images_from_list'] && stripos($post->post_mime_type, 'image') === 0 )
        return $post_parent;
      $post_parent = $post->post_parent;
      $post = get_post($post_parent);
    }

    if ( !in_array($post->post_type, $post_types) || $post->post_status != 'publish' ) {
      return $post_parent;
    }
    if ( self::$conf['exclude_images_from_list'] ) {
      $query = "SELECT ID FROM $wpdb->posts WHERE ";
      $where[] = "post_type='attachment'";
      $where[] = "post_status='inherit'";
      $where[] = "post_mime_type NOT LIKE 'image/%'";
      $where[] = "post_parent={$post_parent}";
      $where = implode(' AND ', $where);
      $query .= $where;
      $query .= " ORDER BY menu_order, ID ASC";
      $files = $wpdb->get_col($query);
    } else {
      $files = get_children( array('post_parent' => $post_parent, 'post_type' => 'attachment', 'fields' => 'ids', 'post_status' => 'inherit') );
    }
    if ( !empty( $exclude) ) {
      $files = array_diff( $files, (array) $exclude );
    }
    if ( $files ) {
      update_post_meta($post_parent, 'attached_files', $files);
    } else {
      delete_post_meta($post_parent, 'attached_files');
    }
  }

  static function admin_collect_attachments($location) {
    // check we are doing on right positoin
    if ( strpos( $location, 'wp-admin/upload.php' ) === false || (strpos( $location, 'attached=' ) === false && strpos( $location, 'detach=' ) === false ) )
      return $location;

    $post_parent = 0;
    if ( isset( $_REQUEST['found_post_id'] ) && isset( $_REQUEST['media'] ) ) {
      $post_parent = (int) $_REQUEST['found_post_id'];
    }
    elseif ( isset( $_REQUEST['parent_post_id'] ) && isset( $_REQUEST['media'] ) )
      $post_parent = $_REQUEST['parent_post_id'];

    if ( !$post_parent )
      return $location;

    self::collect_attachments( $post_parent );

    return $location;
  }

  static function delete_attachment($post_id) {
    self::collect_attachments( $post_id, array($post_id) );
  }

  static function icon_class($file) {
    $type = 'file-o';
    if ( $ext = pathinfo($file, PATHINFO_EXTENSION) ) {
      $ext2type = array(
        'file-image-o'       => array( 'jpg', 'jpeg', 'jpe',  'gif',  'png',  'bmp',   'tif',  'tiff', 'ico' ),
        'file-audio-o'       => array( 'aac', 'ac3',  'aif',  'aiff', 'm3a',  'm4a',   'm4b',  'mka',  'mp1',  'mp2',  'mp3', 'ogg', 'oga', 'ram', 'wav', 'wma' ),
        'file-video-o'       => array( '3g2',  '3gp', '3gpp', 'asf', 'avi',  'divx', 'dv',   'flv',  'm4v',   'mkv',  'mov',  'mp4',  'mpeg', 'mpg', 'mpv', 'ogm', 'ogv', 'qt',  'rm', 'vob', 'wmv' ),
        'file-pdf-o'         => array( 'pdf' ),
        'file-word-o'   => array( 'docm', 'dotm', 'odt',  'pages',  'xps',  'oxps', 'rtf',  'wp', 'wpd', 'psd', 'xcf', 'hwp' ),
        'excel'       => array( 'numbers',     'ods',  'xls',  'xlsx', 'xlsm',  'xlsb' ),
        'file-powerpoint-o' => array( 'swf', 'key',  'ppt',  'pptx', 'pptm', 'pps',   'ppsx', 'ppsm', 'sldx', 'sldm', 'odp' ),
        'file-text-o'        => array( 'asc', 'csv',  'tsv',  'txt' ),
        'file-archive-o'     => array( 'bz2', 'cab',  'dmg',  'gz',   'rar',  'sea',   'sit',  'sqx',  'tar',  'tgz',  'zip', '7z', 'alz' ),
        'file-code-o'        => array( 'css', 'htm',  'html', 'php',  'js' ),
        );
      $type = false;
      foreach ( $ext2type as $type => $exts )
        if ( in_array( $ext, $exts ) )
          break;
      if ( !$type ) {
        $type = 'file-o';
      }
    }
    return $type;
  }

  static function embed_attachments($post_id = 0) {
    global $post;
    if ( ! $post_id && is_a($post, 'WP_Post') )
      $post_id = $post->ID;
    if ( ! $post_id )
      return '';
    if ( $files = get_post_meta($post_id, 'attached_files', true) )
      return self::_content('', $files);
    return '';
  }

  static function _content($empty_atts='', $files='') {
    global $post, $user_ID;
    if ( !is_array($files) ) {
      $files = str_replace(array(' ', "\r\n", "\n"), '', $files);
      $files = explode(',', $files);
      $files = array_filter( array_map('intval', $files) );
    }
    if(empty($files))
      return '';

    $url = home_url();

    $r = '<div class="dm-wrap">';
    foreach ( $files as $file_id ) {
    // for ($i = 0; $i < count($files); $i++) {
      $f = get_attached_file($file_id, true);
      $fname = self::filename_from_postid($file_id, $f);
      $furl = home_url( "?dl={$file_id}" );

      if ( file_exists( $f ) ) {

        $url = add_query_arg('_wpnonce', wp_create_nonce('bbpresskr-attachments'));
        $url = add_query_arg('att_id', $file_id, $url);
        $url = add_query_arg('bbp_id', $post->ID, $url);

        $allow = 'no';
        $actions = array();
        if ( bbp_is_user_keymaster() ) {
          $allow = self::$conf['delete_visible_to_admins'];
        } else if ( current_user_can('moderate') ) {
          $allow = self::$conf['delete_visible_to_moderators'];
        } else if ( $post->post_author == $user_ID ) {
          $allow = self::$conf['delete_visible_to_author'];
        }

        if ($allow == 'delete' || $allow == 'both') {
          $actions[] = '<a href="'.add_query_arg('bbpkraction', 'delete', $url).'">'.__('delete', 'bbpresskr').'</a>';
        }

        if ($allow == 'detach' || $allow == 'both') {
          $actions[] = '<a href="'.add_query_arg('bbpkraction', 'detach', $url).'">'.__('detach', 'bbpresskr').'</a>';
        }

        if ( count($actions) > 0 ) {
          $actions = ' ['.join(' | ', $actions).']';
        } else {
          $actions = '';
        }

        $fnamefull = $fname;
        if ( mb_strlen($fname) > 54 )
          $fname = mb_substr($fname, 0, 54) . '&hellip;';
        $fsize = self::_getFilesize( @filesize($f) );
        $icon = self::icon_class($f);
        $r .= '<div class="dm-file"><span class="dm-fname">';
        $r .= "<i class='fa fa-{$icon}'></i>";
        // $r .= '<img alt="download" src="'.self::$pluginURL.'/i/i-png/'.self::_getFileicon($fname).'.png" style="vertical-align:top;" /> ';
        $r .= '<a href="' . $furl . '" title="' . sprintf(esc_attr__('Download %s', 'bbpresskr'), $fnamefull) . '"> ' . $fname . '</a>';
        $r .='</span> <span class="dm-meta">(' . $fsize . ')' . $actions . '</span></div>';
      } else {
        $r .= '<div class="dm-file">File Not Found</div>';
      }
    }
    $r .= '</div>';
    return $r;
  }

  static function filename_from_postid($post_id, $file='') {
    $ftitle = get_post_field('post_title', $post_id);
    if ( !preg_match('#\.([a-z0-9]+)$#i', $ftitle) ) {
      if ( !$file )
        $file = get_attached_file($post_id, true);
      $ext = pathinfo($file, PATHINFO_EXTENSION);
      $ftitle .= '.' . $ext;
    }
    if ( preg_match('#^\.([a-z0-9]+)$#i', $ftitle) ) {
      if ( $file )
        $filename = pathinfo($file, PATHINFO_BASENAME);
      else
        $filename = 'notitle';
      $ftitle = $filename . $ftitle;
    }
    return $ftitle;
  }

  //from Download Beautifier(http://binslashbash.org)
  static function _getFilesize ($fsize) {
    if (strlen($fsize) <= 9 && strlen($fsize) >= 7) {       
      $fsize = number_format($fsize / 1048576,1);
      return "$fsize MB";
    } elseif (strlen($fsize) >= 10) {
      $fsize = number_format($fsize / 1073741824,1);
      return "$fsize GB";
    } else {
      $fsize = number_format($fsize / 1024,1);
      return "$fsize KB";
    }
  }

  static function delete_attachments() {
    if (isset($_GET['bbpkraction'])) {
      $nonce = wp_verify_nonce($_GET['_wpnonce'], 'bbpresskr-attachments');

      if ($nonce) {
        global $user_ID;

        $action = $_GET['bbpkraction'];
        $att_id = $_GET['att_id'];
        $bbp_id = $_GET['bbp_id'];

        $post = get_post($bbp_id);
        $author_ID = $post->post_author;

        $file = get_attached_file($att_id);
        $file = pathinfo($file, PATHINFO_BASENAME);

        $allow = 'no';
        if ( bbp_is_user_keymaster() ) {
          $allow = self::$conf['delete_visible_to_admins'];
        } else if ( current_user_can('moderate') ) {
          $allow = self::$conf['delete_visible_to_moderators'];
        } else if ( $author_ID == $user_ID ) {
          $allow = self::$conf['delete_visible_to_author'];
        }

        if ($action == 'delete' && ($allow == 'delete' || $allow == 'both')) {
          wp_delete_attachment($att_id);
        }

        if ($action == 'detach' && ($allow == 'detach' || $allow == 'both')) {
          global $wpdb;
          $wpdb->update($wpdb->posts, array('post_parent' => 0), array('ID' => $att_id));
        }
        self::collect_attachments($post->ID);
      }

      $url = remove_query_arg(array('_wpnonce', 'bbpkraction', 'att_id', 'bbp_id'));
      wp_redirect($url);
      exit;
    }
  }

}

Attachments::init();
