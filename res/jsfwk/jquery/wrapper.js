MKWrapper.framework = 'jquery';

MKWrapper.$tag = function(name, attr) {
	f = MKWrapper._tagFunc(name);
	tag = f(attr);
	return tag[0];
};
MKWrapper.attachEvent = function(obj, event, func, scope) {
	if(obj==null) return; //wenn kein obj gegeben ist wird das event aufs dokument gelegt, nicht gut!
	scope = scope ? scope : this;
	if(scope!='skip') func = func.bind(scope);
	jQuery(obj).bind(event, {'scope' : scope}, func);
};
MKWrapper.removeEvent = function(obj, event, func) {
	jQuery(obj).unbind(event, func);
};
MKWrapper.stopEvent = function(event) {
	event.stopPropagation();
};
MKWrapper.bindAsEventListener = function(fkt, scope) {
	return fkt.bind(scope);
};

MKWrapper.each = function(collection, f, scope) {
	jQuery.each(collection,function(idx, value) {
		f(value, idx);
	});
};

MKWrapper.findChilds = function(container, str) {
	if(typeof(str) == "undefined") {
		return jQuery(container).children();
	} else {
		return jQuery(container).find(str);	
	}
};
MKWrapper.next = function(element) {
	return jQuery(element).next().get(0);
};
MKWrapper.previous = function(element) {
	return jQuery(element).prev().get(0);
};

MKWrapper.setStyle = function(container, style) {
	return jQuery(container).css(style);
};

/**
 * Method from Lowpro 0.2 (http://www.danwebb.net/2006/9/3/low-pro-unobtrusive-scripting-for-prototype)
 */
MKWrapper._tagFunc = function(tag) {
	return function() {
		var attrs, children; 
		if (arguments.length>0) { 
		  	if (arguments[0].nodeName || typeof arguments[0] == "string") {
		  		children = arguments;
		  	}
			else { attrs = arguments[0]; children = [].slice.call(arguments, 1); }
		}
		return MKWrapper._create(tag, attrs, children);
	};
};
/**
 * Method from Lowpro 0.2 (http://www.danwebb.net/2006/9/3/low-pro-unobtrusive-scripting-for-prototype)
 */
MKWrapper._create = function(tag, attrs, children) {
	attrs = attrs || {}; children = children || [];
	var isIE = navigator.userAgent.match(/MSIE/);
	var el = document.createElement((isIE && attrs.name) ? "<" + tag + " name=" + attrs.name + ">" : tag);
	for (var attr in attrs) {
		if (typeof attrs[attr] != 'function') {
			//if (isIE) this.ieAttrSet(attrs, attrs, el);
			if (isIE) {
				//this.ieAttrSet(attrs, attr, el);
				IE_TRANSLATIONS = {'class' : 'className', 'for' : 'htmlFor'};
				var trans; 
				if (trans = IE_TRANSLATIONS[attr]) 	{ el[trans] = attrs[attr]; } 
				else if (attr == 'style') 			{ el.style.cssText = attrs[attr]; } 
				else if (attr.match(/^on/)) 		{ el[attr] = new Function(attrs[attr]); } 
				else 								{ el.setAttribute(attr, attrs[attr]); } 
			}
			else 									{ el.setAttribute(attr, attrs[attr]); }
		}
	}
	for (var i=0; i<children.length; i++) {
		if (typeof children[i] == 'string') children[i] = document.createTextNode(children[i]);
		el.appendChild(children[i]);
	}
	return jQuery(el);
};

