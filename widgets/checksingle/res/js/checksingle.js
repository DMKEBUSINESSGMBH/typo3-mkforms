Formidable.Classes.CheckSingle = Formidable.Classes.RdtBaseClass.extend({
	initialize: function() {
		var element = this.domNode();
		if (!element) {
			return;
		}
		var event = 'click.redirectCheckToHidden'
		MKWrapper.removeEvent(
			element,
			event
		);
		MKWrapper.attachEvent(
			element, 
			event, 
			this.redirectClickToAssociatedHiddenField, 
			this
		);
	},
	redirectClickToAssociatedHiddenField: function() {
		if (!this.domNode()) {
			return;
		}
		if(this.isChecked()) {
			this.setValue(1);
		} else {
			this.setValue(0);
		}
	},
	clearValue: function() {
		this.unCheck();
	},
	clearData: function() {
		this.unCheck();
	},
	check: function() {
		this.setValue(1);
	},
	unCheck: function() {
		this.setValue(0);
	},
	isChecked: function() {
		return (this.domNode().checked == true);
	},
	getValue: function() {
		if(this.domNode() && !this.isChecked()) {
			return 0;
		}
		
		sRes = this.base();
		
		if(typeof(sRes) != "object") { // we do this, as it might also be an object of values hashed by row-uid when iterating
			return parseInt(sRes);
		}
		
		return sRes;
	},
	setValue: function(value) {
		if(value == 0) {
			this.domNode().checked = false;
			this.realDomNode().value = 0;
		} else {
			this.domNode().checked = true;
			this.realDomNode().value = 1;
		}
	},
	domNode: function() {
		return MKWrapper.$(this.config.id + '_checkbox');
	},
	// die eigentliche ID liegt auf dem hidden Feld
	realDomNode: function() {
		return MKWrapper.domNode(this.config.id);
	}
});
