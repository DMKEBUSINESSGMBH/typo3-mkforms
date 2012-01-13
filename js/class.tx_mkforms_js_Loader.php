<?php
require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_mkforms_util_Json');

/**
 * Diese Klasse bindet das notwendige Javascript ein. Es ist eine neue Fassung der
 * Klasse formidable_jslayer.
 *
 * Änderung im Vorgehen:
 * Die Klasse sollte grundsätzliche JS-Parts zur Verfügung stellen. Danach wird ein JS-Wrapper nach den
 * notwendigen Scripten gefragt. Diese werden dann hier eingebunden.
 * Minify und Zip werden hier erledigt.
 * base - prototype
 * effects - scriptaculous
 * tooltip -
 * dragndrop -
 *
 *
 */
class tx_mkforms_js_Loader {
	private static $isLoaded = false;

	var $oForm = null;
	var $bLoadScriptaculous = FALSE;
	var $bLoadScriptaculousDragDrop = FALSE;
	var $bLoadScriptaculousBuilder = FALSE;
	var $bLoadtooltip = FALSE;
	var $headerKeys = array(); // Hier sammeln wir die Keys der zusatzlichen JS-Scripte
	
	var $aHeadersAjax		= array();	// stores the headers that are added to the page via ajax
	var $aHeadersWhenInjectNonStandard = array();	// stores the headers when they have to be injected in the page content at given marker

	private function __construct($form) {
		$this->init($form);
	}

	/**
	 * Returns the form instance
	 *
	 * @return tx_ameosformidable
	 */
	private function getForm() {
		return $this->oForm;
	}
	/**
	 * Erstellt eine Instanz dieser Klasse
	 *
	 * @param tx_mkforms_forms_IForm $form
	 * @return tx_mkforms_js_Loader
	 */
	public static function createInstance($form) {
		return new tx_mkforms_js_Loader($form);
	}
	private function init(&$oForm) {
		require_once(PATH_tslib . 'class.tslib_pagegen.php');
		$this->oForm =& $oForm;
	}
	

	/**
	 * Genau ein Aufruf in formidable_mainrenderer
	 * Das wurde bisher ziemlich verteilt eingebunden.
	 * Neue Strategie:
	 * - Sammeln aller notwendigen JS-Dateien
	 * - ggf. Minify und gzip
	 * - Gemeinsame Einbindung der JS-Dateien
	 * - Einbindung der Inittask
	 *
	 */
	public function includeBaseLibraries() {
		if(!$this->useJs()) return;
		
//t3lib_div::debug('inclOnceLibs', 'tx_mkforms_util_JSLoader :: _includeOnceLibs'); // TODO: remove me
		if($this->mayLoadJsFramework()) {
			if(!self::$isLoaded) {

				// Minimierte Scripte werden nun über getScriptPath gesetzt/geprüft!
//				if($this->minified()) {
//					$this->_includeMinifiedJs();
//				} else {
					$this->_includePrototype();
					$this->_includeJSFramework();
//				}
				$this->additionalHeaderData(
					'<!-- consider formidable core loaded after this line -->',
					'tx_ameosformidable_core',
					$bFirstPos = FALSE,$sBefore = FALSE,$sAfter = 'tx_mkforms_jsbase'
				);
				
				self::$isLoaded = TRUE;
			}
			$this->includeFormidablePath();
			$this->includeThisFormDesc();
			$this->includeAdditional();
		}
		$this->includeDebugStyles();
	}

	/**
	 * Einige Widgets können zusätzliche Bibliotheken benötigen. Diese können mit dieser Methode eingebunden werden
	 *
	 */
	public function includeAdditionalLibraries() {
//		t3lib_div::debug('Scriptac', 'tx_ameosformidable :: _render'); // TODO: remove me
		$this->includeScriptaculous();
		$this->includeTooltip();

	}
	
