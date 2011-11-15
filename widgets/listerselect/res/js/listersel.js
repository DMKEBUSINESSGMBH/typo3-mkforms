Formidable.Classes.ListerSel = Formidable.Classes.RdtBaseClass.extend({
	isParentObj: function() {
		// TODO: Das kann für die CheckBoxen noch notwendig sein...
		return this.config.bParentObj == true;
	},
	getValue: function() {
		for(var k in this.config.radiobuttons) {
			var value = MKWrapper.$F(this.oForm.o(this.config.radiobuttons[k]));
			if(value) return value;
		}
		return '';
	},
	attachEvent: function(sEventHandler, fFunc) {
		var radiobuttons = this.config.radiobuttons;
		if(typeof(radiobuttons) === 'undefined') {
			var radiobuttons = this.parent().rdt( this.config.localname ).config.radiobuttons;
		}
		for(var k in radiobuttons) {
			oObj = this.oForm.o(radiobuttons[k]);
			// Vorheriges Event löschen!
			// Bei ajaxCalls bei denen ein Refresh eines Elements stattfindet
			// und ein Event besitzt, addieren sie die Events.
			// On Clicks werden beispielsweise mehrfach ausgeführt.
			MKWrapper.removeEvent(oObj, sEventHandler+'.fEvent');
			MKWrapper.attachEvent(oObj, sEventHandler+'.fEvent', fFunc, oObj);
//			MKWrapper.attachEvent(oObj, sEventHandler, fFunc, oObj);
		}
	},
	isNaturalSubmitter: function() {
		return false;
	}
});