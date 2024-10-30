<?
define ("KD_TASK_MAKE_MP4_H264", 2);
define ("KD_TASK_TAKE_AUDIO", 3);
define ("KD_TASK_COMBIME_VIDEO_AUDIO", 4);
define ("KD_TASK_CLEAR_LOST_FILE", 100);
class KDisk_Base extends KDisk_BaseMY
{
	private $m_name_table_users;
	private $m_name_table_files;
	public $m_name_table_task;
	public $m_name_table_stat_tmp;
	public $m_user_id = 0;
	function set_name_table_users ( $name_table_users )
	{
		$this->m_name_table_users = $name_table_users;
	}
	function set_name_table_task ( $name_table_task )
	{
		$this->m_name_table_task = $name_table_task;
	}
	//Таблица с инфой по всем файлам
	function set_name_table_files( $name_table_files )
	{
		$this->name_table_files = $name_table_files;
	}
	function set_name_table_stat_tmp( $name_table_stat_tmp )
	{
		$this->m_name_table_stat_tmp = $name_table_stat_tmp;
	}
	function log_task( $str )
	{
		global $KDisk_task;
		if (!isset($KDisk_task))return;
		$KDisk_task->kv_write_log( $str );
		
	}
	//Delete from table lostfiles
	function clear_lost_files()
	{
		//$mycmd = "SELECT name_file, id FROM " . $this->name_table_files;
		
		$mycmd = "SELECT count(*) FROM " . $this->name_table_files;
		$ret = $this->Query($mycmd);
		if ($ret)
		{
			$cnt = $ret[0]['count(*)'];
			//			
			
//			return 0;

//			$this->log_task( "cnt_files : " . $cnt  );
			for($i = 0; $i < $cnt; $i+=100)
			{
				$mycmd = "SELECT name_file, id FROM " . $this->name_table_files . " order by id LIMIT " . $i . ",100";
				$ret = $this->Query($mycmd);
				if ($ret)
				{
					
					for($j = 0; $j < count($ret); $j++)
					{
						if ( !is_file($ret[$j]['name_file']) && !is_dir($ret[$j]['name_file'])) 
						{
							$mycmd = "DELETE FROM " . $this->name_table_files . " WHERE id = " . $ret[$j]['id'] ;
//							$this->log_task( $mycmd );
							$this->Query($mycmd);
	//						return;
						}else
						{
						//	$this->log_task( "file_id: " . $ret[$j]['id']);
						}
					}
				}
				
			}
		}
		
	}
	//
	function inc_view_dwn_file($col, $name_file, $crcBr, $remote_adr)
	{
		if ( $this->add_view_file($name_file, $crcBr, $remote_adr) )
		{
			$ar = array( 'crc32_name_file' => crc32( $name_file ), 'name_file' => $name_file, $col => array("`" . $col . "` + 1"), 'time_last_view' => array("now()"));
			$this->update_info_file( $ar );
			$end = 100;
			$dir = $name_file;
			do
			{
				$dir = dirname($dir);
				$ar = array( 'crc32_name_file' => crc32( $dir ), 'name_file' => $dir, $col => array("`" . $col . "` + 1"), 'time_last_view' => array("now()"));
				$ret = $this->update_info_file( $ar );
				$end--;
				
			}while( $ret && $end > 0);
		}
	}
	function download_file($name_file, $crcBr, $remote_adr)
	{
		$this->inc_view_dwn_file('cnt_dwns', $name_file, $crcBr, $remote_adr);
	}
	function view_file($name_file, $crcBr, $remote_adr)
	{
		$this->inc_view_dwn_file('cnt_views', $name_file, $crcBr, $remote_adr);
	}
	function add_view_file($name_file, $crcBr, $remote_adr)
	{
		
		$mycmd = "INSERT INTO `" . $this->m_name_table_stat_tmp . "` (`name_file`,`crcBr`,`utime`,`ip`) VALUES ('" . $this->my->real_escape_string($name_file) . "','" . $crcBr . "',now(),'" . $remote_adr . "')";
		$ret = $this->Query($mycmd);

		if(!$ret)
		{
			
			switch ( $this->last_errno )
			{
				case 1062:
					$mycmd = "UPDATE `" . $this->m_name_table_stat_tmp . "` SET `utime` = now(), `ip` = '" . $remote_adr . "' WHERE `utime` < SUBDATE(now(), INTERVAL 1 MINUTE) && `crcBr` = '" . $crcBr . "' &&  `name_file` = '" . $this->my->real_escape_string($name_file) . "'";
					//echo $mycmd;
					$ret = $this->Query($mycmd);
					
					if ( is_array($ret) )
					{
						if ( $this->my->affected_rows )
						{
							return 1;
						}else
						{
							$mycmd = "UPDATE `" . $this->m_name_table_stat_tmp . "` SET `utime` = now(), `ip` = '" . $remote_adr . "' WHERE `crcBr` = '" . $crcBr . "' &&  `name_file` = '" . $this->my->real_escape_string($name_file) . "'";
							$ret = $this->Query($mycmd);
						}
						return 0;
					}else
					{
						//echo $this->last_error;
						//return 0;
					}
				break;
			}
			return 1;
		}
		return 1;
	}
	function get_info_file_from_open_url( $url_open )
	{
		$crc32_url_open = crc32( $url_open );
		$mycmd = "SELECT `name_file`, `direct_link`, `type`, `type_file`, `mime`, `Description`, `uName`, `url_open`,UNIX_TIMESTAMP(ftime) as uftime, `size_file`, `cnt_views`, `cnt_dwns`,UNIX_TIMESTAMP(time_last_view) as time_last_view FROM `" . $this->name_table_files . "` WHERE `crc32_url_open` = '$crc32_url_open' && `url_open` = '" . $this->my->real_escape_string($url_open) ."'";
		$ret = $this->Query($mycmd);
		if ( $ret )
		{
			return $ret[0];
		}
		return 0;
	}
	//Добавляем параметры файла в базе
	function add_file_info_in_base( $file_info )
	{
		
		$vlues = "(";
		$columns = "(";
		foreach ($file_info as $key => $value) {
 		  	$columns .= "`$key`,";
			$vlues .= "'" . $this->my->real_escape_string($value) . "',";
		}
		$columns .= "`ftime`";
		
		$ftim =  filemtime( $file_info['name_file'] ) ;
		if ( ! $ftim )$ftim=time();
		
		$vlues .= "FROM_UNIXTIME(" . $ftim  .")"; 
		$columns.=")";
		$vlues.=")";
		$mycmd = "INSERT INTO `" . $this->name_table_files . "` " . $columns . " VALUES " . $vlues ;
		$ret = $this->Query($mycmd);

		if(!$ret)
		{
			$this->ShowError(1);
//			echo $this->last_error;
			switch ( $this->last_errno )
			{
				
				case 1146:
				{//Если нет таблицы 
				/*CREATE TABLE `wp_krdisk_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time_add` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `name_file` char(255) NOT NULL,
  `crc32_name_file` int(10) unsigned NOT NULL,
  `url_open` char(32) NOT NULL,
  `mode_access` smallint(6) NOT NULL DEFAULT '0',
  `crc32_url_open` int(10) unsigned NOT NULL,
  `direct_link` char(255) NOT NULL,
  `type` int(11) NOT NULL DEFAULT '0',
  `type_file` char(32) NOT NULL DEFAULT '',
  `mime` char(128) NOT NULL DEFAULT '',
  `size_file` bigint(11) NOT NULL DEFAULT '0',
  `image_pre_1` blob,
  `image_pre_2` blob,
  `image_pre_3` mediumblob,
  `cx` int(11) NOT NULL DEFAULT '0',
  `cy` int(11) NOT NULL DEFAULT '0',
  `user_prm` int(10) unsigned NOT NULL,
  `time_add_old` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ftime` timestamp NOT NULL DEFAULT '2021-01-01 00:00:00',
  `make_work` int(11) NOT NULL DEFAULT '0',
  `json_info` char(255) NOT NULL DEFAULT '',
  `Description` char(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `uName` char(128) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `cnt_views` int(10) unsigned NOT NULL DEFAULT '0',
  `time_last_view` timestamp NOT NULL DEFAULT '2021-01-01 00:00:00',
  PRIMARY KEY (`name_file`,`crc32_name_file`),
  UNIQUE KEY `crc32_name_file` (`name_file`,`crc32_name_file`),
  UNIQUE KEY `url_open` (`url_open`),
  UNIQUE KEY `id` (`id`),
  KEY `crc32_url_open` (`crc32_url_open`),
  KEY `make_work` (`make_work`)
) ENGINE=MyISAM AUTO_INCREMENT=1168 DEFAULT CHARSET=utf8*/
					$mycmdceratetbl = "CREATE TABLE `" . $this->name_table_files . "` (
							  			`time_add` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
										`name_file` char(255) NOT NULL,
										`crc32_name_file` int(10) unsigned NOT NULL,
  										`url_open` char(32) NOT NULL,
  										`mode_access` smallint(6) NOT NULL DEFAULT '0',
  										`crc32_url_open` int(10) unsigned NOT NULL,
  										`direct_link` char(255) NOT NULL,
  										`type` int(11) NOT NULL DEFAULT '0',
  										`type_file` char(64) NOT NULL DEFAULT '',
										`mime` char(128) NOT NULL DEFAULT '',
  										`size_file` bigint(11) NOT NULL DEFAULT '0',
  										`image_pre_1` blob,
	  									`image_pre_2` blob,
  										`image_pre_3` mediumblob,
  										`cx` int(11) NOT NULL DEFAULT '0',
  										`cy` int(11) NOT NULL DEFAULT '0',
  										`user_prm` int(10) unsigned NOT NULL,
										`ftime` TIMESTAMP NOT NULL DEFAULT '2021-01-01 00:00:00',
  										`make_work` int(11) NOT NULL DEFAULT '0',
										`json_info` char(255) NOT NULL DEFAULT '',
										`Description` char(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
										`uName` char(128) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
										`cnt_views` int(10) unsigned NOT NULL DEFAULT '0',
										`time_last_view` timestamp NOT NULL DEFAULT '2021-01-01 00:00:00',
  										UNIQUE KEY `crc32_name_file` (`name_file`,`crc32_name_file`),
  										UNIQUE KEY `url_open` (`url_open`),
  										KEY `crc32_url_open` (`crc32_url_open`),
										KEY `make_work` (`make_work`)
  										) ENGINE=MyISAM DEFAULT CHARSET=utf8";
					
					$this->Query($mycmdceratetbl);
					
					
				}
				break;
			}
		}
		
		$ret = $this->Query($mycmd);
		
		return $ret;
	}
	
	
	function get_preview_file( $url_open, $size )
	{
		$crc32_url_open = crc32( $url_open );
		$size = (int) $size;
		$mycmd = "SELECT image_pre_$size FROM `" . $this->name_table_files . "` WHERE `crc32_url_open` = '$crc32_url_open' && `url_open` = '" . $this->my->real_escape_string($url_open) ."'";

		$ret = $this->Query($mycmd);
		if ( $ret )
		{
			return $ret[0]['image_pre_'.$size];
		}
		return 0;

	}
	//Вернём открытую ссылку на файл
	function get_open_url_file( $name_file )
	{
		$crc32_name_file = crc32( $name_file );
		$mycmd = "SELECT url_open,type,mime,UNIX_TIMESTAMP(ftime),UNIX_TIMESTAMP(ftime) as uftime,make_work,size_file,type_file,cnt_views,cnt_dwns,UNIX_TIMESTAMP(time_last_view) as time_last_view,uName FROM `" . $this->name_table_files . "` WHERE `crc32_name_file` = '$crc32_name_file' && `name_file` = '" . $this->my->real_escape_string($name_file) ."'";
		$ret = $this->Query($mycmd);
		if ( $ret )
		{
			$ftime = filemtime( $name_file );
			$fsize = filesize( $name_file );
			if ( (( $ftime) != $ret[0]['uftime'] || $fsize !=$ret[0]['size_file']) && $ftime)
			{
				$ar = array( 'crc32_name_file' => crc32( $name_file ), 'name_file' => $name_file,'ftime' => array("FROM_UNIXTIME(" . $ftime . ")"), 'image_pre_1' => '', 'size_file' => $fsize );
//				$ar = array( 'crc32_name_file' => crc32( $name_file ), 'name_file' => $name_file, 'image_pre_1' => '');
				$this->update_info_file( $ar );
				$ret[0]['uftime']-=10;
			}
			if($ret[0]['make_work'] > 0)
			{
				
				$ret[0]['uftime']-=10;
			}
			return $ret[0];//['url_open']
		}
		return 0;
		
		
	}
	
