<?
class KDisk_FFmpeg
{
//	public static $m_ffmpeg="/usr/local/bin/ffmpeg";
	public static $m_ffmpeg="ffmpeg";
	public static function get_info_file($name_file)
	{
		
		$result = array( "err" => 1 ); 
		
		$cmd = self::$m_ffmpeg . ' -hide_banner -i "' . $name_file . '" 2>&1' ; 

		ob_start();
		$ret = passthru($cmd);
		$ret = ob_get_contents();
	    ob_end_clean();
		$ar = explode("\n", $ret);
		$result['out_ffmpeg'] = $ret;
		$result["video_inf"] = "";
		$result["audio_inf"] = "";
		$result[ "err" ] = 3;
		$stream = 0;
		for( $i = 0; $i < count($ar); $i++ )
		{
			$ar[$i] = trim( $ar[$i] );
			
			$pos = strpos($ar[$i], "Invalid data found when processing input" );
			if ( $pos )	
			{
				break;
			}
			$pos = strpos($ar[$i], "Duration" );
			if ( $pos === 0 )
			{
				$ar2 = explode(",", $ar[$i]);
				$str_duration = substr($ar2[0], strlen("Duration:"));
				$ar3 = explode( ":", $str_duration );
				if ( $ar3 && count($ar3) > 2 ) 
				{
					$duration = $ar3[0] * 60 * 60 + $ar3[1] * 60 + $ar3[2];
				}else
				{
					$duration = 0;
				}
				$result[ "duration" ] = $duration;	
				
				$result["bitrate"] = substr($ar2[2], strlen("bitrate:"));
			}
			
			$pos = strpos($ar[$i], "Stream" );
			if ( $pos === 0 )
			{
				$result[ "err" ] = 0;	
				$stream[ count($stream) - 1 ] = $ar[$i];
				
				if ( strstr( $ar[$i], "Video") )
				{
					$result["video"] = 1;
					$result["video_inf"] .= $ar[$i];	
				}elseif ( strstr( $ar[$i], "Audio") )
				{
					$result["audio"] = 1;
					$result["audio_inf"] .= $ar[$i];		
				}
				
			}
			$pos = strpos($ar[$i], "Metadata" );
			if ( $pos === 0 )
			{
				$result[ "err" ] = 0;	
				$result[ "metadata" ] = 1;	
			}

		}
		
		
		return $result;
	}
}