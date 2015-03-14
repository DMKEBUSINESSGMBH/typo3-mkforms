<?php
/**
 * Plugin 'rdt_jstree' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_widgets_jstree_Main extends formidable_mainrenderlet {

	var $aLibs = array(
		"rdt_jstree_class" => "res/js/jstree.js",
		"rdt_jstree_lib_class" => "res/lib/js/AxentTree.js",
		//"rdt_jstree_libcookie_class" => "res/lib/js/cookie.js"
	);

	var $sMajixClass = "JsTree";
	var $aPossibleCustomEvents = array(
		"onnodeclick",
		"onnodeopen",
		"onnodeclose"
	);

	var $bCustomIncludeScript = TRUE;

	var $aTreeData = array();

	function _render() {

		$this->oForm->getJSLoader()->loadScriptaculousDragDrop();

		$this->oForm->additionalHeaderData(
			'<link rel="stylesheet" type="text/css" href="' . $this->sExtWebPath . 'res/lib/css/tree.css" />',
			"rdt_jstree_lib_css"
		);


		$mValue = $this->getValue();
		$sLabel = $this->getLabel();
		$this->aTreeData = $this->_fetchData();
		$sTree = $this->renderTree($this->aTreeData);

		$sInput = "<ul id=\"" . $this->_getElementHtmlId() . "\" " . $this->_getAddInputParams() . ">" . $sTree . "</ul>";

		$this->includeScripts(array(
			"value" => $mValue
		));

		return array(
			"__compiled" => $this->_displayLabel($sLabel) . $sInput,
			"input" => $sInput,
			"label" => $sLabel,
			"value" => $mValue,
		);
	}

	function &_fetchData() {
		if(($mData = $this->_navConf("/data")) === FALSE || !$this->oForm->isRunneable($mData)) {
			$this->oForm->mayday("RENDERLET JSTREE <b>" . $this->_getName() . "</b> - requires <b>/data</b> to be properly set with a runneable. Check your XML conf.");
		}

		return $this->getForm()->getRunnable()->callRunnable($mData);
	}

	function renderTree($aData) {
		$aBuffer = array();
		$this->_renderTree($aData, $aBuffer);
		return implode("\n", $aBuffer);
	}

	function _renderTree($aData, &$aBuffer) {
		reset($aData);

		$aBuffer[] = "<li>";
		$aBuffer[] = "<span><input type='hidden' value=\"" . htmlspecialchars($aData["value"]) . "\"/>" . $aData["caption"] . "</span>";

		if(array_key_exists("childs", $aData)) {
			$aBuffer[] = "<ul>";

			reset($aData["childs"]);
			while(list($sKey,) = each($aData["childs"])) {
				$this->_renderTree($aData["childs"][$sKey], $aBuffer);
			}

			$aBuffer[] = "</ul>";
		}

		$aBuffer[] = "</li>";
	}

	function includeScripts($aConf = array()) {
		parent::includeScripts($aConf);

		$sAbsName = $this->getAbsName();

		$sInitScript =<<<INITSCRIPT
			Formidable.f("{$this->oForm->formid}").o("{$sAbsName}").init();
INITSCRIPT;

		# initalization is made post-init
			# as when rendered in an ajax context in a modalbox,
			# the HTML is available *after* init tasks
			# as the modalbox HTML is added to the page using after init tasks !

		$this->oForm->attachPostInitTask(
			$sInitScript,
			"Post-init JSTREE initialization",
			$this->_getElementHtmlId()
		);
	}

	function getSelectedLabel() {
		return $this->getNodeLabel(
			$this->getValue()
		);
	}

	function getNodeLabel($iUid) {
		return $this->_getNodeLabel(
			$iUid,
			$this->aTreeData
		);
	}

	function _getNodeLabel($iUid, $aData) {

		if($aData["value"] == $iUid) {
			return $aData["caption"];
		}

		if(array_key_exists("childs", $aData) && is_array($aData["childs"]) && !empty($aData["childs"])) {

			$aKeys = array_keys($aData["childs"]);
			reset($aKeys);
			while(list(, $sKey) = each($aKeys)) {
				if(($mRes = $this->_getNodeLabel($iUid, $aData["childs"][$sKey])) !== FALSE) {
					return $mRes;
				}
			}
		}

		return FALSE;
	}

	function getSelectedPath() {
		return $this->getPathForNode($this->getValue());
	}

	function getPathForNode($iUid) {
		return implode("/", $this->getPathArrayForNode($iUid)) . "/";
	}

	function getPathArrayForNode($iUid) {
		$aNodes = array();	// only to allow pass-by-ref
		$this->_getPathArrayForNode(
			$iUid,
			array("childs" => array($this->aTreeData)),
			$aNodes
		);
		$aNodes = array_reverse($aNodes, TRUE);
		reset($aNodes);
		return $aNodes;
	}

	function _getPathArrayForNode($iUid, $aData, &$aNodes) {
		if($aData["value"] == $iUid) {
			return TRUE;
		}

		if(array_key_exists("childs", $aData) && is_array($aData["childs"]) && !empty($aData["childs"])) {
			$aKeys = array_keys($aData["childs"]);
			reset($aKeys);
			while(list(, $sKey) = each($aKeys)) {
				if($this->_getPathArrayForNode($iUid, $aData["childs"][$sKey], $aNodes)) {
					$aNodes[$aData["childs"][$sKey]["value"]] = $aData["childs"][$sKey]["caption"];
					return TRUE;
				}
			}
		}

		return FALSE;

/*
		if($aData["value"] == $iUid) {
			$aNodes[$aData["value"]] = $aData["caption"];
			return TRUE;
		} else {
			if(array_key_exists("childs", $aData) && is_array($aData["childs"]) && !empty($aData["childs"])) {

				$aKeys = array_keys($aData["childs"]);
				reset($aKeys);
				while(list(, $sKey) = each($aKeys)) {
					if($this->_getPathArrayForNode($iUid, $aData["childs"][$sKey], $aNodes)) {
						$aNodes[$aData["childs"][$sKey]["value"]] = $aData["childs"][$sKey]["caption"];
					}
				}
			}
		}

		return FALSE;
*/
	}
}


	if (defined("TYPO3_MODE") && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_jstree/api/class.tx_rdttext.php"])	{
		include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_jstree/api/class.tx_rdttext.php"]);
	}
?>