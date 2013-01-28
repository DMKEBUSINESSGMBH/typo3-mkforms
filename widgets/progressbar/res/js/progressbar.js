Formidable.Classes.ProgressBar = Formidable.Classes.RdtBaseClass.extend({
	fValue: null,
	fPercent: null,
	sCurrentClass: null,
	constructor: function(oConfig) {
		this.base(oConfig);
		this.config.min = parseFloat(this.config.min);
		this.config.max = parseFloat(this.config.max);
		this.config.precision = parseFloat(this.config.precision);
		this.config.value = parseFloat(this.config.value);

		if(this.config.width) {
			this.config.width = parseInt(this.config.width);
		}

		this.setValue(this.config.value);
	},
	getStep: function(iValue) {
		for(var sKey in this.config.steps) {
			oStep = this.config.steps[sKey];
			if(oStep.value <= iValue) {
				return oStep;
			}
		}

		return false;
	},
	setPercent: function(iPercent, oStep) {
		this.fPercent = iPercent;
		sClass = sLabel = "";

		if(oStep) {
			if(oStep.className) {
				sClass = oStep.className;
			}
			if(oStep.label) {
				sLabel = oStep.label;
			}
		} else {
			sLabel = iPercent + "%";
		}
		
		if(this.config.width) {
			iTargetWidth = parseInt(((this.config.width * iPercent) / 100));
			sWidth = iTargetWidth + "px";
		} else {
			sWidth = iPercent + "%";
		}

		//this.visible();

		if(this.config.effects) {
			if(this.oEffect) {
				this.oEffect.cancel();
			}

			this.oEffect = new Effect.Morph(
				this.domNode(), {
					style: {
						width: sWidth
					},
					duration: 0.1,
					afterFinish: function() {
						if(iPercent == 0) {
							this.setHtml("");
							this.displayNone();
						} else {
							this.displayDefault();
							this.setHtml(sLabel);
						}
						if(this.sCurrentClass) {
							this.removeClass(this.sCurrentClass);
						}

						if(sClass) {
							this.sCurrentClass = sClass;
							this.addClass(this.sCurrentClass);
						}
					}.bind(this)
				}
			);
		} else {
			this.domNode().style.width = sWidth;
			this.setHtml(sLabel);
		}
	},
	getPercentForValue: function(mValue) {
		if(mValue > this.config.max) {
			//mValue = this.config.max;
			return 100;
		}
		
		if(mValue < this.config.min) {
			//mValue = this.config.min;
			return 0;
		}

		if((this.config.max - this.config.min) == 0) {
			return 0;
		}

		return (100/(this.config.max - this.config.min)) * mValue;
	},
	setValue: function(mValue) {
		this.fValue = mValue;
		this.setPercent(
			this.getPercentForValue(mValue),
			this.getStep(mValue)
		);
	},
	getLabelObject: function() {
		// using childnodes because safari seems to not support .select() correctly
		return this.domNode().childNodes[0];	
	},
	setHtml: function(sHtml) {
		this.getLabelObject().innerHTML = sHtml;
	}
});
