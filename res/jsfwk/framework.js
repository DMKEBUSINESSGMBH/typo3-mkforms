if(!console) {
	var console={
		log: function(oObj) {},
		dir: function(oObj) {}
	};
}

var Formidable = {
	initialize: function(oConfig) {
		// aktuelle Instanz von Formidable wird um die eigenschaften von oConfig erweitert
		MKWrapper.extend(this, oConfig);
		this.Browser.getBrowserInfo();
	},
	SEP: '__',
	SUBMIT_FULL: "AMEOSFORMIDABLE_EVENT_SUBMIT_FULL",
	SUBMIT_REFRESH: "AMEOSFORMIDABLE_EVENT_SUBMIT_REFRESH",
	SUBMIT_TEST: "AMEOSFORMIDABLE_EVENT_SUBMIT_TEST",
	SUBMIT_DRAFT: "AMEOSFORMIDABLE_EVENT_SUBMIT_DRAFT",
	SUBMIT_CLEAR: "AMEOSFORMIDABLE_EVENT_SUBMIT_CLEAR",
	SUBMIT_SEARCH: "AMEOSFORMIDABLE_EVENT_SUBMIT_SEARCH",
	
	Classes: {},		// placeholder for classes definitions; used like this: var oObj = new Formidable.Classes.SomeObject(params)
	CodeBehind: {},
	Context: {
		Forms: {},		// placeholder for subscribed forms in the page
		Objects: {}		// placeholder for page-level objects ( like modalbox )
	},

	Lister: {
		Pager: {
			goTo: function(sFormId, iPage) {

				var aForm = Formidable.f(sFormId);
				var oForm = aForm.domNode();

				oForm[aForm.Misc.HiddenIds.Lister.page].value=iPage;
				aForm.submitRefresh();
			}
		},
		Sort: {
			by: function(sName, sDirection, sFormId) {
				
				var aForm = Formidable.f(sFormId);
				var oForm = aForm.domNode();

				oForm[aForm.Misc.HiddenIds.Lister.sortField].value=sName;
				oForm[aForm.Misc.HiddenIds.Lister.sortDirection].value=sDirection;
				oForm.action=aForm.Misc.Urls.Lister.sortAction;

				aForm.submitRefresh();
			}
		}
	},
	f: function(sFormId) {		// shortcut for getting form instance
		return this.Context.Forms[sFormId];
	},
	o: function(sObjectId) {
		return this.Context.Objects[sObjectId];
	},
	executeInlineJs: function(oJson) {
		$H(oJson).each(function(sJs) {
			Formidable.globalEval(unescape(sJs));
		}.bind(this));
	},
	loadedScripts: [],
	addScript: function(scriptKey) {
		if(!this.isScriptLoaded(scriptKey))
			this.loadedScripts.push(scriptKey);
	},
	// returns true if script is still loaded
	isScriptLoaded: function(scriptKey) {
		return (this.indexOf(this.loadedScripts, scriptKey) > -1);
	},
	indexOf: function(arr, item) {
	    i = 0;
	    var length = arr.length;
	    for (; i < length; i++)
	      if (arr[i] === item) return i;
	    return -1;
	},
	debugMessage: function(sMessage) {
		sMessage = sMessage.replace(/<br \/>/g, "\n");
		sMessage = sMessage.replace(/<b>/g, "");
		sMessage = sMessage.replace(/<\/b>/g, "");
		sMessage = sMessage.replace(/^\s+|\s+$/g,"");
		alert(sMessage);
	},
	Browser: {
		name: "",
		version: "",
		os: "",
		total: "",
		thestring: "",
		place: "",
		detect: navigator.userAgent.toLowerCase(),
		checkIt: function(string) {
			this.place = this.detect.indexOf(string) + 1;
			this.thestring = string;
			return this.place;
		},
		getBrowserInfo: function() {
			//Browser detect script originally created by Peter Paul Koch at http://www.quirksmode.org/
			if (this.checkIt('konqueror')) {
				this.name = "konqueror";
				this.os = "linux";
			}
			else if (this.checkIt('safari')) 	{ this.name = "safari"; }
			else if (this.checkIt('omniweb')) 	{ this.name = "omniweb"; }
			else if (this.checkIt('opera')) 	{ this.name = "opera"; }
			else if (this.checkIt('webtv')) 	{ this.name = "webtv"; }
			else if (this.checkIt('icab')) 		{ this.name = "icab"; }
			else if (this.checkIt('msie')) 		{ this.name = "internet explorer"; }
			else if (!this.checkIt('compatible'))
												{ this.name = "netscape";
												  this.version = this.detect.charAt(8); }
			else 								{ this.name = "unknown"; }

			if (!this.version) { this.version = this.detect.charAt(this.place + this.thestring.length); }

			if (!this.os) {
				if (this.checkIt('linux')) 		{ this.os = "linux"; }
				else if (this.checkIt('x11')) 	{ this.os = "unix"; }
				else if (this.checkIt('mac')) 	{ this.os = "mac"; }
				else if (this.checkIt('win')) 	{ this.os = "windows"; }
				else 							{ this.os = "unknown"; }
			}
		}
	},
	Position: {
		/* caught at http://textsnippets.com/tag/dimensions */
		putCenter: function(item, what) {
			item = MKWrapper.$(item);
			
			var xy = MKWrapper.getDimensions(item);
			var win = this.windowDimensions();
			var scrol = this.scrollOffset();
			if(!what || what == "h") {
				MKWrapper.setStyle(item, {left : (win.width / 2) + scrol.width - (xy.width / 2) + 'px'});
			}

			if(!what || what == "v") {
				MKWrapper.setStyle(item, {top : (win.height / 2) + scrol.height - (xy.height / 2) + 'px'});
			}
		},
		putCenterVertical: function(item) {
			this.putCenter(item, "v");
		},
		putCenterHorizontal: function(item) {
			this.putCenter(item, "h");
		},
		putFixedToWindowVertical: function(item, offset) {
			item = MKWrapper.$(item);
			var xy = MKWrapper.getDimensions(item);
			var win = this.windowDimensions();
			var scrol = this.scrollOffset();
			
			MKWrapper.setStyle(item, {top : (scrol.height + parseInt(offset)) + 'px'});
//			item.style.top = (scrol.height + parseInt(offset)) + "px";
		},
		fullScreen: function(item) {
			item = MKWrapper.$(item);
			var win = this.windowDimensions();
			var scrol = this.scrollOffset();
			MKWrapper.setStyle(item, {height : scrol.height + win.height + 'px'});
//			item.style.height = scrol.height + win.height + "px";
		},
		windowDimensions: function() {
			var x, y;
			if(self.innerHeight) {// all except Explorer
				x = self.innerWidth;
				y = self.innerHeight;
			} else if (document.documentElement && document.documentElement.clientHeight) {
				// Explorer 6 Strict Mode
				x = document.documentElement.clientWidth;
				y = document.documentElement.clientHeight;
			} else if (document.body) {// other Explorers
				x = document.body.clientWidth;
				y = document.body.clientHeight;
			}
			
			if (!x) x = 0;
			if (!y) y = 0;
			return {width: x, "height": y};
		},
		scrollOffset: function() {
			var x, y;
			if(self.pageYOffset) {
				// all except Explorer
				x = self.pageXOffset;
				y = self.pageYOffset;
			} else if (document.documentElement && document.documentElement.scrollTop) {
				// Explorer 6 Strict
				x = document.documentElement.scrollLeft;
				y = document.documentElement.scrollTop;
			} else if (document.body) {
				// all other Explorers
				x = document.body.scrollLeft;
				y = document.body.scrollTop;
			}
			
			if (!x) x = 0;
			if (!y) y = 0;
			return {width: x, height: y};
		}
	},
	getLocalAnchor: function() {
		var last = MKWrapper.$A(window.location.href.replace(window.location.href.split('#')[0],'').split('/'));
		last = last[last.length - 1];
		return last.replace(/#/,'');
	},
	log: function() {
		console.log(arguments);
	},
	formatSize: function(iSizeInBytes) {
		iSizeInByte = parseInt(iSizeInBytes);
		if(iSizeInBytes > 900) {
			if(iSizeInBytes>900000)	{	// MB
				return parseInt(iSizeInBytes/(1024*1024)) + ' MB';
			} else {	// KB
				return parseInt(iSizeInBytes/(1024)) + ' KB';
			}
		}
		
		// Bytes
		return iSizeInBytes + ' B';
	},
	globalEval: function(sScript) {
		// using window.eval here instead of eval
			// to ensure that the script is eval'd in the global context
			// and not the local one
				// see http://www.modulaweb.fr/blog/2009/02/forcer-l-evaluation-du-code-dans-un-contexte-global-en-javascript/
		// however this doesn't work in IE
			// so we have to use window.execScript instead
				// see http://ajaxian.com/archives/evaling-with-ies-windowexecscript
		// NOR in Safari, where we use the brute force approach
		
		if(Formidable.Browser.name == "internet explorer") {
			window.execScript(sScript); // eval in global scope for IE
		} else if(Formidable.Browser.name == "safari") {
			//window.setTimeout(sString, 0);

			var _script = document.createElement("script");
			_script.type = "text/javascript";
			_script.defer = false;
			_script.text = sScript;
			var _headNodeSet = document.getElementsByTagName("head");
			if(_headNodeSet.length) {
				_script = _headNodeSet.item(0).appendChild(_script);
			} else {
				var _head = document.createElement("head");
				_head = document.documentElement.appendChild(_head);
				_script = _head.appendChild(_script);
			}
		} else {
			window.eval(sScript);
		}
	},
	includeStylesheet: function(sUrl) {
		if(document.createStyleSheet) {
			document.createStyleSheet(sUrl);
		} else {
			var styles = "@import url('" + sUrl + "');";
			var newSS=document.createElement('link');
			newSS.rel='stylesheet';
			newSS.href='data:text/css,'+escape(styles);
			document.getElementsByTagName("head")[0].appendChild(newSS);
		}
	},
	/**
	 * Make an array from object attributes
	 * Könnte Ersatz für $H sein!
	 */
	obj2Array: function(obj) {
		var arr = [];
		for (var key1 in obj) {
			arr.push({
				key: key1,
				value: obj[key1]
			});
		}
		return arr;
	},
	/**
	 * Iteration über alle Attrubite des Objekts. Die Callback-Methode wird mit
	 * den Parametern key und value aufgerufen.
	 */
	objEach: function(obj, callback) {
		for (var key1 in obj) {
			callback(key1, obj[key1]);
		}
	},
	/**
	 * Liefert ein Array mit den Attributwerten des Objekts
	 */
	objValues: function(object){
		var results = [];
		for (var property in object)
		  results.push(object[property]);
		return results;
	}

};

Formidable.Classes.FormBaseClass = Base.extend({
	domNode: function() {
		return document.forms[this.sFormId];
	},
	aParamsStack: MKWrapper.$A(),
	aContextStack: MKWrapper.$A(),
	aAddPostVars: [],
	ajaxCache: {},
	ViewState: [],
	Objects: {},		// placeholder for instanciated JS objects in the FORM
	aDynHeadersLoaded: [],
	oLoading: null,
	oDebugDiv: false,
	currentTriggeredArguments: false,
	Services: {},
	AjaxRequestsStack: MKWrapper.$H(),
	constructor: function(oConfig) {
		MKWrapper.extend(this, oConfig);
		this.initLoader();
	},
	getParams: function() {
		var last = MKWrapper.$A(this.aParamsStack);
		return last[last.length - 1];
	},
	getContext: function() {
		var last = MKWrapper.$A(this.aContextStack);
		return last[last.length - 1];
	},
	getSender: function() {
		return this.getContext().sender;
	},
	o: function(sObjectId) {	// shortcut for getting object instance

		if(sObjectId == "tx_ameosformidable") {
			return Formidable;	// the static Formidable object
		} else if(Formidable.f(sObjectId)) {
			return Formidable.f(sObjectId);	// instance of FormBaseClass object
		} else if(this.Objects[sObjectId]) {
			return this.Objects[sObjectId];	// a renderlet
		} else if(this.Objects[this.sFormId + Formidable.SEP + sObjectId]) {
			return this.Objects[this.sFormId + Formidable.SEP + sObjectId];	// a renderlet that was not prefixed with the formid
		} else if(MKWrapper.$(sObjectId)) {
			oObj = MKWrapper.$(sObjectId);

			if(MKWrapper.hasClass(oObj, "readonly")) {	// giving a chance to readonly rdt to be caught
				return new Formidable.Classes.RdtBaseClass({
					"formid": this.sFormId,
					"id": sObjectId
				});
			}

			return oObj;
		}

		return null;
	},
	rdt: function(sObjectId) {
		return this.o(sObjectId);
	},
	attachEvent: function(sRdtId, sEventHandler, fFunc) {
		var oObj = this.o(sRdtId);
		if(oObj && typeof(oObj) != 'undefined') {
			if(typeof(oObj.domNode) != 'undefined') {
				oObj.attachEvent(sEventHandler, fFunc);
			}
		}
	},
	//
	handleValidationErrors: function(oErrors, msgDiv) {
		msgDiv = (msgDiv != '1') ? this.sFormId+'__'+msgDiv : false;
		if(MKWrapper.handleValidationErrors) {
			MKWrapper.handleValidationErrors(oErrors, this, msgDiv);
			return;
		}
		var msg = "Es sind folgende Fehler aufgetreten:\n";
		for (var widget in oErrors) {
			msg += '- ' + oErrors[widget] + "\n";
		}
		alert(msg);
	},
	updateViewState: function(oExecuter) {
		this.ViewState.push(oExecuter);
	},
	executeServerEvent: function(sEventId, sSubmitMode, sParams, sHash, sJsConfirm) {

		var bThrow = false;

		if(sJsConfirm != false) {
			bThrow = confirm(unescape(sJsConfirm));
		} else {
			bThrow = true;
		}

		if(bThrow) {
			$(this.sFormId + "_AMEOSFORMIDABLE_SERVEREVENT").value=sEventId;

			if(sParams != false) {
				$(this.sFormId + "_AMEOSFORMIDABLE_SERVEREVENT_PARAMS").value=sParams;
				$(this.sFormId + "_AMEOSFORMIDABLE_SERVEREVENT_HASH").value=sHash;
			}
			
			
			if(sSubmitMode) {
				this.doSubmit(sSubmitMode, true);
			} else {
				this.domNode().submit();
			}
		}
	},
	_clientData: function(oData) {
		if(oData) {
			if(typeof oData == "string") {
				if(oData.slice(0, 12) == "clientData::") {
					sObjectId = oData.slice(12);
					oObj = this.o(sObjectId);
					if(oObj && typeof oObj.getValue == "function") {
						oData = oObj.getValue();
					}
				}
			} else if(typeof oData == "object") {
				for(var i in oData) {
					oData[i] = this._clientData(oData[i]);
				}
			}
		}

		return oData;
	},
	executeClientEvent: function(sObjectId, bPersist, oTask, sEventName, aLocalArguments, sJsConfirm) {
		//console.log("Executing client event", "sObjectId:", sObjectId, "bPersist:", bPersist, "oTask", oTask, "aLocalArguments", aLocalArguments, "sJsConfirm", sJsConfirm);

		if(sJsConfirm != false) {
			bThrow = confirm(unescape(sJsConfirm));
		} else {
			bThrow = true;
		}

		if(bThrow) {
			if(oTask.tasks.object) {
				// it's a single task to execute
				this.executeClientTask(oTask.tasks, bPersist, sEventName, sObjectId, aLocalArguments);
			} else {
				// it's a collection of tasks to execute
				var _this = this;

//				var oTask_tasks = MKWrapper.$H(oTask.tasks);
				Formidable.objEach(oTask.tasks, MKWrapper.bind(function(value, key) {
					_this.executeClientTask(key, bPersist, sEventName, sObjectId, aLocalArguments);
				}, this));
			}

			if(bPersist) {
				this.updateViewState(oTask);
			}
		}
	},
	executeClientTask: function(oTask, bPersist, sEventName, sSenderId, aLocalArguments) {

		if(oTask.formid) {
			// execute it on given formid
			var oForm = Formidable.f(oTask.formid);
			if(!oForm) {
				//console.log("executeClientEvent: single task: on formid " + oTask.formid + ": No method named " + oTask.method + " on " + oTask.object);
			}
		} else {
			var oForm = this;
		}

		var oObject = oForm.o(oTask.object);
		var oSender = oForm.o(sSenderId);

		if(oObject) {
			if(oObject[oTask.method]) {
				oData = oForm._clientData(oTask.data);

				var aParams = oSender.getParamsForMajix(
					{},
					sEventName,
					{},//aParams,
					{},//aRowParams,
					aLocalArguments
				);

				var aParams = MKWrapper.extend(aParams, oTask.databag.params);
				oContext = oTask.databag.context || {};
				oContext.sender = oSender;
				oContext.event = aLocalArguments[0];
				if(oContext.event) {
					oContext.event[0] = oContext.event;	// back compat
				}
				
				this.aContextStack.push(oContext);
				this.aParamsStack.push(aParams);

				oObject[oTask.method](oData);
				this.aParamsStack.pop();
				this.aContextStack.pop();

			} else {
				console.log("executeClientEvent: single task: No method named " + oTask.method + " on " + oTask.object);
			}
		} else {
			console.log("executeClientEvent: single task: No object named " + oTask.object);
		}
	},
	executeAjaxEvent: function(sEventName, sObjectId, sEventId, sSafeLock, bCache, bPersist, sTrigerTinyMCE, aParams, aRowParams, aLocalArguments, sJsConfirm) {
		var t = this;
		//console.log('executeAjaxEvent ', 'sEventName: ', sEventName, 'sObjectId: ', sObjectId, 'sEventId: ', sEventId, 'sSafeLock: ', sSafeLock, 'bCache: ', bCache, 'bPersist: ', bPersist, 'aParams: ', aParams, 'aRowParams: ', aRowParams, 'aLocalArguments: ', aLocalArguments, 'sJsConfirm: ', sJsConfirm, 'framework.js'); //@TODO: remove me

		bThrow = (sJsConfirm != false) ? confirm(unescape(sJsConfirm)) : true;
		if(!bThrow) return;

		// das tinymce auch bei ajaxcalls mit übergeben!
		if(sTrigerTinyMCE && typeof(tinymce) == 'object') {
			// get rte fields
			if(typeof(sTrigerTinyMCE) == 'string' && sTrigerTinyMCE.toLowerCase() === 'true') {
				// true was given, get all editors
				var aTinyMCE = tinyMCE.EditorManager.get();
			} else {
				// a list of if'd was given, get those editors
				var aTinyMCE = new Array();
				MKWrapper.each(tinyMCE.explode(sTrigerTinyMCE,','), function(o,i){
					if(t.o(o)) {
						aTinyMCE.push( tinyMCE.EditorManager.get( t.o(o).config.id ) );
					}
				});
			}
			// save the boxes!
			MKWrapper.each(aTinyMCE, function(o,i){
				try {
					o.save();
				} catch (e) { /* TODO: something goes wrong at saving the tinymce */ }
			});
		}
		
		var aValues = {};
		if(aParams) {
			for(var sKey in aParams) {
				// TODO: freie Parameter erlauben
				sName = aParams[sKey];
				sReturnName = sName;
				sId = sName;
				if(sName.slice(0, 10) == "rowInput::") {
					aInfo = sName.split("::");
					sReturnName = aInfo[1];
					sId = aInfo[2];
				}

				if((oElement = this.o(sId))) {
					aValues[sReturnName] = oElement.getParamsForMajix(
							oElement.getValue(), sEventName, aParams, aRowParams, aLocalArguments
						);
				} else if((oElement = MKWrapper.$(this.rdtIdByName(sName)))) {
					aValues[sReturnName] = MKWrapper.$F(oElement);
				} else {
					if(sName.indexOf('::')) {
						// Freie Parameterübergabe
						aInfo = sName.split("::");
						sKey = aInfo[0];
						sName = aInfo[1];
					}
					aValues[sKey] = sName; // should be the value itselves
				}
			}
		}

		if(aRowParams) {
			for(var sName in aRowParams) {
				aValues[sName] = aRowParams[sName];
			}
		}

		var oObject = this.o(sObjectId);
		if(oObject.getMajixThrowerIdentity != undefined) {
			var sThrower = oObject.getMajixThrowerIdentity(sObjectId);
			aValues = oObject.getParamsForMajix(aValues, sEventName, aParams, aRowParams, aLocalArguments);
		} else {
			var sThrower = sObjectId;
		}
		
		var sValue = JSONstring.make(aValues, true);
		var sTrueArgs = false;
		if(this.currentTriggeredArguments) {
			var sTrueArgs = JSONstring.make(this.currentTriggeredArguments, true);
		}
		
		var sUrl = this.Misc.Urls.Ajax.event + "&formid=" + this.sFormId + "&eventid=" + sEventId + "&safelock=" + sSafeLock + "&value=" + escape(sValue) + "&thrower=" + escape(sThrower) + "&trueargs=" + escape(sTrueArgs);

		if(!bCache) { sUrl += "&random=" + escape(Math.random());}

		if(bCache && this.ajaxCache[sUrl] != undefined) {
			this.executeAjaxResponse(this.ajaxCache[sUrl],bPersist,bFromCache = true);
		} else {
			this.displayLoader();

			//new Ajax.Request( this.Misc.Urls.Ajax.event,
			
			MKWrapper.ajaxCall( this.Misc.Urls.Ajax.event,
				{
					method:'post',
					asynchronous: true,
					evalJS: true,
					parameters: {
						'formid': this.sFormId,
						'eventid': sEventId,
						'safelock': sSafeLock,
						'value': sValue,
						'thrower': sThrower,
						'trueargs': sTrueArgs
					},
					onSuccess: function(response, scope) {
						response = MKWrapper.strStrip(response);
						scope = typeof scope != 'undefined' ? scope : this;
						var removeLoader = true;
//						if(response != "" && response.substr(0, 1) != "{") {
						if( !MKWrapper.isJSON(response) ) {
							if(response != "null") {
								Formidable.debugMessage(response);
							}
						} else {
							eval("var oJson=" + response + ";");
							if(bCache) {
								scope.ajaxCache[sUrl] = oJson;
							}
							scope.executeAjaxResponse(oJson, bPersist, bFromCache = false);
							//Dont remove loader, if submitt
							if(typeof(oJson.tasks.object) !='undefined' && oJson.tasks.object) {
								if (oJson.tasks.method == "triggerSubmit" || oJson.tasks.method == "sendToPage") removeLoader = false;
							} else {
								MKWrapper.each(Formidable.objValues(oJson.tasks),function(obj, key){
									if (obj.method == "triggerSubmit" || obj.method == "sendToPage") removeLoader = false;
								});
							}
						}
						if(removeLoader) scope.removeLoader();
						else window.setTimeout(function(){ scope.removeLoader(); },10000);
						
					}, //.bindAsEventListener(this),

					onFailure: function(){
						console.log("Ajax request failed");
					}//.bindAsEventListener(this)
				},
				this
			);
		}
	},
	executeViewState: function(oViewState) {
		$A(oViewState).each(function(oTasks) {
			this.executeAjaxResponse(oTasks, true, false);
		}.bind(this));
	},
	executeAjaxResponse: function(oResponse, bPersist, bFromCache) {
		//this.oCurrentAjaxResponse = oResponse;
		this.executeAjaxAttachHeaders(oResponse.attachheaders);

		try{
			this.executeAjaxInit(oResponse.init);
		} catch(e) {
			// allows catching of unexpected js, for easier debug
			console.log("AJAX INIT - Exception:", e);
		}

		if(oResponse.tasks.object) {
			// it's a single task to execute
			this.executeAjaxTask(oResponse.tasks);
		} else {
			// it's a collection of tasks to execute
//			$H(oResponse.tasks).each(function(value, key) {
			Formidable.objEach(oResponse.tasks, MKWrapper.bind(function(key, task) {
				this.executeAjaxTask(task);
//				this.executeAjaxTask(oResponse.tasks[key]);
			},this));
		}

		try{
			this.executeAjaxAttachEvents(oResponse.attachevents);
		} catch(e) {
			// allows catching of unexpected js, for easier debug
			console.log("AJAX ATTACH EVENTS - Exception:", e);
		}

		try{
			this.executeAjaxInit(oResponse.postinit);
		} catch(e) {
			// allows catching of unexpected js, for easier debug
			console.log("AJAX POST-INIT - Exception:", e);
		}
		if(bPersist) {
			this.updateViewState(oResponse);
		}
	},
	executeAjaxInit: function(oInit) {
		for(var sKey in oInit) {
			//console.log("AJAX initialization:" + oInit[sKey]);
			Formidable.globalEval(oInit[sKey]);
		};
	},
	executeAjaxAttachEvents: function(oAttach) {
		var _this = this;
		for(var sKey in oAttach) {
			//console.log("AJAX attach event:" + oAttach[sKey]);
			Formidable.globalEval(oAttach[sKey]);
		};
	},
	executeAjaxAttachHeaders: function(oAttach) {
		// takes the headers returned by Formidable
			// and tries to dynamically load them in the document
			// this is done via synchronous (a)jax
			// to load resources before using them
			// in the executed event
		// Achtung. In dem String können mehrere Scripte stehen. Diese müssen dann auch einzeln 
		// geladen werden!
		for(var sKey in oAttach) {
			if(Formidable.indexOf(this.aDynHeadersLoaded,oAttach[sKey]) > -1) {
				console.log("AJAX attach header avoided:" + oAttach[sKey]);
			} else if(Formidable.isScriptLoaded(sKey)) {
				console.log("AJAX2 attach header avoided:" + sKey);
			} else {
				var aMatches = oAttach[sKey].match(/src=["|'].+["|']/g);	// js headers only
				
				if(aMatches && aMatches.length > 0) {
					for(var i=0; i<aMatches.length; i++) {
						var sSrc = aMatches[i].substr(5, aMatches[i].length-6);
//					new Ajax.Request(
						MKWrapper.loadScript(sSrc, function(scope) {
								scope = typeof scope != 'undefined' ? scope : this;
								// CHANGE: der Aufruf von globalEval ist in den Prototype-Wrapper verlagert
								scope.aDynHeadersLoaded.push(oAttach[sKey]);
							},
							this
						);
					}
				} 
				// Jetzt noch nach CSS suchen
				aMatches = oAttach[sKey].match(/<link rel=["|']stylesheet["|'] type=["|']text\/css["|'].+href=["|'](.+)["|'] \/>/);	// css headers only
				if(aMatches && aMatches.length > 0) {
					sSrc = aMatches[1];
					Formidable.includeStylesheet(sSrc);
				}	
			}
		};
	},
	executeAjaxTask: function(oTask) {
		
		if(oTask.formid) {
			// execute it on given formid
			var oForm = Formidable.f(oTask.formid);
			if(!oForm) {
				console.log("executeClientEvent: single task: on formid " + oTask.formid + ": No method named " + oTask.method + " on " + oTask.object);
			}
		} else {
			var oForm = this;
		}

		var oObject = oForm.o(oTask.object);
		if(oObject) {
			if(oObject[oTask.method]) {
				//console.log("calling", oTask.method, "on", oTask.object, oObject);
				//this.aParamsStack
				
				oContext = oTask.databag.context || {};
				aParams = oTask.databag.params || {};
				//oContext.sender = oSender;
				//oContext.event = aLocalArguments;

				this.aContextStack.push(oContext);
				this.aParamsStack.push(aParams);
				oObject[oTask.method](oTask.data);
				this.aParamsStack.pop();
				this.aContextStack.pop();
				
			} else {
				console.log("executeAjaxResponse: single task: No method named " + oTask.method + " on " + oTask.object);
			}
		} else {
			console.log("executeAjaxResponse: single task: No object named " + oTask.object);
		}
	},
	initPersistedData: function(oData) {
		for(var key in oData) {
			if(this.o(key)) {
				try {
					this.o(key).rebirth(oData[key]);
				} catch(e) {
					// rebirth not implemented on this object
				}
			}
		}
	},
	rdtIdByName: function(sName) {
		return this.sFormId + "_" + sName;
	},
	submitClear: function(oSender) {
		this.doSubmit(Formidable.SUBMIT_CLEAR, false, oSender || false);
	},
	submitSearch: function(oSender) {
		this.doSubmit(Formidable.SUBMIT_SEARCH, false, oSender || false);
	},
	submitDraft: function(oSender) {
		this.doSubmit(Formidable.SUBMIT_DRAFT, false, oSender || false);
	},
	submitTest: function(oSender) {
		this.doSubmit(Formidable.SUBMIT_TEST, false, oSender || false);
	},
	submitRefresh: function(oSender) {
		this.doSubmit(Formidable.SUBMIT_REFRESH, false, oSender || false);
	},
	submitFull: function(oSender) {
		this.doSubmit(Formidable.SUBMIT_FULL, false, oSender || false);
	},

	submitOnEnter: function(sFromUniqueId, myfield, e) {

		var keycode;
		
		if(window.event) {
			keycode = window.event.keyCode;
		} else if (e) {
			keycode = e.which;
		} else {
			return true;
		}
		
		if(keycode == 13) {
			this.doSubmit(Formidable.SUBMIT_FULL);
			return false;
		} else {
			return true;
		}
	},
	cleanSysFields: function(bAll) {
		$(this.sFormId + "_AMEOSFORMIDABLE_SERVEREVENT").value="";
		$(this.sFormId + "_AMEOSFORMIDABLE_SERVEREVENT_PARAMS").value="";
		$(this.sFormId + "_AMEOSFORMIDABLE_SERVEREVENT_HASH").value="";
		$(this.sFormId + "_AMEOSFORMIDABLE_ADDPOSTVARS").value="";
		if(bAll) {
			if($(this.sFormId + "_AMEOSFORMIDABLE_ENTRYID")) {$(this.sFormId + "_AMEOSFORMIDABLE_ENTRYID").value="";}
			$(this.sFormId + "_AMEOSFORMIDABLE_VIEWSTATE").value="";
			$(this.sFormId + "_AMEOSFORMIDABLE_SUBMITTED").value="";
			$(this.sFormId + "_AMEOSFORMIDABLE_SUBMITTER").value="";
		}
	},
	doSubmit: function(iMode, bServerEvent, oSender) {

		if(!iMode) { iMode = '';}
		if(!bServerEvent) {
			this.cleanSysFields();
		}
		
		if(oSender || (this.getContext() && (oSender = this.getSender()))) {
			
			var aAddVars = {};
			
			if(oSender.isNaturalSubmitter()) {
				aAddVars[oSender.config.namewithoutformid] = iMode;
				this.addFormData(aAddVars);
			} 
			MKWrapper.$(this.sFormId + '_AMEOSFORMIDABLE_SUBMITTER').value=oSender.config.idwithoutformid;
		}

		MKWrapper.$(this.sFormId + "_AMEOSFORMIDABLE_ADDPOSTVARS").value=JSONstring.make(this.aAddPostVars, true);

		// submitting Main form
		MKWrapper.$(this.sFormId + "_AMEOSFORMIDABLE_SUBMITTED").value=iMode;

		if(this.ViewState.length > 0) {
			// saving viewstate
			MKWrapper.$(this.sFormId + "_AMEOSFORMIDABLE_VIEWSTATE").value=JSONstring.make(this.ViewState, true);
		} else {
			MKWrapper.$(this.sFormId + "_AMEOSFORMIDABLE_VIEWSTATE").value="";
		}
		
		this.domNode().submit();
	},
	doNothing: function(oSource) {
		return true;
	},
	scrollTo: function(sName) {
		var oObj = this.o(sName);
		if(oObj) {
			if(typeof oObj.domNode == 'undefined') {
				Element.scrollTo(oObj);
			} else {
				Element.scrollTo(oObj.domNode());
			}
		}
	},
	sendToPage: function(sUrl) {
		this.displayLoader();
		if(sUrl.substr(0,window.location.protocol.length) != window.location.protocol) {
			sUrl =  this.getBaseUrl() + sUrl;
		}
		document.location.href = sUrl;
	},
	getBaseUrl: function(){
		var baseAddr = '';
		if( document.getElementsByTagName ) {
			var elems = document.getElementsByTagName( 'base' );
			if( elems.length ) {
				baseAddr = elems[0].href;
			}
		}
		if(baseAddr == '') {
			baseAddr = window.location.protocol + '//' + window.location.host;
		}
		return baseAddr;
	},	
	openPopup: function(mUrl) {
		if(typeof mUrl["url"] != 'undefined') {
			// it's an array of parameters
			
			var aProps = [];
			var sName = (typeof mUrl['name'] != 'undefined') ? mUrl['name'] : 'noname';

			if(typeof mUrl['menubar'] != 'undefined') {
				aProps.push((mUrl['menubar'] == true) ? 'menubar=yes' : 'menubar=no');
			}

			if(typeof mUrl["status"] != 'undefined') {
				if(mUrl["status"] == true) {
					aProps.push("status=yes");
				} else {
					aProps.push("status=no");
				}
			}

			if(typeof mUrl["scrollbars"] != 'undefined') {
				if(mUrl["scrollbars"] == true) {
					aProps.push("scrollbars=yes");
				} else {
					aProps.push("scrollbars=no");
				}
			}

			if(typeof mUrl["resizable"] != 'undefined') {
				if(mUrl["resizable"] == true) {
					aProps.push("resizable=yes");
				} else {
					aProps.push("resizable=no");
				}
			}

			if(typeof mUrl["width"] != 'undefined') {
				aProps.push("width=" + mUrl["width"]);
			}

			if(typeof mUrl["height"] != 'undefined') {
				aProps.push("height=" + mUrl["height"]);
			}

			window.open(mUrl["url"], sName, aProps.join(", "));
		} else {
			window.open(mUrl);
		}
	},
	toggleDebug: function() {
		var oDiv=$(this.sFormId + '_debugzone');
		
		if(oDiv && oDiv.style.display == 'none'){
			oDiv.style.display='block';
			
			aDivs = document.getElementsByClassName("ameosformidable_debugcontainer_void");
			for(sKey in aDivs) { aDivs[sKey].className = "ameosformidable_debugcontainer";}
			
			aDivs = document.getElementsByClassName("ameosformidable_debughandler_void");
			for(sKey in aDivs) { aDivs[sKey].className = "ameosformidable_debughandler";}

		} else {
			oDiv.style.display='none';
			
			aDivs = document.getElementsByClassName("ameosformidable_debugcontainer");
			for(sKey in aDivs) { aDivs[sKey].className = "ameosformidable_debugcontainer_void";}
			
			aDivs = document.getElementsByClassName("ameosformidable_debughandler");
			for(sKey in aDivs) { aDivs[sKey].className = "ameosformidable_debughandler_void";}
		}
	},
	toggleBacktrace: function(iNumCall) {

		var oDiv = $(this.sFormId + '_formidable_call' + iNumCall + '_backtrace');

		if(oDiv && oDiv.style.display == 'none') {
			oDiv.style.display='block';
		} else {
			oDiv.style.display='none';
		}
	},
	debug: function(sMessage) {

		if(this.oDebugDiv == false) {
			this.oDebugDiv = $div({
				id: this.sFormId + "-majixdebug",
				style: "padding: 5px; border: 2px solid red; background-color: white; height: 500px; overflow: scroll;"
			});
			this.domNode().appendChild(this.oDebugDiv);
		}
		var oDate = new Date();
		var sTime = oDate.getHours() + ":" + oDate.getMinutes() + ":" + oDate.getSeconds();

		this.oDebugDiv.innerHTML =
			"<div style='font-weight: bold; font-size: 20px;'>DEBUG - " + sTime + "</div>" +
			sMessage +
			"<hr style='margin: 20px; padding:0; border: 0; border-top:2px solid black; color: black; '/>" +
			this.oDebugDiv.innerHTML;

		this.scrollTo(this.oDebugDiv.id);
		this.oDebugDiv.scrollTop = 0;
	},
	requestNewI18n: function(aParams) {
		this.cleanSysFields(true);
		this.addPostVar({
			"action": "requestNewI18n",
			"params": aParams
		});
		this.submitClear();
	},
	requestEdition: function(aParams) {
		this.cleanSysFields(true);
		this.addPostVar({
			"action": "requestEdition",
			"params": aParams
		});
		this.submitClear();
	},
	execOnNextPage: function(aTask) {
		this.addPostVar({
			"action": "execOnNextPage",
			"params": aTask
		});
	},
	addFormData: function(aData) {
		this.addPostVar({
			"action": "formData",
			"params": aData
		});
	},
	addPostVar: function(aVar) {
		this.aAddPostVars.push(aVar);
	},
	initLoader: function() {
		var spinner = MKWrapper.$tag('div',{
				style: "position: fixed; left: 50%; top: 50%; margin: 0; padding: 0; z-index: 999999999;",
				id: "tx_mkforms_spinner"
			});
		var spinnerOverlay = MKWrapper.$tag('div',{
			style: "position: fixed; left: 0; top: 0; margin: 0; padding: 0; z-index: 99999999; opacity:0.6; filter: progid:DXImageTransform.Microsoft.Alpha(opacity=60)",
			id: "tx_mkforms_spinner_overlay"
		});
		var spinnerWrap = MKWrapper.$tag('div',{
			style: "position: fixed; z-index: 999999999;",
			id: "tx_mkforms_spinner_wrap"
		});
//		var msg = MKWrapper.$tag('p',{
//			style: "display:none;",
//		});
//		msg.appendChild(document.createTextNode("Loading ..."));
//		spinner.appendChild(msg);
		
		var img = MKWrapper.$tag('img',{
				src: Formidable.path + "res/images/loading.gif"
			});
		spinner.appendChild(img);
		spinnerWrap.appendChild(spinnerOverlay);
		spinnerWrap.appendChild(spinner);
		
		Formidable.Position.fullScreen(spinnerOverlay);
		Formidable.Position.putCenter(spinner);
		
		this.oLoading = spinnerWrap;
	},
	// TODO: Das könnte ins Formidable-Objekt
	displayLoader: function() {
		if(this.oLoading == null)
			this.initLoader();
				
		if(Formidable.Browser.name == "internet explorer") {
			this.oLoading.style.position = "absolute";
			Formidable.Position.putCenter(this.oLoading);
		}

		if(this.Misc.MajixSpinner.left) {
			posLeft = this.Misc.MajixSpinner.left;
			if((parseInt(posLeft) + "") == posLeft) {
				posLeft += "px";
			}

			this.oLoading.style.left = posLeft;
		}

		try {
			document.body.appendChild(this.oLoading);
		} catch(e) {}
	},
	removeLoader: function() {
		try {
			MKWrapper.domRemove(this.oLoading);
			this.oLoading = null;
		} catch(e) {}
	},
	execJs: function(sJs) {
		eval(sJs);
	},
	executeCbMethod: function(aArgs) {
//		aParams = MKWrapper.$H(aArgs.params).values();
		var sClass = aArgs['cb']['class'];
		aParams = {	obj: Formidable.CodeBehind[ sClass ], params: aArgs.params[0] };
		Formidable.CodeBehind[ sClass ][aArgs.method](
			aParams,
			this
		);
	},
	filterKeypress: function(oEv, sPattern) {
		iKeyCode = oEv.keyCode;
		bOk = true;
		var iCharCode = oEv.charCode ? oEv.charCode : oEv.keyCode;
		var sChar = String.fromCharCode(iCharCode);
		
		if(
			([8,9,16,17,18,20,27,37,39,40,46,144].indexOf(iKeyCode) == -1) &&
			!sChar.match(sPattern)
		) {
			bOk = false;
			Event.stop(oEv);
		} else if(iKeyCode == 39 || iKeyCode == 40) {
			if(!Prototype.Browser.Gecko) {
				// On IE and Safari, 39 and 40 are codes given for ' AND (
					// BUT: for firefox, it's left and right arrow
					// BUT2: only firefox triggers keypress for left and right arrow
					// CONCLUSION: we block 39 and 40 for anything else than firefox
				bOk = false;
				Event.stop(oEv);
			}
		}
		
		return bOk;
	},
	declareAjaxService: function(sName, sId, sSafeLock) {
		this.Services[sName] = function() {
			aParams = Array.from(arguments);
			aParams.unshift(sId, sSafeLock);
			this.invokeAjaxService.apply(
				this,
				aParams
			);
		}.bind(this);
	},
	invokeAjaxService: function() {
		aParams = Array.from(arguments);
		sId = aParams.shift();
		sSafeLock = aParams.shift();
		
		if(aParams.length > 0 && typeof aParams.first() == "function") {
			fCbk = aParams.shift();
		} else {
			fCbk = Prototype.emptyFunction;
		}
		
		if(this.AjaxRequestsStack.get(sId)) {
			MKWrapper.ajaxAbort(this.AjaxRequestsStack.get(sId));
//			this.AjaxRequestsStack.get(sId).abort();
			this.AjaxRequestsStack.unset(sId);
		}
		
		var sTrueArgs = JSONstring.make(aParams, true);
		this.displayLoader();
		
		this.AjaxRequestsStack.set(sId, new Ajax.Request(
			this.Misc.Urls.Ajax.service, {
				method:'post',
				parameters: {
					'formid': this.sFormId,
					'safelock': sSafeLock,
					'serviceid': sId,
					'trueargs': sTrueArgs
				},
				onSuccess: function(transport) {
					this.removeLoader();
					eval("var oJson=" + transport.responseText.strip() + ";");
					this.AjaxRequestsStack.unset(sId);
					fCbk(oJson);
				}.bind(this),
				onFailure: function(){
					this.removeLoader();
					this.AjaxRequestsStack.unset(sId);
					console.log("Ajax request failed");
				}.bind(this)
			}
		));
	}
});



Formidable.Classes.RdtBaseClass = Base.extend({
	oForm: null,
	config: {},
	constructor: function(oConfig) {
		this.config = oConfig;
		this.config.userChanged = false;
		this.oForm = Formidable.f(this.config.formid);
		this.userChanged('initEvents');
	},
	doNothing: function() {},
	domNode: function() {
	// TODO: das funktioniert mit jQuery nicht richtig. Warum??
	// Die Punkte stören. JQuery denkt es sind Klassen... -> Punkte raus!!
		return MKWrapper.domNode(this.config.id);
//		return $(this.config.id);
	},
	/*
	 * Ist nur relevant wenn auch angegeben.
	 * <form useUserChange="true" />
	 */
	userChanged: function(b){
		if(!this.oForm.Misc.useUserChange) return;
		if(b=='initEvents') { // events initialisieren
			if(
				typeof(this.domNode()) != 'undefined' && (
					this.domNode().nodeName.toUpperCase() === "INPUT" ||
					this.domNode().nodeName.toUpperCase() === "TEXTAREA" ||
					this.domNode().nodeName.toUpperCase() === "SELECT"
				)
			) {
				MKWrapper.removeEvent(this.domNode(), 'change.userChanged');
				MKWrapper.attachEvent(this.domNode(), 'change.userChanged', function(){
						this.userChanged(true);
					}, this);
			}
			return;
		}
		if(typeof(b) != 'undefined') {
//			if(!b && !isNaN(b)) b = (parseInt(b) !== 0);
			if(typeof(b) == 'string') b = (b.toLowerCase() === 'true');
			else b = Boolean(b);
			this.config.userChanged = b;

			if(b) { // status auf elternelemente übertragen, wenn änderungen gemacht wurden.
				var obj = this.parent();
				if( obj ) obj.userChanged(b);
			}
		}
		return this.config.userChanged;
	},
	rdt: function(sName) {
		return this.oForm.o(this.config._rdts[sName]);
	},
	child: function(sName) {
		return this.rdt(sName);	
	},
	childs: function() {
		if(this.config._rdts) {
			return this.config._rdts;
			return MKWrapper.$H(this.config._rdts);
		}
		return {};
		return MKWrapper.$H({});
	},
	parent: function() {
		if(this.config.parent) {
			return this.oForm.o(this.config.parent);
		}

		return false;
	},
	replaceData: function(sData) {
		this.clearData();
		this.domNode().value = sData;
		this.userChanged(false);
	},
	clearData: function(oData) {
		this.domNode().value = "";
		this.userChanged(false);
	},
	clearValue: function() {
		this.domNode().value = "";
		this.userChanged(false);
	},
	getValue: function() {
		if(this.isIterating()) {
			oResults = {};
			
			oIterator = this.oForm.o(this.config.iterator);
			var aRows = oIterator.getRows();
			var tscope = this;
			MKWrapper.each(aRows, function(oRow) {
				var wid = tscope.getIdWithoutFormIdRelativeTo(oIterator);
				var oRdt = oRow.rdt(wid);
				oResults[oRow.uid] = oRdt.getValue();	
			}, tscope);
			
			return oResults;
		} else {
			if(this.domNode()) {
				var ret = MKWrapper.$F(this.domNode());
				return ret;
			}
		}

		return "";
	},
	getIdWithoutFormIdRelativeTo: function(oParentRdt) {
		sOurs = this.config.idwithoutformid;
		sTheirs = oParentRdt.config.idwithoutformid;
		
		if(sOurs.startsWith(sTheirs)) {
			return sOurs.substr(sTheirs.length + 2);
		}
		
		return sOurs;
	},
	setValue: function(sData) {
		this.clearValue();
		this.domNode().value = sData;
		this.userChanged(false);
	},
	appendValue: function(sData) {
		sValue = this.domNode().value;
		if(sValue != "") {
			this.domNode().value += ", " + sData;
		} else {
			this.domNode().value = sData;
		}
		this.userChanged(false);
	},
	displayBlock: function() {
		if(oDomNode=this.domNode()) {
			MKWrapper.setStyle(oDomNode, {'display':'block'});
			this.displayBlockLabel();
		}
	},
	displayNone: function() {
		if(oDomNode=this.domNode()) {
			MKWrapper.setStyle(oDomNode, {'display':'none'});
			this.displayNoneLabel();
		}
	},
	displayDefault: function() {
		this.domNode().style.display="";
		this.displayDefaultLabel();
	},
	displayNoneLabel: function() {
		if(this.getLabel()) {
			this.getLabel().style.display="none";
		}
	},
	displayBlockLabel: function() {
		if(this.getLabel()) {
			this.getLabel().style.display="block";
		}
	},
	displayDefaultLabel: function() {
		if(this.getLabel()) {
			this.getLabel().style.display="";
		}
	},
	getLabel: function() {
		return MKWrapper.$(this.config.id + '_label');
	},
	getReadonly: function() {
		return MKWrapper.$(this.config.id + '_readonly');
	},
	replaceLabel: function(sLabel) {
		oLabel = this.getLabel();
		if(oLabel) {
			oLabel.innerHTML = sLabel;
		}
	},
	visibleLabel: function() {
		if(this.getLabel()) {
			this.getLabel().style.visibility="visible";
		}
	},
	visible: function() {
		this.visibleLabel();
		this.domNode().style.visibility="visible";
	},
	hiddenLabel: function() {
		if(this.getLabel()) {
			this.getLabel().style.visibility="hidden";
		}
	},
	hidden: function() {
		this.hiddenLabel();
		this.domNode().style.visibility="hidden";
	},
	enable: function() {
		Form.enable(this.config.id);
	},
	disable: function() {
		Form.disable(this.config.id);
	},
	toggleDisplay: function() {
		if(this.domNode().style.display=="none") {
			this.displayBlock();
		} else {
			this.displayNone();
		}
	},
	toggleVisibility: function() {
		if(this.domNode().style.visibility=="hidden") {
			this.visible();
		} else {
			this.hidden();
		}
	},
	focus: function() {
		this.domNode().focus();
	},
	blur: function() {
		this.domNode().blur();
	},
	rebirth: function(oValue) {
		/* none in superclass */
	},
	Fx: function(aParams) {
		if(typeof Scriptaculous!='undefined') {
			
			var sType = typeof(aParams);
			if(sType.toLowerCase() == "string") {
				var aParams = {"effect": aParams, "params": {}};
			}

			if(aParams["params"]["afterFinish"] && (typeof aParams["params"]["afterFinish"] != "function")) {
				aParams["params"]["_afterFinish"] = aParams["params"]["afterFinish"];
				aParams["params"]["afterFinish"] = function() {
					this.oForm.executeAjaxTask(aParams["params"]["_afterFinish"]);
				}.bind(this);
			}

			oLabel = this.getLabel();

			switch(aParams["effect"].toLowerCase()) {
				case "appear":	{
					if(oLabel) {
						new Effect.Parallel([
							new Effect.Appear(this.domNode()),
							new Effect.Appear(oLabel)
						], aParams["params"]);
					} else {
						new Effect.Appear(this.domNode(), aParams["params"]);
					}
					break;
				}
				case "fade": {
					if(oLabel) {
						new Effect.Parallel([
							new Effect.Fade(this.domNode()),
							new Effect.Fade(oLabel)
						], aParams["params"]);
					} else {
						new Effect.Fade(this.domNode(), aParams["params"]);
					}
					break;
				}
				case "puff": { new Effect.Puff(this.domNode(), aParams["params"]); break; }
				case "blinddown": { new Effect.BlindDown(this.domNode(), aParams["params"]); break; }
				case "blindup": { new Effect.BlindUp(this.domNode(), aParams["params"]); break; }
				case "switchoff": { new Effect.SwitchOff(this.domNode(), aParams["params"]); break; }
				case "slidedown": { new Effect.SlideDown(this.domNode(), aParams["params"]); break; }
				case "slideup": { new Effect.SlideUp(this.domNode(), aParams["params"]); break; }
				case "dropout": { new Effect.DropOut(this.domNode(), aParams["params"]); break; }
				case "shake": { new Effect.Shake(this.domNode(), aParams["params"]); break; }
				case "pulsate": { new Effect.Pulsate(this.domNode(), aParams["params"]); break; }
				case "squish": { new Effect.Squish(this.domNode(), aParams["params"]); break; }
				case "fold": { new Effect.Fold(this.domNode(), aParams["params"]); break; }
				case "grow": { new Effect.Grow(this.domNode(), aParams["params"]); break; }
				case "shrink": { new Effect.Shrink(this.domNode(), aParams["params"]); break; }
				case "highlight": { new Effect.Highlight(this.domNode(), aParams["params"]); break; }
				case "toggleappear": { new Effect.toggle(this.domNode(), "appear", aParams["params"]); break; }
				case "toggleslide": { new Effect.toggle(this.domNode(), "slide", aParams["params"]); break; }
				case "toggleblind": { new Effect.toggle(this.domNode(), "blind", aParams["params"]); break; }
				case "scrollto": { new Effect.ScrollTo(this.config.id); break; }
			}
		} else {
			console.log("Scriptaculous is not loaded. Add /meta/libs = scriptaculous to your formidable");
		}
	},
	getMajixThrowerIdentity: function(sObjectId) {
		return sObjectId;
	},
	getParamsForMajix: function(aValues, sEventName, aParams, aRowParams, aLocalArguments) {
		fkt = function(aRow) {
			sKey = aRow.key;
			sValue = aRow.value;
			if(!sKey.startsWith("majix:")) return;
			
			aParts = sKey.split(":");
			oObj = this.oForm.o(aParts[1]);
			if(oObj && typeof(oObj[aParts[2]]) == "function") {
				aValues[sKey] = oObj[aParts[2]]();
			}
		};
		MKWrapper.bind(fkt, this);
		// wir haben in aValues ein Objekt oder ein Array.
		// Wenn Objekt: Über die Attribute soll iteriert werden
		// Wenn es immer nur ein Objekt ist, dann ist das eigentlich sinnlos. Man kann
		// das Objekt direkt übergeben
		var arr = Formidable.obj2Array(aValues);
		MKWrapper.each(arr,fkt);

		return aValues;
	},
	getName: function() {
		return this.config.id.substr(this.config.formid.length + 1);
	},
	repaint: function(sHtml) {
		if(typeof(this.domNode()) == "undefined") return;
		oLabel = this.getLabel(); // entferne label, falls vorhanden
		if(oLabel) { MKWrapper.domRemove(oLabel); }
		oLabel = this.getReadonly(); // entferne span, falls readonly
		if(oLabel) { MKWrapper.domRemove(oLabel); }
		MKWrapper.domReplace(this.domNode(), sHtml);
		this.userChanged(false);
	},
	repaintInner: function(sHtml) {
		if(typeof(this.domNode()) == "undefined") return;
		MKWrapper.domInsert(this.domNode(), sHtml);
		this.userChanged(false);
	},
	remove: function(){
		MKWrapper.domRemove(this.domNode());
		this.userChanged(false);
	},
	attachEvent: function(sEventHandler, fFunc) {
		oObj = this.domNode();
		if(typeof(oObj) != 'undefined' && oObj != null) {
			// Vorheriges Event löschen!
			// Bei ajaxCalls bei denen ein Refresh eines Elements stattfindet
			// und ein Event besitzt, addieren sie die Events.
			// On Clicks werden beispielsweise mehrfach ausgeführt.
			MKWrapper.removeEvent(oObj, sEventHandler+'.fEvent');
			MKWrapper.attachEvent(oObj, sEventHandler+'.fEvent', fFunc, oObj);
		}
	},
	addClass: function(sClass) {
		this.domNode().addClassName(sClass);
	},
	removeClass: function(sClass) {
		this.domNode().removeClassName(sClass);
	},
	removeAllClass: function() {
		this.domNode().classNames().each(function(sClass) {
			this.removeClass(sClass);
		}.bind(this));
	},
	setStyle: function(aStyles) {
		this.domNode().setStyle(aStyles);
	},
	isNaturalSubmitter: function() {
		return (this.domNode().nodeName.toUpperCase() === "SUBMIT") || (
				this.domNode().nodeName.toUpperCase() === "INPUT" && (
					this.domNode().type.toUpperCase() === "SUBMIT" ||
					this.domNode().type.toUpperCase() === "IMAGE"
				)
			);
	},
	removeErrorStatus: function() {

		if(this.config.error) {
			this.removeClass("hasError");
			sType = this.config.error.info.type;
			sTypeClass = "hasError" + sType.substr(0,1).toUpperCase() + sType.substr(1, sType.length);
			this.removeClass(sTypeClass);

			if(this.getLabel()) {
				this.getLabel().removeClassName("hasError");
				this.getLabel().removeClassName(sTypeClass);
			}

			$$("SPAN.rdterror").each(function(oObj) {
				if(oObj.hasClassName(this.config.idwithoutformid)) {
					oObj.style.display='none';
				}
			}.bind(this));

			this.config.error = false;
		}
	},
	setErrorStatus: function(oError) {
		if(!this.config.error) {
			this.addClass("hasError");
			if(!oError.type) {
				oError.type = "undetermined";
			}
			
			if(oError.type) {
				sTypeClass = "hasError" + oError.type.substr(0,1).toUpperCase() + oError.type.substr(1, oError.type);
				this.addClass(sTypeClass);
			}

			if(this.getLabel()) {
				this.getLabel().addClassName("hasError");
				if(oError.type) {
					this.getLabel().addClassName(sTypeClass);
				}
			}

			$$("SPAN.rdterror").each(function(oObj) {
				if(oObj.hasClassName(this.config.idwithoutformid)) {
					oObj.style.display='';
				}
			}.bind(this));

			this.config.error = {
				"info": {
					"type": oError.type
				}
			};
		}
	},
	isIterating: function() {
		return this.config.iterating == true;
	},
	triggerSubmit: function(sMode) {

		if(sMode == "refresh") { this.oForm.submitRefresh(this);}
		else if(sMode == "test") { this.oForm.submitTest(this);}
		else if(sMode == "draft") { this.oForm.submitDraft(this);}
		else if(sMode == "clear") { this.oForm.submitClear(this);}
		else if(sMode == "search") { this.oForm.submitSearch(this);}
		else { this.oForm.submitFull(this); }
	},
	trigger: function() {	/* sWhat[, argument_1, argument_2, ..., argument_n] */
		sWhat = arguments[0];
		
		if(arguments.length > 1) {
			this.oForm.currentTriggeredArguments = Array.from(arguments).slice(1);
			Element.fire(this.domNode(), "formidable:" + sWhat);
			this.oForm.currentTriggeredArguments = false;
		} else {
			this.oForm.currentTriggeredArguments = false;
			Element.fire(this.domNode(), "formidable:" + sWhat);
		}
	}
});

Formidable.Classes.CodeBehindClass = Base.extend({
	config: {},
	constructor: function(oConfig) {
		this.config = oConfig;
		this.oForm = Formidable.f(this.config.formid);
		if(this.init && typeof this.init == "function") {
			this.init();
		}
	}
});