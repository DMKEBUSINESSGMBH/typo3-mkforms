<?php
/**
 * Plugin 'rdt_listbox' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_widgets_listbox_Main extends formidable_mainrenderlet
{
    public $sMajixClass = 'ListBox';
    public $aLibs = [
        'rdt_listbox_class' => 'Resources/Public/JavaScript/widgets/listbox/listbox.js',
    ];

    public function _render()
    {
        $sLabel = $this->getLabel();
        $sValue = $this->getValue();

        $sOptionsList = '';

        $aItems = $this->_getItems();
        $sAddStyle = '';

        if (true === $this->_defaultFalse('/hideifempty')) {
            if ($this->isDataEmpty()) {
                $sAddStyle = 'display: none;';
            }
        }

        $strictCheck = $this->defaultFalse('/strictselectedcheck');

        $aSelectedCaptions = [];
        $bSelected = false;

        if (count($aItems) > 0) {
            $aHtml = [];
            $sOptionsListBag = [];

            foreach ($aItems as $aItem) {
                $sSelected = '';
                $value = $aItem['value'];
                $sCaption = isset($aItem['caption']) ? $this->getForm()->getConfig()->getLLLabel($aItem['caption']) : $aItem['value'];

                if ($this->_isMultiple()) {
                    if (is_array($sValue)) {
                        if (in_array($value, $sValue, $strictCheck)) {
                            $sSelected = ' selected="selected" ';
                            $aSelectedCaptions[] = $sCaption;
                        }
                    }
                } else {
                    if (false === $bSelected && (
                        (
                            !$strictCheck && $aItem['value'] == $sValue
                        ) || (
                            $strictCheck && $aItem['value'] === $sValue
                        )
                    )
                    ) {
                        $bSelected = true;
                        $sSelected = ' selected="selected" ';
                        $aSelectedCaptions[] = $sCaption;
                    }
                }

                $sCustom = $this->_getCustom($aItem);
                $sClass = $this->_getClasses($aItem, false);

                $sInput = '<option value="'.$aItem['value'].'" '.$sSelected.$sClass.$sCustom.'>'.$sCaption.'</option>';
                $aHtml[] = $sInput;

                $sOptionsListBag[$aItem['value'].'.'] = [
                    'input' => $sInput,
                    'value' => $aItem['value'],
                    'selected' => $bSelected,
                    'caption' => $sCaption,
                    'class' => $sClass,
                    'custom' => $sCustom,
                ];
            }

            reset($aHtml);
            $sOptionsList = implode('', $aHtml);
        }

        if ($this->_isMultiple()) {
            $sBrackets = '[]';
            $sMultiple = ' multiple="multiple" ';
        } else {
            $sBrackets = '';
            $sMultiple = '';
        }

        $sInputBegin = '<select name="'.$this->_getElementHtmlName().$sBrackets.'" '.$sMultiple.' id="'.$this->_getElementHtmlId().'"'.$this->_getAddInputParams(['style' => $sAddStyle]).'>';
        $sInputEnd = '</select>';
        $sInput = $sInputBegin.$sOptionsList.$sInputEnd;

        $aHtmlBag = [
            '__compiled' => $this->_displayLabel($sLabel).$sInput,
            'value' => $sValue,
            'caption' => implode(', ', $aSelectedCaptions),
            'input' => $sInput,
            'select.' => ['begin' => $sInputBegin, 'end' => $sInputEnd],
            'itemcount' => count($sOptionsListBag ?? []),
            'items.' => $sOptionsListBag,
        ];

        return $aHtmlBag;
    }

    public function _getHumanReadableValue($data = false)
    {
        if (false === $data) {
            $data = $this->getValue();
        }

        if ($this->_isMultiple() && !is_array($data)) {
            $data = \Sys25\RnBase\Utility\Strings::trimExplode(',', $data);
        }

        if (is_array($data)) {
            $aLabels = [];
            $aItems = $this->_getItems();

            foreach ($data as $selectedItemValue) {
                foreach ($aItems as $aItem) {
                    if ($aItem['value'] == $selectedItemValue) {
                        $aLabels[] = $this->oForm->getConfig()->getLLLabel($aItem['caption']);
                        break;
                    }
                }
            }

            return implode(', ', $aLabels);
        } else {
            foreach ($this->_getItems() as $aItem) {
                if ($aItem['value'] == $data) {
                    return $this->oForm->getConfig()->getLLLabel($aItem['caption']);
                }
            }

            return $data;
        }
    }

    public function _sqlSearchClause($sValue, $sFieldPrefix = '', $sFieldName = '', $bRec = true)
    {
        $aValues = \Sys25\RnBase\Utility\Strings::trimExplode(',', $sValue);
        $aParts = [];

        if ('' === $sFieldName) {
            $sFieldName = $this->_getName();
        }

        if (sizeof($aValues) > 0) {
            $sTableName = $this->oForm->_navConf('/tablename', $this->oForm->oDataHandler->aElement);

            foreach ($aValues as $uid) {
                $pattern = \Sys25\RnBase\Database\Connection::getInstance()->quoteStr($uid);
                $aParts[] = 'FIND_IN_SET(\''.$pattern.'\','.$sFieldPrefix.$sFieldName.')';
            }

            $sSql = ' ( '.implode(' OR ', $aParts).' ) ';

            if (true === $bRec) {
                return $this->overrideSql(
                    $sValue,
                    $sFieldPrefix,
                    $sFieldName,
                    $sSql
                );
            } else {
                return $sSql;
            }
        }

        return '';
    }

    public function _getDefaultValue()
    {
        if ($this->_defaultFalse('/data/defaultvalue/first/')
            ||
            ('' === $this->_navConf('/data/defaultvalue/first/'))    // slick tag <first />
        ) {
            // on renvoie la valeur du premier item
            if (false !== ($sFirstValue = $this->getFirstItemValue())) {
                return $sFirstValue;
            }

            return '';
        }

        return parent::_getDefaultValue();
    }

    public function getFirstItemValue()
    {
        $aItems = $this->_getItems();

        if (!empty($aItems)) {
            $aFirst = array_shift($aItems);

            return $this->_substituteConstants($aFirst['value']);
        }

        return false;
    }

    public function majixReplaceData($aData)
    {
        $iKey = array_shift(array_keys($aData));

        if (is_array($aData[$iKey])) {
            // it's an array like array(
            //	0 => array("caption" => "", "value" => "")
            //	1 => array("caption" => "", "value" => "")
            //	)
            $aOldData = $aData;
            $aData = [];

            foreach ($aOldData as $aItem) {
                $aData[$aItem['value']] = $this->oForm->getConfig()->getLLLabel($aItem['caption']);
            }
        }

        return $this->buildMajixExecuter(
            'replaceData',
            $aData
        );
    }

    public function majixSetSelected($sData)
    {
        return $this->buildMajixExecuter(
            'setSelected',
            $sData
        );
    }

    public function majixSetAllSelected()
    {
        return $this->buildMajixExecuter(
            'setAllSelected'
        );
    }

    public function majixTransferSelectedTo($sRdtId, $bRemoveFromSource = true)
    {
        return $this->buildMajixExecuter(
            'transferSelectedTo',
            [
                'list' => $sRdtId,
                'removeFromSource' => $bRemoveFromSource,
            ]
        );
    }

    public function majixMoveSelectedTop()
    {
        return $this->buildMajixExecuter(
            'moveSelectedTop'
        );
    }

    public function majixMoveSelectedUp()
    {
        return $this->buildMajixExecuter(
            'moveSelectedUp'
        );
    }

    public function majixMoveSelectedDown()
    {
        return $this->buildMajixExecuter(
            'moveSelectedDown'
        );
    }

    public function majixMoveSelectedBottom()
    {
        return $this->buildMajixExecuter(
            'moveSelectedBottom'
        );
    }

    public function majixAddItem($sCaption, $sValue)
    {
        return $this->buildMajixExecuter(
            'addItem',
            [
                'caption' => $sCaption,
                'value' => $sValue,
            ]
        );
    }

    public function majixModifyItem($sCaption, $sValue)
    {
        return $this->buildMajixExecuter(
            'modifyItem',
            [
                'caption' => $sCaption,
                'value' => $sValue,
            ]
        );
    }

    public function _isMultiple()
    {
        return $this->oForm->_defaultFalse('/multiple/', $this->aElement);
    }

    public function _flatten($mData)
    {
        if ($this->_isMultiple()) {
            if (is_array($mData) && !$this->_emptyFormValue($mData)) {
                return implode(',', $mData);
            }

            return '';
        } else {
            return $mData;
        }
    }

    public function _unFlatten($sData)
    {
        if ($this->_isMultiple()) {
            if (!$this->_emptyFormValue($sData)) {
                return \Sys25\RnBase\Utility\Strings::trimExplode(',', $sData);
            } else {
                return [];
            }
        } else {
            return $sData;
        }
    }

    public function _getValue()
    {
        $aItems = $this->_getItems();
        if (is_array($aItems) && 1 == count($aItems)) {
            $aFirst = array_shift($aItems);

            return $this->_substituteConstants($aFirst['value']);
        }

        return parent::_getValue();
    }

    public function isDataEmpty()
    {
        $aItems = $this->_getItems();

        return 0 === count($aItems) || (1 === count($aItems) && '' === trim($aItems[array_shift(array_keys($aItems))]['value']));
    }
}
