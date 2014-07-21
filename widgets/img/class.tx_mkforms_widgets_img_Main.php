<?php
/**
 * Plugin 'rdt_img' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */

require_once(PATH_t3lib . 'class.t3lib_basicfilefunc.php');

tx_rnbase::load('tx_mkforms_util_Div');

class tx_mkforms_widgets_img_Main extends formidable_mainrenderlet {

	function _render() {
		return $this->_renderReadOnly();
	}

	function _renderReadOnly() {

		$sPath = $this->_getPath();

		if(
			$sPath !== FALSE || (
				is_array($this->_navConf('/imageconf/')) &&
				$this->defaultFalse('/imageconf/forcegeneration')
			)
		) {

			$sTag = FALSE;
			$aSize = FALSE;
			$bExternal = FALSE;
			$bReprocess = FALSE;

			if(is_array($mConf = $this->_navConf('/imageconf/')) && $this->oForm->isRunneable($mConf)) {
				$bReprocess = TRUE;
			}

			if(tx_mkforms_util_Div::isAbsServerPath($sPath)) {
				$sAbsServerPath = $sPath;
				$sRelWebPath = tx_mkforms_util_Div::removeStartingSlash(tx_mkforms_util_Div::toRelPath($sAbsServerPath));
				$sAbsWebPath = tx_mkforms_util_Div::toWebPath($sRelWebPath);
				$sFileName = basename($sRelWebPath);
				$aSize = @getImageSize($sAbsServerPath);
			} else {
				if(!tx_mkforms_util_Div::isAbsWebPath($sPath)) {
					// relative web path given
						// turn it into absolute web path
					$sPath = tx_mkforms_util_Div::toWebPath($sPath);
					$aSize = @getImageSize(tx_mkforms_util_Div::toServerPath($sPath));
				}

				// absolute web path
				$sAbsWebPath = $sPath;

				$aInfosPath = parse_url($sAbsWebPath);

				$aInfosFile = t3lib_div::split_fileref($sAbsWebPath);
				#debug($aInfosPath);
				#debug($aInfosFile);
				if(strtolower($aInfosPath['host']) !== strtolower(t3lib_div::getIndpEnv('TYPO3_HOST_ONLY'))) {

					// it's an external image

					$bExternal = TRUE;
					$sAbsServerPath = '';
					if($bReprocess === TRUE) {
						// we have to make a local copy of the image to enable TS processing
						$aHeaders = $this->oForm->div_getHeadersForUrl($sAbsWebPath);
						if(array_key_exists('ETag', $aHeaders)) {
							$sSignature = str_replace(
								array('"', ',', ':', '-', '.', '/', "\\", ' '),	// removing separators and protecting againts backpath hacks
								'',
								$aHeaders['ETag']
							);
						} elseif(array_key_exists('Last-Modified', $aHeaders)) {
							$sSignature = str_replace(
								array('"', ',', ':', '-', '.', '/', "\\", ' '),	// removing separators and protecting againts backpath hacks
								'',
								$aHeaders['Last-Modified']
							);
						} elseif(array_key_exists('Content-Length', $aHeaders)) {
							$sSignature = $aHeaders['Content-Length'];
						}
					}

					$sTempFileName = $aInfosFile['filebody'] . $aInfosFile['fileext'] . '-' . $sSignature . '.' . $aInfosFile['fileext'];
					$sTempFilePath = PATH_site . 'typo3temp/' . $sTempFileName;

					if(!file_exists($sTempFilePath)) {
						t3lib_div::writeFileToTypo3tempDir(
							$sTempFilePath,
							t3lib_div::getUrl($sAbsWebPath)
						);
					}

					$sAbsServerPath = $sTempFilePath;
					$sAbsWebPath = tx_mkforms_util_Div::toWebPath($sAbsServerPath);
					$sRelWebPath = tx_mkforms_util_Div::toRelPath($sAbsServerPath);

				} else {
					// it's an local image given as an absolute web url
						// trying to convert pathes to handle the image as a local one
					if(t3lib_div::getIndpEnv('TYPO3_REQUEST_DIR') ==  substr($sAbsWebPath,0,strlen(t3lib_div::getIndpEnv('TYPO3_REQUEST_DIR'))))
						$sTrimmedWebPath = substr($sAbsWebPath,strlen(t3lib_div::getIndpEnv('TYPO3_REQUEST_DIR')));
					else
						$sTrimmedWebPath = $aInfosPath['path'];

					$sAbsServerPath = PATH_site . tx_mkforms_util_Div::removeStartingSlash($sTrimmedWebPath);
					$sRelWebPath = tx_mkforms_util_Div::removeStartingSlash($sTrimmedWebPath);
				}

				$sFileName = $aInfosFile['file'];
			}

			$sRelWebPath = tx_mkforms_util_Div::removeStartingslash($sRelWebPath);

			$aHtmlBag = array(
				'filepath' => $sAbsWebPath,
				'filepath.' => array(
					'rel' => $sRelWebPath,
					'web' => tx_mkforms_util_Div::toWebPath(tx_mkforms_util_Div::toServerPath($sRelWebPath)),
					'original' => $sAbsWebPath,
					'original.' => array(
						'rel' => $sRelWebPath,
						'web' => tx_mkforms_util_Div::toWebPath($sAbsServerPath),
						'server' => $sAbsServerPath,
					)
				),
				'filename' =>$sFileName,
				'filename.' => array(
					'original' => $sFileName,
				)
			);

			if($aSize !== FALSE) {
				$aHtmlBag['filesize.']['width'] = $aSize[0];
				$aHtmlBag['filesize.']['width.']['px'] = $aSize[0] . 'px';
				$aHtmlBag['filesize.']['height'] = $aSize[1];
				$aHtmlBag['filesize.']['height.']['px'] = $aSize[1] . 'px';
			}

			if($bReprocess === TRUE) {

				require_once(PATH_t3lib . 'class.t3lib_stdgraphic.php');
				require_once(PATH_tslib . 'class.tslib_gifbuilder.php');
				// expecting typoscript

				$aParams = array(
					'filename' => $sFileName,
					'abswebpath' => $sAbsWebPath,
					'relwebpath' => $sRelWebPath,
				);

				if($this->oForm->oDataHandler->aObjectType['TYPE'] == 'LISTER') {
					$aParams['row'] = $this->oForm->oDataHandler->__aListData;
				} elseif($this->oForm->oDataHandler->aObjectType['TYPE'] == 'DB') {
					$aParams['row'] = $this->oForm->oDataHandler->_getStoredData();
				}

				$this->getForm()->getRunnable()->callRunnableWidget($this,$mConf,$aParams);

				$aImage = array_pop($this->getForm()->getRunnable()->aLastTs);

				$sTag = ($this->_defaultFalse('/imageconf/generatetag') === TRUE) ? $GLOBALS['TSFE']->cObj->IMAGE($aImage) : FALSE;

				$sNewPath = $GLOBALS['TSFE']->cObj->IMG_RESOURCE($aImage);	// IMG_RESOURCE always returns relative path

				$aHtmlBag['filepath'] = tx_mkforms_util_Div::toWebPath($sNewPath);
				$aHtmlBag['filepath.']['rel'] = $sNewPath;
				$aHtmlBag['filepath.']['web'] = tx_mkforms_util_Div::toWebPath(tx_mkforms_util_Div::toServerPath($sNewPath));
				$aHtmlBag['filename'] = basename($sNewPath);

				$aNewSize = @getImageSize(tx_mkforms_util_Div::toServerPath($sNewPath));

				$aHtmlBag['filesize.']['width'] = $aNewSize[0];
				$aHtmlBag['filesize.']['width.']['px'] = $aNewSize[0] . 'px';
				$aHtmlBag['filesize.']['height'] = $aNewSize[1];
				$aHtmlBag['filesize.']['height.']['px'] = $aNewSize[1] . 'px';
			}

			$sLabel = $this->getLabel();

			if($sTag === FALSE) {
				if(isset($aHtmlBag['filesize.']['width'])) {
					$sWidth = ' width="' . $aHtmlBag['filesize.']['width'] . '" ';
				}

				if(isset($aHtmlBag['filesize.']['height'])) {
					$sHeight = ' height="' . $aHtmlBag['filesize.']['height'] . '" ';
				}

				$aHtmlBag['imagetag'] = '<img src="' . $aHtmlBag['filepath'] . '" id="' . $this->_getElementHtmlId() . '" ' . $this->_getAddInputParams() . ' ' . $sWidth . $sHeight . '/>';
				#print_r($aHtmlBag['imagetag']);
			} else {
				$aHtmlBag['imagetag'] = $sTag;
			}

			$aHtmlBag['__compiled'] = $this->_displayLabel($sLabel) . $aHtmlBag['imagetag'];

			return $aHtmlBag;
		}

		return '';
	}

