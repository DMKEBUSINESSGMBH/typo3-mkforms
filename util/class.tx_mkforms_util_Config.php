<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 René Nitzsche (nitzsche@das-medienkombinat.de)
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


/**
 * Die Klasse ist für die Verarbeitung der XML-Formulardatei verantwortlich.
 * Der Zugriff auf das Form sollte nur reduziert geschehen. Derzeit wird über das Form das Runnable ermittelt.
 *
 */
class tx_mkforms_util_Config {
	private $debug = -99;
	private $config;
	private function __construct($form) {
		$this->form = $form;
	}

	public function navConf($path, $aConf = -1, $sSep = '/') {
		return $this->get($path, $aConf, $sSep);
	}

	private function explodePath($path, $sSep) {
		if($path{0} === $sSep) {
			$path = substr($path, 1);
		}
		$iLen = strlen($path);
		if($path{$iLen - 1} === $sSep) {
			$path = substr($path, 0, $iLen - 1);
		}

		return explode($sSep, $path);
	}
	/**
	 * Liefert einen Wert aus der Config
	 *
	 * @param string $path
	 * @param array $aConf
	 * @param string $sSep
	 * @return mixed
	 */
	public function get($path, $aConf = -1, $sSep = '/') {

		$curZone = ($aConf === -1 || !is_array($aConf)) ? $this->config : $aConf;
		reset($curZone);

		if($path === $sSep) {
			return $curZone;
		}
		$aPath = $this->explodePath($path, $sSep);

		$iSize = sizeOf($aPath);
		for($i = 0; $i < $iSize; $i++) {

			if(is_array($curZone) && array_key_exists($aPath[$i], $curZone)) {
				$curZone = $curZone[$aPath[$i]];
				if(is_string($curZone)) {
					if($curZone{0} === 'X' && substr($curZone, 0, 6) === 'XPATH:') {
						$curZone = $this->xPath($curZone);
					} elseif($curZone{0} === 'T' && substr($curZone, 0, 3) === 'TS:') {
						$sTsPointer = $curZone;
						if(($curZone = $this->getTS($curZone, TRUE)) === AMEOSFORMIDABLE_TS_FAILED) {
							tx_mkforms_util_Div::mayday("The typoscript pointer <b>" . $sTsPointer . "</b> evaluation has failed, as the pointed property does not exists within the current Typoscript template");
						}
					} elseif($curZone{0} === 'T' && substr($curZone, 0, 4) === 'TCA:') {
						$curZone = $this->getTcaVal($curZone);
					} elseif($curZone{0} === 'L' && substr($curZone, 0, 4) === 'LLL:') {
						$curZone = $this->getLLLabel($curZone);
					} elseif($curZone{0} === 'E' && $curZone{1} === 'X' && substr($curZone, 0, 8) === 'EXTCONF:') {
						$curZone = $this->getExtConfVal($curZone);
					}
				}
			} else {
				return FALSE;
			}
		}

		return $curZone;
	}

