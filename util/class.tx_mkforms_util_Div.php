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
 * Some static util functions.
 */
class tx_mkforms_util_Div
{
    public static function isAbsServerPath($sPath)
    {
        $sServerRoot = \Sys25\RnBase\Utility\T3General::getIndpEnv('TYPO3_DOCUMENT_ROOT');

        return substr($sPath, 0, strlen($sServerRoot)) === $sServerRoot;
    }

    public static function isAbsPath($sPath)
    {
        return '/' === $sPath[0];
    }

    public static function isAbsWebPath($sPath)
    {
        return ('http://' === substr(strtolower($sPath), 0, 7)) || ('https://' === substr(strtolower($sPath), 0, 8));
    }

    /**
     * Returns the current TYPO3 Mode: CLI, FE, BE or EID.
     *
     * @return string
     */
    public static function getEnvExecMode()
    {
        if (\Sys25\RnBase\Utility\TYPO3::isCliMode()) {
            return 'CLI';
        } elseif (TYPO3_MODE == 'BE') {
            return TYPO3_MODE;
        }

        return (is_null(\Sys25\RnBase\Utility\T3General::_GP('mkformsAjaxId'))) ? 'FE' : 'EID';
    }

    /**
     * Ein Array in Items für Formular-Widgets umwandeln.
     *
     * @param [type] $aData: ...
     *
     * @return [type] ...
     */
    public static function arrayToRdtItems($aData, $sCaptionMap = false, $sValueMap = false)
    {
        $aItems = [];

        //wenn wir kein Array haben dann gibts nix zu tun
        //sonst knallts bei reset() in tests!!!
        if (!is_array($aData)) {
            return $aItems;
        }

        reset($aData);

        if (false !== $sCaptionMap && false !== $sValueMap) {
            foreach ($aData as $sKey => $notNeeded) {
                $aItems[] = [
                    'value' => $aData[$sKey][$sValueMap],
                    'caption' => $aData[$sKey][$sCaptionMap],
                ];
            }
        } else {
            foreach ($aData as $sValue => $sCaption) {
                $aItems[] = [
                    'value' => $sValue,
                    'caption' => $sCaption,
                ];
            }
        }

        reset($aItems);

        return $aItems;
    }

