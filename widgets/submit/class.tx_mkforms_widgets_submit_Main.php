<?php
/**
 * Plugin 'rdt_submit' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_widgets_submit_Main extends formidable_mainrenderlet {

	function _render() {
		$sLabel = $this->getLabel();
		$sValue = $sLabel ? ' value="' . $sLabel . '"' : '';
		if(($sPath = $this->_navConf('/path')) !== FALSE) {
			$sPath = tx_mkforms_util_Div::toWebPath(
							$this->getForm()->getRunnable()->callRunnableWidget($this, $sPath)
						);
			$sHtml = '<input type="image" name="' . $this->_getElementHtmlName() . '" id="' . $this->_getElementHtmlId() . '"'.$sValue.' src="' . $sPath . '"' . $this->_getAddInputParams() . ' />';
		} else {
			$sHtml = '<input type="submit" name="' . $this->_getElementHtmlName() . '" id="' . $this->_getElementHtmlId() . '"'.$sValue.' ' . $this->_getAddInputParams() . ' />';
		}

		return $sHtml;
	}

	function getSubmitMode() {
		$sMode = $this->_navConf('/mode');
		if($this->oForm->isRunneable($sMode)) {
			$sMode = $this->getForm()->getRunnable()->callRunnableWidget($this, $sMode);
		}

		if(is_string($sMode)) {
			return strtolower(trim($sMode));
		}

		return 'full';
	}

	function _getEventsArray() {

		$aEvents = parent::_getEventsArray();

		if(!array_key_exists('onclick', $aEvents)) {
			$aEvents['onclick'] = array();
		}

		$aEvents['onclick'][] = 'MKWrapper.stopEvent(event)';

		$sMode = $this->getSubmitMode();

		$aAddPost = array(
			$this->_getElementHtmlNameWithoutFormId() => '1'		// to simulate default browser behaviour
		);

		$sEvent = '';

		if($sMode == 'refresh' || $this->_navConf('/refresh') !== FALSE) {
			$sOnclick = $this->oForm->oRenderer->_getRefreshSubmitEvent();
		} elseif($sMode == 'draft' || $this->_navConf('/draft') !== FALSE) {
			$sOnclick = $this->oForm->oRenderer->_getDraftSubmitEvent();
		} elseif($sMode == 'test' || $this->_navConf('/test') !== FALSE) {
			$sOnclick = $this->oForm->oRenderer->_getTestSubmitEvent();
		} elseif($sMode == 'clear' || $this->_navConf('/clear') !== FALSE) {
			$sOnclick = $this->oForm->oRenderer->_getClearSubmitEvent();
		} elseif($sMode == 'search' || $this->_navConf('/search') !== FALSE) {
			$sOnclick = $this->oForm->oRenderer->_getSearchSubmitEvent();
		} else {
			$sOnclick = $this->oForm->oRenderer->_getFullSubmitEvent();
		}

		$sAddPostVars = "Formidable.f('" . $this->oForm->formid . "').addFormData(" . $this->oForm->array2json($aAddPost) . ");";
		$sAddPostVars .= $sOnclick;

		// prüfe Confirm und füge if hinzu
		if(($sConfirm = $this->_navConf('/confirm')) !== FALSE) {
			$sAddPostVars = 'if(confirm(\''.$sConfirm.'\')){'.$sAddPostVars.'}';
		}

		// Nach dem JavaScript-Submit schickt der IE nochmal den richtigen Submit des Submit-Buttons ab
		// was dazu führt das das Formular 2x abgeschickt wird. ein return false sollte das problem beheben.
		$sAddPostVars .= ' return false;';

		$aEvents['onclick'][] = $sAddPostVars;

		reset($aEvents);
		return $aEvents;
	}

	function _hasThrown($sEvent, $sWhen = FALSE) {

		if($sEvent === 'click') {
			// handling special click server event on rdt_submit
			// special because has to work without javascript
			return $this->hasSubmitted();
		}
		return parent::_hasThrown($sEvent, $sWhen);
	}

	function _searchable() {
		return $this->_defaultFalse('/searchable/');
	}

	function _renderOnly() {
		return TRUE;
	}

	function isNaturalSubmitter() {
		return TRUE;
	}

	function _activeListable() {
		return $this->_defaultTrue('/activelistable');
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/widgets/submit/class.tx_mkforms_widgets_submit_Main.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/widgets/submit/class.tx_mkforms_widgets_submit_Main.php']);
}
