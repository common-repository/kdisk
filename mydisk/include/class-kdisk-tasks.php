<?

define ( __DIR__ , dirname( __FILE__ ) ); 

class KDisk_Tasks
{
	private $m_numthread, $f_log;
	function run( $argv )
	{

		$ar = array();
		for( $i = 1; $i < count( $argv ); $i++)
		{
			parse_str( $argv[ $i ], $arr ); 
			$ar = array_merge( $ar, $arr);
		}
		
		define ( __DIR__ , dirname( __FILE__ ) ); 

		$maxthread = 3;

		$this->m_numthread = 0;

		for($i = 0; $i < $maxthread; $i++ )
		{

			$filelog = $ar['tmpdir'] . "/tasks" . $i . ".log_";

		

			if ( is_file( $filelog ))
			{
				$test = $this->kdisk_test_process( $filelog );
				if ( !$test )
				{
					unlink( $filelog );
				}
		
				$this->m_numthread++;
			}else
			{ 
				break;
			}
		}
		

		if ( $this->m_numthread == $maxthread)return;

		$this->m_flog = fopen( $filelog, "wb+");
		fwrite($this->m_flog,getmypid()."\n");
		fwrite($this->m_flog,"start\n");

		$tim = time();
		include(__DIR__ . "/class-kdisk-basemy.php");
		include(__DIR__ . "/class-kdisk-base.php");

		
		$gKDisk_Base = new KDisk_Base();
		$gKDisk_Base->Connect( $ar['host'], $ar['user'], $ar['password'], $ar['name'] );

		$gKDisk_Base->set_name_table_files( $ar['table_user_files'] );
		$gKDisk_Base->set_name_table_task( $ar['tbl_tasks'] );
		$gKDisk_Base->set_name_table_stat_tmp( $ar['tbl_stat_tmp'] );
		$gKDisk_Base->m_user_id = 0;


		include( __DIR__ . "/class-kdisk-make-info-file.php" );

		$Make_Info = new KDisk_Make_Info_File();
		
		
		for( $j = 0; $j < 20 ; $j++ )	
		{
			for( $i = 0; $i < 5; $i++ )
			{
				$this->kv_verify_end_task($gKDisk_Base, $Make_Info, $ar['tmpdir']);
				$ret = $this->kv_make_preview($gKDisk_Base, $Make_Info, $ar['tmpdir']);
				$this->kv_verify_end_task($gKDisk_Base, $Make_Info, $ar['tmpdir']);
				if ( !$ret )break;
//				$i = 0;
				usleep(150);
			}
			if ( $this->m_numthread != $maxthread - 1 )
			{
//				$ret = $this->kv_make_mp4_h264($gKDisk_Base, $Make_Info, $ar['tmpdir']);
				$ret = $this->kd_ffmpeg_operation( KD_TASK_MAKE_MP4_H264, "-c:v libx264 -crf 20 -preset faster",$gKDisk_Base, $Make_Info, $ar['tmpdir'], "mp4" );
				if ($ret)
				{

					usleep(150);
					continue;	
				}
//				$ret = $this->kd_ffmpeg_operation( KD_TASK_TAKE_AUDIO, "-q:a 0 -map a", $gKDisk_Base, $Make_Info, $ar['tmpdir'], "mp3" );
//				$ret = $this->kd_ffmpeg_operation( KD_TASK_TAKE_AUDIO, "-vn -acodec copy", $gKDisk_Base, $Make_Info, $ar['tmpdir'], "ac3" );
				$ret = $this->kd_ffmpeg_operation( KD_TASK_TAKE_AUDIO, "-vn -ar 44100 -ac 2 -ab 192K -f mp3", $gKDisk_Base, $Make_Info, $ar['tmpdir'], "mp3" );
				
				if ($ret)
				{

					usleep(150);
					continue;	
				}
				
				$ret = $this->kd_combine_video_audio($gKDisk_Base, $Make_Info, $ar['tmpdir'], "mp4");
				
				if ($ret)
				{

					usleep(150);
					continue;	
				}else
				{
					break;
				}

				
			}else
			{
				break;
			}
			
		}
		$this->kv_progress_mp4_h264($gKDisk_Base, $Make_Info, $ar['tmpdir']);
		
		$this->kd_statictics( $gKDisk_Base, $ar['tmpdir'] );
		
		$this->kd_clear_lost_files( $gKDisk_Base );
		
		fwrite( $this->m_flog, (time()-$tim)."\nend" );
		fclose( $this->m_flog );
		rename( $filelog, substr($filelog,0,-1));
	}
	function kd_statictics( $gKDisk_Base, $tmpdir )
	{
		
		$file_make_stat = $tmpdir . "/stat.make";
		if ( is_file( $file_make_stat ) )return;
		$sfil = fopen($file_make_stat,"w+");
		if ( !$sfil )return;
		fwrite("utime: " . time() . "\n");
		fclose( $sfil );
			
		global $KDISK_NO_AUTO_DOWNLOAD;
		$KDISK_NO_AUTO_DOWNLOAD = 1;
		include(__DIR__ . "/class-kdisk-stat.php");
//		$this->kv_write_log("kd_statictics " .  $tmpdir ."/stat");
		$path = $tmpdir ."/stat";
		
		if( $handle = opendir( $path ) )
		{
			$view = 0;
			$sizedwn = 0;
			$files_stat = array();
			$cnt_files_stat = 0;
			while( $entry = readdir( $handle ) )
			{
		    	if (strpos($entry, "view_") !== false)
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
					$this->kv_write_log("VIEW: " . $values['file']);
					$retadd = $gKDisk_Base->view_file($values['file'], crc32($values['cookie']), $values['ip']);
					unlink( $fi_full_name );
				}else
				if (strpos($entry, "download_") !== false)
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
					$this->kv_write_log("DWNS: " . $values['file']);
					$retadd = $gKDisk_Base->download_file($values['file'], crc32($values['cookie']), $values['ip']);
					unlink( $fi_full_name );
				}
			}
		}
		unlink( $file_make_stat );
	}
	function kv_write_log($txt){

		fwrite($this->m_flog,$txt . "\n");
	}
	function kv_make_preview($gKDisk_Base, $Make_Info, $tmpdir)		
	{
		$gKDisk_Base->lock_table_files(true);
		$name_file = $gKDisk_Base->get_first_file_no_img();
		
		
		if ( $name_file )
		{
		
			$file_info_update = array(	'name_file' 		=> $name_file, 				//
									'crc32_name_file' 	=> crc32($name_file),		//
									'type'		=> 101, //
								);

			$gKDisk_Base->update_info_file( $file_info_update );
			$gKDisk_Base->lock_table_files(false);	
											
			$infofile = $Make_Info->get_preview_images( $name_file, $tmpdir,  $tmp_file );
											
		
		if ( $infofile['err'] == 5)
		{//Надо пускать ffmpeg
		
				
			//добавим в задания
			$ret = $gKDisk_Base->add_task( $name_file , 1);
fwrite( $this->m_flog,  "add task 1\n" );
			if ( $ret )
			{
				fwrite( $this->m_flog,  "err task " .$gKDisk_Base->last_error. "\n" );
				fwrite( $this->m_flog,  "task " .$name_file. "\n" );
			}else
			{
				fwrite( $this->m_flog,  "ok task\n" );
				if ( isset($infofile['ffmpeg_info']) )
				{
					$ret_info = $infofile['ffmpeg_info'];	
				}else
				{
					$ret_info = KDisk_FFmpeg::get_info_file( $name_file );
				}
				fwrite( $this->m_flog,  "ok task2\n" );	
				$sec = $ret_info['duration'];
				$timeOffset = (int) ( $sec / 2 );
				if ( $timeOffset > 30)
				{
					$timeOffset = 30;
				}
			
				$out_name_file = $tmpdir . "/" . crc32( $name_file ). time() . ".jpg";
				
				fwrite( $this->m_flog,  "ok task5\n" );	
			$tmp_file = $out_name_file . ".log";
			$mycmd = "UPDATE `" . $gKDisk_Base->m_name_table_task . "` SET `status` = 1, `target` = '" . $out_name_file  . "', `pid` = '" . getmypid() . "', `time` = NOW() WHERE `task` = 1 && `status` = 0 && name_file = '" . $gKDisk_Base->my->real_escape_string( $name_file ) . "' LIMIT 1";
			fwrite( $this->m_flog,  "ok task4\n" );	
			$ret = $gKDisk_Base->Query($mycmd);
			
			fwrite( $this->m_flog,  "ok task1\n" );
			$execmd = KDisk_FFmpeg::$m_ffmpeg . ' -i "' . $name_file . '" -an -r 1 -ss ' . $timeOffset . ' -vframes 1 -y -f mjpeg "' . $out_name_file . '" 2> ' . $tmp_file ;
			
			fwrite( $this->m_flog, "execmd: " . $execmd . "\n" );
			
			exec( $execmd, $ret_var );
			fwrite( $this->m_flog,  "ok " . $out_name_file ."\n" );
			
			if ( is_file( $out_name_file ) && filesize($out_name_file) == 0)
			{
				unlink( $out_name_file );
				fwrite( $this->m_flog,  "err make image" . "\n" );
			}
			
			$mycmd = "UPDATE `" . "wp_krdisk_task" . "` SET `status` = 2 WHERE `task` = 1 && `name_file` = '" . $gKDisk_Base->my->real_escape_string( $name_file ) . "' LIMIT 1";
			
			$ret = $gKDisk_Base->Query($mycmd);
			
			$json_info = array("ffmpeg" => array( "duration" => $ret_info['duration'], "bitrate" => $ret_info['bitrate']));

			$file_info_update = array(	'name_file' 		=> $name_file, 				//
										'crc32_name_file' 	=> crc32($name_file),		//
										'json_info'			=> json_encode($json_info), //
										'mime'				=> $infofile['mime'],
										'type'		=> 1,
										
								);
			$gKDisk_Base->update_info_file( $file_info_update );
			
			
			}
		}else
		{
			$file_info_update = array(	'name_file' 		=> $name_file, 				//
									'crc32_name_file' 	=> crc32($name_file),		//
									'image_pre_1'		=> $infofile['img_data_1'], //
									'image_pre_2'		=> $infofile['img_data_2'], //
									'type_file'			=> $infofile['type_file'],	//
									'cx'				=> $infofile['size'][0],
									'cy'				=> $infofile['size'][1],
									'mime'				=> $infofile['mime'],
									'type'		=> 1,
								);
			$gKDisk_Base->update_info_file( $file_info_update );
		}
		}else
		{
			$gKDisk_Base->lock_table_files(false);
			return 0;
		}
		$gKDisk_Base->lock_table_files(false);
		return 1;
	}



