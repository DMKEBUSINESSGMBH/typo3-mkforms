Formidable.Classes.ModalBox = Formidable.Classes.RdtBaseClass.extend({

	aSelect: [],
	oImgClose: null,
	oHtmlContainer: null,
	defConfig: {
		'effects': true,
		showclosebutton: false,
		followScrollVertical: true,
		followScrollHorizontal: true,
		positionStyle: {
			'display': 'none',
			'position': 'absolute',
			'zIndex': 200000		// z-index, camel case
		},
		style: {
			'width': 'auto'/*,
			'background': 'silver',
			'padding': '10px',
			'borderWidth': '2px',
			'borderStyle': 'solid',
			'borderColor': 'white',
			'MozBorderRadius': '3px'*/	// -moz-border-radius, camel case
		}
	},
	constructor: function(config) {
		this.base(config);
		this.overlay = 'tx_ameosformidable_modalboxoverlay';
		this.box = 'tx_ameosformidable_modalboxbox';
	},
	domNode: function() {
		return MKWrapper.$(this.box);
	},
	hideSelects: function() {

		this.aSelect = [];

		aTemp = $$('body select');
		aTempNoHide = $$('#' + this.box + ' select');

		var _this = this;
		aTemp.each(function(oSelect, k) {
			if(aTempNoHide.indexOf(oSelect) == -1) {
				if(Element.getStyle(oSelect, 'visibility') == '' || Element.getStyle(oSelect, 'visibility') == 'inherit' || Element.getStyle(oSelect, 'visibility') == 'visible') {
					oSelect.style.visibility = 'hidden';
					_this.aSelect.push(oSelect);
				}
			}

		});
	},
	showSelects: function() {

		this.aSelect.each(function(oSelect, k) {

			if(Element.getStyle(oSelect, 'visibility') == 'hidden') {
				oSelect.style.visibility = 'visible';
			}

		});

		this.aSelect = [];
	},
	resizeOverlay: function() {
		MKWrapper.$(this.overlay).style.width = document.body.clientWidth + 'px';
	},
	showBox: function(aData){
		var config = MKWrapper.extend({},this.defConfig);
		this.config = MKWrapper.extend(config, this.config);
		this.config.style = MKWrapper.extend(this.config.style, aData.style || {});

		if(!MKWrapper.$(this.overlay)) {
			document.body.appendChild(
					MKWrapper.$tag('div', {
					id:		this.overlay,
					style:	'display: none; background-color: black; position: absolute; top: 0px; left: 0px; z-index: 100000; width: 100%; height: 100%; padding: 0; margin: 0; opacity:0.6; filter: progid:DXImageTransform.Microsoft.Alpha(opacity=60);'
				})
			);
		}

		if(!MKWrapper.$(this.box)) {

			oDivBox = MKWrapper.$tag('div', {
				id:		this.box
			});

			if(this.config.showclosebutton) {
				this.oImgClose = MKWrapper.$tag('img', {
					src: Formidable.path + 'res/images/modalboxclose.gif',
					style: 'position:absolute; top:-5px; right:-5px; cursor:pointer;'
				});
			}

			oTextNode = MKWrapper.$tag('div', {});
			this.oHtmlContainer = oTextNode;
			MKWrapper.domInsert(oTextNode, aData.html);
//			oTextNode.select('IMG').each(function(o, k) {
			var tscope = this;
			MKWrapper.each(MKWrapper.findChilds(oTextNode,'IMG'), function(o, k) {
				MKWrapper.attachEvent(o, 'load', function() {
					tscope.align();
				}, tscope);
			}, tscope);

			if(this.config.showclosebutton) {
				oDivBox.appendChild(this.oImgClose);
			}
			oDivBox.appendChild(oTextNode);


			document.body.appendChild(oDivBox);

			MKWrapper.setStyle(oDivBox, this.config.style);
			MKWrapper.setStyle(oDivBox, this.config.positionStyle);

			for(var sKey in aData.attachevents) {
				Formidable.globalEval(aData.attachevents[sKey]);
			};
		}

		if(Formidable.Browser.name == 'internet explorer') {
			if(Formidable.Browser.version < 7) {
				this.hideSelects();
			}
			this.resizeOverlay();
		}

//		this.onScrollPointer = this.scroll.bindAsEventListener(this);
//		this.onScrollPointer = MKWrapper.bindAsEventListener(this.scroll, this);
		this.onClosePointer = this.close; // onClosePointer scheint eine zentrale Methode zu sein
		MKWrapper.attachEvent(window, 'scroll', this.scroll, this);


		this.alignFirst();
		var _this = this;
//		var handleCloseButton = MKWrapper.bind(function () {
//			if(this.config.showclosebutton) {
//				MKWrapper.attachEvent(this.oImgClose, 'click', this.onClosePointer, this);
//			}
//		},this);

		if(this.config.effects) {
			MKWrapper.fxAppear(this.box, {}, function() {
				if(_this.config.showclosebutton) {
					MKWrapper.attachEvent(_this.oImgClose, 'click', _this.onClosePointer, _this);
				}
			});
/*
			MKWrapper.fxAppear(this.box, {},
				MKWrapper.bind(function() {
					if(this.config.showclosebutton) {
						MKWrapper.attachEvent(this.oImgClose, 'click', this.onClosePointer);
					}
				},this)
			);
*/
/*
			new Effect.Appear(MKWrapper.$(this.box), {
				duration: 0.5,
				fps: 50,
				afterFinish: MKWrapper.bind(function() {
					if(this.config.showclosebutton) {
						MKWrapper.attachEvent(this.oImgClose, 'click', this.onClosePointer);
					}
				},this)
			});
*/
			MKWrapper.$(this.overlay, false).show();

		} else {
			if(this.config.showclosebutton) {
				MKWrapper.attachEvent(this.oImgClose, 'click', this.onClosePointer);
//				Event.observe(this.oImgClose, 'click', this.onClosePointer);
			}
			MKWrapper.$(this.overlay, false).show();
			MKWrapper.$(this.box, false).show();
		}

		return this;
	},
	closeBox: function() {

		if(this.config.effects) {
			var _this = this;

/*			window.setTimeout(
				function() {
					if(MKWrapper.$(this.overlay)) {
						MKWrapper.$(this.overlay, false).hide();
					}
				}.bind(this),
				250
			);
*/
//			new Effect.Fade(MKWrapper.$(this.box), {
			MKWrapper.fxHide(this.box, {}, function() {
				_this.restoreOnHide();
			});
			MKWrapper.fxHide(this.overlay, {});
		} else {
			MKWrapper.$(this.overlay, false).hide();
			MKWrapper.$(this.box, false).hide();
			this.restoreOnHide();
		}

		return false;
	},
	restoreOnHide: function() {

		if(Formidable.Browser.name == 'internet explorer') {
			if(Formidable.Browser.version < 7) {
				this.showSelects();
			}
		}

		if(MKWrapper.$(this.box)) {
			if(this.config.showclosebutton) {
				MKWrapper.removeEvent(this.oImgClose, 'click', this.onClosePointer);
				MKWrapper.domRemove(this.oImgClose);
			}
			MKWrapper.domRemove(MKWrapper.$(this.box));
		}

		if(MKWrapper.$(this.overlay)) { MKWrapper.domRemove(MKWrapper.$(this.overlay));}

		MKWrapper.removeEvent(window, 'scroll', this.onScrollPointer);
		this.onScrollPointer = null;
		this.onClosePointer = null;
	},
	onScrollPointer: null,
	onClosePointer: null,
	alignFirst: function() {
		Formidable.Position.fullScreen(this.overlay);
		Formidable.Position.putCenterHorizontal(this.box);
		Formidable.Position.putFixedToWindowVertical(this.box, 30);
	},
	align: function() {
		Formidable.Position.fullScreen(MKWrapper.$(this.overlay));
		if(this.config.followScrollVertical) {
			Formidable.Position.putFixedToWindowVertical(this.box, 30);
		}

		Formidable.Position.putCenterHorizontal(this.box);
	},
	scroll: function() {
		Formidable.Position.fullScreen(MKWrapper.$(this.overlay));

		//@TODO Scrollen der Modalbox ist nicht gewünscht,
		// das abschalten in jeder modalbox ist zwar möglich
		// allerdings wird hier ein globaler defaultwert benötigt, der gesetzt werden kann.
		return;

		if(this.config.followScrollVertical) {
			Formidable.Position.putFixedToWindowVertical(this.box, 30);
		}

		if(this.config.followScrollHorizontal) {
			Formidable.Position.putCenterHorizontal(this.box);
		}
	},
	close: function(e) {
		Formidable.f(this.config.formid).o(this.config.id).closeBox();
	},
	repaint: function(sHtml) {
		this.oHtmlContainer.innerHTML = sHtml;
/*		this.oHtmlContainer.select('IMG').each(function(o, k) {
			console.log(o);
			Event.observe(o, 'load', function() {
				this.align();
			}.bind(this));
		}.bind(this));*/
		this.align();
	}
});