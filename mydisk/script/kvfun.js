function KVDblClick( ct )
{
	
	switch( ct.tagName.toUpperCase() )
	{
		case "DIV":

			var href = ct.getAttribute("href");
			if ( !href )
			{
				href = ct.querySelector(".kv-file-href");
				if ( href )
				{
					href = href.getAttribute("href");
				}
			}
			if ( !href )
			{
				alert('Error getAttribute("href")');
				return;
			}

			if(event)event.stopPropagation();
			var ar = [href];


			AJSSendJson("/" + g_kv_url_mydisk + "/?kv_dblclick=1",ar,function(r,json){
					var ret = json;
					if ( ret.gourl )
					{

						window.location.href = ret.gourl; 
					}else
					{
						window.location.reload();
					}
					
				},0);
			
		break;
	}
	return false;
}
var g_kv_ct_pre_sel = 0;
var g_stat_touch = 0;
var g_stat_pos = 0;
var g_KvElem = new KV_Elements();
var g_cnti = null;
var g_json_pre_dwn_zip = 0;
function KROnTouchMove( ct )
{
	switch ( ct.id )
	{
		case "krburmenu":
		var cur_touch = { x: event.changedTouches[0].clientX, y: event.changedTouches[0].clientY };
		var dd=(g_stat_touch.x - cur_touch.x );
		var cx = ct.parentNode.offsetWidth;

		var dd = g_stat_pos - dd;
		if ( dd > -ct.offsetWidth + cx && dd < 0)
		{
			ct.style.left = dd+'px';
		}
		break;
	}
	
}
function KROnTouchStart( ct )
{
	switch ( ct.id )
	{
		case "krburmenu":
		g_stat_touch = { x: event.changedTouches[0].clientX, y: event.changedTouches[0].clientY };
		var x = parseInt(ct.style.left);
		if ( isNaN( x ) ) x = 0;
		g_stat_pos = x;
		
		break;
	}
}
function KVMakeArraySelectedFiles()
{
	var fil = document.querySelector(".kv-list-files");
	var fs = fil.querySelectorAll(".kv-file-sel");
	var len = fs.length;
	var ar = [];
	for( var i=0; i < len; i++)
	{
		var ct = fs[i]; 
	//	console.log(ct);
		href = ct.querySelector(".kv-file-href");
		if ( href )
		{
			href = href.getAttribute("href");
//			alert( href );
		}
				
		if ( !href )
		{
			alert('Error getAttribute("href")');
			return;
		}
		ar[ ar.length ] = encodeURIComponent( href );
	}
	return ar;
}
function get_progress_make_zip( tkey )
{
	AJSSendGetJson("/" + g_kv_url_mydisk + "/?kv_prog_make_zip&tkey="+tkey,function(r, json){
			if ( g_cnti )
			{
				 var ct = g_cnti.querySelectorAll('.progrss-zip');
				 if ( ct )
				 {
					 ct[0].innerHTML = g_json_pre_dwn_zip.size_txt + " -> " + json.size_txt ;
				 }
			}
			g_time_progress_zip = window.setTimeout(get_progress_make_zip, 1000, tkey);
	});
	
}
var g_prg_work = 0;
var g_timer_send_work = 0;
var g_time_progress_zip = 0;
function progress_make_work()
{
	var cts = document.querySelectorAll('div[kd-idtask]');
	var tasks= [];
	for( var i = 0; i < cts.length ;i++)
	{
		var ct = cts[i];
		tasks[tasks.length] = ct.getAttribute('kd-idtask');
	
	}
	var cmd = {
			tasksids : tasks,
	};
	clearTimeout(g_timer_send_work);
	g_timer_send_work = window.setTimeout(function(){
		g_timer_send_work = 0;
		g_prg_work.abort();
	}, 15000);
	g_prg_work = AJSSendJson("/" + g_kv_url_mydisk + "/?kv_prog_makework",cmd,function(r,json){
			var ret = json;
			if( json )
			for( var i=0; i < json.prg.length; i++)
			{
				var ct = document.querySelector('div[kd-idtask="' + json.prg[i].id + '"]');
				if ( ct && json.prg[i].prg < 100 ) 
				{
					ct.innerHTML= parseInt(json.prg[i].prg) + " % ";
					function gT(sec){var h = sec/3600 ^ 0 ; var m = (sec-h*3600)/60 ^ 0 ; var s = sec-h*3600-m*60 ;return((h<10?"0"+h:h)+":"+(m<10?"0"+m:m)+":"+(s<10?"0"+s:s));}
					ct.parentNode.setAttribute( "title" , gT(json.prg[i].time) + "\r" + gT(json.prg[i].remain) );					
					var prg0 = ct.nextElementSibling.firstElementChild.firstElementChild.firstElementChild;
					var prg1 = ct.nextElementSibling.firstElementChild.lastElementChild.firstElementChild;
					var deg0 = 180;
					var deg1 = 0;
					if ( json.prg[i].prg <= 50 )
					{
						deg0 = parseInt( 180 / 50 * json.prg[i].prg );
					}else
					{
						deg1 = parseInt( 180 / 50 * ( json.prg[i].prg - 50 ) );
					}
					prg0.style.transform = 'rotate(' + deg0 + 'deg)';
					prg1.style.transform = 'rotate(' + deg1 + 'deg)';
				}else
				{
					window.location.reload();
				}

			}
			
			window.setTimeout(progress_make_work, 5000);
	});	
}
function KVClickM( str )
{
	event.stopPropagation();
	switch ( str )
	{
		case 'kv_show_prop':
		{
//			var ar = KVMakeArraySelectedFiles();
		}
		break;
		case 'kd_combine_video_audio':
		{
			var ar = KVMakeArraySelectedFiles();
			var msg = new KV_Elements();
			msg.MsgBox(KDisk_Lang('Combine',1)+"?",function(ret)
			{
				if ( ret == 1)
				{
					AJSSendJson("/" + g_kv_url_mydisk + "/?kd_combine_video_audio",ar,function(r,json){
						var ret = json;
						if ( !ret.gourl )
						{
							alert("Ошибка");
						}else
						if ( ret.gourl )
						{	
							window.location.href = ret.gourl;
						}
					},0);
				return;		
				}
			});
		}
		break;
		case 'kd_take_audio':
		{
			var ar = KVMakeArraySelectedFiles();
			var msg = new KV_Elements();
			msg.MsgBox(KDisk_Lang('Take audio track?',1),function(ret)
			{
				if ( ret == 1)
				{
					AJSSendJson("/" + g_kv_url_mydisk + "/?kd_take_audio",ar,function(r,json){
						var ret = json;
						if ( !ret.gourl )
						{
							alert("Ошибка");
						}else
						if ( ret.gourl )
						{	
							window.location.href = ret.gourl;
						}
					},0);
				return;		
				}
			});
			
		}
		break;
		case 'kv_make_video':
		{
			var ar = KVMakeArraySelectedFiles();
			var msg = new KV_Elements();
			msg.MsgBox(KDisk_Lang('Convert video?',1),function(ret)
			{
				if ( ret == 1)
				{
					AJSSendJson("/" + g_kv_url_mydisk + "/?kv_make_video",ar,function(r,json){
						var ret = json;
						if ( !ret.gourl )
						{
							alert("Ошибка");
						}else
						if ( ret.gourl )
						{	
							window.location.href = ret.gourl;
						}
					},0);
				return;		
				}
			});
		}
		break;
	}
}
function KVClick( ct )
{
	event.stopPropagation();
	if ( ct === null)return;
	switch( ct.id )
	{	
		case "kv_mode_view":
		{
			var fs = document.querySelectorAll(".kv-panels");
			if ( fs && fs[0] && typeof(fs[0].classList) != 'undefined' )
			{
				fs[0].classList.remove( "kv-panels" );
				fs[0].classList.add( "kv-panels-greed" );
				var cookie_date = new Date ( );
				document.cookie = "kd_view_panel = kv-panels-greed;path=/;expires=" + (cookie_date.getTime() + 60*60);
			}else
			{
				fs = document.querySelectorAll(".kv-panels-greed")
				if ( fs && fs[0])
				{
					fs[0].classList.add( "kv-panels-greed-big" );
					fs[0].classList.remove( "kv-panels-greed" );
					var cookie_date = new Date ( );
					document.cookie = "kd_view_panel = kv-panels-greed-big;path=/;expires=" + (cookie_date.getTime() + 60*60);
					g_KDiskPage.loadKdImage(1);
					break;
				}
				
				fs = document.querySelectorAll(".kv-panels-greed-big")
				if ( fs )
				{
					fs[0].classList.add( "kv-panels" );
					fs[0].classList.remove( "kv-panels-greed-big" );
					var cookie_date = new Date ( );
					document.cookie = "kd_view_panel = kv-panels;path=/;expires=" + (cookie_date.getTime() + 60*60);
					break;
				}
				
				
			}
		}
		break;
		case "direct_link":
		{
			var href = ct.getAttribute("href");
			var ct = document.getElementById("f_dwn_load");
			ct.href = href;
			ct.click();
		}
		break;
		case "kv_clear_trash":
		{
			var fs = document.querySelectorAll(".kv-list-item-element-size");
			var len = fs.length;
			var sizeb = 0;
			for( var i=0; i < len; i++)
			{
				var kvsizef = fs[i].getAttribute("kvsizef");
				sizeb += parseInt(kvsizef);
			}
			
//			var ret = confirm("Очистить корзину размером "+KVMake_size_file(sizeb)+" навсегда?");
			var msg = new KV_Elements();
			msg.MsgBox(KDisk_Lang("Empty Trash Permanently?"),function(ret){
			
				if(!ret)return;	
				var ar = [];
				AJSSendJson("/" + g_kv_url_mydisk + "/?kv_clear_trash",ar,function(r,json){
					var ret = json;
					if ( !ret.gourl )
					{
						alert("Ошибка");
					}else
					if ( ret.gourl )
					{
							window.location.href = ret.gourl;
					}
				},0);
				
			},1);
		}
		break;
		
		case "kv_delete_trash":
		{
			
			var fs = document.querySelectorAll(".kv-file-sel");
			var len = fs.length;
			
			if( len > 50 )len = 50; 
			var isd = 0;
			if ( len == 1 )
			{
				if ( fs[0].classList.contains("kv-dir") )isd = 1;
			}
			//if ( len > 1 || isd )
			{
				var msg = new KV_Elements();
			msg.MsgBox(KDisk_Lang("Delete permanently?"),function(ret){
			
				if(!ret)return;	
				var ar = [];
				for( var i=0; i < len; i++)
				{
					var ct = fs[i]; 
					href = ct.querySelector(".kv-file-href");
					if ( href )
					{
						href = href.getAttribute("href");
						ct.classList.contains("kv-file");
						ct.classList.toggle("kv-file-sel");
					}
					
					if ( !href )
					{
						alert('Error getAttribute("href")');
						return;
					}
					ar[ ar.length ] = href;
				}
				AJSSendJson("/" + g_kv_url_mydisk + "/?kv_delete_trash",ar,function(r,json){
					var ret = json;
					if ( !ret.gourl )
					{
						alert("Ошибка");
					}else
					if ( ret.gourl )
					{
							window.location.href = ret.gourl;
					}
				},0);
			},1);
	//var ret = confirm(KDisk_Lang("Delete permanently?"));
//				if(!ret)return;
				
				
				
				return;
				
			}
		}
		break;
		case "kv_recover":
		{
			var sf = KVMakeArraySelectedFiles();
			if ( !sf.length )
			{
				return;
			}
			var ar = {
				kv_folder : g_kv_folder,
				files : sf,
			}
			
			var msg = new KV_Elements();
			msg.MsgBox(KDisk_Lang("Restore?"),function(ret){
				if(!ret)return;	
				AJSSendJson("/" + g_kv_url_mydisk + "/?kv_recover" ,ar,function(r,json){
					var ret = json;
					if ( !ret.gourl )
					{
						alert(KDisk_Lang("Error"));
					}else
					if ( ret.gourl )
					{
						window.location.href = ret.gourl;
					}
				},0);
			},1);
//			var ret = confirm(KDisk_Lang("Restore?"));
	//		if(!ret)return;
			
			return;
		}
		break;
		case "kv_trash":
		{
			var name = "kv_trash";
			ar = [name];
			AJSSendJson("/" + g_kv_url_mydisk + "/?kv_trash",ar,function(r,json){
					var ret = json;
					if ( ret.gourl )
					{
						window.location.href = ret.gourl; 
					}
					
				},0);
		}
		break;
		case "kv_download":
		{
			var fs = document.querySelectorAll(".kv-file-sel");
			var len = fs.length;
			if( len > 50 )len = 50; 
			var isd = 0;
			if ( len == 1 )
			{
				if ( fs[0].classList.contains("kv-dir") )
				{
					isd = 1;
				}else
				{
					if ( g_cnti ) document.body.removeChild(g_cnti);
					g_cnti = MsgBox("<center>" + KDisk_Lang('Downloading') + "</center>");
					g_cnti_id_timer_close = window.setTimeout(function(){clearTimeout(g_cnti_id_timer_close); if ( g_cnti )document.body.removeChild(g_cnti); g_cnti=null},2000);

				}
			}
			if ( len > 1 || isd )
			{
				var ar = KVMakeArraySelectedFiles();
				AJSSendJson("/" + g_kv_url_mydisk + "/?kv_pre_dwn_zip",ar,function(r,json){
		
					
				g_json_pre_dwn_zip = json;
				var ret = 0;
				if ( !json.tkey )
				{
					alert(json.txt_msg);
				}else
				{
//					ret = confirm( json.txt_msg );

					var msg = new KV_Elements();
					msg.MsgBox(json.txt_msg,function(ret){
						if(!ret)return;	
						
					
				g_cnti = MsgBox('<div style="position: relative;float: left;width: 100%;">' + json.txt_msg_dwnl + '</div><img src="' + g_kv_url_theme + '/imgs/line_loader.gif"><div class="progrss-zip"></div>',function( ret ){
				
				},0);
				

				g_time_progress_zip = window.setTimeout(get_progress_make_zip, 1, json.tkey);
				
				
				var cmd = {
					tkey : json.tkey,
					ar : ar,
				};
				
				AJSSendJson("/" + g_kv_url_mydisk + "/?kv_dwn_zip",cmd,function(r,json){
					var ret = json;
					
					if ( !ret.dwnurl )
					{
						alert("Ошибка");
					}else
					if ( ret.dwnurl )
						{
							var ct = document.getElementById("f_dwn_load");
							ct.href = ret.dwnurl;
							ct.click();
							clearTimeout(g_time_progress_zip);	g_time_progress_zip = 0;
							
							if ( g_cnti ) document.body.removeChild(g_cnti);
							g_cnti = MsgBox("<center>" + KDisk_Lang('Downloading') + "</center>");
							g_cnti_id_timer_close = window.setTimeout(function(){clearTimeout(g_cnti_id_timer_close); if ( g_cnti )document.body.removeChild(g_cnti); g_cnti=null},2000);

					
							
						
						}
					},0);
					},1);

				}
				
				
			//	if(!ret)return;
				
				});
				return;
			}
			
			var ar = KVMakeArraySelectedFiles();
			
			AJSSendJson("/" + g_kv_url_mydisk + "/?kv_download",ar,function(r, json){
				
					var ret = json;
					if ( !ret.dwnurl )
					{
						alert("Ошибка");
					}else
					if ( ret.dwnurl )
						{
							var ct = document.getElementById("f_dwn_load");
							ct.href = ret.dwnurl;
							ct.click();
						}
					},0);
				
				return;
		}
		break;
		case "kv_remove":
		{
			var ar = KVMakeArraySelectedFiles();
			var msg = new KV_Elements();
			msg.MsgBox(KDisk_Lang("Delete?"),function(ret)
			{
				if(!ret)return;
				AJSSendJson("/" + g_kv_url_mydisk + "/?kv_remove",ar,function(r,json){
				var ret = json;
				if ( !ret.gourl )
				{
					alert(KDisk_Lang("Error"));
				}else
				if ( ret.gourl )
				{
					window.location.href = ret.gourl;
				}
			},0);
			
			},1);
			return;
		}
		break;
		case "kv_createdir":
		{
				
			var msg = new KV_Elements();
			msg.MsgBox(KDisk_Lang('<div style="position: relative;width: 100%; text-align:center;"><p>' + KDisk_Lang("New folder name") + '</p><input class="kd-info-file-name" style="display: block;width: 100%;" type="text" id="kv_name_dir"/></div>',1),function(ret)

		{
				if( ret == 1)
				{
					var dir = document.getElementById('kv_name_dir').value;
					
					if ( dir.length > 32 )
					{
						alert(KDisk_Lang("Name cannot exceed 32 characters"));
						return;
						
					}
					var path = window.location.pathname;
					if( path.slice(-1) != "/" )path += "/";
					ar = [decodeURIComponent( path ) + dir ];

					AJSSendJson("/" + g_kv_url_mydisk + "/?kv_mkdir",ar,function(r,json){
					var ret = json;
					if ( ret.gourl )
					{

						window.location.href = ret.gourl; 
					}
					
				},0);
				}
			},1);
		}
		break;
	}

	var e = event;
	if ( ct.classList.contains('kv-one-img-pre-view') || ct.classList.contains('kv-page-view'))
	{
		fs = document.querySelectorAll(".kv-button-panel");
		if(fs && fs[0].style.visibility=="hidden") fs[0].style.visibility="visible";else fs[0].style.visibility="hidden";
	}
	
	
	if ( ct.classList.contains('kv-list-item-element-menu-trash') )
	{
		var arbmenu = 	new Array( { name : KDisk_Lang('Restore'), href : null, fun : function() {
			
			var ctr = document.getElementById("kv_recover");
			if ( ctr )
			{
				ct.parentNode.classList.add("kv-file-sel");
				ctr.click();
				ct.parentNode.classList.remove("kv-file-sel");
			}
			return true;

		}});
		var ctpp = g_KvElem.ShowPopupMenu(event.clientX, event.clientY, arbmenu );
	
		return false;
		
	}else
	if ( ct.classList.contains('kv-list-item-element-menu') )
	{
		var arbmenu = 	new Array( { name : KDisk_Lang('Copy URL'), href : null, fun : function() {
			var cth = ct.previousSibling.firstChild.firstChild;
			var hr = cth.getAttribute("href");

			if ( hr ) {
				ar = [hr];
				AJSSendJson("/" + g_kv_url_mydisk + "/?kr_open_link",ar,function(r,json) {
					var ret = json;
					if ( ! ret.cpyurl ) {
						alert("Ошибка");
					}else
					if ( ret.cpyurl ) {
						var adr = location.protocol+'//'+location.hostname+(location.port ? ':'+location.port: '');
						KRCopyToClipboard( adr + ret.cpyurl );
						if ( g_cnti ) document.body.removeChild(g_cnti);
						g_cnti = MsgBox("<center>" + KDisk_Lang("Link copied to clipboard") + "</center>");
						window.setTimeout(function(){if ( g_cnti )document.body.removeChild(g_cnti); g_cnti=null},2000);
					}
				},0);
			}
			return true;
				

		}},{ name : KDisk_Lang('Download'), href : null, fun : function() {
			ct.parentNode.classList.add("kv-file-sel");
			KVClick(document.getElementById('kv_download'));
			ct.parentNode.classList.remove("kv-file-sel");
			
		}} );
		
		if ( !ct.classList.contains('kv-list-item-element-menu-view') )
		if ( g_access & 4 )
		{
			var fs = ct.parentNode.querySelector(".kv-file-href");
			var fs_p = ct.parentNode.querySelector(".work-progress");
			
			
			arbmenu[arbmenu.length] = { name :null, href : null, fun :null} ;
			arbmenu[arbmenu.length] = { name :KDisk_Lang('Delete'), href : null, fun : function() 
			{
				ct.parentNode.classList.add("kv-file-sel");
				KVClick(document.getElementById('kv_remove'));
				ct.parentNode.classList.remove("kv-file-sel");
				window.setTimeout(function(){document.body.removeChild(ctpp);},2);
			}} ;
			var typ = fs.getAttribute("type-file");

			if ( (typ == "video" || typ == "audio") && fs_p == null )
			{
				arbmenu[arbmenu.length] = { name : KDisk_Lang('Convert video'), href : null, fun : function() 
				{
					ct.parentNode.classList.add("kv-file-sel");
					KVClickM('kv_make_video');
					ct.parentNode.classList.remove("kv-file-sel");
					window.setTimeout(function(){document.body.removeChild(ctpp);},2);
				}};
				arbmenu[arbmenu.length] = { name : KDisk_Lang('Take audio track'), href : null, fun : function() 
				{
					ct.parentNode.classList.add("kv-file-sel");
					KVClickM('kd_take_audio');
					ct.parentNode.classList.remove("kv-file-sel");
					window.setTimeout(function(){document.body.removeChild(ctpp);},2);
				}};

			}
			
			
			var fs = document.querySelectorAll(".kv-file-sel");
			var len = fs.length;
			if ( fs.length == 2)
			{
				var on=0;
				for (var a=0;a<fs.length;a++)
				{
					var ct = fs[a]; 
					var hr = ct.querySelector(".kv-file-href");
					var tp = hr.getAttribute("type-file");
					if (tp == 'video')on|=1;
					if (tp == 'audio')on|=2;
				}
				if ( on == 3 )
				{
					arbmenu[arbmenu.length] = { name : KDisk_Lang('Combine'), href : null, fun : function() 
					{
						ct.parentNode.classList.add("kv-file-sel");
						KVClickM('kd_combine_video_audio');
						ct.parentNode.classList.remove("kv-file-sel");
						window.setTimeout(function(){document.body.removeChild(ctpp);},2);
					}};
				}
				
			}
				
			
		}
		
		arbmenu[arbmenu.length] = { name : KDisk_Lang('Properties'), href : null, fun : function() 
			{
//				console.log(ct.parentNode);
				var ar = [];
				var ctfil = ct.previousSibling.firstChild.firstChild;
				ar[0] = encodeURIComponent(ctfil.getAttribute("href"));
				AJSSendJson("/" + g_kv_url_mydisk + "/?kd_get_info_file",ar,function(r,json){
				var ret = json;
				var KvElem = new KV_Elements();
				
				var ctm = KvElem.ShowCenterPopupWin(ret['htm']);
				var fs = ctm.querySelectorAll(".td-in-utime");
				var len = fs.length;
				for( var i = 0; i < len; i++)
				{
					fs[i].innerHTML = g_KDiskPage.unixTimeToStr( fs[i].innerHTML );
					
				}
				var fs = document.querySelector(".kd-but-close");
				if ( fs )
				{
					fs.onclick = function(){
						ctm.style.visibility='hidden';
					}
				}
				var fs = document.querySelector(".kd-but-ok");
				if ( fs )
				{
					fs.onclick = function()
					{
						var arid = [ [".kd-info-file-title",'uname'] , [".kd-info-file-name","name"], [".kd-info-file-desc","desc"] ];
						var arnew = [];
						arnew[0] = new Object;
						arnew[0]['file'] = ar[0]; 
						var change = 1;
						for( var i = 0; i < arid.length; i++ )
						{
							var ctv = ctm.querySelector( arid[ i ][0] );
							if ( ctv )
							{
								if ( typeof(ctv.value) != 'undefined')
								{
									if ( ctv.value != ret[ 'inf' ][arid[i][1]] )
									{
										arnew[change]=new Object;
										arnew[change][arid[i][1]] = ctv.value;
										change++;
									}
								}else
								{
									if ( ctv.innerHTML != ret[ 'inf' ][arid[i][1]] )
									{
										arnew[change]=new Object;
										arnew[change][arid[i][1]] = ctv.innerHTML;
										change++;
									}
								}
							}
						}

						if ( change > 1 )
						{
							AJSSendJson("/" + g_kv_url_mydisk + "/?kd_set_info_file",arnew,function(r,json){
								var ret = json;
								if ( !ret.err )
								{
									if ( typeof(arnew[1].uname) != 'undefined' && ctfil)ctfil.innerHTML = arnew[1].uname;
								}
								if ( ret.gourl )
								{
									window.location.href = ret.gourl; 
								}
							});
						}
						
						

						document.body.removeChild(ctm);
					}
				}
				
			

			},0);
				window.setTimeout(function(){document.body.removeChild(ctpp);},2);
			}, ct: ct } ;
			
		var ctpp = g_KvElem.ShowPopupMenu(event.clientX - 50, event.clientY - 20, arbmenu );
	
		return false;
	}

	{
		
		if ( !e.shiftKey && !e.ctrlKey)
		{
			var fs = document.querySelectorAll(".kv-file-sel");
			var len = fs.length;
			for( var i=0; i < len; i++)
			{
				if ( ct != fs[i])
				fs[i].classList.toggle("kv-file-sel");
			}
		}
		if ( e.shiftKey )
		{
			var fs = document.querySelectorAll(".kv-file");
			var len = fs.length;
			var selgo = 0;
			for( var i=0; i < len; i++)
			{
				
				if ( ct == fs[i] || g_kv_ct_pre_sel == fs[i])
				{
					selgo ^= 1;
					fs[i].classList.add("kv-file-sel");
				}
				if ( selgo )
				{
					fs[i].classList.add("kv-file-sel");
				}
				
			}
		}else
		{

			var kvfile = ct.classList.contains("kv-file");
			if( kvfile )
			{
				if (ct.classList.toggle("kv-file-sel"))
				{
					g_kv_ct_pre_sel = ct;
				}else
					g_kv_ct_pre_sel = 0;
			}
		}
	}
	return false;
}
function KRCopyToClipboard( txt ) {
	var copytext = document.createElement('input');
	copytext.value = txt; 
 	document.body.appendChild( copytext );
 	copytext.select();
	document.execCommand('copy');
	document.body.removeChild(copytext);
}
function MsgBox( txt, callback, yesno )
	{

		if(window.event&&typeof(window.event.cancelBubble)!='undefined')window.event.cancelBubble=true;
		var ctni=document.createElement("div");
		var tHtml = "<div class='kr-msgbody'><p>"+txt+"</p><div class='divbut'>";
	
		if ( yesno )tHtml += "<button id='msgbclose' class='kv-but-send' />"+KDisk_Lang("Cancel")+"</button><button id='msgyes' class='kv-but-send' />"+KDisk_Lang("Create")+"</button>";
		tHtml += "</div></div>";
		ctni.id = "msgBox"; 
		ctni.innerHTML = tHtml;
		ctni.className="kr-msgbkg";
		document.body.appendChild(ctni);
		var ct = document.getElementById('msgyes');
		if(ct) ct.onclick=function(){return result(1);}
		var ct = document.getElementById('msgbclose');
		if(ct)
		{
			ct.focus();
			ct.onclick=function(e){return result(0);}
		}
		function result(v)
		{
			if(typeof(callback)=='function')callback( v );
			if(window.event&&typeof(window.event.cancelBubble)!='undefined')window.event.cancelBubble=true;
			window.setTimeout(function(){document.body.removeChild(ctni);},100);
		}

		return ctni;
	}

