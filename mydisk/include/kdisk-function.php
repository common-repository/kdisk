<?php
/*Функции
*
*
*
*/
//

include (__DIR__ . "/class-kdisk-pages.php");

if ( !function_exists ("KDisk_SizeFileOrDir") ) 
{
//Подсчитываем размер файла или директория рекурсивно
function KDisk_SizeFileOrDir($dir, $size = 0)
{
//	return 0;
	if ( strlen( $dir ) >= 255 ) return $size;
	if (is_file( $dir ))
	{
		$siz = filesize( $dir );
		$size += $siz;
	}elseif (is_dir( $dir ))
	{	
		$filelist = array();
		if($handle = @opendir( $dir )){
			while($entry = readdir( $handle )){
				if ( $entry == "." || $entry == "..")continue;
				$tmp = $dir . "/" . $entry;

	            if (is_dir( $tmp )) {
					$size = KDisk_SizeFileOrDir( $tmp, $size);
				} elseif (is_file($tmp)) {
					$siz = filesize( $tmp );
					$size += $siz;
				}else
				{
				}
    	    }
		}
	}

	return $size;
}

//Переименовываем файл и убираем лишнее из инени 
function KDisk_Rename( $oldname, $newname )
{
	$patterns = array();
	$patterns [0] = '/\/\.\.\//';
	$patterns [1] = '/\/\.\//';
	$oldname = preg_replace($patterns, '', $oldname);
	$newname = preg_replace($patterns, '', $newname);
	$ret = rename($oldname, $newname);
	if ( !$ret && is_dir($oldname))
	{
		rmdir($oldname);
		//$ret = copy($oldname, $newname);
	}
	return $ret;
}
//Создадим текстовый размер
function KDisk_make_size_file( $size )
{
	$fsize = $size;
	$ak = 0;
	$d = 1024;
	if( $fsize > $d )
	{
		$fsize = $fsize / $d;
		$ak++;
	}
	if( $fsize > $d )
	{
		$fsize = $fsize / $d;
		$ak++;
	}
	if( $fsize > $d )
	{
		$fsize = $fsize / $d;
		$ak++;
	}
	switch($ak)
	{
		case 0:
			$fsize .= " bytes";
		break;
		case 1:
			$fsize = round($fsize, 1 );
			$fsize .= " KB";
		break;
		case 2:
			$fsize = round($fsize, 1 );
			$fsize .= " MB";
		break;
		case 3:
			$fsize =  round($fsize, 2 );
			$fsize .= " GB";
		break;
	}
	return $fsize;
}
////Генерируем случайную строку
function KDisk_generate_string($strength = 16) {
	$input = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $input_length = strlen($input);
    $random_string = '';
    for($i = 0; $i < $strength; $i++) {
        $random_character = $input[mt_rand(0, $input_length - 1)];
        $random_string .= $random_character;
    }
 
    return $random_string;
}
function KDisk_trim_path( $path )
{
	if (!is_string( $path ))return $path;
	$tmp = trim ( $path , "\n\r\t\v\0/" );
	$tmp = str_replace ( '//', '/',$tmp);
	$patterns [0] = '/\/\.\.\//';
	$patterns [1] = '/\/\.\//';
	$tmp = preg_replace($patterns, '', $tmp);
	$tmp = trim($tmp);
	return $tmp;
	
}
}