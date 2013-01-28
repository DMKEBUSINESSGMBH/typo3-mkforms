<?php
/** 
 * Plugin 'rdt_autocomplete' for the 'ameos_formidable' extension.
 *
 * @author	Loredana Zeca <typo3dev@ameos.com>
 */


class tx_mkforms_widgets_autocomplete_Main extends formidable_mainrenderlet {

	private static $ignoreCharacters = array('/');
	
	var $aLibs = array(
		'widget_autocomplete_class' => 'res/js/autocomplete.js',
	);

	var $sMajixClass = 'Autocomplete';

	var $bCustomIncludeScript = TRUE;
	var $aPossibleCustomEvents = array (
		"onlistselect",
	);

	var $sTemplate = FALSE;
	var $aRowsSubpart = FALSE;

	var $aDatasource = FALSE;

	var $aConfig = FALSE;
	var $aLimitAndSort = FALSE;
	var $aFilters = FALSE;
	
	// Damit über die Felder iteriert wird, muss iteratingChilds=true sein
	var $iteratingChilds = TRUE;

	
	function _render() {

		$this->oForm->bStoreFormInSession = TRUE;	// instanciate the Typo3 and formidable context 

		$this->_checkRequiredProperties();		// check if all the required fields are specified into XML

		$this->sTemplate = $this->_getTemplate();
		$this->aRowsSubpart = $this->_getRowsSubpart($this->sTemplate);


		if(($sTimeObserver = $this->_navConf('/timeobserver')) === FALSE) {
			$sTimeObserver = '0.75';
		}

		if(($sSearchType = $this->_navConf('/searchtype')) === FALSE) {
			$sSearchType = 'inside';
		}

		if(($sSearchOnFields = $this->_navConf('/searchonfields')) === FALSE) {
			$this->oForm->mayday('RENDERLET AUTOCOMPLETE <b>' . $this->_getName() . '</b> requires the /searchonfields to be set. Please check your XML configuration!');
		}

		if(($sItemClass = $this->_navConf('/itemclass')) === FALSE) {
			$sItemClass = 'mkforms-autocomplete-item';
		}
	
		if(($sLoaderClass = $this->_navConf('/loaderclass')) === FALSE) {
			$sLoaderClass = 'mkforms-autocomplete-loader';
		}
		if(($sChildsClass = $this->_navConf('/listclass')) === FALSE) {
			$sChildsClass = 'mkforms-autocomplete-list';
		}

		if(($sSelectedItemClass = $this->_navConf('/selecteditemclass')) === FALSE) {
			$sSelectedItemClass = 'selected';
		}

		$sHtmlId = $this->_getElementHtmlId();
		$sObject = 'rdt_autocomplete';
		$sServiceKey = 'lister';
		$sFormId = $this->oForm->formid;
		$sSafeLock = $this->_getSessionDataHashKey();
		// thwoerID without iterating id
		$sThrower = $this->_getElementHtmlId(false, true, false);
		
		$sSearchUrl = tx_mkforms_util_Div::removeEndingSlash(t3lib_div::getIndpEnv('TYPO3_SITE_URL')) . '/index.php?eID='.tx_mkforms_util_Div::getAjaxEId().'&object=' . $sObject . '&servicekey=' . $sServiceKey . '&formid=' . $sFormId . '&safelock=' . $sSafeLock . '&thrower=' . $sThrower;

		$GLOBALS['_SESSION']['ameos_formidable']['ajax_services'][$sObject][$sServiceKey][$sSafeLock] = array(
			'requester' => array(
				'name' => $this->_getName(),
				'xpath' => $this->sXPath, // Der Wert wird im INIT gesetzt
			),
		);

		$sLabel = $this->oForm->getConfigXML()->getLLLabel($this->_navConf('/label'));
		$sValue = $this->getValue(); // hier steckt bei buhl ab und zu ein array drinn, warum!? lister? #2439
		$sValueForHtml = $this->getValueForHtml($sValue);

		$sInput = '<input type="text" name="' .$this->_getElementHtmlName(). '" id="' .$this->_getElementHtmlId(). '" value="' . $sValueForHtml . '" ' .$this->_getAddInputParams(). ' />';

		//div has no name (name="' .$this->_getElementHtmlName(). '[list]")
		$sChilds  = '<div id="' .$this->_getElementHtmlId(). AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN .'loader" class="'. $sLoaderClass .'"></div>';
		$sChilds .= '<div id="' .$this->_getElementHtmlId(). AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN .'list" class="'. $sChildsClass .'"></div>';

		$aHtmlBag = array(
			"__compiled" => $this->_displayLabel($sLabel) . $sInput . $sChilds,
			"label"		=> $sLabel,
			"name"		=> $this->_getElementHtmlName(),
			"id"		=> $this->_getElementHtmlId(),
			"value"		=> is_string($sValue) ? htmlspecialchars($sValue) : $sValue,
			"input" => $sInput,
			"childs" => $sChilds,
			"html" => $sInput . $sChilds,
			"addparams"	=> $this->_getAddInputParams(),
		);
		
		// allowed because of $bCustomIncludeScript = TRUE
		$this->aConfig = array(
			"timeObserver" => $sTimeObserver,
			"searchType" => $sSearchType,
			"searchFields" => $sSearchOnFields,
			"searchUrl" => $sSearchUrl,
			"item" => array(
				"width" => $this->_navConf("/itemwidth"),
				"height" => $this->_navConf("/itemheight"),
				"style" => $this->_navConf("/itemstyle"),
				"class" => $sItemClass,
			),
			"selectedItemClass" => $sSelectedItemClass,
			"jsExtend" => $this->_navConf("/jsextend", false),
			"selectionRequired" => $this->defaultFalse("/selectionrequired"),
			"hideItemListOnLeave" => $this->defaultTrue("/hideitemlistonleave"),
		);
		$this->includeScripts($this->aConfig);

		return $aHtmlBag;
	}

