<?php
//JS запросы
class KDisk_Archive
{
	private $m_temp_directory;
	private $m_archive;
	private $full_name_arc;
	private $short_name_arc;
	public $m_max_size_in = 26843545600;//25*1024*1024*1024;
	
	public function set_temporary_directory( $dir )
	{
		$this->m_temp_directory = $dir;
	}
	public function get_full_name_archive()
	{
		return $this->full_name_arc;
	}
	public function get_short_name_archive()
	{
		return $this->short_name_arc;
	}
	public function make_archive( $name_files, $sdir )
	{
		if ( isset($sdir) )
		{
			$dir_arc = $sdir;
		}else
		{
			$dir_arc = KDisk_generate_string(16); 
		}
			
		$dir_temporary = $this->m_temp_directory . "/" . $dir_arc;
		if ( ! is_dir( $dir_temporary ) )
		{
			mkdir( $dir_temporary, 0755, true);	
		}
		
		$this->m_archive = new ZipArchive();
		
		$file_name_zip = 'kdisk-' . ( microtime( true ) * 10000 ) . '.zip';
		$full_name_arc = $dir_temporary . '/' . $file_name_zip;
//		ZipArchive::CM_STORE = 1;
	//	echo ZipArchive::CM_STORE;
		
		if ( ! $this->m_archive->open( $full_name_arc, ZipArchive::CREATE | ZipArchive::OVERWRITE  ) )
		{

			return 0; 
		}
		$this->m_count_add_in_zip = 0;
		$this->m_current_in_size = 0;
//		$this->m_archive->registerProgressCallback();

/////////////
		session_write_close();
////////////////

		for( $i = 0; $i < count($name_files); $i++ )
		{
			$this->add_file_recursion( $this->m_archive, $name_files[ $i ], '' );
		}
		
		$this->m_archive->close();
		
		$this->short_name_arc = $dir_arc . '/' . $file_name_zip;
		$this->full_name_arc = $full_name_arc;
		
		return $full_name_arc;
	}
	private $m_count_add_in_zip = 0;
	private $m_current_in_size = 0;
	//Добавляем файл или директорий в архив
	private  function add_file_recursion($zip, $dir, $start = '')
	{
		
		if ( empty($start) ) {
			$start = $dir;
		}
		if ( is_file( $dir ) )
		{
			
			$this->m_current_in_size += filesize( $dir );
			if ( $this->m_current_in_size > $this->m_max_size_in )return;
			
			if ( ! $zip->addFile( $dir, basename( $dir ) )) 
			{
				
			}
			
			if( method_exists( $this->m_archive , "setCompressionIndex" ) )
			{
				$this->m_archive->setCompressionIndex( $this->m_count_add_in_zip, ZipArchive::CM_STORE ); 
			}
			$this->m_count_add_in_zip++;
			
		}else
		if ( $objs = glob( $dir . '/*' )) {
			foreach( $objs as $obj ) { 
				if ( is_dir( $obj )) {
					$this->add_file_recursion( $zip, $obj, $start );
				} else {
					$this->m_current_in_size += filesize( $obj );
					if ( $this->m_current_in_size > $this->m_max_size_in )return;
						
					
					$zip->addFile( $obj, str_replace( dirname($start) . '/', '', $obj )); 
					if( method_exists( $this->m_archive , "setCompressionIndex" ) )
					{
						$this->m_archive->setCompressionIndex( $this->m_count_add_in_zip, ZipArchive::CM_STORE );
						
					}
					$this->m_count_add_in_zip++;
				}
			}
		}
	}
	////Генерируем случайную строку
	static public function generate_string($strength = 16) 
	{
		$input = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    	$input_length = strlen($input);
	    $random_string = '';
    	for($i = 0; $i < $strength; $i++)
		{
        	$random_character = $input[mt_rand(0, $input_length - 1)];
	        $random_string .= $random_character;
    	}
	    return $random_string;
	}

};
