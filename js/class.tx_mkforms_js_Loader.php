<?php

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
 */
class tx_mkforms_js_Loader
{
    private static $isLoaded = false;

    protected $oForm = null;

    protected $bLoadScriptaculous = false;

    protected $bLoadScriptaculousDragDrop = false;

    protected $bLoadScriptaculousBuilder = false;

    protected $bLoadtooltip = false;

    protected $headerKeys = array(); // Hier sammeln wir die Keys der zusatzlichen JS-Scripte

    protected $aHeadersAjax = array();    // stores the headers that are added to the page via ajax

    protected $aHeadersWhenInjectNonStandard = array();    // stores the headers when they have to be injected in the page content at given marker

    protected $aCodeBehindJsIncludes = array();

    private function __construct($form)
    {
        $this->init($form);
    }

    /**
     * Returns the form instance.
     *
     * @return tx_ameosformidable
     */
    private function getForm()
    {
        return $this->oForm;
    }

    /**
     * Erstellt eine Instanz dieser Klasse.
     *
     * @param tx_mkforms_forms_IForm $form
     *
     * @return tx_mkforms_js_Loader
     */
    public static function createInstance($form)
    {
        return new tx_mkforms_js_Loader($form);
    }

    private function init($oForm)
    {
        $this->oForm = $oForm;
    }

    /**
     * Genau ein Aufruf in formidable_mainrenderer
     * Das wurde bisher ziemlich verteilt eingebunden.
     * Neue Strategie:
     * - Sammeln aller notwendigen JS-Dateien
     * - ggf. Minify und gzip
     * - Gemeinsame Einbindung der JS-Dateien
     * - Einbindung der Inittask.
     */
    public function includeBaseLibraries()
    {
        if (!$this->useJs()) {
            return;
        }

        if ($this->mayLoadJsFramework()) {
            if (!self::$isLoaded) {
                $this->_includeBaseFramework();
                $this->_includeJSFramework();
                $this->additionalHeaderData(
                    '<!-- consider formidable core loaded after this line -->',
                    'tx_ameosformidable_core',
                    $bFirstPos = false,
                    $sBefore = false,
                    $sAfter = 'tx_mkforms_jsbase'
                );

                self::$isLoaded = true;
            }
            $this->includeFormidablePath();
            $this->includeThisFormDesc();
            $this->includeAdditional();
        }
        $this->includeDebugStyles();
    }

    /**
     * Einige Widgets können zusätzliche Bibliotheken benötigen. Diese können mit dieser Methode eingebunden werden.
     */
    public function includeAdditionalLibraries()
    {
        $this->includeScriptaculous();
        $this->includeTooltip();
    }

    /**
     * Include code behind JS files.
     */
    public function includeCodeBehind()
    {
        if (!empty($this->aCodeBehindJsIncludes)) {
            $this->additionalHeaderData(
                "<!-- CodeBehind includes -->\n".implode("\n", $this->aCodeBehindJsIncludes),
                'codeBehindJSIncludes'
            );
        }
    }

    /**
     * Register code behind script.
     *
     * @param string $ref
     * @param string $file
     */
    public function addCodeBehind($ref, $sFilePath)
    {
        $path = tx_mkforms_util_Div::removeStartingSlash(tx_mkforms_util_Div::toRelPath($sFilePath));
        $path = $this->getAbsRefPrefix().$path;
        $this->aCodeBehindJsIncludes[$ref] = '<script type="text/javascript" src="'.
            $this->getForm()->getJSLoader()->getScriptPath($path).'"></script>';
    }

    private function includeAdditional()
    {
        if (false === ($sLibs = $this->getForm()->getConfig()->get('/meta/libs'))) {
            return;
        }

        $aLibs = Tx_Rnbase_Utility_Strings::trimExplode(',', $sLibs);
        reset($aLibs);
        foreach ($aLibs as $sLib) {
            if ('scriptaculous' === $sLib) {
                $this->loadScriptaculous();
            } elseif ('dragdrop' === $sLib) {
                $this->loadScriptaculousDragDrop();
            } elseif ('builder' === $sLib) {
                $this->loadScriptaculousBuilder();
            } elseif ('tooltip' === $sLib) {
                $this->loadTooltip();
            }
        }
    }