	private function includeAdditional() {
		if(($sLibs = $this->getForm()->getConfig()->get('/meta/libs')) === FALSE) return;
		
		//debug($sLibs);
		$aLibs = t3lib_div::trimExplode(',', $sLibs);
		reset($aLibs);
		while(list(, $sLib) = each($aLibs)) {
			if($sLib === 'scriptaculous') {
				$this->loadScriptaculous();
			} elseif($sLib === 'dragdrop') {
				$this->loadScriptaculousDragDrop();
			} elseif($sLib === 'builder') {
				$this->loadScriptaculousBuilder();
			} elseif($sLib === 'tooltip') {
				$this->loadToolTip();
			}
		}
	}
	/**
	 * Wether or not Javascript is used
	 *
	 * @return boolean
	 */
	public function useJs() {
		return $this->getForm()->_defaultTrue('/meta/accessibility/usejs');
	}

	/**
	 * Aufruf in formidable_mainrenderer
	 *
	 */
	private function includeThisFormDesc() {
		$aConf = array(
			'sFormId' => $this->oForm->formid,
			'Misc' => array(
				'Urls' => array(
					'Ajax' => array(
						'event' => tx_mkforms_util_Div::removeEndingSlash(t3lib_div::getIndpEnv('TYPO3_SITE_URL')) . '/index.php?eID='.tx_mkforms_util_Div::getAjaxEId().'&object=tx_ameosformidable&servicekey=ajaxevent',
						'service' => tx_mkforms_util_Div::removeEndingSlash(t3lib_div::getIndpEnv('TYPO3_SITE_URL')) . '/index.php?eID='.tx_mkforms_util_Div::getAjaxEId().'&object=tx_ameosformidable&servicekey=ajaxservice',
					),
				),
				'MajixSpinner' => (($aSpinner = $this->oForm->_navConf('/meta/majixspinner')) !== FALSE) ? $aSpinner : array(),
				'useUserChange' => $this->getForm()->_defaultFalse('/meta/form/useuserchange'),
			),
		);

		$sJson = tx_mkforms_util_Json::getInstance()->encode($aConf);
		$sScript = <<<JAVASCRIPT

Formidable.Context.Forms["{$this->oForm->formid}"] = new Formidable.Classes.FormBaseClass(
	{$sJson}
);

JAVASCRIPT;
			
			if(isset($GLOBALS['BE_USER']) && method_exists($GLOBALS['BE_USER'], 'isAdmin') && $GLOBALS['BE_USER']->isAdmin() && $this->oForm->bDebug) {
				$sScript .= <<<JAVASCRIPT

Formidable.f("{$this->oForm->formid}").Manager = {
	enabled: true,
	Xml: {
		path: "{$this->oForm->_xmlPath}"
	}
};

JAVASCRIPT;
		}
		// JS-Call einbinden
		$this->getForm()->attachInitTask($sScript,'Form \'' . $this->oForm->formid . '\' instance description','framework-init');
	}

	private function includeDebugStyles() {
		if($this->oForm->bDebug) {
			
			$sPath = t3lib_extMgm::siteRelPath('mkforms');
			$this->getForm()->additionalHeaderData(
				"<link rel='stylesheet' type='text/css' href='" . $sPath . "res/css/debug.css' />",
				'tx_ameosformidable_debugstyles'
			);
		}
	}

	/**
	 * @deprecated
	 * aufrund der verschiedenen frameworks (prototype / jquery)
	 * ist ein mergedes und minimiertes formidable nicht richtig möglich
	 * Minimierte Scripte werden nun mittels getScriptPath() geprüft!
	 * @see self::_includeJSFramework();
	 */
	private function _includeMinifiedJs() {

		if($this->gziped()) {
			$sPath = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . t3lib_extMgm::siteRelPath('mkforms') . 'res/jsfwk/minified/formidable.minified.js.php';
		} else {
			$sPath = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . t3lib_extMgm::siteRelPath('mkforms') . 'res/jsfwk/minified/formidable.minified.js';
		}

		$this->oForm->additionalHeaderData(
			"<script type=\"text/javascript\" src=\"" . $sPath . "\"></script>",
			'tx_mkforms_jsbase_fwk',
			TRUE
		);
	}

