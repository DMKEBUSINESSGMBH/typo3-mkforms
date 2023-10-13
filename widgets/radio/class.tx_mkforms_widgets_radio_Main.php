<?php
/**
 * Plugin 'rdt_radio' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_widgets_radio_Main extends formidable_mainrenderlet
{
    public $sMajixClass = 'Radio';
    public $aLibs = [
        'rdt_radio_class' => 'res/js/radio.js',
    ];

    public $sDefaultLabelClass = 'label-radio';
    public $bCustomIncludeScript = true;

    public function _render()
    {
        $aHtmlBag = [];
        $sCurValue = $this->getValue();
        $sRadioGroup = '';

        $aItems = $this->_getItems();
        $aSubRdts = [];

        if (null === $sCurValue &&
            $this->_defaultFalse('/data/firstactive') &&
            !empty($aItems)
        ) {
            $sCurValue = reset($aItems);
            $sCurValue = $sCurValue['value'];
        }

        $aHtmlBag['value'] = $sCurValue;

        if (!empty($aItems)) {
            $aHtml = [];

            foreach ($aItems as $itemindex => $aItem) {
                // item configuration
                $aConfig = array_merge($this->aElement, $aItem);

                $selected = '';
                $isSelected = false;
                if ($aItem['value'] == $sCurValue) {
                    $isSelected = true;
                    $selected = ' checked="checked" ';
                }

                $sCaption = isset($aItem['caption'])
                    ? $this->getForm()->getConfigXML()->getLLLabel($aItem['caption']) : $aItem['value'];

                $sId = $this->_getElementHtmlId().'_'.$itemindex;
                $aSubRdts[] = $sId;
                $this->sCustomElementId = $sId;
                $this->includeScripts();

                $sValue = $aItem['value'];

                $sInput = '<input type="radio" name="'.$this->_getElementHtmlName().'" id="'.$sId.'" value="'.$aItem['value'].'" '.$selected.$this->_getAddInputParams($aItem).' />';

                $aConfig['sId'] = $sId;

                // nur Label ohne Tag ausgeben
                if (false !== $this->getConfigValue('/addnolabeltag')) {
                    $sLabelStart = $sLabelEnd = '';
                } else {
                    $token = self::getToken();
                    $sLabelTag = $this->getLabelTag($token, $aConfig);
                    $sLabelTag = explode($token, $sLabelTag);
                    $sLabelStart = $sLabelTag[0];
                    $sLabelEnd = '</label>';
                }
                $sLabelTag = $sLabelStart.$sCaption.$sLabelEnd;

                $aHtmlBag[$sValue.'.'] = [
                    'label' => $sCaption,
                    'label.' => [
                        'tag' => $sLabelTag,
                        'tag.' => [
                            'start' => $sLabelStart,
                            'end' => $sLabelEnd,
                        ],
                    ],
                    'input' => $sInput,
                    'value' => $sValue,
                    'caption' => $sCaption,
                    'selected' => $isSelected,
                ];

                $aHtml[] = (('' !== $selected) ? $this->_wrapSelected($sInput.$sLabelTag) : $this->_wrapItem($sInput.$sLabelTag));
                $this->sCustomElementId = false;
            }

            reset($aHtml);
            $sRadioGroup = $this->_implodeElements($aHtml);
        }

        // allowed because of $bCustomIncludeScript = TRUE
        $this->includeScripts(
            [
                'name' => $this->_getElementHtmlName(),
                'radiobuttons' => $aSubRdts,
                'bParentObj' => true,
            ]
        );

        $sInput = $this->_implodeElements($aHtml);
        $aHtmlBag['input'] = $sInput;
        $aHtmlBag['__compiled'] = $this->_displayLabel($this->getLabel()).$sRadioGroup;

        reset($aHtmlBag);

        return $aHtmlBag;
    }

    public function _getHumanReadableValue($data)
    {
        $aItems = $this->_getItems();

        reset($aItems);
        foreach ($aItems as $aItem) {
            if ($aItem['value'] == $data) {
                return $this->oForm->getConfigXML()->getLLLabel($aItem['caption']);
            }
        }

        return $data;
    }

    public function _getSeparator()
    {
        if (false === ($mSep = $this->getConfigValue('/separator'))) {
            $mSep = "\n";
        } else {
            if ($this->oForm->isRunneable($mSep)) {
                $mSep = $this->getForm()->getRunnable()->callRunnableWidget($this, $mSep);
            }
        }

        return $mSep;
    }

    public function _implodeElements($aHtml)
    {
        if (!is_array($aHtml)) {
            $aHtml = [];
        }

        return implode(
            $this->_getSeparator(),
            $aHtml
        );
    }

    public function _wrapSelected($sHtml)
    {
        if (false !== ($mWrap = $this->getConfigValue('/wrapselected'))) {
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
        if (false !== ($mWrap = $this->getConfigValue('/wrapitem'))) {
            if ($this->oForm->isRunneable($mWrap)) {
                $mWrap = $this->getForm()->getRunnable()->callRunnableWidget($this, $mWrap);
            }

            $sHtml = str_replace('|', $sHtml, $mWrap);
        }

        return $sHtml;
    }

    public function _displayLabel($sLabel, $aConfig = false)
    {
        // für bestehende projekte, das main label darf nicht die klasse -radio haben!
        $sDefaultLabelClass = $this->sDefaultLabelClass;
        $this->sDefaultLabelClass = $this->getForm()->sDefaultWrapClass.'-label';

        $aConfig = $this->aElement;
        // via default, kein for tag!
        if (!isset($aConfig['labelfor'])) {
            $aConfig['labelfor'] = 0;
        }

        $sLabel = $this->getLabelTag($sLabel, $aConfig);

        // label zurücksetzen
        $this->sDefaultLabelClass = $sDefaultLabelClass;

        return $sLabel;
    }

    public function _activeListable()
    {
        // listable as an active HTML FORM field or not in the lister
        return $this->_defaultTrue('/activelistable/');
    }
}