function kv_verify_end_task($gKDisk_Base, $Make_Info, $tmpdir)
{
	
	///Проверим выполнены ли какие-нибуть задачи по созданию превьюв
	$ret = $gKDisk_Base->get_task_finish( 1 );
						
		if ( $ret['err'] == 1 )
		{///
			fwrite( $this->m_flog,  "get_task_finish 1" . "\n" );
			$name_file = $ret['name_file'];
			$name_file_out = __DIR__ . "/imgs/kvdoc.png";
			$infofile = $Make_Info->get_preview_images( $name_file_out ,  $tmpdir , $tmp_file );
			$infofile['type_file'] = ''; 
			$gKDisk_Base->delete_task( $name_file , 1);
			$file_info_update = array( 'name_file' 	=> $name_file, 				//
						'crc32_name_file' 	=> crc32($name_file),		//
						'image_pre_1'		=> $infofile['img_data_1'], //
						'image_pre_2'		=> $infofile['img_data_2'], //
						'type_file'			=> $infofile['type_file'],	//
						'cx'				=> $infofile['size'][0],
						'cy'				=> $infofile['size'][1],
//						'mime'				=> $infofile['mime'],
									
						);
			
//			$this->kv_write_log('mime1: ' . $infofile['mime']);
			$gKDisk_Base->update_info_file( $file_info_update );
			@unlink( $name_file_out );
			$name_file=0;
//			return;
		}elseif ( $ret['err'] == 2 )
		{///
			fwrite( $this->m_flog,  "get_task_finish 2" . "\n" );
			$name_file = $ret['name_file'];
			$name_file_out = $ret['target'];
			if ( is_file( $name_file_out ) && filesize( $name_file_out ))
			{
				$infofile = $Make_Info->get_preview_images( $name_file_out ,  $tmpdir , $tmp_file );
				$infofile['type_file'] = 'video'; 
				
			}else
			{
				
				$name_file_out = __DIR__ . "/../imgs/kvnotes.png";
				$infofile = $Make_Info->get_preview_images( $name_file_out ,  $tmpdir , $tmp_file );
				$infofile['type_file'] = 'audio'; 
			}
			
		
//			@unlink( $name_file_out );
			
			$gKDisk_Base->delete_task( $name_file , 1);
			
			$file_info_update = array( 'name_file' 	=> $name_file, 				//
						'crc32_name_file' 	=> crc32($name_file),		//
						'image_pre_1'		=> $infofile['img_data_1'], //
						'image_pre_2'		=> $infofile['img_data_2'], //
						'type_file'			=> $infofile['type_file'],	//
						'cx'				=> $infofile['size'][0],
						'cy'				=> $infofile['size'][1],
//						'mime'				=> $infofile['mime'],
					
						);
		
		//	$this->kv_write_log('mime2: ' . $infofile['mime']);
			$gKDisk_Base->update_info_file( $file_info_update );
	
			$name_file=0;
			

		}
	//Проверим задани по конвертации в mp4 	
	
	$ret = $gKDisk_Base->get_task_finish( KD_TASK_MAKE_MP4_H264 );	
	if( $ret && $ret['err'] == 1)
	{//Проверим работает ли процесс
		if(posix_kill($ret['val']['pid'],0))
		{
//			$this->kv_write_log('pid: ' . $ret['val']['pid']);
		}else
		{

			$gKDisk_Base->update_task(array('id' => $ret['val']['id'], 'status' => 0));
		}
	}
	
	$ret = $gKDisk_Base->get_task_finish( KD_TASK_TAKE_AUDIO );	
	if( $ret && $ret['err'] == 1)
	{//Проверим работает ли процесс
		if(posix_kill($ret['val']['pid'],0))
		{
		}else
		{
			$gKDisk_Base->update_task(array('id' => $ret['val']['id'], 'status' => 0));
		}
	}
	
	$ret = $gKDisk_Base->get_task_finish( KD_TASK_COMBIME_VIDEO_AUDIO );	
	if( $ret && $ret['err'] == 1)
	{//Проверим работает ли процесс
		if(posix_kill($ret['val']['pid'],0))
		{
		}else
		{
			$gKDisk_Base->update_task(array('id' => $ret['val']['id'], 'status' => 0));
		}
	}
}
	function kd_clear_lost_files( $gKDisk_Base )
	{
		
		$gKDisk_Base->lock_table_task( true );
		$file_task = $gKDisk_Base->get_task( KD_TASK_CLEAR_LOST_FILE, 0 );
		
		if ( $file_task )
		{
			
			$name_file = $file_task['name_file'];

			switch( $name_file )
			{
				case 'clear_lost_files':
				{
					$mycmd = "UPDATE `" . "wp_krdisk_task" . "` SET `status` = 1, `pid` = '" . getmypid() . "', `time` = NOW(), `time_start` = NOW() WHERE `task` = " . KD_TASK_CLEAR_LOST_FILE . " && `status` = 0 && name_file = '" . $gKDisk_Base->my->real_escape_string( $name_file ) . "' LIMIT 1";
					$ret = $gKDisk_Base->Query($mycmd);
					$gKDisk_Base->lock_table_task( false );
					$this->kv_write_log("clear_lost");
					$gKDisk_Base->clear_lost_files();
					$gKDisk_Base->delete_task( $name_file , KD_TASK_CLEAR_LOST_FILE);
				}
				break;
			}
		}
		$gKDisk_Base->lock_table_task( false );
	}
	//Объединим видео и аудио
	function kd_combine_video_audio($gKDisk_Base, $Make_Info, $tmpdir, $expand_out_file)		
	{
		
		$gKDisk_Base->lock_table_task( true );
		$file_task = $gKDisk_Base->get_task( KD_TASK_COMBIME_VIDEO_AUDIO, 0 );
		
		if ( $file_task )
		{
			$name_file = $file_task['name_file'];
			$name_file2 = $file_task['target'];
			if ( !class_exists("KDisk_FFmpeg") )
			{
				include( __DIR__ . "/class-kdisk-ffmpeg.php" );
			}
			
			$ret_info = KDisk_FFmpeg::get_info_file( $name_file );
			$ret_info2 = KDisk_FFmpeg::get_info_file( $name_file2 );
			$cngtask = 0;
			$name_file_start = $name_file;
			if($ret_info['video'] && $ret_info2['audio'])
			{
				
			}else
			if($ret_info['audio'] && $ret_info2['video'])
			{
				$t = $ret_info;
				$ret_info = $ret_info2;
				$ret_info2 = $t;
				$t = $name_file;
				$name_file = $name_file2;
				$name_file2 = $t;
			}
			
			
			$this->kv_write_log("get_info_file " . $ret_info );	
			$sec = $ret_info['duration'];
			$this->kv_write_log("duration " . $sec );	
			
			$utime = time();
			$out_name_file = $tmpdir . "/" . crc32( $name_file ) . $utime . "-" . $this->m_numthread . "." . $expand_out_file;
			$tmp_file_log_ffmpeg = $tmpdir . "/" . crc32( $name_file ) . $utime . "-" . $this->m_numthread . "-ffmpeg.log";
			
			$this->kv_write_log("make combine: " . $name_file_start );
			
			
			$mycmd = "UPDATE `" . "wp_krdisk_task" . "` SET `name_file` = '" . $gKDisk_Base->my->real_escape_string($name_file) . "', `status` = 1, `target` = '" . $out_name_file  . "', `pid` = '" . getmypid() . "', `time` = NOW(), `time_start` = NOW() WHERE `task` = " . KD_TASK_COMBIME_VIDEO_AUDIO . " && `status` = 0 && name_file = '" . $gKDisk_Base->my->real_escape_string( $name_file_start ) . "' LIMIT 1";
			
			$this->kv_write_log("mycmd " . $mycmd );
			$ret = $gKDisk_Base->Query($mycmd);
			$gKDisk_Base->lock_table_task( false );
			$execmd = KDisk_FFmpeg::$m_ffmpeg . ' -i "' . $name_file . '"' . ' -i "' . $name_file2 . '"  -c:v copy -c:a  mp3 -strict experimental -map 0:v:0 -map 1:a:0 "' . $out_name_file . '" 2> ' . $tmp_file_log_ffmpeg;

			$this->kv_write_log("file to combine: " . $execmd  );
			exec( $execmd, $ret_var );
			
			if ( is_file( $out_name_file ) && filesize($out_name_file) == 0)
			{
				unlink( $out_name_file );
			}else
			{
				$name_file_tmp = $out_name_file;
				$name_file = $name_file;
				$path_info = pathinfo( $name_file );
				$name_file_out = $path_info['dirname'] . "/" . $path_info['filename'] . "." . $expand_out_file;
				if ( is_file( $name_file_out ) )
				{
					for($i = 1; $i < 50; $i++)
					{
						$name_file_out = $path_info['dirname'] . "/" . $path_info['filename'] . "(" . $i . ")". "." . $expand_out_file;
						if ( ! is_file( $name_file_out ) )break;
					}
				}
		
				if(rename($name_file_tmp, $name_file_out))
				{
					$gKDisk_Base->delete_task( $name_file , KD_TASK_COMBIME_VIDEO_AUDIO);
				}else
				{
					$fer = fopen($tmp_file_log_ffmpeg,"a+");
					
					fwrite($fer,"\n php Error rename file: " . print_r(error_get_last(),true));
					fwrite($fer,"\n" . $name_file_tmp);
					fwrite($fer,"\n" . $name_file_out);
					fclose($fer);
					$gKDisk_Base->delete_task( $name_file , KD_TASK_COMBIME_VIDEO_AUDIO);
				}
				
			}
			
			$mycmd = "UPDATE `" . "wp_krdisk_task" . "` SET `status` = 2 WHERE `task` = " . KD_TASK_COMBIME_VIDEO_AUDIO . " && `name_file` = '" . $gKDisk_Base->my->real_escape_string( $name_file ) . "' LIMIT 1";
			
			$ret = $gKDisk_Base->Query($mycmd);
			
			
			
		}else
		{
			$gKDisk_Base->lock_table_task( false );	
			return 0;
		}
		$gKDisk_Base->lock_table_task( false );
		return 1;
	}
	//Выполняем операцию с ffmpeg
	function kd_ffmpeg_operation($operation, $prm_ffmpeg, $gKDisk_Base, $Make_Info, $tmpdir, $expand_out_file)
	{
		$gKDisk_Base->lock_table_task( true );
		$file_task = $gKDisk_Base->get_task( $operation, 0 );
		
		if ( $file_task )
		{
			$name_file = $file_task['name_file'];
			$file_src = $name_file;
			$utime = time();
			//Copy file in temporary
			$name_file_m = 0;//$tmpdir . "/" . crc32( $name_file ) . $utime . "_" .  basename( $name_file );
/*			if(copy( $name_file, $name_file_m ))
			{
				
				$file_src = $name_file_m;
			}else
			{
				$name_file_m = 0;
			}*/
			//
			if ( !class_exists("KDisk_FFmpeg") )
			{
				include( __DIR__ . "/class-kdisk-ffmpeg.php" );
			}
			
			$ret_info = KDisk_FFmpeg::get_info_file( $name_file );
			$this->kv_write_log("get_info_file " . $ret_info );	
			$sec = $ret_info['duration'];
			$this->kv_write_log("duration " . $sec );	
			

			$out_name_file = $tmpdir . "/" . crc32( $name_file ) . $utime . "-" . $this->m_numthread . "." . $expand_out_file;
			
			$tmp_file_log_ffmpeg = $tmpdir . "/" . crc32( $name_file ) . $utime . "-" . $this->m_numthread . "-ffmpeg.log";
			
			$this->kv_write_log("make mp3: " . $out_name_file );
			
			
			$mycmd = "UPDATE `" . "wp_krdisk_task" . "` SET `status` = 1, `target` = '" . $out_name_file  . "', `pid` = '" . getmypid() . "', `time` = NOW(), `time_start` = NOW() WHERE `task` = " . $operation . " && `status` = 0 && name_file = '" . $gKDisk_Base->my->real_escape_string( $name_file ) . "' LIMIT 1";
			$ret = $gKDisk_Base->Query($mycmd);
			$gKDisk_Base->lock_table_task( false );
			$execmd = KDisk_FFmpeg::$m_ffmpeg . ' -i "' . $file_src . '" ' . $prm_ffmpeg . ' "' . $out_name_file . '" 2> ' . $tmp_file_log_ffmpeg;
			
			if( $name_file_m != 0 )
			{
				unlink( $name_file_m );
			}
			$this->kv_write_log("file to mp4: " . $execmd  );
			exec( $execmd, $ret_var );
			
			if ( is_file( $out_name_file ) && filesize($out_name_file) == 0)
			{
				unlink( $out_name_file );
			}else
			{
				$name_file_tmp = $out_name_file;
				$name_file = $name_file;
				$path_info = pathinfo( $name_file );
				$name_file_out = $path_info['dirname'] . "/" . $path_info['filename'] . "." . $expand_out_file;
				if ( is_file( $name_file_out ) )
				{
					for($i = 1; $i < 50; $i++)
					{
						$name_file_out = $path_info['dirname'] . "/" . $path_info['filename'] . "(" . $i . ")". "." . $expand_out_file;
						if ( ! is_file( $name_file_out ) )break;
					}
				}
		
				if(rename($name_file_tmp, $name_file_out))
				{
					$gKDisk_Base->delete_task( $name_file , $operation);
				}else
				{
					$fer = fopen($tmp_file_log_ffmpeg,"a+");
					
					fwrite($fer,"\n php Error rename file: " . print_r(error_get_last(),true));
					fwrite($fer,"\n" . $name_file_tmp);
					fwrite($fer,"\n" . $name_file_out);
					fclose($fer);
					$gKDisk_Base->delete_task( $name_file , $operation);
				}
				
				
			}
			
			$mycmd = "UPDATE `" . "wp_krdisk_task" . "` SET `status` = 2 WHERE `task` = " . $operation . " && `name_file` = '" . $gKDisk_Base->my->real_escape_string( $name_file ) . "' LIMIT 1";
			
			$ret = $gKDisk_Base->Query($mycmd);
			
			
			
		}else
		{
			$gKDisk_Base->lock_table_task( false );	
			return 0;
		}
		$gKDisk_Base->lock_table_task( false );
		return 1;
	}
	///Выделяем аудио в mp3