	function &_getRowsSubpart($sTemplate) {

		$aRowsTmpl = array();
		if (($sAltRows = $this->_navConf("/template/alternaterows")) !== FALSE) {
			if($this->oForm->isRunneable($sAltRows)) {
				$sAltRows = $this->oForm->isRunneable($sAltRows);
			}
			if(!is_string($sAltRows)) {
				$sAltRows = FALSE;
			} else {
				$sAltRows = trim($sAltRows);
			}
		}

		$aAltList = t3lib_div::trimExplode(",", $sAltRows);
		if(sizeof($aAltList) > 0) {
			$sRowsPart = t3lib_parsehtml::getSubpart($sTemplate, "###ROWS###");

			reset($aAltList);
			while(list(, $sAltSubpart) = each($aAltList)) {
				$sHtml = t3lib_parsehtml::getSubpart($sRowsPart, $sAltSubpart);
				if(empty($sHtml)) {
					$this->oForm->mayday("renderlet:" . $this->_getType() . "[name=" . $this->getName() . "] - The given template with subpart marquer <b>'" . $sAltSubpart . "'</b> returned an empty string - Please check your template!");
				}
				$aRowsTmpl[] = $sHtml;
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
				$this->oForm->mayday("renderlet:" . $this->_getType() . "[name=" . $this->getName() . "] - The given template (<b>'" . $sPath . "'</b> with subpart marquer <b>'" . $sSubpart . "'</b>) <b>returned an empty string</b> - Check your template!");
			}

			return $sHtml;
//			return $this->oForm->getTemplateTool()->parseTemplateCode(
//				$sHtml,
//				$aChildsBag,
//				array(),
//				FALSE
//			);
		} else {
			$this->oForm->mayday("The renderlet:autocomplete <b>" . $this->_getName() . "</b> requires /template to be properly set. Please check your XML configuration");
		}
	}

	function handleAjaxRequest($oRequest) {
		$this->aConfig['searchType'] = t3lib_div::_GP('searchType');
		$this->aConfig['searchText'] = t3lib_div::_GP('searchText');
		$this->aConfig['searchCounter'] = (int)t3lib_div::_GP('searchCounter');

		$this->renderList($aParts, $aRowsHtml);
		
		return array(
			'counter' => $this->aConfig['searchCounter'],
			'html' => array(
				'before' => trim($aParts[0]),
				'after' => trim($aParts[1]),
				'childs' => $aRowsHtml,
			),
			'results' => count($aRowsHtml),
		);
	}

	private function renderList(&$aParts, &$aRowsHtml) {

		$aLimitAndSort = $this->initLimitAndSort();
		$aFilters = $this->initFilters();
		$this->initSearchDS($aFilters, $aLimitAndSort);

		if ($this->aDatasource && $this->aDatasource['numrows']) {		// if there is some items to render

			$iNbAlt = count($this->aRowsSubpart);

			$aRows = $this->aDatasource['results'];
			foreach($aRows as $i => $aRow) {
				// Hier wird die Ergebniszeile wohl mit den definierten Kind-Widgets gemerged.
				$aCurRow = $this->_refineRow($aRow);
				$sRowHtml = $this->getForm()->getTemplateTool()->parseTemplateCode(
					$this->aRowsSubpart[$i % $iNbAlt],		// alternate rows
					$aCurRow
				);
				$aRowsHtml[] = trim($sRowHtml);
			}

			$sHtml = t3lib_parsehtml::substituteSubpart(
				trim($this->sTemplate),
				'###ROWS###',
				'###ROWS###',
				FALSE,
				FALSE
			);
			$sHtml = str_replace(array('{autocomplete_search.numrows}'), array(count($aRows)), $sHtml);
			$aParts = explode('###ROWS###', $sHtml);
		}
	}

