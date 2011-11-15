Formidable.Classes.CheckSingle = Formidable.Classes.RdtBaseClass.extend({
	clearValue: function() {
		this.unCheck();
	},
	clearData: function() {
		this.unCheck();
	},
	check: function() {
		this.domNode().checked = true;
	},
	unCheck: function() {
		this.domNode().checked = false;
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
	}
});