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

/**
 * Ersatz für einige Methoden aus Ameos.
 */
class tx_mkforms_util_Templates
{
    private $formid;
    private $sPfxBegin;
    private $sPfxEnd;
    private $form;

    private function __construct($form)
    {
        $this->form = $form;
        $this->setFormId($form->getFormId());
    }

    public function setFormId($formid)
    {
        $this->formid = $formid;
    }

    public function getFormId()
    {
        return $this->formid;
    }

    /**
     * @return tx_ameosformidable
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Parses a template.
     *
     * @param string $templatePath:   the path to the template file
     * @param string $templateMarker: the marker subpart
     * @param array  $aTags:          array containing the values to render
     * @param [type] $aExclude:       ...
     * @param [type] $bClearNotUsed:  ...
     * @param [type] $aLabels:        ...
     *
     * @return string HTML string with substituted values
     */
    public function parseTemplate($templatePath, $templateMarker, $aTags = [], $aExclude = [], $bClearNotUsed = true, $aLabels = [])
    {
        // $tempUrl : the path of the template for use with \Sys25\RnBase\Utility\T3General::getUrl()
        // $tempMarker :  the template subpart marker
        // $aTags : the marker array for substitution
        // $aExclude : tag names that should not be substituted

        if ('L' === $templateMarker[0] && 'LLL:' === substr($templateMarker, 0, 4)) {
            $templateMarker = $this->getLLLabel($templateMarker);
        }
        $templatePath = tx_mkforms_util_Div::toServerPath($templatePath);

        return $this->parseTemplateCode(
            \Sys25\RnBase\Frontend\Marker\Templates::getSubpart(\Sys25\RnBase\Utility\T3General::getUrl($templatePath), $templateMarker),
            $aTags,
            $aExclude,
            $bClearNotUsed,
            $aLabels
        );
    }

    public static function parseForTemplate($sPath)
    {
        $sPath = trim($sPath);

        $iOpened = 0;
        $bInString = false;

        $iCurrent = 0;
        $aCurrent = [0 => ['expr' => '', 'rec' => false, 'args' => false]];

        $aStr = self::str_split($sPath, 1);
        reset($aStr);
        $sLastCar = '';
        foreach ($aStr as $sCar) {
            if (!$bInString && '.' === $sCar) {
                if (0 === $iOpened) {
                    ++$iCurrent;
                    $aCurrent[$iCurrent] = ['expr' => '', 'rec' => false, 'args' => false];
                    $sCar = '';
                }
            }

            if ('"' === $sCar && '\\' !== $sLastCar) {
                $bInString = !$bInString;
            } elseif (!$bInString && '(' === $sCar) {
                ++$iOpened;
            } elseif (!$bInString && ')' === $sCar) {
                --$iOpened;
                $sTrimArg = trim($aCurrent[$iCurrent]['args']);
                $aCurrent[$iCurrent]['args'] = $sTrimArg;
                $aCurrent[$iCurrent]['rec'] = (strlen($sTrimArg) && '"' !== $sTrimArg[0] && false !== strpos($sTrimArg, '('));
            }

            if (0 !== $iOpened) {
                if ('(' === $sCar && false === $aCurrent[$iCurrent]['args']) {
                    $aCurrent[$iCurrent]['args'] = '';
                } else {
                    $aCurrent[$iCurrent]['args'] .= $sCar;
                }
            } else {
                if ($bInString || ('(' !== $sCar && ')' !== $sCar && ' ' !== $sCar && '\n' !== $sCar && '\r' !== $sCar && '\t' !== $sCar)) {
                    if (!$bInString || '\\' !== $sCar) {
                        $aCurrent[$iCurrent]['expr'] .= $sCar;
                    }
                }
            }
            $sLastCar = $sCar;
        }

        return $aCurrent;
    }

    private function processForEaches($sHtml)
    {
        $sPattern = '/\<\!\-\-.*(\#\#\#foreach (.+)\ \bas\b\ \b(.+)\#\#\#).*\-\-\>([^\1]*?)\<\!\-\-.*\1.*\-\-\>/';
        $sHtml = preg_replace_callback($sPattern, [&$this, 'processForEachesCallBack'], $sHtml, -1);    // no limit

        return $sHtml;
    }