	function &_refineRow($aData) {

		$sText = preg_quote($this->aConfig['searchText'], '/');

		$sPattern = '/' . $sText . '/ui';
		switch($this->aConfig['searchType']) {
			case 'begin':
				$sPattern = '/^' . $sText . '/ui';
				break;
			case 'inside':
				$sPattern = '/' . $sText . '/ui';
				break;
			case 'end':
				$sPattern = '/' . $sText . '$/ui';
				break;
		}
		$bReplaced = false;

		array_push($this->getForm()->getDataHandler()->__aListData, $aData);
		foreach($this->aChilds as $sName => $oChild) {
			if ($bReplaced) {
				$sValue = $aData[$sName];
			} else {				
				if($this->_defaultTrue('highlightresults')) {
					$sValue = preg_replace_callback(
						$sPattern,
						array(
							$this,
							'highlightSearch',
						),
						$aData[$sName],
						1		// replace only the first occurence
					); 
	
					if ($sValue != $aData[$sName]) {
						$bReplaced = true;
					}
				} else {
					$sValue = $aData[$sName];
					$bReplaced = true;
				}
			}
			//Wir können hier nicht die setValue Methode der renderlets aufrufen,
			//da nach dem Submit die Felder gefüllt werden würden, was zu fehlern führt!
			$this->setChildValue($this->aChilds[$sName],$sValue);
//			$this->aChilds[$sName]->setValue($sValue);
		}
		
		$aCurRow = $this->renderChildsBag();
		array_pop($this->getForm()->getDataHandler()->__aListData);
		return $aCurRow;
	}

	function setChildValue($rdt, $mValue) {
		$sAbsName = $rdt->getAbsName();
		$sAbsPath = str_replace(AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN, "/", $sAbsName);

		$aData = array();
		$this->getForm()->setDeepData($sAbsPath, $aData, $mValue);

		$rdt->mForcedValue = $mValue;
		$rdt->bForcedValue = TRUE;
	}

	private function highlightSearch($aMatches) {
//		return $aMatches[0];
		return '<strong>' .$aMatches[0]. '</strong>';
	}
	
	private function stringEncode(){
		
	}
	private function stringDecode(){
		
	}

	private function initSearchDS($aFilters, $aLimitAndSort) {
		if(($sDsToUse = $this->_navConf('/datasource/use')) === FALSE) return;
		
		// zusätzliche parameter besorgen 
		$aConfig = $this->_navConf('/datasource/config');
		$aConfig = $aConfig === false ? $this->_navConf('/datasource/params') : $aConfig;
		$aConfig = is_array($aConfig)
			? $this->getForm()->getRunnable()
				->parseParams($aConfig, $aLimitAndSort)
			: $aLimitAndSort;
		
		try {
			$this->aDatasource = $this->getForm()->getDataSource($sDsToUse)
									->_fetchData($aConfig, $aFilters);
		}
		catch(tx_mkforms_exception_DataSourceNotFound $e) {
			tx_mkforms_util_Div::mayday('WIDGET AUTOCOMPLETE <b>' . $this->_getName() . '</b> - refers to undefined datasource "' . $sDsToUse . '". Check your XML conf.');
		}
	}

	private function initLimitAndSort() {
		// dont set filters if set to false!
		$aFilters = array();
		
		if(($sLimit = $this->_navConf('/datasource/limit')) === FALSE) {
			$sLimit = '5';
		}
		if(!$this->isFalseVal($sLimit)) {
			$aFilters['perpage'] = $sLimit;
		}

		if(($sSortBy = $this->_navConf('/datasource/orderby')) === FALSE) {
			$sSortBy = 'tstamp';
		}
		if(!$this->isFalseVal($sSortBy)) {
			$aFilters['sortcolumn'] = $sSortBy;
		}

		if(($sSortDir = $this->_navConf('/datasource/orderdir')) === FALSE) {
			$sSortDir = 'DESC';
		}
		if(!$this->isFalseVal($sSortDir)) {
			$aFilters['sortdirection'] = $sSortDir;
		}
		
		return $aFilters;
	}

