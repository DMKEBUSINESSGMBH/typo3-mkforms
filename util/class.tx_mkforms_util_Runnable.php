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
 * Execute code within XML.
 */
class tx_mkforms_util_Runnable
{
    private $config;
    private $form;
    private $aUserObjParamsStack = [];
    private $aForcedUserObjParamsStack = [];
    private $aCodeBehinds = [];

    private function __construct($config, $form)
    {
        $this->config = $config;
        $this->form = $form;
    }

    /**
     * [Describe function...].
     *
     * @param [type] $mMixed: ...
     *
     * @return [type] ...
     */
    public static function isUserObj($mMixed)
    {
        return is_array($mMixed) && array_key_exists('userobj', $mMixed);
    }

    private static function hasCodeBehind($mMixed)
    {
        return is_array($mMixed) && array_key_exists('exec', $mMixed);
    }

    public static function isRunnable($mMixed)
    {
        return self::isUserObj($mMixed) || self::hasCodeBehind($mMixed);
    }

    /**
     * Aufruf eines UserObj im XML. Neben dem XML-Pfad können weitere Parameter übergeben werden, die
     * dann als Parameter durchgereicht werden.
     *
     * @param mixed $mMixed
     *
     * @return unknown
     */
    public function callRunnable($mMixed)
    {
        if (!self::isRunnable($mMixed)) {
            return $mMixed;
        }
        // NOTE: for userobj, only ONE argument may be passed
        $aArgs = func_get_args();

        if (self::isUserObj($mMixed)) {
            $params = array_key_exists(1, $aArgs) && is_array($aArgs[1]) ? $aArgs[1] : [];
            $contextObj = count($aArgs) > 1 ? $aArgs[count($aArgs) - 1] : false; // Ggf. das Context-Objekt

            return $this->callUserObj($mMixed, $params, $contextObj);
        }

        if (self::hasCodeBehind($mMixed)) {
            // it's a codebehind
            $aArgs[0] = $mMixed; // Wir ersetzen den ersten Parameter
            $mRes = call_user_func_array([$this, 'callCodeBehind'], $aArgs);

            return $mRes;
        }

        return $mMixed;
    }

    /**
     * Führt das Runnable für ein bestimmtes Widget aus. Die Methode stammt ursprünglich
     * aus der Klasse mainrenderlet.
     *
     * @param mainrenderlet $widget
     * @param mixed         $mMixed
     *
     * @return mixed
     */
    public function callRunnableWidget($widget, $mMixed)
    {
        $aArgs = func_get_args();
        array_shift($aArgs);
        $this->getForm()->pushCurrentRdt($widget);
        // Dieser Aufruf geht ans main_object. Die Methode muss aber da noch raus!
        $mRes = call_user_func_array([$widget, 'callRunneable'], $aArgs);
        $this->getForm()->pullCurrentRdt();

        return $mRes;
    }

    /**
     * [Describe function...].
     *
     * @param array $aUserObjParams
     * @param array $aParams
     *
     * @return array
     */
    public function parseParams($aUserObjParams, $aParams = [])
    {
        foreach ($aUserObjParams as $index => $aParam) {
            if (is_array($aParam)) {
                $name = $aParam['name'];
                // Scalar values are set in attribute "value"
                if (isset($aParam['value'])) {
                    $value = $aParam['value'];
                } else {
                    // Treat deep structures aka arrays:
                    unset($aParam['name']);

                    if (array_key_exists('__value', $aParam)) {
                        unset($aParam['__value']);
                    }
                    $value = $aParam;
                }
            } elseif ('__value' !== $index) {
                $name = $index;
                $value = $aParam;
            }

            // Finally set this parameter
            $aParams[$name] = $this->getForm()->getConfigXML()->getLLLabel($value);
        }
        reset($aParams);

        return $aParams;
    }

