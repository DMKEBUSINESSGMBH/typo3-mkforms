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
 * Loading classes.
 * 
 */
class tx_mkforms_util_XMLParser {
	static $cache = array();
	static $useCache = true;

	public static function enableCacheOn() {
		self::$useCache = true;
	}
	public static function enableCacheOff() {
		self::$useCache = false;
	}
	private static function checkFile($sPath, $isSubXml) {
		if(!file_exists($sPath)) {
			if($isSubXml === FALSE) {
				tx_mkforms_util_Div::smartMayday_XmlFile($sPath);
			} else {
				tx_mkforms_util_Div::mayday("FORMIDABLE CORE - The given XML file path (<b>'" . $sPath . "'</b>) doesn't exists.");
			}
		} elseif(is_dir($sPath)) {
			tx_mkforms_util_Div::mayday("FORMIDABLE CORE - The given XML file path (<b>'" . $sPath . "'</b>) is a directory, and should be a file.");
		} elseif(!is_readable($sPath)) {
			tx_mkforms_util_Div::mayday("FORMIDABLE CORE - The given XML file path (<b>'" . $sPath . "'</b>) exists but is not readable.");
		}
	}
	/**
	 * Reads and parse an xml file, and returns an array of XML
	 * Fresh or cached data, depending on $this->conf["cache."]["enabled"]
	 *
	 * @param	string		$sPath: abs server path to xml file
	 * @param	boolean		$isSubXml
	 * @return	array		xml data
	 */
	public static function getXml($sPath, $isSubXml = FALSE, $bPlain = FALSE) {

		$sHash = md5($sPath);
		if(array_key_exists($sHash, self::$cache)) {
			return self::$cache[$sHash];
		}
		self::checkFile($sPath, $isSubXml);
		
		$aConf = array();

		if(self::$useCache) {
			// TODO: Das muss noch extern gesetzt werden
//		if($this->conf["cache."]["enabled"] == 1) {
			$sProtection = "<?php die('MK Forms - Cache protected'); ?><!--MKFORMS_CACHE-->";

			//debug(stat($sPath));
			$sHash = md5($sPath . '-' . @filemtime($sPath) . '-' . tx_mkforms_util_Div::getVersion());
			$sFile = "formidablexmlcache_" . $sHash . '.php';
			$sCacheDir = "ameos_formidable/cache/";
			$sCachePath = PATH_site . "typo3temp/" . $sCacheDir . $sFile;

			if(file_exists($sCachePath)) {
				$aConf = unserialize(
					base64_decode(
						substr(tx_mkforms_util_Div::fileReadBin($sCachePath), strlen($sProtection) + 3)		/* 3 is size of UTF8-header, aka BOM or Byte Order Mark */
					)
				);
				if(is_array($aConf)) {
					reset($aConf);
				}
			}
		}

		if(empty($aConf)) {

			$sXmlData = tx_mkforms_util_Div::fileReadBin($sPath);
			if(trim($sXmlData) === "") {
				tx_mkforms_util_Div::smartMayday_XmlFile($sPath, "FORMIDABLE CORE - The given XML file path (<b>'" . $sPath . "'</b>) exists but is empty.");
			}

			$aMatches = array();
			preg_match("/^<\?xml(.*)\?>/", $sXmlData, $aMatches);

			// Check result
			if(!empty($aMatches)) {
				$sXmlProlog = $aMatches[0];
				$sXmlData = preg_replace("/^<\?xml(.*)\?>/", "", $sXmlData);

				/*ereg('^[[:space:]]*<\?xml[^>]*encoding[[:space:]]*=[[:space:]]*"([^"]*)"',$sXmlProlog, $aParts);

				if($aParts[1]) {
					$sEncoding = trim(strtolower($aParts[1]));
					if($sEncoding === "utf-8" && trim($GLOBALS["TYPO3_CONF_VARS"]['BE']['forceCharset']) === "") {
						// default T3 charset is ISO-8859-1
						// converting from UTF8 to ISO
						$sXmlData = utf8_decode($sXmlData);
					}
				}*/

			} else {
				$sXmlProlog = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>';
			}

			if($isSubXml) {
				$sXmlData = $sXmlProlog . "\n" . "<phparray>" . $sXmlData . "</phparray>";
			} else {
				$sXmlData = $sXmlProlog . "\n" . $sXmlData;
			}

			if($bPlain === FALSE) {
				$aConf = self::div_xml2array($sXmlData);
			} else {
				$aConf = self::div_xml2array_plain($sXmlData);
			}


			if(is_array($aConf)) {
				if($isSubXml && array_key_exists("phparray", $aConf) && is_array($aConf["phparray"])) {
					$aConf = $aConf["phparray"];
				}
				reset($aConf);
			} else {
				tx_mkforms_util_Div::mayday("FORMIDABLE CORE - The given XML file (<b>'" . $sPath . "'</b>) isn't well-formed XML<br>Parser says : <b>" . $aConf . "</b>");
			}

			if(self::$useCache) {

				if(!@is_dir(PATH_site . "typo3temp/" . $sCacheDir)) {
					if(function_exists('t3lib_div::mkdir_deep')) {
						t3lib_div::mkdir_deep(PATH_site . "typo3temp/", $sCacheDir);
					} else {
						tx_mkforms_util_Div::mkdirDeep(PATH_site . "typo3temp/", $sCacheDir);
					}
				}
				tx_mkforms_util_Div::fileWriteBin(
					$sCachePath,
					$sProtection . base64_encode(serialize($aConf)),
					TRUE	// add UTF-8 header
				);
			}
		}

		reset($aConf);
		self::$cache[$sHash] = $aConf;
		return $aConf;
	}

