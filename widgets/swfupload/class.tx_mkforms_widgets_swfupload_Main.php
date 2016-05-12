<?php
/**
 * Plugin 'rdt_swfupload' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */
tx_rnbase::load('tx_rnbase_util_Typo3Classes');


class tx_mkforms_widgets_swfupload_Main extends formidable_mainrenderlet {

	var $aLibs = array(
		"rdt_swfupload_lib" => "res/js/swfupload.js",
		"rdt_swfupload_lib_cookies" => "res/js/swfupload.cookies.js",
		"rdt_swfupload_lib_queue" => "res/js/swfupload.queue.js",
		"rdt_swfupload_lib_queuetracker" => "res/js/swfupload.queuetracker.js",
		"rdt_swfupload_class" => "res/js/rdt_swfupload.js",
	);

	var $sMajixClass = "SwfUpload";

	var $oButtonBrowse = FALSE;
	var $oButtonUpload = FALSE;
	var $oListQueue = FALSE;

	var $bCustomIncludeScript = TRUE;

	var $aPossibleCustomEvents = array(
		"onuploadprogress",
		"ondialogstart",
		"ondialogclose",
		"onuploadstart",
		"onuploadsuccess",
		"onuploaderror",
		"onuploadcomplete",
		"onfilequeued",
		"onqueueerror",
		"onqueueerrorfilesize",
		"onqueueerrorfiletype",
		"onqueuecomplete",
	);

	function _render() {

		$this->oForm->bStoreFormInSession = TRUE;	// requesting eID context for upload-service

		$this->initButtonBrowse();
		$this->initButtonUpload();
		$this->initListQueue();

		$aButtonBrowse = $this->oForm->_renderElement($this->oButtonBrowse);
		$aButtonUpload = $this->oForm->_renderElement($this->oButtonUpload);
		$aListQueue = $this->oForm->_renderElement($this->oListQueue);


		/* forging access to upload service */

		$sHtmlId = $this->_getElementHtmlId();
		$sObject = "rdt_swfupload";
		$sServiceKey = "upload";
		$sFormId = $this->oForm->formid;
		$sSafeLock = $this->_getSessionDataHashKey();
		$sThrower = $sHtmlId;

		$sUrl = tx_mkforms_util_Div::removeEndingSlash(Tx_Rnbase_Utility_T3General::getIndpEnv("TYPO3_SITE_URL")) . '/index.php?eID='.tx_mkforms_util_Div::getAjaxEId().'&object=' . $sObject . "&servicekey=" . $sServiceKey . "&formid=" . $sFormId . "&safelock=" . $sSafeLock . "&thrower=" . $sThrower;
		$sButtonUrl = $this->oForm->getConfigXML()->getLLLabel("LLL:EXT:mkforms/widgets/swfupload/res/locallang.xml:buttonbrowse.image_url");

		$aConf = array(
			"buttonBrowseId" => $this->oButtonBrowse->_getElementHtmlId(),
			"buttonUploadId" => $this->oButtonUpload->_getElementHtmlId(),
			"listQueueId" => $this->oListQueue->_getElementHtmlId(),
			"swfupload_config" => array(
				"upload_url" => $sUrl,
				"flash_url" => $this->sExtWebPath . "res/flash/swfupload.swf",
				"file_post_name" => "rdt_swfupload",
				"file_size_limit" => $this->getMaxUploadSize(),	// KiloBytes

				"file_types_description" => $this->getFileTypeDesc(),
				"file_types" => $this->getFileType(),

				"file_queue_limit" => $this->getQueueLimit(),

				"button_placeholder_id" => $this->oButtonBrowse->_getElementHtmlId(),
				"button_image_url" => tx_mkforms_util_Div::toWebPath($sButtonUrl),
				"button_width" => "61",
				"button_height" => "22",
			),
		);

		$this->includeScripts($aConf);

		$sAddInputParams = $this->_getAddInputParams();

		$GLOBALS["_SESSION"]["ameos_formidable"]["ajax_services"][$sObject][$sServiceKey][$sSafeLock] = array(
			"requester" => array(
				"name" => $this->getAbsName(),
				"xpath" => $this->sXPath,
			),
		);

		return array(
			"__compiled" => $aButtonBrowse["__compiled"] . " " . $aButtonUpload["__compiled"] . " " . $aListQueue["__compiled"],
			"buttonBrowse" => $aButtonBrowse,
			"buttonUpload" => $aButtonUpload,
			"listQueue" => $aListQueue
		);
	}