	/**
	 * Einbindung der Prototype-Basisskripte
	 * - res/jsfwk/prototype/prototype.js
	 * - res/jsfwk/prototype/addons/lowpro/lowpro.js
	 * - res/jsfwk/prototype/addons/base/Base.js
	 * - res/jsfwk/json/json.js
	 *
	 */
	private function _includePrototype() {

		$includes = $this->getJSFramework()->getBaseIncludes();
		$ext = 'mkforms';
		$server = t3lib_div::getIndpEnv('TYPO3_SITE_URL');
		// JSON stringifier
		// http://www.thomasfrank.se/downloadableJS/jsonStringify.js
		$pagePath = $server . t3lib_extMgm::siteRelPath($ext) . 'res/jsfwk/json/json.js';
		$serverPath = t3lib_extMgm::extPath($ext) . 'res/jsfwk/json/json.js';
		$includes[] = tx_mkforms_forms_PageInclude::createInstance($pagePath, $serverPath, 'tx_mkforms_json');
		
		foreach($includes As $include) {
			$tag = $include->isJS() ? '<script type="text/javascript" src="' . $this->getScriptPath($include->getPagePath()) . '"></script>' :
						'<link href="' . $this->getScriptPath($include->getPagePath(), 'css') . '" type="text/css" rel="stylesheet" />';
			$this->getForm()->additionalHeaderData(
				$tag,$include->getKey(), $include->isFirstPos(), $include->getBeforeKey(),$include->getAfterKey());
		}

		// tx_ameosformidable_prototype_fwk -> tx_mkforms_jsbase_fwk
		$this->additionalHeaderData(
			'<!-- consider base JS-Framework loaded after this line -->',
			'tx_mkforms_jsbase_fwk'
		);
	}

	/**
	 * Genau ein Aufruf im Form in _render()
	 *
	 */
	private function includeScriptaculous() {
		if(!($this->bLoadScriptaculous === TRUE && $this->mayLoadScriptaculous())) return;


		$includes = $this->getJSFramework()->getEffectIncludes();

		foreach($includes As $include) {
			$tag = $include->isJS() ? '<script type="text/javascript" src="' . $this->getScriptPath($include->getPagePath()) . '"></script>' :
						'<link href="' . $this->getScriptPath($include->getPagePath(), 'css') . '" type="text/css" rel="stylesheet" />';
			$this->getForm()->additionalHeaderData(
				$tag,$include->getKey(), $include->isFirstPos(), $include->getBeforeKey(),$include->getAfterKey());
		}

		// scriptaculous
//		$sPath = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . t3lib_extMgm::siteRelPath('mkforms') . 'res/jsfwk/scriptaculous/scriptaculous.js';
//		$sNextAfter = 'tx_mkforms_jsbase_fwk';
//
//		$this->getForm()->additionalHeaderData(
//			"<script type=\"text/javascript\" src=\"" . $sPath . "\"></script>",
//			'tx_ameosformidable_scriptaculous',
//			$bFirstPos = FALSE,$sBefore = FALSE,$sNextAfter);
//
//		$sPath = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . t3lib_extMgm::siteRelPath('mkforms') . 'res/jsfwk/scriptaculous/effects.js';
//
//		$this->oForm->additionalHeaderData(
//			"<script type=\"text/javascript\" src=\"" . $sPath . "\"></script>",
//			'tx_ameosformidable_scriptaculous_effects',
//			$bFirstPos = FALSE,$sBefore = FALSE,$sAfter = 'tx_ameosformidable_scriptaculous');

		$sNextAfter = 'tx_ameosformidable_scriptaculous_effects';

		if($this->bLoadScriptaculousDragDrop === TRUE) {
			$sPath = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . t3lib_extMgm::siteRelPath('mkforms') . 'res/jsfwk/scriptaculous/dragdrop.js';

			$this->oForm->additionalHeaderData(
				"<script type=\"text/javascript\" src=\"" . $this->getScriptPath($sPath) . "\"></script>",
				'tx_ameosformidable_scriptaculous_dragdrop',
				$bFirstPos = FALSE,
				$sBefore = FALSE,
				$sNextAfter
			);

			$sNextAfter = 'tx_ameosformidable_scriptaculous_dragdrop';
		}

		if($this->bLoadScriptaculousBuilder === TRUE) {
			$sPath = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . t3lib_extMgm::siteRelPath('mkforms') . 'res/jsfwk/scriptaculous/builder.js';

			$this->oForm->additionalHeaderData(
				"<script type=\"text/javascript\" src=\"" . $this->getScriptPath($sPath) . "\"></script>",
				'tx_ameosformidable_scriptaculous_builder',
				$bFirstPos = FALSE,
				$sBefore = FALSE,
				$sNextAfter
			);

			$sNextAfter = 'tx_ameosformidable_scriptaculous_builder';
		}

		$this->oForm->additionalHeaderData(
			'<!-- consider scriptaculous loaded after this line -->',
			'tx_ameosformidable_scriptaculous_fwk',
			$bFirstPos = FALSE,
			$sBefore = FALSE,
			$sNextAfter
		);
	}

