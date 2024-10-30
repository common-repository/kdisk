<?php



if( ! defined('WP_UNINSTALL_PLUGIN') ) exit;

include ( __DIR__ . "/include/class-kdisk-wp.php" );



include ( __DIR__ . "/mydisk/class-kdisk-mydisk.php" );

global $wpdb;

$Kv_MyDisk = new KDisk_MyDisk();

$Kv_MyDisk->initialize( array(	"id_user_current" 	=> get_current_user_id(),
								"folder_disk" 		=> KDisk_WP::$disk_name,
								"folder_user_files" => KDisk_WP::$dir_users_files,
								"url_mydisk" 		=> plugins_url("kv-mydisk/mydisk"),
								"db_host" 			=> DB_HOST,
								"db_user" 			=> DB_USER,
								"db_password" 		=> DB_PASSWORD,
								"db_name" 			=> DB_NAME,
								"db_table_prefix" 	=> $wpdb->base_prefix,
								"user_access" 		=> 0xFFFFFFFF,
								"min_free_space"    => KDisk_WP::$kdisk_space['kv_min_free_space'] * 1073741824,
								"start_free_space"	=> KDisk_WP::$kdisk_space['kv_start_space'] * 1073741824,
		 
									 ));
$Kv_MyDisk->delete_plugin();
KDisk_WP::delete_options();
KDisk_WP::delete_pages();

//die();