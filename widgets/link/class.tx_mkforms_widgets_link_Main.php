<?php
/**
 * Plugin 'rdt_link' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_widgets_link_Main extends formidable_mainrenderlet {

	var $aLibs = array(
		"rdt_link_class" => "res/js/link.js",
	);
	var $bCustomIncludeScript = TRUE;

	var $sMajixClass = "Link";

	function _render() {
		return $this->_renderReadOnly();
	}

	function _renderReadOnly() {

		$sValue = $this->getValue();

		if($this->_isTrue("/typolink")) {
			$sUrl = $this->getForm()->getCObj()->typoLink_URL(array(
				"parameter" => $sValue,
				"additionalParams" => "",
			));
		} elseif(
			(($iPageId = $this->_navConf("pageid")) !== FALSE) ||
			(($iPageId = $this->_navConf("pid")) !== FALSE)
		) {
			if($this->oForm->isRunneable($iPageId)) {
				$iPageId = $this->getForm()->getRunnable()->callRunnableWidget($this, $iPageId);
			}
			$absoluteUrl = $this->_defaultFalse("forceabsoluteurl");
			$sUrl = $this->getForm()->getCObj()->typoLink_URL(array(
				"parameter" => $iPageId ? $iPageId : $GLOBALS['TSFE']->id,
				"additionalParams" => "",
				"forceAbsoluteUrl" => $absoluteUrl
			));
		} else {
			$sUrl = $this->_navConf("/href");

			if($sUrl === FALSE) {
				$sUrl = $this->_navConf("/url");
			}

			if($this->oForm->isRunneable($sUrl)) {
				$sUrl = $this->getForm()->getRunnable()->callRunnableWidget($this, $sUrl);
			}

			if(!$sUrl) {

				$sValue = trim($sValue);
				$aParsedURL = @parse_url($sValue);

				if(Tx_Rnbase_Utility_T3General::inList('ftp,ftps,http,https,gopher,telnet', $aParsedURL['scheme'])) {
					$sUrl = $sValue;
				} else {
					$sUrl = FALSE;
				}

			}
		}

		if(($sAnchor = $this->_navConf("/anchor")) !== FALSE) {
			if($this->oForm->isRunneable($sAnchor)) {
				$sAnchor = $this->getForm()->getRunnable()->callRunnableWidget($this, $sAnchor);
			}

			if(is_string($sAnchor) && $sAnchor !== "") {
				$sAnchor = str_replace("#", "", $sAnchor);
			} else {
				$sAnchor = "";
			}

			if($sAnchor !== "") {
				if($sUrl === FALSE) {
					$sUrl = Tx_Rnbase_Utility_T3General::getIndpEnv("REQUEST_URI");
				}

				if(array_key_exists($sAnchor, $this->oForm->aORenderlets)) {
					$sAnchor = $this->oForm->aORenderlets[$sAnchor]->_getElementHtmlId();
				}

				$sAnchor = "#" . $sAnchor;
			}
		}

		if($sUrl !== FALSE) {
			if($sAnchor !== FALSE) {
				$sHref = $sUrl . $sAnchor;
			} else {
				$sHref = $sUrl;
			}
		} else {
			$sHref = FALSE;
		}



		$aHtmlBag = array(
			"url" => $sUrl,
			"href" => $sHref,
			"anchor" => $sAnchor,
			"tag." => array(
				"begin" => "",
				"innerhtml" => "",
				"end" => "",
			)
		);

		if(!$this->oForm->_isTrue("/urlonly", $this->aElement)) {

			if($this->hasChilds()) {

				$aChilds = $this->renderChildsBag();
				$sCaption = $this->renderChildsCompiled(
					$aChilds
				);
			} else {
				if(!$this->_emptyFormValue($sValue)) {
					$sCaption = $sValue;
				} else {
					$sCaption = $sHref;
				}

				if(($sLabel = $this->getLabel()) !== "") {
					$sCaption = $sLabel;
				} else {

					$aItems = $this->_getItems();
					if(count($aItems) > 0) {

						reset($aItems);
						while(list($itemindex, $aItem) = each($aItems)) {

							if($aItem["value"] == $value) {
								$sCaption = $aItem["caption"];
							}
						}
					}
				}
			}

			if($sCaption === FALSE) {
				$sCaption = "";
			}

			$aHtmlBag["caption"] = $sCaption;

			if($sHref !== FALSE) {
				$aHtmlBag["tag."]["begin"] = "<a " . ($sHref != "" ? "href=\"" . $sHref . "\"" : "") . " id=\"" . $this->_getElementHtmlId() . "\"" . $this->_getAddInputParams() . ">";
				$aHtmlBag["tag."]["innerhtml"] = $sCaption;
				$aHtmlBag["tag."]["end"] = "</a>";
			} else {
				$aHtmlBag["tag."]["begin"] = "<span id=\"" . $this->_getElementHtmlId() . "\" " . $this->_getAddInputParams() . ">";
				$aHtmlBag["tag."]["innerhtml"] = $sCaption;
				$aHtmlBag["tag."]["end"] = "</span>";
			}

			$sCompiled = $aHtmlBag["tag."]["begin"] . $aHtmlBag["tag."]["innerhtml"] . $aHtmlBag["tag."]["end"];
			$aHtmlBag["wrap"] = $aHtmlBag["tag."]["begin"] . "|" . $aHtmlBag["tag."]["end"];

		} else {
			$sCompiled = $sHref;
		}

		$aHtmlBag["__compiled"] = $sCompiled;

		$this->includeScripts(array(
			// Timeout in ms
			'followTimeout' => ($timeout = $this->_navConf('/followtimeout')) === false ? 0 : intval($timeout),
		));

		return $aHtmlBag;
	}

	function _renderOnly() {
		return TRUE;
	}

	function _readOnly() {
		return TRUE;
	}

	function _activeListable() {		// listable as an active HTML FORM field or not in the lister
		return $this->oForm->_defaultTrue("/activelistable/", $this->aElement);
	}

	function _searchable() {
		return $this->oForm->_defaultFalse("/searchable/", $this->aElement);
	}

	function mayHaveChilds() {
		return TRUE;
	}

	function _getAddInputParamsArray() {
		$aAddParams = parent::_getAddInputParamsArray();
		if(($sTarget = $this->_navConf("/target")) !== FALSE) {
			$aAddParams[] = " target=\"" . $sTarget . "\" ";
		}

		reset($aAddParams);
		return $aAddParams;
	}

	function majixFollow($bDisplayLoader = false) {
		return $this->buildMajixExecuter( "follow", $bDisplayLoader);
	}
}
