<?
class KDisk_BaseMY
{
	public $my=0,$last_id=0,$last_errno = 0,$show_error = 1,$affected_rows = 0; 
	public $m_adr,$m_user,$m_pass,$m_db;
	public function Connect($adr,$user,$pass,$db)
	{
		$this->m_adr = $adr;
		$this->m_user = $user;
		$this->m_pass = $pass;
		$this->m_db = $db;
		
		$my=mysqli_connect($adr,$user,$pass);
		if ($my)
		{
			mysqli_select_db($my,$db);
			mysqli_set_charset($my, "utf8");
			
		}	
		$this->my=$my;
		$this->Query("SET time_zone = '+00:00'");
	}
	public function ShowError( $mode )
	{
		$this->show_error = $mode;	
	}
	public function Query($mycmd)
	{
		$this->last_errno = 0;
		$this->last_error = "";
		$this->affected_rows = 0;
		$my=$this->my;
		
		if(!$my)return;
		$last_id=0; 
		$res=mysqli_multi_query($my,$mycmd);
//		$res=mysqli_query($my,$mycmd, MYSQLI_USE_RESULT);
		
		$Dp1=array();
		if(!$res)
		{
			$err=mysqli_errno($my);
			$this->last_errno = $err;
			$this->last_error = mysqli_error($my);

			global $KDisk_task;
			if ( isset($KDisk_task) )
			{
				$KDisk_task->kv_write_log( $this->last_error );
			}
			
			switch($err)
			{
				case 1194: //is marked as crashed and should be repaired
				{
			//		$recmd = "REPAIR TABLE ";
				}
//				break;
				case 1062:
				
				break;
				default:
				if ( $this->show_error ){
					
				}
				break;
			}
			return 0;
		}
	//	$last_id = mysql_insert_id( $my );
		if ($result = mysqli_use_result($my))
		{
			while ($row = mysqli_fetch_assoc($result))
			{
				$Dp1[count($Dp1)]=$row;
			}
			mysqli_free_result($result);
		}else
		{
			$this->affected_rows = mysqli_affected_rows($my);
		}
	
		return $Dp1;
	}
	public function QueryRow($mycmd)
	{
		$my=$this->my;
		
		if(!$my)return;
		$last_id=0; 
		$res=mysqli_multi_query($my,$mycmd);
		if(!$res)
		{
			$err=mysqli_errno($my);
			switch($err)
			{
				case 1062:
				break;
				default:
				echo esc_html("code: ".mysqli_errno($my)." ".mysqli_error($my)."<br>");
				break;
			}
			return 0;
		}
		//$last_id = mysql_insert_id( $my );
		$Dp1=array();
		if ($result = mysqli_use_result($my))
		{
			while ($row = mysqli_fetch_row($result))
			{
				$Dp1[count($Dp1)]=$row;
			}
			mysqli_free_result($result);
		}
	
		return $Dp1;
	}
	public function GetInsertId()
	{
		return mysqli_insert_id($this->my);
	}
	
}

?>