<?php
define("KDISK_EXT_FILE_PATCH", "patch");//рассширение в корзине для служебного файла восстановления пути
//JS запросы
class KDisk_In_Command
{
	private $m_params;  
	private $m_ret_ar; 
	private $m_get, $m_post, $m_json_array, $m_json_object;
	private $call_function = array();
	private $m_base;
	private $m_KDisk_Folders;
	
	public function set_KDisk_Folders( $object )
	{
		$this->m_KDisk_Folders = $object;
	}

	public function set_base( $obj_base )
	{
		$this->m_base = $obj_base;
	}
	public  function Set_Params( array $params  )
	{
		$this->m_params = $params;
	}
	//Вернём директорий всех пользователей
	function get_url_all_users_files()
	{
		return "/" . $this->m_params['folder_user_files'];
	}
	
	//
	function get_url_user_files()
	{
		return $this->m_params['folder_user_files'] . "/" . $this->m_params['folder_user'];
	}
	//
	function get_url_user_current()
	{
		return $this->get_url_page_drive() . $this->m_params['folder_user_current'];
	}
	function get_url_page_drive()
	{
		return $this->m_params['folder_disk']."/".$this->m_params['folder_drive'];
	}
	function get_url_page_trash()
	{
		return $this->m_params['folder_disk']."/".$this->m_params['folder_trash'];
	}
	function get_dir_user()
	{
	 	return $this->m_params['dir_full_user_files'] . "/" . $this->m_params['folder_user'];
	}
	function get_dir_user_trash()
	{
	 	return $this->m_params['dir_full_user_files'] . "/" . $this->m_params['folder_user'] . "_" . $this->m_params['folder_trash'];
	}
	
	function get_dir_user_current()
	{
	 	return $this->m_params['dir_full_user_files'] . "/" . $this->m_params['folder_user'] . "/" . $this->m_params['folder_user_current'];
	}
	function set_pre_processing_command( $cmd, $call_function)
	{
		$call_function[ count($call_function) ] = array("cmd" => $cmd, "call" => $call_function);
	}
	//Обработка команд 
	public function Set_Command( array $get, array $post )
	{
		$list_commands = array ( 
			'kr_open_link' 		=> 	'make_open_link',		//Запросили ссылку на файлик
			'kv_delete_trash' 	=> 	'make_delete_trash', 	//Удалить файл навсегда ( "навсегда" - для пользователя, на самом деле, переносится в гдобальную корзину )
			'kv_clear_trash' 	=> 	'make_clear_trash',		//Очищаем корзину пользователя 
			'kv_recover'		=>	'make_recover',			//Восстановить из корзины
			'kv_dwn_zip'		=>	'make_dwn_zip',			//Архивация файликов и директорий
			'kv_pre_dwn_zip'	=>	'make_pre_dwn_zip',		//
			'kv_prog_make_zip'	=>	'make_prog_make_zip',	//
			'kv_download'		=>	'make_download',		//Хотят скачать. Если один файлик, то как есть, а если директорий, то zip.
			'kv_dblclick'		=>	'make_dblclick',		//Двойной клик на файлике или папке	
			'kv_mkdir'			=>	'make_mkdir',			//Хотят создать папку
			'kv_remove'			=>	'make_remove',			//Перенос в корзину
			'kv_trash'			=>	'make_trash',			//Отдаём ссылку на корзину	
			'kv_prog_makework'	=> 	'make_prog_makework',	//отдадим прогресс работы над файлом
			'kv_make_video'		=>	'make_make_video',		//Ставим в очередь на конвертацию видео
			'kv_get_preview'	=>	'make_get_preview',		//Отдадим превьюшки 	
			'kd_get_info_file'	=>	'make_get_info_file',	//Информация о файле
//			'kd_get_info_file'	=>	'make_get_info_file',	//Информация о файле
			'kd_set_info_file'	=>	'make_set_info_file',	//Информация о файле
			'kd_stat'			=>	'make_statistics',
			'kd_take_audio'		=>	'make_take_audio',		//Выделить звуковую дорожку
			'kd_combine_video_audio' => 'make_combine_video_audio',	//Объеденим видео и звук
		
		);
		$this->m_get = $get;
		$this->m_post = $post;
		
		if ( isset($post) && isset( $this->m_post['json'] ))	{
			
			
			$this->m_json = (json_decode( stripslashes( $this->m_post['json'] )));
			
			if(function_exists('rest_sanitize_object'))
			{
				$this->m_json = rest_sanitize_object($this->m_json);
			}else
			{
				$this->m_json = (array)($this->m_json);
			}
			
			$json = $this->m_json;
			
			if ( is_array( $json ) )
			{
				foreach ($json as $key => $value) {
					$this->m_json_array[ $key ] = KDisk_trim_path ( ( $value ));
				}
/*				for ($f = 0; $f < count( $json ); $f++)
				{
					if(isset($json[ $f ]))
					$this->m_json_array[ $f ] = KDisk_trim_path ( ( $json[ $f ] ));	//urldecode
				}*/
			}
		}
		
		
		$this->m_ret_ar = array( "err" => 0 );
		$fun = 0;
		foreach ( $list_commands as $key => $value )
		{
			if ( isset( $get[ $key ] ) )
			{
				$fun = $value;
				break;
			}
		}
		if ( $fun )
		{ 
			$ret = $this->$fun(); 
			return $this->m_ret_ar;
		}
		
		$Up = new KDisk_Upload;
		$Up->dir_main = "/".$this->m_params['folder_disk'];
		$Up->upload_dir = $this->get_dir_user_current();
		$Up->temporary_dir = $this->m_KDisk_Folders->get_full_dir_temporary();
		$Up->url_request = "/".$this->m_params['folder_disk']."/";
		$Up->funTestFreeSize = array($this, "callBackUpFileTstSize");;
		$Up->funCallBackUpload = array($this, "callBackUpFile");

		if ($this->m_params['user_access'] & 2)
		{
			$Up->upload();
		}
		
		$this->m_ret_ar['err'] = -1;
		$this->m_ret_ar['errstr'] = "Unknow command!";
		
		return $this->m_ret_ar;
	}

