jQuery.extend({
    createUploadIframe: function(id) {
			//create frame
            var frameId = 'jUploadFrame' + id;

            if(window.ActiveXObject) {
            	//siehe http://msdn.microsoft.com/en-us/library/ff986077%28v=VS.85%29.aspx
            	//und buhl ticket 2623
                var io = document.createElement('iframe');
                io.setAttribute('id',frameId);
                io.setAttribute('name',frameId);
                io.src = 'javascript:false';
            }
            else {
                var io = document.createElement('iframe');
                io.id = frameId;
                io.name = frameId;
            }
            io.style.position = 'absolute';
            io.style.top = '-1000px';
            io.style.left = '-1000px';

            document.body.appendChild(io);

            return io
    },
    createUploadForm: function(id, fileElementId, additionalFields) {
			//create form
			var formId = 'jUploadForm' + id;
			var fileId = 'jUploadFile' + id;
			var form = $('<form  action="" method="POST" name="' + formId + '" id="' + formId + '" enctype="multipart/form-data" class="jUploadForm"></form>');
			var oldElement = $('#' + fileElementId);
			var newElement = $(oldElement).clone();
			$(oldElement).attr('id', fileId);
			$(oldElement).before(newElement);
			$(oldElement).appendTo(form);

			//set attributes
			form.css('position', 'absolute');
			form.css('top', '-1200px');
			form.css('left', '-1200px');
			form.appendTo('body');
			return form;
    },

    ajaxFileUpload: function(s) {
        s = jQuery.extend({}, jQuery.ajaxSettings, s);
        var id = new Date().getTime()
		var form = jQuery.createUploadForm(id, s.uploadField, s.additionalFields);
		var io = jQuery.createUploadIframe(id);
		var frameId = 'jUploadFrame' + id;
		var formId = 'jUploadForm' + id;

        // Wait for a response to come back
        var uploadCallback = function() {
					var io = document.getElementById(frameId);
					s.onComplete(jQuery(io).contents().find('body').text());

//					var oIframe =  document.getElementById('jUploadFrame1274351608492');
//					var oDoc = (oIframe.contentWindow || oIframe.contentDocument);
//					if (oDoc.document) oDoc = oDoc.document;
//					oDoc = window.jUploadFrame1274351608492.document;
//					alert(oDoc.innerHtml);

					$('#'+s.uploadField).val('');
					//setTimeout(function(){$(io).remove();$(form).remove();}, 100)
        }
				form.attr('action', s.url);
				form.attr('method', 'POST');
				form.attr('target', frameId);
        if(form.encoding) { form.encoding = 'multipart/form-data'; }
        else { form.enctype = 'multipart/form-data'; }

        s.onStart();
        form.submit();

        if(window.attachEvent){
			document.getElementById(frameId).attachEvent('onload', uploadCallback);
        } else{
			document.getElementById(frameId).addEventListener('load', uploadCallback, false);
        }
        return {abort: function () {}};
    }
})


MKWrapper.initAjaxUpload = function(oConfig) {
	jQuery('#'+oConfig.submitButton).bind('click', function(event){
		event.preventDefault();
		jQuery.ajaxFileUpload ( {
			url:oConfig.url,
			uploadField:oConfig.uploadField,
//			additionalFields:oConfig.additionalFields,
			onStart: oConfig.onStart,
			onComplete: oConfig.onComplete
		} );
		/*if(event.isDefaultPrevented()) {
			event.stopImmediatePropagation();
			event.stopPropagation();
		}*/
		return false;
	});
}
