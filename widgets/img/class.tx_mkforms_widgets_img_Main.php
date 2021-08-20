<?php
/**
 * Plugin 'rdt_img' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_widgets_img_Main extends formidable_mainrenderlet
{
    public function _render()
    {
        return $this->_renderReadOnly();
    }

    public function _renderReadOnly()
    {
        $sPath = $this->_getPath();
        if (false !== $sPath || (
                is_array($this->_navConf('/imageconf/')) &&
                $this->defaultFalse('/imageconf/forcegeneration')
            )
        ) {
            $sTag = false;
            $aSize = false;
            $bReprocess = false;

            if (is_array($mConf = $this->_navConf('/imageconf/')) && $this->oForm->isRunneable($mConf)) {
                $bReprocess = true;
            }

            if (tx_mkforms_util_Div::isAbsServerPath($sPath)) {
                $sAbsServerPath = $sPath;
                $sRelWebPath = tx_mkforms_util_Div::removeStartingSlash(tx_mkforms_util_Div::toRelPath($sAbsServerPath));
                $sAbsWebPath = tx_mkforms_util_Div::toWebPath($sRelWebPath);
                $sFileName = basename($sRelWebPath);
                $aSize = @getimagesize($sAbsServerPath);
            } else {
                if (!tx_mkforms_util_Div::isAbsWebPath($sPath)) {
                    // relative web path given
                    // turn it into absolute web path
                    $sPath = tx_mkforms_util_Div::toWebPath($sPath);
                    $aSize = @getimagesize(tx_mkforms_util_Div::toServerPath($sPath));
                }

                // absolute web path
                $sAbsWebPath = $sPath;

                $aInfosPath = parse_url($sAbsWebPath);

                $aInfosFile = Tx_Rnbase_Utility_T3General::split_fileref($sAbsWebPath);
                if (strtolower($aInfosPath['host']) !== strtolower(Tx_Rnbase_Utility_T3General::getIndpEnv('TYPO3_HOST_ONLY'))) {
                    if (true === $bReprocess) {
                        // we have to make a local copy of the image to enable TS processing
                        $aHeaders = $this->oForm->div_getHeadersForUrl($sAbsWebPath);
                        if (array_key_exists('ETag', $aHeaders)) {
                            $sSignature = str_replace(
                                ['"', ',', ':', '-', '.', '/', '\\', ' '],    // removing separators and protecting againts backpath hacks
                                '',
                                $aHeaders['ETag']
                            );
                        } elseif (array_key_exists('Last-Modified', $aHeaders)) {
                            $sSignature = str_replace(
                                ['"', ',', ':', '-', '.', '/', '\\', ' '],    // removing separators and protecting againts backpath hacks
                                '',
                                $aHeaders['Last-Modified']
                            );
                        } elseif (array_key_exists('Content-Length', $aHeaders)) {
                            $sSignature = $aHeaders['Content-Length'];
                        }
                    }

                    $sTempFileName = $aInfosFile['filebody'].$aInfosFile['fileext'].'-'.$sSignature.'.'.$aInfosFile['fileext'];
                    $sTempFilePath = \Sys25\RnBase\Utility\Environment::getPublicPath().'typo3temp/assets/'.$sTempFileName;

                    if (!file_exists($sTempFilePath)) {
                        Tx_Rnbase_Utility_T3General::writeFileToTypo3tempDir(
                            $sTempFilePath,
                            Tx_Rnbase_Utility_T3General::getUrl($sAbsWebPath)
                        );
                    }

                    $sAbsServerPath = $sTempFilePath;
                    $sAbsWebPath = tx_mkforms_util_Div::toWebPath($sAbsServerPath);
                    $sRelWebPath = tx_mkforms_util_Div::toRelPath($sAbsServerPath);
                } else {
                    // it's an local image given as an absolute web url
                    // trying to convert pathes to handle the image as a local one
                    if (Tx_Rnbase_Utility_T3General::getIndpEnv('TYPO3_REQUEST_DIR') == substr($sAbsWebPath, 0, strlen(Tx_Rnbase_Utility_T3General::getIndpEnv('TYPO3_REQUEST_DIR')))) {
                        $sTrimmedWebPath = substr($sAbsWebPath, strlen(Tx_Rnbase_Utility_T3General::getIndpEnv('TYPO3_REQUEST_DIR')));
                    } else {
                        $sTrimmedWebPath = $aInfosPath['path'];
                    }

                    $sAbsServerPath = \Sys25\RnBase\Utility\Environment::getPublicPath().tx_mkforms_util_Div::removeStartingSlash($sTrimmedWebPath);
                    $sRelWebPath = tx_mkforms_util_Div::removeStartingSlash($sTrimmedWebPath);
                }

                $sFileName = $aInfosFile['file'];
            }

            $sRelWebPath = tx_mkforms_util_Div::removeStartingSlash($sRelWebPath);

            $aHtmlBag = [
                'filepath' => $sAbsWebPath,
                'filepath.' => [
                    'rel' => $sRelWebPath,
                    'web' => tx_mkforms_util_Div::toWebPath(tx_mkforms_util_Div::toServerPath($sRelWebPath)),
                    'original' => $sAbsWebPath,
                    'original.' => [
                        'rel' => $sRelWebPath,
                        'web' => tx_mkforms_util_Div::toWebPath($sAbsServerPath),
                        'server' => $sAbsServerPath,
                    ],
                ],
                'filename' => $sFileName,
                'filename.' => [
                    'original' => $sFileName,
                ],
            ];

            if (false !== $aSize) {
                $aHtmlBag['filesize.']['width'] = $aSize[0];
                $aHtmlBag['filesize.']['width.']['px'] = $aSize[0].'px';
                $aHtmlBag['filesize.']['height'] = $aSize[1];
                $aHtmlBag['filesize.']['height.']['px'] = $aSize[1].'px';
            }

            if (true === $bReprocess) {
                // expecting typoscript

                $aParams = [
                    'filename' => $sFileName,
                    'abswebpath' => $sAbsWebPath,
                    'relwebpath' => $sRelWebPath,
                ];

                if ('LISTER' == $this->oForm->oDataHandler->aObjectType['TYPE']) {
                    $aParams['row'] = $this->oForm->oDataHandler->__aListData;
                } elseif ('DB' == $this->oForm->oDataHandler->aObjectType['TYPE']) {
                    $aParams['row'] = $this->oForm->oDataHandler->_getStoredData();
                }

                $this->getForm()->getRunnable()->callRunnableWidget($this, $mConf, $aParams);

                $aImage = array_pop($this->getForm()->getRunnable()->aLastTs);

                $sTag = (true === $this->_defaultFalse('/imageconf/generatetag')) ? $GLOBALS['TSFE']->cObj->IMAGE($aImage) : false;

                // IMG_RESOURCE always returns relative path
                $sNewPath = $GLOBALS['TSFE']->cObj->cObjGetSingle('IMG_RESOURCE', $aImage);

                $aHtmlBag['filepath'] = tx_mkforms_util_Div::toWebPath($sNewPath);
                $aHtmlBag['filepath.']['rel'] = $sNewPath;
                $aHtmlBag['filepath.']['web'] = tx_mkforms_util_Div::toWebPath(tx_mkforms_util_Div::toServerPath($sNewPath));
                $aHtmlBag['filename'] = basename($sNewPath);

                $aNewSize = @getimagesize(tx_mkforms_util_Div::toServerPath($sNewPath));

                $aHtmlBag['filesize.']['width'] = $aNewSize[0];
                $aHtmlBag['filesize.']['width.']['px'] = $aNewSize[0].'px';
                $aHtmlBag['filesize.']['height'] = $aNewSize[1];
                $aHtmlBag['filesize.']['height.']['px'] = $aNewSize[1].'px';
            }

            $sLabel = $this->getLabel();

            if (false === $sTag) {
                if (isset($aHtmlBag['filesize.']['width'])) {
                    $sWidth = ' width="'.$aHtmlBag['filesize.']['width'].'" ';
                }

                if (isset($aHtmlBag['filesize.']['height'])) {
                    $sHeight = ' height="'.$aHtmlBag['filesize.']['height'].'" ';
                }

                $aHtmlBag['imagetag'] = '<img src="'.$aHtmlBag['filepath'].'" id="'.$this->_getElementHtmlId().'" '.$this->_getAddInputParams().' '.$sWidth.$sHeight.'/>';
            } else {
                $aHtmlBag['imagetag'] = $sTag;
            }

            $aHtmlBag['__compiled'] = $this->_displayLabel($sLabel).$aHtmlBag['imagetag'];

            return $aHtmlBag;
        }

        return '';
    }

    public function _getPath()
    {
        if (false !== ($sPath = $this->_navConf('/path'))) {
            $sPath = $this->_processPath($sPath);
        }
        if (tx_mkforms_util_Div::isAbsWebPath($sPath)) {
            return $sPath;
        } else {
            // FAL-Referenz?
            if (tx_rnbase_util_Math::isInteger($sPath) && $this->defaultFalse('/treatidasreference')) {
                $reference = tx_rnbase_util_TSFAL::getFileReferenceById($sPath);
                $sPath = $reference->getForLocalProcessing(false);
            } elseif (false !== ($mFolder = $this->_navConf('/folder'))) {
                if ($this->oForm->isRunneable($mFolder)) {
                    $mFolder = $this->getForm()->getRunnable()->callRunnableWidget($this, $mFolder);
                }

                $sPath = tx_mkforms_util_Div::trimSlashes($mFolder).'/'.$this->getValue();
            } else {
                $sPath = $this->getValue();
            }

            if ($this->defaultFalse('/imageconf/forceurldecode')) {
                $sPath = rawurldecode($sPath);
            }

            $sFullPath = tx_mkforms_util_Div::toServerPath($sPath);

            if (!file_exists($sFullPath) || !is_file($sFullPath) || !is_readable($sFullPath)) {
                if (false !== ($sDefaultPath = $this->_navConf('/defaultpath'))) {
                    $sDefaultPath = $this->_processPath($sDefaultPath);
                    $sFullDefaultPath = tx_mkforms_util_Div::toServerPath($sDefaultPath);

                    if (!file_exists($sFullDefaultPath) || !is_file($sFullDefaultPath) || !is_readable($sFullDefaultPath)) {
                        return false;
                    } else {
                        return $sDefaultPath;
                    }
                }

                return false;
            } else {
                return $sFullPath;
            }
        }
    }

    public function _processPath($sPath)
    {
        if ($this->oForm->isRunneable($sPath)) {
            $sPath = $this->getForm()->getRunnable()->callRunnableWidget($this, $sPath);
        }

        if (Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sPath, 'EXT:')) {
            $sPath = $this->oForm->_removeStartingSlash(
                tx_mkforms_util_Div::toRelPath(
                    Tx_Rnbase_Utility_T3General::getFileAbsFileName($sPath)
                )
            );
        }

        return $sPath;
    }

    public function _renderOnly($bForAjax = false)
    {
        return true;
    }

    public function _readOnly()
    {
        return true;
    }

    public function _getHumanReadableValue($data)
    {
        return $this->_renderReadOnly();
    }

    public function _activeListable()
    {
        // listable as an active HTML FORM field or not in the lister
        return $this->oForm->_defaultTrue('/activelistable/', $this->aElement);
    }

    public function getFullServerPath($sPath)
    {
        return tx_mkforms_util_Div::toServerPath($sPath);
    }

    public function _getAddInputParamsArray($aAdditional = [])
    {
        $aAddParams = parent::_getAddInputParamsArray();

        if (false !== ($mUseMap = $this->_navConf('/usemap'))) {
            if ($this->oForm->isRunneable($mUseMap)) {
                $mUseMap = $this->getForm()->getRunnable()->callRunnableWidget($this, $mUseMap);
            }

            if (false !== $mUseMap) {
                $aAddParams[] = ' usemap="'.$mUseMap.'" ';
            }
        }

        if (false !== ($mAlt = $this->_navConf('/alt'))) {
            if ($this->oForm->isRunneable($mAlt)) {
                $mAlt = $this->getForm()->getRunnable()->callRunnableWidget($this, $mAlt);
            }

            if (false !== $mAlt) {
                $aAddParams[] = ' alt="'.$mAlt.'" ';
            }
        }

        reset($aAddParams);

        return $aAddParams;
    }
}
