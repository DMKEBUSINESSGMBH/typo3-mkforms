/**
 * @author Ryan Johnson <ryan@livepipe.net>
 * @copyright 2007 LivePipe LLC
 * @package Control.Tabs
 * @license MIT
 * @url http://livepipe.net/projects/control_tabs/
 * @version 2.1.1
 */
if(typeof(Control) == 'undefined') {
	var Control = {};
}
Control.Tabs =Base.extend({
	constructor: function(tab_list_container,options){
		if(!MKWrapper.$(tab_list_container)) {
			return;
		}
		this.activeContainer = false;
		this.activeLink = false;
		this.containers = {};
		this.links = [];
		Control.Tabs.instances.push(this);
		this.options = {
			beforeChange: function(){},
			afterChange: function(){},
			hover: false,
			linkSelector: 'li a',
			setClassOnContainer: false,
			activeClassName: 'active',
			defaultTab: 'first',
			autoLinkExternal: true,
			targetRegExp: /#(.+)$/,
			showFunction: MKWrapper.fxAppear,
			hideFunction: MKWrapper.fxHide
		};
		MKWrapper.extend(this.options, options || {});
		var tabs = typeof(this.options.linkSelector) == 'string' ?
				MKWrapper.findChilds(
					MKWrapper.$(tab_list_container), this.options.linkSelector
				) : this.options.linkSelector(MKWrapper.$(tab_list_container));

		MKWrapper.each(
			MKWrapper.filter(
				tabs,
				function(link){
					return (/^#/).exec(link.href.replace(window.location.href.split('#')[0],''));
				}
			),
			function(link){
				this.addTab(link);
			}.bind(this)
		);
		MKWrapper.each(Formidable.objValues(this.containers),this.options.hideFunction);
		if(this.options.defaultTab == 'first')
			this.setActiveTab(this.links[0]);
		else if(this.options.defaultTab == 'last')
			this.setActiveTab(this.links[this.links.length - 1]);
		else if(this.options.defaultTab != 'none')
			this.setActiveTab(this.options.defaultTab);
		var targets = this.options.targetRegExp.exec(window.location);
		if(targets && targets[1]){
			MKWrapper.each(targets[1].split(','),function(target){
				MKWrapper.each(this.links,function(target,link){
					if(link.key == target){
						this.setActiveTab(link);
						return false;
					}
				}.bind(this,target));
			}.bind(this));
		}
		if(this.options.autoLinkExternal){
			MKWrapper.each(document.getElementsByTagName('a'),function(a){
				if(!MKWrapper.inArray(a, this.links)){
					var clean_href = a.href.replace(window.location.href.split('#')[0],'');
					if(clean_href.substring(0,1) == '#'){
						if(this.containers[clean_href.substring(1)]){
							MKWrapper.attachEvent(
								MKWrapper.$(a),
								'click',
								function(event){
									this.setActiveTab(clean_href.substring(1));
								},
								this
							);
						}
					}
				}
			}.bind(this));
		}
	},
	addTab: function(link){
		var _self = this;
		this.links.push(link);
		var hrefParts = link.getAttribute('href').split('#');
		link.key = hrefParts[hrefParts.length - 1];
		this.containers[link.key] = MKWrapper.$(link.key);

		MKWrapper.attachEvent(
			MKWrapper.$(link),
			this.options.hover ? 'mouseover' : 'click',
			function(event){
				MKWrapper.stopEvent(event);
				_self.setActiveTab(link);
				return false;
			},
			this
		);
	},
	setActiveTab: function(link){
		if(!link)
			return;
		if(typeof(link) == 'string'){
			MKWrapper.each(this.links,function(_link){
				if(_link.key == link){
					this.setActiveTab(_link);
					return false;
				}
			}.bind(this));
		}else{
			this.notify('beforeChange', this.activeContainer);
			if(this.activeContainer) {
				this.options.hideFunction(this.activeContainer);
			}
			MKWrapper.each(this.links, function(item) {
				MKWrapper.removeClass(
					this.options.setClassOnContainer ? MKWrapper.$(item.parentNode) : item,
					this.options.activeClassName
				);
			}.bind(this));
			MKWrapper.addClass(
				this.options.setClassOnContainer ? MKWrapper.$(link.parentNode) : link,
				this.options.activeClassName
			);
			this.activeContainer = this.containers[link.key];
			this.activeLink = link;
			this.options.showFunction(this.containers[link.key]);
			this.notify('afterChange',this.containers[link.key]);
		}
	},
	next: function() {
		var linkToShow = false;
		MKWrapper.each(this.links,function(link,i){
			if(this.activeLink == link && this.links[i + 1]){
				linkToShow = this.links[i + 1];
				return;
			}
		}.bind(this));

		this.setActiveTab(linkToShow);
		return false;
	},
	previous: function(){
		MKWrapper.each(this.links,function(link,i){
			if(this.activeLink == link && this.links[i - 1]){
				this.setActiveTab(this.links[i - 1]);
			}
		}.bind(this));
		return false;
	},
	first: function(){
		this.setActiveTab(this.links.first());
		return false;
	},
	last: function(){
		this.setActiveTab(this.links.last());
		return false;
	},
	notify: function(event_name){
		try{
			if(this.options[event_name])
				return [this.options[event_name].apply(this.options[event_name], arguments.slice(1))];
		}catch(e){
			return false;
		}
	}
});
MKWrapper.extend(
	Control.Tabs,{
	instances: [],
	findByTabId: function(id){
		return Control.Tabs.instances.find(function(tab){
			return tab.links.find(function(link){
				return link.key == id;
			});
		});
	}
});