	function _getPath() {

		if(($sPath = $this->_navConf('/path')) !== FALSE) {
			$sPath = $this->_processPath($sPath);
		}

		if(tx_mkforms_util_Div::isAbsWebPath($sPath)) {
			return $sPath;
		} else {

			if(($mFolder = $this->_navConf('/folder')) !== FALSE) {
				if($this->oForm->isRunneable($mFolder)) {
					$mFolder = $this->getForm()->getRunnable()->callRunnableWidget($this, $mFolder);
				}

				$sPath = tx_mkforms_util_Div::trimSlashes($mFolder) . '/' . $this->getValue();
			} else {
				$sPath = $this->getValue();
			}

			$sFullPath = tx_mkforms_util_Div::toServerPath($sPath);

			if(!file_exists($sFullPath) || !is_file($sFullPath) || !is_readable($sFullPath)) {
				if(($sDefaultPath = $this->_navConf('/defaultpath')) !== FALSE) {

					$sDefaultPath = $this->_processPath($sDefaultPath);
					$sFullDefaultPath = tx_mkforms_util_Div::toServerPath($sDefaultPath);

					if(!file_exists($sFullDefaultPath) || !is_file($sFullDefaultPath) || !is_readable($sFullDefaultPath)) {
						return FALSE;
					} else {
						return $sDefaultPath;
					}
				}

				return FALSE;
			} else {
				return $sFullPath;
			}
		}

		return $sPath;
	}