    private function processForEachesCallBack($aMatch)
    {
        $aRes = [];

        $aTags = self::currentTemplateMarkers($this->getFormId());
        $bDelete = false;
        $mValue = $this->resolveForTemplate($aMatch[2], $aTags);

        if (AMEOSFORMIDABLE_LEXER_FAILED === $mValue || AMEOSFORMIDABLE_LEXER_BREAKED === $mValue) {
            $bDelete = true;
        } elseif (is_array($mValue)) {
            reset($mValue);
            foreach ($mValue as $sKey => $notNeeded) {
                $aMarkers = [
                    'context' => $aTags,
                    'key' => $sKey,
                    $aMatch[3] => $mValue[$sKey],
                ];

                $aRes[] = $this->parseTemplateCode($aMatch[4], $aMarkers, $aExclude = [], $bClearNotUsed = false);
            }
        }

        if (true === $bDelete || 0 === count($aRes)) {
            return '';
        }

        return implode('', $aRes);
    }

    private function processWithAs($sHtml)
    {
        $sPattern = '/\<\!\-\-.*(\#\#\#with (.+)\ \bas\b\ \b(.+)\#\#\#).*\-\-\>([^\1]*?)\<\!\-\-.*\1.*\-\-\>/';
        $sHtml = preg_replace_callback($sPattern, [&$this, 'processWithAsCallBack'], $sHtml, -1);

        return $sHtml;
    }

    private function processWithAsCallBack($aMatch)
    {
        $aRes = [];

        $aTags = self::currentTemplateMarkers($this->getFormId());
        $bDelete = false;
        $mValue = $this->resolveForTemplate($aMatch[2], $aTags);

        if (AMEOSFORMIDABLE_LEXER_FAILED === $mValue || AMEOSFORMIDABLE_LEXER_BREAKED === $mValue) {
            $bDelete = true;
        } else {
            if (is_array($mValue)) {
                reset($mValue);
            }

            $aTags[trim($aMatch[3])] = $mValue;
            $aRes[] = $this->parseTemplateCode($aMatch[4], $aTags, $aExclude = [], $bClearNotUsed = false);
        }
        if (true === $bDelete || 0 === count($aRes)) {
            return '';
        }

        return implode('', $aRes);
    }

    private function processPerimeters($sHtml, $bClearNotUsed = true)
    {
        $sPattern = '/\<\!\-\-.*(\#\#\#(.+)\ \bperimeter\b\#\#\#).*\-\-\>([^\1]*?)\<\!\-\-.*\1.*\-\-\>/';

        $sCbk = (true === $bClearNotUsed) ? 'processPerimetersCallBackClearNotUsed' : 'processPerimetersCallBackKeepNotUsed';
        $sHtml = preg_replace_callback($sPattern, [&$this, $sCbk], $sHtml, -1);

        return $sHtml;
    }

    private function processPerimetersCallBackClearNotUsed($aMatch)
    {
        return $this->processPerimetersCallBack($aMatch, true);
    }

    private function processPerimetersCallBackKeepNotUsed($aMatch)
    {
        return $this->processPerimetersCallBack($aMatch, false);
    }

    private function processPerimetersCallBack($aMatch, $bClearNotUsed = true)
    {
        $sCond = $aMatch[2];
        $bDelete = false;
        $mValue = $this->resolveForTemplate($sCond, self::currentTemplateMarkers($this->getFormId()));

        if (AMEOSFORMIDABLE_LEXER_FAILED === $mValue || AMEOSFORMIDABLE_LEXER_BREAKED === $mValue) {
            // boxes are rendered before the whole template,
            // and therefore might define perimeters on conditions
            // that will be evaluable only later in the process
            // if deletion is not asked, we keep the failed perimeters intact
            // to give a chance to later passes to catch it

            if (true === $bClearNotUsed) {
                $bDelete = true;    // deletion
            } else {
                return $aMatch[0];    // keep it intact, to give a chance to later passes to catch it
            }
        } elseif (is_array($mValue)) {
            if (array_key_exists('__compiled', $mValue)) {
                if ('' === trim($mValue['__compiled'])) {
                    $bDelete = true;
                }
            } elseif (empty($mValue)) {
                $bDelete = true;
            }
        } else {
            $bDelete = (is_string($mValue) && ('' === trim($mValue))) || (is_bool($mValue) && false === $mValue);
        }

        if (true === $bDelete) {
            return '';
        }

        $sHtml = $this->processPerimeters($aMatch[3]);

        return $sHtml;
    }