	private function initFilters() {

		$aFilters = array();
		if($this->aConfig['searchType'] == 'external') {
			// Suche wird extern durchgeführt. Es ist nur der Suchbegriff notwendig
			$aFilters[] = $this->aConfig['searchText'];
			return $aFilters;
		}

		$aFields = explode(',', $this->aConfig['searchFields']);
		$aFilter = array();

		foreach($aFields as $sField) {

			switch($this->aConfig['searchType']) {
				case 'begin':
					$aFilter[] = '( '.$sField." LIKE '".$this->aConfig['searchText']."%' )";
					break;
				case 'end':
					$aFilter[] = '( '.$sField." LIKE '%".$this->aConfig['searchText']."' )";
					break;
				default: // 'inside':
					$aFilter[] = '( '.$sField." LIKE '%".$this->aConfig['searchText']."%' )";
					break;
			}
		}
		$aFilters[] = '( ' . implode(' OR ', $aFilter) . ' )';
		return $aFilters;
	}

	function mayHaveChilds() {
		return TRUE;
	}

	function _getElementHtmlName($sName = FALSE) {
		
		$sRes = parent::_getElementHtmlName($sName);
		$aData =& $this->oForm->oDataHandler->_getListData();

		if(!empty($aData)) {
			$sRes .= '[' . $aData['uid'] . ']';
		}

		return $sRes;
	}
	
	function _getElementHtmlNameWithoutFormId($sName = FALSE) {
		$sRes = parent::_getElementHtmlNameWithoutFormId($sName);
		$aData =& $this->oForm->oDataHandler->_getListData();

		if(!empty($aData)) {
			$sRes .= '[' . $aData['uid'] . ']';
		}

		return $sRes;
	}

	/*
	 * Was ist das!?
	 * warum wird hier nochmal die id des listers mit angehängt!?
	 * wird doch beireits beim lister gemacht, somal das feld 'uid' in den seltensten fällen stimmt.
	 * 
	 */
	 /*
	function _getElementHtmlId($sId = FALSE, $withForm = true, $withIteratingId = true) {
		$sRes = parent::_getElementHtmlId($sId, $withForm, $withIteratingId);

		$aData =& $this->oForm->oDataHandler->_getListData();
		if(!empty($aData)) {
			$sRes .= AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN . $aData['uid'] . AMEOSFORMIDABLE_NESTED_SEPARATOR_END;
		}

		return $sRes;
	}
	*/

	function _checkRequiredProperties() {

		if($this->_navConf('/datasource/use') === FALSE) {
			$this->oForm->mayday('The renderlet:autocomplete <b>' . $this->_getName() . '</b> requires /datasource/use to be properly set. Please check your XML configuration');
		}
		if($this->_navConf('/template/path') === FALSE) {
			$this->oForm->mayday('The renderlet:autocomplete <b>' . $this->_getName() . '</b> requires /template/path to be properly set. Please check your XML configuration');
		}
		if($this->_navConf('/template/subpart') === FALSE) {
			$this->oForm->mayday('The renderlet:autocomplete <b>' . $this->_getName() . '</b> requires /template/subpart to be properly set. Please check your XML configuration');
		}
		if($this->_navConf('/template/alternaterows') === FALSE) {
			$this->oForm->mayday('The renderlet:autocomplete <b>' . $this->_getName() . '</b> requires /template/alternaterows to be properly set. Please check your XML configuration');
		}
		if (($aChilds = $this->_navConf('/childs')) === FALSE ){
			$this->oForm->mayday('The renderlet:autocomplete <b>' . $this->_getName() . '</b> requires /childs to be properly set. Please check your XML configuration');
		} elseif (!is_array($aChilds)) {
			$this->oForm->mayday('The renderlet:autocomplete <b>' . $this->_getName() . '</b> requires at least one child to be properly set. Please check your XML configuration: define a renderlet:* as child.');
		}

	}

	/**
	 * Muss in einen postInit-Task initialisiert werden, sonst klappt es mit Ajax nicht.
	 * @see api/formidable_mainrenderlet#includeScripts($aConfig)
	 */
	function includeScripts($aConf=array()) {
		parent::includeScripts($aConf);
		$sAbsName = $this->_getElementHtmlIdWithoutFormId();

		$sInitScript =<<<INITSCRIPT
		Formidable.f("{$this->oForm->formid}").o("{$sAbsName}").initialize();
INITSCRIPT;

		$this->getForm()->attachPostInitTask($sInitScript,'postinit Autocomplete initialization', $this->_getElementHtmlId());
	}

	/**
	 * Methode muss überschrieben werden, da Autocomplete als Kindelement gewertet werden
	 * Sonst gibt es eine Exception!
	 */
	public function checkValue(&$aGP) {
		return;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/widgets/autocomplete/class.tx_mkforms_widgets_autocomplete_Main.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/widgets/autocomplete/class.tx_mkforms_widgets_autocomplete_Main.php']);
}

?>