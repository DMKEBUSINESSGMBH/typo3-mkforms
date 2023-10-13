<?php
/**
 * Plugin 'rdt_swfupload' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_widgets_swfupload_Main extends formidable_mainrenderlet
{
    public $aLibs = [
        'rdt_swfupload_lib' => 'res/js/swfupload.js',
        'rdt_swfupload_lib_cookies' => 'res/js/swfupload.cookies.js',
        'rdt_swfupload_lib_queue' => 'res/js/swfupload.queue.js',
        'rdt_swfupload_lib_queuetracker' => 'res/js/swfupload.queuetracker.js',
        'rdt_swfupload_class' => 'res/js/rdt_swfupload.js',
    ];

    public $sMajixClass = 'SwfUpload';

    public $oButtonBrowse = false;
    public $oButtonUpload = false;
    public $oListQueue = false;

    public $bCustomIncludeScript = true;

    public $aPossibleCustomEvents = [
        'onuploadprogress',
        'ondialogstart',
        'ondialogclose',
        'onuploadstart',
        'onuploadsuccess',
        'onuploaderror',
        'onuploadcomplete',
        'onfilequeued',
        'onqueueerror',
        'onqueueerrorfilesize',
        'onqueueerrorfiletype',
        'onqueuecomplete',
    ];

    public function _render()
    {
        // requesting mkformsAjax context for upload-service
        $this->getForm()->setStoreFormInSession();

        $this->initButtonBrowse();
        $this->initButtonUpload();
        $this->initListQueue();

        $aButtonBrowse = $this->oForm->_renderElement($this->oButtonBrowse);
        $aButtonUpload = $this->oForm->_renderElement($this->oButtonUpload);
        $aListQueue = $this->oForm->_renderElement($this->oListQueue);

        /* forging access to upload service */

        $sHtmlId = $this->_getElementHtmlId();
        $sObject = 'rdt_swfupload';
        $sServiceKey = 'upload';
        $sFormId = $this->oForm->formid;
        $sSafeLock = $this->_getSessionDataHashKey();
        $sThrower = $sHtmlId;

        $sUrl = tx_mkforms_util_Div::getCurrentBaseUrl().'/?mkformsAjaxId='.tx_mkforms_util_Div::getAjaxEId().'&pageId='.$GLOBALS['TSFE']->id.'&object='.$sObject.'&servicekey='.$sServiceKey.'&formid='.$sFormId.'&safelock='.$sSafeLock.'&thrower='.$sThrower;
        $sButtonUrl = $this->oForm->getConfigXML()->getLLLabel('LLL:EXT:mkforms/widgets/swfupload/res/locallang.xml:buttonbrowse.image_url');

        $aConf = [
            'buttonBrowseId' => $this->oButtonBrowse->_getElementHtmlId(),
            'buttonUploadId' => $this->oButtonUpload->_getElementHtmlId(),
            'listQueueId' => $this->oListQueue->_getElementHtmlId(),
            'swfupload_config' => [
                'upload_url' => $sUrl,
                'flash_url' => $this->sExtWebPath.'res/flash/swfupload.swf',
                'file_post_name' => 'rdt_swfupload',
                'file_size_limit' => $this->getMaxUploadSize(),    // KiloBytes

                'file_types_description' => $this->getFileTypeDesc(),
                'file_types' => $this->getFileType(),

                'file_queue_limit' => $this->getQueueLimit(),

                'button_placeholder_id' => $this->oButtonBrowse->_getElementHtmlId(),
                'button_image_url' => tx_mkforms_util_Div::toWebPath($sButtonUrl),
                'button_width' => '61',
                'button_height' => '22',
            ],
        ];

        $this->includeScripts($aConf);

        $GLOBALS['_SESSION']['ameos_formidable']['ajax_services'][$sObject][$sServiceKey][$sSafeLock] = [
            'requester' => [
                'name' => $this->getAbsName(),
                'xpath' => $this->sXPath,
            ],
        ];

        return [
            '__compiled' => $aButtonBrowse['__compiled'].' '.$aButtonUpload['__compiled'].' '.$aListQueue['__compiled'],
            'buttonBrowse' => $aButtonBrowse,
            'buttonUpload' => $aButtonUpload,
            'listQueue' => $aListQueue,
        ];
    }

    public function includeScripts($aConf)
    {
        parent::includeScripts($aConf);
        $sAbsName = $this->getAbsName();

        $sInitScript = <<<INITSCRIPT

		try {
			Formidable.f("{$this->oForm->formid}").o("{$sAbsName}").oSWFUpload = new SWFUpload(
				Formidable.f("{$this->oForm->formid}").o("{$sAbsName}").config.swfupload_config
			);
		} catch(e) {
			//alert("SWFUpload exception !!!" + e.name + ":" + e.message);
			//throw(e);
		}

INITSCRIPT;

        // the SWFUpload initalization is made post-init
        // as when rendered in an ajax context in a modalbox,
        // the HTML is available *after* init tasks
        // as the modalbox HTML is added to the page using after init tasks !

        $this->oForm->attachPostInitTask(
            $sInitScript,
            'Test postinit SWFUPLOAD initialization',
            $this->_getElementHtmlId()
        );
    }

    public function handleAjaxRequest($oRequest)
    {
        $oFile = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Sys25\RnBase\Utility\Typo3Classes::getBasicFileUtilityClass());
        $aFile = $GLOBALS['_FILES']['rdt_swfupload'];

        $sFileName = $aFile['name'];

        if (false !== $this->_defaultTrue('/usedenypattern')) {
            if (!\Sys25\RnBase\Utility\T3General::verifyFilenameAgainstDenyPattern($sFileName)) {
                exit('FILE EXTENSION DENIED');
            }
        }

        if (false !== $this->_defaultTrue('/cleanfilename')) {
            $sFileName = strtolower(
                $oFile->cleanFileName($sFileName)
            );
        }

        $sTargetDir = $this->getTargetDir();
        $sTarget = $sTargetDir.$sFileName;
        if (!file_exists($sTargetDir)) {
            if (true === $this->defaultFalse('/data/targetdir/createifneeded')) {
                // the target does not exist, we have to create it
                tx_mkforms_util_Div::mkdirDeepAbs($sTargetDir);
            }
        }

        if (!$this->_defaultFalse('/overwrite')) {
            $sExt = ((false === strpos($sFileName, '.')) ? '' : '.'.substr(strrchr($sFileName, '.'), 1));

            for ($i = 1; file_exists($sTarget); ++$i) {
                $sTarget = $sTargetDir.substr($sFileName, 0, strlen($sFileName) - strlen($sExt)).'['.$i.']'.$sExt;
            }
        }

        \Sys25\RnBase\Utility\T3General::upload_copy_move(
            $aFile['tmp_name'],
            $sTarget
        );

        exit('OK: '.$sTarget);
    }

    public function getTargetDir()
    {
        $oFileTool = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Sys25\RnBase\Utility\Typo3Classes::getBasicFileUtilityClass());

        if ($this->oForm->isRunneable(($sTargetDir = $this->getConfigValue('/data/targetdir/')))) {
            $sTargetDir = $this->getForm()->getRunnable()->callRunnableWidget($this, $sTargetDir);
        }

        return \Sys25\RnBase\Utility\T3General::fixWindowsFilePath(
            $oFileTool->slashPath($sTargetDir)
        );
    }

    public function initButtonBrowse()
    {
        if (false === $this->oButtonBrowse) {
            $sName = $this->getAbsName();

            $aConf = [
                'type' => 'BOX',
            ];

            $aConf['name'] = $sName.'_btnbrowse';
            $this->oButtonBrowse = $this->oForm->_makeRenderlet(
                $aConf,
                $this->sXPath.'buttonbrowse/',
                false,
                $this,
                false,
                false
            );

            $this->oForm->aORenderlets[$this->oButtonBrowse->getAbsName()] = &$this->oButtonBrowse;
        }
    }

    public function initButtonUpload()
    {
        if (false === $this->oButtonUpload) {
            $sName = $this->getAbsName();

            $sEvent = <<<PHP

				return array(
					\$this->aORenderlets["{$sName}"]->majixStartUpload(),
				);

PHP;

            $aConf = [
                'type' => 'BUTTON',
                'label' => 'Upload',
                'onclick-999' => [            // 999 to avoid overruling by potential customly defined event
                    'runat' => 'client',
                    'userobj' => [
                        'php' => $sEvent,
                    ],
                ],
            ];

            if (false !== ($aCustomConf = $this->getConfigValue('/buttonupload'))) {
                $aConf = \Sys25\RnBase\Utility\Arrays::mergeRecursiveWithOverrule(
                    $aConf,
                    $aCustomConf
                );
            }

            $aConf['name'] = $sName.'_btnupload';

            $this->oButtonUpload = $this->oForm->_makeRenderlet(
                $aConf,
                $this->sXPath.'buttonupload/',
                false,
                $this,
                false,
                false
            );

            $this->oForm->aORenderlets[$this->oButtonUpload->getAbsName()] = &$this->oButtonUpload;
        }
    }

    public function initListQueue()
    {
        if (false === $this->oListQueue) {
            $sName = $this->getAbsName();

            $aConf = [
                'type' => 'LISTBOX',
                'label' => 'Queue',
                'multiple' => true,
                'style' => 'width: 100%',
            ];

            if (false !== ($aCustomConf = $this->getConfigValue('/listqueue'))) {
                $aConf = \Sys25\RnBase\Utility\Arrays::mergeRecursiveWithOverrule(
                    $aConf,
                    $aCustomConf
                );
            }

            $aConf['name'] = $sName.'_listqueue';

            $this->oListQueue = $this->oForm->_makeRenderlet(
                $aConf,
                $this->sXPath.'listqueue/',
                false,
                $this,
                false,
                false
            );

            $this->oForm->aORenderlets[$this->oListQueue->getAbsName()] = &$this->oListQueue;

            $sEvent = <<<JAVASCRIPT

	aParams = this.getParams();
	this.rdt("{$sName}").addFileInQueue(
		aParams["sys_event"].file.name + " [" + aParams["sys_event"].file.humanSize + "]",
		aParams["sys_event"].file.id
	);

JAVASCRIPT;

            $this->aElement['onfilequeued-999'] = [            // 999 to avoid overruling by potential customly defined event
                'runat' => 'client',
                'userobj' => [
                    'js' => $sEvent,
                ],
            ];

            $sEvent = <<<JAVASCRIPT

	aParams = this.getParams();
	this.rdt("{$sName}").removeFileInQueue(
		aParams["sys_event"].file.id
	);

JAVASCRIPT;

            $this->aElement['onuploadsuccess-999'] = [            // 999 to avoid overruling by potential customly defined event
                'runat' => 'client',
                'userobj' => [
                    'js' => $sEvent,
                ],
            ];
        }
    }

    public function majixSelectFiles()
    {
        return $this->buildMajixExecuter(
            'selectFiles'
        );
    }

    public function majixStartUpload()
    {
        return $this->buildMajixExecuter(
            'startUpload'
        );
    }

    public function getMaxUploadSize()
    {
        // sizes are all converted to KB

        $aSizes = [
            'iPhpFileMax' => 1024 * (int) ini_get('upload_max_filesize'),
            'iPhpPostMax' => 1024 * (int) ini_get('post_max_size'),
            'iT3FileMax' => (int) $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
        ];

        if (false !== ($mFileSize = $this->getConfigValue('maxsize'))) {
            // maxSize has to be KB

            if ($this->oForm->isRunneable($mFileSize)) {
                $mFileSize = $this->getForm()->getRunnable()->callRunnableWidget($this, $mFileSize);
            }

            $mFileSize = (int) $mFileSize;
            if ($mFileSize > 0) {
                $aSizes['userdefined'] = $mFileSize;
            }
        }

        asort($aSizes);

        return array_shift($aSizes);
    }

    public function getQueueLimit()
    {
        if (false !== ($mLimit = $this->getConfigValue('/queuelimit'))) {
            if ($this->oForm->isRunneable($mLimit)) {
                $mLimit = $this->getForm()->getRunnable()->callRunnableWidget($this, $mLimit);
            }

            return (int) $mLimit;
        }

        return 0;    // no limit
    }

    public function getFileType()
    {
        if (false !== ($mFileType = $this->getConfigValue('/filetype'))) {
            if ($this->oForm->isRunneable($mFileType)) {
                $mFileType = $this->getForm()->getRunnable()->callRunnableWidget($this, $mFileType);
            }

            return $mFileType;
        }

        return '*.*';
    }

    public function getFileTypeDesc()
    {
        $sFileTypeDesc = 'LLL:EXT:mkforms/widgets/res/locallang.xml:filetypedesc.allfiles';

        if (false !== ($mFileTypeDesc = $this->getConfigValue('filetypedesc'))) {
            if ($this->oForm->isRunneable($mFileTypeDesc)) {
                $mFileTypeDesc = $this->getForm()->getRunnable()->callRunnableWidget($this, $mFileTypeDesc);
            }

            $sFileTypeDesc = $mFileTypeDesc;
        }

        return $this->oForm->getConfigXML()->getLLLabel($sFileTypeDesc);
    }
}
