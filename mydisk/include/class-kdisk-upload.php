<?php if(!headers_sent()){if ( session_status() == PHP_SESSION_NONE && ! session_id() ) { session_start(); }}
class KDisk_Upload{
//	public $str_funCallBackUpload;
	public static $m_upload_type = 1;
	public static $m_upload_xhr_size = 5242880;
	public $funCallBackUpload, $funTestFreeSize;
	public $upload_dir, $temporary_dir;
	public $url_request;
	public $url_scripts;
	public $dir_curdir;
	function __construct() {
		$this->upload_dir = $_SERVER['DOCUMENT_ROOT']."/uploadfiles/";
		$this->temporary_dir = $_SERVER['DOCUMENT_ROOT']."/temporary/";
		$this->url_request = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
		$this->dir_curdir="";
	}
	function upload(){
		
		if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_FILES["userfile"])) 
		{

			$cnt_file = count( $_FILES['userfile']['name'] );
			$sizeall = 0;
			for($i = 0; $i < $cnt_file; $i++)
			{
				$sizeall += (int)( $_FILES["userfile"]['size'][$i] );
			}
			$ret = call_user_func($this->funTestFreeSize, $this->upload_dir . "/" . sanitize_file_name($_POST['kv_user_dir']) . "/" ,$sizeall );
			if (!$ret)
			{
				call_user_func($this->funCallBackUpload, sanitize_file_name($_POST['kv_user_dir']), 1);
				exit;
			}
			
			for($i = 0; $i < $cnt_file; $i++)
			{
				$tmp_name = (sanitize_text_field($_FILES["userfile"]["tmp_name"][$i]));
				$name = (sanitize_text_field($_FILES["userfile"]["name"][$i]));
				if($name[0]=='.')$name[0]='-';
				$file_name_new = $this->upload_dir . "/" . sanitize_file_name($_POST['kv_user_dir']) . "/" . $name;
				for ( $jj = 1; $jj < 50; $jj++ )
				{
					if( file_exists( $file_name_new ) ){
						
						$info = pathinfo( $name );
						$file_name_new = $this->upload_dir ."/" . sanitize_file_name($_POST['kv_user_dir']) . "/" . $info['filename'] . " (" . $jj . ")." . $info['extension'];
					}else{
						break;
					}
				}
				move_uploaded_file( $tmp_name, $file_name_new );
		
			}
			call_user_func($this->funCallBackUpload,sanitize_file_name($_POST['kv_user_dir']),0);
			exit;
		} 
		
		if ( isset($_GET['ko_progress']))
		{
			$ret['procent'] = 0;
			$key = ini_get("session.upload_progress.prefix") . "KVFormUpdate"; 
			if (!empty($_SESSION[$key])) 
			{ 
				$current = sanitize_text_field($_SESSION[$key]["bytes_processed"]); 
				$total = (int)sanitize_text_field($_SESSION[$key]["content_length"]); 
				$ret['procent'] = $current < $total ? ceil($current / $total * 100) : 100; 
				$ret['total'] = $total;
				$ret['current'] = $current;
			} else 
			{ 
				
			} 
			echo json_encode( $ret );
			exit;
		}	
		if (isset($_GET['ko_set_part']))
		{
			
			$ret['err'] = 0;
			if (!isset($_POST['skey']))
			{
				$ret['skey'] = KDisk_generate_string(20) . "_upload";
			}else
			{
				$ret['skey'] = sanitize_text_field( $_POST['skey'] );
			}
			$ret['pos'] = (int)sanitize_text_field( $_POST['pos'] );
			$ret['length'] = (int)sanitize_text_field( $_POST['length'] );
			
			$name_part_file = sanitize_text_field($_POST['numfile']) . ".tmp";
			if ( isset( $_POST['namefile'] ))
			{
				$ret['namefile'] = sanitize_text_field($_POST['namefile']);
				$name = sanitize_text_field($_POST['namefile']);
				if($name[0]=='.')$name[0]='-';
				
				$tmp_name = $this->temporary_dir . "/" . $ret['skey'] . "/" . $name_part_file; 
				$file_name_new = ($this->upload_dir . "/" . sanitize_text_field($_POST['kv_user_dir']) . "/" . $name);
				for ( $jj = 1; $jj < 50; $jj++ )
				{
					if( file_exists( $file_name_new ) ){
						
						$info = pathinfo( $name );
						$file_name_new = $this->upload_dir ."/" . (sanitize_text_field($_POST['kv_user_dir'])) . "/" . $info['filename'] . " (" . $jj . ")." . $info['extension'];
					}else{
						break;
					}
				}
				$ret['newname'] = $file_name_new;
				$ret['file_crc32'] = hash_file('crc32b',$tmp_name);
				$ret['crc32'] = sanitize_text_field($_POST['crc32']);
				if (hexdec($ret['file_crc32']) != hexdec($ret['crc32'])) 
				{
					$ret['err'] = 5;
					$ret['errstr'] = __("Error Checksum",KDISK_PLG);
					$ret['errcrc32'] = hash_file('crc32b',$tmp_name );
					unlink( $tmp_name );
				}else
				{
					rename( $tmp_name, $file_name_new );
				}
				echo json_encode( $ret );
				exit;
			}
			
			if( ! is_dir( $this->temporary_dir ) )
			{
				@mkdir( $this->temporary_dir, 0755, true );
				if( ! is_dir( $this->temporary_dir ) )
				{
					$ret['err'] = 3;
				}
			}
			$filename = $this->temporary_dir . "/" . $ret['skey']; 
			if( ! is_dir( $filename ) )
			{
				@mkdir( $filename, 0755, true );
				if( ! is_dir( $filename ) )
				{
					$ret['err'] = 4;
				}
			}
			
			$ret['length'] =(int)sanitize_text_field($_POST['length']);

			$filename .= "/" . $name_part_file;
			
			if( is_file( $filename ) && $_POST['pos'] > filesize($filename) )
			{
				$ret['err'] = 1;
			}else
			{
				$fi = fopen($filename,"c");
				fseek( $fi, $_POST['pos'] );
				if(sanitize_text_field($_FILES['file']['name']))
		    	{
					$fil = fopen( sanitize_text_field($_FILES['file']['tmp_name']), "rb" );
					$lenwrite = fwrite( $fi, fread($fil,$_FILES['file']['size'] ));
					if ( $lenwrite != $_FILES['file']['size']) 
					{
						$ret['err'] = 2;
					}
					fclose( $fi );
					fclose( $fil );
				}
			}
			
			echo json_encode( $ret );
			exit;
		}
		
	}
	function getHTMLForm()
	{
		wp_enqueue_script('kdisk_upload',$this->url_scripts . '/script/upload.js?' . filemtime(__DIR__ . "/../script/upload.js"));
		wp_add_inline_script('kdisk_upload','var g_krurlcmd="' . $this->url_request . '",g_upload_type='.self::$m_upload_type.',g_upload_xhr_size='.self::$m_upload_xhr_size.';', 'before' );
		$html = '';

		$html .= '<form  class="kv-form-up" method="POST" id="KVFormUpdate" enctype="multipart/form-data">
<input type="hidden" value="KVFormUpdate" name="' . ini_get("session.upload_progress.name") .'">
<input type="hidden" value="' . $this->dir_curdir . '" name="kv_user_dir">
<input type="file" name="userfile[]" id="upfile" onChange="KVSelFiles(event)" style="display:none" multiple >';
//<button id="kv_send" class="kv-but-send" onClick="document.getElementById(\'upfile\').click()" title="' . __('Upload to cloud',KDISK_PLG) . '"></button>
$html .= '</form>';
		return $html;
 	} 
}