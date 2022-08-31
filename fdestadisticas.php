<?php
/**
 * @package FDEstadisticas
 */
/*
Plugin Name: FD Estadisticas
Plugin URI: https://deseisaocho.com/
Description: Finanzas Digital <strong>Estadisticas</strong>
Version: 1.0
Author: JLMA
Author URI: https://deseisaocho.com/wordpress-plugins/
License: GPLv2 or later
Text Domain: fd
*/

if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define( 'FDESTADISTICAS_VERSION', '1.6' );
define( 'FDESTADISTICAS__MINIMUM_WP_VERSION', '5.0' );
define( 'FDESTADISTICAS__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

register_activation_hook( __FILE__, array( 'FDEstadisticas', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'FDEstadisticas', 'plugin_deactivation' ) );

require_once( FDESTADISTICAS__PLUGIN_DIR . 'class.fdestadisticas.php' );
require_once( FDESTADISTICAS__PLUGIN_DIR . 'class.fdestadisticas-widget.php' );

add_action( 'init', array( 'FDEstadisticas', 'init' ) );

if ( is_admin() ) {
	require_once( FDESTADISTICAS__PLUGIN_DIR . 'class.fdestadisticas-admin.php' );
	add_action( 'init', array( 'FDEstadisticas_Admin', 'init' ) );
}