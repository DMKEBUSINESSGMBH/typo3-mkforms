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
 * Die Klasse ist für die Verarbeitung der XML-Formulardatei verantwortlich.
 * Der Zugriff auf das Form sollte nur reduziert geschehen. Derzeit wird über das Form das Runnable ermittelt.
 */
class tx_mkforms_util_Config
{
    private $debug = -99;
    private $config;

    /**
     * @var array
     */
    private $_aConf;

    private function __construct($form)
    {
        $this->form = $form;
    }

    public function navConf($path, $aConf = -1, $sSep = '/')
    {
        return $this->get($path, $aConf, $sSep);
    }

    private function explodePath($path, $sSep)
    {
        if ($path[0] === $sSep) {
            $path = substr($path, 1);
        }
        $iLen = strlen($path);
        if ($path[$iLen - 1] === $sSep) {
            $path = substr($path, 0, $iLen - 1);
        }

        return explode($sSep, $path);
    }

    /**
     * Liefert einen Wert aus der Config.
     *
     * @param string $path
     * @param array  $aConf
     * @param string $sSep
     *
     * @return mixed
     */
    public function get($path, $aConf = -1, $sSep = '/')
    {
        $curZone = (-1 === $aConf || !is_array($aConf)) ? $this->config : $aConf;
        reset($curZone);

        if ($path === $sSep) {
            return $curZone;
        }
        $aPath = $this->explodePath($path, $sSep);

        $iSize = sizeof($aPath);
        for ($i = 0; $i < $iSize; ++$i) {
            if (is_array($curZone) && array_key_exists($aPath[$i], $curZone)) {
                $curZone = $curZone[$aPath[$i]];
                if (is_string($curZone)) {
                    if ('X' === ($curZone[0] ?? '') && 'XPATH:' === substr($curZone, 0, 6)) {
                        $curZone = $this->xPath($curZone);
                    } elseif ('T' === ($curZone[0] ?? '') && 'TS:' === substr($curZone, 0, 3)) {
                        $sTsPointer = $curZone;
                        $curZone = substr($curZone, 3);
                        if (AMEOSFORMIDABLE_TS_FAILED === ($curZone = $this->getTS($curZone, true))) {
                            tx_mkforms_util_Div::mayday('The typoscript pointer <b>'.$sTsPointer.'</b> evaluation has failed, as the pointed property does not exists within the current Typoscript template');
                        }
                    } elseif ('T' === ($curZone[0] ?? '') && 'TCA:' === substr($curZone, 0, 4)) {
                        $curZone = $this->getTcaVal($curZone);
                    } elseif ('L' === ($curZone[0] ?? '') && 'LLL:' === substr($curZone, 0, 4)) {
                        $curZone = $this->getLLLabel($curZone);
                    } elseif ('E' === ($curZone[0] ?? '') && 'X' === ($curZone[1] ?? '') && 'EXTCONF:' === substr($curZone, 0, 8)) {
                        $curZone = $this->getExtConfVal($curZone);
                    }
                }
            } else {
                return false;
            }
        }

        return $curZone;
    }

