<?php
/*
	Plugin Name: KDisk
	Plugin URI: https://kdisk.ru/?pplugin
	Author URI: https://kdisk.ru/?pauthor
	Author:	KDisk
	Description: Плагин загрузка, хранение, скачивание файлов пользователями
	Version: 1.0.8
	Author: KDisk
*/
/*  Copyright 2022  kdisk  (email: kdisk@kdisk.ru)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
/*ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);*/
define ("KDISK_PLG","kdisk");
define ("KDISK_OPT","kdisk-opt");
$g_kv_mydisk_name = 'kdisk';

add_action( 'init', 'kdisk_initialize');

function kdisk_initialize() {
	
	global $g_kv_mydisk_name;
	
 	$o = get_option( KDISK_OPT );
	if ( isset($o['kdisk_mydir_activ']) )
	{
		$g_kv_mydisk_name = $o['kdisk_mydir_activ'];
	}

}

//Проверяем url, если наш, то активизируемся
add_action('parse_request', 'kdisk_custom_url_handler');
function kdisk_custom_url_handler( $query_args ) {
	
	global $g_kv_mydisk_name;
	
   	if( strstr( $_SERVER["REQUEST_URI"], "/" . $g_kv_mydisk_name . "/" )) 
	{
		kdisk_register_styles();
		kdisk_register_scripts();
		unset($query_args->query_vars['error']);
		$query_args->query_vars['page'] = '';
		$query_args->query_vars['pagename'] = $g_kv_mydisk_name;
		if( !class_exists('KDisk_WP') )include( __DIR__ . "/include/class-kdisk-wp.php" );
		KDisk_WP::read_options();
	  	include( __DIR__ . "/templates/tem-mydisk.php" );
		$Kv_MyDisk->go();
		$query_args->query_vars['pagename'] .= "/" . $Kv_MyDisk->m_namepage;
		
   }
   return $query_args;
}
//Активация плагина
register_activation_hook( __FILE__, 'kdisk_plugin_activation' );
function kdisk_plugin_activation()
{
	if( !class_exists('KDisk_WP') )include( __DIR__ . "/include/class-kdisk-wp.php" );
	kdisk_upgrate_function(0,0);	

			
	KDisk_WP::plugin_activation();
	

}
//Деактивация плагина
register_deactivation_hook( __FILE__, 'kdisk_plugin_deactivation'  ); 
function kdisk_plugin_deactivation()
{
	if( !class_exists('KDisk_WP') )include( __DIR__ . "/include/class-kdisk-wp.php" );
	KDisk_WP::plugin_deactivation();
}

//Опции
add_action('admin_menu', 'kdisk_options');
function kdisk_options() {
	if( !class_exists('KDisk_WP') )include( __DIR__ . "/include/class-kdisk-wp.php" );
	add_options_page( __('Options',KDISK_PLG), __('KDisk',KDISK_PLG), 'manage_options', "kdisk-setting", array( 'KDisk_WP', 'option_page'));  
}
add_action( 'admin_init','kdisk_admin_init');
function kdisk_admin_init()
{
	if( !class_exists('KDisk_WP') )include( __DIR__ . "/include/class-kdisk-wp.php" );
	KDisk_WP::option_settings();
}
//Шорт код страницы
add_shortcode( 'kdisk-show', 'kv_mydisk_show' );
function kv_mydisk_show( $atts, $content ) {
	global $Kv_MyDisk;
	if ( isset($Kv_MyDisk) )
	{
		return $Kv_MyDisk->get_page_html();
	}
	return "";
}
//Стили для админки
add_action('admin_head', 'kvdisk_stylesheet_admin');
function kvdisk_stylesheet_admin(){
	wp_enqueue_style("style-admin",plugins_url("css/style-admin.css",__FILE__));
	
}

//Language
add_action( 'plugins_loaded', 'kdisk_plugins_loaded');
function kdisk_plugins_loaded()
{
	load_plugin_textdomain( KDISK_PLG, false, dirname( plugin_basename(__FILE__) ) . '/lang' );

} 

function kdisk_register_styles() {

	wp_register_style( 'kdisk-mydisk', plugins_url( 'mydisk/css/mydisk.css?' .  filemtime( __DIR__ . "/mydisk/css/mydisk.css" ), __FILE__));
	wp_register_style( 'kdisk-kv-elem', plugins_url( 'mydisk/css/kv-elements.css?' . filemtime( __DIR__ . "/mydisk/css/kv-elements.css" ), __FILE__));
	wp_enqueue_style( 'kdisk-mydisk' );
	wp_enqueue_style( 'kdisk-kv-elem' );
}
function kdisk_register_scripts() {
	wp_enqueue_script( 'kdisk-ajskv',plugins_url( 'mydisk/script/ajskv.js?' . filemtime( __DIR__ . "/mydisk/script/ajskv.js" ), __FILE__));
	wp_enqueue_script( 'kdisk-kv-ele',plugins_url( 'mydisk/script/kv-elements.js?' . filemtime( __DIR__ . "/mydisk/script/kv-elements.js" ), __FILE__));
	wp_enqueue_script( 'kdisk-kvfun',plugins_url( 'mydisk/script/kvfun.js?' . filemtime( __DIR__ . "/mydisk/script/kvfun.js" ), __FILE__));
	
	$filelang = __DIR__ . "/mydisk/script/kdisk-lang-" . get_locale() . ".js" ;
	$filetime = 0 ;
	if ( is_file( $filelang ) )
	{
		$filetime = filemtime( $filelang );
	}
	$timelang = filemtime ( __DIR__ . "/mydisk/include/class-kdisk-lang.php" );
	if ( !$filetime || $filetime < $timelang )
	{
		if( !class_exists('KDisk_Lang') )include( __DIR__ . "/mydisk/include/class-kdisk-lang.php" );
		KDisk_Lang::createJS( $filelang );
		$filetime = filemtime( $filelang );
	}
	
	wp_enqueue_script( 'kdisk-klang',plugins_url( "/mydisk/script/kdisk-lang-" . get_locale() . ".js" . "?" . $filetime , __FILE__));
}
//Линки в списке плагина
add_filter( 'plugin_action_links', 'kdisk_plugin_action_links', 10, 2 );
function kdisk_plugin_action_links($links, $file)
{
    if ( $file != plugin_basename(__FILE__) ){
        return $links;
    }
	

    $settings_link = sprintf('<a href="%s">%s</a>', admin_url('options-general.php?page=kdisk-setting'), esc_html__('Settings'));
	$drive_link = sprintf('<a href="%s">%s</a>', site_url("kdisk/drive/") , esc_html__('My Drive',KDISK_PLG));
	$shared_link = sprintf('<a href="%s">%s</a>', site_url("kdisk/shared/"), esc_html__('Shared',KDISK_PLG));
    array_unshift( $links, $settings_link, $drive_link, $shared_link);
    return $links;
}

add_action( 'upgrader_process_complete', 'kdisk_upgrate_function', 10, 2);
function kdisk_upgrate_function( $upgrader_object, $hook_extra ){
	
	global $Kv_MyDisk;
	if ( isset($Kv_MyDisk) )
	{
	}else
	{
		if( !class_exists('KDisk_WP') )include( __DIR__ . "/include/class-kdisk-wp.php" );
		KDisk_WP::read_options();
	  	include( __DIR__ . "/templates/tem-mydisk.php" );
	}

	$Kv_MyDisk->update_plugin();
	
}
