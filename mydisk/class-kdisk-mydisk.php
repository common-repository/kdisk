<?php
include __DIR__ . "/include/class-kdisk-upload.php";
include __DIR__ . "/include/kdisk-function.php";
include __DIR__ . "/include/class-kdisk-basemy.php";
include __DIR__ . "/include/class-kdisk-base.php";
include __DIR__ . "/include/class-kdisk-folders.php";

class KDisk_MyDisk
{
	private $m_folders = array( "users_files"	=> "users_files", 		//Директорий где храняться файлы всех пользователи, служебные и др.
								"all_drive" 	=> "shared",			//Общая папка для всех
								"all_drive_trash" 	=> "shared-trash",	//Общая папка для всех
								"temporary"	 	=> "temporary",			//директорий для формирования zip архива на скачивание
								"all_trash"		=> "alltrash",			//директорий корзины куда удаляют из своих корзины пользователи
								"cur_dir_user"	=> "/",					//Текущий директорий пользователя $KV_DIR_USER_CUR = "/";	
								"dir_user"		=> "'sdcfjwerfWEGE4SDCFDbv", //директорий с файлами пользователя if ( ! isset( $KV_DIR_USER ) ){	$KV_DIR_USER = 'sdcfjwerfWEGE4SDCFDbv'; }


								"disk" 			=> "mydisk",			// главный url
								"drive" 		=> "drive",				// файлы
								"trash" 		=> "trash",				//Корзина для пользователя
								'view'			=> "view",				//URL просмотра скачивания файла
								'preview'		=> "preview",			//URL для привьюшек
								'url_mydisk'	=> "",					//к скриптам	
								'ext_patch_trash' => "patch",
									);
	private $service_words = array("ext_patch_trash" => "patch",		//рассширение в корзине для служебного файла восстановления пути
								   "table_user_dir" => "krdisk_dirs",
								   "table_user_files" => "krdisk_files",
								   "table_tasks"	=> "krdisk_task",
								   "table_stat_tmp"	=> "kdisk_stat_tmp",
								   
								 
	);
	
	private $m_KDisk_Base, $m_KDisk_Pages, $m_id_user_current, $m_KDisk_Folders;
	private $m_user_params, $m_initialize_params;
	public $m_namepage="";

