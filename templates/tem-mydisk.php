<?php 
/**
 * Template Name: Мой диск
 */

global $wpdb;


include __DIR__ . "/../mydisk/class-kdisk-mydisk.php";

global $Kv_MyDisk;
$Kv_MyDisk = new KDisk_MyDisk();
$user_access = 0;
/*if( current_user_can('delete_pages') ){
	$user_access = 0xFF;
}else*/

KDisk_WP::read_options();

if( current_user_can('kdisk_share_read') ){

	$user_access |= 0x1;
}
if( current_user_can('kdisk_share_write') ){

	$user_access |= 0x2;
}
if( current_user_can('kdisk_share_delete') ){

	$user_access |= 0x4;
}
if( current_user_can('kdisk_share_trash') ){

	$user_access |= 0x8;
}
if ( ! get_current_user_id() )
{
	if(get_option("kdisk_notlogin_share_read") == 1)
	{
		$user_access |= 0x1;
	}
	if(get_option("kdisk_notlogin_share_write") == 1)
	{
		$user_access |= 0x2;
	}

}



$Kv_MyDisk->initialize( array(	"id_user_current" 	=> get_current_user_id(),
								"folder_disk" 		=> KDisk_WP::$disk_name,
								"folder_user_files" => KDisk_WP::$dir_users_files,
								"url_mydisk" 		=> plugins_url("kdisk/mydisk"),
								"db_host" 			=> DB_HOST,
								"db_user" 			=> DB_USER,
								"db_password" 		=> DB_PASSWORD,
								"db_name" 			=> DB_NAME,
								"db_table_prefix" 	=> $wpdb->base_prefix,
								"user_access" 		=> $user_access,
								"min_free_space"    => KDisk_WP::$kdisk_space['kv_min_free_space'] * 1073741824,
								"start_free_space"	=> KDisk_WP::$kdisk_space['kv_start_space'] * 1073741824,
								"upload_type"		=> KDisk_WP::$kdisk_space['kv_upload_type'],
								"upload_xhr_size"=> KDisk_WP::$kdisk_space['kv_upload_xhr_size'] * 1048576,
		 
									 ));
									 
return;
