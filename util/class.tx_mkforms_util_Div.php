<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 René Nitzsche (dev@dmk-business.de)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

// @TODO: remove in 2 or 4 versions! it is only a localconf caching workaround
tx_rnbase::load('tx_mkforms_util_Constants');


/**
 * Some static util functions.
 *
 */
class tx_mkforms_util_Div {

	public static function isAbsServerPath($sPath) {
		$sServerRoot = t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT');
		return (substr($sPath, 0, strlen($sServerRoot)) === $sServerRoot);
	}

	public static function isAbsPath($sPath) {
		return $sPath{0} === '/';
	}

	public static function isAbsWebPath($sPath) {
		return (substr(strtolower($sPath), 0, 7) === 'http://') || (substr(strtolower($sPath), 0, 8) === 'https://');
	}

	/**
	 * Returns the current TYPO3 Mode: CLI, FE, BE or EID
	 *
	 * @return string
	 */
	public static function getEnvExecMode() {
		if (defined('TYPO3_cliMode') && TYPO3_cliMode) {
			return 'CLI';
		}elseif(TYPO3_MODE == 'BE'){
			return TYPO3_MODE;
		}
		return (is_null(t3lib_div::_GP('eID'))) ? 'FE' : 'EID';
	}

	/**
	 * Ein Array in Items für Formular-Widgets umwandeln
	 *
	 * @param	[type]		$aData: ...
	 * @return	[type]		...
	 */
	public static function arrayToRdtItems($aData, $sCaptionMap = FALSE, $sValueMap = FALSE) {

		$aItems = array();

		//wenn wir kein Array haben dann gibts nix zu tun
		//sonst knallts bei reset() in tests!!!
		if(!is_array($aData))
			return $aItems;

		reset($aData);

		if($sCaptionMap !== FALSE && $sValueMap !== FALSE) {
			while(list($sKey, ) = each($aData)) {
				$aItems[] = array(
					'value' => $aData[$sKey][$sValueMap],
					'caption' => $aData[$sKey][$sCaptionMap],
				);
			}
		} else {
			while(list($sValue, $sCaption) = each($aData)) {
				$aItems[] = array(
					'value' => $sValue,
					'caption' => $sCaption
				);
			}
		}

		reset($aItems);
		return $aItems;
	}

	/**
	 *  taken from http://drupal.org/node/66183
	 */
	public static function array_insert($arr1, $key, $arr2, $before = FALSE) {
		$arr1 = is_array($arr1) ? $arr1 : array();
		$index = array_search($key, array_keys($arr1), TRUE);
		if($index === FALSE){
			$index = count($arr1); // insert @ end of array if $key not found
		} else {
			if(!$before) {
				$index++;
			}
		}

		$end = array_splice($arr1, $index);
		return array_merge($arr1, $arr2, $end);
	}

