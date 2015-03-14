<?php
/**
 * Plugin 'listerselect' for the 'mkforms' extension.
 * Auswahl von Datensätzen innerhalb eines Listers.
 * Die Radio-Group ist somit ein Teil des Hauptformulars. Im Prinzip verhält sie sich, wie ein normales Widget
 * innerhalb einer Box. Die Box ist in diesem Fall der Lister.
 *
 * @author	René Nitzsche <dev@dmk-business.de>
 */
class tx_mkforms_widgets_listerselect_Main extends formidable_mainrenderlet {

	var $sMajixClass = 'ListerSel';
	var $aLibs = array(
		'widget_listersel_class' => 'res/js/listersel.js',
	);

	function _render() {


		$lister = $this->getParent();
		// Lister renderlet?
		$rowId = $lister->iteratingChilds ? $lister->getCurrentRowUid() : 0;

		// Die ID wird wie bei einer Box zusammengebaut
		$sId = $this->getElementId() . '_' . $rowId;

		$this->addSelectorId($sId);
		$this->sCustomElementId = $sId;
		$this->includeScripts();

		$sValue = $this->getValue();
		$selected = ($rowId == $this->getValue()) ? ' checked="checked" ' : '';

		$sInput = '<input type="radio" name="' . $this->_getElementHtmlName() . '" id="' . $sId . '" value="' . $rowId . '" ' . $selected . $this->_getAddInputParams() . ' />';
		$sCaption = $this->getForm()->getConfigXML()->getLLLabel($aItem['caption']);
		$sLabelStart = '<label for="' . $sId . '">';
		$sLabelEnd = '</label>';
		$sLabel = $sLabelStart . $sCaption . $sLabelEnd;

		$aHtml = array();
		$aHtml[] = (($selected !== '') ? $this->_wrapSelected($sInput . $sLabel) : $this->_wrapItem($sInput . $sLabel));
		$this->sCustomElementId = FALSE;
		reset($aHtml);
		$sRadioGroup = $this->_implodeElements($aHtml);

		$aHtmlBag = array(
			'__compiled' => $sLabel . $sRadioGroup,
			'label' => $sCaption,
			'label.' => array(
				'tag' => $sLabel,
				'tag.' => array(
					'start' => $sLabelStart,
					'end' => $sLabelEnd,
				),
			),
			'input' => $sInput,
			'value' => $sValue,
			'caption' => $sCaption,
		);
		reset($aHtmlBag);
		return $aHtmlBag;
	}
	/**
	 * Die einzelnen Radio-Buttons müssen gespeichert werden
	 * @param string $sId
	 * @return unknown_type
	 */
	private function addSelectorId($sId) {
		if(!is_array($this->aSubWidgets))
			$this->aSubWidgets = array();
		$this->aSubWidgets[] = $sId;

	}
	/**
	 * Liefert hier die ID ohne das Iterating. Das wird bei der Abfrage der Daten vom DataHandler benötigt.
	 * @see api/formidable_mainrenderlet#getElementId()
	 */
	public function getElementId() {
		$lister = $this->getParent();
		$sId  = $lister ? $lister->_getElementHtmlId() . AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN : '';
		$sId .= $this->_getNameWithoutPrefix();
		return $sId;
	}

	/**
	 * Normale Widgets im Lister werden als Iterating gekennzeichnet. Für den Selector gibt das nicht, da er ja wie ein
	 * eigenständiges Widget behandelt werden muss.
	 *
	 * @see api/formidable_mainrenderlet#doAfterListRender($oListObject)
	 */
	function doAfterListRender(&$oListObject) {
		$this->includeScripts(
			array(
				'radiobuttons' => $this->aSubWidgets,
				'bParentObj' => TRUE,
			)
		);

	}

	/**
	 * Der HTML-Name wird hier etwas anders zusammengebaut, da die Elemente über die Zeilen hinweg eine Gruppe bilden
	 * @see api/formidable_mainrenderlet#_getElementHtmlName($sName)
	 */
	function _getElementHtmlName($sName = FALSE) {
		$sName = $this->_getNameWithoutPrefix();
		if(!array_key_exists($sName, $this->aStatics['elementHtmlName'])) {
			$lister = $this->getParent();
			// Lister Renderlet?
			$sPrefix = $lister->iteratingChilds ? $lister->getElementHtmlNameBase() : '';
			$this->aStatics['elementHtmlName'][$sName] = $sPrefix . '[' . $sName . ']';
		}

		return $this->aStatics['elementHtmlName'][$sName];
	}

	function _getHumanReadableValue($data) {

		$aItems = $this->_getItems();

		reset($aItems);
		while(list(, $aItem) = each($aItems)) {

			if($aItem["value"] == $data) {
				return $this->oForm->getConfigXML()->getLLLabel($aItem["caption"]);
			}
		}

		return $data;
	}


	function _getSeparator() {

		if(($mSep = $this->_navConf("/separator")) === FALSE) {
			$mSep = "\n";
		} else {
			if($this->oForm->isRunneable($mSep)) {
				$mSep = $this->getForm()->getRunnable()->callRunnableWidget($this, $mSep);
			}
		}

		return $mSep;
	}

	function _implodeElements($aHtml) {

		if(!is_array($aHtml)) {
			$aHtml = array();
		}

		return implode(
			$this->_getSeparator(),
			$aHtml
		);
	}

	function _wrapSelected($sHtml) {

		if(($mWrap = $this->_navConf("/wrapselected")) !== FALSE) {

			if($this->oForm->isRunneable($mWrap)) {
				$mWrap = $this->getForm()->getRunnable()->callRunnableWidget($this, $mWrap);
			}

			$sHtml = str_replace("|", $sHtml, $mWrap);

		} else {
			$sHtml = $this->_wrapItem($sHtml);
		}

		return $sHtml;
	}

	function _wrapItem($sHtml) {

		if(($mWrap = $this->_navConf("/wrapitem")) !== FALSE) {

			if($this->oForm->isRunneable($mWrap)) {
				$mWrap = $this->getForm()->getRunnable()->callRunnableWidget($this, $mWrap);
			}

			$sHtml = str_replace("|", $sHtml, $mWrap);
		}

		return $sHtml;
	}

	function _displayLabel($sLabel) {
		$sId = $this->_getElementHtmlId() . "_label";
		return ($this->oForm->oRenderer->bDisplayLabels && (trim($sLabel) != "")) ? "<label id='" . $sId . "' class='".$this->getForm()->sDefaultWrapClass."-label " . $sId . "'>" . $sLabel . "</label>\n" : "";
	}

	function _activeListable() {		// listable as an active HTML FORM field or not in the lister
		return $this->_defaultTrue("/activelistable/");
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_radio/api/class.tx_mkforms_widgets_listerselect_Main.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_radio/api/class.tx_mkforms_widgets_listerselect_Main.php']);
}

?>