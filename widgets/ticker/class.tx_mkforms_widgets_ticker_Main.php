<?php
/** 
 * Plugin 'rdt_ticker' for the 'ameos_formidable' extension.
 *
 * @author	Loredana Zeca <typo3dev@ameos.com>
 */


class tx_mkforms_widgets_ticker_Main extends formidable_mainrenderlet {
	
	var $aLibs = array(
		"rdt_ticker_class" => "res/js/ticker.js",
	);

	var $sMajixClass = "Ticker";

	var $bCustomIncludeScript = TRUE;

	var $oDataStream = FALSE;
	var $aDatasource = FALSE;

	var $sSeparatorHtml = "";

	var $aConfig = FALSE;
	var $aLimitAndSort = FALSE;


	function _render() {

		$this->_initLimitAndSort();
		$this->_initDatasource();

		if(($sWidth = $this->_navConf("/width")) === FALSE) {
			$sWidth = "450";
		}

		if(($sHeight = $this->_navConf("/height")) === FALSE) {
			$sHeight = "18";
		}

		if(($sScrollMode = $this->_navConf("/scrolling/mode")) === FALSE || ($sScrollMode !== "horizontal" && $sScrollMode !== "vertical")) {
			$sScrollMode = "horizontal";
		}

		switch($sScrollMode) {
		case "horizontal":
			if(($sScrollDirection = $this->_navConf("/scrolling/direction")) === FALSE || ($sScrollDirection !== "left" && $sScrollDirection !== "right")) {
				$sScrollDirection = "left";
			}
			$this->sSeparatorHtml = "<div id='".$this->_getelementHtmlId().".clear' style='border:medium none; clear:both; font-size:1px; height:1px; line-height:1px;'><hr style='position:absolute; top:-50000px;' /></div>";
			break;
		case "vertical":
			if(($sScrollDirection = $this->_navConf("/scrolling/direction")) === FALSE || ($sScrollDirection !== "top" && $sScrollDirection !== "bottom")) {
				$sScrollDirection = "top";
			}
			break;
		}

		if(($sScrollStartDelay = $this->_navConf("/scrolling/startdelay")) === FALSE) {
			$sScrollStartDelay = "2500";
		}

		if(($sScrollNextDelay = $this->_navConf("/scrolling/nextdelay")) === FALSE) {
			$sScrollNextDelay = "100";
		}

		if(($sScrollAmount = $this->_navConf("/scrolling/amount")) === FALSE) {
			$sScrollAmount = ($sScrollDirection === "top" || $sScrollDirection === "left") ? -1 : 1;
		} else {
			$sScrollAmount = ($sScrollDirection === "top" || $sScrollDirection === "left") ? -($sScrollAmount) : $sScrollAmount;
		}

		if(($sScrollOverflow = $this->_navConf("/scrolling/overflow")) === FALSE) {
			$sScrollOverflow = "hidden";
		}
		

		if(($sOffsetTop = $this->_navConf("/offsettop")) === FALSE) {
			$sOffsetTop = "0";
		}
		
		if(($sOffsetLeft = $this->_navConf("/offsetleft")) === FALSE) {
			$sOffsetLeft = "0";
		}

		if(($sBackground = $this->_navConf("/background")) === FALSE) {
			$sBackground = "none";
		}
		if(($sBgColor = $this->_navConf("/bgcolor")) === FALSE) {
			$sBgColor = "transparent";
		}

		if(($sBorder = $this->_navConf("/border")) === FALSE) {
			$sBorder = "none";
		}
		if(($sBorderColor = $this->_navConf("/bordercolor")) === FALSE) {
			$sBorderColor = "white";
		}

		$this->aConfig = array(
			"width" => $sWidth,
			"height" => $sHeight,
			"item" => array(
				"width" => $this->_navConf("/itemwidth"),
				"height" => $this->_navConf("/itemheight"),
				"style" => $this->_navConf("/itemstyle"),
			),
			"scroll" => array(
				"mode" => $sScrollMode,
				"direction" => $sScrollDirection,
				"startDelay" => $sScrollStartDelay,
				"nextDelay" => $sScrollNextDelay,
				"amount" => $sScrollAmount,
				"stop" => (bool)$this->oForm->_isTrueVal($this->_navConf("/scrolling/stop")),
				"overflow" => $sScrollOverflow,
			),
			"offset" => array(
				"top" => $sOffsetTop,
				"left" => $sOffsetLeft,
			),
			"background" => $sBackground,
			"bgcolor" => $sBgColor,
			"border" => $sBorder,
			"bordercolor" => $sBorderColor,
		);

		$sLabel = $this->oForm->getConfigXML()->getLLLabel($this->_navConf("/label"));

		$sHtml = $this->_renderList();
		$sBox1 = "<div id='" . $this->_getelementHtmlId() . ".1'>" . $sHtml . "</div>";
		$sBox2 = "<div id='" . $this->_getelementHtmlId() . ".2'>" . $sHtml . "</div>";
		$sHtml = "<div id='" . $this->_getelementHtmlId() . "'>" . $this->_displayLabel($sLabel) . $sBox1 . $sBox2 . "</div>";
		$sHtml = "<div " . $this->_getAddInputParams() . ">" . $sHtml . "</div>";


		$aHtmlBag =& $this->aConfig;
		$aHtmlBag["__compiled"] = $sHtml;
		$aHtmlBag["html"] = $sHtml;

		// allowed because of $bCustomIncludeScript = TRUE
		$this->includeScripts($this->aConfig);

		return $aHtmlBag;
	}


