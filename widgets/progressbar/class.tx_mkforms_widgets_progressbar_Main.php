<?php
/**
 * Plugin 'rdt_progressbar' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_widgets_progressbar_Main extends formidable_mainrenderlet {

	var $aLibs = array(
		"rdt_progressbar_class" => "res/js/progressbar.js",
	);

	var $sMajixClass = "ProgressBar";
	var $bCustomIncludeScript = TRUE;
	var $aSteps = FALSE;

	function _render() {

		$fValue = $this->getValue();
		$fMin = $this->getMinValue();
		$fMax = $this->getMaxValue();
		$iWidth = $this->getPxWidth();
		$fPercent = $this->getPercent();
		$bEffects = $this->defaultFalse("/effects");

		$sBegin = "<div id='" . $this->_getElementHtmlId() . "' " . $this->_getAddInputParams() . ">";
		$sEnd = "</div>";

		// allowed because of $bCustomIncludeScript = TRUE
		$this->includeScripts(
			array(
				"min" => $fMin,
				"max" => $fMax,
				"precision" => $iPrecision,
				"value" => $fValue,
				"percent" => $fPercent,
				"width" => $iWidth,
				"steps" => $this->aSteps,
				"effects" => $bEffects,
			)
		);

		if(($aStep = $this->getStep($fValue)) === FALSE) {
			$sProgressLabel = $fPercent . "%";
		} else {
			$sProgressLabel = $aStep["label"];
		}

		$aHtmlBag = array(
			"__compiled" => $sBegin . "<span>" . $sProgressLabel . "</span>" . $sEnd,
		);

		return $aHtmlBag;
	}

	function getMaxValue() {
		if(($mMax = $this->_navConf("/max")) !== FALSE) {
			$mMax = $this->getForm()->getRunnable()->callRunnable($mMax);
		}

		return floatval($mMax);
	}

	function getMinValue() {
		if(($mMin = $this->_navConf("/min")) !== FALSE) {
			$mMin = $this->getForm()->getRunnable()->callRunnable($mMin);
		}

		return floatval($mMin);
	}

	function getPrecision() {
		if(($iPrecision = $this->oForm->_navConf("/precision")) !== FALSE) {
			$iPrecision = intval($mPrecision);
		}

		return intval($iPrecision);
	}

	function _readOnly() {
		return TRUE;
	}

	function _renderOnly($bForAjax = FALSE) {
		return TRUE;
	}

	function _renderReadOnly() {
		return $this->_render();
	}

	function _activeListable() {
		return FALSE;
	}

	function getStep($iValue) {

		$this->initSteps();

		reset($this->aSteps);
		while(list(, $aStep) = each($this->aSteps)) {
			if($aStep["value"] <= $iValue) {
				return $aStep;
			}
		}

		return FALSE;
	}

	function initSteps() {
		if($this->aSteps === FALSE) {
			$aResSteps = array();
			if(($aSteps = $this->_navConf("/steps")) !== FALSE) {
				reset($aSteps);
				while(list(, $aStep) = each($aSteps)) {
					$aResSteps[$aStep["value"]] = array(
						"value" => $aStep["value"],
						"label" => $this->oForm->getLLLabel($aStep["label"]),
						"className" => $aStep["class"],
					);
				}

				krsort($aResSteps);
			}

			reset($aResSteps);
			$this->aSteps = $aResSteps;
		}
	}

	function getValue() {
		$fValue = floatval(parent::getValue());

		$fMin = $this->getMinValue();
		$fMax = $this->getMaxValue();

		if($fValue < $fMin) {
			$fValue = $fMin;
		}

		if($fValue > $fMax) {
			$fValue = $fMax;
		}

		return $fValue;
	}

	function _getClassesArray($aConf = FALSE) {
		$aClasses = parent::_getClassesArray($aConf);
		$mValue = $this->getValue();
		$aStep = $this->getStep($mValue);

		$aClasses[] = $aStep["className"];
		return $aClasses;
	}

	function getPxWidth() {
		if(($mWidth = $this->_navConf("/width")) !== FALSE) {
			$mWidth = intval($this->getForm()->getRunnable()->callRunnable($mWidth));

			if(($mWidth = intval($mWidth)) === 0) {
				$mWidth = FALSE;
			}
		}

		return $mWidth;
	}

	function getPercent() {

		$fValue = $this->getValue();
		$fMin = $this->getMinValue();
		$fMax = $this->getMaxValue();
		$iPrecision = $this->getPrecision();

		if($fMax === $fMin) {
			return 100;
		}

		return round(($fValue / ($fMax - $fMin)) * 100, $iPrecision);
	}

	function _getStyleArray() {

		$aStyles = parent::_getStyleArray();

		$iWidth = $this->getPxWidth();

		if($iWidth !== FALSE) {
			$iStepWidth = round((($iWidth * $this->getPercent()) / 100), 0);
			$aStyles["width"] = $iStepWidth . "px";
		}

		if($this->defaultTrue("/usedefaultstyle")) {
			if(!array_key_exists("border", $aStyles) && !array_key_exists("border-width", $aStyles)) {
				$aStyles["border-width"] = "2px";
			}

			if(!array_key_exists("border", $aStyles) && !array_key_exists("border-color", $aStyles)) {
				$aStyles["border-color"] = "silver";
			}

			if(!array_key_exists("border", $aStyles) && !array_key_exists("border-style", $aStyles)) {
				$aStyles["border-style"] = "solid";
			}

			if(!array_key_exists("text-align", $aStyles)) {
				$aStyles["text-align"] = "center";
			}

			if(!array_key_exists("overflow", $aStyles)) {
				$aStyles["overflow"] = "hidden";
			}
		}

		reset($aStyles);
		return $aStyles;
	}
}


	if (defined("TYPO3_MODE") && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/ap/base/rdt_progressbar/api/class.tx_rdtprogressbar.php"]) {
		include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_progressbar/api/class.tx_rdtprogressbar.php"]);
	}

