// JavaScript Document
function IsDef(v){if(typeof(v)!='undefined')return 1;return 0;}
function AJSCeate(){var r=0;if(IsDef(window.XMLHttpRequest)){r=new XMLHttpRequest;}else if(IsDef(window.ActiveXObject)){r=new ActiveXObject("Microsoft.XMLHTTP");}return r;}

function AJSSendGet(h,f,fpr)
{
	var r=AJSCeate();if(!r)return;r.open('GET',h,true);r.onreadystatechange=function(){if(r.readyState==4)
		{
			if(IsDef(f)){f(r);}
		}
	};
	if(IsDef(fpr)){r.onprogress=fpr;}
	r.send();
	return r;
}
function AJSSendGetJson(h,clb,fpr)
{
	var r=AJSCeate();if(!r)return;r.open('GET',h,true);r.onreadystatechange=function(){if(r.readyState==4)
		{
			var json = 0;
			switch ( r.responseType )
			{
				case 'json':
					json = r.response;
				break;
				default:
				try{
					json = JSON.parse( r.response );
				}catch (err) {
				}
				
				break;
			}
			if(clb)clb(r,json);
		}
	};
	r.responseType = 'json';
	if(IsDef(fpr)){r.onprogress=fpr;}
	r.send();
	return r;
}

function AJSSendJson(h,obj,clb,fprg)
{
	var r=AJSCeate();
	r.open("POST",h,true);
	r.responseType = 'json';
	var boundary="--GFWF"+String(Math.random()).slice(2)+"GFWF";
	r.setRequestHeader("Content-type", "multipart/form-data;boundary="+boundary);//application/x-www-form-urlencoded; 

	r.onreadystatechange=function()
	{
		if(r.readyState===4)
		{
			var json = 0;
			switch ( r.responseType )
			{
				case 'json':
					json = r.response;
				break;
				default:
				try{
					json = JSON.parse( r.response );
				}catch (err) {
				}
				
				break;
			}
			if(clb)clb(r,json);
		}
	}
	var prm1='--'+boundary+'\r\n'+'Content-Disposition: form-data; name="json"\r\n\r\n' + (JSON.stringify(obj)) + '\r\n';
	var pom="\r\n--"+boundary+"--\r\n";
	var bb=new Blob([prm1,pom]);
	if(IsDef(fprg))r.upload.onprogress=fprg;
	r.send(bb);
	return r;
}