	function &_renderList() {

		if ($this->aDatasource) {		// if this ticker has a datasource that must be used to generate his content

			if ($this->aDatasource["numrows"] == 0) {
				$sRowsHtml = "";

			} else {

				$sTemplate = $this->_getTemplate();
				$aAltRows = $this->_getRowsSubpart($sTemplate);
				$iNbAlt = count($aAltRows);

				$aRows = $this->aDatasource["results"];
				foreach($aRows as $i => $aRow) {
					$aCurRow = $this->_refineRow($aRow);
					$sRowHtml = $this->oForm->getTemplateTool()->parseTemplateCode(
						$aAltRows[$i % $iNbAlt],		// alternate rows
						$aCurRow
					);
					$sRowHtml = '<div class="ameosformidable-rdtticker-item">' . $sRowHtml . '</div>';
					$aRowsHtml[] = $sRowHtml;
				}
				$sRowsHtml = implode("", $aRowsHtml);
			}

			$sHtml = t3lib_parsehtml::substituteSubpart(
				$sTemplate,
				"###ROWS###",
				$sRowsHtml,
				FALSE,
				FALSE
			);
			$sHtml .= $this->sSeparatorHtml;

		} elseif (($this->_navConf("/html")) !== FALSE) {		// if this ticker has a html as content

			$sHtml = ($this->oForm->isRunneable($this->aElement["html"])) ? $this->getForm()->getRunnable()->callRunnableWidget($this, $this->aElement["html"]) : $this->_navConf("/html");
			$sHtml = $this->oForm->_substLLLInHtml($sHtml) . $this->sSeparatorHtml;

		} else {

			$this->oForm->mayday("RENDERLET TICKER <b>" . $this->_getName() . "</b> requires /datasource or /html to be properly set. Please check your XML configuration");

		}

		return $sHtml;
	}
	
	function &_refineRow($aData) {
		array_push($this->oForm->oDataHandler->__aListData, $aData);
		foreach($this->aChilds as $sName => $oChild) {
			$this->aChilds[$sName]->setValue($aData[$sName]);
		}
		$aCurRow = $this->renderChildsBag();
		array_pop($this->oForm->oDataHandler->__aListData);
		return $aCurRow;
	}

	function &_getRowsSubpart($sTemplate) {

		$aRowsTmpl = array();

		if (($sAltRows = $this->_navConf("/template/alternaterows")) !== FALSE && $this->oForm->isRunneable($sAltRows)) {
			$sAltList = $this->getForm()->getRunnable()->callRunnableWidget($this, $sAltRows);
		} elseif (($sAltList = $this->_navConf("/template/alternaterows")) === FALSE ){
			$this->oForm->mayday("RENDERLET TICKER <b>" . $this->_getName() . "</b> requires /template/alternaterows to be properly set. Please check your XML configuration");
		}

		$aAltList = t3lib_div::trimExplode(",", $sAltList);
		if(sizeof($aAltList) > 0) {
			$sRowsPart = t3lib_parsehtml::getSubpart($sTemplate, "###ROWS###");

			reset($aAltList);
			while(list(, $sAltSubpart) = each($aAltList)) {
				$aRowsTmpl[] = t3lib_parsehtml::getSubpart($sRowsPart, $sAltSubpart);
			}
		}

		return $aRowsTmpl;
	}