document.addEventListener("DOMContentLoaded", function()
	{
		g_KDiskPage.contentLoaded();
		var tap = 0;
		var cttap = 0; 
		var timeTap = 0;
		var fs = document.querySelectorAll(".kv-file");
		for( var i=0;i < fs.length; i++)
		{
			
			fs[i].ondblclick = function(){if (!cttap)KVDblClick(this); window.clearTimeout(timeTap); return false;}
			fs[i].onclick = function(){if (!cttap){KVClick(this);}else 
				{
					var ar = KVMakeArraySelectedFiles();
					if(ar.length) 
					{
						this.classList.toggle("kv-file-sel");
					}else
					{
						KVDblClick(this);
					}
					window.clearTimeout(timeTap);
				}
					 return false; 
			}
			fs[i].addEventListener("touchstart", function(){ tap = new Date().getTime(); cttap=this; window.clearTimeout(timeTap);}, false);
			fs[i].addEventListener("touchmove", function(){tap = 0; window.clearTimeout(timeTap);}, false);
/*			fs[i].oncontextmenu = function(){
				window.clearTimeout(timeTap);
				tap = 0;
				this.classList.toggle("kv-file-sel");
				return false;
			}
*/			
			fs[i].addEventListener("contextmenu", function(){window.clearTimeout(timeTap);
				tap = 0;
				this.classList.toggle("kv-file-sel");
				return true;	
				}, false);
			
			fs[i].addEventListener("touchend", function()
			{
				
				var taptime = new Date().getTime() - tap;
				if( tap )
				{
					var ar = KVMakeArraySelectedFiles();

					if(ar.length) 
					{
					//	this.classList.toggle("kv-file-sel");
						
					}else
					if ( taptime  > 20 )
					{ 
//						this.classList.add("kv-file-sel");
					/*	timeTap = window.setTimeout(function(ct){
							alert("tap");
						KVDblClick(ct);
						},800,this);*/
					}
					tap = 0;
				}else
				{
					
				}
				return true;
				
			}, false);
		}
		fs = document.querySelectorAll(".kv-file-trash");
		for( var i=0;i < fs.length; i++)
		{
			fs[i].ondblclick = function(){};
		}
		fs = document.querySelectorAll(".kv-list-item-element-menu");
		for( var i=0;i < fs.length; i++)
		{
			fs[i].addEventListener("dblclick", function(){event.stopPropagation();KVClick(this);return false;}, false);
			fs[i].addEventListener("click", function(){event.stopPropagation();KVClick(this);return false;}, false);
			fs[i].addEventListener("touchstart", function(){event.stopPropagation();}, false);
		}
		fs = document.querySelectorAll(".kv-list-item-element-menu-trash");
		for( var i=0;i < fs.length; i++)
		{
			fs[i].addEventListener("dblclick", function(){event.stopPropagation();KVClick(this);return false;}, false);
			fs[i].addEventListener("click", function(){event.stopPropagation();KVClick(this);return false;}, false);
			fs[i].addEventListener("touchstart", function(){event.stopPropagation();}, false);
		}
		fs = document.getElementsByTagName('video');
		for( var i=0;i < fs.length; i++)
		{
//			fs[i].volume = 1.5;
		}
		
		var loadok=0;
		function getprev()
		{
			
			window.setTimeout(function(){
				
				if ( loadok )
				{
					console.log("loadok: " + loadok);
					window.setTimeout(getprev,10);
					return ;
				}else
					console.log("loadok: 0");
				fs = document.querySelectorAll(".kv-no-img-file");
				var lenout = fs.length;
				if (lenout>2)lenout=2;
				if ( lenout ){
					ar = [];
					for( var i=0;i < lenout; i++)
					{
						ar[i] = fs[i].parentElement.nextElementSibling.querySelector('.kv-file-href').getAttribute('href');

					}
					
					AJSSendJson("/" + g_kv_url_mydisk + "/?kv_get_preview="+lenout,ar,function(r,json){
					var ret = json;
					console.log(ret);
					
					for( var i=0; i < ret.preview.length; i++)
					{
						if ( ret.preview[i] != 0)
						{
							loadok++;
							fs[i].removeAttribute("srcset");
							fs[i].classList.remove("kv-no-img-file");
							
							fs[i].onload = function(){
								loadok--;
							}
							fs[i].onerror = function(){
								alert("err");
							}
							fs[i].src = ret.preview[i];
							
						}
					}
					
					
					getprev();
					},0);

				}
				
			},1000 + (5 - loadok) * 1000);
			
		}
		getprev();
		
		g_KDiskPage.loadKdImage(0);
		//Drag
		var el = document.querySelector(".kv-list-files");
		if (el)
		{
		el.addEventListener('dragenter', function(e)
		{
			this.classList.add("dragover");
			
			
			e.preventDefault()
			e.stopPropagation()
		}, false)
		el.addEventListener('dragleave', function(e)
		{
			if ( !this.contains(e.fromElement) )
			this.classList.remove("dragover");
			e.preventDefault()
			e.stopPropagation()
		}, false)
		el.addEventListener('dragover', function(e)
		{
			this.classList.add("dragover");
			e.preventDefault();
			e.stopPropagation();
		}, false)
		el.addEventListener('drop', function(e)
		{
			console.log("drop");
			this.classList.remove("dragover");
			e.preventDefault();
			e.stopPropagation();
			if ( !e.dataTransfer.files.length )return;
			var items = event.dataTransfer.items;
			for (var i=0; i<items.length; i++) 
			{
				if ( items[i].webkitGetAsEntry )
				{
	   				var item = items[i].webkitGetAsEntry();
					
		    		if (item) {
							if (item.isDirectory)
							{
								var dir = item.name;
								var path = window.location.pathname;
								if( path.slice(-1) != "/" )path += "/";
								var ar = [decodeURIComponent( path ) + dir ];
								AJSSendJson("/" + g_kv_url_mydisk + "/?kv_mkdir",ar,function(r,json){
								var ret = json;
								if ( ret.gourl )
								{
									document.getElementsByName('kv_user_dir')[0].value += "/" + dir;
									var dirReader = item.createReader();
									var path = path || "";
								    dirReader.readEntries(function(entries) 
									{
										var files = new Array(0);
										var cnt_file = 0;
										var end_file = entries.length;
										for (var i=0; i<entries.length; i++) 
										{
											if (typeof(entries[i].file) == 'undefined'){end_file--;continue;}
											entries[i].file(function (file)
											{ 
												files[cnt_file++] = file;
											   	if (cnt_file == end_file ) 
												{
													console.log( files ); 
													var e = new Object;
													e.target = new Object;
													e.target.files = files;
													KVSelFiles(e);
   												}
   
											});
								      	}
								
							    	});
								}
								});
								return;
							}
							//
						}
		    	}
  			}
//			alert(e.dataTransfer.files);
			var files=e.dataTransfer.files;
			console.log( files ); 
			KVSelFiles(e);
		}, false)
		}
		
	});