    /**
     * [Describe function...].
     *
     * @param array  $aUserobj:  Array mit XML-Config
     * @param array  $aParams:   Zusätzliche Parameter für Methodenaufruf
     * @param object $contextObj Bei context=="relative" wird diese Object als 2. Parameter übergeben (anstatt des Forms).
     *
     * @return [type] ...
     */
    private function callUserObj($aUserobj, $aParams = [], $contextObj = false)
    {
        if (!is_array($this->getConfig()->get('/userobj/', $aUserobj))) {
            return;
        }
        // Das ContextObj ist der zweite Parameter im Aufruf. Normalerweise das Form, es kann aber auch das betroffene Objekt sein.
        $contextObj = 'relative' == $this->getConfig()->get('/userobj/context/', $aUserobj) ? $contextObj : $this->getForm();

        // Weitere Parameter können per XML übergeben werden
        $aUserObjParams = $this->getConfig()->get('/userobj/params/', $aUserobj);
        if (false !== $aUserObjParams && is_array($aUserObjParams)) {
            $aParams = $this->parseParams($aUserObjParams, $aParams);
        }

        if (false !== ($mPhp = $this->getConfig()->get('/userobj/php', $aUserobj))) {
            $sPhp = (is_array($mPhp) && array_key_exists('__value', $mPhp)) ? $mPhp['__value'] : $mPhp;

            $sClassName = uniqid('tempcl').rand(1, 1000);
            $sMethodName = uniqid('tempmet');

            $this->__sEvalTemp = ['code' => $sPhp, 'xml' => $aUserobj];

            // TODO: hier wird im PHP-Code das $this durch das Formular ersetzt. In dem Fall ist das natürlich falsch
            // weil hier Runnable gesetzt wird
            $form = $this->getForm();
            $GLOBALS['mkforms_forminstance'] = &$contextObj;
            $sPhp = str_replace('$this', '$GLOBALS[\'mkforms_forminstance\']', $sPhp);

            $sClass = 'class '.$sClassName.' {'
                .'	function '.$sMethodName."(\$_this, \$aParams) { \$_this=&\$GLOBALS['mkforms_forminstance'];"
                .'		'.$sPhp
                .'	}'
                .'}';

            set_error_handler([&$form, '__catchEvalException']);
            eval($sClass);
            $oObj = new $sClassName();

            try {
                $this->pushUserObjParam($aParams);
                $sRes = call_user_func([&$oObj, $sMethodName], $this->getForm(), $aParams);
                $this->pullUserObjParam();
            } catch (Exception  $e) {
                $verbose = (int) tx_rnbase_configurations::getExtensionCfgValue('rn_base', 'verboseMayday');

                $ret = 'UNCAUGHT EXCEPTION FOR VIEW: '.get_class($oCbObj)."\r\n";

                if ($verbose) {
                    $ret .= "\r\n".$e->__toString();
                } else {
                    $ret .= "\r\n".$e->getMessage();
                }

                // set msg to return;
                $sRes = $ret;
            }

            unset($this->__sEvalTemp);
            restore_error_handler();

            return $sRes;
        }

        if (false !== ($this->getConfig()->get('/userobj/cobj', $aUserobj))) {
            return $this->getCObj()->cObjGetSingle($aUserobj['userobj']['cobj'], $aUserobj['userobj']['cobj.']);
        }

        if (false !== ($sTs = $this->getConfig()->get('/userobj/ts', $aUserobj))) {
            return $this->callTyposcript($aUserobj, $sTs, $aParams);
        }

        if (false !== ($sJs = $this->getConfig()->get('/userobj/js', $aUserobj))) {
            if (false !== ($aParams = $this->getConfig()->get('/userobj/params', $aUserobj))) {
                if ($this->getForm()->isRunneable($aParams)) {
                    $aParams = $this->getForm()->callRunneable($aParams);
                }
            }
            $aParams = !is_array($aParams) ? [] : $aParams;

            return $this->getForm()->majixExecJs(trim($sJs), $aParams);
        }
        // Jetzt den normalen Fall abarbeiten

        $extension = $this->getConfig()->get('/userobj/extension/', $aUserobj);
        $method = $this->getConfig()->get('/userobj/method/', $aUserobj);
        $mode = $this->getConfig()->get('/userobj/loadmode', $aUserobj);

        $oExtension = (0 == strcasecmp($extension, 'this')) ? $this->getForm()->getParent() : tx_rnbase::makeInstance($extension);

        if (!is_object($oExtension)) {
            return;
        }

        $form = &$this->getForm();
        set_error_handler([&$form, '__catchEvalException']);

        if (!method_exists($oExtension, $method)) {
            $sObject = ('this' == $extension) ? '$this (<b>'.get_class($this->getForm()->getParent()).'</b>)' : $extension;
            tx_mkforms_util_Div::mayday($this->getConfig()->get('/type/', $aUserobj).' <b>'.$this->getConfig()->get('/name/', $aUserobj).'</b> : callback method <b>'.$method.'</b> of the Object <b>'.$sObject.'</b> doesn\'t exist');
        }

        try {
            $newData = $oExtension->{$method}($aParams, $contextObj);
        } catch (Exception  $e) {
            // wir leiten die Exception direkt an rn_base weiter,
            // ohne den Mayday aufzurufen.
            if ($this->getForm()->getConfTS('disableMaydayOnUserObjExceptions')) {
                throw $e;
            }

            $ret = 'UNCAUGHT EXCEPTION FOR VIEW: '.get_class($oCbObj)."\r\n";

            if (tx_rnbase_util_Logger::isWarningEnabled()) {
                tx_rnbase_util_Logger::warn('Method callUserObj() failed.', 'mkforms', ['Exception' => $e->getMessage(), 'XML' => $aUserobj, 'Params' => $aParams, 'Form-ID' => $this->getForm()->getFormId()]);
            }
            $ret .= "\r\n".$e->getMessage();

            $this->getForm()->mayday($ret);

            // set msg to return;
            $newData = $ret;
        }
        restore_error_handler();
        tx_mkforms_util_Div::debug($newData, 'RESULT OF '.$extension.'->'.$method.'()', $this->getForm());

        return $newData;
    }

