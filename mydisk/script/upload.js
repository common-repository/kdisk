// JavaScript Document

var kvButSavStyle = "";
function KVCerateHReq() 
{ 
	var hr; 
	if (navigator.appName == "Microsoft Internet Explorer") 
	{ 
		hr = new ActiveXObject("Microsoft.XMLHTTP"); 
	} else 
	{ 
		hr = new XMLHttpRequest(); 
	} 
	return hr; 
}
function KVSendCmdRetJson(cmd, callback)  
{
	var rs = KVCerateHReq(); 
	rs.open("GET", g_krurlcmd + "?" + cmd); 
	rs.responseType = "json";
	rs.onreadystatechange = function () 
	{ 
		if(rs.readyState===4)
		{
			
			var json = 0;
			switch ( rs.responseType )
			{
				case 'json':
					json = rs.response;
				break;
				default:
				try{
					json = JSON.parse( rs.response );
				}catch (err) {
					alert(err);
				}
				
				break;
			}
			callback(rs,json); 
		}
	}; 
	rs.send(null);
	
}
function KVGetProgress() 
{ 
	KVSendCmdRetJson("ko_progress=1", handleResponse);
} 
function handleResponse(rs) 
{ 
	var response; 
	if (rs.readyState == 4) 
	{ 
		response = rs.response;
		g_cursizesend = rs.response.current;
		if ( !response ){KVStartProgress();return;}
		var ct = document.getElementById("kv_send");
		var ctS = document.getElementById("kv_status");
		ctS.innerHTML = response.procent + "%";
		
		txt_progres = "";
		var sec = ((new Date()).getTime() - g_time_start_send_file) / 1000;
		var speed = g_cursizesend * 8 / (1024 * 1024)  / sec;		
		txt_progres += " " + speed.toFixed(1) + " Mbit\n";
		ctS.setAttribute("title", txt_progres);
		
		 
		 
		if (response.procent < 100)
		{ 
			KVStartProgress();
		} else 
		{ 
			ctS.style.display = "none";
			ct.style = kvButSavStyle ;
		} 
	} 
} 
function KVStartProgress() 
{ 
	setTimeout("KVGetProgress()", 1000);
} 

var g_klistfiles = 0;
var g_cursendfile = 0;
var g_allsizesend = 0;
var g_cursizesend = 0;
var g_size_part_send = 10*1024*1024;
var g_time_start_send_file = 0;

function KVSendPartFile(cmd,obj,clb,fprg)
{
	var r=KVCerateHReq();
	r.open("POST",g_krurlcmd + "?" + cmd,true);
	var boundary="--GFWF"+String(Math.random()).slice(2)+"GFWF";
	r.setRequestHeader("Content-type", "multipart/form-data;boundary="+boundary);
	r.responseType = "json";
	r.onreadystatechange=function()
	{
		if(this.readyState===4)
		{
			if(clb)clb(this);
		}
	}
	r.onerror = function(){
//		alert("Error connection");
		if(clb)clb(0);
		this.abort();
	}
	
	var prm1='--'+boundary+'\r\n'+'Content-Disposition: form-data; name="numfile"\r\n\r\n'+obj.numfile+'\r\n';
	prm1+='--'+boundary+'\r\n'+'Content-Disposition: form-data; name="pos"\r\n\r\n'+obj.pos+'\r\n';
	prm1+='--'+boundary+'\r\n'+'Content-Disposition: form-data; name="length"\r\n\r\n'+obj.length+'\r\n';
	if( obj.skey )prm1+='--'+boundary+'\r\n'+'Content-Disposition: form-data; name="skey"\r\n\r\n'+obj.skey+'\r\n';
	if( obj.namefile )prm1+='--'+boundary+'\r\n'+'Content-Disposition: form-data; name="namefile"\r\n\r\n'+obj.namefile+'\r\n';
	if( obj.kv_user_dir )prm1+='--'+boundary+'\r\n'+'Content-Disposition: form-data; name="kv_user_dir"\r\n\r\n'+obj.kv_user_dir+'\r\n';
	if( obj.crc32 )prm1+='--'+boundary+'\r\n'+'Content-Disposition: form-data; name="crc32"\r\n\r\n'+obj.crc32+'\r\n';
	
	
	

	var prm="--"+boundary+"\r\n";
	prm+='Content-Disposition: form-data; name="file"; filename="part"\r\n';
	prm+='Content-Type: text/html\r\n';
	prm+='\r\n';
	var pom="\r\n--"+boundary+"--\r\n";
	var bb=new Blob([prm1,prm,obj.masfile,pom]);
	if(IsDef(fprg))r.upload.onprogress=fprg;

	r.send(bb);
	return 0;
}