    public function parseTemplateMethodArgs($sArgs)
    {
        $aParams = [];
        $sArgs = trim($sArgs);
        if ('' !== $sArgs) {
            $aArgs = \Sys25\RnBase\Utility\Strings::trimExplode(',', $sArgs);
            reset($aArgs);
            foreach ($aArgs as $sArg) {
                $sTrimArg = trim($sArg);

                if (('"' === $sTrimArg[0] && '"' === $sTrimArg[strlen($sTrimArg) - 1]) || ("'" === $sTrimArg[0] && "'" === $sTrimArg[strlen($sTrimArg) - 1])) {
                    $aParams[] = substr($sTrimArg, 1, -1);
                } elseif (is_numeric($sTrimArg)) {
                    $aParams[] = ($sTrimArg + 0);
                } else {
                    $aParams[] = $this->resolveForTemplate($sTrimArg);
                }
            }
        }

        reset($aParams);

        return $aParams;
    }

    private static function str_split($text, $split = 1)
    {
        // place each character of the string into an array
        $array = [];
        for ($i = 0; $i < strlen($text); ++$i) {
            $key = '';
            for ($j = 0; $j < $split; ++$j) {
                $key .= $text[$i + $j];
            }
            $i = $i + $j - 1;
            array_push($array, $key);
        }

        return $array;
    }

    public function processMarkersCallBack($aMatch, $bClearNotUsed, $bThrusted)
    {
        $sCatch = $aMatch[1];
        $aTags = self::currentTemplateMarkers($this->getFormId());

        if (('L' === $sCatch[0] && 'L' === $sCatch[1] && 'L' === $sCatch[2]) && (':' === $sCatch[3])) {
            if (isset($this)) {
                return $this->getForm()->getConfigXML()->getLLLabel($sCatch);
            }
        } else {
            if (AMEOSFORMIDABLE_LEXER_FAILED !== ($mVal = $this->resolveForTemplate($sCatch, $aTags)) && AMEOSFORMIDABLE_LEXER_BREAKED !== $mVal) {
                if (is_array($mVal)) {
                    if (array_key_exists('__compiled', $mVal)) {
                        if ($bThrusted) {
                            return $mVal['__compiled'];
                        } else {
                            return $this->sanitizeStringForTemplateEngine($mVal['__compiled']);
                        }
                    } else {
                        return '';
                    }
                } else {
                    if ($bThrusted) {
                        return $mVal;
                    } else {
                        return $this->sanitizeStringForTemplateEngine($mVal);
                    }
                }
            } else {
                // nothing
                if ($bClearNotUsed) {
                    return '';
                } else {
                    if ($bThrusted) {
                        return $aMatch[0];
                    } else {
                        return $this->sPfxBegin.$aMatch[1].$this->sPfxEnd;
                    }
                }
            }
        }
    }

    public function processMarkers($sHtml, $bClearNotUsed = true, $bThrusted = false)
    {
        $sPattern = '/{([^\{\}\n]*)}/';
        if (true === $bThrusted) {
            $sCbk = (true === $bClearNotUsed) ? 'processMarkersCallBackClearNotUsedThrusted' : 'processMarkersCallBackKeepNotUsedThrusted';
        } else {
            $sCbk = (true === $bClearNotUsed) ? 'processMarkersCallBackClearNotUsed' : 'processMarkersCallBackKeepNotUsed';
        }
        $sHtml = preg_replace_callback($sPattern, [&$this, $sCbk], $sHtml, -1);

        return $sHtml;
    }

