<?php 
if ( !defined("KDISK_OPT"))
{
	define ("KDISK_OPT","kdisk-opt");
}



class KDisk_WP{
	public static $disk_name = 'kdisk';
	public static $dir_users_files = 'kvfiles';
	public static $ar_sared_access = array('kdisk_share_read','kdisk_share_write','kdisk_share_delete', 'kdisk_share_trash');
	public static $notlogin_share_read,$notlogin_share_write;
	public static $kdisk_space;
	public static $kdisk_statistics;
	public static $m_pages = array(array('KDisk','kdisk'),array('KDisk view','view'),array('KDisk drive','drive'),array('KDisk trash','trash'),array('KDisk shared','shared'),array('KDisk shared-trash','shared-trash'));
	//Обновление
	public static function plugin_upgrate()
	{
		
	}
	//Актавация плагина
	public static function plugin_activation()
	{
		//Roles
		$ar_sared_access = self::$ar_sared_access;
		$wp_roles = wp_roles();
		$editable_roles = $wp_roles->roles;
				
		foreach ( $editable_roles as $rolename => $details ) {
				$role = get_role( $rolename );
				for( $i = 0; $i < count( $ar_sared_access ); $i++)
				{
					if ( $rolename == 'administrator')$wp_roles->add_cap( $rolename, $ar_sared_access[ $i ] ); else
					if ( $rolename == 'editor')$wp_roles->add_cap( $rolename, $ar_sared_access[ $i ] ); else 
					if ( $rolename == 'author' && $i < 3 )$wp_roles->add_cap( $rolename, $ar_sared_access[ $i ] ); else 
					if ( $rolename == 'contributor' && $i < 2 )$wp_roles->add_cap( $rolename, $ar_sared_access[ $i ] ); else 
					if ( $rolename == 'subscriber' && $i < 1 )$wp_roles->add_cap( $rolename, $ar_sared_access[ $i ] ); else
					if ( $i < 1 )$wp_roles->add_cap( $rolename, $ar_sared_access[ $i ] ); 
					
				}
			}
		add_option("kdisk_notlogin_share_read",1,false);
		add_option("kdisk_notlogin_share_write",0,false);
		/////		
		global $wpdb;
		$id_main_post = self::insert_page('KDisk','kdisk',0);
		for( $i = 1; $i < count(self::$m_pages); $i++ ) 
		{
			$page = self::$m_pages[ $i ];
			self::insert_page( $page[0], $page[1], $id_main_post );
		}
	}
	public static function insert_page($new_page_title, $post_name, $post_parent )
	{
    	$new_page_content = "<!-- wp:shortcode -->[kdisk-show]<!-- /wp:shortcode -->";
	    $page_check = get_page_by_title($new_page_title);
    	$new_page = array(
	        'post_type' => 'page',
    	    'post_title' => $new_page_title,
        	'post_content' => $new_page_content,
	        'post_status' => 'publish',
    	    'post_author' => 1,
			'post_name' => $post_name,
			'post_parent' => $post_parent,
			);
	   	if( ! isset($page_check->ID) )
		{
    	   	$new_page_id = wp_insert_post( $new_page );
   	        update_post_meta($new_page_id, '_wp_page_template', $new_page_template);
		}else
		{
			$new_page_id = $page_check->ID;
			$page_check->post_name = $post_name;				
			$page_check->post_status = 'publish';
			wp_update_post($page_check, true);
		}
		return $new_page_id ;
	}
	//Деактавация плагина
	public static function plugin_deactivation()
	{
		
	}
	public static function read_options()
	{
		$o = get_option( KDISK_OPT );
		if ( isset($o['kr_dir_users_files']) )
		{
			self::$dir_users_files = $o['kr_dir_users_files'];
		}else
		{
			self::$dir_users_files = "kvfiles";
		}
		if ( isset($o['kdisk_mydir_activ']) )
		{
			self::$disk_name = $o['kdisk_mydir_activ'];
		}else
		{
			self::$disk_name = "kdisk";
		}
		self::$notlogin_share_read = get_option("kdisk_notlogin_share_read");
		self::$notlogin_share_write = get_option("kdisk_notlogin_share_write");
		self::$kdisk_space = get_option("kdisk_space");
		if (!is_array(self::$kdisk_space))
		self::$kdisk_space = array();
		if(!isset(self::$kdisk_space['kv_min_free_space']))
		{
			self::$kdisk_space['kv_min_free_space'] = 20;
		}
		if(!isset(self::$kdisk_space['kv_start_space']))
		{
			self::$kdisk_space['kv_start_space'] = 10;
		}
		if(!isset(self::$kdisk_space['kv_upload_type']))
		{
			self::$kdisk_space['kv_upload_type'] = 1;
		}
		if(!isset(self::$kdisk_space['kv_upload_xhr_size']))
		{
			self::$kdisk_space['kv_upload_xhr_size'] = 10;
		}
		
		self::$kdisk_statistics = get_option("kdisk_statistics");
		if (!is_array(self::$kdisk_statistics))
		self::$kdisk_statistics = array();
		if(!isset(self::$kdisk_statistics['kd_statistics_on']))
		{
			self::$kdisk_statistics['kd_statistics_on'] = "off";
		}
		
		
	}
	public static function option_tabs( $current )
	{
		$tabs = array( 'dirs' => esc_html(__('Directories',KDISK_PLG)), 'access' => esc_html(__('Access',KDISK_PLG)), 'limits' => esc_html(__('Limits',KDISK_PLG)), 'slugs' => esc_html(__('Service',KDISK_PLG)), 'statistics' => esc_html(__('Statistics',KDISK_PLG)));
		echo '<div class="nav-tab-wrapper" style="margin-bottom: 10px;">';
	    foreach( $tabs as $tab => $name ){
		    $class = ( $tab == $current ) ? ' nav-tab-active' : '';
		    echo "<a class='nav-tab$class' href='?page=kdisk-setting&curtab=" . esc_html($tab) ."'>$name</a>";
	    }
	    echo '</div>';
	}
	static function HumanSize($bytes)
	{
	  	$type=array("b", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB");
	  	$index=0;
	  	while($bytes>=1024)
	  	{
    		$bytes/=1024;
	    	$index++;
	  	}
		if ( !$bytes ) return "0";
		return round($bytes) . " " . $type[$index];
	}
	
	public static function delete_dir( $dir )
	{
		if ( is_file( $dir ) && filemtime($dir) > time() - 60 * 60 * 24){
			return;
		}
		if (is_file( $dir ))
		{
			
			if(strstr($dir,"temporary") || strstr($dir,"alltrash"))unlink( $dir );
			

		}elseif (is_dir( $dir ))
		{	
			$filelist = array();
			if($handle = opendir( $dir )){
				while($entry = readdir( $handle )){
					if ( $entry == "." || $entry == "..")continue;
					$tmp = $dir . "/" . $entry;
					
	            	if (is_dir( $tmp )) {
						self::delete_dir( $tmp );
					} elseif (is_file( $tmp )) {
						self::delete_dir( $tmp );
					}
	    	    }
				closedir( $handle );
				@rmdir( $dir );
			}
		
		}

		return ;


	}
	//Страница настроек
	public static function option_page(){

	?><div class="wrap">
		<h2><? esc_html_e('KDisk Settings',KDISK_PLG);?></h2>
        <?php if ( isset ( $_GET['curtab'] ) ) self::option_tabs( $_GET['curtab'] ); else self::option_tabs('dirs'); 
		$curtab = "dirs";
        if ( isset($_GET['curtab']))$curtab = $_GET['curtab'];
		
		switch( $curtab )
		{ 
			case 'statistics': 
			{
				self::read_options();
				echo '<form method="post" enctype="multipart/form-data" action="options.php">';
				settings_fields( 'kdisk_statistics' );
				do_settings_sections( 'kdisk_statistics' );
				submit_button();
				echo '</form>';
				
			}
			break;
			
			case 'slugs': 
			{
				self::read_options();
				include( __DIR__ . "/../templates/tem-mydisk.php" );
//				include __DIR__ . "/../mydisk/include/class-kdisk-folders.php";
	//			include __DIR__ . "/../mydisk/include/kdisk-function.php";
				
				$Folders = new KDisk_Folders(); 
				$Folders->set_folders( array(
									  'disk' 		=> self::$disk_name,
									  'users_files' => self::$dir_users_files,
									 ));
									 
				echo '<table class="table-slug">';					 
				$dir = $Folders->get_full_dir_temporary();
				$url = $_SERVER['REQUEST_URI'];
				if( isset($_GET['temporary']) && $_GET['temporary'] == 'clear' )
				{
					self::delete_dir( $dir ); 
					///////
					global $Kv_MyDisk;
					if ( isset($Kv_MyDisk) )
					{
					}else
					{
					  		//include( __DIR__ . "/../templates/tem-mydisk.php" );
					}
	
					$Kv_MyDisk->clear_lost_files();
					////////
					
				}else
				{
					$url .= "&temporary=clear";
				}
				echo "<tr><td>" . esc_html(__('Temporary files',KDISK_PLG)) . "<br>".esc_html($dir)."</td><td>" . self::HumanSize( KDisk_SizeFileOrDir( $dir ) ).'</td><td><a href="' . esc_url($url) . '">' . esc_html(__('Delete files created earlier than one day.',KDISK_PLG)) . '</a></td></tr>';
				
				$dir = $Folders->get_full_dir_alltrash();
				
				$url = $_SERVER['REQUEST_URI'];
				if( isset($_GET['alltrash']) && $_GET['alltrash'] == 'clear' )
				{
					self::delete_dir( $dir ); 
					
				}else
				{
					$url .= "&alltrash=clear";
				}
				echo "<tr><td>" . esc_html(__('Global trash',KDISK_PLG)) ."<br>".esc_html($dir)."</td><td>" . self::HumanSize( KDisk_SizeFileOrDir( $dir ) ).'</td><td><a href="' .esc_url($url) . '">' . esc_html(__('Delete files created earlier than one day.',KDISK_PLG)) . '</a></td></tr>';
				echo "</table>";
				
//				echo ini_get("upload_max_filesize")."<br>".ini_get("post_max_size")."<br>".ini_get("memory_limit");
				

			}
			break;
			case 'limits':
				self::read_options();
				
				$dir = $_SERVER['DOCUMENT_ROOT'] . "/" . self::$dir_users_files;
				$space_total = disk_total_space( $dir );
				$space_free = disk_free_space( $dir );
				
				$spaces = array( round($space_total / (1024*1024)) => round($space_free / (1024*1024)) );
				
				
				$ar_files = scandir($dir."/.");
				for($i = 0; $i < count($ar_files); $i++)
				{
					$dd = $dir.  "/" . $ar_files[$i];// . "/.";
					if (is_dir( $dd ))
					{
						$dd_space_total = round( disk_total_space($dd) / (1024*1024));
						$dd_space_free = round( disk_free_space($dd) / (1024*1024));
						
						if ( isset($spaces[$dd_space_total]) && $spaces[$dd_space_total] == $dd_space_free )
						{
						}else
						{
							$spaces[$dd_space_total] = $dd_space_free;
						}
					}
				}
				foreach ($spaces as $key => $value)
				{
					echo  "Свободно " . self::HumanSize($value * (1024*1024)) . " из ". self::HumanSize($key * (1024*1024)) . "<br>";
				}
				echo '<form method="post" enctype="multipart/form-data" action="options.php">';
				settings_fields( 'kdisk_space' );
				do_settings_sections( 'kdisk_space' );
				submit_button();
				echo '</form>';
				
			break;		
			case 'access':
			{
				self::read_options();
				$ar_sared_access = self::$ar_sared_access;
				$wp_roles = wp_roles();
				$editable_roles = $wp_roles->roles;
				?><form class="kdisk-form-access" method="post" enctype="multipart/form-data" action="options.php"><?php settings_fields( 'kdisk_shared' ); ?>
                <div class="kdisk-form-shared-prop" ><h2><? esc_html_e('Shared folder',KDISK_PLG);?></h2><table><thead><tr><th><? esc_html_e('Role',KDISK_PLG);?></th><th><? esc_html_e('Read',KDISK_PLG);?></th><th><? esc_html_e('Write',KDISK_PLG);?></th><th><? esc_html_e('Delete',KDISK_PLG);?></th><th><? esc_html_e('Trash',KDISK_PLG);?></th></tr></thead><tbody>
                <?php
				foreach ( $editable_roles as $role => $details ) {// $role 
				$name = translate_user_role( $details['name'] );
				?>
					<tr>
					<td><?php echo esc_html($name);?></td> 
                    <?php for( $i = 0; $i < count( $ar_sared_access ); $i++){ ?>
                    <td><input name="<?php echo esc_html($ar_sared_access[$i] . $role) ;?>" type="checkbox" <? if ( isset($details['capabilities'][$ar_sared_access[$i]])) echo "checked";?>/></td>
                    <?php } ?>
                    <?php
					echo '</tr>';
				}
				echo '<tr><td>Незарегистрированный пользователь</td><td><input name="kdisk_notlogin_share_read" type="checkbox" ' . esc_html( self::$notlogin_share_read ? 'checked' : '' ).  ' /></td><td><input name="kdisk_notlogin_share_write" type="checkbox" ' . esc_html( self::$notlogin_share_write ? 'checked' : '' ).  ' /></td></tr></tbody></table></div>';
				submit_button();
				?></form><?php
			}
			break;
			default:
			case "dirs":
			{
				?>
				<form method="post" enctype="multipart/form-data" action="options.php">
				<?php 
					settings_fields( KDISK_OPT ); // название настроек
					do_settings_sections( KDISK_OPT );
				?>
				<p class="submit">  
				<input type="submit" class="button-primary" value="<?php esc_html_e('Save Changes') ?>" /> 
                
				</p>
			</form>
            <?php
            }
            break;
		} ?>
	</div><?php
	}
	
	static function option_settings() {
		$kv_page = KDISK_OPT;

		register_setting( KDISK_OPT, KDISK_OPT, 0 ); 
		
		register_setting( 'kdisk_shared', 'kdisk_shared' , array('sanitize_callback' => array('KDisk_WP', 'validate_settings') ));
		register_setting( 'kdisk_statistics', 'kdisk_statistics' , array('sanitize_callback' => array('KDisk_WP', 'validate_statictics') ));
		
 
		add_settings_section( 'kv_mydisk_section_dir', __('Disk folders and URL',KDISK_PLG), '', $kv_page );
 
		$params = array(
			'type'      => 'text',
			'id'        => 'kdisk_mydir_activ',
			'desc'      => __('URL disk name. If you change this name, you must also change the `URL shortcut` on the` KDisk` page.',KDISK_PLG),
		);
		add_settings_field( 'kdisk_mydir_activ', __('Disk url',KDISK_PLG), array('KDisk_WP', 'display_settings'), $kv_page, 'kv_mydisk_section_dir', $params );
	
		$params = array(
			'type'      => 'text',
			'id'        => 'kv_mydir_users_files',
			'desc'      => __('The name of the directories where user files are stored. (Located in the root of the site)',KDISK_PLG),
		);
		add_settings_field( 'krdisk_dir_users_files', __('File folder',KDISK_PLG), array('KDisk_WP', 'display_settings'), $kv_page, 'kv_mydisk_section_dir', $params );
		

		register_setting( 'kdisk_space', 'kdisk_space' , 0 ); 
		add_settings_section( 'kdisk_space', __('Limitations',KDISK_PLG), '', 'kdisk_space' );
		$params = array(
			'type'      => 'text',
			'id'        => 'kv_min_free_space',
			'desc'      => __('Do not download files if free space is less than this value.',KDISK_PLG),
		);
		add_settings_field( 'kv_min_free_space', __('Minimum size',KDISK_PLG), array('KDisk_WP', 'display_limit_settings'), 'kdisk_space', 'kdisk_space', $params );
		
		//add_settings_section( 'kdisk_space', 'Ограничения', '', 'kdisk_space' );
		$params = array(
			'type'      => 'text',
			'id'        => 'kv_start_space',
			'desc'      => __('Initial size of available space for a new user',KDISK_PLG),
		);
		add_settings_field( 'kv_start_space', __('Initial size',KDISK_PLG), array('KDisk_WP', 'display_limit_settings'), 'kdisk_space', 'kdisk_space', $params );
		
		$params = array(
			'type'      => 'radio',
			'id'        => 'kv_upload_type',
			'desc'      => __('File upload method',KDISK_PLG),
		);
		add_settings_field( 'kv_upload_type', __('File upload method',KDISK_PLG), array('KDisk_WP', 'display_limit_settings'), 'kdisk_space', 'kdisk_space', $params );
		
		register_setting( 'kdisk_statistics', 'kdisk_statistics' , 0 );
		add_settings_section( 'kdisk_statistics', __('Views and downloads statistics',KDISK_PLG), '', 'kdisk_statistics' );
		$params = array(
			'type'      => 'checkbox',
			'id'        => 'kd_statistics_on',
			'desc'      => __('Views and downloads statistics',KDISK_PLG),
		);
		add_settings_field( 'kd_statistics_on', __('Enabled',KDISK_PLG), array('KDisk_WP', 'display_statistics'), 'kdisk_statistics', 'kdisk_statistics', $params );
		
 
	}
	static function display_statistics($args) {
		self::read_options();
		$o = get_option( 'kdisk_statistics' );
		switch( $args['id'] )
		{
			case 'kd_statistics_on':
			{
				//echo self::$kdisk_statistics['kd_statistics_on'];
				$checked = (self::$kdisk_statistics['kd_statistics_on'] == 'on') ? " checked='checked'" :  '';  
				echo  '<input type="checkbox" id="'. esc_html($args['id']) . '" name="kdisk_statistics[' . esc_html($args['id']) . ']" ' . $checked. ' />';
			}
			break;
		}
	}
	static function display_limit_settings($args) {

		self::read_options();
		$o = get_option( 'kdisk_space' );
		
		switch( $args['id'] )
		{
			case 'kv_min_free_space':
				if ( ! isset($o[$args['id']]) || ! $o[$args['id']] )  $o[$args['id']] = self::$kdisk_space['kv_min_free_space'];
				echo  ('<input  type="number" id="'. esc_html($args['id']) . '" name="kdisk_space[' . esc_html($args['id']) . ']" value="' . esc_html($o[$args['id']]) . '" /> GB'); 
				echo ($args['desc'] != '') ? '<br /><span class="description">' . esc_html($args['desc']) . '</span>' : '';  
			break;
			case 'kv_start_space':
				if ( ! isset($o[$args['id']]) || ! $o[$args['id']] )  $o[$args['id']] = self::$kdisk_space['kv_start_space'];;
				echo '<input  type="number" id="'. esc_html($args['id']) . '" name="kdisk_space[' . esc_html($args['id']) . ']" value="' . esc_html($o[$args['id']]) . '" /> GB'; 
				echo ($args['desc'] != '') ? '<br /><span class="description">' . esc_html($args['desc']) . '</span>' : '';  
			break;
			case 'kv_upload_type':
				if ( ! isset($o[$args['id']]) )  $o[$args['id']] = 1;
				if ( ! isset($o['kv_upload_xhr_size'])) $o['kv_upload_xhr_size'] = 10;
				echo '<p><input name="kdisk_space[' . esc_html($args['id']) . ']" type="radio" value="0" ' . (( $o[$args['id']] == 0 ) ? "checked":"") .' > POST</p>';
				echo '<p><input name="kdisk_space[' . esc_html($args['id']) . ']" type="radio" value="1" ' . (( $o[$args['id']] == 1 ) ? "checked":"") .'> «XHR» , ' . esc_html(__('block size',KDISK_PLG)) . '<input  type="number" name="kdisk_space[kv_upload_xhr_size]" value="' . esc_html($o['kv_upload_xhr_size']) . '"/> М' . '</p>';
				
			break;
		}
	}
	static function display_settings($args) {
		extract( $args );
 
		$option_name = KDISK_OPT;
 
		self::read_options();
		$o = get_option( $option_name );
		switch( $id )
		{
			case 'kdisk_mydir_activ':
			{
				if (!isset( $o[$id] ))$o[$id] = self::$disk_name;
			}
			break;
			case 'kv_mydir_users_files':
			{
				if (!isset( $o[$id] ))$o[$id] = self::$dir_users_files;
			}
			break;
		}
	switch ( $type ) {  
		case 'text':  
			$o[$id] = esc_attr( stripslashes($o[$id]) );
			echo "<input class='regular-text' type='text' id='$id' name='" . esc_html($option_name) . "[$id]' value='$o[$id]' />"; 
			switch ($id )
			{
				case 'kdisk_mydir_activ':
				{
					echo '&nbsp;&nbsp;<a href="/' . $o[$id] . '">' . site_url( $o[$id] ) . '</a>';
				}
				break;
				case 'kv_mydir_users_files':
				{
					echo '&nbsp;&nbsp;' . get_home_path() . $o[$id] . '/';
					
				}
				break;
			}
			echo ($desc != '') ? "<br /><span class='description'>" . esc_html($desc) . "</span>" : "";  
		break;
		case 'textarea':  
			$o[$id] = esc_attr( stripslashes($o[$id]) );
			echo "<textarea class='code large-text' cols='50' rows='10' type='text' id='" . esc_html($id) ."' name='" . esc_html($option_name) . "[$id]'>$o[$id]</textarea>";  
			echo ($desc != '') ? "<br /><span class='description'>". esc_html($desc) . "</span>" : "";  
		break;
		case 'checkbox':
			$checked = ($o[$id] == 'on') ? " checked='checked'" :  '';  
			echo "<label><input type='checkbox' id='$id' name='" . esc_html($option_name) . "[$id]' $checked /> ";  
			echo ($desc != '') ? esc_html($desc) : "";
			echo "</label>";  
		break;
		case 'select':
			echo '<select id="' . esc_html($id) .'" name="' . esc_html($option_name) . '[$id]">';
			foreach( $vals as $v => $l ){
				$selected = ( $o[$id] == $v) ? "selected='selected'" : '';  
				echo "<option value='" . esc_html($v) . "' " . esc_html($selected) . ">" . esc_html($l) ."</option>";
			}
			echo ($desc != '') ? esc_html( $desc ) : "";
			echo "</select>";  
		break;
		case 'radio':
			echo "<fieldset>";
			foreach($vals as $v=>$l){
				$checked = ($o[$id] == $v) ? "checked='checked'" : '';  
				echo "<label><input type='radio' name='" . esc_html($option_name) . "[$id]' value='".esc_html($v)."' $checked />" . esc_html($l) . "</label><br />";
			}
			echo "</fieldset>";  
		break; 
	}
		
	}
	static function validate_statictics($input) {
		
		{
			self::read_options();
			$name_file_th = ABSPATH . self::$dir_users_files . "/.htaccess";
			$fi = @fopen($name_file_th,"r");
			$enable = false;
			$new_ht = "";
			if ( $fi )
			{
				 while (($str = fgets($fi, 4096)) !== false) {
			        if(strstr($str,"class-kdisk-stat.php"))
					{
						$enable = true;	
						if (isset($input['kd_statistics_on']))
						{
							if($str[0]=="#")
							{
								$str=substr($str,1);
							}
						}else
						{
							if($str[0]!="#")
							{
								$str="#".$str;
							}
						}
					}
					$new_ht .= $str ;
			    }
				if (!feof($fi)) {
					exit;
				}
				fclose($fi);
			}
			if ( !$enable && isset($input['kd_statistics_on']))
			{
				$new_ht .= "\n<IfModule mod_rewrite.c>\nRewriteEngine on\nRewriteCond %{REQUEST_FILENAME} -f\nRewriteRule .*$ /wp-content/plugins/" . KDISK_PLG . "/mydisk/include/class-kdisk-stat.php [L]\n</IfModule>\n";
				$enable = true;
			}
			
			if ( $enable )
			{
				$fi = @fopen($name_file_th,"w");
				fwrite($fi, $new_ht);
				fclose($fi);
			}
		}
		return $input;
	}
	static function validate_settings($input) {

		
		$ar_sared_access = self::$ar_sared_access;
		$wp_roles = wp_roles();
		$editable_roles = $wp_roles->roles;
		
		
		foreach ( $editable_roles as $rolename => $details ) {
				$role = get_role( $rolename );
				for( $i = 0; $i < count( $ar_sared_access ); $i++)
				{
					$wp_roles->remove_cap( $rolename , $ar_sared_access[ $i ]);
				}
			}
		foreach ( $_POST as $key => $value ) {
			
			for( $i = 0; $i < count( $ar_sared_access ); $i++)
			{
				if( 0 === strpos( $key, $ar_sared_access[ $i ] ))
				{
					$name_role = substr( $key, strlen( $ar_sared_access[ $i ] ));
					$role = get_role( $name_role );
					$role->add_cap( $ar_sared_access[ $i ] ); 	
				}
			}
		}
		if( isset($_POST['kdisk_notlogin_share_read'])){
			update_option( 'kdisk_notlogin_share_read', 1, false );
		}else
		{
			update_option( 'kdisk_notlogin_share_read', 0, false );
		}
		if( isset($_POST['kdisk_notlogin_share_write'])){
			update_option( 'kdisk_notlogin_share_write', 1, false );
		}else
		{
			update_option( 'kdisk_notlogin_share_write', 0, false );
		}

		
		return $input;
	}

	static function delete_options()
	{
		//Очищаем параметры из ролей
		$ar_sared_access = self::$ar_sared_access;
		$wp_roles = wp_roles();
		$editable_roles = $wp_roles->roles;
		
		
		foreach ( $editable_roles as $rolename => $details ) {
			$role = get_role( $rolename );
			for( $i = 0; $i < count( $ar_sared_access ); $i++)
			{
				$wp_roles->remove_cap( $rolename , $ar_sared_access[ $i ]);
			}
		}
		//удаляем настройки
		delete_option('kdisk_notlogin_share_read');
		delete_option('kdisk_notlogin_share_write');
		delete_option(KDISK_OPT);
		delete_option('kdisk_shared');
		delete_option('kdisk_space');
		delete_option('kdisk_statistics');
		
		
		
	}
	static function delete_pages()
	{
		$page_title = array_reverse(self::$m_pages);
		foreach ($page_title as $title) 
		{
		    $page_check = get_page_by_title( $title[0] );
			if ( $page_check )
			{
				wp_trash_post( $page_check->ID );
			}
		}
	}

}