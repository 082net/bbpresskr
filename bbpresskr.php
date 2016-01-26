<?php
/**
 * @package bbPressKR
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 */
/*
Plugin Name: bbPressKR
Plugin URI: http://082net.com/
Description: bbPress 포럼 플러그인을 한국형 게시판으로 사용하기 위한 플러그인입니다. 기본, 갤러리, 웹진 형식을 지원하며, 기본적인 게시판별 권한을 지원합니다.
Version: 0.1
Author: 082NeT
Author URI: http://082net.com/
License: GPLv2 or later
Text Domain: bbpresskr
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

if ( !defined( 'ABSPATH' ) ) die('Forbidden');

define( 'BBPKR_PATH', dirname(__FILE__) );
define( 'BBPKR_INC', BBPKR_PATH . '/inc' );
define( 'BBPKR_LIB', BBPKR_PATH . '/lib' );


spl_autoload_register(function ($class) {
    // project-specific namespace prefix
    $prefix = 'bbPressKR\\';

    // does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relativeClass = substr($class, $len);
    $relativeClass = str_replace('_', '-', strtolower($relativeClass));

    $fileInc = rtrim(BBPKR_INC, '/') . '/' . str_replace('\\', '/', $relativeClass) . '.php';
    $fileLib = rtrim(BBPKR_LIB, '/') . '/' . str_replace('\\', '/', $relativeClass) . '.php';

    // if the file exists, require it
    if (file_exists($fileInc)) {
      require $fileInc;
    } elseif ( file_exists($fileLib) ) {
      require $fileLib;
    }
});

require( BBPKR_INC . '/functions-compat.php' );
require( BBPKR_INC . '/functions.php' );

global $bbpkr;
$bbpkr = bbpresskr();

