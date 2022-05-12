Formidable.Classes.Captcha = Formidable.Classes.RdtBaseClass.extend({
	oCaptcha: false,
	oReload: false,
	oInput: false,
	constructor: function(oConfig) {
		this.base(oConfig);

		if(oCaptcha = MKWrapper.$(this.config.id + 'img')) {
			this.oCaptcha = oCaptcha;
		}

		if(oReload = MKWrapper.$(this.config.id + '_reload')) {
			this.oReload = oReload;
			MKWrapper.attachEvent(this.oReload, "click", this.reload, this);
		}

		if(oInput = MKWrapper.$(this.config.id)) {
			this.oInput = oInput;
		}
	},
	reload: function() {
		if(this.oCaptcha) {
			this.oCaptcha.src = this.config.reloadurl + "&amp;" + Math.round(Math.random(0)*1000)+1;
		}
	}
});