	function &_getTemplate() {

		if(($aTemplate = $this->_navConf("/template")) !== FALSE) {

			$sPath = t3lib_div::getFileAbsFileName($this->oForm->_navConf("/path", $aTemplate));
			if(!file_exists($sPath)) {
				$this->oForm->mayday("renderlet:" . $this->_getType() . "[name=" . $this->getName() . "] - The given template file path (<b>'" . $sPath . "'</b>) doesn't exists.");
			} elseif(is_dir($sPath)) {
				$this->oForm->mayday("renderlet:" . $this->_getType() . "[name=" . $this->getName() . "] - The given template file path (<b>'" . $sPath . "'</b>) is a directory, and should be a file.");
			} elseif(!is_readable($sPath)) {
				$this->oForm->mayday("renderlet:" . $this->_getType() . "[name=" . $this->getName() . "] - The given template file path exists but is not readable.");
			}

			if(($sSubpart = $this->oForm->_navConf("/subpart", $aTemplate)) === FALSE) {
				$sSubpart = $this->getName();
			}

			$sHtml = t3lib_parsehtml::getSubpart(
				t3lib_div::getUrl($sPath),
				$sSubpart
			);

			if(trim($sHtml) == "") {
				$this->oForm->mayday("renderlet:" . $this->_getType() . "[name=" . $this->getName() . "] - The given template (<b>'" . $sPath . "'</b> with subpart marquer <b>'" . $sSubpart . "'</b>) <b>returned an empty string</b> - Check your template");
			}

			return $this->oForm->getTemplateTool()->parseTemplateCode(
				$sHtml,
				$aChildsBag,
				array(),
				FALSE
			);
		}
	}

	function _initDatasource() {

		if(($sDsToUse = $this->_navConf("/datasource/use")) !== FALSE) {

			if(!array_key_exists($sDsToUse, $this->oForm->aODataSources)) {
				$this->oForm->mayday("RENDERLET TICKER <b>" . $this->_getName() . "</b> - refers to undefined datasource '" . $sDsToUse . "'. Check your XML conf.");
			} else {
				$this->oDataStream =& $this->oForm->aODataSources[$sDsToUse];
				$this->aDatasource = $this->oDataStream->_fetchData($this->aLimitAndSort);
			}
		}
	}

	function _initLimitAndSort() {

		if(($sLimit = $this->_navConf("/datasource/limit")) === FALSE) {
			$sLimit = "5";
		}

		if(($sSortBy = $this->_navConf("/datasource/orderby")) === FALSE) {
			$sSortBy = "tstamp";
		}

		if(($sSortDir = $this->_navConf("/datasource/orderdir")) === FALSE) {
			$sSortDir = "DESC";
		}

		$this->aLimitAndSort = array(
			"perpage" => $sLimit,
			"sortcolumn" => $sSortBy,
			"sortdirection" => $sSortDir,
		);
	}

	function mayHaveChilds() {
		return TRUE;
	}

	function _getElementHtmlName($sName = FALSE) {
		
		$sRes = parent::_getElementHtmlName($sName);
		$aData =& $this->oForm->oDataHandler->_getListData();

		if(!empty($aData)) {
			$sRes .= "[" . $aData["uid"] . "]";
		}

		return $sRes;
	}
	
	function _getElementHtmlNameWithoutFormId($sName = FALSE) {
		$sRes = parent::_getElementHtmlNameWithoutFormId($sName);
		$aData =& $this->oForm->oDataHandler->_getListData();

		if(!empty($aData)) {
			$sRes .= "[" . $aData["uid"] . "]";
		}

		return $sRes;
	}

	function _getElementHtmlId($sId = FALSE) {
		
		$sRes = parent::_getElementHtmlId($sId);

		$aData =& $this->oForm->oDataHandler->_getListData();
		if(!empty($aData)) {
			$sRes .= AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN . $aData["uid"] . AMEOSFORMIDABLE_NESTED_SEPARATOR_END;
		}

		return $sRes;
	}
}


if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_ticker/api/class.tx_rdtticker.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/ameos_formidable/api/base/rdt_ticker/api/class.tx_rdtticker.php"]);
}

?>