	/**
	 * Lädt den Pfad zu einem Widget relativ zum Webroot.
	 * Die Signatur und Funktion hat sich im Vergleich zu Ameos geändert. Es wird immer der Name der Widgetklasse
	 * als Parameter erwartet!
	 *
	 * @param	string $$clazzname classname
	 * @return string
	 */
	function getExtRelPath($clazzname) {
		$infos = tx_rnbase::getClassInfo($clazzname);
		return tx_rnbase_util_Extensions::siteRelPath($infos['extkey']) . $infos['dir'];

		if(!is_array($mInfos)) {
			// should be object type

			if(isset($this)) {
				$aInfos = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ameos_formidable']['renderlets'][$sType];
				$aInfos = $this->_getInfosRenderletForType($mInfos);
			} else {
				$aInfos = tx_ameosformidable::_getInfosForType(
					$mInfos,
					$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ameos_formidable']['renderlets']
				);
			}
		} else {
			$aInfos = $mInfos;
		}

		if($aInfos['BASE'] === TRUE) {
			return tx_rnbase_util_Extensions::siteRelPath('mkforms') . 'api/base/' . $aInfos['EXTKEY'] . '/';
		} else {
			return tx_rnbase_util_Extensions::siteRelPath($aInfos['EXTKEY']);
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$mInfos: ...
	 * @return	[type]		...
	 */
	function getExtPath($clazzname) {
		$infos = tx_rnbase::getClassInfo($clazzname);
		return tx_rnbase_util_Extensions::extPath($infos['extkey']) . $infos['dir'];
	}

	/**
	 * FE-Umgebung initialisieren
	 *
	 * @param array $aConfig optional config array for TSFE
	 */
	public static function virtualizeFE($aConfig = FALSE, $feSetup = false) {

		if (!defined('PATH_tslib')) {
			if (@is_dir(PATH_site.TYPO3_mainDir.'sysext/cms/tslib/')) {
				define('PATH_tslib', PATH_site.TYPO3_mainDir.'sysext/cms/tslib/');
			} elseif (@is_dir(PATH_site.'tslib/')) {
				define('PATH_tslib', PATH_site.'tslib/');
			}
		}

		$GLOBALS['TT'] = new t3lib_timeTrack;
		$GLOBALS['CLIENT'] = t3lib_div::clientInfo();

		// ***********************************
		// Create $TSFE object (TSFE = TypoScript Front End)
		// Connecting to database
		// ***********************************
		$sExecMode = tx_mkforms_util_Div::getEnvExecMode();

		$GLOBALS['TSFE'] = tx_rnbase::makeInstance(
			'tslib_fe',
			$GLOBALS['TYPO3_CONF_VARS'],
			t3lib_div::_GP('id'),
			t3lib_div::_GP('type'),
			t3lib_div::_GP('no_cache'),
			t3lib_div::_GP('cHash'),
			t3lib_div::_GP('jumpurl'),
			t3lib_div::_GP('MP'),
			t3lib_div::_GP('RDCT')
		);

		/* @var $tsfe tslib_fe */
		$tsfe = &$GLOBALS['TSFE']; // only an alias for codecomplication

		// for typo3 6.2 the tca is required for determineId.
		tx_rnbase::load('tx_rnbase_util_TYPO3');
		if (tx_rnbase_util_TYPO3::isTYPO62OrHigher()) {
			tx_rnbase::load('tx_rnbase_util_TCA');
			tx_rnbase_util_TCA::loadTCA('pages'); // takes 0.0080 T3 6.2
		}

		$tsfe->connectToDB(); // takes 0.0000 T3 6.2
		$tsfe->initFEuser(); // takes 0.0400 T3 6.2
		$tsfe->determineId(); // takes 0.0240 T3 6.2
		$tsfe->getCompressedTCarray(); // takes 0.0000 T3 6.2
		$tsfe->initTemplate(); // takes 0.0030 T3 6.2
		$tsfe->getFromCache(); // takes 0.0040 T3 6.2

		if(!is_array($tsfe->config)) {
			$tsfe->config = array();
			$tsfe->forceTemplateParsing = TRUE;
		}

		if($aConfig === FALSE) {
			// Das benötigt 80-90% der Zeit für diese Methode in anspruch und sollte vermieden werden!
			$tsfe->getConfigArray();
		} else {
			$tsfe->config = $aConfig;
			if($feSetup) {
				$tsfe->tmpl->setup = !empty($tsfe->tmpl->setup) ? array_merge($tsfe->tmpl->setup, $feSetup) : $feSetup;
			}
		}

		$tsfe->convPOSTCharset();
		$tsfe->settingLanguage();
		$tsfe->settingLocale();

		$tsfe->cObj = tx_rnbase::makeInstance('tslib_cObj');
	}


	/**
	 * Stops Formidable and PHP execution : die() if some critical error appeared
	 *
	 * @param	string				$msg: the error message
	 * @param	tx_ameosformidable	$form
	 * @return	void
	 */
	public static function mayday($msg, $form = false) {

		// Wir nutzen die Mayday-Methode von rn_base.
		// Konfigurationen wie forceException4Mayday,
		// verboseMayday und dieOnMayday werden so mit beachtet.

		$aDebug = array('<h2>MKFORMS:</h2><p><strong>'.$msg.'</strong></p>');
		if($form) {
			$aDebug[] = "<span class='notice'><strong>XML: </strong> " . $form->_xmlPath . "</span><br />";
			$aDebug[] = "<span class='notice'><strong>MKFORMS Version: </strong>v" . self::getVersion() . "</span><br />";
			$aDebug[] = "<span class='notice'><strong>Total exec. time: </strong>" . round(t3lib_div::milliseconds() - $form->start_tstamp, 4) / 1000 ." sec</span><br />";
		}
		$aDebug[] = "<br />";

		$aDebug[] = '<span class="notice"><strong>debug trail: </strong></span><ol>';

		tx_rnbase::load('tx_rnbase_util_Debug');
		foreach(t3lib_div::trimExplode('//',tx_rnbase_util_Debug::getDebugTrail()) as $bt) {
			$aDebug[] = "\t<li>".$bt."</li>";
		}
		$aDebug[] = "</ol>";

		$sDebug = implode('', $aDebug);

		// email senden
		$addr = tx_rnbase_configurations::getExtensionCfgValue('rn_base', 'sendEmailOnException');
		if($addr) {
			$exception = tx_rnbase::makeInstance('tx_mkforms_exception_Mayday', $msg, -1, $sDebug);
			tx_rnbase_util_Misc::sendErrorMail($addr, $form ? get_class($form).' FormId:'.$form->getFormId() : get_class($this), $exception);
		}

		// beim ajaxcall nur die meldung ausgeben und fertig!
		if(tx_mkforms_util_Div::getEnvExecMode() === 'EID') {
			die("Formidable::Mayday\n\n" . trim(strip_tags($msg)));
		}

		tx_rnbase_util_Misc::mayday($sDebug, 'mkforms');
		return;

		$aTrace		= debug_backtrace();
		$aLocation	= array_shift($aTrace);
		$aTrace1	= array_shift($aTrace);
		$aTrace2	= array_shift($aTrace);
		$aTrace3	= array_shift($aTrace);
		$aTrace4	= array_shift($aTrace);

		$aDebug[] = "<h2 id='backtracetitle'>Call stack</h2>";
		$aDebug[] = "<div class='backtrace'>";
		$aDebug[] = "<span class='notice'><b>Call 0: </b>" . str_replace(PATH_site, "/", $aLocation["file"]) . ":" . $aLocation["line"]  . " | <b>" . $aTrace1["class"] . $aTrace1["type"] . $aTrace1["function"] . "</b></span><br/>With parameters: " . (!empty($aTrace1["args"]) ? self::viewMixed($aTrace1["args"]) : " no parameters");
		$aDebug[] = "<hr/>";
		$aDebug[] = "<span class='notice'><b>Call -1: </b>" . str_replace(PATH_site, "/", $aTrace1["file"]) . ":" . $aTrace1["line"]  . " | <b>" . $aTrace2["class"] . $aTrace2["type"] . $aTrace2["function"] . "</b></span><br />With parameters: " . (!empty($aTrace2["args"]) ? self::viewMixed($aTrace2["args"]) : " no parameters");
		$aDebug[] = "<hr/>";
		$aDebug[] = "<span class='notice'><b>Call -2: </b>" . str_replace(PATH_site, "/", $aTrace2["file"]) . ":" . $aTrace2["line"]  . " | <b>" . $aTrace3["class"] . $aTrace3["type"] . $aTrace3["function"] . "</b></span><br />With parameters: " . (!empty($aTrace3["args"]) ? self::viewMixed($aTrace3["args"]) : " no parameters");
		$aDebug[] = "<hr/>";
		$aDebug[] = "<span class='notice'><b>Call -3: </b>" . str_replace(PATH_site, "/", $aTrace3["file"]) . ":" . $aTrace3["line"]  . " | <b>" . $aTrace4["class"] . $aTrace4["type"] . $aTrace4["function"] . "</b></span><br />With parameters: " . (!empty($aTrace4["args"]) ? self::viewMixed($aTrace4["args"]) : " no parameters");
		$aDebug[] = "<hr/>";

		tx_rnbase::load('tx_rnbase_util_Debug');
		$aDebug[] = "<span class='notice'>" . tx_rnbase_util_Debug::getDebugTrail() . "</span>";
		$aDebug[] = "<hr/>";

		$aDebug[] = "</div>";

		$aDebug[] = "<br/>";

		$sContent =	"<h1 id='title'>Formidable::Mayday</h1>";
		$sContent .= "<div id='errormessage'>" . $msg . "</div>";
		$sContent .= "<hr />";
		$sContent .= implode("", $aDebug);

		$sPage =<<<MAYDAYPAGE
<!DOCTYPE html
	PUBLIC "-//W3C//DTD XHTML 1.1//EN"
	"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<title>Formidable::Mayday</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<meta name="robots" content="noindex, nofollow" />
		<style type="text/css">

			#title {
				color: red;
				font-family: Verdana;
			}

			#errormessage {
				border: 2px solid red;
				padding: 10px;
				color: white;
				background-color: red;
				font-family: Verdana;
				font-size: 12px;
			}

			.notice {
				font-family: Verdana;
				font-size: 9px;
				font-style: italic;
			}

			#backtracetitle {
			}