function KVMake_size_file( size )
{
	var fsize = size;
	var ak = 0;
	var d = 1024;
	if( fsize > d )
	{
		fsize = fsize / d;
		ak++;
	}
	if( fsize > d )
	{
		fsize = fsize / d;
		ak++;
	}
	if( fsize > d )
	{
		fsize = fsize / d;
		ak++;
	}
	switch(ak)
	{
		case 0:
			fsize += " bytes";
		break;
		case 1:
			fsize = fsize.toFixed(1);
			fsize += " KB";
		break;
		case 2:
			fsize = fsize.toFixed(1);
			fsize += " MB";
		break;
		case 3:
			fsize = fsize.toFixed(1);
			fsize += " GB";
		break;
	}
	return fsize;
}
function KDiskPage()
{
	var t = this;
	t.m_wait_load = 0;
	t.loadKdImage=function(mode)
	{
		if(t.m_wait_load)return;
		var fs = document.querySelectorAll('img[kd-src-b]');
		if ( fs )
		{
			var fsbig = document.querySelectorAll(".kv-panels-greed-big");
			for( var i = 0; i < fs.length; i++ )
			{
				var ct = fs[ i ];
				ct.parentNode.classList.add("kv-list-item-icone-noamin");
				var src = ct.getAttribute("kd-src-a");
				if ( src )
				{
					ct.removeAttribute("kd-src-a");
					if (fsbig && fsbig.length)src = ct.getAttribute("kd-src-b");
				
				}else
				{
					if (!mode)continue;
					src = ct.getAttribute("kd-src-b");
					ct.removeAttribute("kd-src-b");
					
				}
				if ( src ) 
				{
					ct.onerror = ct.onload = function(){
						this.classList.remove( "kv-waiting-img-file" );
						t.m_wait_load--; 
						if(!t.m_wait_load)
						t.loadKdImage(mode);
					} 
					ct.src = src;
					t.m_wait_load++;
					if (t.m_wait_load > 1)
					break;
				}
			}
		}
	}
	t.unixTimeToStr = function(u)
	{
		var date = new Date(u * 1000);
		var year = date.getFullYear();
		var month = "0" + (date.getMonth() + 1);
		var day = "0" + date.getDate();	
		var hours = "0" + date.getHours();
		var minutes = "0" + date.getMinutes();
		var seconds = "0" + date.getSeconds();
		var formattedTime = hours.substr(-2) + ':' + minutes.substr(-2) + ':' + seconds.substr(-2) + " " + day.substr(-2) + "." + month.substr(-2) + "." + year;
	
		return formattedTime;
	}
	t.contentLoaded = function()
	{
		if (document.hasFocus())
		{
			t.sendDAction("dfocus");
		}
		window.addEventListener("focus",function ()
		{
			t.sendDAction("wfocus");
		},false);

		
		var cts = document.getElementsByTagName("audio");
		if (cts)
		{
			for(var i = 0; i < cts.length; i++)
			{
				t.setVideoAudioControl(cts[i]);
			}
		}
		cts = document.getElementsByTagName("video");
		if (cts)
		{
			for(var i = 0; i < cts.length; i++)
			{
				t.setVideoAudioControl(cts[i]);
			}
		}
	}
	t.sendDAction = function (act)
	{
		 var pl = {
			 'time': (new Date()).getTime(),
			 'type' : 1,
			 'action' : act,
			 'url' : window.location.pathname,
			
		 };
		 AJSSendJson("/" + g_kv_url_mydisk + "/?kd_stat",pl,function(r,json){});
	}
	t.setVideoAudioControl = function (ct)
	{
		var dev = navigator.mediaDevices.enumerateDevices();
		var br = 0;
		dev.then(function(devices) {
		  	devices.forEach(function(device) {
					if ( device.deviceId )
					{
  						br += device.deviceId;
					}else
					{
						br += device.groupId;
					}
					
		  }
		  
		  );
		  

	    });
		
		
		var idtimer=0;
		var tic = 0;
		var usec = 0;
		function sendAction(ct,act)
		{
			 var pl = {
				 'time': (new Date()).getTime(),
				 'type' : 1,
				 'src' : ct.getAttribute("src"),
				 'action' : act,
				 'usec' : usec,
				 'tic' : tic,
				 'br' : br,
			 };
			 AJSSendJson("/" + g_kv_url_mydisk + "/?kd_stat",pl,function(r,json){});
			 clearInterval(idtimer);
			 var interval = 10;
			 if ( interval > ct.duration / 2 ) interval = ct.duration / 2 ; 
			 interval *= 1000;
			 idtimer = window.setInterval(function()
			 {
				 
				 tic++;
				 usec += interval;
//				 console.log("usec " + usec + " " + tic);
				 sendAction(ct,"wtime");
				
			 },interval);
			 
		}
		 ct.addEventListener("play", function(){
			sendAction(ct,"play");
		 }, false);
		 ct.addEventListener("ended", function(){
			sendAction(ct,"ended");
			clearInterval(idtimer);
		 }, false);
		 ct.addEventListener("pause", function(){
			sendAction(ct,"pause");
			clearInterval(idtimer);

		 }, false);
		 
		 
	}

		
}
var g_KDiskPage = new KDiskPage();