	public function getExtConfVal($sExtConf) {
		if($sExtConf{0} === 'E' && $sExtConf{1} === 'X' && substr($sExtConf, 0, 8) === 'EXTCONF:') {
			$sExtConf = substr($sExtConf, 8);
		} else {
			$sPath = $sExtConf;
		}

		$sPath = str_replace('.', '/', $sExtConf);
		$sRes = $this->get($sPath, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']);
		return $sRes;
	}

	/**
	 * Returns the translated string for the given LLL path
	 *
	 * @param	string		$label: LLL path
	 * @return	string		The translated string
	 */
	public function getLLLabel($mLabel) {
		$mLabel = $this->findLLLabel($mLabel);
		if(is_string($mLabel) && t3lib_div::isFirstPartOfStr(strtoupper($mLabel), 'LABEL_')) {
			$mLabel = $this->getForm()->getConfigurations()->getLL($mLabel, $mLabel);
		}
		return $mLabel;
	}
	/**
	 * Returns the translated string for the given LLL path
	 *
	 * @param	string		$label: LLL path
	 * @return	string		The translated string
	 */
	private function findLLLabel($mLabel) {

		$mLabel = $this->getForm()->getRunnable()->callRunnable($mLabel);

		/*
		 * Wenn hier ein Array drin steckt, nicht weiter machen.
		 * Kann bei Parametern so sein
		 * <param name="links" showjr="jobRequestId" showjobad="jobAdId" editjr="uid" editjobad="uid" />
		 */
		if(is_array($mLabel)) {
			return $mLabel;
		}

		// Wenn im meta der XML Form ein defaultLLL gesetzt ist,
		// wird versucht anand des absoluten namens vom renderlet ein label zu finden.
		if($this->getForm()->sDefaultLLLPrefix !== FALSE) {
			if(t3lib_div::isFirstPartOfStr($mLabel, 'LLL:') && !t3lib_div::isFirstPartOfStr($mLabel, 'LLL:EXT:')) {
				$mLabel = str_replace('LLL:', 'LLL:' . $this->getForm()->sDefaultLLLPrefix . ':', $mLabel);
			}
		}

		if($mLabel{0} === "L" && t3lib_div::isFirstPartOfStr($mLabel, "LLL:")) {
			if(TYPO3_MODE == "FE") {
				// front end
				if(!$GLOBALS["TSFE"]){
					$message = 'Es gibt kein TSFE aber es soll ein label gesucht werden. Das kann ' . 
						'aus folgenden Grund passieren. Man hat ein autocomplete mit childs, ' .
						'ein default LL aber keine label für die childs. Entweder wird kein ' . 
						'default LL verwendet oder den childs wird ein label gegeben, die es' . 
						'gar nicht geben muss da diese nicht gerendered werden.';
					throw new Exception(
						$message,
						$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['baseExceptionCode'] . 2
					);
				}
				return $GLOBALS["TSFE"]->sL($mLabel);
			}
			else {
				// back end
				return $GLOBALS["LANG"]->sL($mLabel);
			}
		}

		return $mLabel;
	}

	/**
	 * Loads the internal _aConf configuration array from the XML file
	 * IMPORTANT NOTE : the root /formidable is deleted, so all pathes shouldn't start with /formidable
	 *
	 * @return	void
	 */
	private function loadXmlConf($xmlPath) {

		tx_rnbase::load('tx_mkforms_util_XMLParser');
		$this->config = tx_mkforms_util_XMLParser::getXml($xmlPath);

		// root sollte mkforms sein!
		$sRoot = 'mkforms';
		if(!array_key_exists('mkforms', $this->config)){
			// fallback, da viele XMLs formidable im root stehen haben
			$sRoot = 'formidable';
			if(!array_key_exists('formidable', $this->config)) {
				tx_mkforms_util_Div::mayday('Root "mkforms" not found in XML. ('.$xmlPath.')');
			}
		}

		$this->config = $this->config[$sRoot];	// the root is deleted
		$this->sXmlVersion = $this->get('/version', $this->config);

		if(($this->sXmlMinVersion = $this->get('/minversion', $this->_aConf)) !== FALSE) {
			if(tx_mkforms_util_Div::getVersionInt() < t3lib_div::int_from_ver($this->sXmlMinVersion)) {
				tx_mkforms_util_Div::mayday('The given XML requires a version of Formidable (<b>' . $this->sXmlMinVersion . '</b> or above) more recent than the one installed (<b>' . tx_mkforms_util_Div::getVersion() . '</b>).');
			}
		}

		if(($this->sXmlMaxVersion = $this->get('/maxversion', $this->_aConf)) !== FALSE) {
			if(tx_mkforms_util_Div::getVersionInt() > t3lib_div::int_from_ver($this->sXmlMaxVersion)) {
				tx_mkforms_util_Div::mayday('The given XML requires a version of Formidable (<b>' . $this->sXmlMaxVersion . '</b> maximum) older than the one installed (<b>' . tx_mkforms_util_Div::getVersion() . '</b>).');
			}
		}
	}


	/**
	 * Takes an array of typoscript configuration, and adapt it to formidable syntax
	 *
	 * @param	array		$aConf: TS array for application
	 * @return	array		Refined array
	 */
	private function refineTS($aConf) {

		$aTemp = array();

		// processing meta
		$aTemp['meta'] = array();
		if (isset($aConf['meta.']) && is_array($aConf['meta.'])) {
			reset($aConf['meta.']);
			while(list($sKey,) = each($aConf['meta.'])) {
				if(is_string($aConf['meta.'][$sKey]) && $aConf['meta.'][$sKey] === 'codebehind') {
					if(array_key_exists($sKey . '.', $aConf['meta.'])) {
						$aTemp['meta']['codebehind-' . $sKey] = $aConf['meta.'][$sKey . '.'];
					}
					unset($aConf['meta.'][$sKey . '.']);
				} else {
					if(is_array($aConf['meta.'][$sKey])) {
						$sPlainKey = substr($sKey, 0, -1);
						$aTemp['meta'][$sPlainKey] = $this->_removeDots($aConf['meta.'][$sKey]);
					} else {
						$aTemp['meta'][$sKey] = $aConf['meta.'][$sKey];
					}
				}
			}
		}


		// processing control
		$aTemp['control'] = array();
		if (isset($aConf['control.']) && is_array($aConf['control.'])) {
			reset($aConf['control.']);
			while(list($sKey, ) = each($aConf['control.'])) {
				if(is_string($aConf['control.'][$sKey])) {
					if($sKey === 'datahandler') {
						$aTemp['control']['datahandler'] = array(
							'type' => substr($aConf['control.'][$sKey], strlen('datahandler:'))
						);

						if(array_key_exists($sKey . '.', $aConf['control.'])) {
							$aTemp['control']['datahandler'] = t3lib_div::array_merge_recursive_overrule(
								$aTemp['control']['datahandler'],
								$this->_removeDots($aConf['control.'][$sKey . '.'])
							);
						}
					} elseif($sKey === 'renderer') {
						$aTemp['control']['renderer'] = array(
							'type' => substr($aConf['control.'][$sKey], strlen('renderer:'))
						);

						if(array_key_exists($sKey . '.', $aConf['control.'])) {
							$aTemp['control']['renderer'] = t3lib_div::array_merge_recursive_overrule(
								$aTemp['control']['renderer'],
								$this->_removeDots($aConf['control.'][$sKey . '.'])
							);
						}
					}
				} else {
					if($sKey === 'actionlets.') {
						$aTemp['control']['actionlets'] = array();

						reset($aConf['control.'][$sKey]);
						while(list($sActKey, ) = each($aConf['control.'][$sKey])) {
							if(is_string($aConf['control.'][$sKey][$sActKey])) {
								$aTemp['control']['actionlets']['actionlet-' . $sActKey] = array(
									'type' => substr($aConf['control.'][$sKey][$sActKey], strlen('actionlet:'))
								);

								if(array_key_exists($sActKey . '.', $aConf['control.'][$sKey])) {
									$aTemp['control']['actionlets']['actionlet-' . $sActKey] = t3lib_div::array_merge_recursive_overrule(
										$aTemp['control']['actionlets']['actionlet-' . $sActKey],
										$this->_removeDots($aConf['control.'][$sKey][$sActKey . '.'])
									);
								}
							}
						}
					} elseif($sKey === 'datasources.') {
						$aTemp['control']['datasources'] = array();

						reset($aConf['control.'][$sKey]);
						while(list($sActKey, ) = each($aConf['control.'][$sKey])) {
							if(is_string($aConf['control.'][$sKey][$sActKey])) {
								$aTemp['control']['datasources']['datasource-' . $sActKey] = array(
									'type' => substr($aConf['control.'][$sKey][$sActKey], strlen('datasource:'))
								);

								if(array_key_exists($sActKey . '.', $aConf['control.'][$sKey])) {
									$aTemp['control']['datasources']['datasource-' . $sActKey] = t3lib_div::array_merge_recursive_overrule(
										$aTemp['control']['datasources']['datasource-' . $sActKey],
										$this->_removeDots($aConf['control.'][$sKey][$sActKey . '.'])
									);
								}
							}
						}
					} elseif($sKey === 'sandbox.') {
						$aTemp['control']['sandbox'] = $this->_removeDots($aConf['control.']['sandbox.']);
					}
				}
			}
		}

		// processing renderlets
		$aTemp['elements'] = array();
		if (isset($aConf['elements.']) && is_array($aConf['elements.'])) {
			reset($aConf['elements.']);
			while(list($sKey, ) = each($aConf['elements.'])) {
				if(is_string($aConf['elements.'][$sKey])) {

					$aType = explode(':', $aConf['elements.'][$sKey]);

					if($aType[0] === 'renderlet') {
						if(array_key_exists($sKey . '.', $aConf['elements.'])) {

							$aTemp['elements'][$aType[0] . '-' . $sKey . '-' . rand()] = $this->refineTS_renderlet(
								$aConf['elements.'][$sKey],
								$aConf['elements.'][$sKey . '.']
							);

						} else {
							$aTemp['elements'][$aType[0] . '-' . $sKey . '-' . rand()] = array('type' => $aType[1]);
						}
					}
				}
			}
		}

		return $aTemp;
	}

	/**
	 * Takes a typoscript conf for a renderlet and refines it to formidable-syntax
	 *
	 * @param	string		$sTen: TS name like: 10 = renderlet:TEXT
	 * @param	array		$aTenDot: TS value of 10. like: 10.value = Hello World !
	 * @return	array		refined conf
	 */
	private function refineTS_renderlet($sTen, $aTenDot) {
		$aType = explode(':', $sTen);
		$aRdt = array(
			'type' => $aType[1],
		);

		if(array_key_exists('childs.', $aTenDot)) {
			$aRdt['childs'] = array();

			reset($aTenDot['childs.']);
			while(list($sKey, $sChild) = each($aTenDot['childs.'])) {

				$aChild = array();
				if(is_string($sChild)) {
					$aChildType = explode(':', $sChild);
					if($aChildType[0] === 'renderlet') {
						if(array_key_exists($sKey . '.', $aTenDot['childs.'])) {
							$aChild = $this->refineTS_renderlet(
								$sChild,
								$aTenDot['childs.'][$sKey . '.']
							);
						} else {
							$aChild = $this->refineTS_renderlet(
								$sChild,
								array()
							);
						}
					}

					$aRdt['childs'][$aChildType[0] . '-' . $sKey . '-' . rand()] = $aChild;
				}
			}

			unset($aTenDot['childs.']);
		}

		if(array_key_exists('validators.', $aTenDot)) {
			$aRdt['validators'] = array();
			reset($aTenDot['validators.']);
			while(list($sKey, $sValidator) = each($aTenDot['validators.'])) {
				$aValidator = array();
				if(is_string($sValidator)) {
					$aValType = explode(':', $sValidator);
					if($aValType[0] === 'validator') {

						$aValidator['type'] = $aValType[1];

						if(array_key_exists($sKey . '.', $aTenDot['validators.'])) {
							$aValidator = t3lib_div::array_merge_recursive_overrule(
								$aValidator,
								tx_mkforms_util_Div::removeDots($aTenDot['validators.'][$sKey . '.'])
							);
						}

						$aRdt['validators']['validator-' . $sKey] = $aValidator;
					}
				}
			}

			unset($aTenDot['validators.']);
		}

		$aRdt = t3lib_div::array_merge_recursive_overrule(
			$aRdt,
			tx_mkforms_util_Div::removeDots($aTenDot)
		);

		reset($aRdt);
		return $aRdt;
	}

	/**
	 * Fügt die default xml config zur aktuellen hinzu.
	 * @TODO: testcase integrieren!
	 *
	 * @param array $aXmlConf
	 * @param array $aDefaultXml
	 * @return array
	 */
	private function loadDefaultXmlConf(&$aXmlConf = false, $aDefaultXml = false) {
		$aDefaultXml = $aDefaultXml ? $aDefaultXml : $this->getForm()->getConfTS('defaultXml.');
		if (!is_array($aDefaultXml)) return;
		if (!is_array($aXmlConf)) {
			// als referenz, damit die default werte hinzugefügt werden können.
			$aXmlConf = &$this->config;
		}
		// die default config durchlaufen und der aktuellen hinzufügen.
		foreach($aDefaultXml as $key => $value) {
			if (substr($key, strlen($key)-1, 1) == '.'){
				$key_1 = substr($key, 0, strlen($key)-1);
				if(!is_array($aXmlConf[$key_1])) $aXmlConf[$key_1] = array();
				$aXmlConf[$key_1] = $this->loadDefaultXmlConf($aXmlConf[$key_1], $value);
			}
			elseif (!array_key_exists($key, $aXmlConf)) {
				$aXmlConf[$key] = $value;
			}
		}
		return $aXmlConf;
	}

	/**
	 * [Describe function...]
	 *
	 * @param String $sPath: ...
	 * @param array $aConf: ...
	 * @return boolean
	 */
	public function defaultTrue($sPath, $aConf = -1) {
		if(($val =$this->get($sPath, $aConf)) !== FALSE) {
			return $this->isTrueVal($val);
		}
		return TRUE;	// TRUE as a default
	}

	/**
	 *
	 * @param String $sPath: ...
	 * @param array $aConf: ...
	 * @return boolean
	 */
	public function defaultFalse($sPath, $aConf = -1) {
		if(($val =$this->get($sPath, $aConf)) !== FALSE) {
			return $this->isTrueVal($val);
		}
		return FALSE;	// FALSE as a default
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sPath: ...
	 * @param	[type]		$aConf: ...
	 * @return	[type]		...
	 */
	public function isTrue($sPath, $aConf = -1) {
		return $this->isTrueVal($this->get($sPath,	$aConf));
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$sPath: ...
	 * @param	[type]		$aConf: ...
	 * @return	[type]		...
	 */
	public function isFalse($sPath, $aConf = -1) {
		$mValue = $this->get($sPath, $aConf);
		return ($mValue !== FALSE) ? $this->isFalseVal($mValue) : FALSE;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$mVal: ...
	 * @return	[type]		...
	 */
	private function isTrueVal($mVal) {
		$mVal = $this->form->getRunnable()->callRunnable($mVal);
		return (($mVal === TRUE) || ($mVal == "1") || (strtoupper($mVal) == "TRUE"));
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$mVal: ...
	 * @return	[type]		...
	 */
	private function isFalseVal($mVal) {
		$mVal = $this->form->getRunnable()->callRunnable($mVal);
		return (($mVal == FALSE) || (strtoupper($mVal) == "FALSE"));
	}

	public function isDebug() {
		$this->initDebug();
		return $this->debug > 0;
	}
	public function isDebugLight() {
		$this->initDebug();
		return $this->debug == 2;
	}
	/**
	 * In /meta/debug kann man einen Debug-Wert serzen. Dieser ist entweder eine Zahl oder ein Boolean. Wird Boolean=true gesetzt, dann wird das
	 * in die Zahl zwei umgewandelt
	 *
	 */
	private function initDebug() {
		if($this->debug == -99) {
			$this->debug = intval($this->get('/meta/debug'));
			if($this->debug == 0 && $this->isTrue('/meta/debug/')) {
				$this->debug = 2;	// LIGHT
			}
		}
	}

	/**
	 * Refine raw conf and:
	 *	-> inserts recursively all includexml declared
	 *	-> inserts recursively all includets declared
	 *	-> apply modifiers declared, if any
	 *	-> remove sections emptied by modifiers, if any
	 *	-> execute xmlbuilders declared, if any
	 *
	 * @param	array		$aConf: array of raw config to refine
	 * @param	[type]		$aTempDebug: internal use
	 * @return	array		refined array of conf
	 */
	public function compileConfig(&$aTempDebug) {
		$this->config = $this->compileConf($this->config, $aTempDebug);
	}

	/**
	 * @return array
	 */
	public function getConfigArray() {
		return $this->config;
	}

	private function compileConf($aConf, &$aTempDebug) {
		$aTempDebug['aIncHierarchy'] = array();

		$aConf = $this->insertSubXml($aConf,$aTempDebug['aIncHierarchy']);
		$aConf = $this->insertSubTS($aConf);
		$aConf = $this->applyModifiers($aConf);
		$aConf = $this->deleteEmpties($aConf);	// ????  surveiller
		$aConf = $this->insertXmlBuilder($aConf);

		tx_mkforms_util_Div::debug($aIncHierarchy, 'MKFORMS CORE - INCLUSION HIERARCHY', $this->form);
		return $aConf;
	}

	/**
	 * Executes and inserts conf generated by xmlbuilders, if any declared
	 *
	 * @param	array		$aConf: array of conf to process
	 * @param	array		$aTemp: optional; internal use
	 * @return	array		processed array of conf
	 */
	private function insertXmlBuilder($aConf, $aTemp = array()) {

		reset($aConf);
		while(list($key, $val) = each($aConf)) {
			if(is_array($val)) {
				if($key{0} === 'x' && t3lib_div::isFirstPartOfStr($key, 'xmlbuilder')) {
					$aTemp = $this->array_add($this->getForm()->getRunnable()->callRunnable($val), $aTemp);
				} else {
					$aTemp[$key] = $this->insertXmlBuilder($val);
				}
			} else {
				$aTemp[$key] = $val;
			}
		}

		return $aTemp;
	}

	/**
	 * Insert conf referenced by includexml tags
	 * Aufruf nur aus _compileConf(). Achtung Recursiv!!
	 *
	 * @param	array		$aConf: array of conf to process
	 * @param	array		$aDebug: internal use
	 * @param	string		$sParent: optional; parent xpath
	 * @return	array		processed conf array
	 */
	private function insertSubXml($aConf, &$aDebug, $sParent = FALSE) {

		if(!$aConf) { return array();}
		reset($aConf);

		$aTemp = array();
		if($sParent === FALSE) {
			$sParent = '/formidable';
		}

		while(list($key, $val) = each($aConf)) {
			if(is_array($val)) {

				if($key{0} === 'i' && t3lib_div::isFirstPartOfStr($key, 'includexml')) {

					if(array_key_exists('path', $val)) {
						$sPath = $val['path'];
					} elseif(trim($val['__value']) !== '') {
						$sPath = $val['__value'];
					} else {
						$sPath = $this->_xmlPath;
					}

					$sPath = $this->getForm()->getRunnable()->callRunnable($sPath);

					$bInclude = TRUE;

					if(array_key_exists('condition', $val)) {
						$bInclude = $this->_defaultTrue('/condition', $val);
					}

					$bInclude = trim($sPath) === '' ? false : $bInclude;

					if($bInclude) {

						$aDebug[] = array(
							$sParent . ' 1- ' . $sPath,
							'subxml' => array()
						);
						$iNewKey = count($aDebug) - 1;

						tx_rnbase::load('tx_mkforms_util_XMLParser');
						$aXml = tx_mkforms_util_XMLParser::getXml(tx_mkforms_util_Div::toServerPath($sPath), TRUE);

						if(array_key_exists('dynaxml', $val)) {
							$aDynaXml = $val['dynaxml'];
							$aXml = $this->_substituteDynaXml($aXml, $aDynaXml);
						}

						if(array_key_exists('xpath', $val)) {

							if($val['xpath']{0} === '.') {
								$sXPath = $this->absolutizeXPath($val['xpath'], $sParent);
							} else {
								$sXPath = $val['xpath'];
							}

							#debug($aXml);

							$aXml = $this->xPath('XPATH:' . $sXPath,$aXml,TRUE); // BREAKABLE

							if($aXml === AMEOSFORMIDABLE_XPATH_FAILED) {
								tx_mkforms_util_Div::mayday('<b>XPATH:' . $sXPath . '</b> is not valid, or matched nothing.<br />XPATH breaked on: <b>' . $this->sLastXPathError . '</b>');
							}
						}

						if(array_key_exists('debug', $val) && $this->_isTrueVal($val['debug'])) {
							$this->debug(array('include' => $val, 'result' => $aXml));
						}

						$aTemp = $this->array_add(
							$this->insertSubXml($aXml,$aDebug[$iNewKey]['subxml'],$sParent . '/' . $key),
							$aTemp
						);

						if(empty($aDebug[$iNewKey]['subxml'])) {
							unset($aDebug[$iNewKey]['subxml']);
						}
					}
				} else {

					$aInsert = $this->insertSubXml(
						$val,
						$aDebug,
						$sParent . '/' . $key
					);

					if(array_key_exists($key, $aTemp)) {

						// reindexing the xml array for correct merging
						$counter = 0;
						while(array_key_exists($key . '-' . $counter, $aTemp)) {
							$counter++;
						}

						$aTemp[$key . '-' . $counter] = $aInsert;
					} else {
						$aTemp[$key] = $aInsert;
					}
				}
			} else {

				if($key{0} === 'i' && t3lib_div::isFirstPartOfStr($key, 'includexml')) {

					$aDebug[] = array(
						$sParent => $val,
						'subxml' => array()
					);

					$iNewKey = count($aDebug) - 1;

					tx_rnbase::load('tx_mkforms_util_XMLParser');
					$aXml = tx_mkforms_util_XMLParser::getXml(t3lib_div::getFileAbsFileName($val), TRUE);

					$aTemp = $this->array_add(
						$this->insertSubXml($aXml,$aDebug[$iNewKey]['subxml'],$sParent . '/' . $key),
						$aTemp
					);

					if(empty($aDebug[$iNewKey]['subxml'])) {
						unset($aDebug[$iNewKey]['subxml']);
					}

				} else {
					$aTemp[$key] = $val;
				}
			}
		}

		return $aTemp;
	}

	/**
	 * Resolves an xpath and returns value pointed by this xpath
	 *
	 * @param	string		$sPath: xpath
	 * @return	mixed
	 */
	private function xPath($sPath, $aConf = -1, $bBreakable = FALSE) {

		$this->sLastXPathError = '';

		if(!(is_string($sPath) && $sPath{0} === 'X' && substr($sPath, 0, 6) === 'XPATH:')) {
			return FALSE;
		}

		$sPath = tx_mkforms_util_Div::trimSlashes(strtolower(substr($sPath, 6)));

		if(!strpos($sPath, '[')) {
			return $this->get($sPath, $aConf);
		}

		$aSegments = array();
		if($aConf === -1) {
			$aConf = $this->_aConf;
		}

		$aParts = explode('/', $sPath);
		reset($aParts);
		while(list(, $sPart) = each($aParts)) {
			$aTemp = explode('[', str_replace(']', '', $sPart));
			if(count($aTemp) > 1) {
				// we have to search on a criteria sequence
				$sWhat = $aTemp[0];
				$aTempCrits = t3lib_div::trimExplode(',', $aTemp[1]);
				reset($aTempCrits);
				$aCrits = array();
				while(list(, $sTempCrit) = each($aTempCrits)) {
					$aCrit = t3lib_div::trimExplode('=', $sTempCrit);
					$aCrits[$aCrit[0]] = $aCrit[1];
				}
				$aSegments[] = array('what' => $sWhat,'crits' => $aCrits,'segment' => $sPart);
			} else {
				$aSegments[] = array('what' => $sPart,'crits' => FALSE,'segment' => $sPart);
			}
		}
		$aPossibles = array(0 => $aConf);

		reset($aConf);
		while(list($iLevel, $aSegment) = each($aSegments)) {
			$bSegMatch = FALSE;
			$this->sLastXPathError .= '/' . $aSegment['segment'];
			$aNewPossibles = array();
			$aPossKeys = array_keys($aPossibles);
			while(list(, $sPosKey) = each($aPossKeys)) {

				$aKeys = array_keys($aPossibles[$sPosKey]);
				reset($aKeys);
				while(list(, $sKey) = each($aKeys)) {

					if(substr($sKey, 0, strlen($aSegment['what'])) == $aSegment['what']) {
						$bMatch = TRUE;
						if($aSegment['crits'] !== FALSE) {
							reset($aSegment['crits']);
							while(list($sProp, $sValue) = each($aSegment['crits'])) {
								$bMatch = $bMatch && (array_key_exists(strtolower($sProp), $aPossibles[$sPosKey][$sKey]) && strtolower($aPossibles[$sPosKey][$sKey][$sProp]) == strtolower($sValue));
							}
						}

						if($bMatch) {
							$bSegMatch = TRUE;
							$aNewPossibles[$sKey] = $aPossibles[$sPosKey][$sKey];
						}
					}
				}
			}

			if($bSegMatch === FALSE && $bBreakable === TRUE) {
				return AMEOSFORMIDABLE_XPATH_FAILED;
			}

			$aPossibles = $aNewPossibles;
		}

		reset($aPossibles);
		return $aPossibles;
	}

	/**
	 * Debug some data to screen
	 */
	private function debug() {
		$aVars = func_get_args();
		if(func_num_args() === 1) {
			$aVars = func_get_arg(0);
		}
		echo '<div>' . tx_mkforms_util_Div::viewMixed($aVars,TRUE,0) . '</div>';
		flush();
	}

	/**
	 * Inserts conf declared by includets
	 *
	 * @param	array		$aConf: array of conf to process
	 * @param	array		$aTemp: optional; internal use
	 * @return	array		processed conf array
	 */
	private function insertSubTS($aConf, $aTemp = array()) {

		reset($aConf);
		while(list($key, $val) = each($aConf)) {
			$isIncludeTS = ($key{0} === 'i' && t3lib_div::isFirstPartOfStr($key, 'includets'));
			if(is_array($val)) {
				if($isIncludeTS) {
					if(array_key_exists('path', $val)) {
						$aTs = $this->getTS($val);
						$aTemp = $this->array_add($this->insertSubTS($aTs),$aTemp);
					}
				} else {
					$aTemp[$key] = $this->insertSubTS($val);
				}
			}
			else {
				if($isIncludeTS) {
					$aTs = $this->getTS($val);
					$aTemp = $this->array_add($aTs,$aTemp);
				} else {
					$aTemp[$key] = $val;
				}
			}
		}

		return $aTemp;
	}

	/**
	 * Utility function for _insertSubTS
	 *
	 * @param	string		$sTSPath: ts path to get
	 * @return	mixed		ts conf
	 */
	private function getTS($sTSPath) {
		// TODO: Hier die passende TS laden
		return $this->get($sTSPath, tx_mkforms_util_Div::removeDots($this->_oParent->conf));
	}
	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$a1: ...
	 * @param	[type]		$a2: ...
	 * @return	[type]		...
	 */
	private function array_add($a1, $a2) {

		if(is_array($a1)) {
			$aTemp = array();
			$aTemp2 = array();
			reset($a1);
			reset($a2);

			while(list($key, $val) = each($a1)) {
				if($key != 'type' && array_key_exists($key, $a2)) {
					$counter = 0;
					while(array_key_exists($key . '-' . $counter, $a2)) {
						$counter++;
					}
					$a2[$key . '-' . $counter] = $val;
				} else {
					$a2[$key] = $val;
				}
			}
		}
		reset($a2);
		return $a2;
	}

	/**
	 * Utility method for _applyModifiers()
	 *
	 * @param	array		$aSubConf
	 * @return	array
	 */
	private function applyLocalModifiers($aSubConf) {

		reset($aSubConf);
		if(($aModifiers = $this->get('/modifiers', $aSubConf)) !== FALSE) {

			reset($aModifiers);
			while(list($sModKey, $aModifier) = each($aModifiers)) {

				if($this->_matchConditions($aModifier)) {

					$aSubConf =
						t3lib_div::array_merge_recursive_overrule(
							$aSubConf,
							$aSubConf['modifiers'][$sModKey]['modification']
						);
				}
			}
		}

		$aSubConf = $this->deleteEmpties($aSubConf);
		reset($aSubConf);
		return $aSubConf;
	}
	/**
	 * Return
	 * Das wird auch vom Validator aufgerufen formidable_mainvalidator
	 * @param	[type]		$aConditioner: ...
	 * @return	[type]		...
	 */
	public function matchConditions($aConditioner) {

		$bRet = TRUE;

		if(($aConditions = $this->get('/conditions/', $aConditioner)) !== FALSE) {

			if(($sLogic = $this->get('/logic', $aConditions)) === FALSE) {
				$sLogic = 'AND';
			} else {
				$sLogic = strtoupper($sLogic);
			}

			while(list($sCondKey, ) = each($aConditions)) {

				if($sCondKey{0} === 'c' && $sCondKey{1} === 'o' && t3lib_div::isFirstPartOfStr($sCondKey, 'condition')) {
					$aCondition = $this->get($sCondKey, $aConditions);
					switch($sLogic) {
						case 'OR': {
							$bRet = $bRet || $this->_matchCondition($aCondition);
							break;
						}
						case 'AND':
						default: {
							$bRet = $bRet && $this->_matchCondition($aCondition);
							break;
						}
					}
				}
			}
		}

		return $bRet;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$aCondition: ...
	 * @return	[type]		...
	 */
	private function matchCondition($aCondition) {

		reset($aCondition);

		if($this->form->getRunnable()->isRunnable($aCondition)) {
			return $this->form->getRunnable()->callRunnable($aCondition);
		}
		else {
			list($sType, $aInfos) = each($aCondition);
			if($this->form->getRunnable()->isRunnable($aInfos)) {
				$aInfos = $this->form->getRunnable()->callRunnable($aInfos);
			}

			switch(strtoupper($sType)) {

				case 'ISTRUE' : {
					return $this->isTrueVal($aInfos);
					break;
				}
				case 'ISFALSE' : {
					return $this->isFalseVal($aInfos);
					break;
				}
				case 'USERID' :
				case 'USERIDS' : {

					$aUserIds = t3lib_div::trimExplode(',', $aInfos);

					if(is_array($aUserIds)) {
						return in_array(
							@intval($GLOBALS['TSFE']->fe_user->user[$GLOBALS['TSFE']->fe_user->userid_column]),
							$aUserIds
						);
					}

					break;
				}
				case 'ISAUTHENTIFIED' : {

					$bAuth = (intval($GLOBALS['TSFE']->fe_user->user[$GLOBALS['TSFE']->fe_user->userid_column]) > 0);
					return ($this->isTrueVal($aCondition['ISAUTHENTIFIED'])) ? $bAuth : !$bAuth;
					break;
				}
				case 'USERNAME' :
				case 'USERNAMES' : {

					$aUserNames = t3lib_div::trimExplode(',', $aInfos);
					if(is_array($aUserNames)) {
						return @in_array(
							$GLOBALS['TSFE']->fe_user->user[$GLOBALS['TSFE']->fe_user->username_column],
							$aUserNames
						);
					}
					break;
				}
				case 'USERGROUP' :
				case 'USERGROUPS' : {

					$aUserGroups = t3lib_div::trimExplode(',', $aInfos);
					$aCurrentUserGroups = t3lib_div::trimExplode(',',$GLOBALS['TSFE']->fe_user->user['usergroup']);
					return (count(array_intersect($aUserGroups, $aCurrentUserGroups)) > 0);

					break;
				}
				case 'FORMID' : {
					return ($aInfos == $this->formid);
					break;
				}
				case 'ISCREATION' : {
					// TODO: Zugriff auf DataHandler verhindern!
					return !($this->oDataHandler->_edition());
					break;
				}
				case 'ISEDITION' : {
					return ($this->oDataHandler->_edition());
					break;
				}
			}
		}

		return TRUE;
	}

	/**
	 * Removes conf-sections emptied by modifiers, if any
	 *
	 * @param	array		$aConf: array of conf to refine
	 * @return	array		processed conf array
	 */
	private function deleteEmpties($aConf) {

		reset($aConf);
		while(list($sKey, $mValue) = each($aConf)) {
			if(is_array($aConf[$sKey])) {
				if(array_key_exists('empty', $aConf[$sKey])) {
					unset($aConf[$sKey]);
				} else {
					$aConf[$sKey] = $this->deleteEmpties($aConf[$sKey]);
				}
			}
		}

		reset($aConf);
		return $aConf;
	}

	/**
	 * Applies declared modifiers, if any
	 *
	 * @param	array		$aConf: conf to process
	 * @return	array		processed conf
	 */
	private function applyModifiers($aConf) {

		reset($aConf);
		while(list($sKey, $mValue) = each($aConf)) {

			if(is_array($aConf[$sKey])) {
				if($sKey == 'modifiers') {
					$aConf[$sKey] = $this->applyModifiers($aConf[$sKey]);
					$aConf = $this->applyLocalModifiers($aConf);
					unset($aConf[$sKey]);
				} else {
					$aConf[$sKey] = $this->applyModifiers($aConf[$sKey]);
				}
			}
		}

		reset($aConf);
		return $aConf;
	}
	/**
	 * Liefert das Formular
	 * @return tx_mkforms_forms_IForm
	 */
	private function getForm() {
		return $this->form;
	}

	/**
	 * Erstellt eine Instanz auf Basis einer XML-Datei
	 *
	 * @param string $path Pfad zur XML-Datei
	 * @return tx_mkforms_util_Config
	 */
	public static function createInstanceByPath($path, $form) {
		$cfg = new tx_mkforms_util_Config($form);
		$cfg->loadXmlConf(t3lib_div::getFileAbsFileName($path));
		// default config laden hinzufügen
		$cfg->loadDefaultXmlConf();
		return $cfg;
	}
	/**
	 * Erstellt eine Instanz auf Basis eines Typoscript-Arrays
	 *
	 * @param array $confArr Typoscript-Array
	 * @return tx_mkforms_util_Config
	 */
	public static function createInstanceByTS($confArr, $form) {
		$cfg = new tx_mkforms_util_Config($form);
		$cfg->refineTS(t3lib_div::getFileAbsFileName($confArr));
		// default config laden hinzufügen
		$cfg->loadDefaultXmlConf();
		return $cfg;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/util/class.tx_mkforms_util_Config.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/util/class.tx_mkforms_util_Config.php']);
}
?>
