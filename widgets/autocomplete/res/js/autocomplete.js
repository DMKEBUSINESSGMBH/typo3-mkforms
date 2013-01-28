Formidable.Classes.Autocomplete = Formidable.Classes.RdtBaseClass.extend({
	
	oText: null,
	oList: null,
	oLoader: null,
	iLoaderCount: 0,
	onListSelect: null,
	itemSelected: null,

	constructor: function(oConfig) {
		this.base(oConfig);
//		if (this.domNode()) {
//			this.initialize();
//			this.initStartPosition();
//			//this.execEvents(); //wird nach dem addScript geladen
//			this.addScript(this, oConfig.jsExtend);
//		}
	},

	initialize: function() {
		if (!this.domNode()) return;
		this.config.counter = 0;
		this.config.isDbEntry = false;

		var aStyles = {};
		if (this.config.item.width) {
			aStyles["width"] = this.config.item.width + "px";
		}
		if (this.config.item.height) {
			aStyles["height"] = this.config.item.height + "px";
		}
		if (this.config.item.style) {
			var aCustomStyles = this.config.item.style.split(";");
			for (var i=0; i<aCustomStyles.length; ++i) {
				if (!aCustomStyles[i].strip().empty()) {
					var aCustomStyle = aCustomStyles[i].strip().split(":");
					aStyles[aCustomStyle[0].strip().camelize()] = aCustomStyle[1].strip();
				}
			}
		}
		this.config.item.style = aStyles;

		var id = MKWrapper.id(this.domNode());
		
		this.oText 		= MKWrapper.$(id);
		this.oList 		= MKWrapper.$(id + Formidable.SEP + 'list');
		this.oLoader 	= MKWrapper.$(id + Formidable.SEP + 'loader');
		
		this.oText.parentNode.insertBefore(this.oLoader, this.oList);
		
//		MKWrapper.setStyle(this.oList, {
//			"width": "0px",
//			"height": "0px"
//		});
		
		this.initStartPosition();
		this.addScript(this);
	},
	
	initStartPosition: function() {
		// behebt darstellungsprobleme im IE
		if(MKWrapper.isIE(7)){
			var oParent = MKWrapper.parent(this.oList);
			MKWrapper.setStyle(oParent,{"height": MKWrapper.getDimensions(oParent).height });
			MKWrapper.each(
			    MKWrapper.findChilds(oParent),
			    function(el){
			    	MKWrapper.setStyle(el,{"position": "relative"});
			    }
			);
		}
		
		MKWrapper.setStyle(MKWrapper.parent(this.oList),{"position": "relative"});
		MKWrapper.setStyle(this.oList,{"position": "absolute"});
		MKWrapper.setStyle(this.oLoader, {'display':'block', 'height': '16px','width':'16px','background': 'transparent url('+Formidable.path+'widgets/autocomplete/res/img/loader.gif) no-repeat scroll 0 0'});
		var aToHide = MKWrapper.showParents(this.oList);
		MKWrapper.clonePosition(
			this.oList,
			this.oText, {
				setLeft: true,
				setTop: true,
				setWidth: false,
				setHeight: false,
				offsetLeft: 0,
				offsetTop: MKWrapper.getDimensions(this.oText, 'outermargin').height
			}
		);
		MKWrapper.clonePosition(
				this.oLoader,
				this.oText, {
					setLeft: true,
					setTop: true,
					setWidth: false,
					setHeight: false,
					offsetLeft: MKWrapper.getDimensions(this.oText, 'outermargin').width,
					offsetTop: 1
				}
			);
		MKWrapper.setStyle(this.oLoader,{"position": "absolute", 'display':'none'});
		
		MKWrapper.hideElements(aToHide);
	},
	
	execEvents: function() {
		this.oText.oObserver = MKWrapper.delayedObserver( //new Form.Element.Observer
				this.oText,
				this.config.timeObserver,
				this.execAjaxRequest.bind(this)
		);
		MKWrapper.attachEvent(this.oText, 'keydown', this.textChange, this);
		if(this.config.selectionRequired) {
			MKWrapper.attachEvent(this.oText, 'blur', this.checkSelection, this);
		}
		else if(this.config.hideItemListOnLeave) {
			MKWrapper.attachEvent(document.getElementsByTagName('body')[0], 'click', this.hideItemListOnLeave, this);
		}
	},
	loaderShow: function(){
		MKWrapper.setStyle(this.oLoader,{"display": "block"});
		this.iLoaderCount++;
	},
	loaderHide: function(){
		this.iLoaderCount--;
		if(this.iLoaderCount === 0)
			MKWrapper.setStyle(this.oLoader,{"display": "none"});
	},
	addScript: function(obj) {
		var sScript = this.config.jsExtend;
		if(sScript !== false) {
			window.setTimeout(function(){
				sScript = sScript.split(':');
				if(!obj.oForm.isAbsUrl(sScript[0])) {
					sScript[0] = obj.oForm.getBaseUrl() + sScript[0];
				}
				MKWrapper.loadScript(sScript[0], function(){
					Formidable.Classes.Autocomplete[sScript[1]](obj, sScript[2]);
					//events laden
					obj.execEvents();
				});
			},500);
		} else {
			//events laden
			obj.execEvents();
		}
	},
	
	execAjaxRequest: function() {

		if(this.itemSelected === true) {
			this.itemSelected = null;
			return;
		}
		var obj = this;		// save the current object for later use
		var sText = MKWrapper.$F(obj.oText);
	
		//TODO make configurable
		// hide the list of choices
		//obj.hideItemList(obj);

		// if there is no search or only one sign, than instantly exit
		if (sText.length < 2) {
			obj.hideItemList(obj);
			return;
		}
		

		// if there is a search, then execute an AJAX event to the server
		// execute only the last search, using a global counter
		obj.config.counter++;
		obj.loaderShow();
		MKWrapper.ajaxCall(
			obj.config.searchUrl, {
				'method':'post',
				'asynchronous':false,
				'parameters':{
						'searchType': obj.config.searchType,
						'searchText': sText,
						'searchCounter': obj.config.counter
					},
				'onSuccess': 	function (oResponse) {
					var sJSONtext = oResponse;
					if (!MKWrapper.isJSON(sJSONtext)) return;
					var aJSON = MKWrapper.evalJSON(sJSONtext, true);
					if(aJSON.tasks.counter == obj.config.counter) {
						obj.executeAjaxTasks(aJSON.tasks);
					}
					obj.loaderHide();
				}
			}
		);
	},
	executeAjaxTasks: function(tasks) {
		if (tasks.results > 0 && document.activeElement===this.domNode()) {
			this.generateItemList(this, Formidable.objValues(tasks.html));
		} else {
			// TODO: was passiert mit der liste,
			// wenn bei weiterer eingabe keine übereinstimmung gefunden wurde!?
			// Wir entfernen Sie erstmal!
			this.hideItemList(this);
		}
	},
	textChange: function(event) {
		if (event.keyCode == 40) {//Down
			if(this.oList.style.visibility === 'visible'){
				this.itemSelect(this, MKWrapper.next(this.itemSelected));
			}
		}
		if (event.keyCode == 38) {//Up
			if(this.oList.style.visibility === 'visible'){
				this.itemSelect(this, MKWrapper.previous(this.itemSelected));
			}
		}
		if (event.keyCode == 13) { //Return
			if(!this.config.isDbEntry && this.itemSelected !== null) {
				// aktuellen eintrag wählen
				this.listSelect(this, this.itemSelected);
				this.enableButtons();
				this.itemSelected = true;
				return false;
			}
			return true;
		}
		if (event.keyCode == 27 || event.keyCode == 9) { //ESC || Tab
			var obj = this;
			if(!this.config.isDbEntry && this.config.selectionRequired) {
				if(typeof(obj.onlistselect) != 'undefined' && obj.onlistselect !== null) obj.onlistselect('');
				setTimeout(function() { obj.oText.value = ''; }, 50);
			}
			if(this.oList.style.visibility === 'visible') this.hideItemList(this);
			return;
		}
		this.setDbEntryState(false);
	},
	checkSelection: function(event) {
		var obj = this;
		//nur prüfen, wenn die dropdown liste geöffnet ist
		if(obj.oList.style.visibility === 'visible') {
			if(!obj.oText.value.length) {
				// Liste ausblenden wenn kein wert angegeben
				obj.hideItemList(obj);
			} else{
				// Focus behält das Textfeld wenn dropdowsnliste aktiv
				window.setTimeout(function(){obj.oText.focus();},10);
			}
		}
	},
	
	itemSelect: function(obj, element) {
		if(typeof(element) == 'undefined') return false;
		MKWrapper.removeClass(obj.itemSelected, obj.config.selectedItemClass);
		MKWrapper.addClass(element, obj.config.selectedItemClass);
		obj.itemSelected = element;
	},
	
	listSelect: function(obj, element) {
		var sText = MKWrapper.stripTags(element.innerHTML);
		sText = sText.replace(/\s+/g, " ").replace(/^\s+|\s+$/, "");
		//jQuery gibt hier kein Event zurück!?
		//obj.oText.oObserver.lastValue = sText;
		obj.setDbEntryState(true);
		obj.oText.value = sText;
		obj.hideItemList(obj);
		if(typeof(obj.onlistselect) != 'undefined' && obj.onlistselect !== null) obj.onlistselect(sText);
	},
	
	generateItemList: function(obj, aHtml) {

		// set the text before and after the list
		var last = aHtml[1] ? aHtml[1] : '<!-- last -->';
		obj.oList.innerHTML = aHtml[0] + last;

		MKWrapper.each(Formidable.objValues(aHtml[2]),
			function(sValue, sKey) {
				var oElement = MKWrapper.$tag('div', {});
//				oElement = new Element('div');
				oElement.className = obj.config.item['class'];
				MKWrapper.setStyle(oElement, obj.config.item.style);
				oElement.innerHTML = sValue;
				if(sKey === 0) {
					obj.itemSelected = oElement;
				}

				MKWrapper.attachEvent(oElement, 'mouseover', function() {
					obj.itemSelect(obj, this);
				}, oElement);

				MKWrapper.attachEvent(oElement, 'click', function() {
					obj.listSelect(obj, this);
				}, oElement);

				obj.oList.insertBefore(oElement, obj.oList.lastChild);
			}
		);

		MKWrapper.setStyle(obj.oList, {
			"width": 'auto',
			"height": 'auto'
		});
		var iWidth = 0;
		var iHeight = 0;
//		var aChilds = obj.oList.childElements();
		var aChilds = MKWrapper.findChilds(obj.oList);
		for (var i=0, len=aChilds.length; i<len; ++i) {
			var oDim = MKWrapper.getDimensions(aChilds[i]);
			iWidth = (oDim.width > iWidth) ? oDim.width : iWidth;
			iHeight += oDim.height;

			MKWrapper.attachEvent(aChilds[i], 'mouseover', function() {
				MKWrapper.setStyle((this), {'visibility': 'visible'});
			});
			MKWrapper.attachEvent(aChilds[i], 'mouseout', function() {
				MKWrapper.setStyle((this), {'visibility': 'hidden'});
			});
		}
		MKWrapper.setStyle(obj.oList, {
			"width": iWidth + "px",
			"height": iHeight + "px"
		});

		this.showItemList(obj);		
	},
	
	disableButtons: function() {
		this.oForm.disableButtons();
	},
	
	enableButtons: function() {
		this.oForm.enableButtons();
	},
	
	setDbEntryState: function(b) {
		this.config.isDbEntry = b;
	},
	
	showItemList: function(obj) {
		MKWrapper.setStyle(MKWrapper.parent(obj.oList), {'zIndex': '20000'});
		MKWrapper.setStyle((obj.oList), {'zIndex': '30000'});
		obj.oList.style.visibility = 'visible';
		obj.oText.focus();
		if(this.config.selectionRequired) this.disableButtons();
		obj.itemSelect(obj, obj.itemSelected);
	},
	
	hideItemListOnLeave: function() {
		this.hideItemList(this);
	},
	
	hideItemList: function(obj) {
		obj.oList.innerHTML = "";
		MKWrapper.setStyle(MKWrapper.parent(obj.oList), {'zIndex': '10000'});
		MKWrapper.setStyle((obj.oList), {'zIndex': '0'});
		obj.oList.style.visibility = 'hidden';
		if(this.config.selectionRequired) this.enableButtons();
		obj.itemSelected = null;
	},
	
	addHandler: function(sHandler, fFunction) {
		switch (sHandler) {
			case 'onlistselect':
				this.onlistselect = fFunction;
				break;
		}
	}

});