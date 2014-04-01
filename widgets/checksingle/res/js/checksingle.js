Formidable.Classes.CheckSingle = Formidable.Classes.RdtBaseClass.extend({
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
		return (this.getCheckSingleDomNode().checked == true);
	},
	getValue: function() {
		if(this.getCheckSingleDomNode() && !this.isChecked()) {
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
			this.getCheckSingleDomNode().checked = false;
			this.domNode().value = 0;
		} else {
			this.getCheckSingleDomNode().checked = true;
			this.domNode().value = 1;
		}
	},
	// die eigentliche ID liegt auf dem hidden Feld
	getCheckSingleDomNode: function() {
		return MKWrapper.$(this.config.id + '_checkbox');
	},
	constructor: function(oConfig) {
		this.base(oConfig);
		if (typeof(this.domNode()) == "undefined") return;
		this.initialize(this.domNode(), this.config);
	},
	initialize: function(container, options) {
		MKWrapper.attachEvent(
			this.getCheckSingleDomNode(), 
			'click', 
			this.redirectClickToAssociatedHiddenField, 
			this
		);
	},
	
	redirectClickToAssociatedHiddenField: function() {
		if(this.isChecked()) {
			this.setValue(1);
		} else {
			this.setValue(0);
		}
	},
});