	function initialize($ar_param)
	{
		
/*		if ( !isset($_COOKIE['kdisk-view-id']) )
		{
			$cook = KDisk_generate_string();
			setcookie ('kdisk-view-id', $cook, time() + 60*60*10,"/") ;
		}*/
		
		$this->m_initialize_params = $ar_param;
		
		$this->m_id_user_current = $ar_param['id_user_current'];
		$base = new KDisk_Base();
		$base->Connect( $ar_param['db_host'], $ar_param['db_user'], $ar_param['db_password'], $ar_param['db_name'] );
		$base->set_name_table_users( $ar_param['db_table_prefix'] . $this->service_words['table_user_dir'] );
		$base->set_name_table_files( $ar_param['db_table_prefix'] . $this->service_words['table_user_files'] );
		$base->set_name_table_task( $ar_param['db_table_prefix'] . $this->service_words['table_tasks'] );
		$base->set_name_table_stat_tmp( $ar_param['db_table_prefix'] . $this->service_words['table_stat_tmp'] );
		
		$this->m_user_params = $base->get_user_directory( $ar_param['id_user_current'], $ar_param['start_free_space'] );
		$this->m_user_params['user_access'] = $ar_param['user_access'];
		$this->m_folders['dir_user'] = $this->m_user_params['diruser'];
		
		$this->m_KDisk_Base = $base;
		
		$this->m_folders['disk'] = $ar_param['folder_disk'];
		$this->m_folders['users_files'] = $ar_param['folder_user_files'];
		$this->m_folders['url_mydisk'] = $ar_param['url_mydisk'];
		$this->m_folders['full_dir_user_files'] = $_SERVER['DOCUMENT_ROOT'] . "/" . $this->m_folders['users_files'];
		
		$folders = new KDisk_Folders();
		$folders->set_folders( array( 'dir_user' 	=> $this->m_user_params['diruser'],
									  'disk' 		=> $ar_param['folder_disk'],
									  'users_files' => $ar_param['folder_user_files'],
									  'url_mydisk' 	=> $ar_param['url_mydisk'],
									  'all_drive'	=> $this->m_folders['all_drive'],
									 ));
		
		$this->m_KDisk_Folders = $folders;
		
		
	}
	//Вернём сформированные данные html
	function get_page_html()
	{
		$page = $this->m_KDisk_Pages;
		if(!isset($page))return "";
		return $page->m_html;
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
	

	function go()
	{
		//Выполняем какую-нибуть задачу по крону
		$this->kv_mydisk_cron_step();		
		/////////////

		$url_in = urldecode( $_SERVER['REQUEST_URI'] );
		$ar = explode ("/",$url_in);		
		if( ! isset($ar[2]) )return;
		$this->m_namepage = $ar[2];
		switch($ar[2])
		{
			
			case $this->m_folders['view']:
			case $this->m_folders['trash']:
			case $this->m_folders['drive']:
			case $this->m_folders['all_drive']:
			case $this->m_folders['all_drive_trash']:
			{
				////Убираем drive,trash ...
				$url_in = "";
				for($i = 0; $i < count($ar) ; $i++)
				{
					if ( $i == 2 ) continue;
					$url_in .= $ar[ $i ]."/";
				}	

			}
			
			break;
		}
		
		if ( isset($_SERVER['HTTP_REFERER']) )
		{
			$arref = explode ("/",$_SERVER['HTTP_REFERER']);	
		}
		if ( isset($arref) && isset($arref[4]) ){
			$aref = $arref[4];
		}else
		{
			$aref='';
		}
		
		$folder_trash = $this->m_folders['trash'];
		if( (($aref == $this->m_folders['all_drive'] || $aref == $this->m_folders['all_drive_trash']) && $ar[2] != $this->m_folders['drive']) || $ar[2] == $this->m_folders['all_drive'])
		{
			
			if ( ! ($this->m_user_params['user_access'] & 1) )return;
			$iduser = $this->m_id_user_current;
			$this->m_initialize_params['id_user_current'] = 0;
			$this->m_folders['drive']=$this->m_folders['all_drive'];
			$this->initialize( $this->m_initialize_params );
			$this->m_id_user_current = $iduser;
			
			$folder_trash = $this->m_folders['all_drive_trash'];
		}

	

		
		
		$KV_DIR_USER_CUR = "/";
		
		$url = parse_url( $url_in );
		
		$urldisk = "/" . $this->m_folders['disk'];

		if ( strpos( $url['path'], $urldisk ) !== false )
		{
			$KV_DIR_USER_CUR = substr( $url['path'], strlen($urldisk) );
		}
		
		$KV_DIR_USER_CUR = "/" . KDisk_trim_path($KV_DIR_USER_CUR);
		
		

		$gKv_MyDisk_Page = new KDisk_Pages();
		$gKv_MyDisk_Page->set_name_folders( $this->m_folders );
		$gKv_MyDisk_Page->set_KDisk_Folders( $this->m_KDisk_Folders );
		$gKv_MyDisk_Page->set_function_kv_mydisk_set_file(array($this,'kv_mydisk_set_file'));
		$gKv_MyDisk_Page->set_function_get_preview( array( $this, 'kv_mydisk_get_preview') );
		$gKv_MyDisk_Page->set_user_params( $this->m_user_params );
		$this->m_KDisk_Pages = $gKv_MyDisk_Page;
		
		
		
		
		$gKDisk_Base = $this->m_KDisk_Base;


		if( $ar[2] == $this->m_folders['all_drive'] )
		{//Общая папка для всех 
			
			
		}else
		if( $ar[2] == $this->m_folders['preview'] )
		{//Запрос маленькой картинки
			session_write_close();
			
			parse_str($url['query'], $output);
			$ar1 = explode ("?",$ar[3]);
			$blob = $gKDisk_Base->get_preview_file( $ar1[0], $output['size'] );
	
			if ( $blob )
			{
				$secout = (60 * 60 * 24 * 31);
				header("Content-Type: image/png");
				header('Cache-control: max-age=' . $secout);
				header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + $secout));
				header("Pragma: cache");
				$img = imagecreatefromstring($blob);
				imagealphablending($img, false);
				imagesavealpha($img, true);
				imagepng($img);
				imagedestroy($img);
			}else
			{
				header("HTTP/1.0 404 Not Found");
				header('Location: ' . $this->m_folders['url_mydisk'] . "/imgs/kvnoimg.png");
			}
			exit;
		}elseif( $ar[2] == $this->m_folders['view'] ) //KV_DIR_VIEW
		{//Просмотр файла
		
			$info = $gKDisk_Base->get_info_file_from_open_url( $ar[3] );
			if( $info )
			{
				$gKv_MyDisk_Page->make_page_patch( $info ); 
			}else
			{
			}
			return;
	
		}elseif ( $ar[2] == $this->m_folders['drive'] ) //KV_DIR_DRIVE
		{
			if ( !$this->m_id_user_current )return;
			
	
		}else//all_drive_trash
		if( $ar[2] == $this->m_folders['all_drive_trash'] )
		{
			if ( !$this->m_id_user_current )return;
			$dir_user_trash = $this->get_full_dir_cur_user() . "_" . $this->m_folders['all_drive_trash'];	//KV_DIR_TRASH
			if ( !is_dir( $dir_user_trash ) ) mkdir( $dir_user_trash, 0755, true );
			$gKv_MyDisk_Page->MakePageTrash( $this->m_folders['dir_user']. "_" . $this->m_folders['all_drive_trash']  );

			return;
 		}else