    public function getExtConfVal($sExtConf)
    {
        if ('E' === $sExtConf[0] && 'X' === $sExtConf[1] && 'EXTCONF:' === substr($sExtConf, 0, 8)) {
            $sExtConf = substr($sExtConf, 8);
        }

        $sPath = str_replace('.', '/', $sExtConf);
        $sRes = $this->get($sPath, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']);

        return $sRes;
    }

    /**
     * Returns the translated string for the given LLL path.
     *
     * @param mixed $label: LLL path
     *
     * @return string The translated string
     */
    public function getLLLabel($mLabel)
    {
        $mLabel = $this->findLLLabel($mLabel);
        if (is_string($mLabel) && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr(strtoupper($mLabel), 'LABEL_')) {
            $mLabel = $this->getForm()->getConfigurations()->getLL($mLabel, $mLabel);
        }

        return $mLabel;
    }

    /**
     * Returns the translated string for the given LLL path.
     *
     * @param string $label: LLL path
     *
     * @return string The translated string
     */
    private function findLLLabel($mLabel)
    {
        $mLabel = $this->getForm()->getRunnable()->callRunnable($mLabel);

        /*
         * Wenn hier ein Array drin steckt, nicht weiter machen.
         * Kann bei Parametern so sein
         * <param name="links" showjr="jobRequestId" showjobad="jobAdId" editjr="uid" editjobad="uid" />
         */
        if (is_array($mLabel)) {
            return $mLabel;
        }

        // Wenn im meta der XML Form ein defaultLLL gesetzt ist,
        // wird versucht anand des absoluten namens vom renderlet ein label zu finden.
        if (false !== $this->getForm()->sDefaultLLLPrefix) {
            if (\Sys25\RnBase\Utility\Strings::isFirstPartOfStr($mLabel, 'LLL:') && !\Sys25\RnBase\Utility\Strings::isFirstPartOfStr($mLabel, 'LLL:EXT:')) {
                $mLabel = str_replace('LLL:', 'LLL:'.$this->getForm()->sDefaultLLLPrefix.':', $mLabel);
            }
        }

        if ('L' === ($mLabel[0] ?? '') && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($mLabel, 'LLL:')) {
            if (\Sys25\RnBase\Utility\Environment::isFrontend()) {
                // front end
                if (!$GLOBALS['TSFE']) {
                    $message = 'Es gibt kein TSFE aber es soll ein label gesucht werden. Das kann '.
                        'aus folgenden Grund passieren. Man hat ein autocomplete mit childs, '.
                        'ein default LL aber keine label für die childs. Entweder wird kein '.
                        'default LL verwendet oder den childs wird ein label gegeben, die es'.
                        'gar nicht geben muss da diese nicht gerendered werden.';
                    throw new Exception($message, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['baseExceptionCode']. 2);
                }

                return $GLOBALS['TSFE']->sL($mLabel);
            } else {
                // back end
                return $GLOBALS['LANG']->sL($mLabel);
            }
        }

        return $mLabel;
    }

    /**
     * Loads the internal _aConf configuration array from the XML file
     * IMPORTANT NOTE : the root /formidable is deleted, so all pathes shouldn't start with /formidable.
     */
    private function loadXmlConf($xmlPath)
    {
        $this->config = tx_mkforms_util_XMLParser::getXml($xmlPath);

        // root sollte mkforms sein!
        $sRoot = 'mkforms';
        if (!array_key_exists('mkforms', $this->config)) {
            // fallback, da viele XMLs formidable im root stehen haben
            $sRoot = 'formidable';
            if (!array_key_exists('formidable', $this->config)) {
                tx_mkforms_util_Div::mayday('Root "mkforms" not found in XML. ('.$xmlPath.')');
            }
            trigger_error('Root node "mkforms" in "'.$xmlPath.'" missed, but deprecated "formidable" found.', E_USER_DEPRECATED);
        }

        // the root is deleted
        $this->config = $this->config[$sRoot];

        if (false !== ($sXmlMinVersion = $this->get('/minversion', $this->_aConf))) {
            if (tx_mkforms_util_Div::getVersionInt() < \Sys25\RnBase\Utility\TYPO3::convertVersionNumberToInteger($sXmlMinVersion)) {
                tx_mkforms_util_Div::mayday(
                    'The given XML requires a version of MKFORMS'.
                    ' (<b>'.$sXmlMinVersion.'</b> or above)'.
                    ' more recent than the one installed'.
                    ' (<b>'.tx_mkforms_util_Div::getVersion().'</b>).'
                );
            }
        }

        if (false !== ($sXmlMaxVersion = $this->get('/maxversion', $this->_aConf))) {
            if (tx_mkforms_util_Div::getVersionInt() > \Sys25\RnBase\Utility\TYPO3::convertVersionNumberToInteger($sXmlMaxVersion)) {
                tx_mkforms_util_Div::mayday(
                    'The given XML requires a version of MKFORMS'.
                    ' (<b>'.$sXmlMaxVersion.'</b> maximum)'.
                    ' older than the one installed'.
                    ' (<b>'.tx_mkforms_util_Div::getVersion().'</b>).'
                );
            }
        }
    }

    /**
     * Takes an array of typoscript configuration, and adapt it to formidable syntax.
     *
     * @param array $aConf: TS array for application
     */
    private function refineTS($aConf)
    {
        $aTemp = [];

        // processing meta
        $aTemp['meta'] = [];
        if (isset($aConf['meta.']) && is_array($aConf['meta.'])) {
            reset($aConf['meta.']);
            foreach ($aConf['meta.'] as $sKey => $notNeeded) {
                if (is_string($aConf['meta.'][$sKey]) && 'codebehind' === $aConf['meta.'][$sKey]) {
                    if (array_key_exists($sKey.'.', $aConf['meta.'])) {
                        $aTemp['meta']['codebehind-'.$sKey] = $aConf['meta.'][$sKey.'.'];
                    }
                    unset($aConf['meta.'][$sKey.'.']);
                } else {
                    if (is_array($aConf['meta.'][$sKey])) {
                        $sPlainKey = substr($sKey, 0, -1);
                        $aTemp['meta'][$sPlainKey] = tx_mkforms_util_Div::removeDots($aConf['meta.'][$sKey]);
                    } else {
                        $aTemp['meta'][$sKey] = $aConf['meta.'][$sKey];
                    }
                }
            }
        }

        // processing control
        $aTemp['control'] = [];
        if (isset($aConf['control.']) && is_array($aConf['control.'])) {
            reset($aConf['control.']);
            foreach ($aConf['control.'] as $sKey => $notNeeded) {
                if (is_string($aConf['control.'][$sKey])) {
                    if ('datahandler' === $sKey) {
                        $aTemp['control']['datahandler'] = [
                            'type' => substr($aConf['control.'][$sKey], strlen('datahandler:')),
                        ];

                        if (array_key_exists($sKey.'.', $aConf['control.'])) {
                            $aTemp['control']['datahandler'] = \Sys25\RnBase\Utility\Arrays::mergeRecursiveWithOverrule(
                                $aTemp['control']['datahandler'],
                                tx_mkforms_util_Div::removeDots($aConf['control.'][$sKey.'.'])
                            );
                        }
                    } elseif ('renderer' === $sKey) {
                        $aTemp['control']['renderer'] = [
                            'type' => substr($aConf['control.'][$sKey], strlen('renderer:')),
                        ];

                        if (array_key_exists($sKey.'.', $aConf['control.'])) {
                            $aTemp['control']['renderer'] = \Sys25\RnBase\Utility\Arrays::mergeRecursiveWithOverrule(
                                $aTemp['control']['renderer'],
                                tx_mkforms_util_Div::removeDots($aConf['control.'][$sKey.'.'])
                            );
                        }
                    }
                } else {
                    if ('actionlets.' === $sKey) {
                        $aTemp['control']['actionlets'] = [];

                        reset($aConf['control.'][$sKey]);
                        foreach ($aConf['control.'][$sKey] as $sActKey => $notNeeded) {
                            if (is_string($aConf['control.'][$sKey][$sActKey])) {
                                $aTemp['control']['actionlets']['actionlet-'.$sActKey] = [
                                    'type' => substr($aConf['control.'][$sKey][$sActKey], strlen('actionlet:')),
                                ];

                                if (array_key_exists($sActKey.'.', $aConf['control.'][$sKey])) {
                                    $aTemp['control']['actionlets']['actionlet-'.$sActKey] = \Sys25\RnBase\Utility\Arrays::mergeRecursiveWithOverrule(
                                        $aTemp['control']['actionlets']['actionlet-'.$sActKey],
                                        tx_mkforms_util_Div::removeDots($aConf['control.'][$sKey][$sActKey.'.'])
                                    );
                                }
                            }
                        }
                    } elseif ('datasources.' === $sKey) {
                        $aTemp['control']['datasources'] = [];

                        reset($aConf['control.'][$sKey]);
                        foreach ($aConf['control.'][$sKey] as $sActKey => $notNeeded) {
                            if (is_string($aConf['control.'][$sKey][$sActKey])) {
                                $aTemp['control']['datasources']['datasource-'.$sActKey] = [
                                    'type' => substr($aConf['control.'][$sKey][$sActKey], strlen('datasource:')),
                                ];

                                if (array_key_exists($sActKey.'.', $aConf['control.'][$sKey])) {
                                    $aTemp['control']['datasources']['datasource-'.$sActKey] = \Sys25\RnBase\Utility\Arrays::mergeRecursiveWithOverrule(
                                        $aTemp['control']['datasources']['datasource-'.$sActKey],
                                        tx_mkforms_util_Div::removeDots($aConf['control.'][$sKey][$sActKey.'.'])
                                    );
                                }
                            }
                        }
                    } elseif ('sandbox.' === $sKey) {
                        $aTemp['control']['sandbox'] = tx_mkforms_util_Div::removeDots($aConf['control.']['sandbox.']);
                    }
                }
            }
        }

        // processing renderlets
        $aTemp['elements'] = [];
        if (isset($aConf['elements.']) && is_array($aConf['elements.'])) {
            reset($aConf['elements.']);
            foreach ($aConf['elements.'] as $sKey => $notNeeded) {
                if (is_string($aConf['elements.'][$sKey])) {
                    $aType = explode(':', $aConf['elements.'][$sKey]);

                    if ('renderlet' === $aType[0]) {
                        if (array_key_exists($sKey.'.', $aConf['elements.'])) {
                            $aTemp['elements'][$aType[0].'-'.$sKey.'-'.rand()] = $this->refineTS_renderlet(
                                $aConf['elements.'][$sKey],
                                $aConf['elements.'][$sKey.'.']
                            );
                        } else {
                            $aTemp['elements'][$aType[0].'-'.$sKey.'-'.rand()] = ['type' => $aType[1]];
                        }
                    }
                }
            }
        }
        $this->config = $aTemp;
    }

    /**
     * Takes a typoscript conf for a renderlet and refines it to formidable-syntax.
     *
     * @param string $sTen:    TS name like: 10 = renderlet:TEXT
     * @param array  $aTenDot: TS value of 10. like: 10.value = Hello World !
     *
     * @return array refined conf
     */
    private function refineTS_renderlet($sTen, $aTenDot)
    {
        $aType = explode(':', $sTen);
        $aRdt = [
            'type' => $aType[1],
        ];

        if (array_key_exists('childs.', $aTenDot)) {
            $aRdt['childs'] = [];

            reset($aTenDot['childs.']);
            foreach ($aTenDot['childs.'] as $sKey => $sChild) {
                $aChild = [];
                if (is_string($sChild)) {
                    $aChildType = explode(':', $sChild);
                    if ('renderlet' === $aChildType[0]) {
                        if (array_key_exists($sKey.'.', $aTenDot['childs.'])) {
                            $aChild = $this->refineTS_renderlet(
                                $sChild,
                                $aTenDot['childs.'][$sKey.'.']
                            );
                        } else {
                            $aChild = $this->refineTS_renderlet(
                                $sChild,
                                []
                            );
                        }
                    }

                    $aRdt['childs'][$aChildType[0].'-'.$sKey.'-'.rand()] = $aChild;
                }
            }

            unset($aTenDot['childs.']);
        }

        if (array_key_exists('validators.', $aTenDot)) {
            $aRdt['validators'] = [];
            reset($aTenDot['validators.']);
            foreach ($aTenDot['validators.'] as $sKey => $sValidator) {
                $aValidator = [];
                if (is_string($sValidator)) {
                    $aValType = explode(':', $sValidator);
                    if ('validator' === $aValType[0]) {
                        $aValidator['type'] = $aValType[1];

                        if (array_key_exists($sKey.'.', $aTenDot['validators.'])) {
                            $aValidator = \Sys25\RnBase\Utility\Arrays::mergeRecursiveWithOverrule(
                                $aValidator,
                                tx_mkforms_util_Div::removeDots($aTenDot['validators.'][$sKey.'.'])
                            );
                        }

                        $aRdt['validators']['validator-'.$sKey] = $aValidator;
                    }
                }
            }

            unset($aTenDot['validators.']);
        }

        $aRdt = \Sys25\RnBase\Utility\Arrays::mergeRecursiveWithOverrule(
            $aRdt,
            tx_mkforms_util_Div::removeDots($aTenDot)
        );

        reset($aRdt);

        return $aRdt;
    }

    /**
     * Fügt die default xml config zur aktuellen hinzu.
     *
     * @TODO: testcase integrieren!
     *
     * @param array $aXmlConf
     * @param array $aDefaultXml
     *
     * @return array
     */
    private function loadDefaultXmlConf(&$aXmlConf = false, $aDefaultXml = false)
    {
        $aDefaultXml = $aDefaultXml ? $aDefaultXml : $this->getForm()->getConfTS('defaultXml.');
        if (!is_array($aDefaultXml)) {
            return;
        }
        if (!is_array($aXmlConf)) {
            // als referenz, damit die default werte hinzugefügt werden können.
            $aXmlConf = &$this->config;
        }
        // die default config durchlaufen und der aktuellen hinzufügen.
        foreach ($aDefaultXml as $key => $value) {
            if ('.' == substr($key, strlen($key) - 1, 1)) {
                $key_1 = substr($key, 0, strlen($key) - 1);
                if (!is_array($aXmlConf[$key_1] ?? null)) {
                    $aXmlConf[$key_1] = [];
                }
                $aXmlConf[$key_1] = $this->loadDefaultXmlConf($aXmlConf[$key_1], $value);
            } elseif (!array_key_exists($key, $aXmlConf)) {
                $aXmlConf[$key] = $value;
            }
        }

        return $aXmlConf;
    }

    /**
     * [Describe function...].
     *
     * @param string $sPath: ...
     * @param array  $aConf: ...
     *
     * @return bool
     */
    public function defaultTrue($sPath, $aConf = -1)
    {
        if (false !== ($val = $this->get($sPath, $aConf))) {
            return $this->isTrueVal($val);
        }

        return true;    // TRUE as a default
    }

    /**
     * @param string $sPath: ...
     * @param array  $aConf: ...
     *
     * @return bool
     */
    public function defaultFalse($sPath, $aConf = -1)
    {
        if (false !== ($val = $this->get($sPath, $aConf))) {
            return $this->isTrueVal($val);
        }

        return false;    // FALSE as a default
    }

    /**
     * [Describe function...].
     *
     * @param [type] $sPath: ...
     * @param [type] $aConf: ...
     *
     * @return [type] ...
     */
    public function isTrue($sPath, $aConf = -1)
    {
        return $this->isTrueVal($this->get($sPath, $aConf));
    }

    /**
     * [Describe function...].
     *
     * @param [type] $sPath: ...
     * @param [type] $aConf: ...
     *
     * @return [type] ...
     */
    public function isFalse($sPath, $aConf = -1)
    {
        $mValue = $this->get($sPath, $aConf);

        return (false !== $mValue) ? $this->isFalseVal($mValue) : false;
    }

    /**
     * [Describe function...].
     *
     * @param [type] $mVal: ...
     *
     * @return [type] ...
     */
    private function isTrueVal($mVal)
    {
        $mVal = $this->form->getRunnable()->callRunnable($mVal);

        return (true === $mVal) || ('1' == $mVal) || ('TRUE' == strtoupper($mVal));
    }

    /**
     * [Describe function...].
     *
     * @param [type] $mVal: ...
     *
     * @return [type] ...
     */
    private function isFalseVal($mVal)
    {
        $mVal = $this->form->getRunnable()->callRunnable($mVal);

        return (false == $mVal) || ('FALSE' == strtoupper($mVal));
    }

    public function isDebug()
    {
        $this->initDebug();

        return $this->debug > 0;
    }

    public function isDebugLight()
    {
        $this->initDebug();

        return 2 == $this->debug;
    }

    /**
     * In /meta/debug kann man einen Debug-Wert serzen. Dieser ist entweder eine Zahl oder ein Boolean. Wird Boolean=true gesetzt, dann wird das
     * in die Zahl zwei umgewandelt.
     */
    private function initDebug()
    {
        if (-99 == $this->debug) {
            $this->debug = (int) $this->get('/meta/debug');
            if (0 == $this->debug && $this->isTrue('/meta/debug/')) {
                $this->debug = 2;    // LIGHT
            }
        }
    }

    /**
     * Refine raw conf and:
     *  -> inserts recursively all includexml declared
     *  -> inserts recursively all includets declared
     *  -> apply modifiers declared, if any
     *  -> remove sections emptied by modifiers, if any
     *  -> execute xmlbuilders declared, if any.
     *
     * @param array  $aConf:      array of raw config to refine
     * @param [type] $aTempDebug: internal use
     *
     * @return array refined array of conf
     */
    public function compileConfig(&$aTempDebug)
    {
        $this->config = $this->compileConf($this->config, $aTempDebug);
    }

    /**
     * @return array
     */
    public function getConfigArray()
    {
        return $this->config;
    }

    private function compileConf($aConf, &$aTempDebug)
    {
        $aTempDebug['aIncHierarchy'] = [];

        $aConf = $this->insertSubXml($aConf, $aTempDebug['aIncHierarchy']);
        $aConf = $this->insertSubTS($aConf);
        $aConf = $this->applyModifiers($aConf);
        $aConf = $this->deleteEmpties($aConf);    // ????  surveiller
        $aConf = $this->insertXmlBuilder($aConf);

        tx_mkforms_util_Div::debug($aTempDebug['aIncHierarchy'], 'MKFORMS CORE - INCLUSION HIERARCHY', $this->form);

        return $aConf;
    }

    /**
     * Executes and inserts conf generated by xmlbuilders, if any declared.
     *
     * @param array $aConf: array of conf to process
     * @param array $aTemp: optional; internal use
     *
     * @return array processed array of conf
     */
    private function insertXmlBuilder($aConf, $aTemp = [])
    {
        reset($aConf);
        foreach ($aConf as $key => $val) {
            if (is_array($val)) {
                if ('x' === $key[0] && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($key, 'xmlbuilder')) {
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
     * @param array  $aConf:   array of conf to process
     * @param array  $aDebug:  internal use
     * @param string $sParent: optional; parent xpath
     *
     * @return array processed conf array
     */
    private function insertSubXml($aConf, &$aDebug, $sParent = false)
    {
        if (!$aConf) {
            return [];
        }
        reset($aConf);

        $aTemp = [];
        if (false === $sParent) {
            $sParent = '/formidable';
        }

        foreach ($aConf as $key => $val) {
            if (is_array($val)) {
                if ('i' === $key[0] && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($key, 'includexml')) {
                    if (array_key_exists('path', $val)) {
                        $sPath = $val['path'];
                    } elseif ('' !== trim($val['__value'])) {
                        $sPath = $val['__value'];
                    } else {
                        $sPath = $this->_xmlPath;
                    }

                    $sPath = $this->getForm()->getRunnable()->callRunnable($sPath);
                    $bInclude = true;

                    if (array_key_exists('condition', $val)) {
                        $bInclude = $this->defaultTrue('/condition', $val);
                    }

                    $bInclude = '' === trim($sPath) ? false : $bInclude;

                    if ($bInclude) {
                        $aDebug[] = [
                            $sParent.' 1- '.$sPath,
                            'subxml' => [],
                        ];
                        $iNewKey = count($aDebug) - 1;

                        $aXml = tx_mkforms_util_XMLParser::getXml(tx_mkforms_util_Div::toServerPath($sPath), true);
                        $aXml = $this->insertSubXml($aXml, $aDebug[$iNewKey]['subxml']);

                        if (array_key_exists('dynaxml', $val)) {
                            $aDynaXml = $val['dynaxml'];
                            $aXml = $this->_substituteDynaXml($aXml, $aDynaXml);
                        }

                        if (array_key_exists('xpath', $val)) {
                            if ('.' === $val['xpath'][0]) {
                                $sXPath = $this->absolutizeXPath($val['xpath'], $sParent);
                            } else {
                                $sXPath = $val['xpath'];
                            }

                            $aXml = $this->xPath('XPATH:'.$sXPath, $aXml, true); // BREAKABLE

                            if (AMEOSFORMIDABLE_XPATH_FAILED === $aXml) {
                                tx_mkforms_util_Div::mayday('<b>XPATH:'.$sXPath.'</b> is not valid, or matched nothing.<br />XPATH breaked on: <b>'.$this->sLastXPathError.'</b>');
                            }
                        }

                        if (array_key_exists('debug', $val) && $this->_isTrueVal($val['debug'])) {
                            $this->debug(['include' => $val, 'result' => $aXml]);
                        }

                        $aTemp = $this->array_add(
                            $this->insertSubXml($aXml, $aDebug[$iNewKey]['subxml'], $sParent.'/'.$key),
                            $aTemp
                        );

                        if (empty($aDebug[$iNewKey]['subxml'])) {
                            unset($aDebug[$iNewKey]['subxml']);
                        }
                    }
                } else {
                    $aInsert = $this->insertSubXml(
                        $val,
                        $aDebug,
                        $sParent.'/'.$key
                    );

                    if (array_key_exists($key, $aTemp)) {
                        // reindexing the xml array for correct merging
                        $counter = 0;
                        while (array_key_exists($key.'-'.$counter, $aTemp)) {
                            ++$counter;
                        }

                        $aTemp[$key.'-'.$counter] = $aInsert;
                    } else {
                        $aTemp[$key] = $aInsert;
                    }
                }
            } else {
                if ('i' === $key[0] && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($key, 'includexml')) {
                    $aDebug[] = [
                        $sParent => $val,
                        'subxml' => [],
                    ];

                    $iNewKey = count($aDebug) - 1;

                    $aXml = tx_mkforms_util_XMLParser::getXml(\Sys25\RnBase\Utility\T3General::getFileAbsFileName($val), true);

                    $aTemp = $this->array_add(
                        $this->insertSubXml($aXml, $aDebug[$iNewKey]['subxml'], $sParent.'/'.$key),
                        $aTemp
                    );

                    if (empty($aDebug[$iNewKey]['subxml'])) {
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
     * Resolves an xpath and returns value pointed by this xpath.
     *
     * @param string $sPath: xpath
     *
     * @return mixed
     */
    private function xPath($sPath, $aConf = -1, $bBreakable = false)
    {
        $this->sLastXPathError = '';

        if (!(is_string($sPath) && 'X' === $sPath[0] && 'XPATH:' === substr($sPath, 0, 6))) {
            return false;
        }

        $sPath = tx_mkforms_util_Div::trimSlashes(strtolower(substr($sPath, 6)));

        if (false === strpos($sPath, '[')) {
            return $this->get($sPath, $aConf);
        }

        $aSegments = [];
        if (-1 === $aConf) {
            $aConf = $this->_aConf;
        }

        $aParts = explode('/', $sPath);
        reset($aParts);
        foreach ($aParts as $sPart) {
            $aTemp = explode('[', str_replace(']', '', $sPart));
            if (count($aTemp) > 1) {
                // we have to search on a criteria sequence
                $sWhat = $aTemp[0];
                $aTempCrits = \Sys25\RnBase\Utility\Strings::trimExplode(',', $aTemp[1]);
                reset($aTempCrits);
                $aCrits = [];
                foreach ($aTempCrits as $sTempCrit) {
                    $aCrit = \Sys25\RnBase\Utility\Strings::trimExplode('=', $sTempCrit);
                    $aCrits[$aCrit[0]] = $aCrit[1];
                }
                $aSegments[] = ['what' => $sWhat, 'crits' => $aCrits, 'segment' => $sPart];
            } else {
                $aSegments[] = ['what' => $sPart, 'crits' => false, 'segment' => $sPart];
            }
        }
        $aPossibles = [0 => $aConf];

        reset($aConf);
        foreach ($aSegments as $iLevel => $aSegment) {
            $bSegMatch = false;
            $this->sLastXPathError .= '/'.$aSegment['segment'];
            $aNewPossibles = [];
            $aPossKeys = array_keys($aPossibles);
            foreach ($aPossKeys as $sPosKey) {
                $aKeys = array_keys($aPossibles[$sPosKey]);
                reset($aKeys);
                foreach ($aKeys as $sKey) {
                    if (substr($sKey, 0, strlen($aSegment['what'])) == $aSegment['what']) {
                        $bMatch = true;
                        if (false !== $aSegment['crits']) {
                            reset($aSegment['crits']);
                            foreach ($aSegment['crits'] as $sProp => $sValue) {
                                $bMatch = $bMatch && (array_key_exists(strtolower($sProp), $aPossibles[$sPosKey][$sKey]) && strtolower($aPossibles[$sPosKey][$sKey][$sProp]) == strtolower($sValue));
                            }
                        }

                        if ($bMatch) {
                            $bSegMatch = true;
                            $aNewPossibles[$sKey] = $aPossibles[$sPosKey][$sKey];
                        }
                    }
                }
            }

            if (false === $bSegMatch && true === $bBreakable) {
                return AMEOSFORMIDABLE_XPATH_FAILED;
            }

            $aPossibles = $aNewPossibles;
        }

        reset($aPossibles);

        return $aPossibles;
    }

    /**
     * Debug some data to screen.
     */
    private function debug()
    {
        $aVars = func_get_args();
        if (1 === func_num_args()) {
            $aVars = func_get_arg(0);
        }
        echo '<div>'.tx_mkforms_util_Div::viewMixed($aVars, true, 0).'</div>';
        flush();
    }

    /**
     * Inserts conf declared by includets.
     *
     * @param array $aConf: array of conf to process
     * @param array $aTemp: optional; internal use
     *
     * @TODO: reimplement. does not work since the new config was added by the mkforms fork
     *
     * @return array processed conf array
     */
    private function insertSubTS($aConf, $aTemp = [])
    {
        reset($aConf);
        foreach ($aConf as $key => $val) {
            $isIncludeTS = ('i' === $key[0] && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($key, 'includets'));
            if (is_array($val)) {
                if ($isIncludeTS) {
                    if (array_key_exists('path', $val)) {
                        throw new Exception('insertSubTS not supported yet. has to be reimplementet to new config');
                    }
                } else {
                    $aTemp[$key] = $this->insertSubTS($val);
                }
            } else {
                if ($isIncludeTS) {
                    throw new Exception('insertSubTS not supported yet. has to be reimplementet to new config');
                } else {
                    $aTemp[$key] = $val;
                }
            }
        }

        return $aTemp;
    }

    /**
     * Utility function for _insertSubTS.
     *
     * @param string $sTSPath: ts path to get
     *
     * @return mixed ts conf
     */
    private function getTS($sTSPath)
    {
        return $this->getForm()->getConfTS($sTSPath);
    }

    /**
     * [Describe function...].
     *
     * @param [type] $a1: ...
     * @param [type] $a2: ...
     *
     * @return [type] ...
     */
    private function array_add($a1, $a2)
    {
        if (is_array($a1)) {
            reset($a1);
            reset($a2);

            foreach ($a1 as $key => $val) {
                if ('type' != $key && array_key_exists($key, $a2)) {
                    $counter = 0;
                    while (array_key_exists($key.'-'.$counter, $a2)) {
                        ++$counter;
                    }
                    $a2[$key.'-'.$counter] = $val;
                } else {
                    $a2[$key] = $val;
                }
            }
        }
        reset($a2);

        return $a2;
    }

    /**
     * Utility method for _applyModifiers().
     *
     * @param array $aSubConf
     *
     * @return array
     */
    private function applyLocalModifiers($aSubConf)
    {
        reset($aSubConf);
        if (false !== ($aModifiers = $this->get('/modifiers', $aSubConf))) {
            reset($aModifiers);
            foreach ($aModifiers as $sModKey => $aModifier) {
                if ($this->_matchConditions($aModifier)) {
                    $aSubConf =
                        \Sys25\RnBase\Utility\Arrays::mergeRecursiveWithOverrule(
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
     * Das wird auch vom Validator aufgerufen formidable_mainvalidator.
     *
     * @param [type] $aConditioner: ...
     *
     * @return [type] ...
     */
    public function matchConditions($aConditioner)
    {
        $bRet = true;

        if (false !== ($aConditions = $this->get('/conditions/', $aConditioner))) {
            if (false === ($sLogic = $this->get('/logic', $aConditions))) {
                $sLogic = 'AND';
            } else {
                $sLogic = strtoupper($sLogic);
            }

            foreach ($aConditions as $sCondKey => $notNeeded) {
                if ('c' === $sCondKey[0] && 'o' === $sCondKey[1] && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($sCondKey, 'condition')) {
                    $aCondition = $this->get($sCondKey, $aConditions);
                    switch ($sLogic) {
                        case 'OR':
                            $bRet = $bRet || $this->_matchCondition($aCondition);
                            break;

                        case 'AND':
                        default:
                            $bRet = $bRet && $this->_matchCondition($aCondition);
                            break;
                    }
                }
            }
        }

        return $bRet;
    }

    /**
     * Removes conf-sections emptied by modifiers, if any.
     *
     * @param array $aConf: array of conf to refine
     *
     * @return array processed conf array
     */
    private function deleteEmpties($aConf)
    {
        reset($aConf);
        foreach ($aConf as $sKey => $mValue) {
            if (is_array($aConf[$sKey])) {
                if (array_key_exists('empty', $aConf[$sKey])) {
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
     * Applies declared modifiers, if any.
     *
     * @param array $aConf: conf to process
     *
     * @return array processed conf
     */
    private function applyModifiers($aConf)
    {
        reset($aConf);
        foreach ($aConf as $sKey => $mValue) {
            if (is_array($aConf[$sKey])) {
                if ('modifiers' == $sKey) {
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
     * Liefert das Formular.
     *
     * @return tx_mkforms_forms_IForm
     */
    private function getForm()
    {
        return $this->form;
    }

    /**
     * Erstellt eine Instanz auf Basis einer XML-Datei.
     *
     * @param string $path Pfad zur XML-Datei
     *
     * @return tx_mkforms_util_Config
     */
    public static function createInstanceByPath($path, $form)
    {
        $cfg = new tx_mkforms_util_Config($form);
        $cfg->loadXmlConf(\Sys25\RnBase\Utility\T3General::getFileAbsFileName($path));
        // default config laden hinzufügen
        $cfg->loadDefaultXmlConf();

        return $cfg;
    }

    /**
     * Erstellt eine Instanz auf Basis eines Typoscript-Arrays.
     *
     * @param array $confArr Typoscript-Array
     *
     * @return tx_mkforms_util_Config
     */
    public static function createInstanceByTS($confArr, $form)
    {
        $cfg = new tx_mkforms_util_Config($form);
        $cfg->refineTS($confArr);
        // default config laden hinzufügen
        $cfg->loadDefaultXmlConf();

        return $cfg;
    }
}
