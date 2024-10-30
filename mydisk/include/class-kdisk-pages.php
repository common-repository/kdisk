<?php 
/* Формируем страницы html

*/

class KDisk_Pages{
	
	//Выводим в html линки на js, css
	public $m_html = '';
	public $m_Upload ;
	static $m_Base;
	private $m_fun_get_preview_file = 0; //Функция возвращающая иконку к файлу	
	private $m_user_params;
	private $m_name_folders;
	private $m_KDisk_Folders;
	public function __construct() {
		
	}
	
	public function set_KDisk_Folders( $object )
	{
		$this->m_KDisk_Folders = $object;
	}
	
	public function set_function_kv_mydisk_set_file( $fun )
	{
		$this->m_fun_kv_mydisk_set_file = $fun;
	}
	public function set_function_get_preview( $fun )
	{
		$this->m_fun_get_preview_file = $fun;
	}
	public function set_base_class( $class )
	{
		$this->m_Base = $class;
	}
	public function set_name_folders( $ar_folders ){
				
		$this->m_name_folders = $ar_folders;
	}
	public function set_user_params( $ar_params ){
		$this->m_user_params = $ar_params;
		
	}
	/*
	*	Страница просмотра по общедоступной ссылке
	*/
	public function make_page_patch ( $info )
	{		
		$html = $this->getShowLink();
		
		$href = $info['direct_link'];
		$html .= '<a href="/" download id="f_dwn_load" style="display:none"></a>';
		if ( $info['type'] == 1 )//Показываем один файл
		{

			$html .= '<div class="kv-page-view kd-window" onclick="KVClick(this);">';
			$html .= '<div class="kv-button-panel"><button id="direct_link" class="kv-but-send" onClick="KVClick(this);" title="' . esc_html(__('Download','kdisk')) . '" href="'.$href.'"></button></div>'; 
			
			if( strstr( $info['type_file'],"video" ) || strstr( $info['mime'],"video" ))
			{
				$url = call_user_func( $this->m_fun_get_preview_file, $info['name_file']  );
				
				$html .= '<video class="kv-one-img-pre-view-video" src = "' . $href . '" onclick="KVClick(this);" controls poster="' . $url['url_pre_view_2'] . '" /></video>';
			}elseif ( strstr( $info['type_file'],"image" ) ) {
				$html .= '<img class="kv-one-img-pre-view" src = "' . $href . '?view" onclick="KVClick(this);"/>';
			}elseif ( strstr( $info['type_file'],"audio" ) || strstr( $info['mime'],"audio" ))
			{
				$html .= '<audio class="kv-one-img-pre-view" src = "' . $href . '" onclick="KVClick(this);" controls/></audio>';
			}else
			{
				$folders = $this->m_KDisk_Folders;
				$src = $folders->get_url_mydisk_script() . "/imgs/kvdoc.png";
				$url = call_user_func( $this->m_fun_get_preview_file, $info['name_file']   );
				
				if ( $url ) 
				{
					$src = $url['url_pre_view_2'];
				}
				$html .= '<img class="kv-one-img-pre-view" src = "' . $src . '" />' ;
			}
			$html .= '</div>';
		}elseif ( $info['type'] == 0 )//this is folder
		{
			
			$html .= '<a href="/" download id="f_dwn_load" style="display:none"></a>';	
			$cls_panels = "kv-panels-greed-big";
			if ( isset( $_COOKIE['kd_view_panel'] ) )	
			{
				$cls_panels = sanitize_text_field( $_COOKIE['kd_view_panel'] );
			}
			$html .= '<div class="' . $cls_panels . ' kd-window"><div class="kv-panels-block">';
			$html .= '<div class="kv-left-buttons" id="krburmenu" ontouchstart="KROnTouchStart(this)"  ontouchmove="KROnTouchMove(this)">';
	    	$html .= '<button id="kv_download" class="kv-but-send" onClick="KVClick(this);" title="' . esc_html(__('Download','kdisk')) . '"></button>';
			$html .= '<button id="kv_mode_view" class="kv-but-send" onClick="KVClick(this);" title="' . esc_html(__('View Mode','kdisk')) . '"></button></div>';
			///////////////////////////////////////
			$html .= $this->getHtmlListFiles( $info['name_file'] . "/", "", 3);	
		}
		//
		
		
		
        wp_add_inline_script('kdisk-kvfun','var g_access = ' . $this->m_user_params['user_access'] . ';');
		$this->m_html = $html ;
	}
	public function getShowLink()
	{
		
		$html = "";
		wp_add_inline_script('kdisk-kvfun','g_kv_url_theme = "' . $this->m_name_folders['url_mydisk'] . '";g_kv_url_mydisk = "' . $this->m_name_folders['disk'] . '";' . 'g_kv_folder = "' . $this->m_name_folders['drive'] . '";');
		return $html;     	
	}
	//Из директория формируем список файлов в формат html возврашаем переменной
	public function getHtmlListFiles( $path, $KV_DIR_USER_CUR, $mode = 0 )
	{
		 $html = "";
		 if( $handle = opendir( $path ) )
		 {
			$ind = 0;
			while( $entry = readdir( $handle ) )
			{
			    $files[ $ind++ ] = $entry;
				
			}
		
			$ar_dir = array();
			$ar_file = array();
			$ind = 0;
			while($ind < count( $files ))
			{
				if ( $mode == 1)
				{
					$path_info = pathinfo($files[ $ind ]);
					if ( isset( $path_info['extension'] ) && $path_info['extension'] == $this->m_name_folders['ext_patch_trash']  )
					{ 
						$ind++; continue; 
					}
				}
				
			
				$fulname = $path . "/" . $files[ $ind ];
		
				if ( is_dir( $fulname ) )
				{
					$ar_dir[ count($ar_dir) ] = $files[ $ind ];
				}else
				{
					$ar_file[ count($ar_file) ] = $files[ $ind ];
				}

				$ind++;
			}
	
			if ( $ar_dir )
			{
				if ( !natcasesort( $ar_dir ))
				{

				}
			}
			if ( $ar_file )
			{
				if ( !natcasesort( $ar_file )){

					
				}

			}
			if ( count($ar_dir) && count($ar_file) )
			{
				$files = array_merge($ar_dir, $ar_file);
			}else
			if ( count($ar_dir) )
			{
				$files = $ar_dir;
			}else
			if ( count($ar_file) )
			{
				$files = $ar_file;
			}
		


		
		$ind = 0;
		
		$prgstat = 0;

		foreach ($files as $key => $value) 
		{
			$entry = $files[ $ind ];
			$ind++;
			$entry = $value;
			if($entry[0] == "" || $entry[0] == "." || $entry == "..")continue;
			
			$fulname = "/" . KDisk_trim_path ( $path . "/" .  $entry );

			call_user_func( $this->m_fun_kv_mydisk_set_file, $fulname, '', 0 );
		
			if ( $KV_DIR_USER_CUR == "/")$KV_DIR_USER_CUR="";
			$urlfile = $KV_DIR_USER_CUR . "/" . $entry;
			
			
			
			if ( is_dir( $fulname ) ){
					$urlfile .= "/";
			}
			$oldfullnamefile = "";
			$view_file_name = $entry;
			if ( $mode == 1)
			{
				$view_file_name = substr( $entry , 0, strrpos($entry,"_"));
				
				$file_patch = $fulname . "." . $this->m_name_folders['ext_patch_trash'];
			
			}else
			{
				
			}
			
			if ( $mode == 1)
			{
				$html .= '<div class="kv-list-item kv-file kv-file-trash ';
			}else
			{
				$html .= '<div class="kv-list-item kv-file ';
			}
			if ( is_dir( $fulname ) ){ $html .= 'kv-dir';}
			$html .= '"  onClick=" return KVClick(this);">';
			$url = call_user_func( $this->m_fun_get_preview_file, $fulname  );
			
			if (isset($url['uName']) && strlen($url['uName']))
			{
				$view_file_name = $url['uName'];
			}
				if ( is_dir( $fulname ) ){
					$html .= '<div class="kv-list-item-icone kv-list-item-icone-noamin">';	
					$html .= '<img src="' . $this->m_name_folders['url_mydisk'] . '/imgs/folder.png">';
				}else
				{
					$html .= '<div class="kv-list-item-icone">';	
					{
						
						if ( $url )
						{
							$progress = '';
							
							if ( $url['make_work'] && isset($url['idtask']) && $url['idtask'] && $url['make_work'] < 100 ) 
							{
								
								if( $url['make_work'] <= 50)
								{
									$rotate_left = 180 / 50 * $url['make_work'];
									$rotate_right = 0;	
								}else
								{
									$rotate_right = 180 / 50 * ($url['make_work'] - 50);
									$rotate_left = 180;	
									
								}
								$prgstat++;
								$progress = '<div class="work-progress-value" kd-idtask="' . $url['idtask'] . '">' . $url['make_work'] . " %</div>";
								$progress .= '<div class="work-progress"><div class="radial"><div class="circle left rotate"><span style="transform: rotate(
' . $rotate_left . 'deg);"></span></div><div class="circle right rotate"><span style="transform: rotate(
' . $rotate_right . 'deg);"></span></div></div></div>';
							}
							
							if (isset($url['noimg']))
							{
								$class_img = 'class="kv-no-img-file"';
							}else
							{
								$class_img = 'class="kv-waiting-img-file"';
							}
							
//							$html .= '<img ' . $class_img . ' src="' . $url['url_pre_view'] . '" srcset="' . $url['url_pre_view'] . ' 100w, ' . $url['url_pre_view_2'] . ' 500w" >' . $progress;
							$html .= '<img ' . $class_img . ' kd-src-a="' . $url['url_pre_view'] . '"  kd-src-b="' . $url['url_pre_view_2'] . '" >' . $progress;
						}
					}
				}
				if ( $mode == 3 )
				{
					$urlfile = $url['url_open'];
				}

				
				$html .= '</div>';//class="kv-list-item-icone"
				$html .= '<div class="kd-list-item-element">';
					$html .= '<div class="kv-list-item-element-name" >';
					$html .= 	'<div class="kv-file-href" href="'.$urlfile.'" type-file="' . $url['type_file'] . '"  kd-views="' . $url['cnt_views'] . '" >'.$view_file_name.'</div>';
					$html .= '</div>';//kv-list-item-element-name"
					$html .= '<div class="kv-list-item-element-size" >';
					$html .= '<div class="kv-file-size">';
					if ( !is_dir( $fulname ) ){
						$html .= KDisk_make_size_file(filesize( $fulname ));	
					}
					$html .= '</div>';
					$html .= '</div>';//kv-list-item-element-size"
					
					if( $url['cnt_views'] )
					{
						$html .= '<div class="kd-list-item-element-view-img">';
						$html .= '<div class="kd-cnt-view-img" title="' . esc_html(__('Views','kdisk')) . ': ' . $url['cnt_views'] . '&#10' . esc_html(__('Downloaded','kdisk')) . ': ' . $url['cnt_dwns'] . '"></div>';
						$html .= '</div>';
						$html .= '<div class="kd-list-item-element-view">';
						$html .= '<div class="kd-cnt-view-val">'. $url['cnt_views'] . '</div>';
						$html .= '</div>';
					}
					
					
					
				$html .= '</div>';//class="kv-list-item-element"
				if($mode == 1)
					{
						$html .= '<div class="kv-list-item-element-menu-trash">';
					}else
					if($mode == 3)
					{
						$html .= '<div class="kv-list-item-element-menu-view kv-list-item-element-menu">';
					}else
					{
						$html .= '<div class="kv-list-item-element-menu">';
					}
					$html .= '</div>';
        	$html .= '</div>';//class="kv-list-item"
        }
        closedir($handle);
    	}
		