			.backtrace {
				background-color: #FFFFCC;
			}

			HR {
				border: 1px solid silver;
			}
		</style>
	</head>
	<body>
		{$sContent}
	</body>
</html>

MAYDAYPAGE;

		//@todo
		//müssen wir hier wirklich die() verwenden? In Tests werden fehler
		//somit verschluckt.
		// mw: wie wäre es mit $form->isTestMode()?
		die($sPage);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$mMixed: ...
	 * @param	[type]		$bRecursive: ...
	 * @param	[type]		$iLevel: ...
	 * @return	[type]		...
	 */
	public static function viewMixed($mMixed, $bRecursive = TRUE, $iLevel=0) {

		$sStyle = 'font-family: Verdana; font-size: 9px;';
		$sStyleBlack = $sStyle . 'color: black;';
		$sStyleRed = $sStyle . 'color: red;';
		$sStyleGreen = $sStyle . 'color: green;';

		$aBgColors = array(
			'FFFFFF', 'F8F8F8', 'EEEEEE', 'E7E7E7', 'DDDDDD', 'D7D7D7', 'CCCCCC', 'C6C6C6', 'BBBBBB', 'B6B6B6', 'AAAAAA', 'A5A5A5', '999999', '949494', '888888', '848484', '777777', '737373'
		);

		if(is_array($mMixed)) {

			$result="<table border=1 style='border: 1px solid silver' cellpadding=1 cellspacing=0 bgcolor='#" . $aBgColors[$iLevel] . "'>";

			if(!count($mMixed)) {
				$result.= "<tr><td><span style='" . $sStyleBlack . "'><b>".htmlspecialchars("EMPTY!")."</b></span></td></tr>";
			} else {
				while(list($key, $val)=each($mMixed)) {

					$result.= "<tr><td valign='top'><span style='" . $sStyleBlack . "'>".htmlspecialchars((string)$key)."</span></td><td>";

					if(is_array($val))	{
						$result.=self::viewMixed($val, $bRecursive, $iLevel + 1);
					} else {
						$result.= "<span style='" . $sStyleRed . "'>".self::viewMixed($val, $bRecursive, $iLevel + 1)."<br /></span>";
					}

					$result.= "</td></tr>";
				}
			}

			$result.= "</table>";

		} elseif(is_resource($mMixed)) {
			$result = "<span style='" . $sStyleGreen . "'>RESOURCE: </span>" . $mMixed;
		} elseif(is_object($mMixed)) {
			if($bRecursive) {
				$result = "<span style='" . $sStyleGreen . "'>OBJECT (" . get_class($mMixed) .") : </span>" . self::viewMixed(get_object_vars($mMixed), FALSE, $iLevel + 1);
			} else {
				$result = "<span style='" . $sStyleGreen . "'>OBJECT (" . get_class($mMixed) .") : !RECURSION STOPPED!</span>";
			}
		} elseif(is_bool($mMixed)) {
			$result = "<span style='" . $sStyleGreen . "'>BOOLEAN: </span>" . ($mMixed ? "TRUE" : "FALSE");
		} elseif(is_string($mMixed)) {
			if(empty($mMixed)) {
				$result = "<span style='" . $sStyleGreen . "'>STRING(0)</span>";
			} else {
				$result = "<span style='" . $sStyleGreen . "'>STRING(" . strlen($mMixed) . "): </span>" . nl2br(htmlspecialchars((string)$mMixed));
			}
		} elseif(is_null($mMixed)) {
			$result = "<span style='" . $sStyleGreen . "'>!NULL!</span>";
		} elseif(is_integer($mMixed)) {
			$result = "<span style='" . $sStyleGreen . "'>INTEGER: </span>" . $mMixed;
		} else {
			$result = "<span style='" . $sStyleGreen . "'>MIXED: </span>" . nl2br(htmlspecialchars(strVal($mMixed)));
		}

		return $result;
	}

