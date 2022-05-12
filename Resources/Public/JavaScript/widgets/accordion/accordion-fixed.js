// accordion.js v2.0
//
// Copyright (c) 2007 stickmanlabs
// Author: Kevin P Miller | http://www.stickmanlabs.com
//
// Accordion is freely distributable under the terms of an MIT-style license.
//
// I don't care what you think about the file size...
//   Be a pro:
//	    http://www.thinkvitamin.com/features/webapps/serving-javascript-fast
//      http://rakaz.nl/item/make_your_pages_load_faster_by_combining_and_compressing_javascript_and_css_files
//

/*-----------------------------------------------------------------------------------------------*/

if (typeof Effect == 'undefined' && MKWrapper.framework == 'prototype')
	throw("accordion.js requires including script.aculo.us' effects.js library!");


var accordion = {

	//
	//  Setup the Variables
	//
	showAccordion : null,
	currentAccordion : null,
	accordions: [],

	//
	//  Initialize the accordions
	//
	initialize: function(container, options) {

	  if (typeof(container) == "undefined") {
		  throw("container is undefined!");
		  return false;
	  }

	  if (!MKWrapper.$(container)) {
	    throw(container+" doesn't exist!");
	    return false;
	  }

		this.options = MKWrapper.extend({
			parent: null,
			classNames : {
				toggle : 'accordion_toggle',
				toggleActive : 'accordion_toggle_active',
				content : 'accordion_content'
			},
			defaultSize : {
				height : null,
				width : null
			},
			closeactive: true,
			onEvent : 'click'
		}, options || {});

		var ta = MKWrapper.$H(this.options.accordions);
		if (MKWrapper.framework == 'jquery') ta = ta[0];
		this.accordions = Formidable.objValues(ta);

		var tscope = this;
		MKWrapper.each(this.accordions,function(accordion) {
			if (MKWrapper.$(accordion)) {
				accordion = MKWrapper.$(accordion);

				//Buhl Link
				if(MKWrapper.framework == 'jquery') { jQuery(accordion).wrapInner('<a class="plusmn" href="javascript:void(0)"></a>'); }

				MKWrapper.attachEvent(accordion, tscope.options.onEvent, tscope.activate.bind(tscope, accordion), tscope)
				if (tscope.options.onEvent == 'click') {
				  accordion.onclick = function() {return false;};
				}

				if (tscope.options.direction == 'horizontal') {
					var options = {/*width: '0px',*/ display: 'none'};

				} else {
					var options = {/*height: '0px',*/ display: 'none'} ;
				}

				MKWrapper.removeClass(
						accordion,
						tscope.options.classNames.toggleActive
					);

				this.currentAccordion = MKWrapper.setStyle(
						MKWrapper.$(
								MKWrapper.next(accordion)
							)
					, options);
			}
		},tscope);

		this.currentAccordion = null;
	},

	//
	//  Activate an accordion
	//
	activate : function(accordion) {
		if(typeof(accordion) == 'undefined') return;
		this.currentAccordion = MKWrapper.$(
			MKWrapper.next(accordion)
		);

		MKWrapper.addClass(
			MKWrapper.previous(this.currentAccordion),
			this.options.classNames.toggleActive
		);
		if(this.options.closeactive) {
			MKWrapper.removeClass(
				MKWrapper.previous(this.currentAccordion),
				this.options.classNames.toggle
			);
		}

		if ((MKWrapper.id(this.showAccordion) == MKWrapper.id(this.currentAccordion))) {
			if(this.options.onEvent != "mouseover") {
				this.deactivate();
				if(this.showAccordion)
					MKWrapper.setStyle(this.showAccordion, { height: 'auto' });
				this.showAccordion = null;
			}
		} else {
			if (this.currentAccordion)
				this.options.parent.onTabOpen_eventHandler(
						MKWrapper.id(
							MKWrapper.previous(this.currentAccordion)
						)
					);
			if (this.currentAccordion)
				this.options.parent.onTabChange_eventHandler(
					MKWrapper.id(
							MKWrapper.previous(this.currentAccordion)
						)
				, "open");
			this._handleAccordion();
		}
	},
	//
	// Deactivate an active accordion
	//
	deactivate : function() {

		MKWrapper.removeClass(
			MKWrapper.previous(this.showAccordion),
			this.options.classNames.toggleActive
		);
		MKWrapper.addClass(
				MKWrapper.previous(this.showAccordion),
				this.options.classNames.toggle
			);

		if (this.showAccordion)
			this.options.parent.onTabClose_eventHandler(
				MKWrapper.id(
					MKWrapper.previous(this.showAccordion)
				)
			);
		if (this.showAccordion)
			this.options.parent.onTabChange_eventHandler(
				MKWrapper.id(
						MKWrapper.previous(this.showAccordion)
					)
			, "close");

		if (this.showAccordion)
			MKWrapper.fxHide(this.showAccordion);

	},

  //
  // Handle the open/close actions of the accordion
  //
	_handleAccordion : function() {

		if (this.showAccordion && this.options.closeactive) {
			this.deactivate();
		}
		if (this.currentAccordion && MKWrapper.id(this.showAccordion) != MKWrapper.id(this.currentAccordion)) {
			MKWrapper.fxAppear(this.currentAccordion);
			this.showAccordion = this.currentAccordion;
		}

		if (this.showAccordion) {
			MKWrapper.fxAppear(this.showAccordion);
		}
		MKWrapper.setStyle(this.currentAccordion, {  height: 'auto' });
		this.showAccordion = this.currentAccordion;
	}
}