		if ( $prgstat )
		{
			wp_add_inline_script('kdisk-kvfun','document.addEventListener("DOMContentLoaded", function(){ progress_make_work(); });');
		}
		
		return $html;
	}
	//Формируем страничку корзины пользователя
	function MakePageTrash( $folder_trash )
	{
		
		
		$html = $this->m_html;
		$html = $this->getShowLink();
		
		$html .='<div class="kv-panels kd-window" >
	<div class="kv-left-buttons" id="krburmenu" ontouchstart="KROnTouchStart(this)"  ontouchmove="KROnTouchMove(this)">

    <button id="kv_recover" class="kv-but-send" onClick="KVClick(this);" title="' . esc_html(__('Recover','kdisk')) . '"></button>
    <button id="kv_delete_trash" class="kv-but-send" onClick="KVClick(this);" title="' . esc_html(__('Delete forever','kdisk')) . '"></button>
    <button id="kv_clear_trash" class="kv-but-send" onClick="KVClick(this);" title="' . esc_html(__('Clear trash forever','kdisk')) . '"></button>
	<button id="kv_mode_view" class="kv-but-send" onClick="KVClick(this);" title="' . esc_html(__('View Mode','kdisk')) . '"></button>
    </div>
	<div class="kv-list-liles">

    <div class="kv-list-item-disk">';
			//Добавим занятый размер в гигах
//			global $G_KRDISK;

			$mem_free = $this->m_user_params['mem_total'] - $this->m_user_params['mem_busy'];
			if( $mem_free < 0) $mem_free = 0; 
			$str_mem_free = KDisk_make_size_file( $mem_free );
			$str_mem_total = KDisk_make_size_file( $this->m_user_params['mem_total'] );
			$html .= '<div class="kd-size-drive" title="' . esc_html(__('Free','kdisk')) . ' ' . $str_mem_free . ' ' . esc_html(__('from','kdisk')) . ' ' . $str_mem_total . '">';
			$html .= '<p>' . $str_mem_free . '</p>';
			$html .= '</div>';
			/////////////

		$html .= '
		<div class="kv-list-item-element">
			<div class="kv-path-dir" >';
				
				$html .='<a href="' . '/' . $this->m_name_folders['disk'] . '/' . $this->m_name_folders['drive'] . '/">' . esc_html(__('Disk','kdisk')) . '</a><div style="display:inline-block">'; 

				//Разберём отдельно по названиям директория
					$html .='<div class="kv-path-separator"><a href="./">' . esc_html(__('Trash','kdisk')) . '</a></div>'; 
				$html .='</div>

			</div>
		</div>
	</div>
	</div>
	
    <div class="kv-list-files">';

		$path = $this->m_name_folders['full_dir_user_files'] . "/" . $folder_trash . "/"; 
		
		$html .= $this->getHtmlListFiles( $path , "", 1 );
		$html .='</div></div></div></div>';
		$this->m_html = $html;
		wp_add_inline_script('kdisk-kvfun','var g_access = ' . $this->m_user_params['user_access'] . ';');
		return $html;
	}
	