		if( $ar[2] == $this->m_folders['trash'] )//KV_DIR_TRASH
		{
			if ( !$this->m_id_user_current )return;
			$dir_user_trash = $this->get_full_dir_cur_user() . "_" . $this->m_folders['trash'];	//KV_DIR_TRASH
			if ( !is_dir( $dir_user_trash ) ) mkdir( $dir_user_trash, 0755, true );
			$gKv_MyDisk_Page->MakePageTrash( $this->m_folders['dir_user'] . "_" . $this->m_folders['trash']  );

			return;
 		}else
		{//JS запросы
			
			include(__DIR__."/class-kdisk-in-command.php");
			$KV_Disk = new KDisk_In_Command();
			$KV_Disk->set_KDisk_Folders( $this->m_KDisk_Folders );
			$KV_Disk->set_base( $this->m_KDisk_Base );
			$mem_free = $this->m_user_params['mem_total'] - $this->m_user_params['mem_busy'];

			
			
			
			$KV_Disk->Set_Params( array( 'dir_full_user_files'	=> 	$this->get_full_dir_user_files(), //KV_FULL_DIR_USER_FILES,	//Полный путь к директорию где храняться файлы всех пользователей. 
										 'folder_user' 			=> 	$this->m_folders['dir_user'], //$KV_DIR_USER			//Название папки текущего пользователя
										 'folder_user_current' 	=> 	$KV_DIR_USER_CUR,		//Текущий путь выбранный пользователем 
										 'folder_disk' 			=> 	$this->m_folders['disk'],	//KV_DIR_DISK //Название страницы disk  
										 'folder_drive' 		=> 	$this->m_folders['drive'], //KV_DIR_DRIVE,			//Название drive	
										 'folder_user_files'	=> 	$this->m_folders['users_files'],//KV_DIR_USER_FILE	//папка всех юзеров
										 
										 'folder_trash'			=>	$folder_trash,			//KV_DIR_TRASH,			//Название папки корзины
										 
										 'user_access'			=> 	$this->m_user_params['user_access'], //
										 'min_free_space'		=>	$this->m_initialize_params['min_free_space'], //Свободное место на диске
 										 'user_free_size'		=>	$mem_free, //Доступное место на диске для пользователя
										
												) ) ;
			$ret = $KV_Disk->Set_Command( $_GET, $_POST ); 											
			if ( $ret['err'] == -1 )
			{
				header("Location: /" . $this->m_folders['disk'] . "/" . $this->m_folders['drive'] ."/"); 
			}
			echo json_encode( $ret );
			exit;
		}
		