	function get_user_directory( $id_user_current, $start_free_space )
	{
		$this->m_user_id = $id_user_current;
		$mycmd = "SELECT diruser, ukey, mem_busy, mem_total FROM " . $this->m_name_table_users . " WHERE iduser = " . $id_user_current;
		$this->ShowError( 0 );
		$ret = $this->Query($mycmd);

		if(!$ret)
		{
			$this->ShowError( 1 );
			switch ( $this->last_errno )
			{
				case 1146:
				{//Создаём таблицу с диреториями для пользователей
		 
				 	$mycmdcreatetbl = "CREATE TABLE `" . $this->m_name_table_users . "` ( `iduser` INT(10) UNSIGNED NOT NULL,
									  `diruser` CHAR(24) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
									  `ukey` CHAR(24) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
									  `mem_busy` BIGINT(20) NOT NULL DEFAULT '0',
									  `mem_total` BIGINT(20) NOT NULL DEFAULT '16106127360',
									  `time_last_delete` DATETIME NOT NULL DEFAULT '2021-01-01 00:00:00',
									  `time_last_verify_files` DATETIME NOT NULL DEFAULT '2021-01-01 00:00:00',
									  PRIMARY KEY (`iduser`)) ";	
					$this->Query($mycmdcreatetbl);
				}
				break;
			}
			$ret = $this->Query($mycmd);
			if( !$ret && !$this->last_errno) 
			{
				//Новый пользователь 
				if ( !function_exists ("KDisk_SizeFileOrDir") ) { include __DIR__ . "/kdisk-function.php"; }
				$diruser = KDisk_generate_string(20);	
				$ukey = KDisk_generate_string(24);

				$myinsert = "INSERT `" . $this->m_name_table_users . "` (`iduser`, `diruser`, `ukey`, `mem_total`) VALUES ('" . $id_user_current . "', '".$diruser."', '".$ukey."', '".$start_free_space."')";    	
				$ret = $this->Query( $myinsert );
				if( ! $ret && ! $this->last_errno) 
				{//всё ok
					$ret = $this->Query( $mycmd );
				}else
				{
					exit;
				}
			}else
			{
				echo esc_html($mybase->last_errno);
			}
		}
		return $ret[0];
	}
	//
	function lock_table_files($value)
	{
		if ( $value )
		{
			$mycmd = "LOCK TABLES `" . $this->name_table_files . "` WRITE";    	
		}else
		{
			$mycmd = "UNLOCK TABLES ";    	
		}
		$ret = $this->Query( $mycmd );
	}
	//вернём первый файл без картинок
	function get_first_file_no_img()
	{
		$mycmd = "SELECT name_file FROM " . $this->name_table_files . " WHERE (LENGTH(`image_pre_1`) = 0 || `image_pre_1` IS NULL) && `type` = 1 LIMIT 1";
		$this->ShowError( 1 );
		$ret = $this->Query($mycmd);

		if( $ret )
		{
			$name_file = $ret[0]['name_file'];
			
			if( !is_file( $name_file ) )
			{//файл отсутствует
				
				$this->delete_file_from_base( $name_file );
				return 0;	
			}
		
			return $name_file;
		}
		
		
		return 0;
	}
	//Переименовать файл
	function rename_file( $file_info_update, $new_file_name, $direct_link )
	{
		
		$new_file_name = str_replace('/./', '/', $new_file_name);
		if (rename($file_info_update['name_file'], $new_file_name))
		{
			
			
			$where = " `crc32_name_file` = '".$file_info_update['crc32_name_file']."' && `name_file` = '" . $this->my->real_escape_string($file_info_update['name_file']) . "'";
			$mycmd = "UPDATE " . $this->name_table_files . " SET `name_file` = '" . $this->my->real_escape_string( $new_file_name ) . "', `crc32_name_file` = '".crc32($new_file_name)."', `direct_link` = '".$this->my->real_escape_string( $direct_link)."' WHERE " .$where;
			$ret = $this->Query($mycmd);
			if ( $this->last_errno == 1062 )
			{
				$mydel = "DELETE FROM `" . $this->name_table_files . "` WHERE `name_file` = '" . $this->my->real_escape_string( $new_file_name ) . "' && `crc32_name_file` = '".crc32($new_file_name)."'";
				$ret = $this->Query($mydel);
				$ret = $this->Query($mycmd);
			}
			if ( is_dir( $new_file_name ) )
			{
				$search = $file_info_update['name_file'] . "/";
				$change = $new_file_name . "/";
				$search_link = $file_info_update['direct_link'] . "/";
				$change_link = $direct_link . "/";
				//$change_link = str_replace('/./', '/', $change_link);
				
				$search = $this->my->real_escape_string( preg_replace('|([/]+)|s', '/', $search) );
				$change = $this->my->real_escape_string( preg_replace('|([/]+)|s', '/', $change) );
				$search_link = $this->my->real_escape_string( preg_replace('|([/]+)|s', '/', $search_link) );
				$change_link = $this->my->real_escape_string( preg_replace('|([/]+)|s', '/', $change_link) );
				
				$mycmd = "UPDATE IGNORE `" . $this->name_table_files . "` SET `crc32_name_file` = CRC32(REPLACE( `name_file`,'" . $search . "','" . $change . "')), `name_file` = REPLACE( `name_file`,'" . $search . "','" . $change . "'), `direct_link` = REPLACE( `direct_link`,'" . $search_link . "','" . $change_link . "') WHERE POSITION('" . $search . "' IN `name_file`) = 1";
				
				//$mycmd = "UPDATE IGNORE `" . $this->name_table_files . "` SET `crc32_name_file` = CRC32(REPLACE( `name_file`,'" . $search . "','" . $change . "')), `name_file` = REPLACE( `name_file`,'" . $search . "','" . $change . "'), `direct_link` = REPLACE( `direct_link`,'" . $search_link . "','" . $change_link . "') WHERE POSITION('" . $search . "' IN `name_file`) = 1";
				$ret = $this->Query($mycmd);
				
			}
		}
	}
	//Обновим инфо по файлу
	function update_info_file( $file_info_update )
	{
		$vlues = "";
		foreach ($file_info_update as $key => $value) {
			if(is_array($value))
			{
				$vlues .= "`$key` = " . $this->my->real_escape_string($value[0]) . ",";
			}else
			{
				$vlues .= "`$key` = '" . $this->my->real_escape_string($value) . "',";
			}
		}
		$vlues[ strlen($vlues) - 1 ] = " ";
		$where = " `crc32_name_file` = '".$file_info_update['crc32_name_file']."' && `name_file` = '" . $this->my->real_escape_string($file_info_update['name_file']) . "'";
		$mycmd = "UPDATE " . $this->name_table_files . " SET " . $vlues . " WHERE " .$where;
		$this->ShowError( 1 );
		$ret = $this->Query($mycmd);
		return $this->affected_rows;
		
	}
	//Обновляем параметры пользователя
	function update_param_user( $folder_user, $ar_param )
	{
		$vlues = "";
		foreach ($ar_param as $key => $value) {
			$vlues .= "`$key` = '" . $this->my->real_escape_string($value) . "',";
		}
		$vlues[ strlen($vlues) - 1 ] = " ";
		$where = " `diruser` = '" . $this->my->real_escape_string( $folder_user ) . "'";
		$mycmd = "UPDATE " . $this->m_name_table_users . " SET " . $vlues . " WHERE " .$where;
		$this->ShowError( 1 );
		$ret = $this->Query($mycmd);
	}
	//Удаление в корзину пользователя
	function remove_file( $old_name, $new_name)
	{
		
		$crc2_old_name = crc32( $old_name );
		$crc2_new_name = crc32( $new_name );
		
		$mycmd = "UPDATE " . $this->name_table_files . " SET `name_file` = '" . $this->my->real_escape_string( $new_name ) . "', `crc32_name_file` = '" . $crc2_new_name . "' WHERE `crc32_name_file` = '" . $crc2_old_name . "' && `name_file` = '" . $this->my->real_escape_string ( $old_name ) . "'";
		
		$ret = $this->Query($mycmd);
	}
	//Обновляем параметры пользователя.
	//Значения в параметрах без кавычек
	function update_param_user_no_quotes( $folder_user, $ar_param )
	{
		$vlues = "";
		foreach ($ar_param as $key => $value) {
			$vlues .= "`$key` = " . $this->my->real_escape_string($value) . ",";
		}
		$vlues[ strlen($vlues) - 1 ] = " ";
		$where = " `diruser` = '" . $folder_user . "'";
		$mycmd = "UPDATE " . $this->m_name_table_users . " SET " . $vlues . " WHERE " .$where;
		$this->ShowError( 1 );
		$ret = $this->Query($mycmd);
	}
	//Удаление записи о файле из базы
	function delete_file_from_base( $name_file )
	{
		$crc2_name_file = crc32( $name_file );
		$mycmd = "DELETE FROM " . $this->name_table_files . " WHERE  `crc32_name_file` = '" . $crc2_name_file . "' && `name_file` = '" . $this->my->real_escape_string ( $name_file ) . "'";
		$this->ShowError( 1 );
		$ret = $this->Query($mycmd);
		
	}
	//Поставить задачу на работу с файлом
	function set_start_make_work( $name_file, $make_work = 1 )
	{
		$ar = array( 'crc32_name_file' => crc32( $name_file ), 'name_file' => $name_file, 'make_work' => $make_work );
		$this->update_info_file( $ar );
		
	}
	
	//дай задание по id
	function get_task_from_id( $id )
	{
		$myinsert = "SELECT * FROM `" . $this->m_name_table_task . "` WHERE `id`='" . $id . "'";    	
		$ret = $this->Query( $myinsert );
		if($ret)
		{
			return $ret[0];
		}
		return 0;
	}
	//проверим есть ли задание
	function is_task( $name_file, $task ){
		$myinsert = "SELECT id FROM `" . $this->m_name_table_task . "` WHERE `name_file`='" . $this->my->real_escape_string ( $name_file ) . "' && `task`='" . $task . "'";    	
		$ret = $this->Query( $myinsert );
		if($ret)
		{
			return $ret[0]['id'];
		}else
		{
			if ( $this->last_errno == 1146 )
			{
				$this->create_tables();
			}
		}
		return 0;
	}
	//Дай все задания
	function get_all_task( $task, $status ){
		$myinsert = "SELECT `task`,`name_file`,`status`,`target`,UNIX_TIMESTAMP(`time`),`pid`,`id`,UNIX_TIMESTAMP(`time_start`) FROM `" . $this->m_name_table_task . "` WHERE `task`='" . $task . "' && status = " . $status ;    	
		$ret = $this->Query( $myinsert );
		if($ret)
		{
			return $ret;
		}
		return 0;
		
	}
	//Записать на сколько процентов выполнено задание
	function set_progress_task( $id_task, $progress, $remain  ){
		
		$mycmd = "UPDATE `" . $this->m_name_table_task . "` SET `time` = now(), `progress` = '".$progress."', `remain` = '".$remain."'  WHERE `id` = '".$id_task."'";
		$ret = $this->Query( $mycmd );
	}
	function lock_table_task($value)
	{
		if ( $value )
		{
			$mycmd = "LOCK TABLES `" . $this->m_name_table_task . "` WRITE";    	
		}else
		{
			$mycmd = "UNLOCK TABLES ";    	
		}
		$ret = $this->Query( $mycmd );
	}
	//Дай задание 
	function get_task( $task, $status ){
		
		if ( $status == 0 ) 
		{
//			global $KDisk_task;
			
//			return 0;
			$myinsert = "SELECT id_user,SUM(`status`) FROM `" . $this->m_name_table_task . "` WHERE `task`='" . $task . "' GROUP BY `id_user`"; 
			$ret_u = $this->Query( $myinsert );
			
			if ($ret_u)
			{
				for( $i=0; $i<count($ret_u); $i++)
				{
					if ( $ret_u[$i]['SUM(`status`)'] == 0 )
					{
						$myinsert = "SELECT * FROM `" . $this->m_name_table_task . "` WHERE `task`='" . $task . "' && `status` = 0 && `id_user`=" . $ret_u[$i]['id_user'] . " LIMIT 1";
						$ret = $this->Query( $myinsert );
						if($ret)
						{
							$ret = $ret[0];
						}
						return $ret;			
					}
				}
			}
			
		}else
		{
			$myinsert = "SELECT * FROM `" . $this->m_name_table_task . "` WHERE `task`='" . $task . "' && status = " . $status . " LIMIT 1";    	
			$ret = $this->Query( $myinsert );
		}
		
		
		
		if($ret)
		{
			$ret = $ret[0];
		}
		return $ret;
		
	}
	function create_tables()
	{
		$mycmdcreatetbl = 	"CREATE TABLE `" . $this->m_name_table_task . "` ( 
									`task` int(11) NOT NULL,
									`name_file` char(255) NOT NULL,
  									`status` int(11) NOT NULL DEFAULT '0',
  									`target` char(255) NOT NULL DEFAULT '',
  									`time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  									`pid` int(10) unsigned NOT NULL DEFAULT '0',
									`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
									`progress` float NOT NULL DEFAULT '0',
									`remain` int(10) unsigned NOT NULL DEFAULT '0',
									`time_start` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  									PRIMARY KEY (`task`,`name_file`),
									UNIQUE KEY `id` (`id`))";	
		$this->Query($mycmdcreatetbl);
		$mycmdcreatetbl = 	"CREATE TABLE `" . $this->m_name_table_stat_tmp . "` (
									`crcBr` int(10) unsigned NOT NULL DEFAULT '0',
  									`name_file` char(255) COLLATE utf8_bin NOT NULL DEFAULT '',
  									`utime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  									`ip` char(64) COLLATE utf8_bin DEFAULT '',
  									PRIMARY KEY (`crcBr`,`name_file`))";
		$this->Query($mycmdcreatetbl);
		
	}
	//отложенное задание
	function add_task( $name_file , $task){
		
		if ( is_array($name_file) )
		{
			
			$myinsert = "INSERT `" . $this->m_name_table_task . "` (`name_file`, `target`, `task`, `id_user`) VALUES ('" . $this->my->real_escape_string ( $name_file['file1'] ) . "','" . $this->my->real_escape_string ( $name_file['file2'] ) . "', '" . $task . "', '" . $this->m_user_id ."')";    	
		}else
		{
			$myinsert = "INSERT `" . $this->m_name_table_task . "` (`name_file`, `task`, `id_user`) VALUES ('" . $this->my->real_escape_string ( $name_file ) . "','" . $task . "','" . $this->m_user_id ."')";    	
		}
		//echo $myinsert;
		$ret = $this->Query( $myinsert );
		if(!$ret)
		{
//			echo "error: ". $this->last_error;
			$this->ShowError( 1 );
		
			switch ( $this->last_errno )
			{
				case 1062:
					return 1;
				break;
				case 1146:
				{//Создаём таблицу

/*CREATE TABLE `wp_krdisk_task` (
  `task` int(11) NOT NULL,
  `name_file` char(255) COLLATE utf8_bin NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  `target` char(255) COLLATE utf8_bin NOT NULL DEFAULT '',
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `progress` float NOT NULL DEFAULT '0',
  `remain` int(10) unsigned NOT NULL DEFAULT '0',
  `time_start` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`task`,`name_file`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3818 DEFAULT CHARSET=utf8 COLLATE=utf8_bin*/

					$mycmdcreatetbl = 	"CREATE TABLE `" . $this->m_name_table_task . "` ( 
									`task` int(11) NOT NULL,
									`name_file` char(255) NOT NULL,
  									`status` int(11) NOT NULL DEFAULT '0',
  									`target` char(255) NOT NULL DEFAULT '',
  									`time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  									`pid` int(10) unsigned NOT NULL DEFAULT '0',
									`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
									`progress` float NOT NULL DEFAULT '0',
									`remain` int(10) unsigned NOT NULL DEFAULT '0',
									`time_start` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  									PRIMARY KEY (`task`,`name_file`),
									UNIQUE KEY `id` (`id`))";	
					$this->Query($mycmdcreatetbl);
					$ret = $this->Query( $myinsert );
				}
			}
		}else
		{
			$id = $this->GetInsertId();
			
			$this->set_start_make_work($name_file, $id);
		}
		return 0;
	}
	//Удалить задание
	function delete_task( $name_file , $task){
		
		$mycmd_task = "DELETE FROM `" . $this->m_name_table_task . "` WHERE `task` = '" . $task . "' && `name_file` = '" . $this->my->real_escape_string($name_file) . "'";
		$rettask = $this->Query($mycmd_task);
		
	}
	//Вернём выполненное задание
	function get_task_finish( $task = 1)
	{ 
				
		$mycmd_task = "SELECT `name_file`, UNIX_TIMESTAMP(time), `target`,`status`,`id`,`pid`,UNIX_TIMESTAMP(time_start) FROM `" . $this->m_name_table_task . "` WHERE `task` = '" . $task . "' && (`status` = 1 || `status` = 2) order by  `status` DESC LIMIT 1";
		$this->ShowError( 1 );
		$rettask = $this->Query($mycmd_task);
		
		if ( $rettask )
		{
			$val = $rettask[0];
			$ret['val'] = $val;	
			if ( $val['status'] == 1 )
			{

				if( time() - $val['UNIX_TIMESTAMP(time_start)'] > 3600)
				{//что то долго

					$ret['err'] = 1;
					$ret['name_file'] = $val['name_file'];
					
					return $ret;

				}
			}elseif ( 2 == $val['status'] )
			{
				$ret['err'] = 2;
				$ret['name_file'] = $val['name_file'];
				$ret['target'] = $val['target'];
				return $ret;
			}
		}
		return 0;
	}
	function update_task($info_task)
	{
		$vlues = "";
		foreach ($info_task as $key => $value) {
			if(is_array($value))
			{
				$vlues .= "`$key` = " . $this->my->real_escape_string($value[0]) . ",";
			}else
			{
				$vlues .= "`$key` = '" . $this->my->real_escape_string($value) . "',";
			}
		}
		$vlues[ strlen($vlues) - 1 ] = " ";
		$where = " `id` = '".$info_task['id']."'";
		$mycmd = "UPDATE " . $this->m_name_table_task . " SET " . $vlues . " WHERE " .$where;

		$this->ShowError( 1 );
		$ret = $this->Query($mycmd);
	}
	/*
	*	Удаляем таблицы плагина из базы
	*/
	function delete_tables($dir_kdisk_files)
	{
		$this->ShowError( 1 );
		$mycmd = "DROP TABLE `" . $this->m_name_table_task . "`";
		$rettask = $this->Query($mycmd);
		$mycmd = "DROP TABLE `" . $this->name_table_files . "`";
		$rettask = $this->Query($mycmd);
		$mycmd = "DROP TABLE `" . $this->m_name_table_users . "`";
		
		$mycmd = "SELECT * FROM " . $this->m_name_table_users . " INTO DUMPFILE '" . $dir_kdisk_files . "/dump.sql'"; 
//		echo esc_html($mycmd);
		$rettask = $this->Query($mycmd);
	//	exec('mysqldump --user=' . $this->m_user . '--password=' . $this->m_pass . '--host=' . $this->m_adr . ' ' . $this->m_db . ' > /path/to/output/file.sql');

		//Сбросим дамп базы пользователей в директорий со всеми файлами
		
		/*$rettask = $this->Query($mycmd);*///Отключим на время, а то потеряется связь id изера и директория
		
		
	}
	//Создадим таблицы и добавим столбцы
	function modify_tables()
	{
		
		$this->create_tables();
		$mycmd = "ALTER TABLE " . $this->name_table_files . " ADD `Description` char(255) COLLATE utf8_bin NOT NULL DEFAULT ''";
		$this->Query($mycmd);
		$mycmd = "ALTER TABLE " . $this->name_table_files . " ADD `uName` char(128) COLLATE utf8_bin NOT NULL DEFAULT ''";
		$this->Query($mycmd);
		$mycmd = "ALTER TABLE " . $this->name_table_files . " ADD `cnt_views` int(10) unsigned NOT NULL DEFAULT '0'";
		$this->Query($mycmd);
		$mycmd = "ALTER TABLE " . $this->name_table_files . " ADD `time_last_view` timestamp NOT NULL DEFAULT '2021-01-01 00:00:00'";
		$this->Query($mycmd);
		$mycmd = "ALTER TABLE " . $this->name_table_files . " ADD `cnt_dwns` int(10) unsigned NOT NULL DEFAULT '0'";
		$this->Query($mycmd);
		$mycmd = "ALTER TABLE " . $this->m_name_table_task . " ADD `id_user` int(10) unsigned NOT NULL DEFAULT '0'";
		$this->Query($mycmd);

		

		
	}
	
	
	//Смотрим кто из юзеров удалял навсега.Для него проверяем наличие файлов из таблицы
}

?>