Formidable.Classes.Accordion = Formidable.Classes.RdtBaseClass.extend({
	oAccordion: null,
	aHandlers: {
		"ontabopen": MKWrapper.$A(),
		"ontabclose": MKWrapper.$A(),
		"ontabchange": MKWrapper.$A()
	},
	constructor: function(oConfig) {
		this.base(oConfig);
		this.config.libconf["parent"] = this;
		this.oAccordion = MKWrapper.extend({}, accordion);
		if (typeof(this.domNode()) == "undefined") return;
		this.oAccordion.initialize(this.domNode(), this.config.libconf);
	},
	setActiveTab: function(sTab) {
		this.oAccordion.activate(
			this.rdt(sTab).domNode()
		);
	},
	close: function(sTab) {
		alert('TODO, set active'+sTab);
	},
	next: function() {
		if(this.getCurrent()) {
			var iCurKey = this.oAccordion.accordions.indexOf(this.getCurrent().config.id);
			if(this.oAccordion.accordions[iCurKey+1]) {
				this.setActiveTab(this.oForm.o(this.oAccordion.accordions[iCurKey+1]).config.localname);
			}
		} else {
			this.first();
		}
	},
	previous: function() {
		if(this.getCurrent()) {
			var iCurKey = this.oAccordion.accordions.indexOf(this.getCurrent().config.id);
			if(this.oAccordion.accordions[iCurKey-1]) {
				this.setActiveTab(this.oForm.o(this.oAccordion.accordions[iCurKey-1]).config.localname);
			}
		} else {
			this.last();
		}
	},
	first: function() {
		this.setActiveTab(
			this.oForm.o(this.oAccordion.accordions[0]).config.localname
		);
	},
	last: function() {
		this.setActiveTab(
			this.oForm.o(this.oAccordion.accordions[this.oAccordion.accordions.lenght]).config.localname
		);
	},
	getCurrent: function() {
		
		if(this.oAccordion.currentAccordion) {
			oCurrentBox = $(this.oAccordion.currentAccordion.id);
			oCurrentAccordion = oCurrentBox.previous(0);
			return this.oForm.o(oCurrentAccordion.id);
		}
		
		return false;
	},
	addHandler: function(sHandler, fFunction) {
		this.aHandlers[sHandler].push(fFunction);
	},
	onTabOpen_eventHandler: function(sTabName) {
		MKWrapper.each(this.aHandlers["ontabopen"], function(fFunc, iKey) {
			fFunc(sTabName);
		});
	},
	onTabClose_eventHandler: function(sTabName) {
		MKWrapper.each(this.aHandlers["ontabclose"], function(fFunc, iKey) {
			fFunc(sTabName);
		});
	},
	onTabChange_eventHandler: function(sTabName, sAction) {
		MKWrapper.each(this.aHandlers["ontabchange"], function(fFunc, iKey) {
			fFunc(sTabName, sAction);
		});
	},
	getParamsForMajix: function(aValues, sEventName, aParams, aRowParams, aLocalArgs) {

		aValues = this.base(aValues, sEventName, aParams, aRowParams, aLocalArgs);

		aValues["sys_event"] = {};
		if(sEventName == "ontabopen" || sEventName == "ontabclose" || sEventName == "ontabchange") {
			aValues["sys_event"].tab = aLocalArgs[0];
		}

		if(sEventName == "ontabchange") {
			aValues["sys_event"].action = aLocalArgs[1];
		}

		return aValues;
	}
});