/*	function kd_take_mp3($gKDisk_Base, $Make_Info, $tmpdir, $expand_out_file)		
	{
		
		$gKDisk_Base->lock_table_task( true );
		$file_task = $gKDisk_Base->get_task( KD_TASK_TAKE_AUDIO, 0 );
		
		if ( $file_task )
		{
			$name_file = $file_task['name_file'];
			
//			$this->kv_write_log("tmp_file: " . $tmp_file );	
			if ( !class_exists("KDisk_FFmpeg") )
			{
				include( __DIR__ . "/class-kdisk-ffmpeg.php" );
			}
			
			$ret_info = KDisk_FFmpeg::get_info_file( $name_file );
			$this->kv_write_log("get_info_file " . $ret_info );	
			$sec = $ret_info['duration'];
			$this->kv_write_log("duration " . $sec );	
			
			$utime = time();
			$out_name_file = $tmpdir . "/" . crc32( $name_file ) . $utime . "-" . $this->m_numthread . "." . $expand_out_file;
			$tmp_file_log_ffmpeg = $tmpdir . "/" . crc32( $name_file ) . $utime . "-" . $this->m_numthread . "-" . $expand_out_file . ".log";
			
			$this->kv_write_log("make mp3: " . $out_name_file );
			
			
			$mycmd = "UPDATE `" . "wp_krdisk_task" . "` SET `status` = 1, `target` = '" . $out_name_file  . "', `pid` = '" . getmypid() . "', `time` = NOW(), `time_start` = NOW() WHERE `task` = " . KD_TASK_TAKE_AUDIO . " && `status` = 0 && name_file = '" . $gKDisk_Base->my->real_escape_string( $name_file ) . "' LIMIT 1";
			$ret = $gKDisk_Base->Query($mycmd);
			$gKDisk_Base->lock_table_task( false );
			$execmd = KDisk_FFmpeg::$m_ffmpeg . ' -i "' . $name_file . '" -q:a 0 -map a "' . $out_name_file . '" 2> ' . $tmp_file_log_ffmpeg;
			

			$this->kv_write_log("file to mp4: " . $execmd  );
			exec( $execmd, $ret_var );
			
			if ( is_file( $out_name_file ) && filesize($out_name_file) == 0)
			{
				unlink( $out_name_file );
			}else
			{
				$name_file_tmp = $out_name_file;
				$name_file = $name_file;
				$path_info = pathinfo( $name_file );
				$name_file_out = $path_info['dirname'] . "/" . $path_info['filename'] . "." . $expand_out_file;
				if ( is_file( $name_file_out ) )
				{
					for($i = 1; $i < 50; $i++)
					{
						$name_file_out = $path_info['dirname'] . "/" . $path_info['filename'] . "(" . $i . ")". "." . $expand_out_file;
						if ( ! is_file( $name_file_out ) )break;
					}
				}
		
				if(rename($name_file_tmp, $name_file_out))
				{
					$gKDisk_Base->delete_task( $name_file , KD_TASK_TAKE_AUDIO);
				}else
				{
					$fer = fopen($tmp_file_log_ffmpeg,"a+");
					
					fwrite($fer,"\n php Error rename file: " . print_r(error_get_last(),true));
					fwrite($fer,"\n" . $name_file_tmp);
					fwrite($fer,"\n" . $name_file_out);
					fclose($fer);
					$gKDisk_Base->delete_task( $name_file , KD_TASK_TAKE_AUDIO);
				}
				
				
			}
			
			$mycmd = "UPDATE `" . "wp_krdisk_task" . "` SET `status` = 2 WHERE `task` = " . KD_TASK_TAKE_AUDIO . " && `name_file` = '" . $gKDisk_Base->my->real_escape_string( $name_file ) . "' LIMIT 1";
			
			$ret = $gKDisk_Base->Query($mycmd);
			
			
			
		}else
		{
			$gKDisk_Base->lock_table_task( false );	
			return 0;
		}
		$gKDisk_Base->lock_table_task( false );
	return 1;
}
	///Конвертируем в mp4
	function kv_make_mp4_h264($gKDisk_Base, $Make_Info, $tmpdir)		
	{
		
		$gKDisk_Base->lock_table_task( true );
		$file_task = $gKDisk_Base->get_task(2,0);
		
		if ( $file_task )
		{
			$name_file = $file_task['name_file'];
			
			$this->kv_write_log("tmp_file: " . $tmp_file );	
			if ( !class_exists("KDisk_FFmpeg") )
			{
				include( __DIR__ . "/class-kdisk-ffmpeg.php" );
			}
			
			$ret_info = KDisk_FFmpeg::get_info_file( $name_file );
			$this->kv_write_log("get_info_file " . $ret_info );	
			$sec = $ret_info['duration'];
			$this->kv_write_log("duration " . $sec );	
			
			$utime = time();
			$out_name_file = $tmpdir . "/" . crc32( $name_file ) . $utime . "-" . $this->m_numthread . ".mp4";
			$tmp_file_log_ffmpeg = $tmpdir . "/" . crc32( $name_file ) . $utime . "-" . $this->m_numthread . "-h264.log";
			
			$this->kv_write_log("file to mp4: " . $out_name_file );
			
			
			$mycmd = "UPDATE `" . "wp_krdisk_task" . "` SET `status` = 1, `target` = '" . $out_name_file  . "', `pid` = '" . getmypid() . "', `time` = NOW(), `time_start` = NOW() WHERE `task` = 2 && `status` = 0 && name_file = '" . $gKDisk_Base->my->real_escape_string( $name_file ) . "' LIMIT 1";
			$ret = $gKDisk_Base->Query($mycmd);
			$gKDisk_Base->lock_table_task( false );
			$execmd = KDisk_FFmpeg::$m_ffmpeg . ' -i "' . $name_file . '" -c:v libx264 -crf 20 -preset faster "' . $out_name_file . '" 2> ' . $tmp_file_log_ffmpeg;//libx264

			$this->kv_write_log("file to mp4: " . $execmd  );
			exec( $execmd, $ret_var );
			
			if ( is_file( $out_name_file ) && filesize($out_name_file) == 0)
			{
				unlink( $out_name_file );
			}else
			{
				$name_file_tmp = $out_name_file;
				$name_file = $name_file;
				$path_info = pathinfo( $name_file );
				$name_file_out = $path_info['dirname'] . "/" . $path_info['filename'] . ".mp4";
				if ( is_file( $name_file_out ) )
				{
					for($i = 1; $i < 50; $i++)
					{
						$name_file_out = $path_info['dirname'] . "/" . $path_info['filename'] . "(" . $i . ")". ".mp4";
						if ( ! is_file( $name_file_out ) )break;
					}
				}
		
				//$mycmd = "UPDATE `" . "wp_krdisk_task" . "` SET `status` = 3 WHERE `task` = 2 && `status` = 2 && name_file = '" . $ret['name_file'] . "' LIMIT 1";
				//$gKDisk_Base->Query($mycmd);
		
				if(rename($name_file_tmp, $name_file_out))
				{
					$gKDisk_Base->delete_task( $name_file , 2);
				}else
				{
					$fer = fopen($tmp_file_log_ffmpeg,"a+");
					
					fwrite($fer,"\n php Error rename file: " . print_r(error_get_last(),true));
					fwrite($fer,"\n" . $name_file_tmp);
					fwrite($fer,"\n" . $name_file_out);
					fclose($fer);
					$gKDisk_Base->delete_task( $name_file , 2);
				}
//				$gKDisk_Base->delete_task( $name_file , 2);
				
				
			}
			
			$mycmd = "UPDATE `" . "wp_krdisk_task" . "` SET `status` = 2 WHERE `task` = 2 && `name_file` = '" . $gKDisk_Base->my->real_escape_string( $name_file ) . "' LIMIT 1";
			
			$ret = $gKDisk_Base->Query($mycmd);
			
			
			
		}else
		{
			$gKDisk_Base->lock_table_task( false );	
			return 0;
		}
		$gKDisk_Base->lock_table_task( false );
	return 1;
}*/
//Проверим прогресс конвертации
function kv_progress_mp4_h264($gKDisk_Base, $Make_Info, $tmpdir)		
{
	$file_task = $gKDisk_Base->get_all_task( KD_TASK_MAKE_MP4_H264, 1 );

	if ( $file_task )
	{
		for($i = 0; $i < count($file_task); $i++ )
		{
			$t = $file_task[$i];
			
			$path_info = pathinfo( $t['target'] );
			$filelog = $path_info['dirname'] . "/" . $path_info['filename'] . "-ffmpeg.log";
			$fi=@fopen($filelog,"r");
			$log=fread($fi,filesize($filelog));
			fclose($fi);
			
			$pos=strrpos($log,"time=");
			
			if($pos)
			{
				$pose=strpos($log,"\n",$pos);
				if(!$pose)
					$pose=strpos($log,"\r",$pos);
				$str=substr($log,$pos,$pose-$pos);
				
				$cur_dur=substr($str,5,12);
				
				$ar = explode(":",$cur_dur);
				
				$sec = 0;
				$mn = 1;
				for($k = count( $ar ) - 1; $k >=0 ; $k--)
				{
					$sec += $ar[$k] * $mn;
					$mn *= 60;
				}
				
				$this->kv_write_log($cur_dur . " " . $sec);
				
				$pos=strpos($log,"Duration:");
				if ( $pos )
				{
					$dur=substr($log,$pos + 10 ,12);
					$ar = explode(":",$dur);
					$all_sec = 0;
					$mn = 1;
					for($k = count( $ar ) - 1; $k >=0 ; $k--)
					{
						$all_sec += $ar[$k] * $mn;
						$mn *= 60;
					}
					
					$this->kv_write_log($dur . " " . $all_sec);
					
					$procent = 100 / $all_sec  * $sec ;
					
					
					
					$this->kv_write_log( round($procent, 1)."%" );
					
					$this->kv_write_log( "bt ". $t['UNIX_TIMESTAMP(`time`)'] );
					
					$unix_date = time() - $t['UNIX_TIMESTAMP(`time_start`)'];
					
					$remain = round($unix_date / $procent * ( 100 - $procent ));
					
					$gKDisk_Base->set_progress_task( $t['id'], $procent, $remain);
					$this->kv_write_log( $unix_date . ":" . round($unix_date / $procent * ( 100 - $procent ))  );
					$this->kv_write_log( date_default_timezone_get() );
				}
				
				$gKDisk_Base->set_start_make_work($t['name_file'],$procent);
				
			}
			
		}
	}
	
	$file_task = $gKDisk_Base->get_all_task( KD_TASK_TAKE_AUDIO, 1 );
	if ( $file_task )
	{
		for($i = 0; $i < count($file_task); $i++ )
		{
			$t = $file_task[$i];
			
			$path_info = pathinfo( $t['target'] );
			$filelog = $path_info['dirname'] . "/" . $path_info['filename'] . "-ffmpeg.log";
			$fi=@fopen($filelog,"r");
			$log=fread($fi,filesize($filelog));
			fclose($fi);
			
			$pos=strrpos($log,"time=");
			
			if($pos)
			{
				$pose=strpos($log,"\n",$pos);
				if(!$pose)
					$pose=strpos($log,"\r",$pos);
				$str=substr($log,$pos,$pose-$pos);
				
				$cur_dur=substr($str,5,12);
				
				$ar = explode(":",$cur_dur);
				
				$sec = 0;
				$mn = 1;
				for($k = count( $ar ) - 1; $k >=0 ; $k--)
				{
					$sec += $ar[$k] * $mn;
					$mn *= 60;
				}
				
				$this->kv_write_log($cur_dur . " " . $sec);
				
				$pos=strpos($log,"Duration:");
				if ( $pos )
				{
					$dur=substr($log,$pos + 10 ,12);
					$ar = explode(":",$dur);
					$all_sec = 0;
					$mn = 1;
					for($k = count( $ar ) - 1; $k >=0 ; $k--)
					{
						$all_sec += $ar[$k] * $mn;
						$mn *= 60;
					}
					
					$this->kv_write_log($dur . " " . $all_sec);
					
					$procent = 100 / $all_sec  * $sec ;
					
					
					
					$this->kv_write_log( round($procent, 1)."%" );
					
					$this->kv_write_log( "bt ". $t['UNIX_TIMESTAMP(`time`)'] );
					
					$unix_date = time() - $t['UNIX_TIMESTAMP(`time_start`)'];
					
					$remain = round($unix_date / $procent * ( 100 - $procent ));
					
					$gKDisk_Base->set_progress_task( $t['id'], $procent, $remain);
					$this->kv_write_log( $unix_date . ":" . round($unix_date / $procent * ( 100 - $procent ))  );
					$this->kv_write_log( date_default_timezone_get() );
				}
				
				$gKDisk_Base->set_start_make_work($t['name_file'],$procent);
				
			}
			
		}
	}
	
	$file_task = $gKDisk_Base->get_all_task( KD_TASK_COMBIME_VIDEO_AUDIO, 1 );
	if ( $file_task )
	{
		for($i = 0; $i < count($file_task); $i++ )
		{
			$t = $file_task[$i];
			
			$path_info = pathinfo( $t['target'] );
			$filelog = $path_info['dirname'] . "/" . $path_info['filename'] . "-ffmpeg.log";
			$fi=@fopen($filelog,"r");
			$log=fread($fi,filesize($filelog));
			fclose($fi);
			
			$pos=strrpos($log,"time=");
			
			if($pos)
			{
				$pose=strpos($log,"\n",$pos);
				if(!$pose)
					$pose=strpos($log,"\r",$pos);
				$str=substr($log,$pos,$pose-$pos);
				
				$cur_dur=substr($str,5,12);
				
				$ar = explode(":",$cur_dur);
				
				$sec = 0;
				$mn = 1;
				for($k = count( $ar ) - 1; $k >=0 ; $k--)
				{
					$sec += $ar[$k] * $mn;
					$mn *= 60;
				}
				
				$this->kv_write_log($cur_dur . " " . $sec);
				
				$pos=strpos($log,"Duration:");
				if ( $pos )
				{
					$dur=substr($log,$pos + 10 ,12);
					$ar = explode(":",$dur);
					$all_sec = 0;
					$mn = 1;
					for($k = count( $ar ) - 1; $k >=0 ; $k--)
					{
						$all_sec += $ar[$k] * $mn;
						$mn *= 60;
					}
					
					$this->kv_write_log($dur . " " . $all_sec);
					
					$procent = 100 / $all_sec  * $sec ;
					
					
					
					$this->kv_write_log( round($procent, 1)."%" );
					
					$this->kv_write_log( "bt ". $t['UNIX_TIMESTAMP(`time`)'] );
					
					$unix_date = time() - $t['UNIX_TIMESTAMP(`time_start`)'];
					
					$remain = round($unix_date / $procent * ( 100 - $procent ));
					
					$gKDisk_Base->set_progress_task( $t['id'], $procent, $remain);
					$this->kv_write_log( $unix_date . ":" . round($unix_date / $procent * ( 100 - $procent ))  );
					$this->kv_write_log( date_default_timezone_get() );
				}
				
				$gKDisk_Base->set_start_make_work($t['name_file'],$procent);
				
			}
			
		}
	}
	
	
}
	
	function kdisk_test_process( $filepid )		
	{
		$ret = 0;
		$fi = fopen($filepid,"r");
		if( $fi )
		{
			$pid = (int) fgets( $fi );
			if(posix_kill( $pid, 0))
			{
				$ret = 1;
			}
			fclose( $fi );
		}
	
		return $ret;
	}

}
global $KDisk_task;
$KDisk_task = new KDisk_Tasks();
$KDisk_task->run( $argv );