	private function _includeJSFramework() {
		
//		wird in $this->getScriptPath() erledigt,
//		das ganze framework in einer js ist duch den wrapper (prototype, jquery) nich mehr möglich
//		if(false && $this->minified()) {
//			if($this->gziped()) {
//				$sPath = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . t3lib_extMgm::siteRelPath('mkforms') . 'res/jsfwk/minified/formidable.minified.js.php';
//			} else {
//				$sPath = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . t3lib_extMgm::siteRelPath('mkforms') . 'res/jsfwk/minified/formidable.minified.js';
//			}
//		} else {
			$sPath = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . t3lib_extMgm::siteRelPath('mkforms') . 'res/jsfwk/framework.js';
//		}
		
//		$tag = '<script type="text/javascript" src="' . $sPath . '"></script>';
		$tag = '<script type="text/javascript" src="' . $this->getScriptPath($sPath) . '"></script>';
		$this->oForm->additionalHeaderData(
			$tag,
			'tx_ameosformidable_jsframework',
			$bFirstPos = FALSE,
			$sBefore = FALSE,
			$sAfter = 'tx_mkforms_jsbase_fwk'
		);
		
	}

	/**
	 * Bindet das JS für die Initialisierung des Formidable-Objekts ein
	 *
	 */
	private function includeFormidablePath() {
		$sPath = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . t3lib_extmgm::siteRelPath('mkforms');
		$sScript =<<<JAVASCRIPT
	Formidable.initialize({path: '{$sPath}'});
JAVASCRIPT;
		$this->getForm()->attachInitTask($sScript,'Framework Formidable.path initialization');
	}

	/**
	 * Wird im Accordion, tx_mkforms_widgets_modalbox_Main aufgerufen
	 *
	 */
	public function loadScriptaculous() {
		$this->bLoadScriptaculous = TRUE;
	}

	/**
	 * Aufruf in tx_mkforms_widgets_jstree_Main
	 *
	 */
	public function loadScriptaculousDragDrop() {
		$this->loadScriptaculous();
		$this->bLoadScriptaculousDragDrop = TRUE;
	}

	/**
	 * Aufruf im Form und hier
	 *
	 */
	public function loadScriptaculousBuilder() {
		$this->bLoadScriptaculousBuilder = TRUE;
	}

	public function loadTooltip() {
		$this->loadScriptaculous();
		$this->loadScriptaculousBuilder();
		$this->bLoadtooltip = TRUE;
	}


