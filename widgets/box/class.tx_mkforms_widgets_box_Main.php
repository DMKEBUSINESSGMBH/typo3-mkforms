<?php
/**
 * Plugin 'rdt_box' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_widgets_box_Main extends formidable_mainrenderlet {

	var $sMajixClass = "Box";
	var $bCustomIncludeScript = TRUE;
	var $aLibs = array(
		"rdt_box_class" => "res/js/box.js",
	);
	var $aPossibleCustomEvents = array(
		"ondragdrop",
		"ondraghover",
	);

	var $oDataSource = FALSE;
	var $sDsKey = FALSE;

	function _render() {

		$sHtml = ($this->oForm->isRunneable($this->aElement["html"])) ? $this->getForm()->getRunnable()->callRunnableWidget($this, $this->aElement["html"]) : $this->_navConf("/html");
		$sHtml = $this->oForm->_substLLLInHtml($sHtml);

		$sMode = $this->_navConf("/mode");
		if($sMode === FALSE) {
			$sMode = "div";
		} else {
			$sMode = strtolower(trim($sMode));
			if($sMode === "") {
				$sMode = "div";
			} elseif($sMode === "none" || $sMode === "inline") {
				$sMode = "inline";
			}
		}

		if($this->hasData()) {

			$sValue = $this->getValue();

			if(!$this->_emptyFormValue($sValue) && $this->hasData() && !$this->hasValue()) {
				$sHtml = $this->getValueForHtml($sValue);
			}

			$sName = $this->_getElementHtmlName();
			$sId = $this->_getElementHtmlId() . "_value";
			$sHidden = "<input type=\"hidden\" name=\"" . $sName . "\" id=\"" . $sId . "\" value=\"" . $this->getValueForHtml($sValue) . "\" />";
		} elseif($this->isDataBridge()) {

			$sDBridgeName = $this->_getElementHtmlName() . "[databridge]";
			$sDBridgeId = $this->_getElementHtmlId() . "_databridge";
			$sSignature = $this->dbridge_getCurrentDsetSignature();
			$sHidden = "<input type=\"hidden\" name=\"" . $sDBridgeName . "\" id=\"" . $sDBridgeId . "\" value=\"" . htmlspecialchars($sSignature) . "\" />";
		}

		if($sMode !== "inline") {
			$sBegin = "<" . $sMode . " id='" . $this->_getElementHtmlId() . "' " . $this->_getAddInputParams() . ">";
			$sEnd = "</" . $sMode . ">" . $sHidden;
		} else {
			$sBegin = "<!--BEGIN:BOX:inline:" . $this->_getElementHtmlId() . "-->";
			$sEnd = "<!--END:BOX:inline:" . $this->_getElementHtmlId() . "-->";
		}

		$aChilds = $this->renderChildsBag();
		$aChilds = $this->processBeforeDisplay($aChilds);
		$sCompiledChilds = $this->renderChildsCompiled($aChilds);

		// allowed because of $bCustomIncludeScript = TRUE
		$this->includeScripts(
			array(
				"hasdata" => $this->hasData(),
			)
		);

		if(($mDraggable = $this->_navConf("/draggable")) !== FALSE) {

			$aConf = array();

			if(is_array($mDraggable)) {
				if($this->_defaultTrue("/draggable/use") === TRUE) {
					$bDraggable = TRUE;
					$aConf["revert"] = $this->_defaultFalse("/draggable/revert");

					if(($sHandle = $this->_navConf("/draggable/handle")) !== FALSE) {
						$aConf["handle"] = $this->oForm->aORenderlets[$sHandle]->_getElementHtmlId();
					}

					if(($sConstraint = $this->_navConf("/draggable/constraint")) !== FALSE) {
						$aConf["constraint"] = strtolower($sConstraint);
					}
				}
			} else {
				$bDraggable = TRUE;
			}

			if($bDraggable === TRUE) {

				$sHtmlId = $this->_getElementHtmlId();

				$sJson = $this->oForm->array2json($aConf);

				$sScript = '
new Draggable("' . $sHtmlId . '", ' . $sJson . ');
';

				$this->oForm->attachInitTask($sScript);
			}
		}

		if(($mDroppable = $this->_navConf("/droppable")) !== FALSE) {

			$aConf = array();

			if(is_array($mDroppable)) {
				if($this->_defaultTrue("/droppable/use") === TRUE) {
					$bDroppable = TRUE;

					if(($sAccept = $this->_navConf("/droppable/accept")) !== FALSE) {
						$aConf["accept"] = $sAccept;
					}

					if(($sContainment = $this->_navConf("/droppable/containment")) !== FALSE) {
						$aConf["containment"] = Tx_Rnbase_Utility_Strings::trimExplode($sContainment);
						reset($aConf["containment"]);
						while(list($iKey,) = each($aConf["containment"])) {
							$aConf["containment"][$iKey] = $this->oForm->aORenderlets[$aConf["containment"][$iKey]]->_getElementHtmlId();
						}
					}

					if(($sHoverClass = $this->_navConf("/droppable/hoverclass")) !== FALSE) {
						$aConf["hoverclass"] = $sHoverClass;
					}

					if(($sOverlap = $this->_navConf("/droppable/overlap")) !== FALSE) {
						$aConf["overlap"] = $sOverlap;
					}

					if(($bGreedy = $this->_defaultFalse("/droppable/greedy")) !== FALSE) {
						$aConf["greedy"] = $bGreedy;
					}
				}
			} else {
				$bDroppable = TRUE;
			}

			if($bDroppable === TRUE) {

				$sHtmlId = $this->_getElementHtmlId();

				if(array_key_exists("ondragdrop", $this->aCustomEvents)) {
					$sJs = implode("\n", $this->aCustomEvents["ondragdrop"]);
					$aConf["onDrop"] = "function() {" . $sJs . "}";
				}

				if(array_key_exists("ondraghover", $this->aCustomEvents)) {
					$sJs = implode("\n", $this->aCustomEvents["ondraghover"]);
					$aConf["onHover"] = "function() {" . $sJs . "}";
				}

				$sJson = $this->oForm->array2json($aConf);

				$sScript = '
Droppables.add("' . $sHtmlId . '", ' . $sJson . ');
';

				$this->oForm->attachInitTask($sScript);
			}
		}

		$aHtmlBag = array(
			"__compiled" => $this->_displayLabel($sLabel) . $sBegin . $sHtml . $sCompiledChilds . $sEnd,
			"html" => $sHtml,
			"box." => array(
				"begin" => $sBegin,
				"end" => $sEnd,
				"mode" => $sMode,
			),
			"childs" => $aChilds
		);

		return $aHtmlBag;
	}

	function mayBeDataBridge() {
		return TRUE;
	}

	function setHtml($sHtml) {
		$this->aElement["html"] = $sHtml;
	}

	function _readOnly() {
		return TRUE;
	}

	function _renderOnly() {
		return $this->_defaultTrue("/renderonly/");
	}

	function _renderReadOnly() {
		return $this->_render();
	}

	function _activeListable() {
		return $this->oForm->_defaultTrue("/activelistable/", $this->aElement);
	}

	function _debugable() {
		return $this->oForm->_defaultFalse("/debugable/", $this->aElement);
	}

	function majixReplaceData($aData) {
		return $this->buildMajixExecuter(
			"replaceData",
			$aData
		);
	}

	function majixSetHtml($sData) {
		return $this->buildMajixExecuter(
			"setHtml",
			$this->oForm->_substLLLInHtml($sData)
		);
	}

	function majixSetValue($sData) {
		return $this->buildMajixExecuter(
			"setValue",
			$sData
		);
	}

	function majixToggleDisplay() {
		return $this->buildMajixExecuter(
			"toggleDisplay"
		);
	}

	function mayHaveChilds() {
		return TRUE;
	}

	function _emptyFormValue($sValue) {

		if($this->hasData()) {
			return (trim($sValue) === "");
		}

		return TRUE;
	}

	function hasValue() {
		return ($this->_navConf("/data/value") !== FALSE || $this->_navConf("/data/defaultvalue") !== FALSE);
	}

	function _searchable() {
		if($this->hasData()) {
			return $this->_defaultTrue("/searchable/");
		}

		return $this->_defaultFalse("/searchable/");
	}

	function doAfterListRender(&$oListObject) {
		parent::doAfterListRender($oListObject);

		if($this->hasChilds()) {
			$aChildKeys = array_keys($this->aChilds);
			reset($aChildKeys);
			while(list(, $sKey) = each($aChildKeys)) {
				$this->aChilds[$sKey]->doAfterListRender($oListObject);
			}
		}
	}

	function processBeforeDisplay($aChilds) {
		if(($aBeforeDisplay = $this->_navConf('/beforedisplay')) !== FALSE && $this->oForm->isRunneable($aBeforeDisplay)) {
			$aChilds = $this->getForm()->getRunnable()->callRunnableWidget($this, $aBeforeDisplay, $aChilds);
		}

		return $aChilds;
	}
}


	if (defined("TYPO3_MODE") && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_box/api/class.tx_rdtbox.php"])	{
		include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_box/api/class.tx_rdtbox.php"]);
	}

