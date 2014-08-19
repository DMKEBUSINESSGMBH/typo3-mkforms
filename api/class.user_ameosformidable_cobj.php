<?php
	/*
		Handles TS content objects FORMIDABLE (cached) and FORMIDABLE_INT (not cached)
	*/

	class user_ameosformidable_cobj {
		
		function cObjGetSingleExt($name, $conf, $TSkey, &$oCObj) {
			
			$content = "";
			
			switch($name) {
				case "FORMIDABLE_INT": {
					
					$substKey = "INT_SCRIPT." . $GLOBALS['TSFE']->uniqueHash();
					$content .= "<!--" . $substKey . "-->";

					$GLOBALS["TSFE"]->config["INTincScript"][$substKey] = array(
						"file" => $incFile,
						"conf" => $conf,
						"cObj" => serialize($this),
						"type" => "POSTUSERFUNC",	// places a flag to call callUserFunction() later on serialized object $this, precisely in $GLOBALS["TSFE"]->INTincScript()
					);

					break;
				}
				case "FORMIDABLE": {
					
					$content .= $this->_render($conf);

					if($GLOBALS["TSFE"]->cObj->checkIf($conf["if."])) {				
						if($conf["wrap"]) {
							$content = $GLOBALS["TSFE"]->cObj->wrap($content, $conf["wrap"]);
						}

						if($conf["stdWrap."]) {
							$content = $GLOBALS["TSFE"]->cObj->stdWrap($content, $conf["stdWrap."]);
						}
					}

					break;
				}
			}

			return $content;
		}

		function callUserFunction($postUserFunc, $conf, $content) {

			$content .= $this->_render($conf);

			if($GLOBALS["TSFE"]->cObj->checkIf($conf["if."])) {				
				if($conf["wrap"]) {
					$content = $GLOBALS["TSFE"]->cObj->wrap($content, $conf["wrap"]);
				}

				if($conf["stdWrap."]) {
					$content = $GLOBALS["TSFE"]->cObj->stdWrap($content, $conf["stdWrap."]);
				}
			}

			return $content;
		}

		function _render($conf) {
			
			require_once(t3lib_extMgm::extPath('mkforms') . "api/class.tx_ameosformidable.php");
			$this->oForm = t3lib_div::makeInstance("tx_ameosformidable");
			$this->oForm->initFromTs(
				$this,
				$conf
			);

			return $this->oForm->render();
		}
	}

	if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/api/class.user_ameosformidable_cobj.php']) {
		include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/api/class.user_ameosformidable_cobj.php']);
	}

?>