    /**
     * Wether or not Javascript is used.
     *
     * @return bool
     */
    public function useJs()
    {
        return // js nur laden, wenn das framework aktiviert ist
            $this->mayLoadJsFramework()
            // nur laden, wenn im formular nicht deaktiviert.
            && $this->getForm()->_defaultTrue('/meta/accessibility/usejs');
    }

    /**
     * Aufruf in formidable_mainrenderer.
     */
    private function includeThisFormDesc()
    {
        $aConf = array(
            'sFormId' => $this->oForm->formid,
            'Misc' => array(
                'Urls' => array(
                    'Ajax' => array(
                        'event' => tx_mkforms_util_Div::removeEndingSlash($this->getAbsRefPrefix()).'/index.php?eID='
                            .tx_mkforms_util_Div::getAjaxEId().'&object=tx_ameosformidable&servicekey=ajaxevent',
                        'service' => tx_mkforms_util_Div::removeEndingSlash($this->getAbsRefPrefix()).'/index.php?eID='
                            .tx_mkforms_util_Div::getAjaxEId().'&object=tx_ameosformidable&servicekey=ajaxservice',
                    ),
                ),
                'MajixSpinner' => (false !== ($aSpinner = $this->oForm->_navConf('/meta/majixspinner'))) ? $aSpinner : array(),
                'useUserChange' => $this->getForm()->_defaultFalse('/meta/form/useuserchange'),
                'disableButtonsOnSubmit' => $this->getForm()->_defaultTrue('/meta/form/disablebuttonsonsubmit'),
                'displayLoaderOnSubmit' => $this->getForm()->_defaultFalse('/meta/form/displayloaderonsubmit'),
            ),
        );

        $sJson = tx_mkforms_util_Json::getInstance()->encode($aConf);
        $sScript
            = <<<JAVASCRIPT

Formidable.Context.Forms["{$this->oForm->formid}"] = new Formidable.Classes.FormBaseClass(
    {$sJson}
);

JAVASCRIPT;

        if (isset($GLOBALS['BE_USER']) && method_exists($GLOBALS['BE_USER'], 'isAdmin') && $GLOBALS['BE_USER']->isAdmin()
            && $this->oForm->bDebug
        ) {
            $sScript
                .= <<<JAVASCRIPT

Formidable.f("{$this->oForm->formid}").Manager = {
    enabled: true,
    Xml: {
        path: "{$this->oForm->_xmlPath}"
    }
};

JAVASCRIPT;
        }
        // JS-Call einbinden
        $this->getForm()->attachInitTask(
            $sScript,
            'Form \''.$this->oForm->formid.'\' instance description',
            'framework-init'
        );
    }

    private function includeDebugStyles()
    {
        if ($this->oForm->bDebug) {
            $sPath = tx_rnbase_util_Extensions::siteRelPath('mkforms');
            $this->additionalHeaderData(
                "<link rel='stylesheet' type='text/css' href='".$sPath."res/css/debug.css' />",
                'tx_ameosformidable_debugstyles'
            );
        }
    }

    /**
     * Einbindung der Basisskripte des Base-JS-Frameworks. Derzeit werden
     * Prototype und jQuery unterstützt. Wobei aktiv nur noch für jQuery entwickelt wird.
     *
     * - res/jsfwk/prototype/prototype.js
     * - res/jsfwk/prototype/addons/lowpro/lowpro.js
     * - res/jsfwk/prototype/addons/base/Base.js
     * - res/jsfwk/json/json.js
     */
    private function _includeBaseFramework()
    {
        $absRefPrefix = $this->getAbsRefPrefix();
        $includes = $this->getJSFramework()->getBaseIncludes($absRefPrefix);
        $ext = 'mkforms';

        // JSON stringifier
        // http://www.thomasfrank.se/downloadableJS/jsonStringify.js
        $pagePath = $absRefPrefix.tx_rnbase_util_Extensions::siteRelPath($ext).'res/jsfwk/json/json.js';
        $serverPath = tx_rnbase_util_Extensions::extPath($ext).'res/jsfwk/json/json.js';
        $includes[] = tx_mkforms_forms_PageInclude::createInstance($pagePath, $serverPath, 'tx_mkforms_json');

        foreach ($includes as $include) {
            $tag = $include->isJS() ? '<script type="text/javascript" src="'.$this->getScriptPath($include->getPagePath()).'"></script>' :
                '<link href="'.$this->getScriptPath($include->getPagePath(), 'css').'" type="text/css" rel="stylesheet" />';
            $this->additionalHeaderData(
                $tag,
                $include->getKey(),
                $include->isFirstPos(),
                $include->getBeforeKey(),
                $include->getAfterKey()
            );
        }
        // tx_ameosformidable_prototype_fwk -> tx_mkforms_jsbase_fwk
        $this->additionalHeaderData(
            '<!-- consider base JS-Framework loaded after this line -->',
            'tx_mkforms_jsbase_fwk'
        );
    }