    private function getCObj()
    {
        $sEnvExecMode = tx_mkforms_util_Div::getEnvExecMode();

        return ('BE' === $sEnvExecMode || 'CLI' === $sEnvExecMode) ? $this->getForm()->getCObj() : $GLOBALS['TSFE']->cObj;
    }

    /**
     * Typoscript-Code ausführen.
     *
     * @param array  $aUserobj
     * @param string $sTs
     * @param array  $aParams
     *
     * @return mixed
     */
    private function callTyposcript($aUserobj, $sTs, $aParams)
    {
        $sTs = '
				temp.ameos_formidable >
				temp.ameos_formidable {
					'.$sTs.'
				}';

        $oParser = tx_rnbase::makeInstance(tx_rnbase_util_Typo3Classes::getTypoScriptParserClass());
        $oParser->tt_track = 0;    // Do not log time-performance information
        $oParser->setup = $GLOBALS['TSFE']->tmpl->setup;

        if (array_key_exists('params.', $oParser->setup)) {
            unset($oParser->setup['params.']);
        }
        $oParser->setup['params.'] = tx_mkforms_util_Div::addDots($aParams);

        if (false !== ($aUserObjParams = $this->getConfig()->get('/userobj/params', $aUserobj))) {
            if (is_array($aUserObjParams)) {
                if ($this->getForm()->isRunneable($aUserObjParams)) {
                    $aUserObjParams = $this->getForm()->getRunnable()->callRunnable($aUserObjParams);
                    if (!is_array($aUserObjParams)) {
                        $aUserObjParams = [];
                    }
                }
                $oParser->setup['params.'] = tx_rnbase_util_Arrays::mergeRecursiveWithOverrule(
                    $oParser->setup['params.'],
                    $aUserObjParams
                );
            }
        }

        $oParser->parse($sTs);
        $this->aLastTs = $oParser->setup['temp.']['ameos_formidable.'];

        $sOldCWD = getcwd();        // changing current working directory for use of GIFBUILDER in BE
        chdir(\Sys25\RnBase\Utility\Environment::getPublicPath());

        $aRes = $this->getCObj()->cObjGet($oParser->setup['temp.']['ameos_formidable.']);

        chdir($sOldCWD);

        return $aRes;
    }

