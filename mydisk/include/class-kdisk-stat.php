<?php 

class KDisk_Stat{
	
	
	
	public static function download()
	{
		
		$log = "";
		$headers=array();
		foreach (getallheaders() as $name => $value) {
	    	$log .= ( "$name: $value\n");
			$headers[$name] = $value;
		}
		
		$uri_parts = explode('?', urldecode($_SERVER['REQUEST_URI']), 2);
		$filename = ( $uri_parts[0] );
	
		$log .= $filename;

		
		
		$fullPath = $_SERVER['DOCUMENT_ROOT'] . "/" . $filename;
		$fsize = filesize($fullPath); 
		$path_parts = pathinfo($fullPath); 
		if ( isset($_SERVER['HTTP_RANGE']) )
		{
	
			if (!preg_match('/bytes=\d*-\d*(,\d*-\d*)*$/i', $_SERVER['HTTP_RANGE'])) {
	    		error_log("Client requested invalid Range.");
			    send_error($filelength);
    			exit;
			}
			$ranges = explode('-', substr($_SERVER['HTTP_RANGE'], 6)); // everything after bytes=
		
			switch( count($ranges) )
			{
				case 2:
					http_response_code(206);
					$sizesend = $fsize;
					
					if ( $ranges[1] )
					{
						$sizesend = $ranges[1]- $ranges[0];
						header("Content-Range: bytes " . $ranges[0] . "-" . $ranges[1] . "/" . $fsize);	
						$sizesend = $ranges[1] - $ranges[0];
					}else
					{
						$ranges[1] = $ranges[0] + $sizesend;
						if ( $ranges[1] >= $fsize ) $ranges[1] = $fsize - 1;
						
						header("Content-Range: bytes " . $ranges[0] . "-" . $ranges[1] . "/" . $fsize);	
				
						$sizesend = $ranges[1] - $ranges[0] + 1;
					}
					
					self::KDLog($log);
					$cook = self::GenericIdV();
					if ( !isset($_COOKIE['kdisk-view-id']) )
					{
						setcookie ('kdisk-view-id', $cook, time() + 60*60*10,"/") ;
					}else
					{
						$cook = $_COOKIE['kdisk-view-id'];
					}
					
					header("Accept-Ranges: bytes");
					header("Content-type: video/*");
					header("Content-length: $sizesend"); 
					$lenout = 0;
					$fd = fopen ($fullPath, "r");
					fseek( $fd, $ranges[0], SEEK_SET );
					$view_pp =0;
					$sizesend = $fsize / 5;
					if ($sizesend > 1024 * 10000) $sizesend = 1024 * 10000;
					while(!feof($fd)) { 
						$len = 2048;
						$buffer = fread($fd, $len);
						echo $buffer;
						$lenout+=2048;
						if ($lenout > $sizesend) break;
				 
					} 
					fclose ($fd);

					self::writeStat("range", $filename, $fullPath, $ranges, $lenout, $cook,$fsize);
				
					
					exit;
				break;
			}
	
		}
		
		$cook = "" ;
		$ar = explode("/",trim($filename,"/"));
		$path_parts = pathinfo($fullPath); 
		if(isset($ar[1]) && $ar[1] == "temporary" && $path_parts['extension'] != "zip")
		{
			http_response_code(404);
			exit;
		}
		$cook = self::GenericIdV();
		if ( !isset($_COOKIE['kdisk-view-id']) )
		{
			setcookie ('kdisk-view-id', $cook, time() + 60*60*10,"/") ;
		}else
		{
			$cook = $_COOKIE['kdisk-view-id'];
		}
		if (isset($_GET['img'])||isset($_GET['view']))
		{
			header("Content-type: image/jpeg"); 
			header("Content-length: $fsize"); 
		}else
		{
			header("Content-type: application/octet-stream"); 
			header("Content-Disposition: filename=". $path_parts['basename'] ); 
			header("Content-length: $fsize"); 
			header("Cache-control: max-age=900");
		}

		$fd = fopen ($fullPath, "r");
		
		self::KDLog($log);
		
		while(!feof($fd)) { 
			$buffer = fread($fd, 2048);
			echo $buffer; 
		} 
		fclose ($fd);
		if (isset($headers['Accept']) || strstr($headers['User-Agent'], "SmartTV"))
		{
			self::writeStat("view", $filename, $fullPath, $ranges, filesize($fullPath), $cook, $fsize);
		}else
		{
			self::writeStat("download", $filename, $fullPath, $ranges, filesize($fullPath), $cook, $fsize);
		}
		
		
		exit; 
	}
	public static function writeStat($type, $filename, $fullPath, $ranges, $lenout, $cook, $fsize)
	{

		$ar = explode("/",trim($filename,"/"));
		
		if ( $ar && isset($ar[0]) )
		{
			$dir_stat = $_SERVER['DOCUMENT_ROOT'] . "/" . $ar[0] . "/temporary/stat/";
			$filestst = $dir_stat . $type . "_" . $cook . "_" . self::GenericIdV() . ".vw";
			$fiview = @fopen( $filestst, "w+");
			if (!$fiview)
			{
				mkdir($dir_stat, 0755 ,true );
				$fiview = @fopen( $filestst, "w+");
			}
			if ( $fiview )
			{
				fwrite($fiview,"time: " . time() . "\n");
				fwrite($fiview,"cookie: " . $cook . "\n");
									
				$fullPath = str_replace("//","/",$fullPath);
				fwrite($fiview ,"file: " . $fullPath . "\n" );
				fwrite($fiview ,"type: " . $type . "\n" );
				fwrite($fiview ,"range: " . $ranges[0] . "\n" );
							
				fwrite($fiview ,"size-dwn: " . $lenout . "\n" );
				fwrite($fiview ,"size-file: " . $lenout . "\n" );
				fwrite($fiview ,"ip: " . $_SERVER['REMOTE_ADDR'] . "\n");
				fwrite($fiview ,"url: " . $_SERVER['REQUEST_URI'] . "\n");
				
				$headers = "\n";
				foreach (getallheaders() as $name => $value) { $headers .= ( "$name: $value\n");}
				fwrite($fiview , $headers);
				fclose($fiview);
			}
			switch($type)
			{
				case "range":
				{
					self::KDLog("VAV");
					self::VerifyAllView($dir_stat, $filename, $fullPath, $ranges, $lenout, $cook, $fsize);	
				}
			}
			
		}
						
	}
	public static function KDLog($txt)
	{
		//return;
		$filename = ( urldecode($_SERVER['REQUEST_URI']) );
		$ar = explode("/",trim($filename,"/"));
		if ( $ar && isset($ar[0]) )
		{
			$filelog = $_SERVER['DOCUMENT_ROOT'] . "/" . $ar[0] . "/temporary/" . date("d-m-y")."dwn.log";
			$flog = fopen( $filelog, "a+");
		
//		$flog=fopen(__DIR__ ."/" .date("d-m-y")."dwn.log","a+");
		fwrite($flog,"\n\r[".date("H:i:s")."] ".$_SERVER['REMOTE_ADDR']." ".session_id()."\r\n");
		fwrite($flog,"\t".$txt."\r\n" );
		fclose($flog);
		}
	}
	static function GenericIdV( $strength = 16 )
	{
		$input = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    	$input_length = strlen($input);
	    $random_string = '';
    	for($i = 0; $i < $strength; $i++) {
        	$random_character = $input[mt_rand(0, $input_length - 1)];
	        $random_string .= $random_character;
    	}
	    return $random_string;
	}
	static function VerifyAllView($dir_stat, $filename, $fullPath, $ranges, $lenout, $cook, $fsize)
	{
		$path = $dir_stat;
		$name_file = $fullPath;
		self::KDLog("VAV1 " . $name_file);
		if( $handle = opendir( $path ) )
		{
			$view = 0;
			$sizedwn = 0;
			$files_stat = array();
			$cnt_files_stat = 0;
			while( $entry = readdir( $handle ) )
			{
		    	if (strpos($entry, $cook) > 0)
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
			self::KDLog("VAV2 " . $sizedwn);
			$sizeV = filesize($name_file) / 1.1;
			self::KDLog("VAV3 " . $sizedwn . " " . $sizeV);
			if ( $sizedwn && $sizeV )
			{
				while ( $sizedwn >= $sizeV )
				{
					
					for($d = 0; $d < $cnt_files_stat; $d++)
					{
						unlink($files_stat[$d]);
					}
					self::KDLog("VAV view");
					self::writeStat("view", $filename, $fullPath, $ranges, $sizedwn, $cook, $fsize);
					
					$sizedwn -= $sizeV;

				}
			}
		}
	}

}
if(!isset($KDISK_NO_AUTO_DOWNLOAD))
{
	KDisk_Stat::download();
}