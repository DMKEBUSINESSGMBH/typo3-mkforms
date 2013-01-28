Formidable.Classes.ListerRow = Base.extend({
	uid: false,
	_parent: false,
	constructor: function(oConfig) { MKWrapper.extend(this, oConfig);},
	rdt: function(sRdt) {
		if((sRdtId = this._parent.config.rdtbyrow[this.uid][sRdt])) {
			return this._parent.oForm.o(sRdtId);	
		} else {
			sPath = sRdt.replace("__", "/");
			aPath = sPath.split("/");
			if((oRdt = this.rdt(aPath[0]))) {
				aPath.shift();
				i = 0;
				aPath.each(function(sPathSegment) {
					if((oRdt = oRdt.rdt(sPathSegment))) {
						i++;
					} else {
						throw $break;
					}
				}.bind(this));
				if(i == aPath.size()) {
					return oRdt;
				}	
			}
		}
		
		return false;
	}
});

Formidable.Classes.Lister = Formidable.Classes.RdtBaseClass.extend({
	constructor: function(oConfig) {
		this.base(oConfig);
	},
	init: function() {
		if(this.config.isajaxlister) {
			var oScope = this;
			
			oFirst = MKWrapper.$(this.config.id + "_pagelink_first");
			oPrev = MKWrapper.$(this.config.id + "_pagelink_prev");
			oNext = MKWrapper.$(this.config.id + "_pagelink_next");
			oLast = MKWrapper.$(this.config.id + "_pagelink_last");
			
			if(oFirst) {
				oFirst.href="javascript:void(0);";
				MKWrapper.attachEvent(oFirst, "click", this.repaintFirst, this);
			}
			
			if(oPrev) {
				oPrev.href="javascript:void(0);";
				MKWrapper.attachEvent(oPrev, "click", this.repaintPrev, this);
			}
			
			if(oNext) {
				oNext.href="javascript:void(0);";
				MKWrapper.attachEvent(oNext, "click", this.repaintNext, this);
			}
			
			if(oLast) {
				oLast.href="javascript:void(0);";
				MKWrapper.attachEvent(oLast, "click", this.repaintLast, this);
			}

			for(var count = 1; count <= this.config.pages; count++) {
				var sPageLinkId = this.config.id + "_pager_" + count;
				var oPageLink = MKWrapper.$(sPageLinkId);
//				this.oPageLinks.aLinks[sPageLinkId] = count;
				if(oPageLink){
					oPageLink.href="javascript:void(0);";
					MKWrapper.attachEvent(oPageLink, "click", function(event){
							oScope.repaintToSite(this, oScope.config.repainttosite, event);
						}, 'skip');
				}
			}

			for (var property in this.config.columns) {
				oSortLink = MKWrapper.$(this.config.id + "_sortlink_" + this.config.columns[property])
				if(oSortLink) {
					oSortLink.href="javascript:void(0);";
					MKWrapper.attachEvent(oSortLink, "click", function(event){
						oScope.repaintSortBy(this, oScope.config.repaintsortby, event);
					}, 'skip');
				}
			}
		}	
	},
	iToPage: 0,
	iSortCur: 0,
	iSortDir: 0,
	getRow: function(iUid) {
		return new Formidable.Classes.ListerRow({
			"uid": iUid,
			"_parent": this
		});
	},
	getRows: function() {
		var aRows = MKWrapper.$A();
		
		var tscope = this;
		for (var property in this.config.rdtbyrow) {
			aRows.push(tscope.getRow(property));	
		}
//		MKWrapper.each(MKWrapper.$H(this.config.rdtbyrow), function(oData,i) {
//			aRows.push(tscope.getRow(oData[0]));
//		}, tscope);

		return aRows;
	},
	getCurrentRow: function() {
		aContext = this.oForm.getContext();
		if(typeof(aContext.currentrow) != "undefined") {
			return this.getRow(aContext.currentrow);
		}
	},
	repaintFirst: function() {
		Formidable.globalEval(this.config.repaintfirst);
	},
	repaintPrev: function() {
		Formidable.globalEval(this.config.repaintprev);
	},
	repaintNext: function() {
		Formidable.globalEval(this.config.repaintnext);
	},
	repaintLast: function() {
		Formidable.globalEval(this.config.repaintlast);
	},
	repaintToSite: function(oElement, func, event) {
		sElementId = MKWrapper.id(oElement);
		this.iToPage = parseInt( sElementId.substr( sElementId.search(/_[0-9]+$/) + 1 ) );
		Formidable.globalEval(func);
	},
	repaintSortBy: function(oElement, func, event) {
		sElementId = MKWrapper.id(oElement);
		sElementCur = sElementId.substr( sElementId.lastIndexOf('_') + 1 );
		for (var property in this.config.columns) {
			if(sElementCur==this.config.columns[property])
				this.iSortCur = sElementCur;
		}
		this.iSortDir = 'no';
		if(this.config.sort.column == sElementCur)
			this.iSortDir = this.config.sort.direction;
		//Formidable.globalEval(func);
		eval(func);	// not globalEval to pass local arguments (event) to further methods
	},
	getParamsForMajix: function(aValues, sEventName, aParams, aRowParams, aLocalArgs) {
		aValues = this.base(aValues, sEventName, aParams, aRowParams, aLocalArgs);
		
		oSysEvents = new Object();
		
		aParamValues = Formidable.objValues(aParams);
		
		if((index = Formidable.indexOf(aParamValues,'sys_event.pagenum')) != -1) {
			oSysEvents.pagenum = this.iToPage;
//			aValues['sys_event.pagenum'] = this.iToPage;
		}
		
		if((index = Formidable.indexOf(aParamValues, 'sys_event.sortcol')) != -1) {
			oSysEvents.sortcol = this.iSortCur;
		}
		
		if((index = Formidable.indexOf(aParamValues, 'sys_event.sortdir')) != -1) {
			if(this.iSortDir == 'asc') {
				oSysEvents.sortdir = 'desc';
			} else {
				// covers "no" and "desc"
				oSysEvents.sortdir = 'asc';
			}
		}

		aValues['sys_event'] = oSysEvents;
		
		return aValues;
	}
});
