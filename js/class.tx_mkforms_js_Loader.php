<?php

use Sys25\RnBase\Utility\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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

    protected $headerKeys = []; // Hier sammeln wir die Keys der zusatzlichen JS-Scripte

    protected $aHeadersAjax = [];    // stores the headers that are added to the page via ajax

    protected $aHeadersWhenInjectNonStandard = [];    // stores the headers when they have to be injected in the page content at given marker

    protected $aCodeBehindJsIncludes = [];

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
        $this->aCodeBehindJsIncludes[$ref] = '<script src="'.
            $this->getForm()->getJSLoader()->getScriptPath($path).'"></script>';
    }

    private function includeAdditional()
    {
        if (false === ($sLibs = $this->getForm()->getConfig()->get('/meta/libs'))) {
            return;
        }

        $aLibs = \Sys25\RnBase\Utility\Strings::trimExplode(',', $sLibs);
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
        $aConf = [
            'sFormId' => $this->oForm->formid,
            'Misc' => [
                'Urls' => [
                    'Ajax' => [
                        'event' => tx_mkforms_util_Div::getCurrentBaseUrl().'/?mkformsAjaxId='
                            .tx_mkforms_util_Div::getAjaxEId().'&pageId='.$GLOBALS['TSFE']->id.'&object=tx_ameosformidable&servicekey=ajaxevent',
                        'service' => tx_mkforms_util_Div::getCurrentBaseUrl().'/?mkformsAjaxId='
                            .tx_mkforms_util_Div::getAjaxEId().'&pageId='.$GLOBALS['TSFE']->id.'&object=tx_ameosformidable&servicekey=ajaxservice',
                    ],
                ],
                'MajixSpinner' => (false !== ($aSpinner = $this->oForm->_navConf('/meta/majixspinner'))) ? $aSpinner : [],
                'useUserChange' => $this->getForm()->_defaultFalse('/meta/form/useuserchange'),
                'disableButtonsOnSubmit' => $this->getForm()->_defaultTrue('/meta/form/disablebuttonsonsubmit'),
                'displayLoaderOnSubmit' => $this->getForm()->_defaultFalse('/meta/form/displayloaderonsubmit'),
            ],
        ];

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
            $sPath = \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix(
                \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mkforms')
            );
            $this->additionalHeaderData(
                "<link rel='stylesheet' type='text/css' href='".$sPath."Resources/Public/CSS/debug.css' />",
                'tx_ameosformidable_debugstyles'
            );
        }
    }

    /**
     * Einbindung der Basisskripte des Base-JS-Frameworks. Derzeit werden
     * Prototype und jQuery unterstützt. Wobei aktiv nur noch für jQuery entwickelt wird.
     *
     * - Resources/Public/JavaScript/prototype/prototype.js
     * - Resources/Public/JavaScript/prototype/addons/lowpro/lowpro.js
     * - Resources/Public/JavaScript/prototype/addons/base/Base.js
     * - Resources/Public/JavaScript/json/json.js
     */
    private function _includeBaseFramework()
    {
        $absRefPrefix = $this->getAbsRefPrefix();
        $includes = $this->getJSFramework()->getBaseIncludes($absRefPrefix);
        $ext = 'mkforms';

        // JSON stringifier
        // http://www.thomasfrank.se/downloadableJS/jsonStringify.js
        $pagePath = $absRefPrefix.
            \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($ext)).
            'Resources/Public/JavaScript/json/json.js';
        $serverPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($ext).'Resources/Public/JavaScript/json/json.js';
        $includes[] = tx_mkforms_forms_PageInclude::createInstance($pagePath, $serverPath, 'tx_mkforms_json');

        foreach ($includes as $include) {
            $tag = $include->isJS() ? '<script src="'.$this->getScriptPath($include->getPagePath()).'"></script>' :
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
            $tag = $include->isJS() ? '<script src="'.$this->getScriptPath($include->getPagePath()).'"></script>' :
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

        $mkformsPath = \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix(
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mkforms')
        );
        if (true === $this->bLoadScriptaculousDragDrop) {
            $sPath = $this->getAbsRefPrefix().$mkformsPath.'Resources/Public/JavaScript/scriptaculous/dragdrop.js';

            $this->additionalHeaderData(
                '<script src="'.$this->getScriptPath($sPath).'"></script>',
                'tx_ameosformidable_scriptaculous_dragdrop',
                $bFirstPos = false,
                $sBefore = false,
                $sNextAfter
            );

            $sNextAfter = 'tx_ameosformidable_scriptaculous_dragdrop';
        }

        if (true === $this->bLoadScriptaculousBuilder) {
            $sPath = $this->getAbsRefPrefix().$mkformsPath.'Resources/Public/JavaScript/scriptaculous/builder.js';

            $this->additionalHeaderData(
                '<script src="'.$this->getScriptPath($sPath).'"></script>',
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
        $sPath = $this->getAbsRefPrefix().
            \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix(
                \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mkforms')
            ).
            'Resources/Public/JavaScript/framework.js';
        $tag = '<script src="'.$this->getScriptPath($sPath).'"></script>';
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
            $absRefPrefix = \Sys25\RnBase\Utility\T3General::getIndpEnv('TYPO3_SITE_URL');
        }

        return $absRefPrefix;
    }

    /**
     * Bindet das JS für die Initialisierung des Formidable-Objekts ein.
     */
    private function includeFormidablePath()
    {
        $sPath = $this->getAbsRefPrefix().\TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix(
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mkforms')
        );
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
            $mkformsPath = \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix(
                \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mkforms')
            );
            $sPath = $this->getAbsRefPrefix().$mkformsPath.'Resources/Public/JavaScript/tooltip/tooltips.css';

            $this->additionalHeaderData(
                '<link rel="stylesheet" type="text/css" href="'.$sPath.'" />',
                'tx_ameosformidable_tooltip_css',
                $bFirstPos = false,
                $sBefore = false,
                'tx_ameosformidable_scriptaculous_fwk'
            );

            // tooltip js
            $sPath = $this->getAbsRefPrefix().$mkformsPath.'Resources/Public/JavaScript/tooltip/tooltips.js';

            $this->additionalHeaderData(
                '<script src="'.$sPath.'"></script>',
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
            $this->jsWrapper = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
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
                $sDesc = "\n\n<!-- MKFORMS: ".str_replace(['<!--', '-->'], '', $sDesc).' -->';
            }

            $directory = 'typo3temp/assets/mkforms/';
            // Create filename / tags:
            $script = '';
            switch ($ext) {
                case 'js':
                    $script = 'javascript_'.substr(md5($str), 0, 10).'.js';
                    $output = $sDesc."\n".'<script src="'.htmlspecialchars(
                        $this->getAbsRefPrefix().$directory.$script
                    ).'"></script>'."\n\n";
                    break;

                case 'css':
                    $script = 'stylesheet_'.substr(md5($str), 0, 10).'.css';
                    $output = $sDesc."\n".'<link rel="stylesheet" type="text/css" href="'.htmlspecialchars(
                        $this->getAbsRefPrefix().$directory.$script
                    ).'" />'."\n\n";
                    break;
            }

            // Write file:
            if ($script) {
                if (!\is_dir($directory)) {
                    GeneralUtility::mkdir_deep($directory);
                }
                if (!@is_file(Environment::getPublicPath().$directory.$script)) {
                    \Sys25\RnBase\Utility\T3General::writeFile(Environment::getPublicPath().$directory.$script, $str);
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
                    $aHeaders = [rand() => $sData] + $aHeaders;
                } elseif (false !== $sBefore || false !== $sAfter) {
                    $bBefore = (false !== $sBefore);
                    $sLookFor = $bBefore ? $sBefore : $sAfter;
                    $aHeaders = tx_mkforms_util_Div::array_insert(
                        $aHeaders,
                        $sLookFor,
                        [count($aHeaders) => $sData],
                        $bBefore
                    );
                } else {
                    $aHeaders[] = $sData;
                }
            } else {
                if (true === $bFirstPos) {
                    $aHeaders = [$sKey => $sData] + $aHeaders;
                } elseif (false !== $sBefore || false !== $sAfter) {
                    $bBefore = (false !== $sBefore);
                    $sLookFor = $bBefore ? $sBefore : $sAfter;
                    $aHeaders = tx_mkforms_util_Div::array_insert($aHeaders, $sLookFor, [$sKey => $sData], $bBefore);
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
            $GLOBALS['tx_ameosformidable']['headerinjection'][] = [
                'marker' => $sHeaderMarker,
                'headers' => $this->getHeadersWhenInjectNonStandard(),
            ];
        } elseif ($this->manuallyInjectHeaders()) {
            $GLOBALS['tx_ameosformidable']['headerinjection'][] = [
                'manual' => true,
                'headers' => $this->getHeadersWhenInjectNonStandard(),
            ];
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
            $sSitePath = $this->getAbsRefPrefix().\TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix(
                \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mkforms')
            );
            $sFile = substr($sPath, strlen($sSitePath), strrpos($sPath, $sScriptErw) - strlen($sSitePath));
            // prüfen ob gzip genutzt werden soll, wenn ja auf datei prüfen.
            if ($this->gziped() && file_exists(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mkforms').$sFile.'.min'.$sScriptErw.'.php')) {
                $sGZipPath = $sSitePath.$sFile.'.min'.$sScriptErw.'.php';
                $newPath = $sGZipPath;
            } // prüfen ob minimiertes js verfügbar ist.
            elseif (file_exists(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mkforms').$sFile.'.min'.$sScriptErw)) {
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