    /**
     * Genau ein Aufruf im Form in _render().
     */
    private function includeScriptaculous()
    {
        if (!(true === $this->bLoadScriptaculous && $this->mayLoadScriptaculous())) {
            return;
        }

        $includes = $this->getJSFramework()->getEffectIncludes($this->getAbsRefPrefix());

        foreach ($includes as $include) {
            $tag = $include->isJS() ? '<script type="text/javascript" src="'.$this->getScriptPath($include->getPagePath()).'"></script>' :
                '<link href="'.$this->getScriptPath($include->getPagePath(), 'css').'" type="text/css" rel="stylesheet" />';
            $this->additionalHeaderData(
                $tag,
                $include->getKey(),
                $include->isFirstPos(),
                $include->getBeforeKey(),
                $include->getAfterKey()
            );
        }

        // scriptaculous
        $sNextAfter = 'tx_ameosformidable_scriptaculous_effects';

        if (true === $this->bLoadScriptaculousDragDrop) {
            $sPath = $this->getAbsRefPrefix().tx_rnbase_util_Extensions::siteRelPath('mkforms')
                .'res/jsfwk/scriptaculous/dragdrop.js';

            $this->additionalHeaderData(
                '<script type="text/javascript" src="'.$this->getScriptPath($sPath).'"></script>',
                'tx_ameosformidable_scriptaculous_dragdrop',
                $bFirstPos = false,
                $sBefore = false,
                $sNextAfter
            );

            $sNextAfter = 'tx_ameosformidable_scriptaculous_dragdrop';
        }

        if (true === $this->bLoadScriptaculousBuilder) {
            $sPath = $this->getAbsRefPrefix().tx_rnbase_util_Extensions::siteRelPath('mkforms')
                .'res/jsfwk/scriptaculous/builder.js';

            $this->additionalHeaderData(
                '<script type="text/javascript" src="'.$this->getScriptPath($sPath).'"></script>',
                'tx_ameosformidable_scriptaculous_builder',
                $bFirstPos = false,
                $sBefore = false,
                $sNextAfter
            );

            $sNextAfter = 'tx_ameosformidable_scriptaculous_builder';
        }

        $this->additionalHeaderData(
            '<!-- consider scriptaculous loaded after this line -->',
            'tx_ameosformidable_scriptaculous_fwk',
            $bFirstPos = false,
            $sBefore = false,
            $sNextAfter
        );
    }

    private function _includeJSFramework()
    {
        $sPath = $this->getAbsRefPrefix().tx_rnbase_util_Extensions::siteRelPath('mkforms').'res/jsfwk/framework.js';
        $tag = '<script type="text/javascript" src="'.$this->getScriptPath($sPath).'"></script>';
        $this->additionalHeaderData(
            $tag,
            'tx_ameosformidable_jsframework',
            $bFirstPos = false,
            $sBefore = false,
            $sAfter = 'tx_mkforms_jsbase_fwk'
        );
    }

    public function getAbsRefPrefix()
    {
        $absRefPrefix = $this->getForm()->getConfTS('absRefPrefix');
        if (null === $absRefPrefix) {
            $absRefPrefix = Tx_Rnbase_Utility_T3General::getIndpEnv('TYPO3_SITE_URL');
        }

        return $absRefPrefix;
    }