MKWrapper.cloneObject = function(node) {
	return MKWrapper.extend({},node);
};
MKWrapper.extend = function(target, source) {
	var ret = jQuery.extend(target, source);
	return ret;
};
// Aufruf erwartet immer eine ID
MKWrapper.$ = function(id, html) {
	if (!id) return false;
	if (html == null){ html = true; }
	if (typeof id == "string" ) { //# voranstellen, wenn string und keines vorhanden
		if (id.search(/#/) == -1) id = '#'+id;
	}
	var elem = jQuery(id);
	return elem.length > 0 ? (html ? elem.get(0) : elem) : false;
};
MKWrapper.id = function(element) {
	if (typeof id != "object" ) element = jQuery(element);
	return element.attr('id');
};
MKWrapper.hasClass = function(element, classname) {
	return jQuery(element).hasClass(classname);
};
MKWrapper.addClass = function(element, classname) {
	return jQuery(element).addClass(classname);
};
MKWrapper.removeClass = function(element, classname) {
	return jQuery(element).removeClass(classname);
};
MKWrapper.parent = function (collection) {
	return jQuery(collection).parent().get(0);
};
MKWrapper.getDimensions = function(container, mode) {
	var element = jQuery(container);
	switch (mode) {
		case 'inner': // width with padding
			return { height: element.innerHeight(), width: element.innerWidth() };
		case 'outer': // width with padding and border
			return { height: element.outerHeight(), width: element.outerWidth() };
		case 'outermargin': // width with padding, border and margin 
			return { height: element.outerHeight(true), width: element.outerWidth(true) };
		default:
			return { height: element.height(), width: element.width() };
	}
};
MKWrapper.clonePosition = function(element, source, options) {
    var options = jQuery.extend({
    	setLeft: true,
    	setTop: true,
    	setWidth: true,
    	setHeight: true,
    	offsetLeft: 0,
    	offsetTop: 0
      }, (options || {}));
    element = jQuery(element); source = jQuery(source);
    var spos = {'top':0,'left':0};
    if (source[0] !== document ) spos = jQuery.extend(spos, (source.position() || {}));
    if (options.setLeft)	element.css({'left'	: spos.left + options.offsetLeft });
    if (options.setTop)		element.css({'top'	: spos.top + options.offsetTop });
    if (options.setWidth)	element.width(source.width() );
    if (options.setHeight)	element.height(source.height() );
	return element;
};
/**
 * zeigt alle versteckten eltern elemente an
 * 		hideElements versteckt wie wieder
 * 
 * 	wird benötigt, da einige methoden nur bei angezeigtem objekt richtige werte liefern
 *  (beispiel: .position .getDimensions)
 */
MKWrapper.showParents = function(element) {
	var aToHide = new Array();
    if (!jQuery(element).is(':visible')) {
        var el = jQuery(element).parent();
        var aHiden = new Array();
        while (!el.is(':visible') && el.length > 0) {
        	aHiden.push(el);
            el = el.parent();
        }
    	for(var el in aHiden.reverse()) {
    		if ( !aHiden[el].is(':visible')) {
    			aToHide.push(aHiden[el]);
    			aHiden[el].show();
    		}
    	}
    }
    return aToHide;
};
MKWrapper.hideElements = function(aToHide){
    if(aToHide.length)
    	for(var el in aToHide) 
    		aToHide[el].hide();
};

MKWrapper.$A = function(iterable) {
	return jQuery.makeArray(iterable);
};
MKWrapper.$F = function(node) {
	node = jQuery(node);
	// Sonderbehandlung für Checkboxen und Radiobuttons
	if((node.is('input:checkbox')||node.is('input:radio')) && !node.is(':checked'))
		return null;
	return node.val();
};

MKWrapper.$H = function(object) {
  return jQuery.makeArray(object);
};

MKWrapper.onDOMReady = function(f) {
	jQuery(document).ready(f);
};

MKWrapper.ajaxCall = function(url, options, scope) {
/*
	jQuery(scope).ajaxComplete(function (evt, request, settings) {
		if(evt.target != this) return;
		console.debug(evt);
		console.debug(this);
		options.onSuccess(request.responseText, this);
	});
*/
	// Die Optionen neu mappen
	new jQuery.ajax({
		url: url,
		type: options.method,
		success: function (response, evt) {
		  options.onSuccess(response, scope);
		},
		data: options.parameters,
		error: options.onFailure
	});
};
MKWrapper.loadScript = function(url, callback, scope) {
	jQuery.ajax({
		url : url,
		dataType : 'script',
		async : false,
		success : function(data, textStatus, jqXHR) {
			callback(scope);
		}
	});
	// Der Call wird u.U. ansynchron ausgeführt.
	// Ob die Einbindung manuell aber korrekt ist, steht leider nicht fest...
	// jQuery.getScript(url, function() {
	// callback(scope);
	// });
};

MKWrapper.strStrip = function(str) {
	return jQuery.trim(str);
};

MKWrapper.domNode = function(id) {
	return jQuery('#'+id).get(0);
};
MKWrapper.domRemove = function(node) {
	if(typeof(node) == 'undefined') return;
	if(node == null) return;
	jQuery(node).empty();
	jQuery(node).remove();
};
MKWrapper.domInsert = function(node, html) {
	jQuery(node).html(html)
};
MKWrapper.domReplace = function(node, html) {
	jQuery(node).replaceWith(html);
};

MKWrapper.bind = function(fkt, scope) {
	return fkt.bind(scope);
};

MKWrapper.remove = function(elem) {
	return jQuery(elem).hide();
};


MKWrapper.fxAppear = function(id, options, callback) {
	elem = MKWrapper.$(id, false);
	var speed = 0;
	if(elem) elem.show(speed,callback);
	// Bei speed 0 wird die callback von jQuery nicht ausgeführt!!
	if(!speed && typeof callback == 'function') callback();
};

MKWrapper.fxHide = function(id, options, callback) {
	elem = MKWrapper.$(id, false);
	var speed = 0;
	if(elem) elem.hide(speed,callback);
	// Bei speed 0 wird die callback von jQuery nicht ausgeführt!!
	if(!speed && typeof callback == 'function') callback(); 
};

/**
 * @param {Object} context the 'this' value to be used.
 * @param {arguments} [1..n] optional arguments that are
 * prepended to returned function's call.
 * @return {Function} a function that applies the original
 * function with 'context' as the thisArg.
 */
Function.prototype.bind = function(context){
  var fn = this, 
      ap, concat, args,
      isPartial = arguments.length > 1;
  // Strategy 1: just bind, not a partialApply
  if(!isPartial) {
    return function() {
        if(arguments.length !== 0) {
          return fn.apply(context, arguments);
        } else {
          return fn.call(context); // faster in Firefox.
        }
      };
    } else {
	    // Strategy 2: partialApply
	    ap = Array.prototype,
	    args = ap.slice.call(arguments, 1);
	    concat = ap.concat;
	    return function() {
	      return fn.apply(context, 
	        arguments.length === 0 ? args : 
	        concat.apply(args, arguments));
	    };
  }
};

String.prototype.startsWith = function(t, i) {
	if (i==false) {
		return (t == this.substring(0, t.length));
	} else {
		return (t.toLowerCase() == this.substring(0, t.length).toLowerCase());
	}
};

MKWrapper.delayedObserver = function(element, delay, callback) {
	return jQuery(element).delayedObserver(callback, delay);
};
/*  jquery function 4 delayed observer */
jQuery.extend(jQuery.fn, {
    delayedObserver: function(callback, delay, options){
        return this.each(function(){
            var el = jQuery(this);
            var op = options || {};
            el.data('oldval', el.val())
                .data('delay', delay || 0.5)
                .data('condition', op.condition || function() { return (jQuery(this).data('oldval') == jQuery(this).val()); })
                .data('callback', callback)
                [(op.event||'keyup')](function(){
                    if (el.data('condition').apply(el)) { return; }
                    else {
                        if (el.data('timer')) { clearTimeout(el.data('timer')); }
                        el.data('timer', setTimeout(function(){
                            el.data('callback').apply(el);
                        }, el.data('delay') * 1000));
                        el.data('oldval', el.val());
                    }
                });
        });
    }
});
/////////
// json
/////////
RegExp.prototype.match = RegExp.prototype.test;
MKWrapper.isJSON = function (str) {
	if (str.length==0) return false;
    str = str.replace(/\\./g, '@').replace(/"[^"\\\n\r]*"/g, '');
    return (/^[,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t]*$/).test(str);
};
MKWrapper.evalJSON = function(str,sanitize) {
    var json = str.replace(/^\/\*-secure-([\s\S]*)\*\/\s*$/, '$1');
    try {
    	// TODO: Bitte konsequent json2.js verwenden!!
      if (!sanitize || MKWrapper.isJSON(json)) return eval('(' + json + ')');
    } catch (e) { }
    throw new SyntaxError('Badly formed JSON string');
};

/////////
// Validierung
/////////
MKWrapper.handleValidationErrors = function(oErrors, form, msgDiv) {
	msgDiv = msgDiv ? MKWrapper.$(msgDiv, false) : false;
	if(msgDiv) msgDiv.empty();
	if(oErrors.noErrors && oErrors.noErrors === true) return;
	var errContainer = MKWrapper.$tag('div', {'class':'errors'});
	for (var widget in oErrors) {
		var field = form.o(widget);
		if(field != null) {
			if(msgDiv) {
				var errDiv = MKWrapper.$tag('div', {'class':'error'});
				jQuery(errDiv).text(oErrors[widget]);
				jQuery(errContainer).append(errDiv);
			}
			var input = MKWrapper.$(field.config.id, false);
			var label = MKWrapper.$(field.getLabel(), false);
			var span = MKWrapper.$('showspan_' + field.config.id, false);
			if(label) label.addClass('hasError');
			if(span) span.addClass('hasError');
			if(input) {
				input.addClass('hasError');
				input.unbind('change.errorhandler');
				input.bind('change.errorhandler', function(){
					// wir brauchen das form objekt!
					var field = form.o(jQuery(this).attr('id'));
					var span = MKWrapper.$('showspan_' + field.config.id, false);
					var label = MKWrapper.$(field.getLabel(), false);
					jQuery(this).removeClass('hasError');
					if(label) label.removeClass('hasError');
					if(span) span.addClass('hasError');
				});
			}
		}
	}
	if(msgDiv && jQuery(errContainer).children().length) msgDiv.append(errContainer);
};
MKWrapper._changeHandler = function() {
	jQuery(this).removeClass('hasError');
};

MKWrapper.filter = function(element, callback) {
	return jQuery(element).filter(function(index, object) {
		return callback(object);
	});
};

MKWrapper.inArray = function(needle, haystack) {
	return jQuery.inArray(needle, haystack);
};