function SendCurrentPart()
{
	
	var fil = g_klistfiles[g_cursendfile.numfile];
//	console.log(fil);
	var reader = new FileReader();
	
	reader.onloadend = function(evt) 
	{
		if (evt.target.readyState == FileReader.DONE) 
 		{
			g_cursendfile.length=evt.target.result.byteLength;
			
			g_cursendfile.masfile=evt.target.result;
			
			g_cursendfile.classCrc32.crc32(evt.target.result);
			
			KVSendPartFile("ko_set_part",g_cursendfile,function(r){
				if (!r)
				{
					g_cursendfile.classCrc32.back_crc();
					window.setTimeout(SendCurrentPart,5000);
					return;
				}
				if(r.status == 200 && r.response)
				{
					if(r.response.err == 0)
					{
						g_cursendfile.skey = r.response.skey;
						g_cursendfile.pos+=g_cursendfile.length;
						g_cursizesend+=g_cursendfile.length;
						var progress=100 * g_cursizesend / g_allsizesend ;
						var ct = document.getElementById("kv_send");
						var ctS = document.getElementById("kv_status");
						ctS.innerHTML = ctS.innerHTML = '<div class="kd-txt-progres-ipload">'+Math.round(progress) + "%"+'</div>';
						
						var sizeend = 0;
						var txt_progres = "";
						for(var i=0;i<g_klistfiles.length;i++)	
						{
							if ( i < g_cursendfile.numfile )
							{
								txt_progres+=g_klistfiles[i].name +" : 100%\n";
								sizeend += g_klistfiles[i].size;
							}else
							if ( i == g_cursendfile.numfile )
							{
								var progress_cur_file = (g_cursizesend - sizeend) * 100 / g_klistfiles[i].size;
								txt_progres+=g_klistfiles[i].name + " : " + progress_cur_file.toFixed(2) + "%";		
								var sec = ((new Date()).getTime() - g_time_start_send_file) / 1000;
								var speed = g_cursizesend * 8 / (1024 * 1024)  / sec;		
								txt_progres+=" " + speed.toFixed(1) + " Mbit\n";
							}else
							{
								txt_progres+=g_klistfiles[i].name +" : 0%\n";
							}
						}
						
						
						ctS.setAttribute("title", txt_progres);
						SendCurrentPart();
					}else
					{
						if ( r.response.err == 1 )
						{
							g_cursendfile.pos-=g_size_part_send;
							g_cursizesend-=g_size_part_send;
							if (g_cursendfile.pos<0)g_cursendfile.pos=0;
							if (g_cursizesend<0)g_cursizesend=0;
							SendCurrentPart();
							
						}
					}
				}else
				if(!r.response)
				{
					alert("Error upload!")
					g_cursendfile.pos-=g_size_part_send;
					g_cursizesend-=g_size_part_send;
					if (g_cursendfile.pos<0)g_cursendfile.pos=0;
					if (g_cursizesend<0)g_cursizesend=0;
					SendCurrentPart();
				}
			});
		}
	}
	var blob;
	if(fil.slice)
	{
		blob=fil.slice(g_cursendfile.pos, g_cursendfile.pos + g_size_part_send );
	}else
	{
		alert("err slice");
	}
	
	if (!blob.size)
	{

		g_cursendfile.crc32 = g_cursendfile.classCrc32.get_result().toString(16);
	
		g_cursendfile.kv_user_dir = document.getElementsByName('kv_user_dir')[0].value;
		g_cursendfile.namefile = fil.name;
		KVSendPartFile("ko_set_part",g_cursendfile,function(r){
				delete (g_cursendfile.namefile);
				delete (g_cursendfile.kv_user_dir);
				delete (g_cursendfile.crc32);
				g_cursendfile.classCrc32.m_crc = 0 ^ (-1);
				if(r.status == 200 && r.response)
				{
					if(r.response.err == 0)
					{
						g_cursendfile.numfile++;
						if(g_cursendfile.numfile >= g_klistfiles.length)
						{
							window.onbeforeunload = null;		
							window.location.reload();
							
						}else
						{
							g_cursendfile.pos=0;
							SendCurrentPart();
						}
					}else
					{
						alert(r.response.err + " " + r.response.errstr + " " + r.response.file_crc32 + " " + r.response.crc32);
					}
				}else
				{
				}
			});
		
	}else
	{
		reader.readAsArrayBuffer(blob);
	}
}
function KDSelFiles(evt)
{
	var files = evt.target.files;
	if (typeof(evt.dataTransfer)!='undefined')files = evt.dataTransfer.files;
	

	//console.log(files);
//	return ;
	
	g_allsizesend = 0;
	g_cursizesend = 0;
	
	for(var i=0;i<files.length;i++)
	{
		g_allsizesend+=files[i].size;
	}

	g_klistfiles = files;
	
	g_cursendfile = {
			numfile : 0,
			pos : 0,
			classCrc32 : new UpCrc32(), 
			//skey : "$$sdfrtdfget",
		};
	window.onbeforeunload = function() {  return false; };	
	
	var ct = document.getElementById("kv_send");
	var ctS = document.getElementById("kv_status");
	ctS.style.display = "inline";
	ctS.innerHTML = '<div class="kd-txt-progres-ipload">0%</div>';
	ct.style.display = "none";

	SendCurrentPart();
	return;	
}