    /**
     * Bindet das JS für die Initialisierung des Formidable-Objekts ein.
     */
    private function includeFormidablePath()
    {
        $sPath = $this->getAbsRefPrefix().tx_rnbase_util_Extensions::siteRelPath('mkforms');
        $sScript
            = <<<JAVASCRIPT
    Formidable.initialize({path: '{$sPath}'});
JAVASCRIPT;
        $this->getForm()->attachInitTask($sScript, 'Framework Formidable.path initialization');
    }

    /**
     * Wird im Accordion, tx_mkforms_widgets_modalbox_Main aufgerufen.
     */
    public function loadScriptaculous()
    {
        $this->bLoadScriptaculous = true;
    }

    /**
     * Aufruf in tx_mkforms_widgets_jstree_Main.
     */
    public function loadScriptaculousDragDrop()
    {
        $this->loadScriptaculous();
        $this->bLoadScriptaculousDragDrop = true;
    }

    /**
     * Aufruf im Form und hier.
     */
    public function loadScriptaculousBuilder()
    {
        $this->bLoadScriptaculousBuilder = true;
    }

    public function loadTooltip()
    {
        $this->loadScriptaculous();
        $this->loadScriptaculousBuilder();
        $this->bLoadtooltip = true;
    }

    /**
     * Genau ein Aufruf im Form::_render().
     */
    private function includeTooltip()
    {
        if (true === $this->bLoadtooltip) {
            // tooltip css
            $sPath
                =
                $this->getAbsRefPrefix().tx_rnbase_util_Extensions::siteRelPath('mkforms').'res/jsfwk/tooltip/tooltips.css';

            $this->additionalHeaderData(
                '<link rel="stylesheet" type="text/css" href="'.$sPath.'" />',
                'tx_ameosformidable_tooltip_css',
                $bFirstPos = false,
                $sBefore = false,
                'tx_ameosformidable_scriptaculous_fwk'
            );

            // tooltip js
            $sPath
                =
                $this->getAbsRefPrefix().tx_rnbase_util_Extensions::siteRelPath('mkforms').'res/jsfwk/tooltip/tooltips.js';

            $this->additionalHeaderData(
                '<script type="text/javascript" src="'.$sPath.'"></script>',
                'tx_ameosformidable_tooltip_js',
                $bFirstPos = false,
                $sBefore = false,
                'tx_ameosformidable_tooltip_css'
            );
        }
    }

    /**
     * Die Methode entscheidet darüber, ob ein JS-Framework geladen wird.
     *
     * @return bool
     */
    public function mayLoadJsFramework()
    {
        if ('BE' == tx_mkforms_util_Div::getEnvExecMode()) {
            return true;
        }
        if (true !== $this->oForm->_defaultTrue('/meta/loadjsframework')) {
            return false;
        }

        return (int) $this->getForm()->getConfTS('loadJsFramework') > 0;
    }

    /**
     * Hier kann man per TS oder XML das Laden von Scriptaculous verhindern.
     *
     * @return bool
     */
    private function mayLoadScriptaculous()
    {
        if (true !== $this->oForm->_defaultTrue('/meta/mayloadscriptaculous')) {
            return false;
        }

        return (int) $this->getForm()->getConfTS('mayLoadScriptaculous') > 0;
    }

    /**
     * Returns the js wrapper.
     *
     * @return tx_mkforms_forms_IJSFramework
     */
    private function getJSFramework()
    {
        if (!$this->jsWrapper) {
            $wrapperClass = $this->getForm()->getConfTS('jslib');
            $wrapperClass = $wrapperClass ? $wrapperClass : 'tx_mkforms_js_DefaultFramework';
            $this->jsWrapper = tx_rnbase::makeInstance(
                $wrapperClass,
                $this->getForm()->getConfigurations(),
                $this->getForm()->getConfId().'jsframework.'
            );
        }

        return $this->jsWrapper;
    }

    /**
     * Liefert den Namen des aktuellen JS-Frameworks.
     *
     * @return string
     */
    public function getJSFrameworkId()
    {
        return $this->getJSFramework()->getId();
    }

    private function minified()
    {
        return (int) $this->getForm()->getConfTS('minify.enabled') > 0;
    }

    private function gziped()
    {
        return (int) $this->getForm()->getConfTS('minify.gzip') > 0;
    }