    public function processMarkersCallBackClearNotUsed($aMatch)
    {
        return $this->processMarkersCallBack($aMatch, true, false);
    }

    public function processMarkersCallBackKeepNotUsed($aMatch)
    {
        return $this->processMarkersCallBack($aMatch, false, false);
    }

    public function processMarkersCallBackClearNotUsedThrusted($aMatch)
    {
        return $this->processMarkersCallBack($aMatch, true, true);
    }

    public function processMarkersCallBackKeepNotUsedThrusted($aMatch)
    {
        return $this->processMarkersCallBack($aMatch, false, true);
    }

    /**
     * [Describe function...].
     *
     * @param [type] $$sHtml:        ...
     * @param [type] $aTags:         ...
     * @param [type] $aExclude:      ...
     * @param [type] $bClearNotUsed: ...
     * @param [type] $aLabels:       ...
     *
     * @return [type] ...
     */
    public function parseTemplateCode($sHtml, $aTags, $aExclude = [], $bClearNotUsed = true, $aLabels = [], $bThrusted = false)
    {
        $sHtml = \Sys25\RnBase\Frontend\Marker\Templates::includeSubTemplates($sHtml);

        if (!isset($this->sPfxBegin)) {
            $this->sPfxBegin = md5(rand());
            $this->sPfxEnd = md5(rand());
        }

        if (!is_array($aTags)) {
            $aTags = [];
        }

        $this->pushTemplateMarkers($aTags);

        if (count($aExclude) > 0) {
            $sExcludePfx = md5(microtime(true));
            $sExcludePfx2 = md5(microtime(true) + 1);

            reset($aExclude);
            foreach ($aExclude as $tag) {
                $sHtml = str_replace('{'.$tag.'}', $sExcludePfx.$tag.$sExcludePfx, $sHtml);
                $sHtml = str_replace('{'.$tag.'.label}', $sExcludePfx2.$tag.$sExcludePfx2, $sHtml);
            }
        }
        reset($aTags);
        foreach ($aTags as $sName => $mVal) {
            if ('' !== ($sRdtSubPart = \Sys25\RnBase\Frontend\Marker\Templates::getSubpart($sHtml, '###'.$sName.'###'))) {
                $sHtml = \Sys25\RnBase\Frontend\Marker\Templates::substituteSubpart(
                    $sHtml,
                    '###'.$sName.'###',
                    $mVal['__compiled'],
                    false,
                    false
                );
            }
        }

        $sHtml = $this->processForEaches($sHtml);
        $sHtml = $this->processWithAs($sHtml);
        $sHtml = $this->processPerimeters(
            $sHtml,
            $bClearNotUsed
        );

        $sHtml = $this->processMarkers(
            $sHtml,
            $bClearNotUsed,
            $bThrusted
        );

        if (count($aExclude) > 0) {
            reset($aExclude);
            foreach ($aExclude as $tag) {
                $sHtml = str_replace($sExcludePfx.$tag.$sExcludePfx, '{'.$tag.'}', $sHtml);
                $sHtml = str_replace($sExcludePfx2.$tag.$sExcludePfx2, '{'.$tag.'.label}', $sHtml);
            }
        }

        $this->pullTemplateMarkers();

        if ($bClearNotUsed) {
            $sHtml = str_replace(
                [
                    $this->sPfxBegin, $this->sPfxEnd,
                ],
                [
                    '{', '}',
                ],
                $sHtml
            );

            $sHtml = preg_replace("|{[^\{\}\n]*}|", '', $sHtml);
        }

        // call module markers, so Labels and Modules can be rendered
        // disable the cache
        \Sys25\RnBase\Frontend\Marker\Templates::disableSubstCache();
        $markerArray = $subpartArray = $wrappedSubpartArray = $params = [];
        // check for Module markers
        $formatter = $this->getForm()->getConfigurations()->getFormatter();
        \Sys25\RnBase\Frontend\Marker\BaseMarker::callModules(
            $sHtml,
            $markerArray,
            $subpartArray,
            $wrappedSubpartArray,
            $params,
            $formatter
        );
        // render the module markers
        $sHtml = \Sys25\RnBase\Frontend\Marker\BaseMarker::substituteMarkerArrayCached(
            $sHtml,
            $markerArray,
            $subpartArray,
            $wrappedSubpartArray
        );

        return $sHtml;
    }

