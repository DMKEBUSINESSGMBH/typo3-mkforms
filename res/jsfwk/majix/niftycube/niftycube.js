/* Nifty Corners Cube - rounded corners with CSS and Javascript
Copyright 2006 Alessandro Fulciniti (a.fulciniti@html.it)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

var Nifty = {
	
	find: function(str, what) {
		return(str.indexOf(what)>=0 ? true : false);
	},
	$Nifty: function(obj, options) {
		var i,top="",bottom="",v=new Array();
		if(options!="") {
			options=options.replace("left","tl bl");
			options=options.replace("right","tr br");
			options=options.replace("top","tr tl");
			options=options.replace("bottom","br bl");
			options=options.replace("transparent","alias");
			
			if(this.find(options, "tl")) {
				top="both";
				if(!this.find(options, "tr")) {
					top="left";
				}
			} else if(this.find(options, "tr")) {
				top="right";
			}
			
			if(this.find(options, "bl")) {
				bottom="both";
				if(!this.find(options, "br")) {
					bottom="left";
				}
			} else if(this.find(options, "br")) {
				bottom="right";
			}
		}
		
		if(top=="" && bottom=="" && !this.find(options, "none")) {
			top="both";bottom="both";
		}
		
		v=$(obj);
		this.FixIE(v);
		
		if(top!="") {
			this.AddTop(v,top,options);
		}
		
		if(bottom!="") {
			this.AddBottom(v,bottom,options);
		}
	},
	Nifty: function(selector,options) {
		var i,v=selector.split(","),h=0;
		if(options==null) {
			options="";
		}
		if(this.find(options, "fixed-height")) {
			h=$$(v[0])[0].offsetHeight;
		}

		for(i=0;i<v.length;i++) {
			this.Rounded(v[i],options);
		}
		
		if(this.find(options, "height")) {
			this.SameHeight(selector,h);
		}
	},
	Rounded: function(selector,options) {
		var i,top="",bottom="",v=new Array();
		if(options!="") {
			options=options.replace("left","tl bl");
			options=options.replace("right","tr br");
			options=options.replace("top","tr tl");
			options=options.replace("bottom","br bl");
			options=options.replace("transparent","alias");
			
			if(this.find(options, "tl")) {
				top="both";
				if(!this.find(options, "tr")) {
					top="left";
				}
			} else if(this.find(options, "tr")) {
				top="right";
			}
			
			if(this.find(options, "bl")) {
				bottom="both";
				if(!this.find(options, "br")) {
					bottom="left";
				}
			} else if(this.find(options, "br")) {
				bottom="right";
			}
		}
		
		if(top=="" && bottom=="" && !this.find(options, "none")) {
			top="both";bottom="both";
		}
		
		v=$$(selector);
		
		for(i=0;i<v.length;i++) {
			this.FixIE(v[i]);
			
			if(top!="") {
				this.AddTop(v[i],top,options);
			}
			
			if(bottom!="") {
				this.AddBottom(v[i],bottom,options);
			}
		}
	},
	AddTop: function(el,side,options) {
		var lim=4,border="",p,i,btype="r",bk,color;

		if(this.find(options, "alias") || (color=this.getBk(el))=="transparent") {
			color="transparent";bk="transparent"; border=this.getParentBk(el);btype="t";
		} else {
			bk=this.getParentBk(el); border=this.Mix(color,bk);
		}

		d=$b({"class": "niftycorners"});
		Element.setStyle(d, $H({
			"margin-left": "-" + this.getPadding(el,"Left") + "px",
			"margin-right": "-" + this.getPadding(el,"Right") + "px",
			"background": bk
		}));

		p=this.getPadding(el,"Top");
		
		if(this.find(options, "small")) {
			Element.setStyle(d, $H({
				"margin-bottom": (p-2)+"px"
			}));
			btype+="s"; lim=2;
		} else if(this.find(options, "big")) {
			Element.setStyle(d, $H({
				"margin-bottom": (p-10)+"px"
			}));
			btype+="b"; lim=8;
		} else {
			Element.setStyle(d, $H({
				"margin-bottom": (p-5)+"px"
			}));
		}
		
		for(i=1;i<=lim;i++) {
			d.appendChild(this.CreateStrip(i,side,color,border,btype));
		}
		
		Element.setStyle(el, $H({
			'padding-top': "0"
		}));
		el.insertBefore(d,el.firstChild);
	},
	AddBottom: function(el,side,options) {
		var lim=4,border="",p,i,btype="r",bk,color;

		if(this.find(options, "alias") || (color=this.getBk(el))=="transparent") {
			color="transparent";bk="transparent"; border=this.getParentBk(el);btype="t";
		} else {
			bk=this.getParentBk(el); border=this.Mix(color,bk);
		}

		d=$b({"class": "niftycorners"});
		Element.setStyle(d, $H({
			"margin-left": "-" + this.getPadding(el,"Left") + "px",
			"margin-right": "-" + this.getPadding(el,"Right") + "px",
			"background": bk
		}));

		p=this.getPadding(el,"Bottom");
		
		if(this.find(options, "small")) {
			Element.setStyle(d, $H({
				"margin-top": (p-2)+"px"
			}));
			btype+="s"; lim=2;
		} else if(this.find(options, "big")) {
			Element.setStyle(d, $H({
				"margin-top": (p-10)+"px"
			}));
			btype+="b"; lim=8;
		} else {
			Element.setStyle(d, $H({
				"margin-top": (p-5)+"px"
			}));
		}
		
		for(i=lim;i>0;i--) {
			d.appendChild(this.CreateStrip(i,side,color,border,btype));
		}
		
		Element.setStyle(el, $H({
			'padding-bottom': "0"
		}));
		el.appendChild(d);
	},
	CreateStrip: function(index,side,color,border,btype) {
		
		var x=$b({
			"class": btype+index
		});

		Element.setStyle(x, $H({
			"background-color": color,
			"border-color": border
		}));
		
		if(side=="left") {
			Element.setStyle(x, $H({
				"border-right-width": 0,
				"margin-right": 0
			}));
		} else if(side=="right") {
			Element.setStyle(x, $H({
				"border-left-width": 0,
				"margin-left": 0
			}));
		}
		
		return(x);
	},
	FixIE: function(el) {
		if(el.currentStyle!=null && el.currentStyle.hasLayout!=null && el.currentStyle.hasLayout==false) {
			Element.setStyle(el, $H({display: "inline-block"}));
		}
	},
	SameHeight: function(selector,maxh) {
		var i,v=selector.split(","),t,j,els=[],gap;
		for(i=0;i<v.length;i++) {
			t=$$(v[i]);
			els=els.concat(t);
		}
		
		for(i=0;i<els.length;i++) {
			if(els[i].offsetHeight>maxh) {
				maxh=els[i].offsetHeight;
			}
			Element.setStyle(els[i], $H({height: "auto"}));
		}
		
		for(i=0;i<els.length;i++) {
			gap=maxh-els[i].offsetHeight;
			if(gap>0) {
				t=$b({"class": "niftyfill"});
				Element.setStyle(t, $H({height: gap+"px"}));
				nc=els[i].lastChild;
				if(nc.className=="niftycorners") {
					els[i].insertBefore(t,nc);
				} else {
					els[i].appendChild(t);
				}
			}
		}
	},
	getParentBk: function(x) {
		var el=x.parentNode,c;
		
		while(el.tagName.toUpperCase()!="HTML" && (c=this.getBk(el))=="transparent") {
			el=el.parentNode;
		}
		
		if(c=="transparent") {
			c="#FFFFFF";
		}
		
		return(c);
	},
	getBk: function(x) {
		var c=this.getStyleProp(x,"backgroundColor");
		if(c==null || c=="transparent" || this.find(c, "rgba(0, 0, 0, 0)")) {
			return("transparent");
		}
		
		if(this.find(c, "rgb")) {
			c=this.rgb2hex(c);
		}
		
		return(c);
	},
	getPadding: function(x,side) {
		var p=this.getStyleProp(x,"padding"+side);
		if(p==null || !this.find(p, "px")) {
			return(0);
		}
		
		return(parseInt(p));
	},
	getStyleProp: function(x,prop) {
		if(x.currentStyle) {
			return(x.currentStyle[prop]);
		}
		
		if(document.defaultView.getComputedStyle) {
			return(document.defaultView.getComputedStyle(x,'')[prop]);
		}
		
		return(null);
	},
	rgb2hex: function(value) {
		var hex="",v,h,i;
		var regexp=/([0-9]+)[, ]+([0-9]+)[, ]+([0-9]+)/;
		var h=regexp.exec(value);
		
		for(i=1;i<4;i++) {
			v=parseInt(h[i]).toString(16);
			if(v.length==1) {
				hex+="0"+v;
			} else {
				hex+=v;
			}
		}
		
		return("#"+hex);
	},
	Mix: function(c1,c2) {
		var i,step1,step2,x,y,r=new Array(3);
		if(c1.length==4)step1=1;
		else step1=2;
		if(c2.length==4) step2=1;
		else step2=2;
		for(i=0;i<3;i++){
			x=parseInt(c1.substr(1+step1*i,step1),16);
			if(step1==1) x=16*x+x;
			y=parseInt(c2.substr(1+step2*i,step2),16);
			if(step2==1) y=16*y+y;
			r[i]=Math.floor((x*50+y*50)/100);
			r[i]=r[i].toString(16);
			if(r[i].length==1) r[i]="0"+r[i];
		}
		
		return("#"+r[0]+r[1]+r[2]);
	}
};