	function includeScripts($aConf) {
		parent::includeScripts($aConf);
		$sAbsName = $this->getAbsName();

		$sInitScript =<<<INITSCRIPT

		try {
			Formidable.f("{$this->oForm->formid}").o("{$sAbsName}").oSWFUpload = new SWFUpload(
				Formidable.f("{$this->oForm->formid}").o("{$sAbsName}").config.swfupload_config
			);
		} catch(e) {
			//alert("SWFUpload exception !!!" + e.name + ":" + e.message);
			//throw(e);
		}

INITSCRIPT;

		# the SWFUpload initalization is made post-init
			# as when rendered in an ajax context in a modalbox,
			# the HTML is available *after* init tasks
			# as the modalbox HTML is added to the page using after init tasks !

		$this->oForm->attachPostInitTask(
			$sInitScript,
			"Test postinit SWFUPLOAD initialization",
			$this->_getElementHtmlId()
		);
	}

	function handleAjaxRequest($oRequest) {

		$oFile = tx_rnbase::makeInstance(tx_rnbase_util_Typo3Classes::getBasicFileUtilityClass());
		$aFile = $GLOBALS["_FILES"]["rdt_swfupload"];

		$sFileName = $aFile["name"];

		if($this->_defaultTrue("/usedenypattern") !== FALSE) {
			if(!Tx_Rnbase_Utility_T3General::verifyFilenameAgainstDenyPattern($sFileName)) {
				die("FILE EXTENSION DENIED");
			}
		}

		if($this->_defaultTrue("/cleanfilename") !== FALSE) {
			$sFileName = strtolower(
				$oFile->cleanFileName($sFileName)
			);
		}

		$sTargetDir = $this->getTargetDir();
		$sTarget = $sTargetDir . $sFileName;
		if(!file_exists($sTargetDir)) {
			if($this->defaultFalse("/data/targetdir/createifneeded") === TRUE) {
				// the target does not exist, we have to create it
				tx_mkforms_util_Div::mkdirDeepAbs($sTargetDir);
			}
		}

		if(!$this->_defaultFalse("/overwrite")) {
			$sExt = ((strpos($sFileName,'.') === FALSE) ? '' : '.' . substr(strrchr($sFileName, "."), 1));

			for($i=1; file_exists($sTarget); $i++) {
				$sTarget = $sTargetDir . substr($sFileName, 0, strlen($sFileName)-strlen($sExt)).'['.$i.']'.$sExt;
			}

			$sFileName = basename($sTarget);
		}

		Tx_Rnbase_Utility_T3General::upload_copy_move(
			$aFile["tmp_name"],
			$sTarget
		);

		die("OK: " . $sTarget);
	}

	function getTargetDir() {

		$oFileTool = tx_rnbase::makeInstance(tx_rnbase_util_Typo3Classes::getBasicFileUtilityClass());

		if($this->oForm->isRunneable(($sTargetDir = $this->_navConf("/data/targetdir/")))) {
			$sTargetDir = $this->getForm()->getRunnable()->callRunnableWidget($this, $sTargetDir);
		}

		return Tx_Rnbase_Utility_T3General::fixWindowsFilePath(
			$oFileTool->slashPath($sTargetDir)
		);
	}

	function initButtonBrowse() {
		if($this->oButtonBrowse === FALSE) {
			$sName = $this->getAbsName();

			$aConf = array(
				"type" => "BOX",
			);

			$aConf["name"] = $sName . "_btnbrowse";
			$this->oButtonBrowse = $this->oForm->_makeRenderlet(
				$aConf,
				$this->sXPath . "buttonbrowse/",
				FALSE,
				$this,
				FALSE,
				FALSE
			);

			$this->oForm->aORenderlets[$this->oButtonBrowse->getAbsName()] =& $this->oButtonBrowse;
		}
	}

	function initButtonUpload() {
		if($this->oButtonUpload === FALSE) {
			$sName = $this->getAbsName();

			$sEvent = <<<PHP

				return array(
					\$this->aORenderlets["{$sName}"]->majixStartUpload(),
				);

PHP;

			$aConf = array(
				"type" => "BUTTON",
				"label" => "Upload",
				"onclick-999" => array(			// 999 to avoid overruling by potential customly defined event
					"runat" => "client",
					"userobj" => array(
						"php" => $sEvent,
					),
				),
			);

			if(($aCustomConf = $this->_navConf("/buttonupload")) !== FALSE) {
				$aConf = tx_rnbase_util_Arrays::mergeRecursiveWithOverrule(
					$aConf,
					$aCustomConf
				);
			}

			$aConf["name"] = $sName . "_btnupload";

			$this->oButtonUpload = $this->oForm->_makeRenderlet(
				$aConf,
				$this->sXPath . "buttonupload/",
				FALSE,
				$this,
				FALSE,
				FALSE
			);

			$this->oForm->aORenderlets[$this->oButtonUpload->getAbsName()] =& $this->oButtonUpload;
		}
	}

