Formidable.Classes.CheckBox = Formidable.Classes.RdtBaseClass.extend({
	isParentObj: function() {
		return this.config.bParentObj === true;
	},
	initialize: function(){
		if(this.config.bParentObj && this.config.radioMode) {
			this.radioMode();
		}
		// Bei dem RadioMode werden die Events vor der Bearbeitung der Boxen ausgeführt
		// wir speichern uns die events beim binden ab, starten das radio event und erst danach die anderen events!
		this.executeAttachedEvents();
	},
	getParentObj: function() {
		if(!this.isParentObj()) {
			return this.oForm.o(this.config.parentid);
		} else {
			return this;
		}
	},
	radioMode: function(){
		var oParent = this.getParentObj();
		for(var k in oParent.config.checkboxes) {
			oObj = this.oForm.o(oParent.config.checkboxes[k]);
			MKWrapper.removeEvent(oObj, 'click.mkradiomode');
			MKWrapper.attachEvent(oObj, 'click.mkradiomode', function(){
				var state = this.checked;
				oParent.checkNone();
				this.checked = state;
			}, oObj);
		}
	},
	checkAll: function() {
		var oParent = this.getParentObj();
		for(var k in oParent.config.checkboxes) {
			this.oForm.o(oParent.config.checkboxes[k]).checked = true;
		}
	},
	checkNone: function() {
		var oParent = this.getParentObj();
		for(var k in oParent.config.checkboxes) {
			this.oForm.o(oParent.config.checkboxes[k]).checked = false;
		}
	},
	checkItem: function(sValue) {
		if(this.isParentObj()) {
			for(var k in this.config.checkboxes) {
				var oItem = this.oForm.o(this.config.checkboxes[k]);
				if(oItem.value == sValue) {
					oItem.checked = true;
					break;
				}
			}
		}
	},
	unCheckItem: function(sValue) {
		if(this.isParentObj()) {
			for(var k in this.config.checkboxes) {
				var oItem = this.oForm.o(this.config.checkboxes[k]);
				if(oItem.value == sValue) {
					oItem.checked = false;
					break;
				}
			}
		}
	},
	getValue: function() {
		if(this.isParentObj()) {

			var aValues = [];

			for(var k in this.config.checkboxes) {
				var sValue = MKWrapper.$F(this.oForm.o(this.config.checkboxes[k]));
				if(sValue !== null) {
					aValues[aValues.length] = sValue;
				}
			}
			return aValues;
		} else {
			return MKWrapper.$F(this);
		}
	},
	getMajixThrowerIdentity: function(sObjectId) {
		var oParent = this.getParentObj();

		for(var k in oParent.config.checkboxes) {
			var oItem = this.oForm.o(oParent.config.checkboxes[k]);
			if(oItem.id == sObjectId) {
				return oItem.value;
			}
		}

		return sObjectId;
	},
	aStoredEvents: [],
	attachEvent: function(sEventHandler, fFunc) {
		var event = {
				'event': sEventHandler,
				'func': fFunc
			};
		this.aStoredEvents.push(event);
	},
	executeAttachedEvents: function(){
		var i = 0;
		for(i=0; i < this.aStoredEvents.length; i++){
			var oParent = this.getParentObj();
			for(var k in oParent.config.checkboxes) {
				oObj = this.oForm.o(oParent.config.checkboxes[k]);
				MKWrapper.removeEvent(oObj, this.aStoredEvents[i].event+'.fEvent');
				MKWrapper.attachEvent(oObj, this.aStoredEvents[i].event+'.fEvent',this.aStoredEvents[i].func,oObj);
			}
		}
	},
	getItem: function(sValue) {
		var oParent = this.getParentObj();

		for(var k in oParent.config.checkboxes) {
			var oItem = this.oForm.o(oParent.config.checkboxes[k]);
			if(oItem.value == sValue) {
				return oItem;
			}
		}

		return false;
	},
	disableAll: function() {
		var oParent = this.getParentObj();

		for(var k in oParent.config.checkboxes) {
			var oItem = this.oForm.o(oParent.config.checkboxes[k]);
			oParent.disableItem(oItem.value);
		}
	},
	disableItem: function(sValue) {
		var oCheckBox = this.getItem(sValue);
		if(oCheckBox) {
			oCheckBox.disabled = true;
		}
	},
	enableAll: function() {
		var oParent = this.getParentObj();

		for(var k in oParent.config.checkboxes) {
			var oItem = this.oForm.o(oParent.config.checkboxes[k]);
			oParent.enableItem(oItem.value);
		}
	},
	enableItem: function(sValue) {
		var oCheckBox = this.getItem(sValue);
		if(oCheckBox) {
			oCheckBox.disabled = false;
		}
	}
});