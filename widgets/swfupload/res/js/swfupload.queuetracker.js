/*
	Queue Plug-in

	Features:
		*Adds a cancelQueue() method for cancelling the entire queue.
		*All queued files are uploaded when startUpload() is called.
		*If false is returned from uploadComplete then the queue upload is stopped.
		 If false is not returned (strict comparison) then the queue upload is continued.
		*Adds a QueueComplete event that is fired when all the queued files have finished uploading.
		 Set the event handler with the queue_complete_handler setting.

	*/

var SWFUpload;
if (typeof(SWFUpload) === "function") {
	SWFUpload.uploadedFiles = [];
	SWFUpload.lastUploadedFiles = [];
	SWFUpload.queueTracker = {};
	SWFUpload.queueTrackerSettings = {};
	SWFUpload.prototype.initSettings = (function (oldInitSettings) {
		return function () {
			if (typeof(oldInitSettings) === "function") {
				oldInitSettings.call(this);
			}

			this.uploadedFiles = [];
			this.lastUploadedFiles = [];
			this.queueTracker = {};
			this.queueTrackerSettings = {};

			this.queueTrackerSettings.queue_complete_handler = this.settings.queue_complete_handler;
			this.settings.queue_complete_handler = SWFUpload.queueTracker.queueCompleteHandler;

			this.queueTrackerSettings.upload_complete_handler = this.settings.upload_complete_handler;
			this.settings.upload_complete_handler = SWFUpload.queueTracker.uploadCompleteHandler;
		};
	})(SWFUpload.prototype.initSettings);

	SWFUpload.queueTracker.uploadCompleteHandler = function(file) {
		this.uploadedFiles.push(file);

		if (typeof(this.queueTrackerSettings.upload_complete_handler) === "function") {
			returnValue = this.queueTrackerSettings.upload_complete_handler.call(this, file);
		}

		return returnValue;
	};

	SWFUpload.queueTracker.queueCompleteHandler = function(file) {
		this.lastUploadedFiles = this.uploadedFiles;
		this.uploadedFiles = [];

		if (typeof(this.queueTrackerSettings.queue_complete_handler) === "function") {
			returnValue = this.queueTrackerSettings.queue_complete_handler.call(this, file);
		}

		return returnValue;
	};

	SWFUpload.prototype.getUploadedFiles = function() {
		return this.lastUploadedFiles;
	}
}