	/**
	 * Genau ein Aufruf im Form::_render()
	 *
	 */
	private function includeTooltip() {
		if($this->bLoadtooltip === TRUE) {

			// tooltip css
			$sPath = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . t3lib_extMgm::siteRelPath('mkforms') . 'res/jsfwk/tooltip/tooltips.css';

			$this->oForm->additionalHeaderData(
				"<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $sPath . "\" />",
				"tx_ameosformidable_tooltip_css",
				$bFirstPos = FALSE,
				$sBefore = FALSE,
				'tx_ameosformidable_scriptaculous_fwk'
			);

			// tooltip js
			$sPath = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . t3lib_extMgm::siteRelPath('mkforms') . 'res/jsfwk/tooltip/tooltips.js';

			$this->oForm->additionalHeaderData(
				"<script type=\"text/javascript\" src=\"" . $sPath . "\"></script>",
				'tx_ameosformidable_tooltip_js',
				$bFirstPos = FALSE,$sBefore = FALSE,'tx_ameosformidable_tooltip_css');
		}
	}

	/**
	 * Die Methode entscheidet darüber, ob ein JS-Framework geladen wird
	 *
	 * @return boolean
	 */
	public function mayLoadJsFramework() {
		if(tx_mkforms_util_Div::getEnvExecMode() == 'BE') {
			return TRUE;
		}
		if($this->oForm->_defaultTrue('/meta/loadjsframework') !== TRUE) {
			return FALSE;
		}
		return intval($this->getForm()->getConfTS('loadJsFramework')) > 0;
	}
	/**
	 * Hier kann man per TS oder XML das Laden von Scriptaculous verhindern
	 *
	 * @return boolean
	 */
	private function mayLoadScriptaculous() {
		if($this->oForm->_defaultTrue('/meta/mayloadscriptaculous') !== TRUE) {
			return FALSE;
		}
		return intval($this->getForm()->getConfTS('mayLoadScriptaculous')) > 0;
	}
	/**
	 * Returns the js wrapper
	 *
	 * @return tx_mkforms_forms_IJSFramework
	 */
	private function getJSFramework() {
		if(!$this->jsWrapper) {
			$wrapperClass = $this->getForm()->getConfTS('jslib');
			$wrapperClass = $wrapperClass ? $wrapperClass : 'tx_mkforms_js_DefaultFramework';
			$this->jsWrapper = tx_rnbase::makeInstance($wrapperClass, $this->getForm()->getConfigurations(), $this->getForm()->getConfId().'jsframework.');
		}
		return $this->jsWrapper;
	}
	/**
	 * Liefert den Namen des aktuellen JS-Frameworks
	 * @return string
	 */
	public function getJSFrameworkId() {
		return $this->getJSFramework()->getId();
	}
	private function minified() {
		return intval($this->getForm()->getConfTS('minify.enabled')) > 0;
	}

	private function gziped() {
		return intval($this->getForm()->getConfTS('minify.gzip')) > 0;
	}

	/**
	 * Packt einen String in eine temporäre datei
	 *
	 * @param string $str Der JS oder CSS-Code
	 * @param string $ext "js" oder "css"
	 * @param string $sDesc Beschreibungstext für HTML-Kommentar
	 * @return string das HTML-Tag für die Integration in der Seite
	 */
	public function inline2TempFile($str, $ext, $sDesc="")	{

		$output = '';

		if(is_string($str)) {
			if($sDesc != '') {
				$sDesc = "\n\n<!-- MKFORMS: " . str_replace(array('<!--', '-->'), '', $sDesc) . ' -->';
			}

			// Create filename / tags:
			$script = '';
			switch($ext) {
				case 'js': {

					$script = 'typo3temp/javascript_'.substr(md5($str),0,10).'.js';
					$output = $sDesc . "\n" . '<script type="text/javascript" src="'.htmlspecialchars(t3lib_div::getIndpEnv('TYPO3_SITE_URL') . $script).'"></script>' . "\n\n";
					break;
				}
				case 'css': {
					$script = 'typo3temp/stylesheet_'.substr(md5($str),0,10).'.css';
					$output = $sDesc . "\n" . '<link rel="stylesheet" type="text/css" href="'.htmlspecialchars(t3lib_div::getIndpEnv('TYPO3_SITE_URL') . $script).'" />' . "\n\n";
					break;
				}
			}

			// Write file:
			if($script){
				if(!@is_file(PATH_site.$script)) {
					t3lib_div::writeFile(PATH_site.$script, $str);
				}
			}
		}

		return $output;
	}