/*
*	 
*	Формируем страницу файлов пользователя
*	
*/
	public function MakePageUser($path, $KV_DIR_USER_CUR)
	{
		$mem_free = $this->m_user_params['mem_total'] - $this->m_user_params['mem_busy'];
		
		$this->InitUpload( $path, $KV_DIR_USER_CUR );
		$Up = $this->m_Upload;

		$ar = explode ("/", $KV_DIR_USER_CUR );
		
		$html = $this->m_html;
		$html .= $this->getShowLink(); 
		
		$html .= '<a href="/" download id="f_dwn_load" style="display:none"></a>';
		$cls_panels = "kv-panels";
		if ( isset( $_COOKIE['kd_view_panel'] ) )	
		{
			$cls_panels = sanitize_text_field( $_COOKIE['kd_view_panel'] );
		}
		$html .= '<div class="' . $cls_panels . ' kd-window"><div class="kv-panels-block">';
		$html .= '<div class="kv-left-buttons" id="krburmenu" ontouchstart="KROnTouchStart(this)"  ontouchmove="KROnTouchMove(this)">';
		
		
		

		if ( $this->m_user_params['user_access'] & 2 )
		{
			if ( $mem_free > 0)
			{
				
				$html .= '<button class="kv-but-send" id="kv_send" onClick="document.getElementById(\'upfile\').click()" title="' . __('Upload to cloud',KDISK_PLG) . '">' . $Up->getHTMLForm() . "</button>";
					$html .= '<button id="kv_status" class="kv-stat-send"></button>';
			}
			
			if ( count($ar) < 4)
			{
				$html .= '<button id="kv_createdir" class="kv-but-send" onClick="KVClick(this);" title="' . esc_html(__('Create a folder','kdisk')) . '"></button>';

			}
		}
		
	   		$html .= '<button id="kv_download" class="kv-but-send" onClick="KVClick(this);" title="' . esc_html(__('Download','kdisk')) . '"></button>';
		
   	 	if ( $this->m_user_params['user_access'] & 4 )
		{
			$html .= '<button id="kv_remove" class="kv-but-send" onClick="KVClick(this);" title="' . esc_html(__('Delete','kdisk')) . '"></button>';
		}
		if ( $this->m_user_params['user_access'] & 8 ){
			$html .= '<button id="kv_trash" class="kv-but-send" onClick="KVClick(this);" title="' . esc_html(__('Trash','kdisk')) . '"></button>';

		}
		$html .= '<button id="kv_mode_view" class="kv-but-send" onClick="KVClick(this);" title="' . esc_html(__('View Mode','kdisk')) . '"></button>';
	
    $html .= '</div>
	<div class="kv-list-liles">
    	<div class="kv-list-item-disk">';
					
		//Добавим занятый размер в гигах
		
		$class_mem_size = 'kv-normal-disk-space';
		if( $mem_free < 0)
		{
			$mem_free = 0; 
			$class_mem_size = 'class="kv-no-disk-space"';
			$str_mem_free = 'Нет места';
		}else
		{
			$str_mem_free = KDisk_make_size_file( $mem_free );
		}
		$str_mem_total = KDisk_make_size_file( $this->m_user_params['mem_total'] );
		$html .= '<div class="kd-size-drive" title="' . $str_mem_free . ' ' . esc_html(__('from','kdisk')) . ' ' . $str_mem_total . '">';
		$html .= '<p ' . $class_mem_size . '>' . $str_mem_free . '</p>';
		$html .= '</div>';
				
		$html .= '<lu class="kv-dir-names">
			<li><a href="' . '/' . $this->m_name_folders['disk'] . '/' . $this->m_name_folders['drive'] .'/">' . esc_html(__('Disk','kdisk')) . '</a></li>';
				  
				//Разберём отдельно по названиям директория
				
				$cnt = 0;
				$href = "/" . $this->m_name_folders['disk'] . "/" . $this->m_name_folders['drive'] . "/";
				for( $i = 0; $i < count($ar); $i++ )
				{
					if( !strlen($ar[$i]) )continue;
					//if ( $cnt ) echo '';
					$href .= $ar[$i]."/";
					$html .= '<li><a href="'.$href.'">'.$ar[$i].'</a></li>'; 
					$cnt++;
				}
				$html .= '</lu>

		</div>
	</div>
    <div class="kv-list-files">';

		$html .= $this->getHtmlListFiles( $path, $KV_DIR_USER_CUR );
		$html .='</div></div></div></div>';
		$this->m_html = $html ;
		
		wp_add_inline_script('kdisk-kvfun','var g_access = ' . $this->m_user_params['user_access'] . ';');

		return $this->m_html;

	}
	//Добавляем к текущей страницы html код
	function AddHtmlInCurrentPage ( $html ){
		$this->m_html .= $html;
		return $this->m_html;
	}
	//Инициализируем класс Закачки
	function InitUpload( $path, $KV_DIR_USER_CUR )
	{
		$Up = new KDisk_Upload;
//		$Up->dir_main = "/".KV_DIR_DISK;
		$Up->upload_dir = $path;
//		$Up->url_uploaddir = $myurl;
		$Up->url_request = "/" . $this->m_name_folders['disk'] . "/";
		$Up->dir_curdir = $KV_DIR_USER_CUR;
		$Up->url_scripts = $this->m_name_folders['url_mydisk'];
		$this->m_Upload = $Up;
	}
}