	function _processPath($sPath) {

		if($this->oForm->isRunneable($sPath)) {
			$sPath = $this->getForm()->getRunnable()->callRunnableWidget($this, $sPath);
		}

		if(t3lib_div::isFirstPartOfStr($sPath, 'EXT:')) {

			$sPath = tx_mkforms_util_Div::removeStartingSlash(
				tx_mkforms_util_Div::toRelPath(
					t3lib_div::getFileAbsFileName($sPath)
				)
			);
		}

		return $sPath;
	}

	function _renderOnly() {
		return TRUE;
	}

	function _readOnly() {
		return TRUE;
	}

	function _getHumanReadableValue($data) {
		return $this->_renderReadOnly();
	}

	function _activeListable() {		// listable as an active HTML FORM field or not in the lister
		return $this->oForm->_defaultTrue('/activelistable/', $this->aElement);
	}

	function getFullServerPath($sPath) {
		return tx_mkforms_util_Div::toServerPath($sPath);
	}

	function _getAddInputParamsArray() {

		$aAddParams = parent::_getAddInputParamsArray();

		if(($mUseMap = $this->_navConf('/usemap')) !== FALSE) {

			if($this->oForm->isRunneable($mUseMap)) {
				$mUseMap = $this->getForm()->getRunnable()->callRunnableWidget($this, $mUseMap);
			}

			if($mUseMap !== FALSE) {
				$aAddParams[] = ' usemap="' . $mUseMap . '" ';
			}
		}

		if(($mAlt = $this->_navConf('/alt')) !== FALSE) {

			if($this->oForm->isRunneable($mAlt)) {
				$mAlt = $this->getForm()->getRunnable()->callRunnableWidget($this, $mAlt);
			}

			if($mAlt !== FALSE) {
				$aAddParams[] = ' alt="' . $mAlt . '" ';
			}
		}

		reset($aAddParams);
		return $aAddParams;
	}
}


	if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_img/api/class.tx_rdtimg.php'])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_img/api/class.tx_rdtimg.php']);
	}

?>