    /**
     * Packt einen String in eine temporäre datei.
     *
     * @param string $str   Der JS oder CSS-Code
     * @param string $ext   "js" oder "css"
     * @param string $sDesc Beschreibungstext für HTML-Kommentar
     *
     * @return string das HTML-Tag für die Integration in der Seite
     */
    public function inline2TempFile($str, $ext, $sDesc = '')
    {
        $output = '';

        if (is_string($str)) {
            if ('' != $sDesc) {
                $sDesc = "\n\n<!-- MKFORMS: ".str_replace(array('<!--', '-->'), '', $sDesc).' -->';
            }

            // Create filename / tags:
            $script = '';
            switch ($ext) {
                case 'js':

                    $script = 'typo3temp/mkforms/javascript_'.substr(md5($str), 0, 10).'.js';
                    $output = $sDesc."\n".'<script type="text/javascript" src="'.htmlspecialchars(
                        $this->getAbsRefPrefix().$script
                    ).'"></script>'."\n\n";
                    break;

                case 'css':
                    $script = 'typo3temp/mkforms/stylesheet_'.substr(md5($str), 0, 10).'.css';
                    $output = $sDesc."\n".'<link rel="stylesheet" type="text/css" href="'.htmlspecialchars(
                        $this->getAbsRefPrefix().$script
                    ).'" />'."\n\n";
                    break;
            }

            // Write file:
            if ($script) {
                if (!@is_file(\Sys25\RnBase\Utility\Environment::getPublicPath().$script)) {
                    Tx_Rnbase_Utility_T3General::writeFile(\Sys25\RnBase\Utility\Environment::getPublicPath().$script, $str);
                }
            }
        }

        return $output;
    }

    /**
     * Fügt zusätzliche Headerdaten in den Response ein.
     *
     * @param [type] $sData:     ...
     * @param [type] $sKey:      ...
     * @param [type] $bFirstPos: ...
     *
     * @return [type] ...
     */
    public function additionalHeaderData($sData, $sKey = false, $bFirstPos = false, $sBefore = false, $sAfter = false)
    {
        if ($sKey && !array_key_exists($sKey, $this->headerKeys)) {
            $this->headerKeys[$sKey] = 1;
        }
        if (TYPO3_MODE === 'FE') {
            if ($this->mayUseStandardHeaderInjection()) {
                $aHeaders = &$GLOBALS['TSFE']->additionalHeaderData;
            } else {
                $aHeaders = &$this->aHeadersWhenInjectNonStandard;
            }
        }

        if ('EID' === tx_mkforms_util_Div::getEnvExecMode()) {
            if (false === $sKey) {
                $this->aHeadersAjax[] = $sData;
            } else {
                $this->aHeadersAjax[$sKey] = $sData;
            }
        } else {
            if (false === $sKey) {
                if (true === $bFirstPos) {
                    $aHeaders = array(rand() => $sData) + $aHeaders;
                } elseif (false !== $sBefore || false !== $sAfter) {
                    $bBefore = (false !== $sBefore);
                    $sLookFor = $bBefore ? $sBefore : $sAfter;
                    $aHeaders = tx_mkforms_util_Div::array_insert(
                        $aHeaders,
                        $sLookFor,
                        array(count($aHeaders) => $sData),
                        $bBefore
                    );
                } else {
                    $aHeaders[] = $sData;
                }
            } else {
                if (true === $bFirstPos) {
                    $aHeaders = array($sKey => $sData) + $aHeaders;
                } elseif (false !== $sBefore || false !== $sAfter) {
                    $bBefore = (false !== $sBefore);
                    $sLookFor = $bBefore ? $sBefore : $sAfter;
                    $aHeaders = tx_mkforms_util_Div::array_insert($aHeaders, $sLookFor, array($sKey => $sData), $bBefore);
                } else {
                    $aHeaders[$sKey] = $sData;
                }
            }
        }
    }

    /**
     * Liefert die Non-Standard-Headers.
     *
     * @return array
     */
    private function getHeadersWhenInjectNonStandard()
    {
        return $this->aHeadersWhenInjectNonStandard;
    }

