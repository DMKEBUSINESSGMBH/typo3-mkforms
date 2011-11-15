Formidable.Classes.DamUpload = Formidable.Classes.RdtBaseClass.extend({
	
	onajaxstart: null,
	onajaxcomplete: null,
	
	constructor: function(oConfig) {
		this.base(oConfig);
	},
	initAjaxUpload: function(button) {
		var fId = this.oForm.sFormId;
		var tscope = this;
		var oConfig = {
			url: this.config.uploadUrl,
			uploadField: this.config.id,
			submitButton: button,
			submitForm:fId,
			onStart: function(){
				if (typeof(tscope.onajaxstart) != 'undefined' && tscope.onajaxstart != null)
						tscope.onajaxstart();
				
				tscope.oForm.displayLoader();
			},
			onComplete: function(oResponse){
				//TODO executeAjaxResponse(oJson, bPersist, bFromCache = false)
				eval("var oJson=" + oResponse + ";");
				tscope.oForm.executeAjaxResponse(oJson, true, false);
				tscope.oForm.removeLoader();
				
				if (typeof(tscope.onajaxcomplete) != 'undefined' && tscope.onajaxcomplete != null)
						tscope.onajaxcomplete();
			}
		};

		MKWrapper.initAjaxUpload(oConfig);
	},
	
	addHandler: function(sHandler, fFunction) {
		switch (sHandler) {
			case 'onajaxstart':
				this.onajaxstart = fFunction;
				break;
			case 'onajaxcomplete':
				this.onajaxcomplete = fFunction;
				break;
		}
	}
	
});

if(!MKWrapper.initAjaxUpload) {
	MKWrapper.initAjaxUpload = function(oConfig) {
		alert('Ajax upload not implemented!');
	}
}