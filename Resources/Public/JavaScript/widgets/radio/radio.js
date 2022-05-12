Formidable.Classes.Radio = Formidable.Classes.RdtBaseClass.extend({
	isParentObj: function() {
		return this.config.bParentObj == true;
	},
	getValue: function() {
		if(this.isParentObj()) {
			for(var k in this.config.radiobuttons) {
				var value = MKWrapper.$F(this.oForm.o(this.config.radiobuttons[k]));
				if(value) return value;
			}
		}
		return '';
	},
	setValue: function(sValue){
		if(this.isParentObj()) {
			for(var k in this.config.radiobuttons) {
				var el = this.oForm.o(this.config.radiobuttons[k]);
				if(el && el.value == sValue) return (el.checked = true);
			}
		}
	},
	clearValue: function(){
		if(this.isParentObj()) {
			for(var k in this.config.radiobuttons) {
				var el = this.oForm.o(this.config.radiobuttons[k]);
				if(el) el.checked = false;
			}
		}
	},
	attachEvent: function(sEventHandler, fFunc) {
		for(var k in this.config.radiobuttons) {
			oObj = this.oForm.o(this.config.radiobuttons[k]);
			MKWrapper.attachEvent(oObj, sEventHandler, fFunc, oObj);
		}
	},
	isNaturalSubmitter: function() {
		return false;
	}
});