    /**
     * Liefert zusätzliche Header, die bei Ajax-Calls eingebunden werden sollen.
     *
     * @return array
     */
    public function getAjaxHeaders()
    {
        return $this->aHeadersAjax;
    }

    /**
     * Wir wollen verhindern, das JS-Dateien bei Ajax-Calls doppelt geladen werden. Daher wird das Formidable-Objekt
     * mit den Keys der JS-Libs gefüttert.
     */
    public function setLoadedScripts()
    {
        $keys = array_keys($this->headerKeys);
        $script = '';
        foreach ($keys as $key) {
            $script .= 'Formidable.addScript(\''.$key.'\'); '."\n";
        }
        $this->getForm()->attachInitTask($script, 'Set loaded scripts', 'finalTask');
    }

    /**
     * Das wird im Form in der Methode _render aufgerufen. Funktion noch unklar und das funktioniert wohl auch noch nicht...
     */
    public function injectHeaders()
    {
        if (false !== ($sHeaderMarker = $this->getMarkerForHeaderInjection())) {
            $GLOBALS['tx_ameosformidable']['headerinjection'][] = array(
                'marker' => $sHeaderMarker,
                'headers' => $this->getHeadersWhenInjectNonStandard(),
            );
        } elseif ($this->manuallyInjectHeaders()) {
            $GLOBALS['tx_ameosformidable']['headerinjection'][] = array(
                'manual' => true,
                'headers' => $this->getHeadersWhenInjectNonStandard(),
            );
        }
    }

    public function mayUseStandardHeaderInjection()
    {
        return (false === $this->getMarkerForHeaderInjection()) && (false === $this->manuallyInjectHeaders());
    }

    public function getMarkerForHeaderInjection()
    {
        if (isset($GLOBALS['TSFE']->tmpl->setup['config.']['tx_ameosformidable.']['injectHeadersInContentAtMarker'])
            && '' !== ($sHeaderMarker = trim(
                $GLOBALS['TSFE']->tmpl->setup['config.']['tx_ameosformidable.']['injectHeadersInContentAtMarker']
            ))
        ) {
            return $sHeaderMarker;
        }

        return false;
    }

    public function manuallyInjectHeaders()
    {
        return (int) $this->getForm()->getConfTS('injectHeadersManually') > 0;
    }

    /**
     * Prüft, ob für dieses Script eine minimierte Version vorligt
     * und gibt diese entsprechend der konfiguration zurück.
     *
     * @param string $sPath
     *
     * @return string
     */
    public function getScriptPath($sPath, $sScriptErw = 'js')
    {
        $newPath = $sPath;
        $sScriptErw = '.'.$sScriptErw;
        // soll minimierte Version genutzt werden
        if ($this->minified()) {
            $sSitePath = $this->getAbsRefPrefix().tx_rnbase_util_Extensions::siteRelPath('mkforms');
            $sFile = substr($sPath, strlen($sSitePath), strrpos($sPath, $sScriptErw) - strlen($sSitePath));
            // prüfen ob gzip genutzt werden soll, wenn ja auf datei prüfen.
            if ($this->gziped() && file_exists(tx_rnbase_util_Extensions::extPath('mkforms').$sFile.'.min'.$sScriptErw.'.php')) {
                $sGZipPath = $sSitePath.$sFile.'.min'.$sScriptErw.'.php';
                $newPath = $sGZipPath;
            } // prüfen ob minimiertes js verfügbar ist.
            elseif (file_exists(tx_rnbase_util_Extensions::extPath('mkforms').$sFile.'.min'.$sScriptErw)) {
                $sMinPath = $sSitePath.$sFile.'.min'.$sScriptErw;
                $newPath = $sMinPath;
            }
            // else, keine minimierte version gefunden, nutze standard Datei
        }

        return $newPath;
    }

    /**
     * @param tx_mkforms_forms_Base $form
     */
    public function setForm(tx_mkforms_forms_Base $form)
    {
        $this->oForm = $form;
    }

    public function unsetForm()
    {
        unset($this->oForm);
    }
}

if (defined('TYPO3_MODE')
    && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/util/class.tx_mkforms_js_Loader.php']
) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/util/class.tx_mkforms_js_Loader.php'];
}