function KVSelFiles(evt)
{
	if ( g_time_start_send_file ) return 0;
	g_time_start_send_file = (new Date()).getTime();
	if ( g_upload_type == 1)
	{
		KDSelFiles(evt);
		return;
	}
	var files = evt.target.files;
	if (typeof(evt.dataTransfer)!='undefined')files = evt.dataTransfer.files;
	
	var ct = document.getElementById("kv_send");
	kvButSavStyle = ct.style;
	var ctS = document.getElementById("kv_status");
	
/*	ctS.style.display = "table-cell";*/
	ctS.innerHTML = "0%";
	ct.style.display = "none";
	var rs = new XMLHttpRequest();
	var FD = new FormData( document.getElementById("KVFormUpdate") );
	rs.open( "POST", g_krurlcmd );
	rs.responseType = "json";
	rs.onreadystatechange = function () { 
		
		if (rs.readyState == 4) 
		{
			if( rs.status == 413){
				alert('Слишком большой размер загружаемого файла!');
				return;
			}
			window.onbeforeunload = null;		
			ret = rs.response;
			if ( ret.gourl )
			{
				window.location.href = ret.gourl;
			}
		}
	 }; 
	window.onbeforeunload = function() {  return false; };
	rs.send( FD );
	KVStartProgress();
}
function UpCrc32()
{
	
 	function makeCRCTableT(){
    	var c;
	    var crcTable = [];
    	for(var n =0; n < 256; n++){
        	c = n;
	        for(var k =0; k < 8; k++){
    	        c = ((c&1) ? (0xEDB88320 ^ (c >>> 1)) : (c >>> 1));
        	}
	        crcTable[n] = c;
    	}
	    return crcTable;
	}
	this.m_crc =  0 ^ (-1);
	this.m_back_crc = 0 ^ (-1);
	
	this.crc32 = function(ar) {
	this.m_back_crc = this.m_crc;
	var str = new Uint8Array(ar);
//    var crc = 0 ^ (-1);
	
    for (var i = 0; i < str.length; i++ ) {
        this.m_crc = (this.m_crc >>> 8) ^ this.m_tabe_crc32[(this.m_crc ^ str[i]) & 0xFF];
    }
//		alert( this.m_crc );
    //return (crc ^ (-1)) >>> 0;
	};
	this.get_result = function() { return (this.m_crc ^ (-1)) >>> 0; }
	this.back_crc = function() { this.m_crc = this.m_back_crc; }
	
	this.m_tabe_crc32 = makeCRCTableT();
	
	
}

/*function makeCRCTable(){
    var c;
    var crcTable = [];
    for(var n =0; n < 256; n++){
        c = n;
        for(var k =0; k < 8; k++){
            c = ((c&1) ? (0xEDB88320 ^ (c >>> 1)) : (c >>> 1));
        }
        crcTable[n] = c;
    }
    return crcTable;
}

function crc32(ar) {
	
	var str = new Uint8Array(ar);
    var crcTable = makeCRCTable();
    var crc = 0 ^ (-1);

    for (var i = 0; i < str.length; i++ ) {
        crc = (crc >>> 8) ^ crcTable[(crc ^ str[i]) & 0xFF];
    }

    return (crc ^ (-1)) >>> 0;
};*/
document.addEventListener("DOMContentLoaded", function () { document.getElementById("KVFormUpdate").onsubmit = KVStartProgress; if ( g_upload_xhr_size ) g_size_part_send=g_upload_xhr_size; } );