    /**
     *  taken from http://drupal.org/node/66183.
     */
    public static function array_insert($arr1, $key, $arr2, $before = false)
    {
        $arr1 = is_array($arr1) ? $arr1 : [];
        $index = array_search($key, array_keys($arr1), true);
        if (false === $index) {
            $index = count($arr1); // insert @ end of array if $key not found
        } else {
            if (!$before) {
                ++$index;
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
     * @param string $$clazzname classname
     *
     * @return string
     */
    public static function getExtRelPath($clazzname)
    {
        $infos = tx_rnbase::getClassInfo($clazzname);

        return \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix(
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($infos['extkey'])
        ).$infos['dir'];
    }

    /**
     * [Describe function...].
     *
     * @param [type] $mInfos: ...
     *
     * @return [type] ...
     */
    public static function getExtPath($clazzname)
    {
        $infos = tx_rnbase::getClassInfo($clazzname);

        return \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($infos['extkey']).$infos['dir'];
    }

    /**
     * Stops Formidable and PHP execution : die() if some critical error appeared.
     *
     * @param string             $msg: the error message
     * @param tx_ameosformidable $form
     */
    public static function mayday($msg, $form = false)
    {
        // Wir nutzen die Mayday-Methode von rn_base.
        // Konfigurationen wie forceException4Mayday,
        // verboseMayday und dieOnMayday werden so mit beachtet.

        $aDebug = ['<h2>MKFORMS:</h2><p><strong>'.$msg.'</strong></p>'];
        if ($form) {
            $aDebug[] = "<span class='notice'><strong>XML: </strong> ".$form->_xmlPath.'</span><br />';
            $aDebug[] = "<span class='notice'><strong>MKFORMS Version: </strong>v".self::getVersion().'</span><br />';
            $aDebug[] = "<span class='notice'><strong>Total exec. time: </strong>".round(\Sys25\RnBase\Utility\T3General::milliseconds() - $form->start_tstamp, 4) / 1000 .' sec</span><br />';
        }
        $aDebug[] = '<br />';

        $aDebug[] = '<span class="notice"><strong>debug trail: </strong></span><ol>';

        foreach (\Sys25\RnBase\Utility\Strings::trimExplode('//', \Sys25\RnBase\Utility\Debug::getDebugTrail()) as $bt) {
            $aDebug[] = "\t<li>".$bt.'</li>';
        }
        $aDebug[] = '</ol>';

        $sDebug = implode('', $aDebug);

        // email senden
        $addr = \Sys25\RnBase\Configuration\Processor::getExtensionCfgValue('rn_base', 'sendEmailOnException');
        if ($addr) {
            $exception = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mkforms_exception_Mayday', $msg, -1, $sDebug);
            \Sys25\RnBase\Utility\Misc::sendErrorMail($addr, $form ? get_class($form).' FormId:'.$form->getFormId() : __CLASS__, $exception);
        }

        // beim ajaxcall nur die meldung ausgeben und fertig!
        if ('EID' === self::getEnvExecMode()) {
            exit("Formidable::Mayday\n\n".trim(strip_tags($msg)));
        }

        \Sys25\RnBase\Utility\Misc::mayday($sDebug, 'mkforms');
    }

    /**
     * [Describe function...].
     *
     * @param [type] $mMixed:     ...
     * @param [type] $bRecursive: ...
     * @param [type] $iLevel:     ...
     *
     * @return [type] ...
     */
    public static function viewMixed($mMixed, $bRecursive = true, $iLevel = 0)
    {
        $sStyle = 'font-family: Verdana; font-size: 9px;';
        $sStyleBlack = $sStyle.'color: black;';
        $sStyleRed = $sStyle.'color: red;';
        $sStyleGreen = $sStyle.'color: green;';

        $aBgColors = [
            'FFFFFF', 'F8F8F8', 'EEEEEE', 'E7E7E7', 'DDDDDD', 'D7D7D7', 'CCCCCC', 'C6C6C6', 'BBBBBB', 'B6B6B6', 'AAAAAA', 'A5A5A5', '999999', '949494', '888888', '848484', '777777', '737373',
        ];

        if (is_array($mMixed)) {
            $result = "<table border=1 style='border: 1px solid silver' cellpadding=1 cellspacing=0 bgcolor='#".$aBgColors[$iLevel]."'>";

            if (!count($mMixed)) {
                $result .= "<tr><td><span style='".$sStyleBlack."'><b>".htmlspecialchars('EMPTY!').'</b></span></td></tr>';
            } else {
                foreach ($mMixed as $key => $val) {
                    $result .= "<tr><td valign='top'><span style='".$sStyleBlack."'>".htmlspecialchars((string) $key).'</span></td><td>';

                    if (is_array($val)) {
                        $result .= self::viewMixed($val, $bRecursive, $iLevel + 1);
                    } else {
                        $result .= "<span style='".$sStyleRed."'>".self::viewMixed($val, $bRecursive, $iLevel + 1).'<br /></span>';
                    }

                    $result .= '</td></tr>';
                }
            }

            $result .= '</table>';
        } elseif (is_resource($mMixed)) {
            $result = "<span style='".$sStyleGreen."'>RESOURCE: </span>".$mMixed;
        } elseif (is_object($mMixed)) {
            if ($bRecursive) {
                $result = "<span style='".$sStyleGreen."'>OBJECT (".get_class($mMixed).') : </span>'.self::viewMixed(get_object_vars($mMixed), false, $iLevel + 1);
            } else {
                $result = "<span style='".$sStyleGreen."'>OBJECT (".get_class($mMixed).') : !RECURSION STOPPED!</span>';
            }
        } elseif (is_bool($mMixed)) {
            $result = "<span style='".$sStyleGreen."'>BOOLEAN: </span>".($mMixed ? 'TRUE' : 'FALSE');
        } elseif (is_string($mMixed)) {
            if (empty($mMixed)) {
                $result = "<span style='".$sStyleGreen."'>STRING(0)</span>";
            } else {
                $result = "<span style='".$sStyleGreen."'>STRING(".strlen($mMixed).'): </span>'.nl2br(htmlspecialchars((string) $mMixed));
            }
        } elseif (is_null($mMixed)) {
            $result = "<span style='".$sStyleGreen."'>!NULL!</span>";
        } elseif (is_integer($mMixed)) {
            $result = "<span style='".$sStyleGreen."'>INTEGER: </span>".$mMixed;
        } else {
            $result = "<span style='".$sStyleGreen."'>MIXED: </span>".nl2br(htmlspecialchars((string) $mMixed));
        }

        return $result;
    }

    public static function isDebugIP()
    {
        return \Sys25\RnBase\Utility\T3General::cmpIP(\Sys25\RnBase\Utility\T3General::getIndpEnv('REMOTE_ADDR'), $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']);
    }

    /**
     * Debug a value in Ajax-Mode.
     *
     * @param mixed  $mVar
     * @param string $sTitle
     */
    public static function debug4ajax($mVar, $sTitle = null)
    {
        echo '/*';
        if (!is_null($sTitle)) {
            echo $sTitle.' | ';
        }
        print_r($mVar);
        echo '*/'."\r\n";
    }

    /**
     * Ausgabe des Backtrace in Ajax.
     */
    public static function debugBT4ajax()
    {
        $aTrace = debug_backtrace();
        $aLocation = array_shift($aTrace);
        $aTrace1 = array_shift($aTrace);
        $aTrace2 = array_shift($aTrace);
        $aTrace3 = array_shift($aTrace);
        $aTrace4 = array_shift($aTrace);
        $aTrace5 = array_shift($aTrace);
        $aTrace6 = array_shift($aTrace);
        $aTrace7 = array_shift($aTrace);
        $aTrace8 = array_shift($aTrace);
        $aTrace9 = array_shift($aTrace);
        $aTrace0 = array_shift($aTrace);

        $aDebug = [];
        $aDebug[] = 'Call  0: '.str_replace(\Sys25\RnBase\Utility\Environment::getPublicPath(), '/', $aLocation['file']).':'.$aLocation['line'].' | '.$aTrace1['class'].$aTrace1['type'].$aTrace1['function'];
        $aDebug[] = 'Call -1: '.str_replace(\Sys25\RnBase\Utility\Environment::getPublicPath(), '/', $aTrace1['file']).':'.$aTrace1['line'].' | '.$aTrace2['class'].$aTrace2['type'].$aTrace2['function'];
        $aDebug[] = 'Call -2: '.str_replace(\Sys25\RnBase\Utility\Environment::getPublicPath(), '/', $aTrace2['file']).':'.$aTrace2['line'].' | '.$aTrace3['class'].$aTrace3['type'].$aTrace3['function'];
        $aDebug[] = 'Call -3: '.str_replace(\Sys25\RnBase\Utility\Environment::getPublicPath(), '/', $aTrace3['file']).':'.$aTrace3['line'].' | '.$aTrace4['class'].$aTrace4['type'].$aTrace4['function'];
        $aDebug[] = 'Call -4: '.str_replace(\Sys25\RnBase\Utility\Environment::getPublicPath(), '/', $aTrace4['file']).':'.$aTrace4['line'].' | '.$aTrace5['class'].$aTrace5['type'].$aTrace5['function'];

        $aDebug[] = 'Call -5: '.str_replace(\Sys25\RnBase\Utility\Environment::getPublicPath(), '/', $aTrace5['file']).':'.$aTrace5['line'].' | '.$aTrace6['class'].$aTrace6['type'].$aTrace6['function'];
        $aDebug[] = 'Call -6: '.str_replace(\Sys25\RnBase\Utility\Environment::getPublicPath(), '/', $aTrace6['file']).':'.$aTrace6['line'].' | '.$aTrace7['class'].$aTrace7['type'].$aTrace7['function'];
        $aDebug[] = 'Call -7: '.str_replace(\Sys25\RnBase\Utility\Environment::getPublicPath(), '/', $aTrace7['file']).':'.$aTrace7['line'].' | '.$aTrace8['class'].$aTrace8['type'].$aTrace8['function'];
        $aDebug[] = 'Call -8: '.str_replace(\Sys25\RnBase\Utility\Environment::getPublicPath(), '/', $aTrace8['file']).':'.$aTrace8['line'].' | '.$aTrace9['class'].$aTrace9['type'].$aTrace9['function'];
        $aDebug[] = 'Call -9: '.str_replace(\Sys25\RnBase\Utility\Environment::getPublicPath(), '/', $aTrace9['file']).':'.$aTrace9['line'].' | '.$aTrace0['class'].$aTrace0['type'].$aTrace0['function'];
        self::debug4ajax($aDebug);
    }

    /**
     * Internal debug function
     * Calls the TYPO3 debug function if the XML conf sets /formidable/meta/debug/ to TRUE.
     *
     * @param mixed              $variable: the variable to dump
     * @param string             $name:     title of this debug section
     * @param tx_ameosformidable $form
     * @param bool               $bAnalyze: detailierter debug?
     */
    public static function debug($variable, $name, $form, $bAnalyze = true)
    {
        if ($form->getConfig()->isDebug() || $form->bDebug) {
            $aTrace = debug_backtrace();
            $aLocation = array_shift($aTrace);
            $aTrace1 = array_shift($aTrace);
            $aTrace2 = array_shift($aTrace);
            $aTrace3 = array_shift($aTrace);
            $aTrace4 = array_shift($aTrace);

            $numcall = sizeof($form->aDebug) + 1;

            $aDebug = [];
            $aDebug[] = "<p align = 'right'><a href = '#".$form->formid."formidable_debugtop' target = '_self'>^top^</a></p>";
            $aDebug[] = "<a name = '".$form->formid.'formidable_call'.$numcall."' />";
            $aDebug[] = "<a href = '#".$form->formid.'formidable_call'.($numcall - 1)."'>&lt;&lt; prev</a> / <a href = '#".$form->formid.'formidable_call'.($numcall + 1)."'>next &gt;&gt;</a><br>";
            $aDebug[] = '<strong>#'.$numcall.' - '.$name.'</strong>';
            $aDebug[] = '<br/>';
            $aDebug[] = "<span style='font-family: verdana;font-size: 9px; font-style: italic;'><b>(Total exec. time: </b>".round(\Sys25\RnBase\Utility\T3General::milliseconds() - $form->start_tstamp, 4) / 1000 .' sec)</span>';
            $aDebug[] = '<br/>';

            $aDebug[] = "<a href='javascript:void(Formidable.f(\"".$form->formid.'").toggleBacktrace('.$numcall."))'>Toggle details</a><br>";
            $aDebug[] = "<div id='".$form->formid.'_formidable_call'.$numcall."_backtrace' style='display: none; background-color: #FFFFCC' >";

            if (!$form->getConfig()->isDebugLight()) {
                $aDebug[] = "<span style='font-family: verdana;font-size: 9px; font-style: italic;'><b>Call 0: </b>".str_replace(\Sys25\RnBase\Utility\Environment::getPublicPath(), '/', $aLocation['file']).':'.$aLocation['line'].' | <b>'.$aTrace1['class'].$aTrace1['type'].$aTrace1['function'].'</b></span><br>'.self::viewMixed($aTrace1['args']);
                $aDebug[] = '<hr/>';
                $aDebug[] = "<span style='font-family: verdana;font-size: 9px; font-style: italic;'><b>Call -1: </b>".str_replace(\Sys25\RnBase\Utility\Environment::getPublicPath(), '/', $aTrace1['file']).':'.$aTrace1['line'].' | <b>'.$aTrace2['class'].$aTrace2['type'].$aTrace2['function'].'</b></span><br>'.self::viewMixed($aTrace2['args']);
                $aDebug[] = '<hr/>';
                $aDebug[] = "<span style='font-family: verdana;font-size: 9px; font-style: italic;'><b>Call -2: </b>".str_replace(\Sys25\RnBase\Utility\Environment::getPublicPath(), '/', $aTrace2['file']).':'.$aTrace2['line'].' | <b>'.$aTrace3['class'].$aTrace3['type'].$aTrace3['function'].'</b></span><br>'.self::viewMixed($aTrace3['args']);
                $aDebug[] = '<hr/>';
                $aDebug[] = "<span style='font-family: verdana;font-size: 9px; font-style: italic;'><b>Call -3: </b>".str_replace(\Sys25\RnBase\Utility\Environment::getPublicPath(), '/', $aTrace3['file']).':'.$aTrace3['line'].' | <b>'.$aTrace4['class'].$aTrace4['type'].$aTrace4['function'].'</b></span><br>'.self::viewMixed($aTrace4['args']);
                $aDebug[] = '<hr/>';
            }

            if (is_string($variable)) {
                $aDebug[] = $variable;
            } else {
                if ($bAnalyze) {
                    $aDebug[] = self::viewMixed($variable);
                } else {
                    $aDebug[] = \Sys25\RnBase\Utility\Debug::viewArray($variable);
                }
            }

            $aDebug[] = '</div>';

            $aDebug[] = '<br/>';

            // @TODO wo wird das ausgegeben!?
            $form->aDebug[] = implode('', $aDebug);
        }
    }

    public static function smartMayday_XmlFile($sPath, $sMessage = false)
    {
        $sVersion = self::getVersion();
        $sXml = <<<XMLFILE
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

        if (false === $sMessage) {
            $sMessage = 'FORMIDABLE CORE - The given FML file path (<b>'.$sPath.'</b>) does not exist';
        }

        $sXml = htmlspecialchars($sXml);
        $sMayday = <<<ERRORMESSAGE

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

    public static function smartMayday_CBJavascript($sPath, $sClassName, $sMessage = false)
    {
        $sJs = <<<XMLFILE
Formidable.Classes.{$sClassName} = Formidable.Classes.CodeBehindClass.extend({

	init: function() {
		// your init code here
	},
	doSomething: function() {
		// your implementation here
	}

});
XMLFILE;

        if (false === $sMessage) {
            $sMessage = 'FORMIDABLE CORE - The given javascript CodeBehind file path (<b>'.$sPath.'</b>) does not exist';
        }

        $sJs = htmlspecialchars($sJs);
        $sMayday = <<<ERRORMESSAGE

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
     * Returns the current version of formidable running.
     *
     * @return string current mkforms version number
     */
    public static function getVersion()
    {
        static $version = null;
        if (null === $version) {
            $version = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionVersion('mkforms');
        }

        return $version;
    }

    public static function getVersionInt()
    {
        static $version = null;
        if (null === $version) {
            $version = \Sys25\RnBase\Utility\TYPO3::convertVersionNumberToInteger(self::getVersion());
        }

        return $version;
    }

    /**
     * Returns mkformsAjaxId for Ajax calls.
     *
     * @return string
     */
    public static function getAjaxEId()
    {
        return 'tx_mkforms_ajax';
    }

    /**
     * [Describe function...].
     *
     * @param [type] $sPath: ...
     *
     * @return [type] ...
     */
    public static function toWebPath($sPath)
    {
        if (\Sys25\RnBase\Utility\Strings::isFirstPartOfStr(strtolower($sPath), 'http://') || \Sys25\RnBase\Utility\Strings::isFirstPartOfStr(strtolower($sPath), 'https://')) {
            return $sPath;
        }

        return self::removeEndingSlash(\Sys25\RnBase\Utility\T3General::getIndpEnv('TYPO3_SITE_URL')).'/'.self::removeStartingSlash(self::toRelPath($sPath));
    }

    /**
     * Converts an absolute or EXT path to a relative path plus a leading slash.
     *
     * @param string $sPath: the path to convert, must be either an
     *                       absolute path or a path starting with 'EXT:'
     *
     * @return string $sPath converted to a relative path plus a leading
     *                slash
     */
    public static function toRelPath($sPath)
    {
        if ('EXT:' === substr($sPath, 0, 4)) {
            $sPath = \Sys25\RnBase\Utility\T3General::getFileAbsFileName($sPath);
        }
        $sPath = str_replace(\Sys25\RnBase\Utility\Environment::getPublicPath(), '', $sPath);
        if ('/' != $sPath[0]) {
            $sPath = '/'.$sPath;
        }

        return $sPath;
    }

    /**
     * Make an absolute server path.
     *
     * @param string $sPath: ...
     *
     * @return string
     */
    public static function toServerPath($sPath)
    {
        // removes the leading slash so the path _really_ is relative
        $sPath = self::removeStartingSlash(self::toRelPath($sPath));

        if (file_exists($sPath) && is_dir($sPath) && ('/' !== $sPath[(strlen($sPath) - 1)])) {
            $sPath .= '/';
        }

        return self::removeEndingSlash(\Sys25\RnBase\Utility\Environment::getPublicPath()).'/'.$sPath;
    }

    public static function removeStartingSlash($sPath)
    {
        return ('/' === $sPath[0]) ? substr($sPath, 1) : $sPath;
    }

    public static function removeEndingSlash($sPath)
    {
        return preg_replace('|/$|', '', $sPath);
    }

    public static function trimSlashes($sPath)
    {
        return self::removeStartingSlash(self::removeEndingSlash($sPath));
    }

    /**
     * Binary-reads a file.
     *
     * @param string $sPath: absolute server path to file
     *
     * @return string file contents
     */
    public static function fileReadBin($sPath)
    {
        $sData = '';
        $rFile = fopen($sPath, 'rb');
        while (!feof($rFile)) {
            $sData .= fread($rFile, 1024);
        }
        fclose($rFile);

        return $sData;
    }

    /**
     * Binary-writes a file.
     *
     * @param string $sPath: absolute server path to file
     * @param string $sData: file contents
     * @param bool   $bUTF8: add UTF8-BOM or not ?
     */
    public static function fileWriteBin($sPath, $sData, $bUTF8 = true)
    {
        $rFile = fopen($sPath, 'wb');
        if (true === $bUTF8) {
            fputs($rFile, "\xEF\xBB\xBF".$sData);
        } else {
            fputs($rFile, $sData);
        }
        fclose($rFile);
    }

    public static function mkdirDeep($destination, $deepDir)
    {
        $allParts = \Sys25\RnBase\Utility\Strings::trimExplode('/', $deepDir, 1);
        $root = '';
        foreach ($allParts as $part) {
            $root .= $part.'/';
            if (!is_dir($destination.$root)) {
                \Sys25\RnBase\Utility\T3General::mkdir($destination.$root);
                if (!@is_dir($destination.$root)) {
                    return 'Error: The directory "'.$destination.$root.'" could not be created...';
                }
            }
        }
    }

    public static function mkdirDeepAbs($deepDir)
    {
        return self::mkdirDeep('/', $deepDir);
    }

    /**
     * Entfernt aus dem Array alle Keys, die mit einem bestimmten Prefix beginnen.
     * Achtung: Das �bergebene Array wird ver�ndert!
     *
     * @param array  $params
     * @param string $prefix
     *
     * @return array
     */
    public static function removeKeysWithPrefix(&$params, $prefix)
    {
        $keys = array_keys($params);
        foreach ($keys as $key) {
            if (0 === strpos($key, $prefix)) {
                unset($params[$key]);
            }
        }

        return $params;
    }

    /**
     * Sucht in dem Array nach allen Keys, die mit einem bestimmten Prefix beginnen.
     *
     * @param array  $params
     * @param string $prefix
     *
     * @return array ein Array mit den gefundenen Keys
     */
    public static function findKeysWithPrefix($params, $prefix)
    {
        $ret = [];
        $keys = array_keys($params);
        foreach ($keys as $key) {
            if (0 === strpos($key, $prefix)) {
                $ret[] = $key;
            }
        }

        return $ret;
    }

    /**
     * Removes dots in typoscript configuration arrays
     * TODO: Die Methode ist eventuell obsolete, wenn die Configurations für den TS-Zugriff verwendet wird.
     *
     * @param array  $aData:      typoscript conf
     * @param array  $aTemp:      internal use
     * @param string $sParentKey: key in conf currently processed
     *
     * @return array conf without TS dotted notation
     */
    public static function removeDots($aData, $aTemp = false, $sParentKey = false)
    {
        if (false === $aTemp) {
            $aTemp = [];
        }

        foreach ($aData as $key => $val) {
            if (is_array($val)) {
                if ('userobj.' === $sParentKey && 'cobj.' === $key) {
                    $aTemp['cobj'] = $aData['cobj'];
                    $aTemp['cobj.'] = $aData['cobj.'];
                } else {
                    $aTemp[substr($key, 0, -1)] = self::removeDots($val, false, $key);
                }
            } else {
                $aTemp[$key] = $val;
            }
        }

        return $aTemp;
    }

    /**
     * Add dots to an array to have a typoscript-like structure.
     *
     * @param array $aData: plain conf
     * @param array $aTemp
     *
     * @return array typoscript dotted conf
     */
    public static function addDots($aData, $aTemp = false)
    {
        $aTemp = (false === $aTemp) ? [] : $aTemp;
        foreach ($aData as $key => $val) {
            if (is_array($val)) {
                $aTemp[$key.'.'] = self::addDots($val);
            } else {
                $aTemp[$key] = $val;
            }
        }

        return $aTemp;
    }

    public static function getRelExtensionPath($filename)
    {
        if (!self::isExtensionPath($filename)) {
            return $filename;
        }

        list($extKey, $local) = explode('/', substr($filename, 4), 2);
        $filename = '';
        if (strcmp($extKey, '') && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extKey) && strcmp($local, '')) {
            $filename = \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix(
                \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extKey)
            ).$local;
        }

        return $filename;
    }

    public static function isExtensionPath($filename)
    {
        return 'EXT:' == substr($filename, 0, 4);
    }

    /**
     * Erstellt ein Parameter-Array. Der Parameter ist dabei entweder ein String oder ein Array
     * params="name::value"
     * <params><param name="this()" value="this" /></params>
     * Aufpassen: es gibt verschiedene Arten von Parameteraufrufen. Bei den userObjects läuft das anders!
     *
     * @param mixed $mParams String oder Array
     *
     * @return array
     */
    public static function extractParams($mParams)
    {
        $aParamsCollection = [];
        if (false === $mParams) {
            return $aParamsCollection;
        }

        if (is_string($mParams)) {
            // Das ist der Normalfall. Die Parameter als String
            $paramArr = \Sys25\RnBase\Utility\Strings::trimExplode('::', $mParams);
            $aParamsCollection[$paramArr[0]] = (count($paramArr) > 0) ? $paramArr[1] : '';
        } else {
            foreach ($mParams as $mParam) {
                $aParamsCollection[$mParam['name']] = $mParam['value'];
            }
        }

        return $aParamsCollection;
    }

    /**
     * Durchläuft ein Array rekursiv und wendet auf jedes Element
     * urlDecodeByReference an.
     *
     * @param $aArray
     */
    public static function urlDecodeRecursive(&$aArray)
    {
        if (is_array($aArray)) {//nur wenn ein array vorliegt
            array_walk_recursive($aArray, ['tx_mkforms_util_Div', 'urlDecodeByReference']);
        }
    }

    /**
     * Wendet urldecode auf das gegebene Element an. Dabei ist
     * das Element im gegensatz zum original eine referenz
     * womit die Funktion auch als callback verwendet werden kann.
     *
     * @param mixed $item
     * @param mixed $key
     */
    public static function urlDecodeByReference(&$item, $key = null)
    {
        $item = urldecode($item);
    }

    /**
     * Wandelt einen String anhand eines Trennzeichens in CamelCase um.
     *
     * @param string $sString
     * @param string $sDelimiter
     *
     * @return string
     */
    public static function toCamelCase($sString, $sDelimiter = '_', $bLcFirst = false)
    {
        //$sCamelCase = implode('', array_map('ucfirst', explode($sDelimiter, $sString)));
        // das ist schneller als die array_map methode!
        $sCamelCase = '';
        foreach (explode($sDelimiter, $sString) as $sPart) {
            $sCamelCase .= ucfirst($sPart);
        }
        // lcfirst gibt es erst ab php 5.3!
        if ($bLcFirst) {
            $sCamelCase[0] = strtolower($sCamelCase[0]);
        }

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
     *
     * @return string
     */
    public static function cleanupFileName($name)
    {
        if (empty($name) || '' === trim($name, '.')) {
            return '';
        }
        $cleaned = $name;
        if (function_exists('iconv')) {
            $charset = \Sys25\RnBase\Utility\Strings::isUtf8String($cleaned) ? 'UTF-8' : 'ISO-8859-1';
            $oldLocal = setlocale(LC_ALL, 0);
            setlocale(LC_ALL, 'de_DE@euro', 'de_DE', 'deu_deu', 'de', 'ge');
            $cleaned = iconv($charset, 'ASCII//TRANSLIT', $cleaned);
            setlocale(LC_ALL, $oldLocal); //
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
     * Liefert bestimmte Pfade.
     */
    public static function getSetupByKeys($tsSetup, $keys)
    {
        arsort($keys);
        $tsArray = [];
        if (!empty($tsSetup) && is_array($keys)) {
            foreach ($keys as $key => $value) {
                if ('.' === substr($key, -1)) {
                    $tsArray[$key] = self::getSetupByKeys($tsSetup[$key], $value);
                } elseif ($value) {
                    if (array_key_exists($key, $tsSetup)) {
                        $tsArray[$key] = $tsSetup[$key];
                    }
                    if (array_key_exists($key.'.', $tsSetup)) {
                        $tsArray[$key.'.'] = $tsSetup[$key.'.'];
                    }
                }
            }
        }

        return $tsArray;
    }
}