	function make_view_from_stat( $name_file )
	{
		/*$Base = $this->m_base;
		$path = $this->m_KDisk_Folders->get_full_dir_temporary() ."/stat";
		if( $handle = opendir( $path ) )
		{
			$view = 0;
			$sizedwn = 0;
			$files_stat = array();
			$cnt_files_stat = 0;
			while( $entry = readdir( $handle ) )
			{
		    	if (strpos($entry, $_COOKIE['kdisk-view-id']) > 0)
				{
					$fi_full_name = $path . "/" . $entry;
					$fi = fopen($fi_full_name, "r");
					$val = fread($fi,5000);
							
					$arV = explode("\n",$val);
					$values = array();
					for($a=0; $a < count($arV); $a++)
					{
						$ar1 = explode(": ",$arV[$a]);
						if (count($ar1)>1)
						{
							$values[$ar1[0]] = $ar1[1];
						}else
						{
							break;
						}
					}
					if ( strcmp($name_file,  $values['file']) === 0 )
					{
						$files_stat[ $cnt_files_stat++ ] = $fi_full_name;
						$sizedwn += $values['size-dwn'];
					}
				}
			}
			$sizeV = filesize($name_file) / 1.1;
			if ( $sizedwn && $sizeV )
			while ( $sizedwn >= $sizeV )
			{
				$sizedwn -= $sizeV;
				for($d = 0; $d < $cnt_files_stat; $d++)
				{
					unlink($files_stat[$d]);
				}
				
				$retadd = $Base->view_file($name_file,crc32($_COOKIE['kdisk-view-id']));
			}
		}*/
	}
	public function make_statistics(){
		$ret = array("err" => 0);
		if ( isset( $this->m_json_array ))
		{
			$ar = (array)$this->m_json_array;
			switch( $ar['action'] )
			{
				case 'dfocus':
				{
//					break;
					$urlview = $this->m_KDisk_Folders->get_url_disk_view();
					if ( strpos($urlview, $ar['url'] ) == 0 )
					{
						$openurl = substr( $ar['url'],strlen($urlview));
						$info_file = $this->m_base->get_info_file_from_open_url($openurl);
						if ( $info_file )
						{
							$this->make_view_from_stat( $info_file['name_file'] );
							$ret['url'] = $info_file['name_file'];
						}
					}
				}
				break;
				case 'wtime':
				{
					$this->make_view_from_stat( $_SERVER['DOCUMENT_ROOT'] . "/" . $ar['src'] );
					
				}
				break;
			}
			
		}
		$this->m_ret_ar = $ret;
	}
	public function make_set_info_file(){
		$ret = array("err" => 0);
		if ( isset( $this->m_json_array ))
		{
			$ar = $this->m_json_array;
			$atn = (array)$ar[ 0 ];
			$name_file = $this->get_dir_user() . "/" . KDisk_trim_path( urldecode( $atn['file'] ) );
//			$ret['file'] =  $name_file ;
			
			$arup = array( 'crc32_name_file' => crc32( $name_file ), 'name_file' => $name_file );
//				$ar = array( 'crc32_name_file' => crc32( $name_file ), 'name_file' => $name_file, 'image_pre_1' => '');

			for ( $i = 1; $i < count($ar) ; $i++ )
			{
				$at = (array)$ar[ $i ];
				if (isset($at['uname']))$arup['uName']=$at['uname'];else
				if (isset($at['desc']))$arup['Description']=$at['desc'];
				if (isset($at['name']))$new_name_file = $this->get_dir_user() . "/" . dirname( KDisk_trim_path( urldecode( $atn['file'] ) ) ) . "/" . KDisk_trim_path( urldecode( $at['name'] ) );
				
			}
//			$ret['arup']=$arup;
	//		$ret['newname'] = $new_name_file;
			$Base = $this->m_base;
			$Base->update_info_file( $arup );
			
			//Переименовываем файл
			if ( isset($new_name_file) )
			{
		//		$ret['newname'] = $new_name_file;
			//	$ret['direct_link'] = $direct_link;
				
				$direct_link 		= substr( $new_name_file, strpos( $new_name_file, $this->get_url_all_users_files() )  );
				$arup['direct_link']= substr( $name_file, strpos( $name_file, $this->get_url_all_users_files() )  );
				$Base->rename_file($arup, $new_name_file, $direct_link);
				$ret['gourl'] = "/" . $this->get_url_user_current() . (dirname( KDisk_trim_path( urldecode( $atn['file'] ) ) )) . "/";
			}
			
			

		}
		$this->m_ret_ar = $ret;
	}
	public function make_get_info_file()
	{
		$ret = array("err" => 0);
		if ( isset( $this->m_json_array ))
		{
			
			$ar = $this->m_json_array;
			$Base = $this->m_base;
			$href = $ar[ 0 ];
			
			$file = KDisk_trim_path( urldecode( $href ) ); 
				
			$ar = explode ("/",$file);
			$name = $ar[ count($ar) - 1 ];
			
			$ending = "_" . ( microtime(true) * 10000 );
			$name_trash = $name . $ending;
			$pach = "";
			for ( $i = 0; $i < count($ar) - 1; $i++ )
			{
				$pach.="/".$ar[ $i ];
			}
				
			$old_name_file = $this->get_dir_user() . "/" . $file;
			$opurl = $Base->get_open_url_file( $this->get_dir_user() . "/" . $file );
			if (!$opurl)
			{
				$ret['opurl']['url_open'] = $file;
			}else
			{
				$ret['opurl'] = $opurl;
			}
			$info = $Base->get_info_file_from_open_url( $ret['opurl']['url_open'] );
			$ret['info'] = $info;
			$ar = explode ("/",$info['name_file']);
			$name = $ar[ count($ar) - 1 ];
			//$this->m_ret_ar = $ret;
			//return;
			$ret['inf'] = array('name' => $name,
								'type' => $info['type'],
								'direct_link' => $info['direct_link'],
								'mime' => $info['mime'],
								'type_file' => $info['type_file'],
								'gourl' => $this->m_KDisk_Folders->get_url_disk_view() . "/" . $info['url_open'],
								'uftime' => $info['uftime'],
								'uname' => $info['uName'],
								'desc' => $info['Description'],
								'cnt_views' => $info['cnt_views'],
								);
			$head = esc_html__("File", 'kdisk');
			if ( $info['type'] == 0 ) $head = esc_html__("Folder", 'kdisk');  		
			
			$htm = '<div class="kd-info-file"><button class="kd-but-close"></button><p class="kd-title">' . $head . '</p><table>';
			
			if ($this->m_params['user_access'] & 2)
			{
				$htm .= '<tr><td>' . esc_html__("Title", 'kdisk') . '</td><td><input class="kd-info-file-title" type="text" value="' . esc_html($info['uName']) . '"></td></tr>';
			$htm .= '<tr><td>' . esc_html__("Name", 'kdisk') . '</td><td><input class="kd-info-file-name" type="text" value="' . esc_html($name) . '"></td></tr>';
			}else
			{
				if( $info['uName'] != "" )$htm .= '<tr><td>' . esc_html__("Title", 'kdisk') . '</td><td><div class="kd-info-file-title">' . esc_html($info['uName']) . '</div></td></tr>';
				$htm .= '<tr><td>' . esc_html__("Name", 'kdisk') . '</td><td><div class="kd-info-file-name">' . esc_html($name) . '</div></td></tr>';
			}
			if ( $info['cnt_views'] )
			$htm .= '<tr><td>' . esc_html__("Views", 'kdisk') . '</td><td>' . esc_html($info['cnt_views']) . '</td></tr>';
			if ( $info['cnt_dwns'] )
			$htm .= '<tr><td>' . esc_html__("Downloaded", 'kdisk') . '</td><td>' . esc_html($info['cnt_dwns']) . '</td></tr>';
			
			$htm .= '<tr><td>' . esc_html__("Size", 'kdisk') . '</td><td>' . esc_html($info['size_file']) . ' ' . esc_html__("bytes", 'kdisk') . '</td></tr>';
			
			$htm .= '<tr><td>' . esc_html__("Time", 'kdisk') . '</td><td class="td-in-utime">' . esc_html($info['uftime']) . '</td></tr>';
			
		if ($this->m_params['user_access'] & 2)
		{
			$htm .= '<tr><td colspan="2"><p class="kd-desc">' . esc_html__("Description", 'kdisk') . '</p></td></tr>';
			$htm .= '<tr><td colspan="2"><textarea class="kd-info-file-desc">' . esc_html($info['Description']) . '</textarea></td></tr>';	}else
		{
			if( strlen($info['Description']) )
			{
				$htm .= '<tr><td colspan="2"><p class="kd-desc">' . esc_html__("Description", 'kdisk') . '</p></td></tr>';
				$htm .= '<tr><td colspan="2"><div class="kd-info-file-desc">' . esc_html($info['Description']) . '</div></td></tr>';
			}
		}
			$htm .= '<tr><td>&nbsp;</td></tr><tr><td colspan="2" style="text-align: center;"><button class="kd-but-ok">' . esc_html__("Ok", 'kdisk') . '</button></td></tr>';
			$htm .= '</table></div>';					
			$ret['htm'] = $htm;
		}
		$this->m_ret_ar = $ret;
	}
	public function make_get_preview()
	{
		if ( isset( $this->m_json_array ))
		{
			$arout = array(0);
			$gKDisk_Base = $this->m_base;
			$ar = $this->m_json_array;
			$this->m_ret_ar['err'] = 0;
			$cntout = count($ar); 
			if ( $cntout > 10)$cntout = 10;
			for( $i=0; $i < $cntout; $i++ )
			{
			//	if ($cntout > 10)break;
				$arout[$i] = 0;
				$href = $ar[ $i ];
				$ret= $gKDisk_Base->get_open_url_file( $this->get_dir_user() . "/" . $href , "/" . $this->get_url_user_files() . "/" . $href );
//				$infofile = $gKDisk_Base->get_info_file_from_open_url( $href );
			//	$open_url = array(0);
				
				if($ret['type'] == 1 && $ret['mime'] == '')
				{
					//$this->m_ret_ar['pre0'] = "!";
				}else
				{
					if( ! $ret['url_open'] )
					{
						
					}else
					{
						$this->m_ret_ar['pre0'] = 1;
						$arout[$i] = $this->m_KDisk_Folders->get_url_disk_preview() . "/" . $ret['url_open'] . "/?size=2&".time();

					}
				}
			}
			
			
			$this->m_ret_ar['preview'] = $arout;
		}
		
	}
	public function callBackUpFileTstSize( $dir, $size )
	{
		if ( $size > $this->m_params['user_free_size'] )return 0;
		$freespace = disk_free_space( $dir ) - $this->m_params['min_free_space'];
		if ( $size > $freespace )return 0;
		return 1;
	}
	
	
	public function callBackUpFile( $dir_user_cur, $error )
	{
		$gourl = "/" . $this->get_url_page_drive() . "/" .$dir_user_cur;
		if ( ! $error )
		{
			$ret = array("err" => $error, "upload" => 1, "gourl" => $gourl);
		}else
		{
			$ret = array("err" => $error, "upload" => 0, "errtxt" => __("No free disk space.", 'kdisk'), "gourl" => $gourl);
		}
		echo json_encode( $ret );
	}
	function make_combine_video_audio()
	{
		if ( isset($this->m_post['json'] ))	{
			$gKDisk_Base = $this->m_base;	
			$json = $this->m_json;
			
			//echo "combine: " .  KDisk_trim_path( urldecode( $json[ 0 ] ) );
			
			if ( count( $json ) != 2)return;
			
			$files = array();
			$files['file1'] = KDisk_trim_path( urldecode( $json[ 0 ] ) );
			$files['file2'] = KDisk_trim_path( urldecode( $json[ 1 ] ) );
			
			$ar = explode ("/", $files['file1']);
			$pach = "";
			for ( $i = 0; $i < count($ar) - 1; $i++ )
			{
				$pach.="/".$ar[ $i ];
			}
			$this->m_ret_ar["gourl"] = "/" . $this->get_url_page_drive() . "/" . KDisk_trim_path( $pach ) . "/";
			
			$files['file1'] = $this->get_dir_user() . "/" . $files['file1'];
			$files['file2'] = $this->get_dir_user() . "/" . $files['file2'];
			
			
			$gKDisk_Base->add_task( $files, 4 );
			

			$ret_info1 = $gKDisk_Base->get_open_url_file( $files['file1'] );
			$ret_info2 = $gKDisk_Base->get_open_url_file( $files['file2'] );
			
			$this->m_ret_ar['video1'] = $ret_info1['type_file'];
			$this->m_ret_ar['video2'] = $ret_info2['type_file'];
			if ( $ret_info1['type_file'] == 'video' )
			{
				$gKDisk_Base->set_start_make_work( $files['file1'], 1 );
			}else
			{
				$gKDisk_Base->set_start_make_work( $files['file2'], 1 );
			}
			

			
		}
	}
	function make_take_audio()
	{
		if ( isset($this->m_post['json'] ))	{
			$gKDisk_Base = $this->m_base;	
			$json = $this->m_json;
			for ($f = 0; $f < count( $json ); $f++)
			{
				$href = $json[ $f ];	
				$file = KDisk_trim_path( urldecode( $href ) );
				$ar = explode ("/",$file);
				$pach = "";
				for ( $i = 0; $i < count($ar) - 1; $i++ )
				{
					$pach.="/".$ar[ $i ];
				}
				$this->m_ret_ar["gourl"] = "/" . $this->get_url_page_drive() . "/" . KDisk_trim_path( $pach ) . "/";
				
				$name_file = $this->get_dir_user() . "/" . $file;
				$gKDisk_Base->add_task( $name_file, 3 );
				$gKDisk_Base->set_start_make_work( $name_file, 1 );

			}
		}
		
	}

