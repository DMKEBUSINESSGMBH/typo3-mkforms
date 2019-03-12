Formidable.Classes.Date = Formidable.Classes.RdtBaseClass.extend({
	constructor: function(oConfig) {
		this.base(oConfig);
	},
	initCal: function() {
		var node = this.domNode();
		if(node) {
			this.config.calendarconf.onUpdate = function(cal) {
				MKWrapper.trigger(node, 'change');
			};
			Calendar.setup(this.config.calendarconf);
			if(node.value != "") {
				this.replaceData(node.value);
			}
		}
	},
	replaceData: function(sValue) {
		if(!this.domNode()) return;
		if(this.config.allowmanualedition) {
			this.setValue(sValue);
		} else {
			if(this.config.converttotimestamp) {
				iTstamp = parseInt(sValue);
				if(iTstamp == 0) {
					this.clearData();
				} else {
					this.setValue(iTstamp);
					if(this.getDisplayArea()) {
						oDate = new Date();
						oDate.setTime(iTstamp * 1000);
						this.getDisplayArea().innerHTML = oDate.print(this.config.calendarconf.daFormat);
					}
				}
			} else {
				this.setValue(sValue);
				if(this.getDisplayArea()) {
					this.getDisplayArea().innerHTML = sValue;
				}
			}
		}
	},
	getDisplayArea: function() {
		oDisplay = MKWrapper.$("showspan_" + this.config.id);
		if(oDisplay) {
			return oDisplay;
		}

		return false;
	},
	clearData: function() {
		if(this.getDisplayArea()) {
			this.getDisplayArea().innerHTML = this.config.emptystring;
		}
		this.domNode().value="";
	},
	clearValue: function() {
		this.clearData();
	}
});