	/**
	 * Fügt zusätzliche Headerdaten in den Response ein.
	 *
	 * @param	[type]		$sData: ...
	 * @param	[type]		$sKey: ...
	 * @param	[type]		$bFirstPos: ...
	 * @return	[type]		...
	 */
	public function additionalHeaderData($sData, $sKey = FALSE, $bFirstPos = FALSE, $sBefore = FALSE, $sAfter = FALSE) {
		if($sKey && !array_key_exists($sKey, $this->headerKeys)) $this->headerKeys[$sKey] = 1;
//t3lib_div::debug($sData, $sKey.' - tx_ameosformidable :: additionalHeaderData'); // TODO: remove me
		if(TYPO3_MODE === 'FE') {
			if($this->mayUseStandardHeaderInjection()) {
				$aHeaders =& $GLOBALS["TSFE"]->additionalHeaderData;
			} else {
				$aHeaders =& $this->aHeadersWhenInjectNonStandard;
			}
		}


		if(tx_mkforms_util_Div::getEnvExecMode() === "EID") {
			if($sKey === FALSE) {
				$this->aHeadersAjax[] = $sData;
			} else {
				$this->aHeadersAjax[$sKey] = $sData;
			}
//t3lib_div::debug($this->aHeadersAjax,'class.tx_ameosformidable.php : '); // TODO: remove me
		} else {
			if($sKey === FALSE) {
				if($bFirstPos === TRUE) {
					$aHeaders = array(rand() => $sData) + $aHeaders;
				}
				elseif($sBefore !== FALSE || $sAfter !== FALSE) {
					$bBefore = ($sBefore !== FALSE);
					$sLookFor = $bBefore ? $sBefore : $sAfter;
					$aHeaders = tx_mkforms_util_Div::array_insert($aHeaders,$sLookFor,array(count($aHeaders) => $sData),$bBefore);
				} else {
					$aHeaders[] = $sData;
				}
			} else {

				//	Was macht das hier!?
				// wir nutzen das tinymce nun auch in modalboxen!
//				if($sKey == 'ameosformidable_tx_rdttinymce') {
//					if(!in_array($sData, $aHeaders)) {
//						array_unshift($aHeaders, $sData);
//					}
//				} else {
					if($bFirstPos === TRUE) {
						$aHeaders = array($sKey => $sData) + $aHeaders;
					} elseif($sBefore !== FALSE || $sAfter !== FALSE) {
						$bBefore = ($sBefore !== FALSE);
						$sLookFor = $bBefore ? $sBefore : $sAfter;
						$aHeaders = tx_mkforms_util_Div::array_insert($aHeaders,$sLookFor,array($sKey => $sData),$bBefore);
					} else {
						$aHeaders[$sKey] = $sData;
					}
//				}
			}
		}
	}
	/**
	 * Liefert die Non-Standard-Headers.
	 * @return array
	 */
	private function getHeadersWhenInjectNonStandard() {
		return $this->aHeadersWhenInjectNonStandard;
	}
	/**
	 * Liefert zusätzliche Header, die bei Ajax-Calls eingebunden werden sollen.
	 * @return array
	 */
	public function getAjaxHeaders() {
		return $this->aHeadersAjax;
	}
	/**
	 * Wir wollen verhindern, das JS-Dateien bei Ajax-Calls doppelt geladen werden. Daher wird das Formidable-Objekt
	 * mit den Keys der JS-Libs gefüttert.
	 */
	public function setLoadedScripts() {
		$keys = array_keys($this->headerKeys);
		$script = '';
		foreach($keys As $key) {
			$script .= "Formidable.addScript('$key') \n";
		}
		$this->getForm()->attachInitTask($script,'Set loaded scripts','finalTask');
	}
	/**
	 * Das wird im Form in der Methode _render aufgerufen. Funktion noch unklar und das funktioniert wohl auch noch nicht...
	 */
	public function injectHeaders() {
		if(($sHeaderMarker = $this->getMarkerForHeaderInjection()) !== FALSE) {
			$GLOBALS["tx_ameosformidable"]["headerinjection"][] = array(
				"marker" => $sHeaderMarker,
				"headers" => $this->getHeadersWhenInjectNonStandard()
			);
		} elseif($this->manuallyInjectHeaders()) {
			$GLOBALS["tx_ameosformidable"]["headerinjection"][] = array(
				"manual" => TRUE,
				"headers" => $this->getHeadersWhenInjectNonStandard()
			);
		}
	}
	function mayUseStandardHeaderInjection() {
		return ($this->getMarkerForHeaderInjection() === FALSE) && ($this->manuallyInjectHeaders() === FALSE);
	}
	function getMarkerForHeaderInjection() {
		if(
			isset($GLOBALS["TSFE"]->tmpl->setup["config."]["tx_ameosformidable."]["injectHeadersInContentAtMarker"]) &&
			($sHeaderMarker = trim($GLOBALS["TSFE"]->tmpl->setup["config."]["tx_ameosformidable."]["injectHeadersInContentAtMarker"])) !== ""
		) {
			return $sHeaderMarker;
		}
		
		return FALSE;
	}
	function manuallyInjectHeaders() {
		return intval($this->getForm()->getConfTS('injectHeadersManually')) > 0;
		
		if(isset($GLOBALS["TSFE"]->tmpl->setup["config."]["tx_ameosformidable."]["injectHeadersManually"])) {
			// notnot returns real boolean
			return !!intval($GLOBALS["TSFE"]->tmpl->setup["config."]["tx_ameosformidable."]["injectHeadersManually"]);
		}
		
		return FALSE;
	}
	/**
	 * Prüft, ob für dieses Script eine minimierte Version vorligt
	 * und giebt diese entsprechend der konfiguration zurück.
	 *
	 * @param string $sPath
	 * @return srtring
	 */
	function getScriptPath($sPath, $sScriptErw = 'js'){
		$newPath = $sPath;
		$sScriptErw = '.'.$sScriptErw;
		// soll minimierte Version genutzt werden
		if($this->minified()) {
			$sSitePath = t3lib_div::getIndpEnv('TYPO3_SITE_URL').t3lib_extMgm::siteRelPath('mkforms');
			$sFile = substr( $sPath, strlen($sSitePath) , strrpos($sPath,$sScriptErw) - strlen($sSitePath) );
			// prüfen ob gzip genutzt werden soll, wenn ja auf datei prüfen.
			if($this->gziped() && file_exists( t3lib_extMgm::extPath('mkforms').$sFile.'.min'.$sScriptErw.'.php' )) {
				$sGZipPath = $sSitePath.$sFile.'.min'.$sScriptErw.'.php';
				$newPath = $sGZipPath;
			}
			// prüfen ob minimiertes js verfügbar ist.
			elseif(file_exists( t3lib_extMgm::extPath('mkforms').$sFile.'.min'.$sScriptErw )) {
				$sMinPath = $sSitePath.$sFile.'.min'.$sScriptErw;
				$newPath = $sMinPath;
			}
			// else, keine minimierte version gefunden, nutze standard Datei
			
		}
		return $newPath;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/util/class.tx_mkforms_js_Loader.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/util/class.tx_mkforms_js_Loader.php']);
}
?>