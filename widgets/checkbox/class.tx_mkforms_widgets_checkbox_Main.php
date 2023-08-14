<?php
/**
 * Plugin 'rdt_checkbox' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_widgets_checkbox_Main extends formidable_mainrenderlet
{
    public $sMajixClass = 'CheckBox';
    public $sAttachPostInitTask = 'initialize';
    public $aLibs = [
        'rdt_checkbox_class' => 'res/js/checkbox.js',
    ];

    public $bArrayValue = true;
    public $bCustomIncludeScript = true;

    public function _render()
    {
        $sParentId = $this->_getElementHtmlId();
        $aHtml = [];
        $aHtmlBag = [];

        $aItems = $this->_getItems();
        $aChecked = $this->getValue();

        $aSubRdts = [];

        foreach ($aItems as $index => $aItem) {
            // item configuration
            $aConfig = array_merge($this->aElement, $aItem);

            $value = $aItem['value'];
            $caption = $this->oForm->getConfigXML()->getLLLabel($aItem['caption']);

            // on cree le nom du controle
            $name = $this->_getElementHtmlName().'['.$index.']';
            $sId = $this->_getElementHtmlId().'_'.$index;
            $aSubRdts[] = $sId;
            $this->sCustomElementId = $sId;
            $this->includeScripts(
                [
                    'bParentObj' => false,
                    'parentid' => $sParentId,
                ]
            );

            $checked = '';

            if (is_array($aChecked)) {
                if (in_array($value, $aChecked)) {
                    $checked = ' checked="checked" ';
                }
            }

            $sInput = '<input type="checkbox" name="'.$name.'" id="'.$sId.'" value="'.$this->getValueForHtml($value).'" '.$checked.$this->_getAddInputParams($aItem).' ';

            if (array_key_exists('custom', $aItem)) {
                $sInput .= $aItem['custom'];
            }

            $sInput .= '/>';

            $sLabelEnd = '</label>';

            $aConfig['sId'] = $sId;
            $token = self::getToken();
            $labelTag = $this->getLabelTag($token, $aConfig);
            $labelTag = explode($token, $labelTag);
            $sLabelStart = $labelTag[0];

            $aHtmlBag[$value.'.'] = [
                'input' => $sInput,
                'caption' => $caption,
                'value.' => [
                    'htmlspecialchars' => htmlspecialchars($value),
                ],
                'label' => $sLabelStart.$caption.$sLabelEnd,
                'label.' => [
                    'for.' => [
                        'start' => $sLabelStart,
                        'end' => $sLabelEnd,
                    ],
                ],
            ];

            // TODO: ist renderlabelfirst hier sinnvoll?
            // $renderLabelFirst = $this->isTrue('renderlabelfirst');

            $htmlCode = $sInput.$sLabelStart.$caption.$sLabelEnd;
            if (array_key_exists('wrapitem', $aItem)) {
                $htmlCode = str_replace('|', $htmlCode, $aItem['wrapitem']);
            }

            $aHtml[] = (('' !== $checked) ? $this->_wrapSelected($htmlCode) : $this->_wrapItem($htmlCode));

            $this->sCustomElementId = false;
        }

        // allowed because of $bCustomIncludeScript = TRUE
        $this->includeScripts(
            [
                'checkboxes' => $aSubRdts,
                'bParentObj' => true,
                'radioMode' => $this->defaultFalse('/radiomode'),
            ]
        );

        $sInput = $this->_implodeElements($aHtml);

        if (empty($aItems) && $this->defaultFalse('/hidelabelwhenempty')) {
            $aHtmlBag['__compiled'] = $sInput;
        } else {
            $aHtmlBag['__compiled'] = $this->_displayLabel(
                $this->getLabel()
            ).$sInput;
        }
        $aHtmlBag['input'] = $sInput;

        return $aHtmlBag;
    }

    public function _flatten($mData)
    {
        if (is_array($mData)) {
            if (!$this->_emptyFormValue($mData)) {
                return implode(',', $mData);
            }

            return '';
        }

        return $mData;
    }

    public function _unFlatten($sData)
    {
        if (!$this->_emptyFormValue($sData)) {
            return \Sys25\RnBase\Utility\Strings::trimExplode(',', $sData);
        }

        return [];
    }

    public function _getHumanReadableValue($data)
    {
        if (!is_array($data)) {
            $data = \Sys25\RnBase\Utility\Strings::trimExplode(',', $data);
        }

        $aLabels = [];
        $aItems = $this->_getItems();

        foreach ($data as $selectedItemValue) {
            foreach ($aItems as $aItem) {
                if ($aItem['value'] == $selectedItemValue) {
                    $aLabels[] = $this->oForm->getConfigXML()->getLLLabel($aItem['caption']);
                    break;
                }
            }
        }

        return implode(', ', $aLabels);
    }

    public function _sqlSearchClause($sValues, $sFieldPrefix = '', $sFieldName = '', $bRec = true)
    {
        $aParts = [];
        $aValues = \Sys25\RnBase\Utility\Strings::trimExplode(',', $sValues);

        if (sizeof($aValues) > 0) {
            reset($aValues);

            $sTableName = $this->oForm->_navConf('/tablename', $this->oForm->oDataHandler->aElement);
            $aConf = $this->_navConf('/search');

            if (!is_array($aConf)) {
                $aConf = [];
            }

            foreach ($aValues as $sValue) {
                if (array_key_exists('onfields', $aConf)) {
                    if ($this->oForm->isRunneable($aConf['onfields'])) {
                        $sOnFields = $this->getForm()->getRunnable()->callRunnableWidget($this, $aConf['onfields']);
                    } else {
                        $sOnFields = $aConf['onfields'];
                    }

                    $aFields = \Sys25\RnBase\Utility\Strings::trimExplode(',', $sOnFields);
                    reset($aFields);
                } else {
                    $aFields = [$this->_getName()];
                }

                foreach ($aFields as $sField) {
                    $aParts[] = "FIND_IN_SET('"
                        .\Sys25\RnBase\Database\Connection::getInstance()->quoteStr($sValue, $sTableName)
                        ."', ".$sFieldPrefix.$sField.')';
                }
            }

            $sSql = ' ( '.implode(' OR ', $aParts).' ) ';

            return $sSql;
        }

        return '';
    }

    public function majixCheckAll()
    {
        return $this->buildMajixExecuter(
            'checkAll'
        );
    }

    /**
     * @return array
     */
    public function majixCheckNone()
    {
        return $this->buildMajixExecuter(
            'checkNone'
        );
    }

    /**
     * @param string $sValue
     *
     * @return array
     */
    public function majixCheckItem($sValue)
    {
        return $this->buildMajixExecuter(
            'checkItem',
            $sValue
        );
    }

    public function majixUnCheckItem($sValue)
    {
        return $this->buildMajixExecuter(
            'unCheckItem',
            $sValue
        );
    }

    public function _getSeparator()
    {
        if (false === ($mSep = $this->_navConf('/separator'))) {
            $mSep = "<br />\n";
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

    public function _displayLabel($sLabel, $aConfig = false)
    {
        $this->sDefaultLabelClass = $this->getForm()->sDefaultWrapClass.'-label';

        $aConfig = $this->aElement;
        // via default, kein for tag!
        if (!isset($aConfig['labelfor'])) {
            $aConfig['labelfor'] = 0;
        }

        $sLabel = $this->getLabelTag($sLabel, $aConfig);

        // label zurücksetzen
        $this->sDefaultLabelClass = 'label-radio';

        return $sLabel;
    }

    /**
     * Setzt den/die Werte des Feldes.
     * Wir wollen hier immer ein Array.
     *
     * @param mixed $mValue
     *
     * @return void
     */
    public function setValue($mValue)
    {
        parent::setValue(is_array($mValue) || empty($mValue) ? $mValue : [$mValue]);
    }
}
