/**
 * Wrapper class
 *
 * @deprecated This file will be removed in version 10.0.0. Please use the file in Resources/Public/JavaScript instead.
 *
 * This file also will not receive and updates or bug fixes before it gets removed.
 */
var MKWrapper = new Base;
/**
 * Creates a DOM tag
 * Replacement for $img({'src':'image.gif'}) -> MKWrapper.$tag('img', {'src':'image.gif'});
 */
MKWrapper.tag = function(name, data) {};
MKWrapper.domNode = function(id) { return document.getElementById(id); };
MKWrapper.stripTags = function(str) { return str.replace(/<\w+(\s+("[^"]*"|'[^']*'|[^>])+)?>|<\/\w+>/gi, ''); };
/**
 * Ajax call abbrechen
 */
MKWrapper.ajaxAbort = function(ajaxCall) { ajaxCall.abort(); };
/**
 * wertet den userAgent aus
 */
MKWrapper.browser = function(){
	var ua = navigator.userAgent;
	ua = ua.toLowerCase();
	var match = /(webkit)[ \/]([\w.]+)/.exec( ua ) ||
				/(opera)(?:.*version)?[ \/]([\w.]+)/.exec( ua ) ||
				/(msie) ([\w.]+)/.exec( ua ) ||
				!/compatible/.test( ua ) && /(mozilla)(?:.*? rv:([\w.]+))?/.exec( ua ) ||
				[];
	return{ browser: match[1] || "", version: match[2] || "0" };
};
/**
 * prüft auf ie oder die gegebene ieversion
 */
MKWrapper.isIE = function(iVer){
	if(typeof(iVer)=='undefined') return navigator.userAgent.match(/MSIE/);
	var b = MKWrapper.browser();
	return (b.browser == 'msie' && parseInt(b.version) <=7);
};
MKWrapper.debug = function(param) { console.debug(param); };