	//Ставим в очередь на конвертацию видео
	function make_make_video()
	{
		if ( isset($this->m_post['json'] ))	{
			$gKDisk_Base = $this->m_base;	
			$json = $this->m_json;
			for ($f = 0; $f < count( $json ); $f++)
			{
				$href = $json[ $f ];	
				$file = KDisk_trim_path( urldecode( $href ) );
				$ar = explode ("/",$file);
				$pach = "";
				for ( $i = 0; $i < count($ar) - 1; $i++ )
				{
					$pach.="/".$ar[ $i ];
				}
				$this->m_ret_ar["gourl"] = "/" . $this->get_url_page_drive() . "/" . KDisk_trim_path( $pach ) . "/";
				
				$name_file = $this->get_dir_user() . "/" . $file;
				$gKDisk_Base->add_task( $name_file, 2 );
				$gKDisk_Base->set_start_make_work( $name_file, 1 );

			}
		}
		
	}
	//Отдаём прогресс работы над файлом
	function make_prog_makework()
	{
		$in = $this->m_json;
		$json = $in['tasksids'];
		$this->m_ret_ar['prg'] = array();
		for( $i = 0; $i < count( $json ); $i++)
		{
			$info = $this->m_base->get_task_from_id( $json[$i] );
			if ( $info )
			{
				$this->m_ret_ar['prg'][$i] = array("id" => $info['id'],"prg" => $info['progress'],"remain" => $info['remain'],"time" => (time() - strtotime($info['time_start'])));
			}else
			{
				$this->m_ret_ar['prg'][$i] = array("id" => $json[$i],"prg" => 100,"remain" => 0);
			}
		}
	}
	//Отдаём ссылку на корзину	
	function make_trash()
	{
		$this->m_ret_ar["gourl"] = "/" . $this->get_url_page_trash();
		return;
	}
	//Перенос в корзину (удалить)
	function make_remove()
	{
		$dir_trash = $this->get_dir_user_trash();
		if ( !is_dir($dir_trash) ) {
			mkdir( $dir_trash, 0755, true );
		}
		if ( isset($this->m_post['json'] ))	{
			
			$json = $this->m_json;
			$mydir_trash = $this->get_dir_user_trash();		
			for ($f = 0; $f < count( $json ); $f++)
			{
				$href = $json[ $f ];	
				//$ar = parse_url( urldecode( $href ));
				$file = KDisk_trim_path( urldecode( $href ) ); // ( urldecode($this->m_get['href']) , " \n\r\t\v\0/" );		
				
				$ar = explode ("/",$file);
				$name = $ar[ count($ar) - 1 ];
				$ending = "_" . ( microtime(true) * 10000 );
				$name_trash = $name . $ending;
				$pach = "";
				for ( $i = 0; $i < count($ar) - 1; $i++ )
				{
					$pach.="/".$ar[ $i ];
				}
				$this->m_ret_ar["gourl"] = "/" . $this->get_url_page_drive() . "/" . KDisk_trim_path( $pach ) . "/";
				
				$old_name_file = $this->get_dir_user() . "/" . $file;
				$new_name_file = $dir_trash . "/" . $name_trash;

				$r = KDisk_Rename( $old_name_file, $new_name_file );
				$fi = fopen( $dir_trash . "/" . $name_trash . "." . KDISK_EXT_FILE_PATCH, "w+");
				fwrite( $fi, $pach );
				
				//Отметим в базе удаление
				$gKDisk_Base = $this->m_base;
				$gKDisk_Base->remove_file( $old_name_file, $new_name_file );
	
			}
			return;
		}
		
		$file = trim ( urldecode($this->m_get['href']) , " \n\r\t\v\0/" );
	
		$ar = explode ("/",$file);
		$name = $ar[ count($ar) - 1 ];
		$name_trash = $name."_".(microtime(true) * 10000);
		$pach = "";
		for ( $i = 0; $i< count($ar) - 1; $i++ )
		{
			$pach .= "/" . $ar[ $i ];
		}
		$this->m_ret_ar["gourl"] = "/" . $this->get_url_page_drive() . "/" . KDisk_trim_path($pach);
		$r = KDisk_Rename($this->get_dir_user_current() . "/" . $pach . "/" . $name, $dir_trash . "/" . $name_trash);
		$fi = fopen($dir_trash . "/" . $name_trash . "." . KDISK_EXT_FILE_PATCH, "w+");
		fwrite($fi,$pach);
		
		
		//Отметим в базе удаление
		$gKDisk_Base = $this->m_base;
		$gKDisk_Base->rename_file( $this->get_dir_user_current() . "/" . $pach . "/" . $name, $dir_trash . "/" . $name_trash );
	
		return;
	}
	//Создаём новую папочку для пользователя
	function make_mkdir()
	{
		
		$json = $this->m_json;
		$dir = $json[0];
		while ($dir[strlen($dir)-1] == '.') {
		    $dir = substr($dir,0,-1);
		}
		$dneed = "/".$this->get_url_page_drive();
		if (strpos($dir, $dneed) == 0)
		{
			$dir = substr($dir, strlen($dneed) );
		}
		$path = $this->get_dir_user();
		
		$dir = KDisk_trim_path($dir);
		$dir = trim($dir);

		if ( !@mkdir($path . "/" . $dir ,0755, true ) )
		{
			$this->m_ret_ar["err"] = 1;
			$this->m_ret_ar["err_ar"] = error_get_last();
		}else
		{
			$this->m_ret_ar["gourl"] = "/" . $this->get_url_page_drive() . "/" . $dir . "/";
		}
	
		return;
	}
	//Обрабатываем двойной клик.
	//Либо в новую папочку либо на файле
	function make_dblclick()
	{
		$gKDisk_Base = $this->m_base;
		if ( isset( $this->m_post['json'] ))
		{
			$json = $this->m_json;
	
			$href = urldecode( $json[ 0 ] );
			
			$infofile = $gKDisk_Base->get_info_file_from_open_url( $href );
			if ( $infofile )	
			{
				$this->m_ret_ar['gourl'] = $this->m_KDisk_Folders->get_url_disk_view() . "/" . $href;
//				$this->m_ret_ar['gourl'] = "/" . $this->m_params['folder_disk'] . "/" . KV_DIR_VIEW . "/" . $href;

				return;
			}
	
//			$this->m_ret_ar['err'] = 1;
	//		return;
		}

/*		if ( ! isset( $this->m_get['href'] ) )
		{
			$this->m_ret_ar['err'] = 1;
			$this->m_ret_ar['errstr'] = "No argument.";
			return ;
		}
		$href = urldecode( $this->m_get['href'] );*/
		$ar = parse_url( $href );
		$path = $this->get_dir_user_current();
		
		if ( is_dir( $path.$ar['path'] ) )
		{
			$this->m_ret_ar['gourl'] = "/" . $this->get_url_page_drive() . str_replace ( '//', '/',$href);
			
		}else
		{
			$name_file = str_replace ( '//', '/',$path.$ar['path']);
			$name_file = str_replace ( '//', '/',$name_file);
			$ret = $gKDisk_Base->get_open_url_file( $name_file );//, "/" . $this->get_url_user_files() . "/" . $href );
			if(!$ret || !isset($ret['url_open']))
			{
				
			}else
			{
				$this->m_ret_ar['gourl'] = $this->m_KDisk_Folders->get_url_disk_view() . "/" . $ret['url_open'];
			}


		}
		

		
		
		return ;
	}
	function make_download()
	{
		$json = $this->m_json;
		$href = urldecode( $json[ 0 ] );
		if ( $href[0] != "/" )
		{
			$info = $this->m_base->get_info_file_from_open_url( $href );
			$this->m_ret_ar['dwnurl'] = $info['direct_link'];
			return;
		}
		$this->m_ret_ar['dwnurl'] = "/" . $this->get_url_user_files() . str_replace ( '//', '/',$href);
	}
	//Запрашивают сколько уже заархивировалось
	function make_prog_make_zip()
	{
		$dir = $this->m_params['dir_full_user_files'] . '/temporary/' . $this->m_get['tkey'];
		$this->m_ret_ar['tkey'] = $this->m_get['tkey'];
		$this->m_ret_ar['size'] = KDisk_SizeFileOrDir( $dir );
		$this->m_ret_ar['size_txt'] = KDisk_make_size_file( $this->m_ret_ar['size'] );
	}
	//Перед отправкой и формированием архива на скачивание
	function make_pre_dwn_zip()
	{
		$path = $this->get_dir_user();
		
		
		$json = $this->m_json;
		$name_files = array();
		
		for ($i = 0; $i < count( $json ); $i++)
		{
			$href = urldecode( $json[ $i ] );
			if ( $href[0] != "/" )
			{
				$info = $this->m_base->get_info_file_from_open_url( $href );
				if ( $info )$name_files[ $i ] = $info['name_file'];
			}else
			{
				$name_files[ $i ] = $path . urldecode( $href );
			}
		}
		$all_size = 0;
		for( $i = 0; $i < count($name_files); $i++ )
		{
			$all_size += KDisk_SizeFileOrDir( $name_files[$i] , 0);
		}
		
		$limit_size = 25*1024*1024*1024;
		$this->m_ret_ar['size'] = $all_size;
		$this->m_ret_ar['size_txt'] = KDisk_make_size_file($all_size);
		$this->m_ret_ar['size_limit'] = $limit_size;
		
		if ( $all_size > $limit_size)
		{
			$this->m_ret_ar['txt_msg'] = esc_html(__('Total file size','kdisk')) . " " . $this->m_ret_ar['size_txt'] . "\n" . esc_html(__('Archive cannot exceed','kdisk')) . " " . KDisk_make_size_file($limit_size);
			$this->m_ret_ar['tkey'] = 0;
		}else
		{
			$this->m_ret_ar['txt_msg'] = esc_html(__('Download archive','kdisk')) . " " . KDisk_make_size_file($all_size). " ?";
			$this->m_ret_ar['txt_msg_dwnl'] = esc_html(__('Archiving process','kdisk'));
			$this->m_ret_ar['tkey'] = KDisk_generate_string(16); 
		}
		
		
	}
	//Просят заархивировать файлики пользователя
	function make_dwn_zip()
	{
		include __DIR__."/include/class-kdisk-archive.php";
		$Archive = new KDisk_Archive;
		$Archive->set_temporary_directory( $this->m_params['dir_full_user_files'] . '/temporary' );
		
		$path = $this->get_dir_user();
		
		
		$in = $this->m_json; 
		$json = $in['ar'];

		$name_files = array();
		for ($i = 0; $i < count( $json ); $i++)
		{
			$href = urldecode( $json[ $i ] );
			if ( $href[0] != "/" )
			{
				$info = $this->m_base->get_info_file_from_open_url( $href );
				if ( $info )$name_files[ $i ] = $info['name_file'];
			}else
			{
				$name_files[ $i ] = $path . urldecode( $href );
			}
		}
		

		$name_file_arc = $Archive->make_archive( $name_files, $in['tkey'] );
		$this->m_ret_ar['dwnurl'] = $this->get_url_all_users_files() . '/temporary/' . $Archive->get_short_name_archive();
		return;
	}
	//Восстанавливаем из корзины
	function make_recover()
	{
		$json = $this->m_json;
		$kv_folder = $json['kv_folder'];
		$json = $json['files'];
		
		$mydir_trash = $this->get_dir_user_trash();		

		for ($i = 0; $i < count( $json ); $i++)
		{
			$file_recocer = KDisk_trim_path( urldecode( $json[ $i ] ) );
			$file = $mydir_trash . "/" . $file_recocer;
			$file_patch = $mydir_trash . "/" . $file_recocer.".".KDISK_EXT_FILE_PATCH;
			$fi = fopen($file_patch,"r");
			$rdir = "";
			if ( filesize($file_patch) )
			{
				$rdir = fread($fi,filesize($file_patch));
			}
			$rdir = $this->get_dir_user() . $rdir;
			$ret['mkdir'] = @mkdir( $rdir, 0755, true );
			$filenew = $rdir . "/" . substr( $file_recocer, 0, strrpos( $file_recocer, "_" ));
			if ( is_file($filenew) )
			{
				
				$path_info = pathinfo($filenew);
			    $path_info['extension'];
				for($n = 1; $n < 1000; $n++)
				{
					$tnam = substr( $filenew, 0, strrpos( $filenew, "." )) . " (".$n.")." .$path_info['extension'];
					if ( !file_exists($tnam) )break ; 
					
				}
				
				$filenew = $tnam;
			}else
			if ( is_dir($filenew) )
			{
				for($n = 1; $n < 1000; $n++)
				{
					$tnam = $filenew . " (".$n.")";
					if ( !is_dir($tnam) )break ; 
					
				}
				$filenew = $tnam;
			}
			$r = KDisk_Rename( $file, $filenew );
							//Отметим в базе удаление
			$gKDisk_Base = $this->m_base;
			$gKDisk_Base->remove_file( $file, $filenew );

			
			$ret['rename'] = $r;
			
		}

		$this->m_ret_ar['gourl'] = "/" . $this->get_url_page_trash() . "/" ;
		return;
	}
	//Формируем открытую ссылочку на файл 
	function make_open_link()
	{
		$href = $this->m_json_array[ 0 ];
		$gKDisk_Base = $this->m_base;
		
		$infofile = $gKDisk_Base->get_info_file_from_open_url( $href );
		$open_url = array(0);
		if( ! $infofile )
		{
			$open_url = $gKDisk_Base->get_open_url_file( $this->get_dir_user() . "/" . $href , "/" . $this->get_url_user_files() . "/" . $href );
		}else
		{
			$open_url['url_open'] = $href;
		}
		
		
		$this->m_ret_ar['cpyurl'] = $this->m_KDisk_Folders->get_url_disk_view() . "/" . $open_url['url_open'];
//		$this->m_ret_ar['cpyurl'] = "/" . $this->m_params['folder_disk'] . "/" . KV_DIR_VIEW . "/" . $open_url;
		
		return ;
	}
	//Удалить из корзины
	//Переносим в общую корзину для всех пользователей
	function make_delete_trash()
	{
		$json = $this->m_json;
		$mydir_trash = $this->get_dir_user_trash();
		for ($i = 0; $i < count( $json ); $i++)
		{
			$file = KDisk_trim_path($json[ $i ]);
			$file_recover = KDisk_trim_path($json[ $i ]);
			$file = $mydir_trash . "/" . $file_recover;
			$file_patch = $mydir_trash . "/" . $file_recover.".".KDISK_EXT_FILE_PATCH;
			$oldfile = $file;

			$newfile = $this->m_KDisk_Folders->get_full_dir_alltrash() . "/" . $this->m_KDisk_Folders->m_folders['dir_user'] . "_" . KDisk_generate_string(10);
			
			if( ! is_dir( $newfile ) )
			{
				mkdir( $newfile, 0755, true );
			}
			$newfile .= "/" . $file_recover;
			
			$r = KDisk_Rename($oldfile, $newfile );
			$r = KDisk_Rename($oldfile.".".KDISK_EXT_FILE_PATCH, $newfile.".".KDISK_EXT_FILE_PATCH );
	
		}
		
		$this->m_ret_ar['gourl'] = "/" . $this->get_url_page_trash() . "/" ;
		
		$gKDisk_Base = $this->m_base;
		$gKDisk_Base->update_param_user_no_quotes( $this->m_params['folder_user'], array( 'time_last_delete' => 'now()') );
		
		
		$allsize = KDisk_SizeFileOrDir( $this->get_dir_user() ) + KDisk_SizeFileOrDir ( $this->get_dir_user_trash() );
		$gKDisk_Base->update_param_user( $this->m_params['folder_user'] , array( "mem_busy" => $allsize));

		
		return;
	}
	//Очичтить корзину пользователя
	//Переносим в общую корзину для всех пользователей
	function make_clear_trash()
	{
		//Удаляем корзину навсегда
		$json = $this->m_json;
		$mydir_trash = $this->get_dir_user_trash();
		$oldfile = $mydir_trash;
		$newfile = $this->m_KDisk_Folders->get_full_dir_alltrash() . "/" . $this->m_KDisk_Folders->m_folders['dir_user'] . "_" . KDisk_generate_string(10);
		mkdir( $newfile, 0755, true );
//		$this->m_ret_ar['newfile'] = $newfile;

		$r = KDisk_Rename( $oldfile, $newfile );
		$this->m_ret_ar['gourl'] = "/" . $this->get_url_page_trash() . "/" ;
		
		$gKDisk_Base = $this->m_base;
		$gKDisk_Base->update_param_user_no_quotes( $this->m_params['folder_user'], array( 'time_last_delete' => 'now()') );
		
		$allsize = KDisk_SizeFileOrDir( $this->get_dir_user() ) + KDisk_SizeFileOrDir ( $this->get_dir_user_trash() );
		$gKDisk_Base->update_param_user( $this->m_params['folder_user'] , array( "mem_busy" => $allsize));
		
		return;
	}
	

	
};
