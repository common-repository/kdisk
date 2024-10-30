function KV_Elements()
{
	this.MsgBox = function ( txt, callback, yesno )
	{
		
		var htm = '<div class="kd-msgbox"><button class="kd-but-close"></button><p class="kd-title">' + txt + '</p><table>';
			htm += '<tr class="kd-msgbox-yes-no"><td><button class="kd-but-ok">' + KDisk_Lang('Yes') + '</button></td><td><button class="kd-but-no">' + KDisk_Lang('No') + '</button></td></tr>';
			htm += '</table></div>';	
		var ctni=this.ShowCenterPopupWin(htm);
		var ct = ctni.querySelector(".kd-but-no");
		function retshow(p){
			if(window.event&&typeof(window.event.cancelBubble)!='undefined')window.event.cancelBubble=true;
			ctni.style.visibility='hidden';
			
			callback(p);
		}
		ct.onclick = function(){ retshow(0); }
		ct = ctni.querySelector(".kd-but-ok");
		ct.onclick = function(){ retshow(1); }
		ct = ctni.querySelector(".kd-but-close");
		ct.onclick = function(){ retshow(0); }
		return ctni;
	}

	this.ShowPopupMenu = function (posX,posY,elm)
	{
		var div = document.createElement('div'); 
		div.className = "menu-menu-container";
		for ( var i = 0; i < elm.length; i++)
		{
			var li=document.createElement('li'); 
			if (elm[i].name){
			var a = document.createElement('a'); 
			if ( elm[i].href )
			{
				a.setAttribute('href',elm[i].href);
			}
			li.onclick = elm[i].fun;
			a.innerHTML = elm[i].name;
			li.appendChild(a);
			}else
			{
				li=document.createElement('div');
			}
			div.appendChild(li);
		}
		var ct = document.getElementById("kv-popup-menu");
		if (!ct)
		{
			ct = document.createElement('nav');
			ct.setAttribute('tabindex','0');
			ct.className = "popup-menu";
			ct.id = "kv-popup-menu";
			ct.onmouseout = function(){ if ( ! this.contains(event.relatedTarget) )this.style.visibility='hidden'; }
			ct.onblur = function(){	if ( ! this.contains(event.relatedTarget) )this.style.visibility='hidden'; }
			ct.appendChild(div);
			document.body.appendChild(ct);
		
		}else
		{
			ct.innerHTML='';
			ct.appendChild(div);
		}
		
		posX -= 5;
		posY -= 5;
		if(posX + ct.offsetWidth > document.body.offsetWidth)posX = document.body.offsetWidth - ct.offsetWidth - 5;
		
		
		
		if ( posY + ct.offsetHeight > window.innerHeight)posY = window.innerHeight - ct.offsetHeight - 5 ;
		
		posY += window.pageYOffset;
		
		ct.style.visibility= "visible";
		ct.style.left = (posX) + "px";
		ct.style.top = (posY) + "px";
		ct.focus();
		return ct; 
	}
	this.ShowCenterPopupWin = function (txthtml)
	{
		var posX=document.body.offsetWidth/2;
		var posY=window.innerHeight/2;
//		return;
		var div = document.createElement('div'); 
		div.className = "pop-win-container";
		div.innerHTML=txthtml;
		
		var ct = document.getElementById("kv-popup-win");
		if (!ct)
		{
			ct = document.createElement('nav');
			ct.setAttribute('tabindex','0');
			ct.className = "kd-popup-win";
			ct.id = "kv-popup-win";
//			ct.onmouseout = function(){ if ( ! this.contains(event.relatedTarget) )this.style.visibility='hidden'; }
			ct.onblur = function(){	if ( ! this.contains(event.relatedTarget) )this.style.visibility='hidden'; }
			ct.appendChild(div);
			document.body.appendChild(ct);
		
		}else
		{
			ct.innerHTML='';
			ct.appendChild(div);
		}
		
		posX -= 5;
		posY -= 5;
		//if(posX + ct.offsetWidth > document.body.offsetWidth)
		posX = document.body.offsetWidth/2 - ct.offsetWidth/2;
		if ( posY + ct.offsetHeight > window.innerHeight)posY = window.innerHeight - ct.offsetHeight - 5 ;
		posY += window.pageYOffset;
		ct.style.visibility= "visible";
//		ct.style.left = (posX) + "px";
	//	ct.style.top = (posY) + "px";
		ct.focus();
		return ct; 
	}
}