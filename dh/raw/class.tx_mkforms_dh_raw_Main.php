<?php

/**
 * Plugin 'dh_raw' for the 'ameos_formidable' extension.
 *
 * @author    Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_dh_raw_Main extends formidable_maindatahandler {

	private $editionMode = FALSE;

	/**
	 * Eleminate leaf renderlets marked as "renderonly" recursively
	 *
	 * @param array $aData
	 * @param string $path Absolute renderlet path
	 *
	 * @return array            Cleaned-up array
	 */
	private function eleminateRequireOnlies($aData, $path = '') {
		if ($path) {
			$path .= '__';
		}

		foreach (array_keys($aData) as $key) {
			if (!is_array($aData[$key])) {
				if (is_object($this->oForm->aORenderlets[$path . $key])
					&& $this->oForm->aORenderlets[$path . $key]->_renderOnly()
				) {
					unset($aData[$key]);
				}
			} else {
				// Restart the dance recursively:
				$aData[$key] = $this->eleminateRequireOnlies($aData[$key], $path . $key);
			}
		}

		return $aData;
	}

	public function _doTheMagic($bShouldProcess = TRUE) {
		// @TODO: mw, das gehört hier nicht her!
		if (!($bShouldProcess && $this->getForm()->getValidationTool()->isAllValid())) {
			$this->showValidationErrorsInTestMode();

			return;
		}

		$aData = $this->getFormData();

		// @todo Insert this option into documentation!!!
		if ($this->_defaultTrue('/ignorerenderonly')) {
			$aData = $this->eleminateRequireOnlies($aData);
		}

		// calling back
		$callback = $this->getForm()->getConfig()->get('/control/datahandler/parentcallback/');
		if ($callback === FALSE) {
			$callback = $this->getForm()->getConfig()->get('/control/datahandler/callback/');
		}

		// sicher, was ist mit formularen, die nur etwas anzeigen, aber nichts verarbeiten!?
		// ja, wenn die nix verarbeiten, darf der callback nicht definiert werden.
		// mw: super conversation leute, wer korrigiert das jetzt?
		// hab vorerst skipcallback implementiert.
		if ($callback === FALSE) {
			if ($this->_defaultFalse('/skipcallback')) {
				return;
			}
			tx_mkforms_util_Div::mayday(
				'DATAHANDLER RAW : you MUST declare a callback method on the Parent Object <b>' . get_class(
					$this->oForm->_oParent
				)
				. '</b> in the section <b>/control/datahandler/parentcallback/</b> of the XML conf for this DATAHANDLER ( RAW )',
				$this->getForm()
			);
		}

		if ($this->getForm()->getRunnable()->isRunnable($callback)) {
			$this->getForm()->getRunnable()->callRunnable($callback, $aData);
		} elseif (is_string($callback)) {
			// Das wird wohl das parentCallback sein. Ist aber eigentlich nicht notwendig...
			if (method_exists($this->getForm()->_oParent, $callback)) {
				$this->getForm()->_oParent->{$callback}($aData);
			} else {
				tx_mkforms_util_Div::mayday(
					'DATAHANDLER RAW : the callback method ' . $callback
					. ' doesn\'t exists in the definition of the Parent object',
					$this->getForm()
				);
			}
		}
	}

	/**
	 * @TODO: mw, das gehört hier nicht her!
	 * Wenn dan sollte es in den dh main,
	 * damit es sich auf alle datahandler auswirkt!
	 *
	 * @return void
	 */
	private function showValidationErrorsInTestMode() {
		if ($this->getForm()->isTestMode() && !empty($this->getForm()->_aValidationErrors)
		) {
			tx_rnbase::load('tx_rnbase_util_Debug');
			tx_rnbase_util_Debug::debug(
				array(
					'es gab Validierungsfehler' => $this->getForm()->_aValidationErrors
				),
				__METHOD__ . __LINE__
			);

			//ohne exit kommt seit phpunit 3.6 kein output im browser an
			exit(1);
		}
	}

	/**
	 * Liefert die Ursprungsdaten des Formulars. Für den DH RAW kann ein PHP-Array bereitgestellt werden.
	 * Wenn ein Parameter übergeben wird, dann wird der konkrete Wert eines Widgets geliefert. Ohne Parameter
	 * wird der gesamte Record geliefert.
	 *
	 * @param string $sName der Name eines Widgets oder false
	 *
	 * @return array oder String
	 */
	function _getStoredData($sName = FALSE) {
		if (empty($this->__aStoredData)) {

			$this->__aStoredData = array();

			if (($record = $this->getForm()->getConfig()->get('/control/datahandler/record')) !== FALSE) {
				$this->__aStoredData = $this->getForm()->getRunnable()->callRunnable($record);
				if (!is_array($this->__aStoredData)) {
					tx_mkforms_util_Div::mayday('DataHandler RAW needs a record!');
				}
				$this->editionMode = TRUE; // Wenn ein Array übergeben wurde, dann wird auf EditMode geschalten
				// Jetzt noch die Confirm-Felder füllen
				$widgetNames = $this->getForm()->getWidgetNames();
				foreach ($widgetNames As $name) {
					// Das ist nur für Confirm-Felder interessant
					if (($sConfirm = $this->getForm()->getWidget($name)->_navConf('/confirm')) !== FALSE) {
						$this->__aStoredData[$name] = $this->__aStoredData[$sConfirm];
					}
				}
			}
		}

		if (is_array($this->__aStoredData)) {
			if ($sName !== FALSE) {
				if (array_key_exists($sName, $this->__aStoredData)) {
					return $this->__aStoredData[$sName];
				}

				return '';
			}
			reset($this->__aStoredData);

			return $this->__aStoredData;
		}

		return ($sName !== FALSE) ? '' : array();
	}

	/**
	 * Legt fest, ob die Bearbeitung im Edit-Modus erfolgt. Das bedeutet, dass das Formular mit vorbelegten Feldern
	 * eines vorhandenen Records befüllt wird.
	 *
	 * @return boolean
	 */
	function _edition() {
		if ($this->_isClearSubmitted()) {
			// clearsubmitted should display a blank-data page
			// except if edition or new i18n requested
			return ($this->getForm()->editionRequested() || $this->newI18nRequested());
		}

		return $this->editionMode;
	}
}

if (defined('TYPO3_MODE')
	&& $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/dh/raw/class.tx_mkforms_dh_raw_Main.php']
) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/dh/raw/class.tx_mkforms_dh_raw_Main.php']);
}
