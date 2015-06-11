<?php
/**
 * Plugin 'rdt_chooser' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_widgets_chooser_Main extends formidable_mainrenderlet {

	function _render() {

		$aHtml = array();
		$aHtmlBag = array();
		$sLabel = $this->getLabel();
		$sValue = $this->getValue();
		$sValueForHtml = $this->getValueForHtml($sValue);

		$aAddPost = array(
			"formdata" => array(
				$this->_getName() => "1"		// to simulate default browser behaviour
			)
		);

		$sFuncName = "_formidableRdtChooser" . t3lib_div::shortMd5($this->oForm->formid . $this->_getName());
		$sElementId = $this->_getElementHtmlId();

		$sMode = $this->_navConf("/submitmode");
		if($sMode == "draft") {
			$sSubmitEvent = $this->oForm->oRenderer->_getDraftSubmitEvent($aAddPost);
		} elseif($sMode == "test") {
			$sSubmitEvent = $this->oForm->oRenderer->_getTestSubmitEvent($aAddPost);
		} elseif($sMode == "clear") {
			$sSubmitEvent = $this->oForm->oRenderer->_getClearSubmitEvent($aAddPost);
		} elseif($sMode == "search") {
			$sSubmitEvent = $this->oForm->oRenderer->_getSearchSubmitEvent($aAddPost);
		} elseif($sMode == "full") {
			$sSubmitEvent = $this->oForm->oRenderer->_getFullSubmitEvent($aAddPost);
		} else {
			$sSubmitEvent = $this->oForm->oRenderer->_getRefreshSubmitEvent($aAddPost);
		}

		$sSystemField = $this->oForm->formid . "_AMEOSFORMIDABLE_SUBMITTER";
		$sSubmitter = $this->_getElementHtmlIdWithoutFormId();

$sScript = <<<JAVASCRIPT

	function {$sFuncName}(sValue, sItemId) {

		$("{$sElementId}").value = sValue;
		$("{$sSystemField}").value = "{$sSubmitter}";
		{$sSubmitEvent}
	}

JAVASCRIPT;

		if(!tx_rnbase_util_TYPO3::isTYPO62OrHigher()) {
			require_once(PATH_tslib . "class.tslib_pagegen.php");
		}
		$this->oForm->additionalHeaderData(
			$this->oForm->inline2TempFile($sScript, 'js', "Chooser " . $sHtmlId . " stuff")
		);

		$aItems = $this->_getItems();

		$sSelectedId = "";

		if(!empty($aItems)) {

			reset($aItems);
			while(list($sIndex, $aItem) = each($aItems)) {

				$sItemValue = $aItem["value"];
				$sCaption = $aItem["caption"];

				// on cr�e le nom du controle
				$sId = $this->_getElementHtmlId() . "_" . $sIndex;

				$sSelected = ($sValue == $sItemValue) ? 1 : 0;

				if($this->oForm->isRunneable($this->_navConf("/renderaslinks"))) {
					$sHref = $this->getForm()->getRunnable()->callRunnableWidget($this, $this->_navConf("/renderaslinks"), array("value" => $sItemValue));
				} else {
					$sHref = "javascript:void(" . $sFuncName . "(unescape('" . rawurlencode($sItemValue) . "'), unescape('" . rawurlencode($sId) . "')))";
				}

				$sLinkStart = "<a id=\"" . $sId . "\" href=\"" . $sHref . "\">";
				$sLinkEnd = "</a>";
				$sInner = $sLinkStart . $sCaption . $sLinkEnd;

				if($sSelected == 1) {
					$sLink = $this->_wrapSelected($sInner);
					$sSelectedId = $sId;
				} else {
					$sLink = $this->_wrapItem($sInner);
				}

				if(trim($sItemValue) == "") {
					$sChannel = "void";
				} else {
					$sChannel = $sValue;
				}

				$aHtmlBag[$sChannel . "."] = array(
					"id" => $sId,
					"input" => $sLink,
					"action" => $sHref,
					"tag." => array(
						"start" => $sLinkStart,
						"end" => $sLinkEnd,
					),
					"caption" => $sCaption,
					"inner" => $sInner,
					"value" => $sItemValue,
					"selected" => $sSelected,
				);

				$aHtml[] = $sLink;
			}

			$aHtmlBag["hidden"] = "<input type=\"hidden\" name=\"" . $this->_getElementHtmlName() . "\" id=\"" . $this->_getElementHtmlId() . "\" value=\"" . $sValueForHtml . "\" />";
			$aHtmlBag["separator"] = $this->_getSeparator();
			$aHtmlBag["value"] = $sValue;
			$aHtmlBag["selectedid"] = $sSelectedId;

			$aHtmlBag["__compiled"] = $this->_displayLabel(
				$this->getLabel()
			) . $this->_implodeElements($aHtml) . $aHtmlBag["hidden"];

			return $aHtmlBag;
		}
	}

	function _listable() {
		return $this->oForm->_defaultFalse("/listable/", $this->aElement);
	}

	function _getSeparator() {

		if(($mSep = $this->_navConf("/separator")) === FALSE) {
			$mSep = " &#124; ";
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

	function _searchable() {
		return $this->_defaultTrue("/searchable");
	}
}