    /**
     * Wird nochmal im mainrenderlet verwendet.
     *
     * @param string $sString
     *
     * @return string
     */
    public static function sanitizeStringForTemplateEngine($sString)
    {
        return str_replace(['{', '}'], ['&#123;', '&#125;'], $sString);
    }

    private function resolveForTemplate($sPath, $aConf = false, $mStackedValue = [])
    {
        if (false === $aConf) {
            $aConf = self::currentTemplateMarkers($this->getFormId());
        }

        return $this->resolveScripting('templatemethods', $sPath, $aConf, $mStackedValue);
    }

    public function pushTemplateMarkers($aMarkers)
    {
        if (!isset($GLOBALS['tx_ameosformidable'][$this->formid]['aTags'])) {
            $GLOBALS['tx_ameosformidable'][$this->getFormId()]['aTags'] = [];
        }

        $GLOBALS['tx_ameosformidable'][$this->getFormId()]['aTags'][] = $aMarkers;
    }

    public function pullTemplateMarkers()
    {
        return array_pop($GLOBALS['tx_ameosformidable'][$this->getFormId()]['aTags']);
    }

    /**
     * Liefert Werte aus dem Array $GLOBALS['tx_ameosformidable'][$formId]['aTags'].
     *
     * @param $formId
     *
     * @return array
     */
    private static function currentTemplateMarkers($formId)
    {
        if (!isset($GLOBALS['tx_ameosformidable'][$formId]['aTags'])) {
            return [];
        }

        if (empty($GLOBALS['tx_ameosformidable'][$formId]['aTags'])) {
            return [];
        }

        $iCount = count($GLOBALS['tx_ameosformidable'][$formId]['aTags']);

        return $GLOBALS['tx_ameosformidable'][$formId]['aTags'][$iCount - 1];
    }

    /**
     * Aufruf in Form->resolveForMajixParams und Rekursiv!
     *
     * @param unknown_type $sInterpreter
     * @param unknown_type $sPath
     * @param mixed        $aConf         hier wird ein Widget als Objekt �bergeben...
     * @param unknown_type $mStackedValue
     *
     * @return unknown
     */
    public function resolveScripting($sInterpreter, $sPath, $aConf = false, $mStackedValue = [])
    {
        //		tx_mkforms_util_Div::debugBT4ajax();

        $aRes = self::parseForTemplate($sPath);
        $aValue = $aConf;
        reset($aRes);
        foreach ($aRes as $i => $aExp) {
            if (AMEOSFORMIDABLE_LEXER_FAILED === $aValue || AMEOSFORMIDABLE_LEXER_BREAKED === $aValue) {
                // throwing exception to notify that lexer has failed or has breaked
                return $aValue;
            }

            $sTrimExpr = trim($aExp['expr']);

            if (true === $aExp['rec']) {
                if ('"' == $sTrimExpr[0] && '"' == $sTrimExpr[strlen($sTrimExpr) - 1]) {
                    $aValue = substr($sTrimExpr, 1, -1);
                } else {
                    $aBeforeValue = $aValue;

                    $aValue = $this->resolveScripting($sInterpreter, $aExp['args'], $aConf, $aValue);

                    if (!is_array($aValue)) {
                        $sExecString = $aExp['expr'].'("'.$aValue.'")';
                    } else {
                        $sExecString = $aExp['expr'];
                    }

                    $aValue = $this->resolveScripting($sInterpreter, $sExecString, $aBeforeValue);
                }
            } else {
                if ('"' == $sTrimExpr[0] && '"' == $sTrimExpr[strlen($sTrimExpr) - 1]) {
                    $aValue = substr($sTrimExpr, 1, -1);
                } else {
                    $sExecString = $aExp['expr'];
                    if (false !== $aExp['args']) {
                        $sExecString .= '('.trim($aExp['args']).')';
                    }

                    if (array_key_exists($i + 1, $aRes)) {
                        $aNextExp = $aRes[$i + 1];
                        $sNextExecString = $aNextExp['expr'];
                        if (false !== $aNextExp['args']) {
                            $sNextExecString .= '('.trim($aNextExp['args']).')';
                        }
                    } else {
                        $sNextExecString = false;
                    }

                    $aValue = $this->resolveScripting_atomic($sInterpreter, $sExecString, $aValue, $sNextExecString);
                }
            }
        }

        return $aValue;
    }

