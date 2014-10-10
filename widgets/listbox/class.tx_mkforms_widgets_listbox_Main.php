<?php
/**
 * Plugin 'rdt_listbox' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_widgets_listbox_Main extends formidable_mainrenderlet {

	var $sMajixClass = "ListBox";
	var $aLibs = array(
		"rdt_listbox_class" => "res/js/listbox.js"
	);

	function _render() {

		$sLabel = $this->getLabel();
		$sValue = $this->getValue();

		$sOptionsList = '';

		$aItems = $this->_getItems();
		$sAddStyle = '';

		if($this->_defaultFalse('/hideifempty') === TRUE) {
			if($this->isDataEmpty()) {
				$sAddStyle = 'display: none;';
			}
		}

		$aSelectedCaptions = array();
		$bSelected = FALSE;

		if(count($aItems) > 0) {

			$aHtml = array();
			$sOptionsListBag = array();

			reset($aItems);
			while(list(, $aItem) = each($aItems)) {

				$sSelected = '';
				$value = $aItem['value'];
				$sCaption = isset($aItem['caption'])
						? $this->getForm()->getConfig()->getLLLabel($aItem['caption'])
						: $aItem['value']
					;

				if($this->_isMultiple()) {
					if(is_array($sValue)) {
						if(in_array($value, $sValue)) {
							$sSelected = ' selected="selected" ';
							$aSelectedCaptions[] = $sCaption;
						}
					}
				} else {
					if($bSelected === FALSE && $aItem['value'] == $sValue) {
						$bSelected = TRUE;
						$sSelected = ' selected="selected" ';
						$aSelectedCaptions[] = $sCaption;
					}
				}

				$sCustom = $this->_getCustom($aItem);
				$sClass = $this->_getClasses($aItem, FALSE);
				$sStyle = $this->_getStyle($aItem);

				#$aHtml[] = "<option value=\"" . $aItem["value"] . "\" " . $sSelected . $sClass . $sStyle . $sCustom . ">" . $sCaption . "</option>";
				$sInput = '<option value="' . $aItem['value'] . '" ' . $sSelected . $sClass . $sCustom . '>' . $sCaption . '</option>';
				$aHtml[] = $sInput;

//				$sOptionsListBag[$aItem['value']] = $sInput;
				$sOptionsListBag[$aItem['value'].'.'] = array(
					'input' => $sInput,
					'value' => $aItem['value'],
					'selected' => $bSelected,
					'caption' => $sCaption,
					'class' => $sClass,
					'custom' => $sCustom
				);
			}

			reset($aHtml);
			$sOptionsList = implode('', $aHtml);
		}

		if($this->_isMultiple()) {
			$sBrackets = '[]';
			$sMultiple = ' multiple="multiple" ';
		} else {
			$sBrackets = '';
			$sMultiple = '';
		}

		$sInputBegin = '<select name="' . $this->_getElementHtmlName() . $sBrackets . '" ' . $sMultiple . ' id="' . $this->_getElementHtmlId() . '"' . $this->_getAddInputParams(array('style' => $sAddStyle)) . '>';
		$sInputEnd = '</select>';
		$sInput = $sInputBegin . $sOptionsList . $sInputEnd;

		$aHtmlBag = array(
			'__compiled' => $this->_displayLabel($sLabel) . $sInput,
			'value' => $sValue,
			'caption' => implode(', ', $aSelectedCaptions),
			'input' => $sInput,
			'select.' => array('begin' => $sInputBegin, 'end' => $sInputEnd),
			'itemcount' => count($sOptionsListBag),
			'items.' => $sOptionsListBag,
		);

		return $aHtmlBag;
	}

	function _getHumanReadableValue($data = FALSE) {

		if($data === FALSE) {
			$data = $this->getValue();
		}

		if($this->_isMultiple() && !is_array($data)) {
			$data = t3lib_div::trimExplode(",", $data);
		}

		if(is_array($data)) {

			$aLabels = array();
			$aItems = $this->_getItems();

			reset($data);
			while(list(, $selectedItemValue) = each($data)) {

				reset($aItems);
				while(list(, $aItem) = each($aItems)) {

					if($aItem["value"] == $selectedItemValue) {

						$aLabels[] = $this->oForm->getConfig()->getLLLabel($aItem["caption"]);
						break;
					}
				}
			}

			return implode(", ", $aLabels);

		} else {

			$aItems = $this->_getItems();

			reset($aItems);
			while(list(, $aItem) = each($aItems)) {

				if($aItem["value"] == $data) {
					return $this->oForm->getConfig()->getLLLabel($aItem["caption"]);
				}
			}

			return $data;
		}

		return "";
	}

	function _sqlSearchClause($sValue, $sFieldPrefix = "", $sFieldName = "", $bRec = TRUE) {

		$aValues = t3lib_div::trimExplode(",", $sValue);
		$aParts = array();

		if($sFieldName === "") {
			$sFieldName = $this->_getName();
		}

		if(sizeof($aValues) > 0) {

			$sTableName = $this->oForm->_navConf("/tablename", $this->oForm->oDataHandler->aElement);

			reset($aValues);
			while(list(, $uid) = each($aValues)) {
				//$aParts[] = "(FIND_IN_SET('" . addslashes($sValue) . "', " . $sFieldPrefix . $sFieldName . "))";
				$aParts[] = $GLOBALS["TYPO3_DB"]->listQuery($sFieldPrefix . $sFieldName, $uid, $sTableName);
			}

			$sSql = " ( " . implode(" OR ", $aParts) . " ) ";

			if($bRec === TRUE) {
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

		return "";
	}

	function __getDefaultValue() {

		if(
			$this->_defaultFalse("/data/defaultvalue/first/")
			||
			($this->_navConf("/data/defaultvalue/first/") === "")	// slick tag <first />
		) {
			// on renvoie la valeur du premier item
			if(($sFirstValue = $this->getFirstItemValue()) !== FALSE) {
				return $sFirstValue;
			}

			return "";
		}

		return parent::__getDefaultValue();
	}

	function getFirstItemValue() {
		$aItems = $this->_getItems();

		if(!empty($aItems)) {

			$aFirst = array_shift($aItems);
			return $this->_substituteConstants($aFirst["value"]);
		}

		return FALSE;
	}

	function majixReplaceData($aData) {

		$iKey = array_shift(array_keys($aData));

		if(is_array($aData[$iKey])) {

			// it's an array like array(
			//	0 => array("caption" => "", "value" => "")
			//	1 => array("caption" => "", "value" => "")
			//	)
			$aOldData = $aData;
			$aData = array();

			reset($aOldData);
			while(list(, $aItem) = each($aOldData)) {
				$aData[$aItem["value"]] = $this->oForm->getConfig()->getLLLabel($aItem["caption"]);
			}
		}

		return $this->buildMajixExecuter(
			"replaceData",
			$aData
		);
	}

	function majixSetSelected($sData) {
		return $this->buildMajixExecuter(
			"setSelected",
			$sData
		);
	}

	function majixSetAllSelected() {
		return $this->buildMajixExecuter(
			"setAllSelected"
		);
	}

	function majixTransferSelectedTo($sRdtId, $bRemoveFromSource = TRUE) {
		return $this->buildMajixExecuter(
			"transferSelectedTo",
			array(
				"list" => $sRdtId,
				"removeFromSource" => $bRemoveFromSource,
			)
		);
	}

	function majixMoveSelectedTop() {
		return $this->buildMajixExecuter(
			"moveSelectedTop"
		);
	}

	function majixMoveSelectedUp() {
		return $this->buildMajixExecuter(
			"moveSelectedUp"
		);
	}

	function majixMoveSelectedDown() {
		return $this->buildMajixExecuter(
			"moveSelectedDown"
		);
	}

	function majixMoveSelectedBottom() {
		return $this->buildMajixExecuter(
			"moveSelectedBottom"
		);
	}

	function majixAddItem($sCaption, $sValue) {
		return $this->buildMajixExecuter(
			"addItem",
			array(
				"caption" => $sCaption,
				"value" => $sValue
			)
		);
	}

	function majixModifyItem($sCaption, $sValue) {
		return $this->buildMajixExecuter(
			"modifyItem",
			array(
				"caption" => $sCaption,
				"value" => $sValue
			)
		);
	}

	function _isMultiple() {
		return ($this->oForm->_defaultFalse("/multiple/", $this->aElement));
	}

	function _flatten($mData) {
		if($this->_isMultiple()) {
			if(is_array($mData) && !$this->_emptyFormValue($mData)) {
				return implode(",", $mData);
			}

			return "";
		} else {
			return $mData;
		}
	}

	function _unFlatten($sData) {
		if($this->_isMultiple()) {
			if(!$this->_emptyFormValue($sData)) {
				return t3lib_div::trimExplode(",", $sData);
			} else {
				return array();
			}
		} else {
			return $sData;
		}
	}

	function _getValue() {

		$aItems = $this->_getItems();
		if(is_array($aItems) && count($aItems) == 1) {
			$aFirst = array_shift($aItems);
			return $this->_substituteConstants($aFirst["value"]);
		}

		return parent::_getValue();
	}

	function isDataEmpty() {
		$aItems = $this->_getItems();
		return (count($aItems) === 0 || (count($aItems) === 1 && trim($aItems[array_shift(array_keys($aItems))]["value"]) === ""));
	}
}


	if (defined("TYPO3_MODE") && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_listbox/api/class.tx_rdtlistbox.php"])	{
		include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_listbox/api/class.tx_rdtlistbox.php"]);
	}

?>