    public function pushUserObjParam($aParam)
    {
        array_push($this->aUserObjParamsStack, $aParam);
    }

    public function pullUserObjParam()
    {
        array_pop($this->aUserObjParamsStack);
    }

    public function pushForcedUserObjParam($aParam)
    {
        array_push($this->aForcedUserObjParamsStack, $aParam);

        return count($this->aForcedUserObjParamsStack) - 1;
    }

    public function pullForcedUserObjParam($iIndex = false)
    {
        if (false === $iIndex) {
            if (!empty($this->aForcedUserObjParamsStack)) {
                array_pop($this->aForcedUserObjParamsStack);
            }
        } else {
            if (array_key_exists($iIndex, $this->aForcedUserObjParamsStack)) {
                unset($this->aForcedUserObjParamsStack[$sName]);
            }
        }
    }

    public function getForcedUserObjParams()
    {
        $aParams = [];
        if (!empty($this->aForcedUserObjParamsStack)) {
            $aParams = $this->aForcedUserObjParamsStack[count($this->aForcedUserObjParamsStack) - 1];
        }

        return $aParams;
    }

    /**
     * Liefert die Parameter eines aktuellen Aufrufs eines UserObjects im XML.
     *
     * @return array
     */
    public function getUserObjParams()
    {
        $aParams = [];

        if (!empty($this->aUserObjParamsStack)) {
            $aParams = $this->aUserObjParamsStack[count($this->aUserObjParamsStack) - 1];
        }

        if (!empty($this->aForcedUserObjParamsStack)) {
            $aForcedParams = $this->getForcedUserObjParams();
            $aParams = tx_rnbase_util_Arrays::mergeRecursiveWithOverrule($aParams, $aForcedParams);
        }

        return $aParams;
    }

    public function cleanBeforeSession()
    {
        reset($this->aCodeBehinds['php']);
        foreach ($this->aCodeBehinds['php'] as $sKey => $notNeeded) {
            unset($this->aCodeBehinds['php'][$sKey]['object']->oForm);
            $this->aCodeBehinds['php'][$sKey]['object'] = serialize($this->aCodeBehinds['php'][$sKey]['object']);
            unset($this->aCB[$sKey]);
        }
    }