	private static function div_xml2array_plain($data) {
		return self::div_xml2array(
			$data,
			$keepAttribs=1,
			$caseFolding=0,
			$skipWhite=0,
			$prefix=FALSE,
			$numeric='n',
			$index='index',
			$type='type',
			$base64='base64',
			$php5defCharset='UTF-8',
			TRUE
		);
	}

	/**
	 * Method div_xml2array taken from the Developer API (api_macmade).
	 *
	 * (c) 2004 macmade.net
	 * All rights reserved
	 *
	 * The goal of this API is to provide to the Typo3 developers community
	 * some useful functions, to help in the process of extension development.
	 *
	 * It includes functions, for frontend, backend, databases and miscellaneous
	 * development.
	 *
	 * It's not here to replace any of the existing Typo3 core class or
	 * function. It just try to complete them by providing a quick way to
	 * develop extensions.
	 *
	 * Please take a look at the manual for a complete description of this API.
	 *
	 * @author		Jean-David Gadina (macmade@gadlab.net)
	 * @version		2.3
	 */

	/**
	 * Convert XML data to an array.
	 *
	 * This function is used to convert an XML data to a multi-dimensionnal array,
	 * representing the structure of the data.
	 *
	 * This function is based on the Typo3 array2xml function, in t3lib_div. It basically
	 * does the same, but has a few more options, like the inclusion of the xml tags arguments
	 * in the output array. This function also has support for same multiple tag names
	 * inside the same XML element, which is not the case with the core Typo3 function. In that
	 * specific case, the array keys are suffixed with '-N', where N is a numeric value.
	 *
	 * SPECIAL NOTE: This function can be called without the API class instantiated.
	 *
	 * @param	$data		The XML data to process
	 * @param	$keepAttribs		If set, also includes the tag attributes in the array (with key 'xml-attribs')
	 * @param	$caseFolding		XML parser option: case management
	 * @param	$skipWhite		XML parser option: white space management
	 * @param	$prefix		A tag prefix to remove
	 * @param	$numeric		Keep only the numeric value for a tag prefixed with this argument (default is 'n')
	 * @param	$index		Set the tag name to an alternate value found in the tag arguments (default is 'index')
	 * @param	$type		Force the tag value to a special type, found in the tag arguments (default is 'type')
	 * @param	$base64		Decode the tag value from base64 if the specified tag argument is present (default is 'base64')
	 * @param	$php5defCharset		The default charset to use with PHP5
	 * @return	An		array with the XML structure, or an XML error message if the data is not valid
	 */
	private static function div_xml2array($data,$keepAttribs=1,$caseFolding=0,$skipWhite=0,$prefix=FALSE,$numeric='n',$index='index',$type='type',$base64='base64',$php5defCharset='UTF-8', $bPlain=FALSE) {

		// Function ID
		$funcId = 'div_xml2array';

		// Storage
		$xml = array();
		$xmlValues = array();
		$xmlIndex = array();
		$stack = array(array());

		// Counter
		$stackCount = 0;

		// New XML parser
		$parser = xml_parser_create();

		// Case management option
		xml_parser_set_option($parser,XML_OPTION_CASE_FOLDING,$caseFolding);

		// White space management option
		xml_parser_set_option($parser,XML_OPTION_SKIP_WHITE,$skipWhite);

		// Support for PHP5 charset detection
		if ((double) phpversion() >= 5) {
			// Find the encoding parameter in the XML declaration
			//ereg('^[[:space:]]*<\?xml[^>]*encoding[[:space:]]*=[[:space:]]*"([^"]*)"',substr($data,0,200),$result);
			preg_match('/^[[:space:]]*<\?xml[^>]*encoding[[:space:]]*=[[:space:]]*"([^"]*)"/',substr($data,0,200),$result);
			// Check result
			if ($result[1]) {
				// Charset found in the XML declaration
				$charset = $result[1];
			} else if ($TYPO3_CONF_VARS['BE']['forceCharset']) {
				// Force charset to Typo3 configuration if defined
				$charset = $TYPO3_CONF_VARS['BE']['forceCharset'];
			} else {
				// Default charset
				$charset = $php5defCharset;
			}
			// Charset management option
			xml_parser_set_option($parser,XML_OPTION_TARGET_ENCODING,$charset);
		}

		// Parse XML structure
		xml_parse_into_struct($parser,$data,$xmlValues,$xmlIndex);
		// Error in XML
		if (xml_get_error_code($parser)) {
			// Error
			$error = 'XML error: ' . xml_error_string(xml_get_error_code($parser)) . ' at line ' . xml_get_current_line_number($parser);
			// Free XML parser
			xml_parser_free($parser);
			// Return error
			return $error;
		} else {
			// Free XML parser
			xml_parser_free($parser);
			// Counter for multiple same keys
			$sameKeyCount = array();
			// Process each value
			while(list($key,$val) = each($xmlValues)) {
				if($bPlain === FALSE) {
					// lower-case on tagName
					$val['tag'] = strtolower($val['tag']);
					// lower-case on attribute name
					if(array_key_exists("attributes", $val)) {
						$val["attributes"] = array_change_key_case($val["attributes"], CASE_LOWER);
					}
				}

				// Get the tag name (without prefix if specified)
				$tagName = ($prefix && t3lib_div::isFirstPartOfStr($val['tag'], $prefix)) ? substr($val['tag'],strlen($prefix)) : $val['tag'];
				if($bPlain === FALSE) {
					$aTagName = explode(
						':',
						$tagName
					);
					if(sizeof($aTagName) > 1) {
						$type = $aTagName[1];
						$tagName = $aTagName[0];
						$val['attributes']['type'] = strtoupper($type);
					}
				}

				// Support for numeric tags (<nXXX>)
				$numTag = (substr($tagName,0,1) == $numeric) ? substr($tagName,1) : FALSE;
				// Check if tag is a real numeric value
				if ($numTag && !strcmp(intval($numTag),$numTag)) {
					// Store only numeric value
					$tagName = intval($numTag);
				}
				// Support for alternative value
				if (strlen($val['attributes'][$index])) {
					// Store alternate value
					$tagName = $val['attributes'][$index];
				}
				// Check if array key already exists
				if (array_key_exists($tagName,$xml)) {
					// Check if the current level has already a key counter
					if (!isset($sameKeyCount[$val['level']])) {
						// Create array
						$sameKeyCount[$val['level']] = 0;
					}
					// Increase key counter
					$sameKeyCount[$val['level']]++;
					// Change tag name to avoid overwriting existing values
					$tagName = $tagName . '-' . $sameKeyCount[$val['level']];
				}

				// Check tag type
				switch($val['type']) {
					// Open tag
					case 'open':
						// Storage
						$xml[$tagName] = array();
						// Memorize content
						$stack[$stackCount++] = $xml;
						// Reset main storage
						$xml = array();
						// Support for tag attributes
						if ($keepAttribs && $val['attributes']) {
							$xml = $val['attributes'];
						}
					break;

					// Close tag
					case 'close':
						// Memorize array
						$tempXML = $xml;
						// Decrease the stack counter
						$xml = $stack[--$stackCount];
						// Go to the end of the array
						end($xml);
						// Add temp array
						if(!empty($tempXML)) {
							$xml[key($xml)] = $tempXML;
						}
						// Unset temp array
						unset($tempXML);
						// Unset key counters for the child level
						unset($sameKeyCount[$val['level'] + 1]);
						reset($xml);
					break;

					// Complete tag
					case 'complete':
						// Check for base64
						if ($val['attributes']['base64']) {
							// Decode value
							$xml[$tagName] = base64_decode($val['value']);
						} else {
							// Add value (force string)
							if(
								array_key_exists("value", $val) != ""
								&&
								$tagName != "0"
							) {
								$xml[$tagName] = (string) $val['value'];
							} else {
								$xml[$tagName] = "";
							}
							// Support for value types
							switch((string)$val['attributes'][$type]) {
								// Integer
								case 'integer':
									// Force variable type
									$xml[$tagName] = (integer) $xml[$tagName];
								break;
								// Double
								case 'double':
									$xml[$tagName] = (double) $xml[$tagName];
								break;
								// Boolean
								case 'boolean':
									// Force type
									$xml[$tagName] = (bool) $xml[$tagName];
								break;
								// Array
								case 'array':
									// Create an empty array
									$xml[$tagName] = array();
								break;
							}
						}

						// Support for tag attributes
						if ($keepAttribs && $val['attributes']) {
							// Store attributes
							if(is_array($xml[$tagName])) {
								$xml[$tagName] = array_merge($xml[$tagName], $val['attributes']);
							} else {
								$xml[$tagName] = array_merge(
									$val['attributes'],
									array(
										"__value" => $val["value"]
									)
								);
							}
							// Unset memorized value
							unset($tempTagValue);
						}
					break;
				}
			}
			// Return the array of the XML root element
			return $xml;
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/util/class.tx_mkforms_util_XMLParser.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/util/class.tx_mkforms_util_XMLParser.php']);
}
?>