	public static function isDebugIP() {
		return (t3lib_div::cmpIP(t3lib_div::getIndpEnv('REMOTE_ADDR'), $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']));
	}

	/**
	 * Debug a value in Ajax-Mode
	 *
	 * @param	mixed $mVar
	 * @param	string $sTitle
	 */
	public static function debug4ajax($mVar, $sTitle=null) {
		echo '/*';
		if(!is_null($sTitle)) echo $sTitle.' | ';
		print_r($mVar);
		echo '*/'."\r\n";
	}

	/**
	 * Ausgabe des Backtrace in Ajax
	 *
	 */
	public static function debugBT4ajax() {
		$aTrace		= debug_backtrace();
		$aLocation	= array_shift($aTrace);
		$aTrace1	= array_shift($aTrace);
		$aTrace2	= array_shift($aTrace);
		$aTrace3	= array_shift($aTrace);
		$aTrace4	= array_shift($aTrace);
		$aTrace5	= array_shift($aTrace);
		$aTrace6	= array_shift($aTrace);
		$aTrace7	= array_shift($aTrace);
		$aTrace8	= array_shift($aTrace);
		$aTrace9	= array_shift($aTrace);
		$aTrace0	= array_shift($aTrace);

		$aDebug = array();
		$aDebug[] = "Call  0: " . str_replace(PATH_site, "/", $aLocation['file']) . ':' . $aLocation['line']  . ' | ' . $aTrace1['class'] . $aTrace1['type'] . $aTrace1['function'];
		$aDebug[] = "Call -1: " . str_replace(PATH_site, "/", $aTrace1['file']) . ':' . $aTrace1['line']  . ' | ' . $aTrace2['class'] . $aTrace2['type'] . $aTrace2['function'];
		$aDebug[] = "Call -2: " . str_replace(PATH_site, "/", $aTrace2['file']) . ':' . $aTrace2['line']  . ' | ' . $aTrace3['class'] . $aTrace3['type'] . $aTrace3['function'];
		$aDebug[] = "Call -3: " . str_replace(PATH_site, "/", $aTrace3['file']) . ':' . $aTrace3['line']  . ' | ' . $aTrace4['class'] . $aTrace4['type'] . $aTrace4['function'];
		$aDebug[] = "Call -4: " . str_replace(PATH_site, "/", $aTrace4['file']) . ':' . $aTrace4['line']  . ' | ' . $aTrace5['class'] . $aTrace5['type'] . $aTrace5['function'];

		$aDebug[] = "Call -5: " . str_replace(PATH_site, "/", $aTrace5['file']) . ':' . $aTrace5['line']  . ' | ' . $aTrace6['class'] . $aTrace6['type'] . $aTrace6['function'];
		$aDebug[] = "Call -6: " . str_replace(PATH_site, "/", $aTrace6['file']) . ':' . $aTrace6['line']  . ' | ' . $aTrace7['class'] . $aTrace7['type'] . $aTrace7['function'];
		$aDebug[] = "Call -7: " . str_replace(PATH_site, "/", $aTrace7['file']) . ':' . $aTrace7['line']  . ' | ' . $aTrace8['class'] . $aTrace8['type'] . $aTrace8['function'];
		$aDebug[] = "Call -8: " . str_replace(PATH_site, "/", $aTrace8['file']) . ':' . $aTrace8['line']  . ' | ' . $aTrace9['class'] . $aTrace9['type'] . $aTrace9['function'];
		$aDebug[] = "Call -9: " . str_replace(PATH_site, "/", $aTrace9['file']) . ':' . $aTrace9['line']  . ' | ' . $aTrace0['class'] . $aTrace0['type'] . $aTrace0['function'];
		self::debug4ajax($aDebug);
	}

	/**
	 * Internal debug function
	 * Calls the TYPO3 debug function if the XML conf sets /formidable/meta/debug/ to TRUE
	 *
	 * @param	mixed 				$variable: the variable to dump
	 * @param	string 				$name: title of this debug section
	 * @param	tx_ameosformidable 	$form
	 * @param	boolean 			$bAnalyze: detailierter debug?
	 * @return	void
	 */
	public static function debug($variable, $name, $form, $bAnalyze = TRUE) {

		if($form->getConfig()->isDebug() || $form->bDebug) {

			$aTrace		= debug_backtrace();
			$aLocation	= array_shift($aTrace);
			$aTrace1	= array_shift($aTrace);
			$aTrace2	= array_shift($aTrace);
			$aTrace3	= array_shift($aTrace);
			$aTrace4	= array_shift($aTrace);

			$numcall = sizeof($form->aDebug) + 1;

			$aDebug = array();
			$aDebug[] = "<p align = 'right'><a href = '#" . $form->formid . "formidable_debugtop' target = '_self'>^top^</a></p>";
			$aDebug[] = "<a name = '" . $form->formid . "formidable_call" . $numcall . "' />";
			$aDebug[] = "<a href = '#" . $form->formid . "formidable_call" . ($numcall - 1) . "'>&lt;&lt; prev</a> / <a href = '#" . $form->formid . "formidable_call" . ($numcall + 1) . "'>next &gt;&gt;</a><br>";
			$aDebug[] = "<strong>#" . $numcall ." - " . $name . "</strong>";
			$aDebug[] = "<br/>";
			$aDebug[] = "<span style='font-family: verdana;font-size: 9px; font-style: italic;'><b>(Total exec. time: </b>" . round(t3lib_div::milliseconds() - $form->start_tstamp, 4) / 1000 ." sec)</span>";
			$aDebug[] = "<br/>";


			$aDebug[] = "<a href='javascript:void(Formidable.f(\"" . $form->formid . "\").toggleBacktrace(" . $numcall . "))'>Toggle details</a><br>";
			$aDebug[] = "<div id='" . $form->formid . "_formidable_call" . $numcall . "_backtrace' style='display: none; background-color: #FFFFCC' >";

			if(!$form->getConfig()->isDebugLight()) {
				$aDebug[] = "<span style='font-family: verdana;font-size: 9px; font-style: italic;'><b>Call 0: </b>" . str_replace(PATH_site, "/", $aLocation["file"]) . ":" . $aLocation["line"]  . " | <b>" . $aTrace1["class"] . $aTrace1["type"] . $aTrace1["function"] . "</b></span><br>" . self::viewMixed($aTrace1["args"]);
				$aDebug[] = "<hr/>";
				$aDebug[] = "<span style='font-family: verdana;font-size: 9px; font-style: italic;'><b>Call -1: </b>" . str_replace(PATH_site, "/", $aTrace1["file"]) . ":" . $aTrace1["line"]  . " | <b>" . $aTrace2["class"] . $aTrace2["type"] . $aTrace2["function"] . "</b></span><br>" . self::viewMixed($aTrace2["args"]);
				$aDebug[] = "<hr/>";
				$aDebug[] = "<span style='font-family: verdana;font-size: 9px; font-style: italic;'><b>Call -2: </b>" . str_replace(PATH_site, "/", $aTrace2["file"]) . ":" . $aTrace2["line"]  . " | <b>" . $aTrace3["class"] . $aTrace3["type"] . $aTrace3["function"] . "</b></span><br>" . self::viewMixed($aTrace3["args"]);
				$aDebug[] = "<hr/>";
				$aDebug[] = "<span style='font-family: verdana;font-size: 9px; font-style: italic;'><b>Call -3: </b>" . str_replace(PATH_site, "/", $aTrace3["file"]) . ":" . $aTrace3["line"]  . " | <b>" . $aTrace4["class"] . $aTrace4["type"] . $aTrace4["function"] . "</b></span><br>" . self::viewMixed($aTrace4["args"]);
				$aDebug[] = "<hr/>";
			}

			{
				if(is_string($variable)) {
					$aDebug[] = $variable;
				} else {
					if($bAnalyze) {
						$aDebug[] = self::viewMixed($variable);
					} else {
						tx_rnbase::load('tx_rnbase_util_Debug');
						$aDebug[] = tx_rnbase_util_Debug::viewArray($variable);
					}
				}
			}

			$aDebug[] = "</div>";

			$aDebug[] = "<br/>";

			// @TODO wo wird das ausgegeben!?
			$form->aDebug[] = implode("", $aDebug);
		}
	}
	public static function smartMayday_XmlFile($sPath, $sMessage = FALSE) {

		$sVersion = self::getVersion();
		$sXml =<<<XMLFILE
<?xml version="1.0" encoding="UTF-8" standalone="yes"
<mkforms version="{$sVersion}">

	<meta>
		<name>New FML file</name>
		<form formid="myform"/>
	</meta>

	<elements>
		<renderlet:TEXT name="mytxt" label="Some text field"/>
	</elements>

</mkforms>
XMLFILE;

		if($sMessage === FALSE) {
			$sMessage = "FORMIDABLE CORE - The given FML file path (<b>" . $sPath . "</b>) does not exist";
		}

		$sXml = htmlspecialchars($sXml);
		$sMayday =<<<ERRORMESSAGE

	<div>{$sMessage}</div>
	<hr />
	<div>This basic FML might be useful: </div>
	<br />
	<div style='color: black; background-color: #e6e6fa; border: 2px dashed #4682b4; font-family: Courier; padding-left: 20px;'>
		<br />
<pre>{$sXml}</pre>
		<br /><br />
	</div>

ERRORMESSAGE;

		self::mayday($sMayday);
	}

	public static function smartMayday_CBJavascript($sPath, $sClassName, $sMessage = FALSE) {

		$sJs =<<<XMLFILE
Formidable.Classes.{$sClassName} = Formidable.Classes.CodeBehindClass.extend({

	init: function() {
		// your init code here
	},
	doSomething: function() {
		// your implementation here
	}

});
XMLFILE;

		if($sMessage === FALSE) {
			$sMessage = "FORMIDABLE CORE - The given javascript CodeBehind file path (<b>" . $sPath . "</b>) does not exist";
		}

		$sJs = htmlspecialchars($sJs);
		$sMayday =<<<ERRORMESSAGE

	<div>{$sMessage}</div>
	<hr />
	<div>This basic JS codebehind might be useful</div>
	<br />
	<div style='color: black; background-color: #e6e6fa; border: 2px dashed #4682b4; font-family: Courier; padding-left: 20px;'>
		<br />
<pre>{$sJs}</pre>
		<br /><br />
	</div>

ERRORMESSAGE;

		self::mayday($sMayday);
	}

	/**
	 * Returns the current version of formidable running
	 *
	 * @return	string		current mkforms version number
	 */
	public static function getVersion()
	{
		static $version = NULL;
		if ($version === NULL) {
			$version = $GLOBALS['EM_CONF']['mkforms']['version'];
			if (tx_rnbase_util_TYPO3::isTYPO60OrHigher()) {
				$version = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionVersion('mkforms');
			}
		}

		return $version;
	}

	public static function getVersionInt()
	{
		static $version = NULL;
		if ($version === NULL) {
			tx_rnbase::load('tx_rnbase_util_TYPO3');
			$version = tx_rnbase_util_TYPO3::convertVersionNumberToInteger(self::getVersion());
		}

		return $version;
	}

	/**
	 * Returns eID for Ajax calls
	 *
	 * @return string
	 */
	public static function getAjaxEId() {
		return 'tx_mkforms_ajax';
	}
	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sPath: ...
	 * @return	[type]		...
	 */
	public static function toWebPath($sPath) {
		if(t3lib_div::isFirstPartOfStr(strtolower($sPath), 'http://') || t3lib_div::isFirstPartOfStr(strtolower($sPath), 'https://')) {
			return $sPath;
		}
		return self::removeEndingSlash(t3lib_div::getIndpEnv('TYPO3_SITE_URL')) . '/' . self::removeStartingSlash(self::toRelPath($sPath));
	}

	/**
	 * Converts an absolute or EXT path to a relative path plus a leading slash.
	 *
	 * @param	string		$sPath: the path to convert, must be either an
	 * 						absolute path or a path starting with 'EXT:'
	 * @return	string		$sPath converted to a relative path plus a leading
	 * 						slash
	 */
	function toRelPath($sPath) {
		if (substr($sPath, 0, 4) === 'EXT:') {
			$sPath = t3lib_div::getFileAbsFileName($sPath);
		}
		$sPath = str_replace(PATH_site, '', $sPath);
		if ($sPath{0} != '/') {
			$sPath = '/' . $sPath;
		}
		return $sPath;
	}

	/**
	 * Make an absolute server path
	 *
	 * @param	string $sPath: ...
	 * @return string
	 */
	public static function toServerPath($sPath) {
		// removes the leading slash so the path _really_ is relative
		$sPath = self::removeStartingSlash(self::toRelPath($sPath));

		if (file_exists($sPath) && is_dir($sPath) && ($sPath{(strlen($sPath) - 1)} !== '/')) {
			$sPath .= '/';
		}
		return self::removeEndingSlash(PATH_site) . '/' . $sPath;
	}

	public static function removeStartingSlash($sPath) {
		return ($sPath{0} === '/') ? substr($sPath, 1) : $sPath;
	}

	public static function removeEndingSlash($sPath) {
		return preg_replace('|/$|','', $sPath);
	}

	public static function trimSlashes($sPath) {
		return self::removeStartingSlash(self::removeEndingSlash($sPath));
	}


	/**
	 * Binary-reads a file
	 *
	 * @param	string		$sPath: absolute server path to file
	 * @return	string		file contents
	 */
	function fileReadBin($sPath) {
		$sData = '';
		$rFile = fopen($sPath, "rb");
		while(!feof($rFile)) {
			$sData .= fread($rFile, 1024);
		}
		fclose($rFile);

		return $sData;
	}

	/**
	 * Binary-writes a file
	 *
	 * @param	string		$sPath: absolute server path to file
	 * @param	string		$sData: file contents
	 * @param	boolean		$bUTF8: add UTF8-BOM or not ?
	 * @return	void
	 */
	function fileWriteBin($sPath, $sData, $bUTF8 = TRUE) {
		$rFile=fopen($sPath, "wb");
		if($bUTF8 === TRUE) {
			fputs($rFile, "\xEF\xBB\xBF" . $sData);
		} else {
			fputs($rFile, $sData);
		}
		fclose($rFile);
	}

	public static function mkdirDeep($destination,$deepDir) {
		$allParts = t3lib_div::trimExplode('/',$deepDir,1);
		$root = '';
		foreach($allParts as $part)	{
			$root.= $part.'/';
			if (!is_dir($destination.$root))	{
				t3lib_div::mkdir($destination.$root);
				if (!@is_dir($destination.$root))	{
					return 'Error: The directory "'.$destination.$root.'" could not be created...';
				}
			}
		}
	}

	public static function mkdirDeepAbs($deepDir) {
		return self::mkdirDeep('/', $deepDir);
	}
	/**
	 * Entfernt aus dem Array alle Keys, die mit einem bestimmten Prefix beginnen.
	 * Achtung: Das �bergebene Array wird ver�ndert!
	 *
	 * @param array $params
	 * @param string $prefix
	 * @return array
	 */
	public static function removeKeysWithPrefix(&$params, $prefix) {
		$keys = array_keys($params);
		foreach($keys As $key) {
			if(strpos($key,$prefix) === 0) {
				unset($params[$key]);
			}
		}
		return $params;
	}
	/**
	 * Sucht in dem Array nach allen Keys, die mit einem bestimmten Prefix beginnen
	 *
	 * @param array $params
	 * @param string $prefix
	 * @return array ein Array mit den gefundenen Keys
	 */
	public static function findKeysWithPrefix($params, $prefix) {
		$ret = array();
		$keys = array_keys($params);
		foreach($keys As $key) {
			if(strpos($key,$prefix) === 0) {
				$ret[] = $key;
			}
		}
		return $ret;
	}

	/**
	 * Removes dots in typoscript configuration arrays
	 * TODO: Die Methode ist eventuell obsolete, wenn die Configurations für den TS-Zugriff verwendet wird.
	 *
	 * @param	array		$aData: typoscript conf
	 * @param	array		$aTemp: internal use
	 * @param	string		$sParentKey: key in conf currently processed
	 * @return	array		conf without TS dotted notation
	 */
	public static function removeDots($aData, $aTemp = FALSE, $sParentKey = FALSE) {

		if($aTemp === FALSE) {
			$aTemp = array();
		}

		while(list($key, $val) = each($aData)) {
			if(is_array($val)) {
				if($sParentKey === 'userobj.' && $key === 'cobj.') {
					$aTemp['cobj'] = $aData['cobj'];
					$aTemp['cobj.'] = $aData['cobj.'];
				} else {
					$aTemp[substr($key, 0, -1)] = self::removeDots($val, FALSE, $key);
				}
			} else {
				$aTemp[$key] = $val;
			}
		}

		return $aTemp;
	}

	/**
	 * Add dots to an array to have a typoscript-like structure
	 *
	 * @param	array		$aData: plain conf
	 * @param	array		$aTemp
	 * @return	array		typoscript dotted conf
	 */
	public static function addDots($aData, $aTemp = FALSE) {

		$aTemp = ($aTemp === FALSE) ? array() : $aTemp;
		while(list($key, $val) = each($aData)) {
			if(is_array($val)) {
				$aTemp[$key.'.'] = self::addDots($val);
			}
			else {
				$aTemp[$key] = $val;
			}
		}
		return $aTemp;
	}

	public static function getRelExtensionPath($filename) {
		if(!self::isExtensionPath($filename)) return $filename;

		list($extKey,$local) = explode('/',substr($filename,4),2);
		$filename='';
		if (strcmp($extKey,'') && tx_rnbase_util_Extensions::isLoaded($extKey) && strcmp($local,''))	{
			$filename = tx_rnbase_util_Extensions::siteRelPath($extKey).$local;
		}
		return $filename;
	}

	public static function isExtensionPath($filename) {
		return (substr($filename,0,4)=='EXT:');
	}

	/**
	 * Erstellt ein Parameter-Array. Der Parameter ist dabei entweder ein String oder ein Array
	 * params="name::value"
	 * <params><param name="this()" value="this" /></params>
	 * Aufpassen: es gibt verschiedene Arten von Parameteraufrufen. Bei den userObjects läuft das anders!
	 *
	 * @param mixed $mParams String oder Array
	 * @return Array
	 */
	public static function extractParams($mParams) {
		$aParamsCollection = array();
		if($mParams === FALSE) return $aParamsCollection;

		if(is_string($mParams)) {
			// Das ist der Normalfall. Die Parameter als String
			$aTemp = t3lib_div::trimExplode(',', $mParams);
			foreach($aTemp As $sParam) {
				$paramArr = t3lib_div::trimExplode('::', $mParams);
				$aParamsCollection[$paramArr[0]] = (count($paramArr) > 0) ? $paramArr[1] : '';
			}
		} else {
			foreach($mParams As $mParam) {
				$aParamsCollection[$mParam['name']] = $mParam['value'];
			}
		}
		return $aParamsCollection;
	}

	/**
	 * Durchläuft ein Array rekursiv und wendet auf jedes Element
	 * urlDecodeByReference an
	 * @param $aArray
	 */
	public static function urlDecodeRecursive(&$aArray){
		if(is_array($aArray))//nur wenn ein array vorliegt
			array_walk_recursive( $aArray , array('tx_mkforms_util_Div','urlDecodeByReference'));
	}

	/**
	 * Wendet urldecode auf das gegebene Element an. Dabei ist
	 * das Element im gegensatz zum original eine referenz
	 * womit die Funktion auch als callback verwendet werden kann
	 * @param mixed $item
	 * @param mixed $key
	 */
	public static function urlDecodeByReference(&$item, $key = null) {
		$item = urldecode($item);
	}


	/**
	 * Wandelt einen String anhand eines Trennzeichens in CamelCase um.
	 * @param 	string 	$sString
	 * @param 	string 	$sDelimiter
	 * @return string
	 */
	public static function toCamelCase($sString, $sDelimiter='_', $bLcFirst = false){
		//$sCamelCase = implode('', array_map('ucfirst', explode($sDelimiter, $sString)));
		// das ist schneller als die array_map methode!
		$sCamelCase = '';
		foreach(explode($sDelimiter, $sString) as $sPart) {
			$sCamelCase .= ucfirst($sPart);
		}
		// lcfirst gibt es erst ab php 5.3!
		if($bLcFirst) $sCamelCase{0} = strtolower($sCamelCase{0});
		return $sCamelCase;
	}

	/**
	 * Wir lassen als Dateinamen nur Buchstaben, Zahlen,
	 * Bindestrich, Unterstrich und Punkt zu.
	 * Umlaute und Sonderzeichen werden versucht in lesbare Buchstaben zu parsen.
	 * Nicht zulässige Zeichen werden in einen Unterstrich umgewandelt.
	 * Der Dateiname wird immer in Kleinbuchstaben umgewandelt!
	 *
	 * @param string $name
	 * @return string
	 */
	public static function cleanupFileName($name) {
		if (empty($name) || trim($name, '.') === '') {
			return '';
		}
		$cleaned = $name;
		if (function_exists('iconv')) {
			tx_rnbase::load('tx_rnbase_util_Strings');
			$charset = tx_rnbase_util_Strings::isUtf8String($cleaned)
				? 'UTF-8' : 'ISO-8859-1';
			$oldLocal = setlocale(LC_ALL, 0);
			setlocale(LC_ALL, 'de_DE@euro', 'de_DE', 'deu_deu', 'de', 'ge');
			$cleaned = iconv($charset, 'ASCII//TRANSLIT', $cleaned);
			setlocale(LC_ALL, $oldLocal);#
			// ignore iconf, if it fails.
			$cleaned = $cleaned ? $cleaned : $name;
		}
		// only alphanumeric dot, minus and underscore aviable
		$cleaned = preg_replace('/[^A-Za-z0-9-_.]/', '_', $cleaned);
		// make lowercase and remove all enclosing dots !
		$cleaned = trim(strtolower($cleaned), '.');

		// the folowing code checks for a given filename/extension and removes all dots of filename
		// file.name.dat > file_name.jpg
		/* @TODO: is there any necessity to remove the dots?
		$dotExploded = explode('.', $cleaned);
		if (count($dotExploded) > 1) {
			$fExt = array_pop($dotExploded);
			// remove all dots
			$fName = implode('_', $dotExploded);
			$cleaned = (empty($fName) ? 'file' : $fName) . (empty($fExt) ? '' : '.' . $fExt);
		}
		*/
		// make shure, we return always a filename!
		return empty($cleaned) ? 'file.dat' : $cleaned;
	}

	/**
	 * Liefert bestimmte Pfade
	 */
	public static function getSetupByKeys($tsSetup, $keys){
		arsort($keys);
		$tsArray = array();
		if(!empty($tsSetup) && is_array($keys)) {
			foreach($keys as $key => $value) {
				if(substr($key, -1) === '.') {
					$tsArray[$key] = self::getSetupByKeys($tsSetup[$key], $value);
				} elseif($value) {
					if(array_key_exists($key, $tsSetup)) {
						$tsArray[$key] = $tsSetup[$key];
					}
					if(array_key_exists($key.'.', $tsSetup)) {
						$tsArray[$key.'.'] = $tsSetup[$key.'.'];
					}
				}
			}
		}
		return $tsArray;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/util/class.tx_mkforms_util_Div.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/util/class.tx_mkforms_util_Div.php']);
}
