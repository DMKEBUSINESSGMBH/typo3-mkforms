MKWrapper.framework == 'prototype';

MKWrapper.attachEvent = function(obj, event, func, scope) {
	scope = scope ? scope : this;
	if(scope!='skip') func = func.bindAsEventListener(scope);
	Event.observe(obj, event, func);
}
MKWrapper.removeEvent = function(obj, event, func) {
	Event.stopObserving(obj, event, func);
}

MKWrapper.stopEvent = function(event) {
	Event.stop(event);
}

MKWrapper.$tag = function(name, attr) {
	f = DOM.Builder.tagFunc(name);
	tag = f(attr);
	return tag;
}

MKWrapper.$ = function(str) {
	return $(str);
}
MKWrapper.id = function(element) {
	return element ? element.id : element;
}
MKWrapper.hasClass = function(element, classname) {
	return Element.hasClassName(element, classname);
}
MKWrapper.addClass = function(element, classname) {
	return Element.addClassName(element, classname);
}
MKWrapper.removeClass = function(element, classname) {
	return Element.removeClassName(element, classname);
}
MKWrapper.parent = function (collection) {
	return collection.up(0);
}
MKWrapper.$A = function(iterable) {
	return $A(iterable);
}
MKWrapper.$F = function(node) {
	return $F(node);
}
MKWrapper.cloneObject = function(node) {
	return Object.clone(node);
}
MKWrapper.$H = function(object) {
  return $H(object);
}

MKWrapper.extend = function(target, source) {
	return Object.extend(target, source);
}

MKWrapper.strStrip = function(str) {
	return str.strip();
}

MKWrapper.onDOMReady = function(f) {
	return Event.onDOMReady(f);
}

MKWrapper.domNode = function(id) {
  return $(id);
}
MKWrapper.domRemove = function(node) {
	if($(node).parentNode)
		DOM.remove(node);
}
MKWrapper.domInsert = function(node, html) {
	node.innerHTML = html
}
MKWrapper.domReplace = function(node, html) {
	Element.replace(node, html);
}

MKWrapper.bind = function(fkt, scope) {
	return fkt.bind(scope);
}

MKWrapper.bindAsEventListener = function(fkt, scope) {
	fkt = fkt.bindAsEventListener(scope);
	return fkt;
}

MKWrapper.each = function(collection, f, scope) {
	if(scope) f.bind(scope);
	collection.each(f);
}

MKWrapper.setStyle = function(container, style) {
	Element.setStyle(container, style);
}

MKWrapper.createClass = function(){
	return Class.create();
}

MKWrapper.getDimensions = function(container) {
	return Element.getDimensions(container);
}
MKWrapper.clonePosition = function(element, source, options) {
	return Element.clonePosition(element, source, options);
}
/**
 * zeigt alle versteckten eltern elemente an
 * 		hideElements versteckt wie wieder
 * 
 * 	wird benötigt, da einige methoden nur bei angezeigtem objekt richtige werte liefern
 *  (beispiel: .position .getDimensions)
 *  @TODO: umsetzung prototype
 */
MKWrapper.showParents = function(element) {}
MKWrapper.hideElements = function(aToHide){}

MKWrapper.findChilds = function(container, str) {
	return container.select(str);
}
MKWrapper.next = function(element) {
	return element.next(0);
}
MKWrapper.previous = function(element) {
	return element.previous(0);
}

MKWrapper.ajaxCall = function(url, options, scope) {
	options.onSuccess = options.onSuccess.bindAsEventListener(scope);
	var protoOptions = {
		url: url,
		asynchronous: options.asynchronous,
		evalJS: options.evalJS,
		method: options.method,
		onSuccess: function(transport) { options.onSuccess(transport.responseText); },
		parameters: options.parameters
	}
	if(options.onFailure)
		protoOptions['onFailure'] = options.onFailure.bindAsEventListener(scope);
	new Ajax.Request( url, protoOptions);
}

MKWrapper.loadScript = function(url, callback, scope) {
	MKWrapper.ajaxCall(url, {
			method:'get',
			asynchronous: false,
			evalJS: false,
			onSuccess: function(response) {
				Formidable.globalEval(response);
				callback(scope);
			}
		},
		scope
	);
}

MKWrapper.remove = function(elem) {
	return DOM.remove(elem);
}

MKWrapper.fxAppear = function(id, options, callback) {
	new Effect.Appear($(id), {
		duration: 0.5,
		fps: 50,
		afterFinish: callback
	});
}

MKWrapper.fxHide = function(id, options, callback) {
	new Effect.Appear($(id), {
		duration: 0.3,
		fps: 50,
		afterFinish: callback
	});
}

MKWrapper.delayedObserver = function(element, delay, callback) {
	return new Form.Element.Observer(
			element,
			delay,
			callback
		);
}

/**
 * TODO: Ajax-Calls verstecken
 * Ajax.Request.abort
 * extend the prototype.js Ajax.Request object so that it supports an abort method
 */
Ajax.Request.prototype.abort = function() {
    // prevent and state change callbacks from being issued
    this.transport.onreadystatechange = Prototype.emptyFunction;
    // abort the XHR
    this.transport.abort();
    // update the request counter
    Ajax.activeRequestCount--;
	if(Ajax.activeRequestCount < 0) {
	    Ajax.activeRequestCount = 0;
	}
};

/*json*/
MKWrapper.isJSON = function (str) {
	return str.isJSON;
}
MKWrapper.evalJSON = function(str,sanitize) {
	return str.evalJSON(sanitize);
}

MKWrapper.filter = function(element, callback) {
	return element.findAll(callback);
};