		///DRIVE

		$path = $this->get_full_dir_user_files() . "/" . $this->m_folders['dir_user'] . $KV_DIR_USER_CUR;
		if( !is_dir($path."/"))return -1;
		
		KDisk_Upload::$m_upload_type = $this->m_initialize_params['upload_type'];
		KDisk_Upload::$m_upload_xhr_size = $this->m_initialize_params['upload_xhr_size'];
		
		

		
		$gKv_MyDisk_Page->MakePageUser( $path, $KV_DIR_USER_CUR ); 
	
		
		 wp_add_inline_script('kdisk-kvfun','var g_KrDiskStat = {mem_busy : ' . $this->m_user_params['mem_busy'] . ', mem_total : ' . $this->m_user_params['mem_total'] . '};');
		
		

	}



	function kv_mydisk_get_preview( $name_file )
	{
		$gKDisk_Base = $this->m_KDisk_Base;
		if ( isset( $gKDisk_Base ) )
		{
			$ret = $gKDisk_Base->get_open_url_file( $name_file );
			if ( $ret )
			{
				$noimg = $this->m_folders['url_mydisk'] . "/imgs/kvnoimg.png";
				if($ret['type'] == 1 && $ret['mime'] == '')
				{
					$ret['url_pre_view'] = $noimg;
					$ret['url_pre_view_2'] = $noimg;
					$ret['noimg'] = 1;
					$ret['url_open'] = "";
					$ret['type_file'] = "";
					$ret['cnt_views'] = 0;
					$ret['cnt_dwns'] = 0;
					$ret['time_last_view'] = 0;
					return $ret;
				}else
				{
					$idtask = 0;
					if ( $ret['make_work'] > 0 ) 
					{
						
						$idtask = $gKDisk_Base->is_task($name_file, 2);
						if( !$idtask )
						{
							$idtask = $gKDisk_Base->is_task($name_file, 3);
						}
						if( !$idtask )
						{
							$idtask = $gKDisk_Base->is_task($name_file, KD_TASK_COMBIME_VIDEO_AUDIO);
						}
						if( !$idtask )
						{
							$ret['make_work'] = 0;
							$gKDisk_Base->set_start_make_work($name_file, 0);
						}
					}
					
					return array( 	'url_open' => $ret['url_open'],
									'url_pre_view' => "/" . $this->m_folders['disk'] . "/" . $this->m_folders['preview'] . "/" . $ret['url_open'] . "/?size=1&t=".((int)$ret['UNIX_TIMESTAMP(ftime)']^(int)$ret['size_file']),
									'url_pre_view_2' => "/" . $this->m_folders['disk'] . "/" . $this->m_folders['preview'] . "/" . $ret['url_open'] . "/?size=2&t=".((int)$ret['UNIX_TIMESTAMP(ftime)']^(int)$ret['size_file']),
									'make_work' => $ret['make_work'],
									'idtask'	=> $idtask,
									'mime'		=> $ret['mime'],
									'type_file'	=> $ret['type_file'],
									'cnt_views'	=> $ret['cnt_views'],
									'cnt_dwns'	=> $ret['cnt_dwns'],
									'time_last_view' => $ret['time_last_view'],
									'uName' => $ret['uName'],
									
								);
				}
			}
		}
		return 0;
	}
	
	//Обрабатываем файл добавляем в базу
	function kv_mydisk_set_file( $name_file, $ures_identificator, $user_prm )
	{
		$gKDisk_Base = $this->m_KDisk_Base;
		$url_open = $gKDisk_Base->get_open_url_file( $name_file );
		
		if( $url_open )
		{
			return $url_open;
		}
	
		$ures_identificator = $this->m_folders['dir_user'];
	
		//Маке links
		$direct_link 		= substr( $name_file, strpos( $name_file, $this->m_folders['users_files'] ) - 1 );
		$url_open 			= KDisk_generate_string( 32 );
		$crc32_url_open 	= crc32($url_open);
	
		$infofile = array( 'size' => array(0,0));
		$infofile['img_data_1'] = '';
		$infofile['img_data_2'] = '';
		$infofile['type_file'] = '';
		if ( is_dir ($name_file) )
		{

			$type = 0;
		}elseif ( is_file ($name_file) )
		{
			$type = 1;
		}else
		{
			$type = 2;
		}
	
	
	
		$file_info = array( 'name_file' 		=> $name_file, 				//
							'crc32_name_file' 	=> crc32($name_file),		//
							'direct_link'		=> $direct_link, 			//
							'url_open'			=> $url_open,				//
							'crc32_url_open'	=> $crc32_url_open,			//
							'size_file'			=> filesize( $name_file ),  //
							'image_pre_1'		=> $infofile['img_data_1'], //
							'image_pre_2'		=> $infofile['img_data_2'], //
							'type'				=> $type,					//
							'type_file'			=> $infofile['type_file'],	//
							'cx'				=> $infofile['size'][0],
							'cy'				=> $infofile['size'][1],
							'user_prm'			=> $user_prm, 				//Как правило id usera
//							'ftime'				=> 
						
						);
						

		$gKDisk_Base->add_file_info_in_base( $file_info );
			
		$allsize = KDisk_SizeFileOrDir( $this->get_full_dir_cur_user() ) + KDisk_SizeFileOrDir ( $this->get_full_dir_cur_user() . "_" . $this->m_folders['trash'] );
		$gKDisk_Base->update_param_user( $ures_identificator, array( "mem_busy" => $allsize));
		
		//
		$path_info = pathinfo($name_file);
		if ( isset( $path_info ) && isset( $path_info['extension'] ))
		switch(strtolower($path_info['extension']))
		{
			case 'mts':
			{//задача для преобразования файла в mp4
			
				$gKDisk_Base->add_task( $name_file, 2 );
				$gKDisk_Base->set_start_make_work( $name_file, 1 );
			}
			break;
		}
		
	}
	///выполняем задачу при любом запросе 
	function kv_mydisk_cron_step()
	{
		//запустим php в фоне
		$gKDisk_Base = $this->m_KDisk_Base;
		$tbl_user_files=$this->m_initialize_params['db_table_prefix'] . $this->service_words['table_user_files'];
		$tbl_tasks = $this->m_initialize_params['db_table_prefix'] . $this->service_words['table_tasks'];
		$tbl_stat_tmp = $this->m_initialize_params['db_table_prefix'] . $this->service_words['table_stat_tmp'];
		$phpexec = 'php'; 
		$execmd = $phpexec . ' ' . __DIR__ . '/include/class-kdisk-tasks.php host=' . $gKDisk_Base->m_adr . ' user=' . $gKDisk_Base->m_user . ' password=' . $gKDisk_Base->m_pass . ' name=' . $gKDisk_Base->m_db . ' tmpdir=' . $this->m_KDisk_Folders->get_full_dir_temporary() . ' table_user_files=' . $tbl_user_files . ' tbl_tasks=' . $tbl_tasks . ' tbl_stat_tmp=' . $tbl_stat_tmp . ' > /dev/null 2>/dev/null &';
		exec( $execmd, $ret_var );
		return;
		
	}
	function clear_lost_files()
	{
		$this->m_KDisk_Base->add_task("clear_lost_files",KD_TASK_CLEAR_LOST_FILE);
	}
	/*
	* Удаление плагина
	*/
	function delete_plugin()
	{
		$base = $this->m_KDisk_Base;
		$base->delete_tables($this->get_full_dir_user_files());
	}
	function update_plugin()
	{
		$base = $this->m_KDisk_Base;
		$base->modify_tables();
	}
}