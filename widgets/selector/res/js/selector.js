Formidable.Classes.Selector = Formidable.Classes.RdtBaseClass.extend({
	updateHidden: function() {
		oSelected = this.oForm.o(this.config.selectedId);
		this.domNode().value = $H(oSelected.getData(true)).values().join(",");
	},
	getParamsForMajix: function(aValues, sEventName, aParams, aRowParams, aLocalArgs) {
		sValues = aValues;	// input is a string
		aValues = {};
		
		aValues["selected"] = $H(this.oForm.o(this.config.selectedId).getData(true)).values();
		aValues["selected_active"] = this.oForm.o(this.config.selectedId).getValue();
		if(!aValues["selected_active"]) {
			aValues["selected_active"] = [];
		}

		aValues["available"] = $H(this.oForm.o(this.config.availableId).getData(true)).values();
		aValues["available_active"] = this.oForm.o(this.config.availableId).getValue();
		if(!aValues["available_active"]) {
			aValues["available_active"] = [];
		}

		return aValues;
	},
	unSelectAll: function() {
		this.oForm.o(this.config.selectedId).setAllSelected();
		this.oForm.o(this.config.selectedId).transferSelectedTo({
			"list": this.oForm.o(this.config.availableId),
			"removeFromSource": true
		});
		this.updateHidden();
	}
});