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
			container, 
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
	domNode: function() {
		return MKWrapper.$(this.config.id + '_checkbox');
	},
	// die eigentliche ID liegt auf dem hidden Feld
	realDomNode: function() {
		return MKWrapper.domNode(this.config.id);
	}
});