    /**
     * Die Methode wird noch in unHibernate der Formklasse aufgerufen.
     */
    public function initCodeBehinds()
    {
        $aMetas = $this->getConfig()->get('/meta');

        if ('EID' === tx_mkforms_util_Div::getEnvExecMode()) {
            $this->aCodeBehinds['js'] = [];
        } else {
            unset($this->aCodeBehinds);
            unset($this->aCB);
            $this->aCodeBehinds = [
                'js' => [],
                'php' => [],
            ];
        }

        if (false !== $this->_xmlPath) {
            // application is defined in an xml file, and we know it's location
            // checking for default codebehind file, named after formid
            // convention over configuration paradigm !

            // default php CB
            $sDefaultCBClass = preg_replace('/[^a-zA-Z0-9_]/', '', $this->formid).'_cb';
            $sDefaultCBFile = 'class.'.$sDefaultCBClass.'.php';
            $sDefaultCBDir = tx_mkforms_util_Div::toServerPath(dirname($this->_xmlPath));
            $sDefaultCBPath = $sDefaultCBDir.$sDefaultCBFile;

            if (file_exists($sDefaultCBPath) and is_readable($sDefaultCBPath)) {
                $aDefaultCB = [
                    'type' => 'php',
                    'name' => 'cb',
                    'path' => $sDefaultCBPath,
                    'class' => $sDefaultCBClass,
                ];

                $aMetas = array_merge(
                    [
                        'codebehind-default-php' => $aDefaultCB,
                    ],
                    $aMetas
                );
            }

            // default js CB
            $sDefaultCBFile = 'class.'.$sDefaultCBClass.'.js';
            $sDefaultCBPath = $sDefaultCBDir.$sDefaultCBFile;

            if (file_exists($sDefaultCBPath) and is_readable($sDefaultCBPath)) {
                $aDefaultCB = [
                    'type' => 'js',
                    'name' => 'js',
                    'path' => $sDefaultCBPath.':'.$sDefaultCBClass,
                    'class' => $sDefaultCBClass,
                ];

                $aMetas = array_merge(
                    [
                        'codebehind-default-js' => $aDefaultCB,
                    ],
                    $aMetas
                );
            }
        }

        if (!is_array($aMetas)) {
            $aMetas[0] = $aMetas;
        }
        reset($aMetas);
        foreach ($aMetas as $sKey => $notNeeded) {
            if ('c' === $sKey[0] && 'o' === $sKey[1] && Tx_Rnbase_Utility_Strings::isFirstPartOfStr(strtolower($sKey), 'codebehind')) {
                $aCB = $this->initCodeBehind($aMetas[$sKey]);
                if ('php' === $aCB['type']) {
                    if ('EID' === tx_mkforms_util_Div::getEnvExecMode()) {
                        $this->aCodeBehinds['php'][$aCB['name']]['object'] = unserialize($this->aCodeBehinds['php'][$aCB['name']]['object']);
                        $this->aCodeBehinds['php'][$aCB['name']]['object']->oForm = &$this->getForm();
                    } else {
                        $this->aCodeBehinds['php'][$aCB['name']] = $aCB;
                    }
                    $this->aCB[$aCB['name']] = &$this->aCodeBehinds['php'][$aCB['name']]['object'];
                } elseif ('js' === $aCB['type']) {
                    $this->aCodeBehinds['js'][$aCB['name']] = $this->buildJsCbObject($aCB);
                    $this->aCB[$aCB['name']] = &$this->aCodeBehinds['js'][$aCB['name']];
                }
            }
        }
    }

    private function &buildJsCbObject($aCB)
    {
        // den loader benutzen, damit die klasse beim ajax geladen wird
        $oJsCb = $this->getForm()->getObjectLoader()->makeInstance(
            'formidable_mainjscb',
            tx_rnbase_util_Extensions::extPath('mkforms', 'api/class.mainjscb.php')
        );
        $oJsCb->init($this, $aCB);

        return $oJsCb;
    }

    private function initCodeBehind($aCB)
    {
        $sCBRef = $aCB['path'];
        $sName = $aCB['name'];

        // check for this (form object)
        if ('this' === strtolower($sCBRef)) {
            $oCB = &$this->getForm()->getParent();

            return [
                'type' => 'php',
                'name' => $sName,
                'class' => get_class($oCB),
                'object' => &$oCB,
            ];
        }

        if ('E' === $sCBRef[0] && 'X' === $sCBRef[1] && Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sCBRef, 'EXT:')) {
            $sCBRef = substr($sCBRef, 4);
            $sPrefix = 'EXT:';
        } else {
            $sPrefix = '';
        }

        $aParts = explode(':', $sCBRef);

        $sFileRef = $sPrefix.$aParts[0];
        $sFilePath = tx_mkforms_util_Div::toServerPath($sFileRef);