	function initListQueue() {
		if($this->oListQueue === FALSE) {
			$sName = $this->getAbsName();

			$aConf = array(
				"type" => "LISTBOX",
				"label" => "Queue",
				"multiple" => true,
				"style" => "width: 100%"
			);

			if(($aCustomConf = $this->_navConf("/listqueue")) !== FALSE) {
				$aConf = tx_rnbase_util_Arrays::mergeRecursiveWithOverrule(
					$aConf,
					$aCustomConf
				);
			}

			$aConf["name"] = $sName . "_listqueue";

			$this->oListQueue = $this->oForm->_makeRenderlet(
				$aConf,
				$this->sXPath . "listqueue/",
				FALSE,
				$this,
				FALSE,
				FALSE
			);

			$this->oForm->aORenderlets[$this->oListQueue->getAbsName()] =& $this->oListQueue;


			$sEvent =<<<JAVASCRIPT

	aParams = this.getParams();
	this.rdt("{$sName}").addFileInQueue(
		aParams["sys_event"].file.name + " [" + aParams["sys_event"].file.humanSize + "]",
		aParams["sys_event"].file.id
	);

JAVASCRIPT;

			$this->aElement["onfilequeued-999"] = array(			// 999 to avoid overruling by potential customly defined event
				"runat" => "client",
				"userobj" => array(
					"js" => $sEvent,
				),
			);

			$sEvent =<<<JAVASCRIPT

	aParams = this.getParams();
	this.rdt("{$sName}").removeFileInQueue(
		aParams["sys_event"].file.id
	);

JAVASCRIPT;

			$this->aElement["onuploadsuccess-999"] = array(			// 999 to avoid overruling by potential customly defined event
				"runat" => "client",
				"userobj" => array(
					"js" => $sEvent,
				),
			);
		}
	}

	function majixSelectFiles() {
		return $this->buildMajixExecuter(
			"selectFiles"
		);
	}

	function majixStartUpload() {
		return $this->buildMajixExecuter(
			"startUpload"
		);
	}

	function getMaxUploadSize() {

		// sizes are all converted to KB

		$aSizes = array(
			"iPhpFileMax"	=> 1024 * intval(ini_get("upload_max_filesize")),
			"iPhpPostMax"	=> 1024 * intval(ini_get("post_max_size")),
			"iT3FileMax"	=> intval($GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize']),
		);

		if(($mFileSize = $this->_navConf("maxsize")) !== FALSE) {
			// maxSize has to be KB

			if($this->oForm->isRunneable($mFileSize)) {
				$mFileSize = $this->getForm()->getRunnable()->callRunnableWidget($this, $mFileSize);
			}

			$mFileSize = intval($mFileSize);
			if($mFileSize > 0) {
				$aSizes["userdefined"] = $mFileSize;
			}
		}

		asort($aSizes);
		return array_shift($aSizes);
	}

	function getQueueLimit() {
		if(($mLimit = $this->_navConf("/queuelimit")) !== FALSE) {
			if($this->oForm->isRunneable($mLimit)) {
				$mLimit = $this->getForm()->getRunnable()->callRunnableWidget($this, $mLimit);
			}

			return intval($mLimit);
		}

		return 0;	// no limit
	}

	function getFileType() {

		if(($mFileType = $this->_navConf("/filetype")) !== FALSE) {
			if($this->oForm->isRunneable($mFileType)) {
				$mFileType = $this->getForm()->getRunnable()->callRunnableWidget($this, $mFileType);
			}
			return $mFileType;
		}

		return "*.*";
	}

	function getFileTypeDesc() {

		$sFileTypeDesc = "LLL:EXT:mkforms/widgets/res/locallang.xml:filetypedesc.allfiles";

		if(($mFileTypeDesc = $this->_navConf("filetypedesc")) !== FALSE) {
			if($this->oForm->isRunneable($mFileTypeDesc)) {
				$mFileTypeDesc = $this->getForm()->getRunnable()->callRunnableWidget($this, $mFileTypeDesc);
			}

			$sFileTypeDesc = $mFileTypeDesc;

		}

		return $this->oForm->getConfigXML()->getLLLabel($sFileTypeDesc);
	}
}


	if (defined("TYPO3_MODE") && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]["XCLASS"]["ext/mkforms/widgets/swfupload/class.tx_rdtswfupload.php"])	{
		include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]["XCLASS"]["ext/mkforms/widgets/swfupload/class.tx_rdtswfupload.php"]);
	}