    private static function isTemplateMethod($sToken)
    {
        $aRes = [];
        $bMatch = (0 !== preg_match("@^[\w]+\((.|\n)*\)$@", $sToken, $aRes));

        return $bMatch;
    }

    // $mData is the data that comes from the preceding method in the chained execution
    // ex: "hello".concat(", world") => "hello" is $mData, and ", world" the arguments
    private function executeTemplateMethod($sInterpreter, $mData, $sCall)
    {
        $sMethod = substr($sCall, 0, strpos($sCall, '('));
        $sArgs = trim(substr(strstr($sCall, '('), 1, -1));

        $sClassPath = 'class.'.$sInterpreter.'.php';

        require_once __DIR__.'/../api/'.$sClassPath;
        // TODO: Was ist das??
        // wird für template scripting benötigt
        // Bsp.: <!-- ###jobads-subtype.formData("jobads-maintype").equals(501) perimeter### begin-->
        $oMethods = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('formidable_'.$sInterpreter);
        $oMethods->_init($this->form);

        return $oMethods->process($sMethod, $mData, $sArgs);
    }

    public function resolveScripting_atomic($sInterpreter, $sPath, $aConf = AMEOSFORMIDABLE_LEXER_VOID, $sNextPath = false)
    {
        if (AMEOSFORMIDABLE_LEXER_VOID === $aConf) {
            $aConf = self::currentTemplateMarkers($this->getFormId());
        }

        if (is_array($aConf)) {
            reset($aConf);
        }

        $curZone = $aConf;
        if ($this->isTemplateMethod($sPath)) {
            $curZone = $this->executeTemplateMethod($sInterpreter, $curZone, $sPath);
            if (AMEOSFORMIDABLE_LEXER_FAILED === $curZone || AMEOSFORMIDABLE_LEXER_BREAKED === $curZone) {
                return AMEOSFORMIDABLE_LEXER_FAILED;
            }
        } else {
            if (!is_array($curZone)) {
                return $curZone;
            }

            if (array_key_exists($sPath, $curZone) && array_key_exists($sPath.'.', $curZone)) {
                // ambiguous case: both "token" and "token." exists in the data array
                /*
                    algo:
                        if there's a next token asked after this one
                            if "nexttoken" exists in "token."
                                current zone become "token."
                            else
                                current zone become "token"
                        else
                            current zone become "token"

                */

                if (false !== $sNextPath) {
                    $curZone = (array_key_exists($sNextPath, $curZone[$sPath.'.'])) ? $curZone[$sPath.'.'] : $curZone[$sPath];
                } else {
                    $curZone = $curZone[$sPath];
                }
            } elseif (array_key_exists($sPath, $curZone)) {
                $curZone = $curZone[$sPath];
            } elseif (array_key_exists($sPath.'.', $curZone)) {
                $curZone = $curZone[$sPath.'.'];
            } else {
                return AMEOSFORMIDABLE_LEXER_FAILED;
            }
        }

        return $curZone;
    }

    /**
     * @param tx_mkforms_forms_IForm $form
     *
     * @return tx_mkforms_util_Templates
     */
    public static function createInstance(tx_mkforms_forms_IForm $form)
    {
        $runnable = new tx_mkforms_util_Templates($form);

        return $runnable;
    }
}
