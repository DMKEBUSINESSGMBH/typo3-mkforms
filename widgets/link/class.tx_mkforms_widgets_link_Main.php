<?php
/**
 * Plugin 'rdt_link' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_widgets_link_Main extends formidable_mainrenderlet
{
    public $aLibs = array(
        'rdt_link_class' => 'res/js/link.js',
    );
    public $bCustomIncludeScript = true;

    public $sMajixClass = 'Link';

    public function _render()
    {
        return $this->_renderReadOnly();
    }

    public function _renderReadOnly()
    {
        $sValue = $this->getValue();

        if ($this->_isTrue('/typolink')) {
            $sUrl = $this->getForm()->getCObj()->typoLink_URL(array(
                'parameter' => $sValue,
                'additionalParams' => '',
            ));
        } elseif ((false !== ($iPageId = $this->_navConf('pageid'))) ||
            (false !== ($iPageId = $this->_navConf('pid')))
        ) {
            if ($this->oForm->isRunneable($iPageId)) {
                $iPageId = $this->getForm()->getRunnable()->callRunnableWidget($this, $iPageId);
            }
            $absoluteUrl = $this->_defaultFalse('forceabsoluteurl');
            $sUrl = $this->getForm()->getCObj()->typoLink_URL(array(
                'parameter' => $iPageId ? $iPageId : $GLOBALS['TSFE']->id,
                'additionalParams' => '',
                'forceAbsoluteUrl' => $absoluteUrl,
            ));
        } else {
            $sUrl = $this->_navConf('/href');

            if (false === $sUrl) {
                $sUrl = $this->_navConf('/url');
            }

            if ($this->oForm->isRunneable($sUrl)) {
                $sUrl = $this->getForm()->getRunnable()->callRunnableWidget($this, $sUrl);
            }

            if (!$sUrl) {
                $sValue = trim($sValue);
                $aParsedURL = @parse_url($sValue);

                if (Tx_Rnbase_Utility_T3General::inList('ftp,ftps,http,https,gopher,telnet', $aParsedURL['scheme'])) {
                    $sUrl = $sValue;
                } else {
                    $sUrl = false;
                }
            }
        }

        if (false !== ($sAnchor = $this->_navConf('/anchor'))) {
            if ($this->oForm->isRunneable($sAnchor)) {
                $sAnchor = $this->getForm()->getRunnable()->callRunnableWidget($this, $sAnchor);
            }

            if (is_string($sAnchor) && '' !== $sAnchor) {
                $sAnchor = str_replace('#', '', $sAnchor);
            } else {
                $sAnchor = '';
            }

            if ('' !== $sAnchor) {
                if (false === $sUrl) {
                    $sUrl = Tx_Rnbase_Utility_T3General::getIndpEnv('REQUEST_URI');
                }

                if (array_key_exists($sAnchor, $this->oForm->aORenderlets)) {
                    $sAnchor = $this->oForm->aORenderlets[$sAnchor]->_getElementHtmlId();
                }

                $sAnchor = '#'.$sAnchor;
            }
        }

        if (false !== $sUrl) {
            if (false !== $sAnchor) {
                $sHref = $sUrl.$sAnchor;
            } else {
                $sHref = $sUrl;
            }
        } else {
            $sHref = false;
        }

        $aHtmlBag = array(
            'url' => $sUrl,
            'href' => $sHref,
            'anchor' => $sAnchor,
            'tag.' => array(
                'begin' => '',
                'innerhtml' => '',
                'end' => '',
            ),
        );

        if (!$this->oForm->_isTrue('/urlonly', $this->aElement)) {
            if ($this->hasChilds()) {
                $aChilds = $this->renderChildsBag();
                $sCaption = $this->renderChildsCompiled(
                    $aChilds
                );
            } else {
                if (!$this->_emptyFormValue($sValue)) {
                    $sCaption = $sValue;
                } else {
                    $sCaption = $sHref;
                }

                if ('' !== ($sLabel = $this->getLabel())) {
                    $sCaption = $sLabel;
                } else {
                    $aItems = $this->_getItems();
                    foreach ($aItems as $aItem) {
                        if ($aItem['value'] == $value) {
                            $sCaption = $aItem['caption'];
                        }
                    }
                }
            }

            if (false === $sCaption) {
                $sCaption = '';
            }

            $aHtmlBag['caption'] = $sCaption;

            if (false !== $sHref) {
                $aHtmlBag['tag.']['begin'] = '<a '.('' != $sHref ? 'href="'.$sHref.'"' : '').' id="'.$this->_getElementHtmlId().'"'.$this->_getAddInputParams().'>';
                $aHtmlBag['tag.']['innerhtml'] = $sCaption;
                $aHtmlBag['tag.']['end'] = '</a>';
            } else {
                $aHtmlBag['tag.']['begin'] = '<span id="'.$this->_getElementHtmlId().'" '.$this->_getAddInputParams().'>';
                $aHtmlBag['tag.']['innerhtml'] = $sCaption;
                $aHtmlBag['tag.']['end'] = '</span>';
            }

            $sCompiled = $aHtmlBag['tag.']['begin'].$aHtmlBag['tag.']['innerhtml'].$aHtmlBag['tag.']['end'];
            $aHtmlBag['wrap'] = $aHtmlBag['tag.']['begin'].'|'.$aHtmlBag['tag.']['end'];
        } else {
            $sCompiled = $sHref;
        }

        $aHtmlBag['__compiled'] = $sCompiled;

        $this->includeScripts(array(
            // Timeout in ms
            'followTimeout' => false === ($timeout = $this->_navConf('/followtimeout')) ? 0 : (int) $timeout,
        ));

        return $aHtmlBag;
    }

    public function _renderOnly($bForAjax = false)
    {
        return true;
    }

    public function _readOnly()
    {
        return true;
    }

    public function _activeListable()
    {
        // listable as an active HTML FORM field or not in the lister
        return $this->oForm->_defaultTrue('/activelistable/', $this->aElement);
    }

    public function _searchable()
    {
        return $this->oForm->_defaultFalse('/searchable/', $this->aElement);
    }

    public function mayHaveChilds()
    {
        return true;
    }

    public function _getAddInputParamsArray($aAdditional = array())
    {
        $aAddParams = parent::_getAddInputParamsArray();
        if (false !== ($sTarget = $this->_navConf('/target'))) {
            $aAddParams[] = ' target="'.$sTarget.'" ';
        }

        reset($aAddParams);

        return $aAddParams;
    }

    public function majixFollow($bDisplayLoader = false)
    {
        return $this->buildMajixExecuter('follow', $bDisplayLoader);
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_link/api/class.tx_rdtlink.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_link/api/class.tx_rdtlink.php'];
}