        // determining type of the CB
        $sFileExt = strtolower(array_pop(Tx_Rnbase_Utility_T3General::revExplode('.', $sFileRef, 2)));
        switch ($sFileExt) {
            case 'php':
                if (is_file($sFilePath) && is_readable($sFilePath)) {
                    if (count($aParts) < 2) {
                        if (!in_array($sFilePath, get_included_files())) {
                            // class has not been defined. Let's try to determine automatically the class name

                            $aClassesBefore = get_declared_classes();
                            ob_start();
                            require_once $sFilePath;
                            ob_end_clean();        // output buffering for easing use of php class files that execute something outside the class definition ( like BE module's index.php !!)
                            $aClassesAfter = get_declared_classes();

                            $aNewClasses = array_diff($aClassesAfter, $aClassesBefore);

                            if (1 !== count($aNewClasses)) {
                                tx_mkforms_util_Div::mayday("<b>CodeBehind: Cannot automatically determine the classname to use in '".$sFilePath."'</b><br />Please add ':myClassName' after the file-path to explicitely.");
                            } else {
                                $sClass = array_shift($aNewClasses);
                            }
                        } else {
                            tx_mkforms_util_Div::mayday("<b>CodeBehind: Cannot automatically determine the classname to use in '".$sFilePath."'</b><br />Please add ':myClassName' after the file-path.");
                        }
                    } else {
                        $sClass = $aParts[1];
                        ob_start();
                        require_once $sFilePath;
                        ob_end_clean();        // output buffering for easing use of php class files that execute something outside the class definition ( like BE module's index.php !!)
                    }
                    if (class_exists($sClass)) {
                        $oCB = new $sClass();
                        $oCB->oForm = &$this->getForm();
                        if (method_exists($oCB, 'init')) {
                            $oCB->init($this->getForm());    // changed: avoid call-time pass-by-reference
                        }

                        return ['type' => 'php', 'name' => $sName, 'class' => $sClass, 'object' => &$oCB];
                    } else {
                        tx_mkforms_util_Div::mayday('CodeBehind ['.$sCBRef.']: class <b>'.$sClass.'</b> does not exist.');
                    }
                } else {
                    tx_mkforms_util_Div::mayday('CodeBehind ['.$sCBRef.']: file <b>'.$sFileRef.'</b> does not exist.');
                }
                break;

            case 'js':

                if (count($aParts) < 2) {
                    tx_mkforms_util_Div::mayday('CodeBehind ['.$sCBRef.']: you have to provide a class name for javascript codebehind <b>'.$sCBRef."</b>. Please add ':myClassName' after the file-path.");
                } else {
                    $sClass = $aParts[1];
                }

                if (is_file($sFilePath) && is_readable($sFilePath)) {
                    if (0 === (int) filesize($sFilePath)) {
                        tx_mkforms_util_Div::smartMayday_CBJavascript($sFilePath, $sClass, false);
                    }
                    // inclusion of the JS
                    $this->getForm()->getJSLoader()->addCodeBehind($sCBRef, $sFilePath);
                    $sScript = 'Formidable.CodeBehind.'.$sClass.' = new Formidable.Classes.'.$sClass."({formid: '".$this->getForm()->getFormId()."'});";
                    $this->getForm()->aCodeBehindJsInits[] = $sScript;

                    return ['type' => 'js', 'name' => $sName, 'class' => $sClass];
                } else {
                    tx_mkforms_util_Div::smartMayday_CBJavascript($sFilePath, $sClass, false);
                }
                break;

            default:
                tx_mkforms_util_Div::mayday('CodeBehind ['.$sCBRef."]: allowed file extensions are <b>'.php', '.js' and '.ts'</b>.");
        }
    }

    /**
     * Aufruf von CodeBehind-Code. Ajax-Calls (PHP) und JavaScript.
     *
     * @param array $aCB Array mit der Konfiguration des CB aus dem XML
     *
     * @return string
     */
    private function &callCodeBehind($aCB)
    {
        if (!array_key_exists('exec', $aCB)) {
            return;
        } // Nix zu tun

        $aArgs = func_get_args();
        $cbConfig = $aArgs[0];
        $bCbRdt = false;
        // $sCBRef - Der eigentliche Aufruf: cb.doSomething()
        $sCBRef = $aCB['exec'];
        $aExec = $this->getForm()->getTemplateTool()->parseForTemplate($sCBRef);
        $aInlineArgs = $this->getForm()->getTemplateTool()->parseTemplateMethodArgs($aExec[1]['args']);
        // $aExec enthält ein Array mit zwei Einträgen. Der erste ist ein Array mit
        // mit dem CodeBehind-Namen und der zweite ein Array mit der eigentlichen Aufrufmethode
        // array([expr] => btnUserSave_click,  [rec] => '', [args] => '')

        // Es gibt anscheinend den Sonderfall von rdt( als CB -Code...
        if (Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sCBRef, 'rdt(')) {
            $bCbRdt = true;
            $aCbRdtArgs = $this->getForm()->getTemplateTool()->parseTemplateMethodArgs($aExec[0]['args']);
            if (false === ($oRdt = &$this->getForm()->getWidget($aCbRdtArgs[0]))) {
                tx_mkforms_util_Div::mayday('CodeBehind '.$sCBRef.': Refers to an undefined renderlet', $this->getForm());
            }
        }

        // Das sind vermutlich nochmal zusätzliche Parameter für den Aufruf...
        if (count($aInlineArgs) > 0) {
            reset($aInlineArgs);
            foreach ($aInlineArgs as $sKey => $notNeeded) {
                if (is_object($aInlineArgs[$sKey])) {
                    $aArgs[] = &$aInlineArgs[$sKey];
                } else {
                    $aArgs[] = $aInlineArgs[$sKey];
                }
            }
        }

        // $aArgs enthält zwei Einträge. Der erste ist ein Array mit der Config des CB aus dem XML
        // im zweiten Eintrag liegen die Parameter aus dem Request

        $sName = $aExec[0]['expr'];
        $sMethod = $aExec[1]['expr'];

        $tmpArr = $aArgs;
        array_shift($tmpArr);
        $iNbParams = count($tmpArr);
        // back compat with revisions when only one single array-parameter was allowed
        $this->pushUserObjParam((1 === $iNbParams) ? $tmpArr[0] : $tmpArr);
        unset($tmpArr);

        if (array_key_exists($sName, $this->aCodeBehinds['php'])) {
            $sType = 'php';
        } elseif (array_key_exists($sName, $this->aCodeBehinds['js'])) {
            $sType = 'js';
        } else {
            if (true !== $bCbRdt) {
                tx_mkforms_util_Div::mayday('CodeBehind '.$sCBRef.': '.$sName.' is not a declared CodeBehind');
            }
        }

        // Jetzt wird wohl die eigentliche Klasse aufgelöst die aufgerufen wird
        if (true === $bCbRdt) {
            // Das ist der Sonderfall mit dem rdt(
            $sType = 'php';
            $oCbObj = &$oRdt;
            $sClass = get_class($oCbObj);
        } else {
            if ('php' === $sType) {
                $aCB = &$this->aCodeBehinds[$sType][$sName];
                $oCbObj = &$aCB['object'];
                $sClass = $aCB['class'];
            } elseif ('js' === $sType) {
                $aCB = &$this->aCodeBehinds[$sType][$sName]->aConf;
                $oCbObj = &$this->aCodeBehinds[$sType][$sName];
                $sClass = $aCB['class'];
            }
        }

        // forms object has to be the second parameter in php callbacks!!!
        $aArgs = tx_mkforms_util_Div::array_insert($aArgs, 1, ['form' => $this->getForm()]);

        // parameter aus dem xml übernehmen
        $aUserObjParams = $this->getConfig()->get('/params/', $aArgs[0]);
        if (false !== $aUserObjParams && is_array($aUserObjParams)) {
            $aArgs[0] = $this->parseParams($aUserObjParams, $aArgs[0]);
        }

        // Jetzt der Aufruf
        switch ($sType) {
            case 'php':
                array_shift($aArgs);
                if (is_object($oCbObj) && method_exists($oCbObj, $sMethod)) {
                    // sollen die Widget validiert werden?
                    $errors = [];
                    $validate = array_key_exists('validate', $cbConfig) && $cbConfig['validate'] ? $cbConfig['validate'] : '';
                    if ($validate) {
                        // Im ersten Parameter werden die Widgets erwartet
                        // Wir validieren ein Set von Widgets
                        $errors = $this->getForm()->getValidationTool()->validateWidgets4Ajax($aArgs[0]);
                        if (count($errors)) {
                            $this->getForm()->attachErrorsByJS($errors, $validate);
                        } else {
                            // wenn keine validationsfehler aufgetreten sind,
                            // eventuell vorherige validierungs fehler entfernen
                            $this->getForm()->attachErrorsByJS(null, $validate, true);
                        }
                    }

                    if (!count($errors)) {
                        try {
                            $mRes = call_user_func_array([$oCbObj, $sMethod], $aArgs);
                        } catch (Exception  $e) {
                            $verbose = (int) tx_rnbase_configurations::getExtensionCfgValue('rn_base', 'verboseMayday');
                            $dieOnMayday = (int) tx_rnbase_configurations::getExtensionCfgValue('rn_base', 'dieOnMayday');

                            $ret = 'UNCAUGHT EXCEPTION FOR VIEW: '.get_class($oCbObj)."\r\n";

                            if ($verbose) {
                                $ret .= "\r\n".$e->__toString();
                            } else {
                                $ret .= "\r\n".$e->getMessage();
                            }

                            if ($dieOnMayday) {
                                die($ret);
                            } else {
                                echo $ret;
                            }
                        }
                    }
                } else {
                    if (!is_object($oCbObj)) {
                        tx_mkforms_util_Div::mayday('CodeBehind '.$sCBRef.': '.$sClass.' is not a valid PHP class');
                    } else {
                        tx_mkforms_util_Div::mayday('CodeBehind '.$sCBRef.': <b>'.$sMethod.'()</b> method does not exists on object <b>'.$sClass.'</b>');
                    }
                }
                break;

            case 'js':
                // TODO: Das muss noch getestet werden!!
                if (isset($aArgs[0]['params'])) {
                    $aArgs[] = $aArgs[0]['params'];
                }
                $aArgs[0] = $sMethod;
                $mRes = call_user_func_array([$oCbObj, 'majixExec'], $aArgs);
        }

        $this->pullUserObjParam();

        return $mRes;
    }

    /**
     * @return tx_mkforms_util_Config $form
     */
    private function getConfig()
    {
        return $this->config;
    }

    /**
     * Liefert das Form.
     *
     * @return tx_ameosformidable
     */
    private function getForm()
    {
        return $this->form;
    }

    /**
     * @param tx_mkforms_forms_IForm $form
     */
    public static function createInstance(tx_mkforms_util_Config $config, $form)
    {
        $runnable = new tx_mkforms_util_Runnable($config, $form);
        if ($form->getFormId()) { // without a formid the CodeBehind is not valid
            $runnable->initCodeBehinds();
        }

        return $runnable;
    }

    /**
     * Liefert den codeBehind.
     *
     * @param $sNname
     * @param $sType
     */
    public function getCodeBehind($sNname, $sType = 'php')
    {
        if (array_key_exists($sNname, $this->aCodeBehinds[$sType])) {
            return $this->aCodeBehinds[$sType][$sNname];
        }

        //else
        return false;
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/util/class.tx_mkforms_util_Runnable.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/util/class.tx_mkforms_util_Runnable.php'];
}
