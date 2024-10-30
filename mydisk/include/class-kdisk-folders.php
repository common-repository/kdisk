<?php 
/* 

*/

class KDisk_Folders{
	public $m_folders = array( 'users_files'	=> "kvfiles", 					//Директорий где храняться файлы всех пользователи, служебные и др.
								'all_drive' 	=> "alldrive",					//Для незарегистрированных пользователей 
								'temporary'	 	=> "temporary",					//директорий для формирования zip архива на скачивание
								'all_trash'		=> "alltrash",					//директорий корзины куда удаляют из своих корзины пользователи
								'cur_dir_user'	=> "/",							//Текущий директорий пользователя $KV_DIR_USER_CUR = "/";	
								'dir_user'		=> "sdcfjwerfWEGE4SDCFDbv", 	//директорий с файлами пользователя if ( ! isset( $KV_DIR_USER ) ){	$KV_DIR_USER = 'sdcfjwerfWEGE4SDCFDbv'; }
								'disk' 			=> "kdisk",						//главный url
								'drive' 		=> "drive",						//файлы
								'trash' 		=> "trash",						//Корзины
								'view'			=> "view",						//URL просмотра скачивания файла
								'preview'		=> "preview",					//URL для привьюшек
								'url_mydisk'	=> "",							//к скриптам	
								'ext_patch_trash' => "patch",
								'shared_trash'	=> "shared-trash",
								'shared'	=> "shared",
									);
	function create_directories()
	{
		if ( !is_dir( $this->get_full_dir_user_files() ) ) mkdir( $this->get_full_dir_user_files(), 0755, true );
		if ( !is_dir( $this->get_full_dir_cur_user() ) ) mkdir( $this->get_full_dir_cur_user(), 0755, true );
		if ( !is_dir( $this->get_full_dir_temporary() ) ) mkdir( $this->get_full_dir_temporary(), 0755, true );
		if ( !is_dir( $this->get_full_dir_share_trash() ) ) mkdir( $this->get_full_dir_share_trash(), 0755, true );
		
		$htaccess_file = $this->get_full_dir_user_files() . "/.htaccess";
		if ( !is_file( $htaccess_file ) )copy( __DIR__ . "/../service/.htaccess_main", $htaccess_file );
	}									
	public function set_folders( $ar_folders )										
	{
		$this->m_folders = array_merge( $this->m_folders, $ar_folders);
		$this->create_directories();
		
	}
	//Полный путь к корзине общей папки
	function get_full_dir_share_trash()
	{
		return $_SERVER['DOCUMENT_ROOT'] . "/" . $this->m_folders['users_files'] . "/" . $this->m_folders['shared_trash'];
	}
	//Полный путь к глобальной корзине
	function get_full_dir_alltrash()
	{
		return $_SERVER['DOCUMENT_ROOT'] . "/" . $this->m_folders['users_files'] . "/" . $this->m_folders['all_trash'];
	}
	//Полный путь директория с файлами всех пользователей
	function get_full_dir_user_files()
	{
		return  $_SERVER['DOCUMENT_ROOT'] . "/" . $this->m_folders['users_files'];
	}
	//Полный путь к директорию с файлами текущего пользователя
	function get_full_dir_cur_user()
	{
		return $_SERVER['DOCUMENT_ROOT'] . "/" . $this->m_folders['users_files'] . "/" . $this->m_folders['dir_user'];
	}
	//Полный путь к директорию с временными файлами
	function get_full_dir_temporary()
	{
		return $_SERVER['DOCUMENT_ROOT'] . "/" . $this->m_folders['users_files'] . "/" . $this->m_folders['temporary'];
	}
	//ULR к скриптам 
	function get_url_mydisk_script(){
		return $this->m_folders['url_mydisk'];
	}
	//URL просмотра и скачивания файлов
	function get_url_disk_view(){
		return "/" . $this->m_folders['disk'] . "/" . $this->m_folders['view'];
	}
	//URL preview файлов
	function get_url_disk_preview(){
		return "/" . $this->m_folders['disk'] . "/" . $this->m_folders['preview'];
	}
	//
/*	function get_dir_user_all_drive_trash(){
		return $this->m_params['dir_full_user_files'] . "/" . $this->m_params['folder_user'] . "_" . $this->m_params['folder_trash'];
	}*/
	 
}

