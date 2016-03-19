<?php
/**
 * Plugin 'rdt_checkbox' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_widgets_checkbox_Main extends formidable_mainrenderlet {

	var $sMajixClass = 'CheckBox';
	var $sAttachPostInitTask = 'initialize';
	var $aLibs = array(
		'rdt_checkbox_class' => 'res/js/checkbox.js',
	);

	var $bCustomIncludeScript = TRUE;

	function _render() {

		$sParentId = $this->_getElementHtmlId();
		$aHtml = array();
		$aHtmlBag = array();

		$aItems = $this->_getItems();
		$aChecked = $this->getValue();

		$aSubRdts = array();

		reset($aItems);
		while(list($index, $aItem) = each($aItems)) {

			// item configuration
			$aConfig = array_merge($this->aElement, $aItem);

			$value = $aItem['value'];
			$caption = $this->oForm->getConfigXML()->getLLLabel($aItem['caption']);

			// on cree le nom du controle
			$name = $this->_getElementHtmlName() . '[' . $index . ']';
			$sId = $this->_getElementHtmlId() . '_' . $index;
			$aSubRdts[] = $sId;
			$this->sCustomElementId = $sId;
			$this->includeScripts(
				array(
					'bParentObj' => FALSE,
					'parentid' => $sParentId,
				)
			);

			$checked = '';

			if(is_array($aChecked)) {
				if(in_array($value, $aChecked)) {
					$checked = ' checked="checked" ';
				}
			}

			$sInput = '<input type="checkbox" name="' . $name . '" id="' . $sId . '" value="' . $this->getValueForHtml($value) . '" ' . $checked . $this->_getAddInputParams($aItem) . ' ';

			if(array_key_exists('custom', $aItem)) {
				$sInput .= $aItem['custom'];
			}

			$sInput .= '/>';

			$sLabelEnd = '</label>';

			$aConfig['sId'] = $sId;
			$token = self::getToken();
			$labelTag = $this->getLabelTag($token, $aConfig);
			$labelTag = explode($token, $labelTag);
			$sLabelStart = $labelTag[0];

			$aHtmlBag[$value . '.'] = array(
				'input' => $sInput,
				'caption' => $caption,
				'value.' => array(
					'htmlspecialchars' => htmlspecialchars($value),
				),
				'label' => $sLabelStart . $caption . $sLabelEnd,
				'label.' => array(
					'for.' => array(
						'start' => $sLabelStart,
						'end' => $sLabelEnd,
					)
				)
			);

			$htmlCode = $sInput . $sLabelStart . $caption . $sLabelEnd;
			if (array_key_exists('wrapitem', $aItem)) {
				$htmlCode = str_replace('|', $htmlCode, $aItem['wrapitem']);
			}

			$aHtml[] = (($checked !== '') ? $this->_wrapSelected($htmlCode) : $this->_wrapItem($htmlCode));

			$this->sCustomElementId = FALSE;
		}

		// allowed because of $bCustomIncludeScript = TRUE
		$this->includeScripts(
			array(
				'checkboxes' => $aSubRdts,
				'bParentObj' => TRUE,
				'radioMode' => $this->defaultFalse('/radiomode'),
			)
		);


		$sInput = $this->_implodeElements($aHtml);

		if (empty($aItems) && $this->defaultFalse('/hidelabelwhenempty')) {
			$aHtmlBag['__compiled'] = $sInput;
		}
		else {
			$aHtmlBag['__compiled'] = $this->_displayLabel(
					$this->getLabel()
			) . $sInput;
		}
		$aHtmlBag['input'] = $sInput;

		return $aHtmlBag;
	}

	function _flatten($mData) {

		if(is_array($mData)) {
			if(!$this->_emptyFormValue($mData)) {
				return implode(',', $mData);
			}

			return '';
		}

		return $mData;
	}

	function _unFlatten($sData) {

		if(!$this->_emptyFormValue($sData)) {
			return Tx_Rnbase_Utility_Strings::trimExplode(',', $sData);
		}

		return array();
	}

	function _getHumanReadableValue($data) {

		if(!is_array($data)) {
			$data = Tx_Rnbase_Utility_Strings::trimExplode(',', $data);
		}

		$aLabels = array();
		$aItems = $this->_getItems();

		reset($data);
		while(list(, $selectedItemValue) = each($data)) {

			reset($aItems);
			while(list(, $aItem) = each($aItems)) {

				if($aItem['value'] == $selectedItemValue) {

					$aLabels[] = $this->oForm->getConfigXML()->getLLLabel($aItem['caption']);
					break;
				}
			}
		}

		return implode(', ', $aLabels);
	}

	function _sqlSearchClause($sValues, $sFieldPrefix = '') {

		$aParts = array();
		$aValues = Tx_Rnbase_Utility_Strings::trimExplode(',', $sValues);

		if(sizeof($aValues) > 0) {

			reset($aValues);

			$sFieldName = $this->_navConf('/name');
			$sTableName = $this->oForm->_navConf('/tablename', $this->oForm->oDataHandler->aElement);
			$aConf = $this->_navConf('/search');

			if(!is_array($aConf)) {
				$aConf = array();
			}

			while(list(, $sValue) = each($aValues)) {

				if(array_key_exists('onfields', $aConf)) {

					if($this->oForm->isRunneable($aConf['onfields'])) {
						$sOnFields = $this->getForm()->getRunnable()->callRunnableWidget($this, $aConf['onfields']);
					} else {
						$sOnFields = $aConf['onfields'];
					}

					$aFields = Tx_Rnbase_Utility_Strings::trimExplode(',', $sOnFields);
					reset($aFields);
				} else {
					$aFields = array($this->_getName());
				}

				reset($aFields);
				while(list(, $sField) = each($aFields)) {
					$aParts[] = "FIND_IN_SET('" . $GLOBALS["TYPO3_DB"]->quoteStr($sValue, $sTableName) . "', " . $sFieldPrefix . $sField . ")";
				}
			}

			$sSql = ' ( ' . implode(' OR ', $aParts) . ' ) ';

			return $sSql;
		}

		return '';
	}

	function majixCheckAll() {
		return $this->buildMajixExecuter(
			'checkAll'
		);
	}

	function majixCheckNone() {
		return $this->buildMajixExecuter(
			'checkNone'
		);
	}

	function majixCheckItem($sValue) {
		return $this->buildMajixExecuter(
			'checkItem',
			$sValue
		);
	}

	function majixUnCheckItem($sValue) {
		return $this->buildMajixExecuter(
			'unCheckItem',
			$sValue
		);
	}


	function _getSeparator() {

		if(($mSep = $this->_navConf('/separator')) === FALSE) {
			$mSep = "<br />\n";
		} else {
			if($this->oForm->isRunneable($mSep)) {
				$mSep = $this->getForm()->getRunnable()->callRunnableWidget($this, $mSep);
			}
		}

		return $mSep;
	}

	function _implodeElements($aHtml) {

		return implode(
			$this->_getSeparator(),
			$aHtml
		);
	}

	function _wrapSelected($sHtml) {

		if(($mWrap = $this->_navConf('/wrapselected')) !== FALSE) {

			if($this->oForm->isRunneable($mWrap)) {
				$mWrap = $this->getForm()->getRunnable()->callRunnableWidget($this, $mWrap);
			}

			$sHtml = str_replace('|', $sHtml, $mWrap);

		} else {
			$sHtml = $this->_wrapItem($sHtml);
		}

		return $sHtml;
	}

	function _wrapItem($sHtml) {

		if(($mWrap = $this->_navConf('/wrapitem')) !== FALSE) {

			if($this->oForm->isRunneable($mWrap)) {
				$mWrap = $this->getForm()->getRunnable()->callRunnableWidget($this, $mWrap);
			}

			$sHtml = str_replace('|', $sHtml, $mWrap);
		}

		return $sHtml;
	}

	function _displayLabel($sLabel) {

		// für bestehende projekte, das main label darf nicht die klasse -radio haben!
		$sDefaultLabelClass = $this->sDefaultLabelClass;
		$this->sDefaultLabelClass = $this->getForm()->sDefaultWrapClass.'-label';

		$aConfig =  $this->aElement;
		// via default, kein for tag!
		if(!isset($aConfig['labelfor'])) $aConfig['labelfor'] = 0;

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
	 */
	function setValue($mValue) {
		return parent::setValue(is_array($mValue) || empty($mValue) ? $mValue : array($mValue));
	}
}


	if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_checkbox/api/class.tx_rdtcheckbox.php'])	{
		include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_checkbox/api/class.tx_rdtcheckbox.php']);
	}

