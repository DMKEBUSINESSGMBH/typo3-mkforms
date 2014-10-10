<?php

class ux_t3lib_TSparser extends t3lib_TSparser {
	var $multiLineHeredoc=FALSE;	// Internally set, when multiline value is accumulated

	/**
	 * Parsing the $this->raw TypoScript lines from pointer, $this->rawP
	 *
	 * @param	array		Reference to the setup array in which to accumulate the values.
	 * @return	string		Returns the string of the condition found, the exit signal or possible nothing (if it completed parsing with no interruptions)
	 */
	function parseSub(&$setup)	{
		while (isset($this->raw[$this->rawP]))	{
			$line = ltrim($this->raw[$this->rawP]);
			$lineP = $this->rawP;
			$this->rawP++;
			if ($this->syntaxHighLight)	$this->regHighLight("prespace",$lineP,strlen($line));

				// Breakpoint?
			if ($this->breakPointLN && ($this->lineNumberOffset+$this->rawP-1)==($this->breakPointLN+1))	{	// by adding 1 we get that line processed
				return '[_BREAK]';
			}

				// Set comment flag?
			if (!$this->multiLineEnabled && substr($line,0,2)=='/*')	{
				$this->commentSet=1;
			}

			if (!$this->commentSet && ($line || $this->multiLineEnabled))	{	// If $this->multiLineEnabled we will go and get the line values here because we know, the first if() will be true.
				if ($this->multiLineEnabled) {	// If multiline is enabled. Escape by ')'
					if (($this->multiLineHeredoc === FALSE && substr($line,0,1)==')') || ($this->multiLineHeredoc !== FALSE && substr($line,0,strlen($this->multiLineHeredoc . ";")) === ($this->multiLineHeredoc . ";")))	{	// Multiline ends...
						if ($this->syntaxHighLight)	$this->regHighLight("operator",$lineP,strlen($line)-1);
						$this->multiLineEnabled=0;	// Disable multiline
						$this->multiLineHeredoc = FALSE;
						$theValue = implode($this->multiLineValue,chr(10));
						if (strstr($this->multiLineObject,'.'))	{
							$this->setVal($this->multiLineObject,$setup,array($theValue));	// Set the value deeper.
						} else {
							$setup[$this->multiLineObject] = $theValue;	// Set value regularly
							if ($this->lastComment && $this->regComments)	{
								$setup[$this->multiLineObject.'..'].=$this->lastComment;
							}
							if ($this->regLinenumbers)	{
								$setup[$this->multiLineObject.'.ln..'][]=($this->lineNumberOffset+$this->rawP-1);
							}
						}
					} else {
						if ($this->syntaxHighLight)	$this->regHighLight("value",$lineP);
						$this->multiLineValue[]=$this->raw[($this->rawP-1)];
					}
				} elseif ($this->inBrace==0 && substr($line,0,1)=='[')	{	// Beginning of condition (only on level zero compared to brace-levels
					if ($this->syntaxHighLight)	$this->regHighLight("condition",$lineP);
					return $line;
				} else {
					if (substr($line,0,1)=='[' && strtoupper(trim($line))=='[GLOBAL]')	{		// Return if GLOBAL condition is set - no matter what.
						if ($this->syntaxHighLight)	$this->regHighLight("condition",$lineP);
						$this->error('Line '.($this->lineNumberOffset+$this->rawP-1).': On return to [GLOBAL] scope, the script was short of '.$this->inBrace.' end brace(s)',1);
						$this->inBrace=0;
						return $line;
					} elseif (strcspn($line,'}#/')!=0)	{	// If not brace-end or comment
						$varL = strcspn($line,' {=<>:(');	// Find object name string until we meet an operator
						$objStrName=trim(substr($line,0,$varL));
						if ($this->syntaxHighLight)	$this->regHighLight("objstr",$lineP,strlen(substr($line,$varL)));
						if (strlen($objStrName)) {
							$r = array();
							if ($this->strict && preg_match('/[^[:alnum:]_\.-]/i',$objStrName,$r))	{
								$this->error('Line '.($this->lineNumberOffset+$this->rawP-1).': Object Name String, "'.htmlspecialchars($objStrName).'" contains invalid character "'.$r[0].'". Must be alphanumeric or one of: "_-."');
							} else {
								$line = ltrim(substr($line,$varL));
								if ($this->syntaxHighLight)	{
									$this->regHighLight("objstr_postspace", $lineP, strlen($line));
									if (strlen($line)>0)	{
										$this->regHighLight("operator", $lineP, strlen($line)-1);
										$this->regHighLight("operator_postspace", $lineP, strlen(ltrim(substr($line,1))));
									}
								}

									// Checking for special TSparser properties (to change TS values at parsetime)
								$match = array();
								if (preg_match('/^:=([^\(]+)\((.+)\).*/', $line, $match))	{
									$tsFunc = trim($match[1]);
									$tsFuncArg = $match[2];
									list ($currentValue) = $this->getVal($objStrName,$setup);

									switch ($tsFunc)	{
										case 'prependString':
											$newValue = $tsFuncArg . $currentValue;
										break;
										case 'appendString':
											$newValue = $currentValue . $tsFuncArg;
										break;
										case 'removeString':
											$newValue = str_replace($tsFuncArg, '', $currentValue);
										break;
										case 'replaceString':
											list($fromStr,$toStr) = explode('|', $tsFuncArg, 2);
											$newValue = str_replace($fromStr, $toStr, $currentValue);
										break;
										case 'addToList':
											$newValue = (strcmp('',$currentValue) ? $currentValue.',' : '') . trim($tsFuncArg);
										break;
										case 'removeFromList':
											$existingElements = t3lib_div::trimExplode(',',$currentValue);
											$removeElements = t3lib_div::trimExplode(',',$tsFuncArg);
											if (count($removeElements))	{
												$newValue = implode(',', array_diff($existingElements, $removeElements));
											}
										break;
										default:
											if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tsparser.php']['preParseFunc'][$tsFunc]))	{
												$hookMethod = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tsparser.php']['preParseFunc'][$tsFunc];
												$params = array('currentValue'=>$currentValue, 'functionArgument'=>$tsFuncArg);
												$fakeThis = FALSE;
												$newValue = t3lib_div::callUserFunction($hookMethod,$params,$fakeThis);
											} else {
												t3lib_div::sysLog('Missing function definition for '.$tsFunc.' on TypoScript line '.$lineP,'Core',2);
											}
									}

									if (isset($newValue))	{
										$line = '= '.$newValue;
									}
								}

								switch(substr($line,0,1))	{
									case '=':
										if ($this->syntaxHighLight)	$this->regHighLight('value', $lineP, strlen(ltrim(substr($line,1)))-strlen(trim(substr($line,1))));

										if (strstr($objStrName,'.'))	{
											$value = Array();
											$value[0] = trim(substr($line,1));
											$this->setVal($objStrName,$setup,$value);
										} else {
											$setup[$objStrName] = trim(substr($line,1));
											if ($this->lastComment && $this->regComments)	{	// Setting comment..
												$setup[$objStrName.'..'].=$this->lastComment;
											}
											if ($this->regLinenumbers)	{
												$setup[$objStrName.'.ln..'][]=($this->lineNumberOffset+$this->rawP-1);
											}
										}
									break;
									case '{':
										$this->inBrace++;
										if (strstr($objStrName,'.'))	{
											$exitSig=$this->rollParseSub($objStrName,$setup);
											if ($exitSig)	return $exitSig;
										} else {
											if (!isset($setup[$objStrName.'.'])) {$setup[$objStrName.'.'] = Array();}
											$exitSig=$this->parseSub($setup[$objStrName.'.']);
											if ($exitSig)	return $exitSig;
										}
									break;
									case '(':
										$this->multiLineObject = $objStrName;
										$this->multiLineEnabled=1;
										$this->multiLineValue=array();
									break;
									case '<':
										if(substr($line, 0, 3) === "<<<") {
											$this->multiLineObject = $objStrName;
											$this->multiLineEnabled=1;
											$this->multiLineValue=array();
											$this->multiLineHeredoc = trim(substr($line, 3));
										} else {
											if ($this->syntaxHighLight)	$this->regHighLight("value_copy", $lineP, strlen(ltrim(substr($line,1)))-strlen(trim(substr($line,1))));
											$theVal = trim(substr($line,1));
											if (substr($theVal,0,1)=='.') {
												$res = $this->getVal(substr($theVal,1),$setup);
											} else {
												$res = $this->getVal($theVal,$this->setup);
											}
											$this->setVal($objStrName,$setup,unserialize(serialize($res)),1);	// unserialize(serialize(...)) may look stupid but is needed because of some reference issues. See Kaspers reply to "[TYPO3-core] good question" from December 15 2005.
										}
									break;
									case '>':
										if ($this->syntaxHighLight)	$this->regHighLight("value_unset", $lineP, strlen(ltrim(substr($line,1)))-strlen(trim(substr($line,1))));
										$this->setVal($objStrName,$setup,'UNSET');
									break;
									default:
										$this->error('Line '.($this->lineNumberOffset+$this->rawP-1).': Object Name String, "'.htmlspecialchars($objStrName).'" was not preceeded by any operator, =<>({');
									break;
								}
							}
							$this->lastComment='';
						}
					} elseif (substr($line,0,1)=='}')	{
						$this->inBrace--;
						$this->lastComment='';
						if ($this->syntaxHighLight)	$this->regHighLight("operator", $lineP, strlen($line)-1);
						if ($this->inBrace<0)	{
							$this->error('Line '.($this->lineNumberOffset+$this->rawP-1).': An end brace is in excess.',1);
							$this->inBrace=0;
						} else {
							break;
						}
					} else {
						if ($this->syntaxHighLight)	$this->regHighLight("comment",	$lineP);

							// Comment. The comments are concatenated in this temporary string:
						if ($this->regComments) $this->lastComment.= trim($line).chr(10);
					}
				}
			}

				// Unset comment
			if ($this->commentSet)	{
				if ($this->syntaxHighLight)	$this->regHighLight("comment",	$lineP);
				if (substr($line,0,2)=='*/')	$this->commentSet=0;
			}
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/res/xclass/class.ux_t3lib_tsparser.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/res/xclass/class.ux_t3lib_tsparser.php']);
}
?>
