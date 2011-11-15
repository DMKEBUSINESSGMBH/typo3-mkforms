Formidable.Classes.ModalBox2 = Formidable.Classes.RdtBaseClass.extend({
	
	constructor: function(config) {
		this.base(config);
	},
	domNode: function() {
		return $(this.box);
	},
	showBox: function(aData){
		
		oTextNode = $div();
		oTextNode.innerHTML = aData.html;
		oOptions = {
			afterLoad: function() {
				//alert("afterLoad");
				for(var sKey in aData.attachevents) {
					Formidable.globalEval(aData.attachevents[sKey]);
				};

				for(var sKey in aData.postinit) {
					Formidable.globalEval(aData.postinit[sKey]);
				};
			},
			beforeHide: function removeObservers() {
				//alert("removeObservers");
			}
		};
		
		oOptions = Object.extend(oOptions, aData || {});
		Modalbox.show(oTextNode, oOptions);
		
		return this;
	},
	closeBox: function(oOptions) {
	/*	console.log(oOptions);
		if(oOptions && oOptions.afterHide) {
			Modalbox.options.afterHide = function() {
				oTempResponse = this.oForm.oCurrentAjaxResponse;
				oTempResponse.tasks = oOptions.afterHide;
				
				this.oForm.executeAjaxResponse(oTempResponse);
				
				Modalbox.options = Modalbox._options;	// reinit modalbox !
			}.bind(this);
		}

	*/	
		Modalbox.hide();
		return false;
	},
	close: function(e) {
		Formidable.f(this.config.formid).o(this.config.id).closeBox();
	},
	resizeToContent: function() {
		Modalbox.resizeToContent();
	},
	resizeToInclude: function(oElement) {
		if(oElement && typeof oElement == "string") {
			oElement = this.oForm.o(oElement);
		}

		if(oElement && oElement.domNode) {
			oNode = oElement.domNode();
		} else {
			oNode = oElement;
		}

		if(oNode) {
			Modalbox.resizeToInclude(oNode);
		}
	},
	repaint: function(sHtml) {
		this.oHtmlContainer.innerHTML = sHtml;
		this.align();
	}
});