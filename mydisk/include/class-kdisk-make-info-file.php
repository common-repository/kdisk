<?
class KDisk_Make_Info_File
{

	private function make_small_image( $img_in, $size_in, $size_out )
	{
		$siz_x = $size_out[0];
		$siz_y = $size_out[1] ;
		$size = $size_in;		 
		$pos_x = 0;
		$pos_y = 0;
					
		$img_out = imagecreatetruecolor($siz_x, $siz_y);
		$transparent = imagecolorallocatealpha($img_out, 0, 0, 0, 127);
		imagefill($img_out, 0, 0, $transparent);
		imagesavealpha($img_out, true);
					
		if( $size[0] > $size[1] )//Если ширина больше высоты
		{
			$pos_x = ( $size[0] - $size[1] ) / 2;
			$size[0] = $size[1];
		}elseif ( $size[0] < $size[1] )
		{
			$pos_y =  ( $size[1] - $size[0] ) /2;
			$size[1] = $size[0];
		}
		imagecopyresampled( $img_out, $img_in, 0, 0, $pos_x, $pos_y, $siz_x , $siz_y , $size[0] , $size[1]);
		ob_start();
		imagepng ($img_out);
		$ret = ob_get_contents();
		ob_end_clean();
		return $ret;
	}
	//Зоздаём картинки иконки для файла
	function get_preview_images( $name_file, $temporary_dir, $tmp_file = "" )
	{
		$ret = array( 'err' => 1, 'img_data' => 0, 'size' => array(0,0), 'type_file' => '', 'mime' => '' );

		$size = 0;

		if ( !class_exists("KDisk_FFmpeg") )
		{
			include( __DIR__ . "/class-kdisk-ffmpeg.php" );
		}

		if ( function_exists('finfo_open') )
		{
			$finfo = finfo_open( FILEINFO_MIME );
			if ( $finfo )
			{
				
				$ret['mime'] = finfo_file( $finfo, $name_file );
				finfo_close( $finfo );
					
				$armime = explode(";", $ret['mime']);
				if ( isset($armime[0]) )
				{
					$type = explode("/", $armime[0]);
					switch($type[0])
					{
						case 'audio':
						case 'video':
						{
							$info = KDisk_FFmpeg::get_info_file( $name_file );
							if ( ! $info["err"] )
							{	
								$ret['ffmpeg_info'] = $info;
								$ret['err'] = 5;
								return $ret;
								
							}
						}
						break;
					}
				}
		
			}
		}else
		{
//			echo "finfo_open not suported";

		}
		
		if ( $ret['mime'] == '' )
		{
			$info = KDisk_FFmpeg::get_info_file( $name_file );
			if ( ! $info["err"] && $info["video"] && $info["audio"])
			{
				$ret['ffmpeg_info'] = $info;
				$ret['err'] = 5;
				return $ret;
			}
		}
		
		
		if ( isset( $info ) && $info['video'] && ! isset( $info['metadata'] ) && ! $info["err"] )
		{
			$size = getimagesize( $name_file );
		}else
		if ( (isset( $info ) && $info["err"] ) || ! isset( $info ) )
		{//
			
			if ( function_exists('exif_imagetype') )
			{
				$itype = exif_imagetype( $name_file );
				if ( $itype ) 
				{
					$size = getimagesize( $name_file );
				}
			}else
			{
				$type = $this->get_type_file_from_ext( $name_file );
				if ( $type == 'image' )$size = getimagesize( $name_file );
			}
			
		}

		

		
		
		if ( $size )
		{///Если картинка
			
			switch ( $size['mime'] )
			{	
				case 'image/png':
				{
					$img_in = imagecreatefrompng( $name_file );
				}
				break;	
				case 'image/jpeg':
				{
					$img_in = imagecreatefromjpeg( $name_file );
				}
				break;
				case 'image/gif':
				{
					$img_in = imagecreatefromgif( $name_file );
				}
				break;
				default:

				break;			
			}
			if ( isset($img_in) )
			{
				$ret['size'] = $size;
				$ret['err'] = 0;
				$ret['type_file'] = 'image';	
				$ret[ 'img_data_1' ] = $this->make_small_image( $img_in, $size, array(32,32) );
				$ret[ 'img_data_2' ] = $this->make_small_image( $img_in, $size, array(128,128) );
				
			}
			
		}else
		{
		
		}

		
		if( $ret['err'] == 1  )
		{//может видео или звук
			if ( isset($info) && ! $info["err"] )
			{
				$ret['ffmpeg_info'] = $info;
				$ret['err'] = 5;
				return $ret;
			}
				
			if( $ret['err'] == 1 )
			{//Что то другое
				$name_file_out  = 0;
				if ( $ret['mime'] )	
				{
					if( strstr ($ret['mime'],'video') )
					{
						$name_file_out = __DIR__ . "/../imgs/kvvideo.png";
					}
				}
				if ( ! $name_file_out )
				{
					$ret['type_file'] = $this->get_type_file_from_ext( $name_file );
					$ret['err'] = 0;
					switch( $ret['type_file'] )
					{
						case 'zip':
						{
							$name_file_out = __DIR__ . "/../imgs/kvzipincon.png";
						}
						break;
						case 'audio':
						{
							$name_file_out = __DIR__ . "/../imgs/kvnotes.png";
						}
						break;
						case 'video':
						{
							$name_file_out = __DIR__ . "/../imgs/kvvideo.png";
						}
						break;
						
						default:
							$name_file_out = __DIR__ . "/../imgs/kvdoc.png";
							break;	
					}
				}
				
				$ret_tem = $this->get_preview_images ( $name_file_out ,'','' );
				if ( ! $ret_tem['err'] )
				{
					$ret['img_data_1'] = $ret_tem['img_data_1'];
					$ret['img_data_2'] = $ret_tem['img_data_2'];								
				}
			}
				
			
		}
	
		return $ret;			
	}
	function get_type_file_from_ext( $name_file )
	{
		$path_info = pathinfo( $name_file );
   	 	switch( strtolower( $path_info['extension'] ) )
		{
			case "mov":
			case "mkv":
			case "mts":
			case "mp4":
			case "avi":
				return "video";
				break;
			case "wav":
			case "mp3":
				return "audio";
				break;
			case "7z":
			case "rar":	
			case "tgz":
			case "tar":
			case "zip":
				return "zip";
				break;
			case "png":
			case "gif":
			case "jpg":
			case "jpeg":
				return "image";
				break;
		}
		return '';
	}

}

