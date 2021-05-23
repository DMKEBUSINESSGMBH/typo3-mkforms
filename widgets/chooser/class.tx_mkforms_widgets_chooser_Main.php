<?php
/**
 * Plugin 'rdt_chooser' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_widgets_chooser_Main extends formidable_mainrenderlet
{
    public function _render()
    {
        $aHtml = [];
        $aHtmlBag = [];
        $sValue = $this->getValue();
        $sValueForHtml = $this->getValueForHtml($sValue);

        $aAddPost = [
            'formdata' => [
                $this->_getName() => '1',        // to simulate default browser behaviour
            ],
        ];

        $sFuncName = '_formidableRdtChooser'.\Sys25\RnBase\Utility\T3General::shortMd5($this->oForm->formid.$this->_getName());
        $sElementId = $this->_getElementHtmlId();

        $sMode = $this->_navConf('/submitmode');
        if ('draft' == $sMode) {
            $sSubmitEvent = $this->oForm->oRenderer->_getDraftSubmitEvent($aAddPost);
        } elseif ('test' == $sMode) {
            $sSubmitEvent = $this->oForm->oRenderer->_getTestSubmitEvent($aAddPost);
        } elseif ('clear' == $sMode) {
            $sSubmitEvent = $this->oForm->oRenderer->_getClearSubmitEvent($aAddPost);
        } elseif ('search' == $sMode) {
            $sSubmitEvent = $this->oForm->oRenderer->_getSearchSubmitEvent($aAddPost);
        } elseif ('full' == $sMode) {
            $sSubmitEvent = $this->oForm->oRenderer->_getFullSubmitEvent($aAddPost);
        } else {
            $sSubmitEvent = $this->oForm->oRenderer->_getRefreshSubmitEvent($aAddPost);
        }

        $sSystemField = $this->oForm->formid.'_AMEOSFORMIDABLE_SUBMITTER';
        $sSubmitter = $this->_getElementHtmlIdWithoutFormId();

        $sScript = <<<JAVASCRIPT

	function {$sFuncName}(sValue, sItemId) {

		$("{$sElementId}").value = sValue;
		$("{$sSystemField}").value = "{$sSubmitter}";
		{$sSubmitEvent}
	}

JAVASCRIPT;

        $this->oForm->additionalHeaderData(
            $this->oForm->inline2TempFile($sScript, 'js', 'Chooser stuff')
        );

        $aItems = $this->_getItems();

        $sSelectedId = '';

        if (!empty($aItems)) {
            foreach ($aItems as $sIndex => $aItem) {
                $sItemValue = $aItem['value'];
                $sCaption = $aItem['caption'];

                // on cr�e le nom du controle
                $sId = $this->_getElementHtmlId().'_'.$sIndex;

                $sSelected = ($sValue == $sItemValue) ? 1 : 0;

                if ($this->oForm->isRunneable($this->_navConf('/renderaslinks'))) {
                    $sHref = $this->getForm()->getRunnable()->callRunnableWidget($this, $this->_navConf('/renderaslinks'), ['value' => $sItemValue]);
                } else {
                    $sHref = 'javascript:void('.$sFuncName."(unescape('".rawurlencode($sItemValue)."'), unescape('".rawurlencode($sId)."')))";
                }

                $sLinkStart = '<a id="'.$sId.'" href="'.$sHref.'">';
                $sLinkEnd = '</a>';
                $sInner = $sLinkStart.$sCaption.$sLinkEnd;

                if (1 == $sSelected) {
                    $sLink = $this->_wrapSelected($sInner);
                    $sSelectedId = $sId;
                } else {
                    $sLink = $this->_wrapItem($sInner);
                }

                if ('' == trim($sItemValue)) {
                    $sChannel = 'void';
                } else {
                    $sChannel = $sValue;
                }

                $aHtmlBag[$sChannel.'.'] = [
                    'id' => $sId,
                    'input' => $sLink,
                    'action' => $sHref,
                    'tag.' => [
                        'start' => $sLinkStart,
                        'end' => $sLinkEnd,
                    ],
                    'caption' => $sCaption,
                    'inner' => $sInner,
                    'value' => $sItemValue,
                    'selected' => $sSelected,
                ];

                $aHtml[] = $sLink;
            }

            $aHtmlBag['hidden'] = '<input type="hidden" name="'.$this->_getElementHtmlName().'" id="'.$this->_getElementHtmlId().'" value="'.$sValueForHtml.'" />';
            $aHtmlBag['separator'] = $this->_getSeparator();
            $aHtmlBag['value'] = $sValue;
            $aHtmlBag['selectedid'] = $sSelectedId;

            $aHtmlBag['__compiled'] = $this->_displayLabel(
                $this->getLabel()
            ).$this->_implodeElements($aHtml).$aHtmlBag['hidden'];

            return $aHtmlBag;
        }
    }

    public function _listable()
    {
        return $this->oForm->_defaultFalse('/listable/', $this->aElement);
    }

    public function _getSeparator()
    {
        if (false === ($mSep = $this->_navConf('/separator'))) {
            $mSep = ' &#124; ';
        } else {
            if ($this->oForm->isRunneable($mSep)) {
                $mSep = $this->getForm()->getRunnable()->callRunnableWidget($this, $mSep);
            }
        }

        return $mSep;
    }

    public function _implodeElements($aHtml)
    {
        return implode(
            $this->_getSeparator(),
            $aHtml
        );
    }

    public function _wrapSelected($sHtml)
    {
        if (false !== ($mWrap = $this->_navConf('/wrapselected'))) {
            if ($this->oForm->isRunneable($mWrap)) {
                $mWrap = $this->getForm()->getRunnable()->callRunnableWidget($this, $mWrap);
            }

            $sHtml = str_replace('|', $sHtml, $mWrap);
        } else {
            $sHtml = $this->_wrapItem($sHtml);
        }

        return $sHtml;
    }

    public function _wrapItem($sHtml)
    {
        if (false !== ($mWrap = $this->_navConf('/wrapitem'))) {
            if ($this->oForm->isRunneable($mWrap)) {
                $mWrap = $this->getForm()->getRunnable()->callRunnableWidget($this, $mWrap);
            }

            $sHtml = str_replace('|', $sHtml, $mWrap);
        }

        return $sHtml;
    }

    public function _searchable()
    {
        return $this->_defaultTrue('/searchable');
    }
}
