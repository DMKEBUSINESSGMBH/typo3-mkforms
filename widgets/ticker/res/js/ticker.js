Formidable.Classes.Ticker = Formidable.Classes.RdtBaseClass.extend({

	iWidth: 0,
	iHeight: 0,

	stopScrolling: false,

	constructor: function(oConfig) {
		this.base(oConfig);
		if (this.domNode()) {
			this.setItemDimensions();
			this.setStartPosition();
			this.setEvents();
			setTimeout(this.execScroll.bind(this), this.config.scroll.startDelay);
		}
	},

	setItemDimensions: function() {
		var aStyles = {};
		if (this.config.item.style) {
			var aCustomStyles = this.config.item.style.split(";");
			for (var i=0, len=aCustomStyles.length; i<len; ++i) {
				if (!aCustomStyles[i].strip().empty()) {
					var aCustomStyle = aCustomStyles[i].strip().split(":");
					aStyles[aCustomStyle[0].strip().camelize()] = aCustomStyle[1].strip();
				}
			}
		}
		if (this.config.scroll.mode == "horizontal") {
			aStyles["float"] = "left";
			//aStyles["display"] = "block";
		}
		if (this.config.item.width) {
			aStyles["width"] = this.config.item.width + "px";
		}
		if (this.config.item.height) {
			aStyles["height"] = this.config.item.height + "px";
		}

		var aBox1Elements = $(this.domNode().id + '.1').childElements();
		var aBox2Elements = $(this.domNode().id + '.2').childElements();

		for (var i=0, len=aBox1Elements.length; i<len; ++i) {

			if (aBox1Elements[i].id != this.domNode().id + ".clear") {

				if (aBox1Elements[i].className == "ameosformidable-rdtticker-item") {
					aBox1Elements[i].setStyle(aStyles);
					aBox2Elements[i].setStyle(aStyles);
				} else if (this.config.scroll.mode == "horizontal") {
					aBox1Elements[i].setStyle({
						float: "left"
					});
					aBox2Elements[i].setStyle({
						float: "left"
					});
				}

				switch (this.config.scroll.mode) {
				case "horizontal" :
					if (!isNaN(parseInt(aBox1Elements[i].style.width))) {
						this.iWidth += parseInt(aBox1Elements[i].style.width);
					} else {
						this.iWidth += parseInt(aBox1Elements[i].getWidth());
					}
					this.iHeight = (aBox1Elements[0].style.height) ? parseInt(aBox1Elements[0].style.height) : this.config.height;
					break;
				case "vertical" :
					this.iWidth = (aBox1Elements[0].style.width) ? parseInt(aBox1Elements[0].style.width) : this.config.width;
					if (aBox1Elements[i].id != this.domNode().id + ".clear") {
						if (!isNaN(parseInt(aBox1Elements[i].style.height))) {
							this.iHeight += parseInt(aBox1Elements[i].style.height);
						} else {
							this.iHeight += parseInt(aBox1Elements[i].getHeight());
						}
					}
					break;
				}
			}
		}
	},

	setStartPosition: function() {

		Element.cleanWhitespace($(this.domNode().id + '.1'));
		Element.cleanWhitespace($(this.domNode().id + '.2'));


		var aParentStyle = {};
		aParentStyle['overflow'] = this.config.scroll.overflow;
		aParentStyle['display'] = "block";
		aParentStyle['width'] = this.config.width + "px";
		aParentStyle['height'] = this.config.height + "px";
		if (this.config.bordercolor && this.config.border == "none") {
			aParentStyle['border'] = "1px solid " + this.config.bordercolor;
		} else if (this.config.border) {
			aParentStyle['border'] = this.config.border;
		}
		if (this.config.bgcolor && this.config.background == "none") {
			aParentStyle['backgroundColor'] = this.config.bgcolor;
		} else if (this.config.background) {
			aParentStyle['background'] = this.config.background;
		}
		$(this.domNode().id).up(0).setStyle(aParentStyle);


		var aParentStyle = {};
		aParentStyle['overflow'] = this.config.scroll.overflow;
		switch (this.config.scroll.mode) {
		case "horizontal" :
			aParentStyle['width'] = (this.iWidth * 2) + "px";
			aParentStyle['height'] = this.iHeight + "px";
			break;
		case "vertical":
			aParentStyle['width'] = this.iWidth + "px";
			aParentStyle['height'] = (this.iHeight * 2) + "px";
			break;
		}
		aParentStyle['top'] = this.config.offset.top + "px";
		aParentStyle['left'] = this.config.offset.left + "px";
		$(this.domNode().id).setStyle(aParentStyle);


		var aBoxStyle = {};
		aBoxStyle['position'] = "absolute";
		//aBoxStyle['top'] = this.config.offset.top + "px";
		//aBoxStyle['left'] = this.config.offset.left + "px";
		aBoxStyle['top'] = 0 + "px";
		aBoxStyle['left'] = 0 + "px";
		if (this.iWidth) {
			aBoxStyle['width'] = this.iWidth + "px";
		}
		if (this.iHeight) {
			aBoxStyle['height'] = this.iHeight + "px";
		}
		$(this.domNode().id + '.1').setStyle(aBoxStyle);
		$(this.domNode().id + '.2').setStyle(aBoxStyle);

		
		Element.makePositioned($(this.domNode().id).up(0));
		Element.makePositioned($(this.domNode().id));
		Element.absolutize($(this.domNode().id + '.1'));


		var aOptions = {};
		aOptions['setLeft'] = true;
		aOptions['setTop'] = true;
		aOptions['setWidth'] = true;
		aOptions['setHeight'] = true;

		switch (this.config.scroll.mode) {
		case "horizontal" :
			aOptions['offsetLeft'] = (this.config.scroll.direction == "right") ? (-1) * this.iWidth : this.iWidth;
			aOptions['offsetTop'] = 0;
			break;
		case "vertical" :
			aOptions['offsetLeft'] = 0;
			aOptions['offsetTop'] = (this.config.scroll.direction == "bottom") ? (-1) * this.iHeight : this.iHeight;
			break;
		}

		Element.clonePosition(
			$(this.domNode().id + '.2'),
			$(this.domNode().id + '.1'), 
			aOptions
		);


		// if it was set a padding bottom, then we must generate it manually
		var iPaddingBottom = parseInt($(this.domNode().id).up(0).getStyle("padding-bottom"));
		if (iPaddingBottom != 0) {
			var aStyles = new Array();
			aStyles[0] = 'overflow:' + this.config.scroll.overflow;
			aStyles[1] = 'z-index:' + "20";
			aStyles[2] = 'background-color:' + $(this.domNode().id).up(0).getStyle("background-color");
			aStyles[3] = 'position:' + "relative";
			aStyles[4] = 'display:' + "block";
			aStyles[5] = 'width:' + this.config.width + "px";
			aStyles[6] = 'height:' + this.config.height + "px";

			var sNewElement = $(this.domNode().id).up(0).innerHTML;
			sNewElement = '<div style="' + aStyles.join(";") + '">' + sNewElement + '</div>';
			$(this.domNode().id).up(0).innerHTML = sNewElement;
		}
	},

	setEvents: function() {

		Event.observe($(this.domNode().id), 'mouseover', function() {
			this.stopScrolling = true;
		}.bind(this));

		Event.observe($(this.domNode().id), 'mouseout', function() {
			this.stopScrolling = false;
		}.bind(this));

	},

	execScroll: function() {

		if (this.stopScrolling == false && this.config.scroll.stop == false) {

			switch (this.config.scroll.mode) {

			case "horizontal" :

				var iLeftBox1 = parseInt($(this.domNode().id + '.1').style.left);
				var iLeftBox2 = parseInt($(this.domNode().id + '.2').style.left);

				var iNewLeftBox1 = iLeftBox1 + parseInt(this.config.scroll.amount);
				var iNewLeftBox2 = iLeftBox2 + parseInt(this.config.scroll.amount);

				$(this.domNode().id + '.1').style.left = iNewLeftBox1 + "px";
				$(this.domNode().id + '.2').style.left = iNewLeftBox2 + "px";

				switch (this.config.scroll.direction) {
				case "left":
					if ((-1) * iNewLeftBox1 > this.iWidth) {
						$(this.domNode().id + '.1').style.left = (iNewLeftBox2 + this.iWidth) + "px";
					}
					if ((-1) * iNewLeftBox2 > this.iWidth) {
						$(this.domNode().id + '.2').style.left = (iNewLeftBox1 + this.iWidth) + "px";
					}
					break;
				case "right":
					if (iNewLeftBox1 > this.iWidth) {
						$(this.domNode().id + '.1').style.left = (iNewLeftBox2 - this.iWidth) + "px";
					}
					if (iNewLeftBox2 > this.iWidth) {
						$(this.domNode().id + '.2').style.left = (iNewLeftBox1 - this.iWidth) + "px";
					}
					break;
				}
				break;

			case "vertical" :

				var iTopBox1 = parseInt($(this.domNode().id + '.1').style.top);
				var iTopBox2 = parseInt($(this.domNode().id + '.2').style.top);

				var iNewTopBox1 = iTopBox1 + parseInt(this.config.scroll.amount);
				var iNewTopBox2 = iTopBox2 + parseInt(this.config.scroll.amount);

				$(this.domNode().id + '.1').style.top = iNewTopBox1 + "px";
				$(this.domNode().id + '.2').style.top = iNewTopBox2 + "px";

				switch (this.config.scroll.direction) {
				case "top":
					if ((-1) * iNewTopBox1 > this.iHeight) {
						$(this.domNode().id + '.1').style.top = (iNewTopBox2 + this.iHeight) + "px";
					}
					if ((-1) * iNewTopBox2 > this.iHeight) {
						$(this.domNode().id + '.2').style.top = (iNewTopBox1 + this.iHeight) + "px";
					}
					break;
				case "bottom":
					if (iNewTopBox1 > this.iHeight) {
						$(this.domNode().id + '.1').style.top = (iNewTopBox2 - this.iHeight) + "px";
					}
					if (iNewTopBox2 > this.iHeight) {
						$(this.domNode().id + '.2').style.top = (iNewTopBox1 - this.iHeight) + "px";
					}
					break;
				}
				break;
			}
		}

		setTimeout(this.execScroll.bind(this), this.config.scroll.nextDelay);
	}

});