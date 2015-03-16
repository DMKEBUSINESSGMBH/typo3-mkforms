Formidable.Classes.SwfUpload = Formidable.Classes.RdtBaseClass.extend({
	oSWFUpload: null,
	getButtonBrowse: function() {return this.oForm.o(this.config.buttonBrowseId);},
	getButtonUpload: function() {return this.oForm.o(this.config.buttonUploadId);},
	getListQueue: function() {return this.oForm.o(this.config.listQueueId);},
	aHandlers: {
		"ondialogstart": MKWrapper.$A(),
		"ondialogclose": MKWrapper.$A(),
		"onuploadstart": MKWrapper.$A(),
		"onuploadprogress": MKWrapper.$A(),
		"onuploadsuccess": MKWrapper.$A(),
		"onuploaderror": MKWrapper.$A(),
		"onuploadcomplete": MKWrapper.$A(),
		"onfilequeued": MKWrapper.$A(),
		"onqueueerror": MKWrapper.$A(),
		"onqueueerrorfilesize": MKWrapper.$A(),
		"onqueueerrorfiletype": MKWrapper.$A(),
		"onqueuecomplete": MKWrapper.$A()
	},
	onDialogStart_handler: function() {
		MKWrapper.each(this.aHandlers["ondialogstart"], function(fFunc, iKey) { fFunc(); });
	},
	onDialogClose_handler: function(num_files_selected) {
		MKWrapper.each(this.aHandlers["ondialogclose"], function(fFunc, iKey) { fFunc(num_files_selected); });
	},
	onUploadStart_handler: function(file) {
		MKWrapper.each(this.aHandlers["onuploadstart"], function(fFunc, iKey) { fFunc(file); });
	},
	onUploadProgress_handler: function(file, bytes_complete, bytes_total) {
		MKWrapper.each(this.aHandlers["onuploadprogress"], function(fFunc, iKey) { fFunc(file, bytes_complete, bytes_total); });
	},
	onUploadSuccess_handler: function(file, server_data) {
		MKWrapper.each(this.aHandlers["onuploadsuccess"], function(fFunc, iKey) { fFunc(file, server_data); });
		var stats = this.oSWFUpload.getStats();
		if (stats.files_queued > 0) {
			this.oSWFUpload.startUpload();
		}
	},
	onUploadError_handler: function(file, error_code, message) {
		MKWrapper.each(this.aHandlers["onuploaderror"], function(fFunc, iKey) {
			fFunc(file, error_code, message);
		});
	},
	onUploadComplete_handler: function(file) {
		MKWrapper.each(this.aHandlers["onuploadcomplete"], function(fFunc, iKey) {
			fFunc(file);
		});
	},
	onFileQueued_handler: function(file) {
		MKWrapper.each(this.aHandlers["onfilequeued"], function(fFunc, iKey) {
			fFunc(file);
		});
	},
	onQueueError_handler: function(file, error_code, message) {
		MKWrapper.each(this.aHandlers["onqueueerror"], function(fFunc, iKey) {
			fFunc(file, error_code, message);
		});

		if(error_code == SWFUpload.QUEUE_ERROR.FILE_EXCEEDS_SIZE_LIMIT) {
			MKWrapper.each(this.aHandlers["onqueueerrorfilesize"], function(fFunc, iKey) {
				fFunc(file, error_code, message);
			});
		}

		if(error_code == SWFUpload.QUEUE_ERROR.INVALID_FILETYPE) {
			MKWrapper.each(this.aHandlers["onqueueerrorfiletype"], function(fFunc, iKey) {
				fFunc(file, error_code, message);
			});
		}
	},
	onQueueComplete_handler: function(iQueueCount) {
		MKWrapper.each(this.aHandlers["onqueuecomplete"], function(fFunc, iKey) {
			fFunc(iQueueCount);
		});
	},
	addHandler: function(sHandler, fFunction) {
		this.aHandlers[sHandler].push(fFunction);
	},
	swfDebug: function(sMessage) {
		return;
		console.log(sMessage);
	},
	constructor: function(oConfig) {
		this.base(oConfig);

		MKWrapper.extend(this.config.swfupload_config, {
			"debug": true,
			"debug_handler": this.swfDebug,
			"file_dialog_start_handler": this.onDialogStart_handler.bind(this),
			"file_queued_handler": this.onFileQueued_handler.bind(this),
			"file_queue_error_handler": this.onQueueError_handler.bind(this),
			"queue_complete_handler": this.onQueueComplete_handler.bind(this),
			"file_dialog_complete_handler": this.onDialogClose_handler.bind(this),
			"upload_start_handler": this.onUploadStart_handler.bind(this),
			"upload_progress_handler": this.onUploadProgress_handler.bind(this),
			"upload_error_handler": this.onUploadError_handler.bind(this),
			"upload_success_handler": this.onUploadSuccess_handler.bind(this),
			"upload_complete_handler": this.onUploadComplete_handler.bind(this)
		});

		//this.oSWFUpload = new SWFUpload(this.config.swfupload_config);

/*		this.oSWFUpload.fileDialogStart_handler =
		this.oSWFUpload.fileDialogComplete_handler = this.onDialogClose_handler.bind(this);
		this.oSWFUpload.uploadProgress_handler = this.onUploadProgress_handler.bind(this);
		this.oSWFUpload.uploadSuccess_handler = this.onUploadSuccess_handler.bind(this);
		this.oSWFUpload.uploadError_handler = this.onUploadError_handler.bind(this);
		this.oSWFUpload.uploadComplete_handler = this.onUploadComplete_handler.bind(this);
		this.oSWFUpload.file_queued_handler = this.onFileQueued_handler.bind(this);
		this.oSWFUpload.fileQueueError_handler = this.onQueueError_handler.bind(this);
*/
	},
	selectFiles: function() {
		this.oSWFUpload.selectFiles();
	},
	startUpload: function() {
		this.oSWFUpload.startUpload();
	},
	addFileInQueue: function(sFileName, sFileId) {
		oListQueue = this.getListQueue();
		if(oListQueue && oListQueue.addItem) {
			oListQueue.addItem({
				"caption": sFileName,
				"value": sFileId
			});
		} else {
			oListQueue.appendValue(sFileName);
		}
	},
	removeFileInQueue: function(sFileId) {
		oListQueue = this.getListQueue();
		if(oListQueue && oListQueue.removeItem) {
			oListQueue.removeItem({
				"value": sFileId
			});
		} else {
			oListQueue.setValue("");
		}
	},
	getParamsForMajix: function(aValues, sEventName, aParams, aRowParams, aLocalArgs) {

		aValues = this.base(aValues, sEventName, aParams, aRowParams, aLocalArgs);
		aFileEvents = ["onfilequeued", "onuploadcomplete", "onuploadsuccess", "onqueueerror", "onqueueerrorfilesize", "onqueueerrorfiletype", "onuploadprogress"];
		aValues["sys_event"] = {};
		if(MKWrapper.$A(aFileEvents).indexOf(sEventName) != -1) {

			if(aLocalArgs && aLocalArgs[0] && aLocalArgs[0].name) {
				// it's a file
				aValues["sys_event"] = {
					"file": MKWrapper.cloneObject(aLocalArgs[0])	// using clone coz if not, reference is kept on arguments[0], thus modificating arguments for next events to be called
				};
				aValues["sys_event"].file.creationdate = parseInt(aValues["sys_event"].file.creationdate.getTime() / 1000);
				aValues["sys_event"].file.modificationdate = parseInt(aValues["sys_event"].file.modificationdate.getTime() / 1000);


				aValues["sys_event"].file.humanSize = Formidable.formatSize(aValues["sys_event"].file.size);
			}
		}

		if(sEventName == "onuploadprogress") {
			aValues["sys_event"].bytes_complete = aLocalArgs[1];
			aValues["sys_event"].bytes_complete_humanSize = Formidable.formatSize(aValues["sys_event"].bytes_complete);
			aValues["sys_event"].bytes_total = aLocalArgs[2];
			aValues["sys_event"].bytes_total_humanSize = Formidable.formatSize(aValues["sys_event"].bytes_total);
			aValues["sys_event"].percentage = parseInt(((aLocalArgs[1] + 1) / (aLocalArgs[2] + 1)) * 100);
		}

		if(sEventName == "onqueueerror" || sEventName == "onqueueerrorfilesize" || sEventName == "onqueueerrorfiletype") {
			aValues["sys_event"].error_code = aLocalArgs[1];
			aValues["sys_event"].message = aLocalArgs[2];
		}

		if(sEventName == "onqueuecomplete") {
			aValues["sys_event"].queue_count = aLocalArgs[0];
			aValues["sys_event"].queue = this.oSWFUpload.getUploadedFiles();
		}

		aValues["sys_event"].stats = this.oSWFUpload.getStats();

		return aValues;
	},
	getMaxFileSize: function() {
		return this.oSWFUpload.settings.file_size_limit * 1024;
	},
	getHumanMaxFileSize: function() {
		return Formidable.formatSize(
			this.getMaxFileSize()
		);
	}
});