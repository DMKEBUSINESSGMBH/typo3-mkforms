<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007 Jerome Schneider (typo3dev@ameos.com)
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
 * Formidable API
 *
 * @author    Jerome Schneider <typo3dev@ameos.com>
 */

define('AMEOSFORMIDABLE_EVENT_SUBMIT_FULL', 'AMEOSFORMIDABLE_EVENT_SUBMIT_FULL');
define('AMEOSFORMIDABLE_EVENT_SUBMIT_REFRESH', 'AMEOSFORMIDABLE_EVENT_SUBMIT_REFRESH');
define('AMEOSFORMIDABLE_EVENT_SUBMIT_TEST', 'AMEOSFORMIDABLE_EVENT_SUBMIT_TEST');
define('AMEOSFORMIDABLE_EVENT_SUBMIT_DRAFT', 'AMEOSFORMIDABLE_EVENT_SUBMIT_DRAFT');
define('AMEOSFORMIDABLE_EVENT_SUBMIT_CLEAR', 'AMEOSFORMIDABLE_EVENT_SUBMIT_CLEAR');
define('AMEOSFORMIDABLE_EVENT_SUBMIT_SEARCH', 'AMEOSFORMIDABLE_EVENT_SUBMIT_SEARCH');

define('AMEOSFORMIDABLE_LEXER_VOID', 'AMEOSFORMIDABLE_LEXER_VOID');
define('AMEOSFORMIDABLE_LEXER_FAILED', 'AMEOSFORMIDABLE_LEXER_FAILED');
define('AMEOSFORMIDABLE_LEXER_BREAKED', 'AMEOSFORMIDABLE_LEXER_BREAKED');

define('AMEOSFORMIDABLE_XPATH_FAILED', 'AMEOSFORMIDABLE_XPATH_FAILED');
define('AMEOSFORMIDABLE_TS_FAILED', 'AMEOSFORMIDABLE_TS_FAILED');

define('AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN', '__');
define('AMEOSFORMIDABLE_NESTED_SEPARATOR_END', '');

define('AMEOSFORMIDABLE_NOTSET', 'AMEOSFORMIDABLE_NOTSET');

tx_rnbase::load('tx_mkforms_util_Div');
tx_rnbase::load('tx_mkforms_util_Loader');
tx_rnbase::load('tx_mkforms_util_Runnable');
tx_rnbase::load('tx_mkforms_util_Templates');
tx_rnbase::load('tx_mkforms_util_Json');
tx_rnbase::load('tx_mkforms_forms_IForm');
tx_rnbase::load('tx_mkforms_session_Factory');
tx_rnbase::load('tx_rnbase_util_Typo3Classes');
tx_rnbase::load('tx_rnbase_util_TYPO3');

require_once(tx_rnbase_util_Extensions::extPath('mkforms') . 'api/class.mainobject.php');
require_once(tx_rnbase_util_Extensions::extPath('mkforms') . 'api/class.maindataset.php');
require_once(tx_rnbase_util_Extensions::extPath('mkforms') . 'api/class.maindatasource.php');
require_once(tx_rnbase_util_Extensions::extPath('mkforms') . 'api/class.mainvalidator.php');
require_once(tx_rnbase_util_Extensions::extPath('mkforms') . 'api/class.maindatahandler.php');
require_once(tx_rnbase_util_Extensions::extPath('mkforms') . 'api/class.mainrenderer.php');
require_once(tx_rnbase_util_Extensions::extPath('mkforms') . 'api/class.mainrenderlet.php');
require_once(tx_rnbase_util_Extensions::extPath('mkforms') . 'api/class.mainactionlet.php');

class tx_ameosformidable implements tx_mkforms_forms_IForm
{

    /**
     * @var    int    Die PageID wird für das caching benötigt. In AjaxCalls haben wir diese ID nicht!
     */
    public $iPageId = 0;

    public $bInited = false;

    public $bInitFromTs = false;

    public $_xmlData = null;

    public $_xmlPath = null;

    public $_aConf = null; // Das ist die XML-Konfiguration

    public $sExtPath = null;    // server abs path to formidable

    public $sExtRelPath = null;    // server path to formidable, relative to server root

    public $sExtWebPath = null;    // web path to formidable

    public $_aValidators = null;

    public $_aDataSources = null;

    public $_aDataHandlers = null;

    public $_aRenderers = null;

    public $_aRenderlets = null;

    public $_aActionlets = null;

    public $oRenderer = null;

    public $oDataHandler = null;

    /**
     * @var formidable_mainrenderlet[]
     */
    public $aORenderlets = array();

    public $aODataSources = array();

    public $oSandBox = null;        // stores sandbox for xml-level user-defined 'macros'

    public $oJs = null;

    public $aInitTasksUnobtrusive = array();

    public $aInitTasks = array(); // Sammlung von JS-Aufrufen für DOM-Loaded

    public $aInitTasksOutsideLoad = array();    // tinyMCE cannot be init'd within Event.observe(window, 'load', function() {})

    public $aInitTasksAjax = array();

    public $aPostInitTasks = array();    // post init tasks are JS init executed after the init tasks

    public $aPostInitTasksAjax = array();        // modalbox relies on that for it's HTML is added to the page in an init task when ajax

    // and so, some renderlets, like swfupload, need a chance to execute something when the HTML is ready

    public $_aValidationErrors = array();

    public $_aValidationErrorsByHtmlId = array();

    public $_aValidationErrorsInfos = array();

    public $_aValidationErrorsTypes = array();

    public $bDebug = false;

    public $aDebug = array();

    public $start_tstamp = null;

    public $formid = '';

    public $_oParent = null;

    public $oParent = null;        // alias for _oParent ...

    protected $_useGP = false;

    protected $_useGPWithUrlDecode = false;

    public $bRendered = false;

    public $aSteps = false;    // array of steps for multi-steps forms

    public $_aStep = false;    // current step extracted from session and stored for further user

    public $iForcedEntryId = false;

    public $_aInjectedData = array();    // contains data to inject in the form at init

    public $aLastTs = array();

    public $cObj = null;

    protected $storeFormInSession = false;    // whether or not to keep FORM in session for further use (ex: processing ajax events)

    public $bStoreParentInSession = false;    // whether or not to keep parent in session, if form is stored (ie $bStoreFormInSession==TRUE)

    public $bTestMode = false;    // im Test-mode ist $storeFormInSession uninteressant um fehler zu vermeiden

    public $aServerEvents = array();

    public $aAjaxEvents = array();

    public $aAjaxArchive = array();    // archives the successive ajax events that are triggered during the page lifetime

    // meant to be accessed thru getPreviousAjaxRequest() and getPreviousAjaxParams()
    public $aAjaxServices = array();

    public $aTempDebug = array();

    public $aCrossRequests = array();

    public $aOnloadEvents
        = array(    // stores events that have to be thrown at onload ( onDOMReady actually )
            'ajax' => array(),
            'client' => array()
        );

    public $aSkinManifests = array();

    public $__aRunningObjects = array();

    public $oHtml = false;

    public $aRdtEvents = array();

    public $aRdtEventsAjax = array();    // stores the events that are added to the page via ajax

    public $aPreRendered = array();

    /**
     * @var formidableajax|bool
     */
    public $oMajixEvent = false;

    public $aAvailableCheckPoints
        = array(
            'start',
            'before-compilation',    // kept for back-compat, but should not be here
            'after-compilation',
            'before-init',
            'before-init-renderer',
            'after-init-renderer',
            'before-init-renderlets',
            'after-init-renderlets',
            'before-init-datahandler',
            'after-init-datahandler',
            'after-init',
            'before-render',
            'after-validation',
            'after-validation-ok',
            'after-validation-nok',
            'after-render',
            'before-actionlets',
            'after-actionlets',
            'end-creation',
            'end-edition',
            'end',
        );

    public $aAddPostVars = false;

    public $aRawPost = array();    // stores the POST vars array, hashed by formid

    public $aRawGet = array();        // stores the GET vars array, hashed by formid

    public $aRawFile = array();    // stores the FILE vars array, hashed by formid

    public $sFormAction = false;    // if FALSE, form action will be determined from GET() and thus, transparent

    public $aFormAction = array();

    public $aParamsToRemove = array();

    public $aCB = array();

    public $aCodeBehindJsInits = array();

    public $aCurrentRdtStack = array();    // stacks the current renderlets (in majix context, and in normal context)

    private $submittedValue = false;

    private $submitter = false;

    private $bIsFullySubmitted = false;

    private $configurations = null;

    private $confid = null;

    private $runnable = null;

    private $validationTool = null;

    private $templateTool = null;

    /**
     * Session id des fe users.
     * wird für den request token beim CSRF schutz verwendet.
     *
     * @var string
     */
    private $sSessionId;

    /**
     * @var tx_mkforms_util_Config
     */
    private $config = null;

    public function __construct()
    {
    }

    /**
     * Liefert ein cObj
     *
     * @return TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    public function &getCObj()
    {
        if (!is_object($this->cObj)) {
            // Das cObj schein beim cachen verloren zu gehen
            if (is_object($this->configurations)) {
                $this->cObj = $this->configurations->getCObj();
            }
            if (!is_object($this->cObj)) {
                $this->cObj = tx_rnbase::makeInstance(tx_rnbase_util_Typo3Classes::getContentObjectRendererClass());
            }
        }

        return $this->cObj;
    }

    /**
     * Return the typoscript configurations object
     *
     * @return tx_rnbase_configurations
     */
    public function getConfigurations()
    {
        return $this->configurations;
    }

    /**
     * Basic typoscript confid-path
     *
     * @return string
     */
    public function getConfId()
    {
        return $this->confid;
    }

    /**
     * Returns a value from TS configurations. The confid will be used relativ to $this->confid.
     *
     * @param string $confid
     *
     * @return mixed
     */
    public function getConfTS($confid)
    {
        return $this->getConfigurations()->get($this->confid . $confid);
    }

    /**
     * Set TS-Configuration. This is either the given instance or a new instance based on config.tx_mkforms
     *
     * @param tx_rnbase_configurations $config
     * @param string                   $confid
     */
    public function setConfigurations($config, $confid)
    {
        if (!is_object($config)) {
            $config = tx_rnbase::makeInstance('tx_rnbase_configurations');
            $config->init($GLOBALS['TSFE']->config['config']['tx_mkforms.'], $config->getCObj(1), 'mkforms', 'mkforms');
        }
        $this->configurations = $config;
        $this->confid = $confid;
    }
    /*********************************
     *
     * FORMidable initialization
     *
     *********************************/

    /**
     * Standard init function
     * Initializes :
     * - the reference to the parent Extension ( stored in $this->_oParent )
     * - the XML conf
     * - the internal collection of Validators
     * - the internal collection of DataHandlers
     * - the internal collection of Renderers
     * - the internal collection of Renderlets
     * - the Renderer as configured in the XML conf in the /formidable/control/renderer/ section
     * - the DataHandler as configured in the XML conf in the /formidable/control/datahandler/ section
     *
     *        //    CURRENT SERVER EVENT CHECKPOINTS ( means when to process the even; ex:  <onclick runat="server"
     *        when="after-compilation" /> )
     *        //    DEFAULT IS *after-init*
     *        //
     *        //        start
     *        //        before-compilation
     *        //        before-compilation
     *        //        after-compilation
     *        //        before-init
     *        //        before-init-renderer
     *        //        after-init-renderer
     *        //        before-init-renderlets
     *        //        after-init-renderlets
     *        //        before-init-datahandler
     *        //        after-init-datahandler
     *        //        after-init
     *        //        before-render
     *        //        after-render
     *        //        end
     *
     * @param                          object          Parent extension using FORMidable
     * @param                          mixed           Absolute path to the XML configuration file
     * @param    int                   $iForcedEntryId :
     * @param tx_rnbase_configurations $configurations TS-Configuration
     * @param string                   $confid         ;
     *
     * @return    void
     */
    public function init(&$oParent, $mXml, $iForcedEntryId = false, $configurations = false, $confid = '')
    {
        $this->start_tstamp = Tx_Rnbase_Utility_T3General::milliseconds();

        if (!$this->isTestMode()) {
            $sesMgr = tx_mkforms_session_Factory::getSessionManager();
            $sesMgr->setForm($this);
        }

        $this->makeHtmlParser();

        //In tests brauchen wir das nicht da es bei installiertem
        //ameos nur zu fehler führen würde wegen dem cache
        if (tx_mkforms_util_Div::getEnvExecMode() !== 'FE'
            && !$this->isTestMode()
        ) {    // virtualizing FE for BE and eID (ajax) modes
            tx_mkforms_util_Div::virtualizeFE();
        }
        /***** BASE INIT *****
         *
         */
        $this->sExtPath = tx_rnbase_util_Extensions::extPath('mkforms');
        $this->sExtRelPath = tx_rnbase_util_Extensions::siteRelPath('mkforms');
        $this->sExtWebPath = Tx_Rnbase_Utility_T3General::getIndpEnv('TYPO3_SITE_URL') . tx_rnbase_util_Extensions::siteRelPath('mkforms');

        // TODO: Der Zugriff auf conf wird durch tx_rnbase_configurations ersetzt
        $this->setConfigurations($configurations, $confid);
        $this->_oParent =& $oParent;
        $this->oParent =& $oParent;

        $this->aTempDebug = array();

        // wird beispielsweise für caching genutzt
        $this->iPageId = $GLOBALS['TSFE']->id;

        /***** XML INIT *****
         *
         */

        if (is_string($mXml)) {
            $this->_xmlPath = $mXml;
        }

        tx_rnbase::load('tx_mkforms_util_Config');
        if ($this->bInitFromTs === false) {

            /**
 * Cyrille Berliat : Patch to handle direct XML arrays when passed to init
*/
            if (is_array($mXml)) {
                // TODO
                $this->_aConf = $mXml;
            } else {
                $this->config = tx_mkforms_util_Config::createInstanceByPath($mXml, $this);
            }
        } else {
            // TODO: Das TS-Array aus dem Plugin setzen. Am besten gleich die Configuration verwenden...
            $this->config = tx_mkforms_util_Config::createInstanceByTS($mXml, $this);
        }

        // usegp gesetzt?
        if ($this->getConfigXML()->get('/meta/form/usegp') !== false) {
            // usegp aktiviert?
            if ($this->getConfigXML()->defaultFalse('/meta/form/usegp')) {
                $this->useGP(
                    $this->getConfigXML()->defaultTrue('/meta/form/usegpwithurldecode')
                );
            }
        } // use gp abhängig von der form method setzen
        elseif ($this->getFormMethod() === tx_mkforms_util_Constants::FORM_METHOD_GET) {
            $this->useGP(
                $this->getConfigXML()->defaultTrue('/meta/form/usegpwithurldecode')
            );
        }

        /***** DEBUG INIT *****
         *
         *    After this point raw xml data is available ( means before precompilation )
         *    So it is now possible to get some basic config from the xml
         *
         */

        // TODO: alle Vorkommen suchen!
        // $this->bDebug -> $this->getConfig()->isDebug()

        $GLOBALS['TYPO3_DB']->store_lastBuiltQuery = true;
        if ($this->getConfig()->isDebug()) {
            $GLOBALS['TYPO3_DB']->debugOutput = true;
        }

        /***** INIT FORM SIGNATURE *****
         *
         */
        $this->formid = $this->getConfig()->get('/meta/form/formid');

        if ($this->getRunnable()->isRunnable($this->formid)) {
            $this->formid = $this->getRunnable()->callRunnable($this->formid);
        }

        // CHECKING FORMID COLLISION IN PAGE
        if (!(!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['context']['forms'])
            || !array_key_exists(
                $this->formid,
                $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['context']['forms']
            )
            || !$this->_defaultFalse('/meta/formwrap'))
        ) {
            $this->mayday(
                'Two (or more) Formidable are using the same formid \'<b>' . $this->formid
                . '</b>\' on this page - cannot continue'
            );
        }
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['context']['forms'][$this->formid] = array();

        $this->initAddPost();

        /***** INIT DEFAULT (TEMPORARY) DATAHANDLER AND RENDERER *****
         *
         *    These two instances are meant to be destroyed later in the init process
         *    Useful for giving access to objects at precompilation time
         *
         */

        $this->oDataHandler =& $this->_makeDefaultDataHandler();
        $this->oRenderer =& $this->_makeDefaultRenderer();

        /***** INIT EDIT MODE ? *****
         *
         */

        if ($iForcedEntryId !== false) {
            // uid "iForcedEntryId" was passed to init() method of formidable

            if (($iCurrentEntryId = $this->oDataHandler->_currentEntryId()) !== false) {
                // there is already an uid asked for edition
                // it has been passed thru POST var myformid[AMEOSFORMIDABLE_ENTRYID]

                if ($iForcedEntryId != $iCurrentEntryId) {
                    // the old edited uid is different of the newly asked one
                    // therefore we'll ask formidable to *force* edition of this iForcedEntryId
                    // meaning that formidable should forget field-values passed by POST
                    // and re-take the record from DB
                    $this->forceEntryId($iForcedEntryId);
                } else {
                    // the old edited uid is the same that the newly asked one
                    // let formidable handle himself the uid passed thru POST var myformid[AMEOSFORMIDABLE_ENTRYID]
                    $iForcedEntryId = false;
                }
            } else {
                $this->forceEntryId($iForcedEntryId);
            }
        } elseif (($mUid = $this->getConfig()->get('/control/datahandler/editentry')) !== false) {
            $mUid = $this->getRunnable()->callRunnable($mUid);
            if (($iCurrentEntryId = $this->getDataHandler()->_currentEntryId()) !== false) {
                if ($mUid != $iCurrentEntryId) {
                    $this->forceEntryId($mUid);
                }
            } else {
                $this->forceEntryId($mUid);
            }
        }

        if ($this->iForcedEntryId === false) {
            if (($iTempUid = $this->editionRequested()) !== false) {
                $this->forceEntryId($iTempUid);
            } else {
                $this->forceEntryId($iForcedEntryId);
            }
        }

        $aRawPost = $this->_getRawPost();
        if (trim($aRawPost['AMEOSFORMIDABLE_SERVEREVENT']) !== '') {
            $aServerEventParams = $this->_getServerEventParams();
            if (array_key_exists('_sys_earlybird', $aServerEventParams)) {
                $aEarlyBird = $aServerEventParams['_sys_earlybird'];
                $aEvent = $this->getConfig()->get($aEarlyBird['xpath'], $this->_aConf);
                $this->getRunnable()->callRunnable($aEvent, $aServerEventParams);
            }
        }

        /***** XML PRECOMPILATION *****
         *
         *    Applying modifiers on the xml structure
         *    Thus producing new parts of xml and deleting some
         *    To get the definitive XML
         *
         */
        // TODO: Hier geht's weiter: das muss in die Config!
        $this->getConfig()->compileConfig($this->aTempDebug);

        /***** GRABBING SERVER EVENTS *****/

        $this->checkPoint(array('start',));

        $this->bReliableXML = true;

        // RELIABLE XML DATA CANNOT BE ACCESSED BEFORE THIS POINT
        // AND THEREFORE NEITHER ALL OBJECTS CONFIGURED BY THIS XML
        // (END OF XML PRE-COMPILATION)

        $this->sDefaultLLLPrefix = $this->getConfig()->get('/meta/defaultlll');

        if ($this->getRunnable()->isRunnable($this->sDefaultLLLPrefix)) {
            $this->sDefaultLLLPrefix = $this->getRunnable()->callRunnable(
                $this->sDefaultLLLPrefix
            );
        }

        if ($this->sDefaultLLLPrefix === false && ($this->oParent instanceof Tx_Rnbase_Frontend_Plugin)) {
            if ($this->oParent->scriptRelPath) {
                $sLLPhp = 'EXT:' . $this->oParent->extKey . '/' . dirname($this->oParent->scriptRelPath) . '/locallang.php';
                $sLLXml = 'EXT:' . $this->oParent->extKey . '/' . dirname($this->oParent->scriptRelPath) . '/locallang.xml';

                if (file_exists(tx_mkforms_util_Div::toServerPath($sLLPhp))) {
                    $this->sDefaultLLLPrefix = $sLLPhp;
                }

                if (file_exists(tx_mkforms_util_Div::toServerPath($sLLXml))) {
                    $this->sDefaultLLLPrefix = $sLLXml;
                }
            }
        }

        $this->sDefaultWrapClass = $this->getConfig()->get('/meta/defaultwrapclass');
        if ($this->sDefaultWrapClass === false) {
            // set classes for all new xml versions to mkforms
            $this->sDefaultWrapClass = 'mkforms-rdrstd';
            // for older forms leave as formidable!
            if (2000000 > tx_rnbase_util_TYPO3::convertVersionNumberToInteger($this->getConfig()->get('/version'))) {
                $this->sDefaultWrapClass = 'formidable-rdrstd';
            }
        }

        $this->makeCallDebug($this->iForcedEntryId);

        $this->checkPoint(array('after-compilation', 'before-init', 'before-init-renderer'));

        $this->sFormAction = false;
        if (($sAction = $this->getConfig()->get('/meta/form/action')) !== false) {
            $sAction = $this->getRunnable()->callRunnable($sAction);
            if ($sAction !== false) {
                $this->sFormAction = trim($sAction);
            }
        }

        $this->analyzeFormAction();

        if (($sSandClass = $this->_includeSandBox()) !== false) {
            $this->_createSandBox($sSandClass);
        }

        if (($aOnInit = $this->getConfig()->get('/meta/oninit')) !== false && $this->getRunnable()->isRunnable($aOnInit)) {
            $this->getRunnable()->callRunnable($aOnInit);
        }

        $this->_initDataSources();
        $this->_initRenderer();

        //wir müssen noch die fe user session id setzen für CSRF schutz
        //und dessen request token
        $this->setSessionId($GLOBALS['TSFE']->fe_user->id);

        $this->checkPoint(array('after-init-renderer', 'before-init-renderlets'));

        $this->_initRenderlets();
        $this->fetchServerEvents();

        $this->checkPoint(array('after-init-renderlets', 'before-init-datahandler'));
        $this->_initDataHandler($this->iForcedEntryId);
        $this->checkPoint(array('after-init-datahandler', 'after-init',));
        $this->bInited = true;
    }

    /**
     * Liefert den JS-Loader
     *
     * @return tx_mkforms_js_Loader
     */
    public function getJSLoader()
    {
        if (!is_object($this->oJs)) {
            $this->getObjectLoader()->load('tx_mkforms_js_Loader');
            $this->oJs = tx_mkforms_js_Loader::createInstance($this);
        }

        return $this->oJs;
    }

    public function initAPI(&$oParent)
    {
        $this->_oParent =& $oParent;
        $this->formid = Tx_Rnbase_Utility_T3General::shortMD5(rand());
    }

    /**
     * Liefert eine Instanz von tx_mkforms_util_Runnable. Es wird systemweit nur eine einzige Instanz
     * verwendet.
     *
     * Im Runnable werden auch die CodeBehinds initialisiert. Wenn der erste Zugriff auf
     * ein Runnable aber erfolgt, bevor das Formular seine formid gelesen hat, dann werden die CodeBehinds
     * ohne gültige formid initialisiert. Darum wird solange ein neues Runnable instanziiert, bis eine formid
     * vorhanden ist. (Es ist aber nur eine weitere Instanz.)
     *
     * @return tx_mkforms_util_Runnable
     */
    public function getRunnable()
    {
        if (!$this->runnable) {
            if (!$this->getFormId()) {
                return tx_mkforms_util_Runnable::createInstance($this->getConfigXML(), $this);
            }
            $this->runnable = tx_mkforms_util_Runnable::createInstance($this->getConfigXML(), $this);
        }

        return $this->runnable;
    }

    /**
     * Makes a persistent instance of an HTML parser
     * Mainly used for template processing
     *
     * @return    void
     */
    public function makeHtmlParser()
    {
        if ($this->oHtml === false) {
            $this->oHtml = tx_rnbase::makeInstance(tx_rnbase_util_Typo3Classes::getHtmlParserClass());
        }
    }

    public function useNewDataStructure()
    {
        return $this->_defaultFalse('/meta/usenewdatastructure');
    }

    public function isFormActionTransparent()
    {
        return $this->sFormAction === false;
    }

    public function isFormActionCurrent()
    {
        return $this->sFormAction == 'current';
    }

    public function analyzeFormAction()
    {
        if ($this->isFormActionTransparent()) {
            $aGet = Tx_Rnbase_Utility_T3General::_GET();

            // diese Parameter werden von TYPO3 verwaltet und dürfen nie übernommen
            // werden
            $keysToIgnore = array('L', 'cHash', 'id');
            foreach ($keysToIgnore as $keyToIgnore) {
                if (array_key_exists($keyToIgnore, $aGet)) {
                    unset($aGet[$keyToIgnore]);
                }
            }
            // remove NoKeepVars
            foreach ($aGet as $qualifier => $dataArr) {
                if (!is_array($dataArr)) {
                    continue;
                }
                foreach ($dataArr as $key => $value) {
                    if (strpos($key, 'NK_') === 0) {
                        unset($aGet[$qualifier][$key]);
                    }
                }
            }

            $this->aFormAction = $aGet;
        } else {
            $this->aFormAction = array();
        }
    }

    public function formActionAdd($aParams)
    {
        if ($this->isFormActionTransparent()) {
            $this->aFormAction = tx_rnbase_util_Arrays::mergeRecursiveWithOverrule($this->aFormAction, $aParams);
        }
    }

    public function formActionRemove($aParams)
    {
        if ($this->isFormActionTransparent()) {
            $this->aFormAction = $this->array_diff_key_recursive($this->aFormAction, $aParams);
        }
    }

    public function array_diff_key()
    {
        $arrs = func_get_args();
        $result = array_shift($arrs);
        foreach ($arrs as $array) {
            foreach ($result as $key => $v) {
                if (is_array($array) && array_key_exists($key, $array)) {
                    unset($result[$key]);
                }
            }
        }

        return $result;
    }

    public function array_diff_key_recursive($a1, $a2)
    {
        $r = array();
        reset($a1);
        while (list($k, $v) = each($a1)) {
            if (is_array($v)) {
                $r[$k] = $this->array_diff_key_recursive($a1[$k], $a2[$k]);
            } else {
                $r = $this->array_diff_key($a1, $a2);
            }

            if (is_array($r[$k]) && count($r[$k]) == 0) {
                unset($r[$k]);
            }
        }
        reset($r);

        return $r;
    }

    public function array_diff_recursive($aArray1, $aArray2, $bStrict = false)
    {
        $aReturn = array();

        foreach ($aArray1 as $mKey => $mValue) {
            if (is_array($aArray2) && array_key_exists($mKey, $aArray2)) {
                if (is_array($mValue)) {
                    $aRecursiveDiff = $this->array_diff_recursive($mValue, $aArray2[$mKey], $bStrict);
                    if (count($aRecursiveDiff)) {
                        $aReturn[$mKey] = $aRecursiveDiff;
                    }
                } else {
                    if ($bStrict === false) {
                        if ($mValue != $aArray2[$mKey]) {
                            $aReturn[$mKey] = $mValue;
                        }
                    } else {
                        if ($mValue !== $aArray2[$mKey]) {
                            $aReturn[$mKey] = $mValue;
                        }
                    }
                }
            } else {
                $aReturn[$mKey] = $mValue;
            }
        }

        return $aReturn;
    }

    private function makeCallDebug($iForcedEntryId)
    {
        if ($this->getConfig()->isDebug()) {
            $aTrace = debug_backtrace();
            $aLocation = array_shift($aTrace);

            $this->_debug(
                'User called FORMidable<br>' . '<br>&#149; In :<br>' . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $aLocation['file'] . ':'
                . $aLocation['line'] . '<br>&#149; At :<br>' . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $aLocation['class']
                . $aLocation['type'] . $aLocation['function'] . '<br>&#149; With args: <br>' . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
                . tx_mkforms_util_Div::viewMixed($aLocation['args']) . (($iForcedEntryId !== false) ?
                    '<br>&#149; Edition of entry ' . $iForcedEntryId . ' requested' : ''),
                'INITIALIZATION OF FORMIDABLE'
            );

            if (!empty($this->aTempDebug['aIncHierarchy'])) {
                $this->_debug(
                    $this->aTempDebug['aIncHierarchy'],
                    'XML INCLUSION HIERARCHY',
                    false
                );
            } else {
                $this->_debug(
                    null,
                    'NO XML INCLUSION',
                    false
                );
            }
        }
    }

    public function getFormAction()
    {
        if ($this->isFormActionTransparent()) {
            // we use the current URL including the host. We do this in case
            // we are on the root page. in this case we would have an empty
            // action attribute if we only used Tx_Rnbase_Utility_T3General::getIndpEnv('REQUEST_URI')
            // Caution! You need to make sure to escape the URL properly
            // when using it somewhere as we pass through the current URL
            // which can be manipulated by users.
            $sRes = Tx_Rnbase_Utility_T3General::getIndpEnv('REQUEST_URI');
        } // Link zur aktuellen Seite.
        // Z.b bei Formularen mit Pagebrowsern interessant
        elseif ($this->isFormActionCurrent()) {
            $sRes = tx_mkforms_util_Div::toWebPath(
                $this->getCObj()->typoLink_URL(
                    array('parameter' => $GLOBALS['TSFE']->id)
                )
            );
        } else {
            $sRes = $this->sFormAction;
        }

        if (($sAnchor = $this->getConfig()->get('/meta/form/actionanchor')) !== false) {
            if ($this->getRunnable()->isRunnable($sAnchor)) {
                $sAnchor = $this->getRunnable()->callRunnable($sAnchor);
            }

            if ($sAnchor !== false && is_string($sAnchor)) {
                $sAnchor = trim(str_replace('#', '', $sAnchor));
                if ($sAnchor !== '') {
                    $sRes .= '#' . $sAnchor;
                }
            }
        }

        return $sRes;
    }

    public function setParamsToRemove($aParams)
    {
        $this->aParamsToRemove = tx_rnbase_util_Arrays::mergeRecursiveWithOverrule(
            $this->aParamsToRemove,
            $aParams
        );

        $this->formActionRemove($aParams);
    }

    public function initAddPost()
    {
        $this->aAddPostVars = $this->getAddPostVars();
    }

    public function getAddPostVars($sFormId = false)
    {
        if ($sFormId === false) {
            $sFormId = $this->formid;
        }

        $aRawPost = Tx_Rnbase_Utility_T3General::_POST();
        $aRawPost = is_array($aRawPost[$sFormId]) ? $aRawPost[$sFormId] : array();

        if (array_key_exists('AMEOSFORMIDABLE_ADDPOSTVARS', $aRawPost) && trim($aRawPost['AMEOSFORMIDABLE_ADDPOSTVARS']) !== '') {
            if (!is_array(
                ($aAddPostVars = tx_mkforms_util_Json::getInstance()->decode($aRawPost['AMEOSFORMIDABLE_ADDPOSTVARS']))
            )
            ) {
                $aAddPostVars = false;
            }
        } else {
            $aAddPostVars = false;
        }

        return $aAddPostVars;
    }

    public function editionRequested()
    {
        if ($this->aAddPostVars !== false) {
            reset($this->aAddPostVars);
            while (list($sKey, ) = each($this->aAddPostVars)) {
                if (array_key_exists('action', $this->aAddPostVars[$sKey])
                    && $this->aAddPostVars[$sKey]['action'] === 'requestEdition'
                ) {
                    $sOurSafeLock = $this->_getSafeLock(
                        'requestEdition' . ':' . $this->aAddPostVars[$sKey]['params']['tablename'] . ':'
                        . $this->aAddPostVars[$sKey]['params']['recorduid']
                    );
                    $sTheirSafeLock = $this->aAddPostVars[$sKey]['params']['hash'];

                    if ($sOurSafeLock === $sTheirSafeLock) {
                        return $this->aAddPostVars[$sKey]['params']['recorduid'];
                    }
                }
            }
        }

        return false;
    }

    /**
     * Dispatch calls to checkpoints defined in the whole code of formidable
     * Similar to hooks
     *
     * @param    array $aPoints : names of the checkpoints to consider
     * @param    array $options
     *
     * @return    void
     */
    public function checkPoint($aPoints, array &$options = array())
    {
        $this->_processServerEvents($aPoints, $options);
        $this->_processRdtCheckPoints($aPoints, $options);
        $this->_processMetaCheckPoints($aPoints, $options);
    }

    /**
     * Handles checkpoint-calls on renderlets
     *
     * @param    array $aPoints : names of the checkpoints to consider
     * @param    array $options
     *
     * @return    void
     */
    private function _processRdtCheckPoints(&$aPoints, array &$options = array())
    {
        if (count($this->aORenderlets) > 0) {
            $aKeys = array_keys($this->aORenderlets);
            while (list(, $sKey) = each($aKeys)) {
                $this->aORenderlets[$sKey]->checkPoint($aPoints, $options);
            }
        }
    }

    /**
     *
     * @param    array $aPoints : names of the checkpoints to consider
     * @param    array $options
     *
     * @return    void
     */
    private function _processMetaCheckPoints(&$aPoints, array $options = array())
    {
        $aMeta = $this->getConfig()->get('/meta');

        if (!is_array($aMeta)) {
            $aMeta[0] = $aMeta;
        }
        $aKeys = array_keys($aMeta);
        reset($aKeys);
        while (list(, $sKey) = each($aKeys)) {
            if ($sKey{0} == 'o' && $sKey{1} == 'n' && (substr($sKey, 0, 12) === 'oncheckpoint')) {
                $sWhen = $this->getConfig()->get('/meta/' . $sKey . '/when');
                if (in_array($sWhen, $aPoints)) {
                    if ($this->getRunnable()->isRunnable($aMeta[$sKey])) {
                        $this->getRunnable()->callRunnable($aMeta[$sKey]);
                    }
                }
            }
        }
    }

    /**
     * Includes the sandbox in php context
     *
     * @return    mixed        FALSE or name of the sandbox class
     */
    public function _includeSandBox()
    {
        $aBox = $this->getConfigXML('/control/sandbox');

        $sExtends = ($aBox !== false && is_array($aBox) && array_key_exists('extends', $aBox)) ? (string)$aBox['extends'] : 'EXT:mkforms/res/shared/php/class.defaultsandbox.php::formidable_defaultsandbox';

        $aExtends = explode('::', $sExtends);
        if (sizeof($aExtends) == 2) {
            $sFile = Tx_Rnbase_Utility_T3General::getFileAbsFileName($aExtends[0]);
            $sClass = $aExtends[1];

            if (file_exists($sFile) && is_readable($sFile)) {
                ob_start();
                require_once($sFile);
                ob_end_clean(
                );        // output buffering for easing use of php class files that execute something outside the class definition ( like BE module's index.php !!)
            } else {
                $this->mayday('<b>The declared php-FILE for sandbox ("' . $sFile . '") doesn\'t exists</b>');
            }
        } else {
            // trying to auto-determine class-name

            $sFile = Tx_Rnbase_Utility_T3General::getFileAbsFileName($aExtends[0]);
            if (file_exists($sFile) && is_readable($sFile)) {
                $aClassesBefore = get_declared_classes();

                ob_start();
                require_once($sFile);
                ob_end_clean(
                );        // output buffering for easing use of php class files that execute something outside the class definition ( like BE module's index.php !!)
                $aClassesAfter = get_declared_classes();

                $aNewClasses = array_diff($aClassesAfter, $aClassesBefore);

                if (count($aNewClasses) !== 1) {
                    $this->mayday(
                        "<b>Cannot automatically determine the classname to use for the sandbox in '" . $sFile
                        . "'</b><br />Please add '::myClassName' after the file-path in the sandbox declaration"
                    );
                } else {
                    $sClass = array_shift($aNewClasses);
                }
            } else {
                $this->mayday("<b>The declared php-FILE for sandbox ('" . $sFile . "') doesn't exists</b>");
            }
        }

        if (!class_exists($sClass)) {
            $this->mayday('<b>The declared php-CLASS for sandbox (\'' . $sClass . '\') doesn\'t exists</b>');

            return false;
        }

        if (!$this->isUserObj($aBox)) {
            return $sClass;
        }

        if (($sPhp = $this->getConfigXML('/userobj/php', $aBox)) !== false) {
            $sClassName = 'formidablesandbox_' . md5($sPhp);    // these 2 lines
            if (!class_exists($sClassName)) {                    // allows same sandbox twice or more on the same page

                $sSandClass
                    = <<<SANDBOXCLASS

    class {$sClassName} extends {$sClass} {

        var \$oForm = null;

        {$sPhp}
    }

SANDBOXCLASS;

                $this->__sEvalTemp = array('code' => $sSandClass, 'xml' => $aBox);
                set_error_handler(array(&$this, '__catchEvalException'));
                eval($sSandClass);
                unset($this->__sEvalTemp);
                restore_error_handler();
            }

            return $sClassName;
        }

        return false;
    }

    /**
     * Builds a persistent instance of the sandbox
     *
     * @param    string $sClassName : Name of the sandbox class, as returned by _includeSandBox()
     *
     * @return    void
     */
    public function _createSandBox($sClassName)
    {
        $this->oSandBox = new $sClassName();
        $this->oSandBox->oForm =& $this;
        if (method_exists($this->oSandBox, 'init')) {
            $this->oSandBox->init($this);    // changed: avoid call-time pass-by-reference
        }
    }

    public function initSteps(&$oParent, $aSteps)
    {
        $this->aSteps = $aSteps;

        $aExtract = $this->__extractStep();

        $iStep = $this->_getStep();
        $aCurStep = $this->aSteps[$iStep];

        $sPath = $aCurStep['path'];

        if ($aExtract === false || ($iEntryId = $aExtract['AMEOSFORMIDABLE_STEP_UID']) === false) {
            $iEntryId = (array_key_exists('uid', $aCurStep) ? $aCurStep['uid'] : false);
        }

        $this->init(
            $oParent,    // changed: avoid call-time pass-by-reference
            $sPath,
            $iEntryId
        );
    }

    public function _getStepperId()
    {
        if ($this->aSteps !== false) {
            return md5(serialize($this->aSteps));
        }

        return false;
    }

    public function _getStep()
    {
        $aStep = $this->__extractStep();
        if ($aStep === false) {
            return 0;
        }

        return $aStep['AMEOSFORMIDABLE_STEP'];
    }

    public function __extractStep()
    {
        $sStepperId = $this->_getStepperId();

        if ($this->_aStep === false) {
            tx_mkforms_session_Factory::getSessionManager()->initialize();
            if (array_key_exists('ameos_formidable', $GLOBALS['_SESSION'])
                && array_key_exists('stepper', $GLOBALS['_SESSION']['ameos_formidable'])
                && array_key_exists($sStepperId, $GLOBALS['_SESSION']['ameos_formidable']['stepper'])
            ) {
                $this->_aStep = $GLOBALS['_SESSION']['ameos_formidable']['stepper'][$sStepperId];
                unset($GLOBALS['_SESSION']['ameos_formidable']['stepper'][$sStepperId]);
            } else {
                $aP = Tx_Rnbase_Utility_T3General::_POST();

                if (array_key_exists('AMEOSFORMIDABLE_STEP', $aP) && array_key_exists('AMEOSFORMIDABLE_STEP_HASH', $aP)) {
                    if ($this->_getSafeLock($aP['AMEOSFORMIDABLE_STEP']) === $aP['AMEOSFORMIDABLE_STEP_HASH']) {
                        $this->_aStep = array(
                            'AMEOSFORMIDABLE_STEP' => $aP['AMEOSFORMIDABLE_STEP'],
                            'AMEOSFORMIDABLE_STEP_UID' => false,
                        );
                    }
                }
            }
        }

        return $this->_aStep;    // FALSE if none set
    }

    /**
     * Util-method for use in __getEventsInConf
     *
     * @param    string $sName : name of the current conf-key
     *
     * @return    bool        TRUE if event (onXYZ), FALSE if not
     */
    public function __cbkFilterEvents($sName)
    {
        return $sName{0} === 'o' && $sName{1} === 'n';    // should start with 'on', but speed check
    }

    /**
     * Extracts all events defined in a conf array
     *
     * @param    array $aConf : conf containing events to detect
     *
     * @return    array        events extracted
     */
    public function __getEventsInConf($aConf)
    {
        return array_merge(// array_merge reindexes array
            array_filter(
                array_keys($aConf),
                array($this, '__cbkFilterEvents')
            )
        );
    }

    /**
     * Creates unique ID for a given server event
     *
     * @param    string $sRdtName : name of the renderlet defining the event
     * @param    array  $aEvent   : conf array of the event
     *
     * @return    string        Server Event ID
     */
    public function _getServerEventId($sRdtName, $aEvent)
    {
        return $this->_getSafeLock(
            $sRdtName . serialize($aEvent)
        );
    }

    /**
     * Creates unique ID for a given ajax event
     *
     * @param    string $sRdtName : name of the renderlet defining the event
     * @param    array  $aEvent   : conf array of the event
     *
     * @return    string        Ajax Event ID
     */
    public function _getAjaxEventId($sRdtName, $aEvent)
    {
        return $this->_getServerEventId($sRdtName, $aEvent);    // same HashKey algorithm
    }

    /**
     * Creates unique ID for a given client event
     *
     * @param    string $sRdtName : name of the renderlet defining the event
     * @param    array  $aEvent   : conf array of the event
     *
     * @return    string        Client Event ID
     */
    public function _getClientEventId($sRdtName, $aEvent)
    {
        return $this->_getServerEventId($sRdtName, $aEvent);    // same HashKey algorithm
    }

    /**
     * Executes triggered server events
     * Called by checkPoint()
     *
     * @param    array $aTriggers : array of checkpoints names to consider
     * @param    array $options
     *
     * @return    void
     */
    private function _processServerEvents(&$aTriggers, array &$options = array())
    {
        $aP = $this->_getRawPost();
        if (array_key_exists('AMEOSFORMIDABLE_SERVEREVENT', $aP) && (trim($aP['AMEOSFORMIDABLE_SERVEREVENT']) !== '')) {
            if (array_key_exists($aP['AMEOSFORMIDABLE_SERVEREVENT'], $this->aServerEvents)) {
                $aEvent = $this->aServerEvents[$aP['AMEOSFORMIDABLE_SERVEREVENT']];
                if (in_array($aEvent['when'], $aTriggers)) {
                    if ($this->getRunnable()->isRunnable($aEvent['event'])) {
                        if (array_key_exists($aEvent['name'], $this->aORenderlets)) {
                            $this->aORenderlets[$aEvent['name']]->callRunneable(
                                $aEvent['event'],
                                $this->_getServerEventParams()
                            );
                        } else {
                            // should never be the case
                            $this->getRunnable()->callRunnable(
                                $aEvent['event'],
                                $this->_getServerEventParams()
                            );
                        }
                    }
                }
            }
        } else {
            // handling unobtrusive server events
            // triggered when onclick runart='server' is defined on a SUBMIT renderlet

            reset($this->aServerEvents);
            while (list($sKey, ) = each($this->aServerEvents)) {
                $sAbsName = $this->aServerEvents[$sKey]['name'];
                $sAbsPath = str_replace('.', '/', $sAbsName);

                if (($aRes = $this->navDeepData($sAbsPath, $aP)) !== false) {
                    if (array_key_exists($sAbsName, $this->aORenderlets)
                        && $this->aORenderlets[$sAbsName]->aObjectType['TYPE'] === 'SUBMIT'
                    ) {
                        $aEvent = $this->aServerEvents[$sKey];
                        if (in_array($aEvent['when'], $aTriggers)) {
                            $this->getRunnable()->callRunnable(
                                $aEvent['event'],
                                array()
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Get params for the triggered server event
     *
     * @return    array        Params
     */
    public function _getServerEventParams()
    {
        $aPost = $this->_getRawPost();

        if (array_key_exists('AMEOSFORMIDABLE_SERVEREVENT_PARAMS', $aPost)
            && array_key_exists(
                'AMEOSFORMIDABLE_SERVEREVENT_HASH',
                $aPost
            )
            && $this->_getSafeLock($aPost['AMEOSFORMIDABLE_SERVEREVENT_PARAMS']) == $aPost['AMEOSFORMIDABLE_SERVEREVENT_HASH']
        ) {
            return unserialize(base64_decode($aPost['AMEOSFORMIDABLE_SERVEREVENT_PARAMS']));
        } else {
            return array();
        }
    }

    /**
     * Setzt einen Submitmode, der AMEOSFORMIDABLE_SUBMITTED überschreibt.
     *
     * @param string $sValue
     */
    public function setSubmitter($sSubmitter, $sValue = false)
    {
        switch ($sValue) {
            case 'full': {
                $sValue = AMEOSFORMIDABLE_EVENT_SUBMIT_FULL;
                break;
            }
            case 'refresh': {
                $sValue = AMEOSFORMIDABLE_EVENT_SUBMIT_REFRESH;
                break;
            }
            case 'draft': {
                $sValue = AMEOSFORMIDABLE_EVENT_SUBMIT_DRAFT;
                break;
            }
            case 'test': {
                $sValue = AMEOSFORMIDABLE_EVENT_SUBMIT_TEST;
                break;
            }
            case 'clear': {
                $sValue = AMEOSFORMIDABLE_EVENT_SUBMIT_CLEAR;
                break;
            }
            case 'search': {
                $sValue = AMEOSFORMIDABLE_EVENT_SUBMIT_SEARCH;
                break;
            }
        }

        $this->submittedValue = $sValue;
        $this->submitter = $sSubmitter;
    }

    public function getSubmitter($sFormId = false)
    {
        if ($this->submitter) {
            return $this->submitter;
        }

        $aP = $this->_getRawPost($sFormId);

        if (array_key_exists('AMEOSFORMIDABLE_SUBMITTER', $aP) && (trim($aP['AMEOSFORMIDABLE_SUBMITTER']) !== '')) {
            $sSubmitter = $aP['AMEOSFORMIDABLE_SUBMITTER'];

            return $sSubmitter;
        }

        return false;
    }

    /**
     * Determines if the FORM is submitted
     * using the AMEOSFORMIDABLE_SUBMITTED constant for naming the POSTED variable
     *
     * @param    string $sFormId : optional; if none given, current formid is used
     *
     * @return    bool
     */
    public function getSubmittedValue($sFormId = false)
    {
        if ($this->submittedValue) {
            return $this->submittedValue;
        }

        $aP = $this->_getRawPost($sFormId);

        if (array_key_exists('AMEOSFORMIDABLE_SUBMITTED', $aP) && (trim($aP['AMEOSFORMIDABLE_SUBMITTED']) !== '')) {
            return trim($aP['AMEOSFORMIDABLE_SUBMITTED']);
        }

        return false;
    }

    /**
     * Returns RAW POST+FILES data
     *
     * @param    string $sFormId : optional; if none given, current formid is used
     *
     * @return    array        POST+FILES data
     */
    public function _getRawPost($sFormId = false, $bCache = true)
    {
        if ($sFormId === false) {
            $sFormId = $this->formid;
        }

        if (!array_key_exists((string)$sFormId, $this->aRawPost) || ($bCache === false)) {
            $aPost = Tx_Rnbase_Utility_T3General::_POST();
            if ($this->_useGP) {
                $aGet = Tx_Rnbase_Utility_T3General::_GET();
                //wurden die Daten Urlencodiert?
                if ($this->_useGPWithUrlDecode) {
                    tx_mkforms_util_Div::urlDecodeRecursive($aGet);
                }
                $aPost = tx_rnbase_util_Arrays::mergeRecursiveWithOverrule(
                    $aGet,
                    is_array($aPost) ? $aPost : array()
                );
            }

            $aPost = is_array($aPost[$sFormId]) ? $aPost[$sFormId] : array();
            $aFiles = $this->_getRawFile();

            $aAddParams = array();

            if ($sFormId === false) {
                $aAddPostVars = $this->aAddPostVars;
            } else {
                $aAddPostVars = $this->getAddPostVars($sFormId);
            }

            if ($aAddPostVars !== false) {
                reset($aAddPostVars);
                while (list($sKey, ) = each($aAddPostVars)) {
                    if (array_key_exists('action', $aAddPostVars[$sKey]) && $aAddPostVars[$sKey]['action'] === 'formData') {
                        reset($aAddPostVars[$sKey]['params']);
                        while (list($sParam, $sValue) = each($aAddPostVars[$sKey]['params'])) {
                            $aAddParams = tx_rnbase_util_Arrays::mergeRecursiveWithOverrule(
                                $aAddParams,
                                Tx_Rnbase_Utility_T3General::explodeUrl2Array(
                                    $sParam . '=' . $sValue,
                                    true    // multidim ?
                                )
                            );
                        }
                    }
                }
            }

            $aRes = tx_rnbase_util_Arrays::mergeRecursiveWithOverrule($aPost, $aFiles);
            $aRes = tx_rnbase_util_Arrays::mergeRecursiveWithOverrule($aRes, $aAddParams);
            reset($aRes);

            if ($bCache === false) {
                return $aRes;
            }

            $this->aRawPost[$sFormId] = $aRes;
        }

        return $this->aRawPost[$sFormId];
    }

    public function getRawPost($sFormId = false)
    {
        // alias for _getRawPost()
        return $this->_getRawPost($sFormId);
    }

    public function _getRawGet($sFormId = false)
    {
        if ($sFormId === false) {
            $sFormId = $this->formid;
        }

        if (!array_key_exists((string)$sFormId, $this->aRawGet)) {
            $aGet = Tx_Rnbase_Utility_T3General::_GET($sFormId);
            //wurden die Daten Urlencodiert?
            if ($this->_useGPWithUrlDecode) {
                tx_mkforms_util_Div::urlDecodeRecursive($aGet);
            }
            $this->aRawGet[$sFormId] = is_array($aGet) ? $aGet : array();
        }

        reset($this->aRawGet[$sFormId]);

        return $this->aRawGet[$sFormId];
    }

    public function getRawGet($sFormId = false)
    {
        // alias for _getRawGet()
        return $this->_getRawGet($sFormId);
    }

    /**
     * Mit $forced werden die Daten nicht gecached. Das wird bei Ajax-Uploads benötigt.
     *
     * @param string  $sFormId
     * @param bool $forced
     *
     * @return array
     */
    public function _getRawFile($sFormId = false, $forced = false)
    {
        if ($sFormId === false) {
            $sFormId = $this->getFormId();
        }

        if ($forced || !array_key_exists((string)$sFormId, $this->aRawFile)) {
            $aTemp = is_array($GLOBALS['_FILES'][$sFormId]) ? $GLOBALS['_FILES'][$sFormId] : array();
            $aF = array();

            if (!empty($aTemp)) {
                $aTemp = array($sFormId => $aTemp);
                reset($aTemp);

                foreach ($aTemp as $var => $info) {
                    foreach (array_keys($info) as $attr) {
                        $this->groupFileInfoByVariable($aF, $info[$attr], $attr);
                    }
                }
            }
            if ($forced) {
                return $aF;
            }
            $this->aRawFile[$sFormId] = $aF;
        }

        reset($this->aRawFile[$sFormId]);

        return $this->aRawFile[$sFormId];
    }

    public function getRawFile($sFormId = false, $forced = false)
    {
        // alias for _getRawGet()
        return $this->_getRawFile($sFormId, $forced);
    }

    public function groupFileInfoByVariable(&$top, $info, $attr)
    {
        if (is_array($info)) {
            foreach ($info as $var => $val) {
                if (is_array($val)) {
                    $this->groupFileInfoByVariable($top[$var], $val, $attr);
                } else {
                    $top[$var][$attr] = $val;
                }
            }
        } else {
            $top[$attr] = $info;
        }

        return true;
    }

    /**
     * Unsets renderlets having /process = FALSE
     *
     * @return    void
     */
    public function _filterUnProcessed()
    {
        $aRdts = array_keys($this->aORenderlets);

        reset($aRdts);
        while (list(, $sName) = each($aRdts)) {
            if (array_key_exists($sName, $this->aORenderlets) && !$this->aORenderlets[$sName]->hasParent()) {
                $this->aORenderlets[$sName]->filterUnprocessed();
            }
        }
    }

    /**
     * Initialize formidable with typoscript
     *
     * @param object $oParent ref to parent object (usually plugin)
     * @param array $aConf typoscript array
     * @param int|bool $iForcedEntryId UID to edit (if any)
     * @param tx_rnbase_configurations|bool $configurations
     * @param string $confid
     *
     * @return void
     */
    public function initFromTs($oParent, array $aConf, $iForcedEntryId = false, $configurations = false, $confid = '')
    {
        $this->bInitFromTs = true;
        $this->init($oParent, $aConf, $iForcedEntryId, $configurations, $confid);
    }

    public function absolutizeXPath($sRelXPath, $sBaseAbsPath)
    {
        $aCurPath = explode('/', substr($sBaseAbsPath, 1));
        $aRelPath = explode('/', $sRelXPath);

        reset($aRelPath);
        while (list(, $sSegment) = each($aRelPath)) {
            if ($sSegment == '..') {
                array_pop($aCurPath);
            } else {
                $aCurPath[] = $sSegment;
            }
        }

        return '/' . implode('/', $aCurPath);
    }

    /**
     * Obsolete method
     *
     * @param    array $aXml     : ...
     * @param    array $aDynaXml : ...
     *
     * @return    array        ...
     */
    public function _substituteDynaXml($aXml, $aDynaXml)
    {
        $sXml = Tx_Rnbase_Utility_T3General::array2xml($aXml);

        reset($aDynaXml);
        while (list(, $aDynaSubst) = each($aDynaXml)) {
            $sXml = str_replace('[' . $aDynaSubst['name'] . ']', $aDynaSubst['value'], $sXml);
        }

        return Tx_Rnbase_Utility_T3General::xml2array($sXml);
    }

    /**
     * Forces datahandler to edit the given uid
     *
     * @param    int $iForcedEntryId : uid to edit
     *
     * @return    void
     */
    public function _forceEntryId($iForcedEntryId = false)
    {
        $this->iForcedEntryId = $iForcedEntryId;
    }

    public function forceEntryId($iForcedEntryId = false)
    {
        return $this->_forceEntryId($iForcedEntryId);
    }

    /**
     * @return tx_mkforms_action_FormBase or the Parent extension using mkForms
     */
    public function getParent()
    {
        return $this->oParent;
    }

    public function relativizeName($sOurs, $sTheirs)
    {
        if (Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sOurs, $sTheirs)) {
            return substr($sOurs, strlen($sTheirs) + 1);
        }

        return $sOurs;
    }

    /**
     * Initializes the declared datahandler
     *
     * @param    int $iForcedEntryId : optional; uid to edit, if any
     *
     * @return    void
     */
    public function _initDataHandler($iForcedEntryId = false)
    {
        if (($aConfDataHandler = $this->getConfig()->get('/control/datahandler/')) !== false) {
            $this->oDataHandler =& $this->_makeDataHandler($aConfDataHandler);
        } else {
            $this->oDataHandler =& $this->_makeDefaultDataHandler();
        }

        if ($iForcedEntryId === false) {
            $entryId = $this->oDataHandler->_currentEntryId();
            $forcedId = false;
        } else {
            $entryId = $iForcedEntryId;
            $forcedId = true;
        }

        if ($entryId !== false) {
            $this->oDataHandler->entryId = $entryId;
            $this->oDataHandler->forcedId = $forcedId;
        }

        $this->getDataHandler()->_initCols();

        $this->getDataHandler()->refreshAllData();

        $this->_debug($this->oDataHandler->__aStoredData, 'oDataHandler->__aStoredData initialized with these values');
        $this->_debug($this->oDataHandler->__aFormData, 'oDataHandler->__aFormData initialized with these values');
        $this->_debug($this->oDataHandler->__aFormDataManaged, 'oDataHandler->__aFormDataManaged initialized with these values');
    }

    public function _makeDefaultDataHandler()
    {
        return $this->_makeDataHandler(
            array(
                'type' => 'STANDARD'
            )
        );
    }

    public function _makeDefaultRenderer()
    {
        return $this->_makeRenderer(
            array(
                'type' => 'STANDARD'
            )
        );
    }

    /**
     * Initializes the declared renderer
     *
     * @return    void
     */
    public function _initRenderer()
    {
        if (($aConfRenderer = $this->getConfig()->get('/control/renderer/')) !== false) {
            $this->oRenderer =& $this->_makeRenderer($aConfRenderer);
        } else {
            $this->_makeDefaultRenderer();
        }
    }

    /**
     * Initializes the declared datasources
     *
     * @return    void
     */
    public function _initDataSources()
    {
        $this->_makeDataSources(
            $this->getConfig()->get('/control/datasources/'),
            '/control/datasources/'
        );

        $this->_makeDataSources(
            $this->getConfig()->get('/control/'),
            '/control/'
        );
    }

    /**
     * Initializes the declared datasources
     *
     * @param    array  $aConf  : conf as given in /control/datasources/*
     * @param    string $sXPath : xpath from where the given conf comes
     *
     * @return    void        ...
     */
    public function _makeDataSources($aConf, $sXPath)
    {
        if (is_array($aConf)) {
            reset($aConf);
            while (list($sElementName, ) = each($aConf)) {
                if ($sElementName{0} === 'd' && Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sElementName, 'datasource')
                    && !Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sElementName, 'datasources')
                ) {
                    $aElement =& $aConf[$sElementName];

                    $this->aODataSources[trim($aElement['name'])] = $this->_makeDataSource(
                        $aElement,
                        $sXPath . $sElementName
                    );
                }
            }
        }
    }

    /**
     * Initializes the declared Renderlets
     *
     * @return    void
     */
    public function _initRenderlets()
    {
        $this->_makeRenderlets(
            $this->getConfig()->get('/elements/'),
            '/elements/',
            $bChilds = false,
            $this    // not used, but required as passing params by ref is not possible with default param value
        );

        $aRdts = array_keys($this->aORenderlets);
        reset($aRdts);
        while (list(, $sAbsName) = each($aRdts)) {
            $this->getWidget($sAbsName)->initDependancies();
        }
    }

    /**
     * Initializes the declared Renderlets
     *
     * @param    array   $aConf      : array of conf; usually /renderlets/*
     * @param    string  $sXPath     : xpath from where the given conf comes
     * @param    bool $bChilds    : TRUE if initializing childs, FALSE if not
     * @param    bool $bOverWrite : if FALSE, two renderlets declared with the same name will trigger a mayday
     *
     * @return    array        array of references to built renderlets
     */
    public function _makeRenderlets($aConf, $sXPath, $bChilds, &$oChildParent, $bOverWrite = false)
    {
        $aRdtRefs = array();

        if (is_array($aConf)) {
            // ermöglicht das dynamische anlegen von feldern.
            $aConf = $this->getRunnable()->callRunnable($aConf);

            reset($aConf);
            while (list($sElementName, ) = each($aConf)) {
                if ($sElementName{0} === 'r' && Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sElementName, 'renderlet')) {
                    if (array_key_exists('name', $aConf[$sElementName]) && (trim($aConf[$sElementName]['name']) != '')) {
                        $sName = trim($aConf[$sElementName]['name']);
                        $bAnonymous = false;
                    } else {
                        $sName = $this->_getAnonymousName($aConf[$sElementName]);
                        $aConf[$sElementName]['name'] = $sName;
                        $bAnonymous = true;
                    }

                    if (!$bAnonymous && !$bOverWrite && array_key_exists($sName, $this->aORenderlets)) {
                        $this->mayday(
                            "Two (or more) renderlets are using the same name '<b>" . $sName . "</b>' on this form ('<b>"
                            . $this->formid . "</b>') - cannot continue<br /><h2>Inclusions:</h2>"
                            . tx_mkforms_util_Div::viewMixed($this->aTempDebug['aIncHierarchy'])
                        );
                    }

                    $oRdt =& $this->_makeRenderlet(
                        $aConf[$sElementName],
                        $sXPath . $sElementName . '/',
                        $bChilds,
                        $oChildParent,
                        $bAnonymous,
                        $sNamePrefix
                    );

                    $sAbsName = $oRdt->getAbsName();
                    $sName = $oRdt->getName();

                    $this->aORenderlets[$sAbsName] =& $oRdt;
                    unset($oRdt);

                    // brothers-childs are stored without prefixing, of course
                    $aRdtRefs[$sName] =& $this->aORenderlets[$sAbsName];
                }
            }
        }

        return $aRdtRefs;
    }

    /**
     * Generates an anonymous name for a renderlet
     *
     * @param    array $aElement : conf of the renderlet
     *
     * @return    string        anonymous name generated
     */
    public function _getAnonymousName($aElement)
    {
        return 'anonymous_' . $this->_getSafeLock(
            serialize($aElement)
        );
    }

    public function templateDataAsString($mData)
    {
        if (is_array($mData)) {
            if (array_key_exists('__compiled', $mData)) {
                $mData = $mData['__compiled'];
            } else {
                $mData = '';
            }
        }

        return $mData;
    }

    public function &getRdtForTemplateMethod($mData)
    {
        // returns the renderlet object corresponding to what's asked in the template
        // if none corresponds, then FALSE is returned

        if ((is_object($mData) && ($mData instanceof formidable_mainrenderlet))) {
            return $mData;
        }

        if (is_array($mData) && array_key_exists('htmlid.', $mData) && array_key_exists('withoutformid', $mData['htmlid.'])) {
            $sHtmlId = $mData['htmlid.']['withoutformid'];
            if (array_key_exists($sHtmlId, $this->aORenderlets)) {
                return $this->aORenderlets[$sHtmlId];
            }
        }

        return false;
    }

    public function resolveForInlineConf($sPath, $oRdt = false)
    {
        return $this->getTemplateTool()->resolveScripting('inlineconfmethods', $sPath, $oRdt);
    }

    /**
     *
     * @param string                   $sPath
     * @param formidable_mainrenderlet $oRdt
     *
     * @return formidable_mainrenderlet
     */
    public function resolveForMajixParams($sPath, $oRdt = false)
    {
        return $this->getTemplateTool()->resolveScripting('majixmethods', $sPath, $oRdt);
    }

    /**
     * Liefert die XML-Config
     *
     * @deprecated use getConfigXML()
     * @return tx_mkforms_util_Config
     */
    public function getConfig()
    {
        return $this->getConfigXML();
    }

    /**
     * Liefert die XML-Config
     *
     * @return tx_mkforms_util_Config
     */
    public function getConfigXML()
    {
        return $this->config;
    }

    /**
     * TODO: Zugriff ändern auf Config
     *
     * @param string $path
     * @param array|int $aConf
     * @param string $sSep
     *
     * @return mixed
     */
    public function _navConf($path, $aConf = -1, $sSep = '/')
    {
        return $this->getConfig()->get($path, $aConf, $sSep);
    }

    public function navDef($sPath, $mDefault, $aConf = -1)
    {
        if (($aTemp = $this->getConfig()->get($sPath, $aConf)) !== false) {
            return $aTemp;
        }

        return $mDefault;
    }

    public function navDeepData($sPath, $aData)
    {
        return $this->getConfig()->get($sPath, $aData);
    }

    public function setDeepData($path, &$aConf, $mValue, $bMergeIfArray = false)
    {
        if (!is_array($aConf)) {
            return false;
        }

        $sSep = '/';

        if ($path{0} === $sSep) {
            $path = substr($path, 1);
        }
        if ($path{strlen($path) - 1} === $sSep) {
            $path = substr($path, 0, strlen($path) - 1);
        }

        $aPath = explode($sSep, $path);
        reset($aPath);
        reset($aConf);
        $curZone =& $aConf;

        $iSize = sizeof($aPath);
        for ($i = 0; $i < $iSize; $i++) {
            if (!is_array($curZone) && ($i !== ($iSize - 1))) {
                return false;
            }

            if (is_array($curZone) && !array_key_exists($aPath[$i], $curZone)) {
                $curZone[$aPath[$i]] = array();
            }

            $curZone =& $curZone[$aPath[$i]];

            if ($i === ($iSize - 1)) {
                $mBackup = $curZone;

                if (is_array($curZone) && is_array($mValue) && $bMergeIfArray === true) {
                    // merging arrays
                    $curZone = tx_rnbase_util_Arrays::mergeRecursiveWithOverrule(
                        $curZone,
                        $mValue
                    );
                } else {
                    $curZone = $mValue;
                }

                return $mBackup;
            }
        }

        return false;
    }

    public function unsetDeepData($path, &$aConf)
    {
        if (!is_array($aConf)) {
            return false;
        }

        $sSep = '/';

        if ($path{0} === $sSep) {
            $path = substr($path, 1);
        }
        if ($path{strlen($path) - 1} === $sSep) {
            $path = substr($path, 0, strlen($path) - 1);
        }

        $aPath = explode($sSep, $path);
        reset($aPath);
        reset($aConf);
        $curZone =& $aConf;

        $iSize = sizeof($aPath);
        for ($i = 0; $i < $iSize; $i++) {
            if (!is_array($curZone) && ($i !== ($iSize - 1))) {
                return false;
            }

            if (is_array($curZone) && !array_key_exists($aPath[$i], $curZone)) {
                return false;
            }

            if ($i === ($iSize - 1)) {
                unset($curZone[$aPath[$i]]);

                return true;
            } else {
                $curZone =& $curZone[$aPath[$i]];
            }
        }

        return false;
    }

    public function implodePathesForArray($aData)
    {
        $aPathes = array();
        $this->implodePathesForArray_rec($aData, $aPathes);
        reset($aPathes);

        return $aPathes;
    }

    public function implodePathesForArray_rec($aData, &$aPathes, $aSegment = array())
    {
        $aKeys = array_keys($aData);
        reset($aKeys);
        while (list(, $sKey) = each($aKeys)) {
            $aTemp = $aSegment;
            $aTemp[] = $sKey;
            if (is_array($aData[$sKey])) {
                $this->implodePathesForArray_rec(
                    $aData[$sKey],
                    $aPathes,
                    $aTemp
                );
            } else {
                $aPathes[] = implode($aTemp, '/');
            }
        }
    }

    public function getTcaVal($sAddress)
    {
        if ($sAddress{0} === 'T' && $sAddress{1} === 'C' && substr($sAddress, 0, 4) === 'TCA:') {
            $aParts = explode(':', $sAddress);
            unset($aParts[0]);

            $sPath = $aParts[1];
            $aPath = explode('/', $sPath);
            $sTable = $aPath[0];

            tx_rnbase::load('tx_rnbase_util_TCA');
            tx_rnbase_util_TCA::loadTCA($sTable);

            return $this->getConfig()->get($sPath, $GLOBALS['TCA']);
        }

        return false;
    }

    public function fetchServerEvents()
    {
        $aKeys = array_keys($this->aORenderlets);
        reset($aKeys);
        while (list(, $sKey) = each($aKeys)) {
            $this->aORenderlets[$sKey]->fetchServerEvents();
        }
    }

    /**
     * Renders the whole application. Alias for _render().
     *
     * @return string
     */
    public function render()
    {
        return $this->_render();
    }

    /**
     * Renders the whole application
     *
     * @return    string        Full rendered HTML
     */
    public function _render()
    {
        if ($this->bInited === false) {
            $this->start_tstamp = Tx_Rnbase_Utility_T3General::milliseconds();    // because it has not been initialized yet
            $this->mayday('TRIED TO RENDER FORM BEFORE CALLING INIT() !');
        }

        $this->checkPoint(array('before-render',));

        //submit mode merken
        $this->setIsFullySubmitted();

        $this->bRendered = true;

        $this->oRenderer->renderStyles();

        if ($this->getDataHandler()->_isSubmitted()) {
            //jetzt prüfen wir ob das Formular auch vom Nutzer abgeschickt wurde,
            //der das Formular erstellt hat
            if ($this->isCsrfProtectionActive() && !$this->validateRequestToken()) {
                throw new RuntimeException(
                    'Das Formular ist nicht valide! erwarteter Token: ' . $this->getRequestTokenFromSession(),
                    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['baseExceptionCode'] . '1'
                );
            }

            if ($this->getDataHandler()->_isFullySubmitted()) {
                $this->_debug('', 'HANDLING --- FULL --- SUBMIT EVENT');

                // validation of the renderlets
                $this->validateEverything();

                $this->_filterUnProcessed();
                $this->getJSLoader()->includeBaseLibraries();

                // the datahandler is executed
                $sDH = $this->getDataHandler()->_doTheMagic(true);

                // Renderlets are rendered
                $aRendered = tx_rnbase_util_Arrays::mergeRecursiveWithOverrule(
                    $this->_renderElements(),
                    $this->aPreRendered
                );

                if ($this->oDataHandler->_allIsValid()) {
                    $this->checkPoint(
                        array(
                            'after-validation-ok',
                        )
                    );
                } else {
                    $options = array('renderedRenderlets' => &$aRendered);
                    $this->checkPoint(
                        array(
                            'after-validation-nok',
                        ),
                        $options
                    );
                }

                if (count($this->_aValidationErrors) > 0) {
                    $this->attachErrorsByJS($this->_aValidationErrors, 'errors');
                    $this->_debug($this->_aValidationErrors, 'SOME ELEMENTS ARE NOT VALIDATED');
                } else {
                    // wenn keine validationsfehler aufgetreten sind,
                    // eventuell vorherige validierungs fehler entfernen
                    $this->_debug('', 'ALL ELEMENTS ARE VALIDATED');
                    $this->attachErrorsByJS(null, 'errors', true);
                }

                // the renderer is executed
                $aHtmlBag = $this->oRenderer->_render(
                    $aRendered
                );

                $this->checkPoint(
                    array(
                        'after-render',
                    )
                );

                // ACTIONLETS are executed
                if ($this->oDataHandler->_allIsValid()) {
                    $this->checkPoint(
                        array(
                            'before-actionlets',
                        )
                    );

                    $this->_executeActionlets(
                        $aRendered,
                        $aHtmlBag['CONTENT']
                    );

                    $this->checkPoint(
                        array(
                            'after-actionlets',
                        )
                    );
                }
            } elseif ($this->getDataHandler()->_isRefreshSubmitted()) {
                $this->_debug('NO VALIDATION REQUIRED', 'HANDLING --- REFRESH --- SUBMIT EVENT');

                $this->_filterUnProcessed();
                $this->getJSLoader()->includeBaseLibraries();

                // the datahandler is executed
                $sDH = $this->oDataHandler->_doTheMagic(false);

                // Renderlets are rendered
                $aRendered = tx_rnbase_util_Arrays::mergeRecursiveWithOverrule(
                    $this->_renderElements(),
                    $this->aPreRendered
                );

                // the renderer is executed
                $aHtmlBag = $this->oRenderer->_render(
                    $aRendered
                );

                $this->checkPoint(
                    array(
                        'after-render',
                    )
                );
            } elseif ($this->oDataHandler->_isTestSubmitted()) {
                $this->_debug('VALIDATION REQUIRED ( ONLY )', 'HANDLING --- TEST --- SUBMIT EVENT');

                // validation of the renderlets
                $this->validateEverything();

                $this->_filterUnProcessed();
                $this->getJSLoader()->includeBaseLibraries();

                // the datahandler is executed
                $sDH = $this->oDataHandler->_doTheMagic(false);

                // Renderlets are rendered
                $aRendered = tx_rnbase_util_Arrays::mergeRecursiveWithOverrule(
                    $this->_renderElements(),
                    $this->aPreRendered
                );

                // the renderer is executed
                $aHtmlBag = $this->oRenderer->_render(
                    $aRendered
                );

                $this->checkPoint(
                    array(
                        'after-render',
                    )
                );
            } elseif ($this->oDataHandler->_isDraftSubmitted()) {
                $this->_debug('NO VALIDATION REQUIRED', 'HANDLING --- DRAFT --- SUBMIT EVENT');

                // validation of the renderlets
                $this->validateEverythingDraft();

                $this->_filterUnProcessed();
                $this->getJSLoader()->includeBaseLibraries();

                // the datahandler is executed
                $sDH = $this->oDataHandler->_doTheMagic(true);

                // Renderlets are rendered
                $aRendered = tx_rnbase_util_Arrays::mergeRecursiveWithOverrule(
                    $this->_renderElements(),
                    $this->aPreRendered
                );

                if ($this->oDataHandler->_allIsValid()) {
                    $this->checkPoint(
                        array(
                            'after-validation-ok',
                        )
                    );
                } else {
                    $options = array('renderedRenderlets' => &$aRendered);
                    $this->checkPoint(
                        array(
                            'after-validation-nok',
                        ),
                        $options
                    );
                }

                if (count($this->_aValidationErrors) > 0) {
                    $this->attachErrorsByJS($this->_aValidationErrors, 'errors');
                    $this->_debug($this->_aValidationErrors, 'SOME ELEMENTS ARE NOT VALIDATED');
                } else {
                    // wenn keine validationsfehler aufgetreten sind,
                    // eventuell vorherige validierungs fehler entfernen
                    $this->_debug('', 'ALL ELEMENTS ARE VALIDATED');
                    $this->attachErrorsByJS(null, 'errors', true);
                }

                // the renderer is executed
                $aHtmlBag = $this->oRenderer->_render(
                    $aRendered
                );

                $this->checkPoint(
                    array(
                        'after-render',
                    )
                );
            } elseif ($this->oDataHandler->_isClearSubmitted() || $this->oDataHandler->_isSearchSubmitted()) {
                $this->_debug('NO VALIDATION REQUIRED', 'HANDLING --- CLEAR OR SEARCH --- SUBMIT EVENT');

                $this->_filterUnProcessed();
                $this->getJSLoader()->includeBaseLibraries();

                // the datahandler is executed
                $sDH = $this->oDataHandler->_doTheMagic(false);

                // Renderlets are rendered
                $aRendered = tx_rnbase_util_Arrays::mergeRecursiveWithOverrule(
                    $this->_renderElements(),
                    $this->aPreRendered
                );

                // the renderer is executed
                $aHtmlBag = $this->oRenderer->_render(
                    $aRendered
                );

                $this->checkPoint(array('after-render',));
            }
        } else {
            $this->_debug('NO VALIDATION REQUIRED', 'NO SUBMIT EVENT TO HANDLE');

            $this->_filterUnProcessed();
            // TODO: _includeLibraries
            $this->getJSLoader()->includeBaseLibraries();

            // the datahandler is executed
            $sDH = $this->oDataHandler->_doTheMagic(false);

            // Renderlets are rendered
            $aRendered = tx_rnbase_util_Arrays::mergeRecursiveWithOverrule(
                $this->_renderElements(),
                $this->aPreRendered
            );

            // the renderer is executed
            $aHtmlBag = $this->oRenderer->_render($aRendered);

            $this->checkPoint(array('after-render',));
        }

        $this->checkPoint(array('before-js-inclusion'));

        if ($this->_defaultTrue('/meta/exportstyles')) {
            $aStyles = $this->getAllHtmlTags('style', $aHtmlBag['CONTENT']);
            if (!empty($aStyles)) {
                $aHtmlBag['CONTENT'] = str_replace(
                    $aStyles,
                    "<!-- Style tag exported to external css file -->\n",
                    $aHtmlBag['CONTENT']
                );

                reset($aStyles);
                while (list(, $sStyle) = each($aStyles)) {
                    $sStyle = $this->oHtml->removeFirstAndLastTag($sStyle);

                    reset($this->aORenderlets);
                    while (list($sName, ) = each($this->aORenderlets)) {
                        $sStyle = str_replace('#' . $sName, '#' . $this->aORenderlets[$sName]->_getElementCssId(), $sStyle);
                    }

                    $this->additionalHeaderData(
                        $this->inline2TempFile(
                            $sStyle,
                            'css',
                            'Exported style-tags of "' . $this->formid . '"'
                        )
                    );
                }
            }
        }

        $this->fetchAjaxServices();

        $debug = '';

        $this->_debug($aHtmlBag, 'FORMIDABLE CORE - RETURN');

        if ($this->getConfig()->isDebug() || $this->bDebug) {
            $debug = $this->debug();
        }

        if ($this->getJSLoader()->useJs()) {
            reset($this->aORenderlets);

            $this->attachAccessibilityInit();

            if ($this->aAddPostVars !== false) {
                reset($this->aAddPostVars);
                while (list($sKey, ) = each($this->aAddPostVars)) {
                    if (array_key_exists('action', $this->aAddPostVars[$sKey])
                        && $this->aAddPostVars[$sKey]['action'] == 'execOnNextPage'
                    ) {
                        $aTask = $this->aAddPostVars[$sKey]['params'];
                        $this->attachInitTask(
                            $this->oRenderer->_getClientEvent($aTask['object'], array(), $aTask, 'execOnNextPage')
                        );
                    }
                }
            }

            $this->getJSLoader()->includeCodeBehind();
            $this->attachCodeBehindJsInits();
            $this->attachRdtEvents();
            $this->attachAjaxServices();

            reset($this->aOnloadEvents['ajax']);
            while (list($sEventId, $aEvent) = each($this->aOnloadEvents['ajax'])) {
                $this->attachInitTask(
                    $this->oRenderer->_getAjaxEvent($this->aORenderlets[$aEvent['name']], $aEvent['event'], 'onload'),
                    'AJAX Event onload for ' . $this->formid . '.' . $aEvent['name'],
                    $sEventId
                );
            }

            reset($this->aOnloadEvents['client']);
            while (list(, $aEvent) = each($this->aOnloadEvents['client'])) {
                $this->attachInitTask(
                    $this->oRenderer->_getClientEvent($aEvent['name'], $aEvent['event'], $aEvent['eventdata'], 'onload'),
                    'CLIENT Event onload for ' . $this->formid . '.' . $aEvent['name']
                );
            }
            $this->getJSLoader()->setLoadedScripts();

            $sJs = "MKWrapper.onDOMReady(function() {\n";
            $sJs .= implode('', $this->aInitTasks);
            $sJs .= "\n});";
            $sJs .= implode("\n", $this->aInitTasksOutsideLoad);

            if ($this->shouldGenerateScriptAsInline() === false) {
                $this->additionalHeaderData(
                    $this->inline2TempFile($sJs, 'js', 'Formidable \'' . $this->formid . '\' initialization')
                );
            } else {
                $this->additionalHeaderData(
                    "<!-- BEGIN:Formidable '" . $this->formid . "' initialization-->\n" . "<script type=\"text/javascript\">\n"
                    . $sJs . "\n</script>\n" . "<!-- END:Formidable '" . $this->formid . "' initialization-->\n"
                );
            }

            // Damit werden direkt zusätzliche HeaderDaten geschrieben
            $this->getJSLoader()->includeAdditionalLibraries();

            $sJs = "MKWrapper.onDOMReady(function() {\n" . implode('', $this->aPostInitTasks) . "\n});";
            if ($this->shouldGenerateScriptAsInline() === false) {
                $this->additionalHeaderData(
                    $this->inline2TempFile($sJs, 'js', 'Formidable \'' . $this->formid . '\ post-initialization')
                );
            } else {
                $this->additionalHeaderData(
                    "<!-- BEGIN:Formidable '" . $this->formid . "' post-initialization-->\n"
                    . "<script type=\"text/javascript\">\n" . $sJs . "\n</script>\n" . "<!-- END:Formidable '" . $this->formid
                    . "' post-initialization-->\n"
                );
            }

            $this->getJSLoader()->injectHeaders();
        }

        if ($this->oDataHandler->bHasCreated) {
            $this->checkPoint(array('after-js-inclusion', 'after-validation', 'end-creation', 'end'));
        } elseif ($this->oDataHandler->bHasEdited) {
            $this->checkPoint(array('after-js-inclusion', 'after-validation', 'end-edition', 'end'));
        } else {
            $this->checkPoint(array('after-js-inclusion', 'after-validation', 'end'));
        }

        $this->setStoreFormInSession(($this->storeFormInSession || $this->_defaultFalse('/meta/keepinsession')));

        if ($this->storeFormInSession === true && !$this->isTestMode()) {
            $sesMgr = tx_mkforms_session_Factory::getSessionManager();
            $sesMgr->persistForm();
        } else {
            $this->_clearFormInSession();
        }

        $this->end_tstamp = Tx_Rnbase_Utility_T3General::milliseconds();
        if (!empty($sDH)) {
            return $aHtmlBag['FORMBEGIN'] . $sDH . $aHtmlBag['HIDDEN'] . $aHtmlBag['FORMEND'] . $debug;
        } else {
            return $aHtmlBag['FORMBEGIN'] . $aHtmlBag['CONTENT'] . $aHtmlBag['HIDDEN'] . $aHtmlBag['FORMEND'] . $debug;
        }
    }

    /**
     * Setzen ob die Form fullySubmitted ist oder nicht
     */
    public function setIsFullySubmitted()
    {
        $this->bIsFullySubmitted = $this->getDataHandler()->_isFullySubmitted();
    }

    /**
     * ist die form fullySubmitted
     */
    public function isFullySubmitted()
    {
        return $this->bIsFullySubmitted;
    }

    /**
     * Wenn wir Testen dann darf die Form nicht in der Session gespeichert
     * werden. Also verhindern wir das.
     */
    public function setTestMode()
    {
        $this->bTestMode = true;
    }

    public function isTestMode()
    {
        return $this->bTestMode;
    }

    public function fetchAjaxServices()
    {
        $aMeta = $this->getConfig()->get('/meta');
        if (!is_array($aMeta)) {
            $aMeta[0] = $aMeta;
        }
        $aServices = array_merge(// array_merge reindexes array
            array_filter(
                array_keys($aMeta),
                array($this, '__cbkFilterAjaxServices')
            )
        );

        reset($aServices);
        while (list(, $sServiceKey) = each($aServices)) {
            if (($mService = $this->getConfig()->get('/meta/' . $sServiceKey)) !== false) {
                $sName = array_key_exists('name', $mService) ? trim(strtolower($mService['name'])) : '';
                $sServiceId = $this->getAjaxServiceId($mService['name']);

                if ($sName === '') {
                    $this->mayday('Ajax service: ajax service requires /name to be set.');
                }

                $this->aAjaxServices[$sServiceId] = array(
                    'definition' => $mService,
                );
            }
        }

        if (!empty($aServices)) {
            // an ajax service (or more) is declared
            // we have to store this form in session
            // for serving ajax requests

            $this->setStoreFormInSession();

            $GLOBALS['_SESSION']['ameos_formidable']['ajax_services']['tx_ameosformidable']['ajaxservice'][$this->_getSessionDataHashKey(
            )]
                = array(
                'requester' => array(
                    'name' => 'tx_ameosformidable',
                    'xpath' => '/',
                ),
            );
        }
    }

    public function attachAjaxServices()
    {
        $aRes = array();
        $sSafeLock = $this->_getSessionDataHashKey();

        reset($this->aAjaxServices);
        while (list($sServiceId, ) = each($this->aAjaxServices)) {
            $sMixedCaseName = trim($this->aAjaxServices[$sServiceId]['definition']['name']);

            $sJs
                = <<<JAVASCRIPT
Formidable.f("{$this->formid}").declareAjaxService("{$sMixedCaseName}", "{$sServiceId}", "{$sSafeLock}");
JAVASCRIPT;
            $aRes[] = $sJs;
        }

        $this->attachInitTask(
            implode("\n", $aRes),
            'Ajax Services'
        );
    }

    public function __cbkFilterAjaxServices($sName)
    {
        $sName = strtolower($sName);

        return ($sName{0} === 'a') && ($sName{1} === 'j')
        && (substr($sName, 0, 11) === 'ajaxservice');    // should start with 'aj'
    }

    public function getAjaxServiceId($sName)
    {
        return $this->_getSafeLock('ajaxservice:' . $this->formid . ':' . $sName);
    }

    public function processDataBridges($bShouldProcess = true)
    {
        if ($bShouldProcess === false) {
            return;
        }

        $aRdts = array_keys($this->aORenderlets);

        reset($aRdts);
        while (list(, $sName) = each($aRdts)) {
            if (array_key_exists($sName, $this->aORenderlets) && !$this->aORenderlets[$sName]->hasParent()) {
                $this->aORenderlets[$sName]->processDataBridge();
            }
        }
    }

    public function attachRdtEvents()
    {
        $this->attachInitTask(
            implode("\n", $this->aRdtEvents),
            'RDT Events'
        );
    }

    public function attachCodeBehindJsInits()
    {
        $this->attachInitTask(
            implode("\n", $this->aCodeBehindJsInits),
            'CodeBehind inits'
        );
    }

    public function attachAccessibilityInit()
    {
        reset($this->aORenderlets);
        while (list($sKey, ) = each($this->aORenderlets)) {
            if ($this->aORenderlets[$sKey]->hideIfJs() === true) {
                $this->attachInitTask(
                    "Formidable.f('" . $this->formid . "').o('" . $this->aORenderlets[$sKey]->_getElementHtmlId()
                    . "').displayNone();",
                    'Access'
                );
            }
        }

        $this->aInitTasks = array_merge(array_values($this->aInitTasksUnobtrusive), array_values($this->aInitTasks));
        // array_merge of array_values to avoid overruling
    }

    public function shouldGenerateScriptAsInline()
    {
        return $this->_defaultFalse('/meta/inlinescripts');
    }

    /**
     * Bindet zusätzlichen JS-Code ein, der nach der Initialisierung der Widgets abgefahren wird.
     *
     * @param string $sScript
     * @param string $sDesc
     * @param string $sKey
     */
    public function attachPostInitTask($sScript, $sDesc = '', $sKey = false)
    {
        if (tx_mkforms_util_Div::getEnvExecMode() === 'EID') {
            $this->attachPostInitTask_ajax($sScript, $sDesc, $sKey);
        } else {
            $this->attachPostInitTask_plain($sScript, $sDesc, $sKey);
        }
    }

    private function attachPostInitTask_plain($sScript, $sDesc = '', $sKey = false)
    {
        $sJs = "\n" . trim($sScript);
        if ($sKey === false) {
            $this->aPostInitTasks[] = $sJs;
        } else {
            $this->aPostInitTasks[$sKey] = $sJs;
        }
    }

    private function attachPostInitTask_ajax($sScript, $sDesc = '', $sKey = false)
    {
        if ($sDesc != '') {
            $sDesc = "\n\n/* FORMIDABLE: " . trim(str_replace(array('/*', '*/', '//'), '', $sDesc)) . ' */';
        }

        $sJs = $sDesc . "\n" . trim($sScript) . "\n";

        if ($sKey === false) {
            $this->aPostInitTasksAjax[] = $sJs;
        } else {
            $this->aPostInitTasksAjax[$sKey] = $sJs;
        }
    }

    /**
     * Declares a JS task to execute at page init time
     *
     * @param    string  $sScript      : JS code
     * @param    string  $sDesc        : optional; description of the code, place as a comment in the HTML
     * @param    string  $sKey         : optional; key of the js code in the header array
     * @param    bool $bOutsideLoad : optional; load it at onload time, or after
     *
     * @return    void
     */
    public function attachInitTask($sScript, $sDesc = '', $sKey = false, $bOutsideLoad = false)
    {
        if (tx_mkforms_util_Div::getEnvExecMode() === 'EID') {
            $this->attachInitTask_ajax($sScript, $sDesc, $sKey, $bOutsideLoad);
        } else {
            $this->attachInitTask_plain($sScript, $sDesc, $sKey, $bOutsideLoad);
        }
    }

    public function attachInitTaskUnobtrusive($sScript)
    {
        $this->aInitTasksUnobtrusive[] = $sScript;
    }

    public function attachInitTask_plain($sScript, $sDesc = '', $sKey = false, $bOutsideLoad = false)
    {
        $sJs = "\n" . trim($sScript);

        if ($bOutsideLoad) {
            if ($sKey === false) {
                $this->aInitTasksOutsideLoad[] = $sJs;
            } else {
                $this->aInitTasksOutsideLoad[$sKey] = $sJs;
            }
        } else {
            if ($sKey === false) {
                $this->aInitTasks[] = $sJs;
            } else {
                $this->aInitTasks[$sKey] = $sJs;
            }
        }
    }

    public function attachInitTask_ajax($sScript, $sDesc = '', $sKey = false, $bOutsideLoad = false)
    {
        if ($sDesc != '') {
            $sDesc = "\n\n/* FORMIDABLE: " . trim(str_replace(array('/*', '*/', '//'), '', $sDesc)) . ' */';
        }

        $sJs = $sDesc . "\n" . trim($sScript) . "\n";

        if ($sKey === false) {
            $this->aInitTasksAjax[] = $sJs;
        } else {
            $this->aInitTasksAjax[$sKey] = $sJs;
        }
    }

    /**
     * Renders all the Renderlets elements as defined in conf
     *
     * @param    bool $bRenderChilds : render childs ? or not
     *
     * @return    array        Array of rendered elements, structured as $elementname => $renderedHTML
     */
    public function _renderElements($bRenderChilds = false)
    {
        $aHtml = array();

        $aKeys = array_keys($this->aORenderlets);
        $iKeys = sizeof($aKeys);

        for ($k = 0; $k < $iKeys; $k++) {
            $sName = $aKeys[$k];

            if (!$this->aORenderlets[$sName]->isChild() || $bRenderChilds) {
                if ($this->oDataHandler->aObjectType['TYPE'] != 'LISTER' || $this->aORenderlets[$sName]->_searchable()) {
                    if (($mHtml = $this->_renderElement($this->aORenderlets[$sName])) !== false) {
                        $aHtml[$sName] = $mHtml;
                    }
                }
            }
        }

        reset($aHtml);

        return $aHtml;
    }

    /**
     * Renders the given Renderlet
     *
     * @param    array $aElement : details about the Renderlet to render, extracted from conf
     *
     * @return    string        The Rendered HTML
     */
    public function _renderElement(&$oRdt)
    {
        if (!$oRdt->i18n_hideBecauseNotTranslated()) {
            $mHtml = $this->oRenderer->processHtmlBag(
                $oRdt->render(),
                $oRdt    // changed: avoid call-time pass-by-reference
            );

            return $mHtml;
        }

        return false;
    }

    /**
     * @deprecated
     * Returns system informations about an object-type
     *
     * @param    string $type             : something like TEXT, IMAGE, ...
     * @param    array  $aCollectionInfos : the collection of objects where to get infos
     *
     * @return    mixed        array of info or FALSE if failed
     */
    public function _getInfosForType($type, $aCollectionInfos)
    {
        reset($aCollectionInfos);
        while (list(, $aInfos) = each($aCollectionInfos)) {
            if ($aInfos['TYPE'] == $type) {
                reset($aInfos);

                return $aInfos;
            }
        }

        return false;
    }

    /**
     * Returns system-informations about the given datasource type
     *
     * @param    string $type : given type
     *
     * @return    array        system-informations
     */
    public function _getInfosDataSourceForType($sType)
    {
        return $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ameos_formidable']['datasources'][$sType];
    }

    /**
     * Returns system-informations about the given datahandler type
     *
     * @param    string $type : given type
     *
     * @return    array        system-informations
     */
    public function _getInfosDataHandlerForType($sType)
    {
        return $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ameos_formidable']['datahandlers'][$sType];
    }

    /**
     * Returns system-informations about the given renderer type
     *
     * @param    string $type : given type
     *
     * @return    array        system-informations
     */
    public function _getInfosRendererForType($sType)
    {
        return $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ameos_formidable']['renderers'][$sType];
    }

    /**
     * Returns system-informations about the given validator type
     *
     * @param    string $type : given type
     *
     * @return    array        system-informations
     */
    public function _getInfosValidatorForType($sType)
    {
        return $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ameos_formidable']['validators'][$sType];
    }

    /**
     * Returns system-informations about the given actionlet type
     *
     * @param    string $type : given type
     *
     * @return    array        system-informations
     */
    public function _getInfosActionletForType($sType)
    {
        return $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ameos_formidable']['actionlets'][$sType];
    }

    /**
     * Liefert den ObjectLoader
     *
     * @return tx_mkforms_util_Loader
     */
    public function getObjectLoader()
    {
        return tx_mkforms_util_Loader::getInstance($this->getFormId());
    }

    /**
     * Makes and initializes a datasource object
     *
     * @param    array  $aElement : conf for this object instance
     * @param    string $sXPath   : xpath where this conf is declared
     *
     * @return    object
     */
    public function _makeDataSource($aElement, $sXPath)
    {
        return $this->getObjectLoader()->makeObject($aElement, 'datasources', $sXPath, $this);
    }

    /**
     * Makes and initializes a datahandler object
     *
     * @param    array $aElement : conf for this object instance
     *
     * @return    object
     */
    public function _makeDataHandler($aElement)
    {
        return $this->getObjectLoader()->makeObject($aElement, 'datahandlers', '/control/datahandler/', $this);
    }

    /**
     * Makes and initializes a renderer object
     *
     * @param    array $aElement : conf for this object instance
     *
     * @return    object
     */
    public function _makeRenderer($aElement)
    {
        return $this->getObjectLoader()->makeObject($aElement, 'renderers', '/control/renderer/', $this);
    }

    /**
     * Makes and initializes a renderlet object
     *
     * @param array $aElement conf for this object instance
     * @param string $sXPath xpath where this conf is declared
     * @param bool $bChilds
     * @param object|bool $oChildParent
     * @param bool $bAnonymous
     * @param string|bool $sNamePrefix
     *
     * @return formidable_mainrenderlet
     */
    public function _makeRenderlet(
        array $aElement,
        $sXPath,
        $bChilds = false,
        $oChildParent = false,
        $bAnonymous = false,
        $sNamePrefix = false
    ) {
        $aOParent = array();
        $aRawPost = $this->_getRawPost();

        if ($bChilds !== false) {
            // optional params cannot be passed by ref, so we're using the array-trick here
            $aOParent = array(&$oChildParent);
        }

        $oRdt =& $this->getObjectLoader()->makeObject($aElement, 'renderlets', $sXPath, $this, $sNamePrefix, $aOParent);
        $oRdt->bAnonymous = $bAnonymous;
        $oRdt->bChild = $bChilds;

        $sAbsName = $oRdt->getAbsName();
        $sAbsPath = str_replace(AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN, '/', $sAbsName);
        if ($this->navDeepData($sAbsPath, $aRawPost) !== false) {
            $oRdt->bHasBeenPosted = true;
        }

        return $oRdt;
    }

    /**
     * Makes and initializes a validator object
     *
     * @param    array $aElement : conf for this object instance
     *
     * @return    object
     */
    public function _makeValidator($aElement)
    {
        return $this->getObjectLoader()->makeObject($aElement, 'validators', '', $this);
    }

    /**
     * Makes and initializes an actionlet object
     *
     * @param    array  $aElement : conf for this object instance
     * @param    string $sXPath   : xpath where this conf is declared
     *
     * @return    object
     */
    public function _makeActionlet($aElement)
    {
        return $this->getObjectLoader()->makeObject($aElement, 'actionlets', '/control/actionlets/', $this);
    }

    public function validateEverything()
    {
        $this->_validateElements();
    }

    public function validateEverythingDraft()
    {
        $this->_validateElementsDraft();
    }

    /**
     * Validates data returned by all the Renderlets elements as defined in conf
     *
     * @return    void        Writes into $this->_aValidationErrors[] using tx_ameosformidable::_declareValidationError()
     */
    public function _validateElements()
    {
        if ($this->oDataHandler->_isSubmitted()) {
            $aRdtKeys = array_keys($this->aORenderlets);

            reset($aRdtKeys);
            while (list(, $sAbsName) = each($aRdtKeys)) {
                // nur validieren wenn für die validierung submitted wurde (_isSubmitted validiert bei allen submits)
                if ($this->aORenderlets[$sAbsName]->_isSubmittedForValidation()
                    && ($this->aORenderlets[$sAbsName]->getIterableAncestor() === false)
                ) {
                    $this->aORenderlets[$sAbsName]->validate();
                }
            }
        }
    }

    /**
     * Validates data returned by all the Renderlets, draft-mode
     *
     * @return    void
     */
    public function _validateElementsDraft()
    {
        if ($this->oDataHandler->_isSubmitted()) {
            $aRdtKeys = array_keys($this->aORenderlets);

            reset($aRdtKeys);
            while (list(, $sName) = each($aRdtKeys)) {
                if ($this->aORenderlets[$sName]->_hasToValidateForDraft()) {
                    $this->aORenderlets[$sName]->validate();
                }
            }
        }
    }

    /**
     * Declares validation error
     * Used by Validators Objects
     *
     * @param    string $sElementName
     * @param    string $sKey
     * @param    string $sMessage : the error message to display
     *
     * @return    void        Writes into $this->_aValidationErrors[]
     */
    public function _declareValidationError($sElementName, $sKey, $sMessage)
    {
        if (array_key_exists($sElementName, $this->aORenderlets)) {
            $sHtmlId = $this->getWidget($sElementName)->getElementId(false);

            if (!array_key_exists($sHtmlId, $this->_aValidationErrorsByHtmlId)) {
                $sNamespace = array_shift(explode(':', $sKey));
                $sType = array_pop(explode(':', $sKey));

                if (trim($sMessage) === '') {
                    if ($this->sDefaultLLLPrefix !== false) {
                        // trying to automap the error message
                        $sKey = 'LLL:' . $sElementName . '.error.' . $sType;
                        $sMessage = $this->getConfig()->getLLLabel($sKey);
                    }
                }

                $this->_aValidationErrors[$sElementName]
                    = $sMessage;    // only one message per renderlet per refresh ( errors displayed one by one )
                $this->_aValidationErrorsByHtmlId[$sHtmlId] = $sMessage;

                $this->_aValidationErrorsInfos[$sHtmlId] = array(
                    'elementname' => $sElementName,
                    'message' => $sMessage,
                    'namespace' => $sNamespace,
                    'type' => $sType,
                );

                $this->_aValidationErrorsTypes[$sKey] = array(
                    'namespace' => $sNamespace,
                    'type' => $sType,
                );
            }
        }
    }

    // $sKey like 'STANDARD:required', 'DB:unique', ...
    public function _hasErrorType($sKey)
    {
        // consider unstable as of rev 101 if /process has unset renderlet, and this renderlet was the only one to throw that type of error
        // TODO: unset also type if it's the case
        return array_key_exists($sKey, $this->_aValidationErrorsTypes);
    }

    public function declareAjaxService($sExtKey, $sServiceKey, $bVirtualizeFE = true, $bInitBEuser = false)
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ameos_formidable']['ajax_services'][$sExtKey][$sServiceKey]['conf'] = array(
            'virtualizeFE' => $bVirtualizeFE,
            'initBEuser' => $bInitBEuser,
        );
    }

    /**
     * Execute each actionlet declared for this FORM
     *
     * @param    array  $aRendered :    array containing the HTML of the rendered renderlets
     * @param    string $sForm     : the whole FORM html string
     *
     * @return    void
     * @see tx_ameosformidable::_render()
     */
    public function _executeActionlets($aRendered, $sForm)
    {
        $this->_executeActionletsByPath('/control', $aRendered, $sForm);
        $this->_executeActionletsByPath('/control/actionlets', $aRendered, $sForm);
    }

    public function _executeActionletsByPath($sPath, $aRendered, $sForm)
    {
        $aActionlets = $this->getConfig()->get($sPath);

        if (is_array($aActionlets)) {
            while (list($sKey, $aActionlet) = each($aActionlets)) {
                if ($sKey{0} === 'a' && $sKey{1} === 'c' && Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sKey, 'actionlet')
                    && !Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sKey, 'actionlets')
                ) {
                    $this->_executeActionlet($aActionlet, $aRendered, $sForm);
                }
            }
        }
    }

    /**
     * Executes the specific process for this actionlet
     *
     * @param    array  $aActionlet : details about the Renderlet element to validate, extracted from XML conf / used in
     *                              formidable_mainvalidator::validate()
     * @param    array  $aRendered  : array containing the HTML of the rendered renderlets
     * @param    string $sForm      : the whole FORM html string
     *
     * @return    void
     * @see tx_ameosformidable::_executeActionlets()
     */
    public function _executeActionlet($aActionlet, $aRendered, $sForm)
    {
        $oActionlet = $this->_makeActionlet($aActionlet);
        $oActionlet->_doTheMagic($aRendered, $sForm);
    }









    /*********************************
     *
     * Debugging functions
     *
     *********************************/

    /**
     * Displays a full debug of :
     * - the XML conf
     * - the collection of declared DataHandlers
     * - the collection of declared Renderers
     * - the collection of declared Renderlets
     * - the collection of declared Validators
     *
     * Can be called by the parent Extension, or by FORMidable itselves, if the XML conf sets /formidable/meta/debug/ to TRUE
     *
     * @param    [type]        $bExpand: ...
     *
     * @return    void
     * @see      tx_ameosformidable::mayday(), tx_ameosformidable::_render()
     */
    public function debug($bExpand = false)
    {
        tx_rnbase::load('tx_rnbase_util_Debug');

        $aHtml = array();

        $aHtml[] = '<a name= "' . $this->formid . 'formidable_debugtop" />';

        // wenn kein js immer ausklappen
        if ($bExpand === false && $this->getJSLoader()->mayLoadJsFramework()) {
            $aHtml[] = '<a href="javascript:void(Formidable.f(\'' . $this->formid . '\').toggleDebug())"><img src="'
                . Tx_Rnbase_Utility_T3General::getIndpEnv('TYPO3_SITE_URL') . tx_rnbase_util_Extensions::siteRelPath('mkforms')
                . '/res/images/debug.gif" border="0" alt="Toggle mkforms::debug()" title="Toggle mkforms::debug()"></a>';
            $aHtml[] = '<div id="' . $this->formid
                . '_debugzone" style="font-family: Verdana; display: none; background-color: #bed1f4; padding-left: 10px; padding-top: 3px; padding-bottom: 10px;font-size: 9px;">';
        } else {
            $aHtml[] = '<img src="' . Tx_Rnbase_Utility_T3General::getIndpEnv('TYPO3_SITE_URL') . tx_rnbase_util_Extensions::siteRelPath('mkforms')
                . '/res/images/debug.gif" border="0" alt="Toggle FORMidable::debug()" title="Toggle FORMidable::debug()">';
            $aHtml[] = "<div id = '" . $this->formid
                . "_debugzone' style = 'font-family: Verdana; display: block; background-color: #bed1f4; padding-left: 10px; padding-top: 3px; padding-bottom: 10px;font-size: 9px;'>";
        }

        $aHtml[] = '<h4>FORMidable debug()</h4>';

        $aHtml[] = '<h5>Tx_Rnbase_Utility_T3General::_POST()</h5>';
        $aHtml[] = tx_rnbase_util_Debug::viewArray(Tx_Rnbase_Utility_T3General::_POST());

        $aHtml[] = '<h5>Tx_Rnbase_Utility_T3General::_GET()</h5>';
        $aHtml[] = tx_rnbase_util_Debug::viewArray(Tx_Rnbase_Utility_T3General::_GET());

        $aHtml[] = '<br>';
        $aHtml[] = '<ul>';

        if (!is_null($this->_xmlPath)) {
            // conf passed by php array ( // typoscript )
            $aHtml[] = "<li><a href = '#" . $this->formid . "formidable_xmlpath' target = '_self'>XML Path</a></li>";
        }

        $aHtml[] = "<li><a href = '#" . $this->formid . "formidable_configuration' target = '_self'>FORM configuration</a></li>";
        $aHtml[] = "<li><a href = '#" . $this->formid . "formidable_callstack' target = '_self'>Call stack</a></li>";
        $aHtml[] = '</ul>';

        if (!is_null($this->_xmlPath)) {
            $aHtml[] = "<a name = '" . $this->formid . "formidable_xmlpath' />";
            $aHtml[] = '<h5>XML Path</h5>';
            $aHtml[] = $this->_xmlPath;

            $aHtml[] = "<p align = 'right'><a href = '#" . $this->formid . "formidable_debugtop' target = '_self'>^top^</a></p>";

            $aHtml[] = "<a name = '" . $this->formid . "formidable_xmlfile' />";
        }

        $aHtml[] = "<a name = '" . $this->formid . "formidable_configuration' />";
        $aHtml[] = '<h5>FORM configuration</h5>';
        $aHtml[]
            = "<div WIDTH = '100%' style = 'HEIGHT: 400px; overflow: scroll'>" . tx_rnbase_util_Debug::viewArray($this->_aConf)
            . '</div>';
        $aHtml[] = "<p align = 'right'><a href = '#" . $this->formid . "formidable_debugtop' target = '_self'>^top^</a></p>";

        $aHtml[] = "<a name = '" . $this->formid . "formidable_callstack' />";
        $aHtml[] = '<h5>Call stack</h5>';
        $aHtml[] = implode('<hr>', $this->aDebug);
        $aHtml[] = "<p align = 'right'><a href = '#" . $this->formid . "formidable_debugtop' target = '_self'>^top^</a></p>";

        $aHtml[] = '</div>';

        return implode("\n", $aHtml);
    }

    /**
     * Internal debug function
     * Calls the TYPO3 debug function if the XML conf sets /formidable/meta/debug/ to TRUE
     *
     * @param    mixed   $variable       : the variable to dump
     * @param    string  $name           : title of this debug section
     * @param    string  $line           : PHP code line calling this function ( __LINE__ )
     * @param    string  $file           : PHP script calling this function ( __FILE__ )
     * @param    int $recursiveDepth : number of levels to debug, if recursive variable
     * @param    string  $debugLevel     : the sensibility of this warning
     *
     * @return    void
     */
    public function _debug($variable, $name, $bAnalyze = true)
    {
        tx_mkforms_util_Div::debug($msg, $name, $this, $bAnalyze);
    }

    /**
     * Stops Formidable and PHP execution : die() if some critical error appeared
     *
     * @param    string $msg : the error message
     *
     * @return    void
     */
    public function mayday($msg)
    {
        tx_mkforms_util_Div::mayday($msg, $this);
    }




    /*********************************
     *
     * Utilitary functions
     *
     *********************************/

    /**
     * Returns the form id
     *
     * @return string
     */
    public function getFormId()
    {
        return $this->formid;
    }

    /**
     * Callback function for preg_callback_replace
     *
     * Returns the translated string for the given {LLL} path
     *
     * @param    string $label : {LLL} path
     *
     * @return    string        The translated string
     */
    public function _getLLLabelTag($aLabel)
    {
        return $this->getConfig()->getLLLabel(
            str_replace(array('{', '}'), '', array_pop($aLabel))
        );
    }

    /**
     * Parses a template
     *
     * @param    string $templatePath   : the path to the template file
     * @param    string $templateMarker : the marker subpart
     * @param    array  $aTags          : array containing the values to render
     * @param    [type]        $aExclude: ...
     * @param    [type]        $bClearNotUsed: ...
     * @param    [type]        $aLabels: ...
     *
     * @return    string        HTML string with substituted values
     */
    public function _parseTemplate(
        $templatePath,
        $templateMarker,
        $aTags = array(),
        $aExclude = array(),
        $bClearNotUsed = true,
        $aLabels = array()
    ) {
        return $this->getTemplateTool()->parseTemplate(
            $templatePath,
            $templateMarker,
            $aTags,
            $aExclude,
            $bClearNotUsed,
            $aLabels
        );
    }

    public function _getParentExtSitePath()
    {
        if (TYPO3_MODE === 'FE') {
            $sExtKey = (is_subclass_of($this->_oParent, 'Tx_Rnbase_Frontend_Plugin')) ? $this->_oParent->extKey : 'mkforms';
        } else {
            $sExtKey = $GLOBALS['_EXTKEY'];
        }

        return Tx_Rnbase_Utility_T3General::getIndpEnv('TYPO3_SITE_URL') . tx_rnbase_util_Extensions::siteRelPath($sExtKey);
    }

    public function _substLLLInHtml($sHtml)
    {
        if ($sHtml{0} === 'L' && Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sHtml, 'LLL:')) {
            return $this->getConfigXML()->getLLLabel($sHtml);
        }

        return @preg_replace_callback(
            '/{LLL:[a-zA-Z0-9_:\/.\-]*}/',
            array($this, '_getLLLabelTag'),
            $sHtml
        );
    }

    public function prefixResourcePath($main_prefix, $content)
    {
        $this->makeHtmlParser();

        // fooling the htmlparser to avoid replacement of {tags} in template
        $content = str_replace('{', 'http://{', $content);
        $content = $this->oHtml->prefixResourcePath(
            $main_prefix,
            $content
        );

        return str_replace('http://{', '{', $content);
    }

    public function getAllHtmlTags($sTag, $sHtml)
    {
        $this->makeHtmlParser();
        $aParts = $this->oHtml->splitIntoBlock(
            $sTag,
            $sHtml
        );

        $iCount = count($aParts);
        for ($k = 0; $k < $iCount; $k += 2) {
            unset($aParts[$k]);
        }

        reset($aParts);

        return array_reverse(array_reverse($aParts));    // reordering keys
    }

    public function __catchEvalException($iErrno, $sMessage, $sFile, $iLine, $oObj)
    {
        $aErrors = array(
            E_ERROR => 'Error',
            E_PARSE => 'Parse error',
        );

        if ((error_reporting() & $iErrno) || array_key_exists($iErrno, $aErrors)) {
            ob_start();
            highlight_string($this->__sEvalTemp['code']);
            $sPhp = ob_get_contents();
            ob_end_clean();
            $sXml = tx_mkforms_util_Div::viewMixed($this->__sEvalTemp['xml']);

            $this->mayday(
                '<b>' . $aErrors[$iErrno] . '</b>: ' . $sMessage . ' in <b>' . $sFile . '</b> on line <b>' . $iLine
                . '</b><br /><hr />' . $sXml . '<hr/>' . $sPhp
            );
        }

        return true;
    }

    /**
     * Liefert die Parameter f�r ein XML-User-Object.
     *
     * @return array
     */
    public function getParams()
    {
        return $this->getRunnable()->getUserObjParams();
    }

    public function getPreviousParams()
    {
        return $this->getPreviousAjaxParams();
    }

    public function getListData($sKey = false)
    {
        return $this->oDataHandler->_getListData($sKey);
    }

    public function __getNeighbourInArray($iStart, $aData, $bCycle, $iDirection, $bKey = false)
    {
        if (!empty($aData)) {
            $aKeys = array_keys($aData);
            $iPos = array_search(
                $iStart,
                $aKeys
            );

            $iNeighbourPos = (int)$iPos + ($iDirection);

            if (array_key_exists($iNeighbourPos, $aKeys)) {
                if ($bKey !== false) {
                    return $aKeys[$iNeighbourPos];
                } else {
                    return $aData[$aKeys[$iNeighbourPos]];
                }
            } elseif ($bCycle) {
                if ($iDirection > 0) {
                    if ($bKey !== false) {
                        return $aKeys[0];
                    } else {
                        return $aData[$aKeys[0]];
                    }
                } else {
                    if ($bKey !== false) {
                        return $aKeys[(count($aKeys) - 1)];
                    } else {
                        return $aData[$aKeys[(count($aKeys) - 1)]];
                    }
                }
            }
        }

        return false;
    }

    public function _getNextInArray($iStart, $aData, $bCycle = false, $bKey = false)
    {
        return self::__getNeighbourInArray(
            $iStart,
            $aData,
            $bCycle,
            +1,
            $bKey
        );
    }

    public function _getPrevInArray($iStart, $aData, $bCycle = false, $bKey = false)
    {
        return self::__getNeighbourInArray(
            $iStart,
            $aData,
            $bCycle,
            -1,
            $bKey
        );
    }

    /**
     * TODO: remove
     */
    public function _isTrue($sPath, $aConf = -1)
    {
        return $this->_isTrueVal(
            $this->getConfig()->get(
                $sPath,
                $aConf
            )
        );
    }

    /**
     * TODO: remove
     */
    public function _isFalse($sPath, $aConf = -1)
    {
        $mValue = $this->getConfig()->get(
            $sPath,
            $aConf
        );

        if ($mValue !== false) {
            return $this->_isFalseVal($mValue);
        } else {
            return false;    // if not found in conf, the searched value is not FALSE, so _isFalse() returns FALSE !!!!
        }
    }

    /**
     * TODO: remove
     */
    public function _isTrueVal($mVal)
    {
        if ($this->getRunnable()->isRunnable($mVal)) {
            $mVal = $this->getRunnable()->callRunnable(
                $mVal
            );
        }

        return (($mVal === true) || ($mVal == '1') || (strtoupper($mVal) == 'TRUE'));
    }

    /**
     * TODO: remove
     */
    public function _isFalseVal($mVal)
    {
        if ($this->getRunnable()->isRunnable($mVal)) {
            $mVal = $this->getRunnable()->callRunnable(
                $mVal
            );
        }

        return (($mVal == false) || (strtoupper($mVal) == 'FALSE'));
    }

    public function _defaultTrue($sPath, $aConf = -1)
    {
        if ($this->getConfig()->get($sPath, $aConf) !== false) {
            return $this->_isTrue($sPath, $aConf);
        } else {
            return true;    // TRUE as a default
        }
    }

    public function _defaultFalse($sPath, $aConf = -1)
    {
        if ($this->getConfig()->get($sPath, $aConf) !== false) {
            return $this->_isTrue($sPath, $aConf);
        } else {
            return false;    // FALSE as a default
        }
    }

    public function _getExtRelPath($mInfos)
    {
        if (!is_array($mInfos)) {
            // should be object type

            if (isset($this)) {
                $aInfos = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ameos_formidable']['renderlets'][$sType];
            } else {
                $aInfos = self::_getInfosForType(
                    $mInfos,
                    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ameos_formidable']['renderlets']
                );
            }
        } else {
            $aInfos = $mInfos;
        }

        if ($aInfos['BASE'] === true) {
            return tx_rnbase_util_Extensions::siteRelPath('mkforms') . 'api/base/' . $aInfos['EXTKEY'] . '/';
        } else {
            return tx_rnbase_util_Extensions::siteRelPath($aInfos['EXTKEY']);
        }
    }

    public function _getExtPath($mInfos)
    {
        if (!is_array($mInfos)) {
            // should be object type

            if (isset($this)) {
                $aInfos = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ameos_formidable']['renderlets'][$sType];
            } else {
                $aInfos = self::_getInfosForType(
                    $mInfos,
                    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ameos_formidable']['renderlets']
                );
            }
        } else {
            $aInfos = $mInfos;
        }

        if ($aInfos['BASE'] === true) {
            return tx_rnbase_util_Extensions::extPath('mkforms') . 'api/base/' . $aInfos['EXTKEY'] . '/';
        } else {
            return tx_rnbase_util_Extensions::extPath($aInfos['EXTKEY']);
        }
    }

    public function _getCustomTags($aTags, $aUserobjParams = array())
    {
        $aCustomTags = array(
            'values' => array(),
            'labels' => array()
        );

        if (is_array($aTags)) {
            reset($aTags);
            while (list(, $aTag) = each($aTags)) {
                $label = array_key_exists('label', $aTag) ? $aTag['label'] : '';
                $name = $aTag['name'];
                $value = $aTag['value'];

                if ($this->getRunnable()->isRunnable($aTag['value'])) {
                    $value = $this->getRunnable()->callRunnable(
                        $aTag['value'],
                        $aUserobjParams
                    );
                }

                if ($value !== false) {
                    $aCustomTags['values'][$name] = $value;
                    $aCustomTags['labels'][$name] = $label;
                }
            }
        }

        reset($aCustomTags);

        return $aCustomTags;
    }

    public function injectData($sName, $mValue)
    {
        $this->mayday('injectData is disabled');
        $this->_aInjectedData[$sName] = $mValue;
    }

    public function unsetInjectedData($sName)
    {
        if (array_key_exists($sName, $this->_aInjectedData)) {
            unset($this->_aInjectedData[$sName]);
        }
    }

    public function renderList()
    {
        if (!$this->bRendered) {
            $this->mayday('ATTEMPT TO CALL renderlist() BEFORE CALL TO render()');
        }

        if ($this->oDataHandler->aObjectType['TYPE'] == 'LISTER') {
            return $this->oDataHandler->sHtmlList;
        }

        return '';
    }

    public function _getSafeLock($sStr = false)
    {
        if ($sStr === false) {
            $sStr = $this->getConfTS('misc.safelockseed');
        }

        return Tx_Rnbase_Utility_T3General::shortMD5(
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] . '||' . $sStr
        );
    }

    public function getSafeLock($sStr = false)
    {
        return $this->_getSafeLock($sStr);
    }

    /**
     * Das wird nirgendwo aufgerufen...
     */
    public function checkSafeLock($sStr = false, $sLock)
    {
        if ($sStr === false) {
            $sStr = $this->getConfTS('misc.safelockseed');
        }

        return (Tx_Rnbase_Utility_T3General::shortMD5($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] . '||' . $sStr) === $sLock);
    }

    public function _watchOutDB($rRes, $sSql = false)
    {
        if (!is_resource($rRes) && $GLOBALS['TYPO3_DB']->sql_error()) {
            $sMsg = 'SQL QUERY IS NOT VALID';
            $sMsg .= '<br/><br />';
            $sMsg .= '<b>' . $GLOBALS['TYPO3_DB']->sql_error() . '</b>';
            $sMsg .= '<br /><br />';

            if ($sSql !== false) {
                $sMsg .= $sSql;
            } else {
                $sMsg .= '<i style="margin-left: 20px;display: block;">' . nl2br($GLOBALS['TYPO3_DB']->debug_lastBuiltQuery)
                    . '</i>';
            }

            $this->mayday($sMsg);
        }

        return $rRes;
    }

    /**
     * alias for __sendMail
     */
    public function sendMail(
        $sAdresse,
        $sMessage,
        $sSubject,
        $sFromAd,
        $sFromName,
        $sReplyAd,
        $sReplyName,
        $aAttachPaths = array(),
        $iMediaRef = 0
    ) {
        if (is_object($this)) {
            return $this->__sendMail(
                $sAdresse,
                $sMessage,
                $sSubject,
                $sFromAd,
                $sFromName,
                $sReplyAd,
                $sReplyName,
                $aAttachPaths,
                $iMediaRef
            );
        } else {
            return self::__sendMail(
                $sAdresse,
                $sMessage,
                $sSubject,
                $sFromAd,
                $sFromName,
                $sReplyAd,
                $sReplyName,
                $aAttachPaths,
                $iMediaRef
            );
        }
    }

    public function __sendMail(
        $sAdresse,
        $sMessage,
        $sSubject,
        $sFromAd,
        $sFromName,
        $sReplyAd,
        $sReplyName,
        $aAttachPaths = array(),
        $iMediaRef = 0
    ) {
        $sDebugSendMail = trim($GLOBALS['TSFE']->tmpl->setup['config.']['tx_ameosformidable.']['debugSendMail']);

        if (is_object($this)) {
            if (($sXmlDebugSendMail = $this->getConfig()->get('/meta/debugsendmail')) !== false) {
                $sDebugSendMail = $sXmlDebugSendMail;
            }
        }

        if (trim($sDebugSendMail) !== '') {
            $sAdresseOld = $sAdresse;
            $sAdresse = $sDebugSendMail;
            $sMessage .= '<hr />Formidable /meta/debugSendMail: This mail would be sent to ' . $sAdresseOld;
        }

        $oMail = tx_rnbase::makeInstance(tx_rnbase_util_Typo3Classes::getMailMessageClass());

        $oMail->setSubject($sSubject);
        $oMail->setFrom($sFromAd, $sFromName);
        $oMail->setReplyTo($sReplyAd, $sReplyName);

        // HTML
        $oMail->setBody($sMessage);

        // SET Attachements

        if (is_array($aAttachPaths) && !empty($aAttachPaths)) {
            reset($aAttachPaths);
            while (list(, $sPath) = each($aAttachPaths)) {
                $sFilePath = Tx_Rnbase_Utility_T3General::fixWindowsFilePath($sPath);

                if (file_exists($sFilePath) && is_file($sFilePath) && is_readable($sFilePath)) {
                    $oMail->attach(Swift_Attachment::fromPath($sFilePath));
                }
            }
        }


        $oMail->setTo($sAdresse);
        $oMail->send();
    }

    public function _arrayToJs($sVarName, $aData, $bMultiLines = false)
    {
        // deprecated; use array2json instead
        $aJs = array();
        $aJs[] = 'var ' . $sVarName . ' = new Array();';

        reset($aData);
        while (list($sKey, $mVal) = each($aData)) {
            $aJs[] = $sVarName . '["' . $sKey . '"]=unescape(\"' . str_replace(
                array('%96', '%92'),
                array('', '\''),
                rawurlencode($mVal)
            ) . '");';
        }

        if ($bMultiLines) {
            return "\n" . implode("\n", $aJs) . "\n";
        } else {
            return implode('', $aJs);
        }
    }

    /**
     * @deprecated Methode entfernen!
     */
    public function arrayToRdtItems($aData, $sCaptionMap = false, $sValueMap = false)
    {
        // alias for _arrayToRdtItems()
        return $this->_arrayToRdtItems($aData, $sCaptionMap, $sValueMap);
    }

    /**
     * @deprecated Methode entfernen!
     */
    public function _arrayToRdtItems($aData, $sCaptionMap = false, $sValueMap = false)
    {
        tx_mkforms_util_Div::arrayToRdtItems($aData, $sCaptionMap, $sValueMap);
    }

    public function _rdtItemsToArray($aData)
    {
        $aArray = array();

        reset($aData);
        while (list($iKey, ) = each($aData)) {
            $aArray[$aData[$iKey]['value']] = $aData[$iKey]['caption'];
        }

        reset($aArray);

        return $aArray;
    }

    public function _arrayRowsToRdtItems($sCaptionKey, $sValueKey, $aData)
    {
        $aItems = array();

        reset($aData);
        while (list(, $aRow) = each($aData)) {
            $aItems[] = array(
                'value' => $aRow[$sValueKey],
                'caption' => $aRow[$sCaptionKey]
            );
        }

        reset($aItems);

        return $aItems;
    }

    public function _tcaToRdtItems($aItems)
    {
        reset($aItems);

        return $this->_arrayToRdtItems($aItems, '0', '1');
    }

    public function _parseTsInBE($iTemplateUid, $iPageId)
    {
        global $tmpl;

        $tmpl = tx_rnbase::makeInstance(tx_rnbase_util_Typo3Classes::getExtendedTypoScriptTemplateServiceClass());    // Defined global here!
        $tmpl->tt_track = 0;    // Do not log time-performance information
        $tmpl->init();

        // Gets the rootLine
        $sys_page = tx_rnbase::makeInstance(tx_rnbase_util_TYPO3::getSysPage());

        $tmpl->runThroughTemplates(
            $sys_page->getRootLine(
                $iPageId
            ),
            $iTemplateUid
        );

        // This generates the constants/config + hierarchy info for the template.
        $tmpl->generateConfig();

        $aConfig = $tmpl->setup['config.'];
        reset($aConfig);

        return $aConfig;
    }

    public function _perf()
    {
        return $this->end_tstamp - $this->start_tstamp;
    }

    public function array2json($aArray)
    {
        return tx_mkforms_util_Json::getInstance()->encode($aArray);
    }

    public function json2array($sJson)
    {
        return tx_mkforms_util_Json::getInstance()->decode($sJson);
    }

    public function array2tree($aArray, $bFirst = true)
    {
        $aNodes = array();
        while (list($sKey, $mVal) = each($aArray)) {
            if (is_array($mVal)) {
                $aNodes[] = array(
                    'label' => $sKey,
                    'nodes' => $this->array2tree($mVal, false),
                );
            } else {
                $sLabel = (trim($sKey) !== '') ? trim($sKey) . ': ' : '';

                $aNodes[] = array(
                    'label' => $sLabel . trim($mVal)    // avoiding null values
                );
            }
        }

        if ($bFirst && count(array_keys($aArray)) > 1) {
            return array(
                array(
                    'label' => 'Root',
                    'nodes' => $aNodes,
                ),
            );
        }

        return $aNodes;
    }

    public function _strToHtmlChar($sStr)
    {
        $sOut = '';
        $iLen = strlen($sStr);

        for ($a = 0; $a < $iLen; $a++) {
            $sOut .= '&#' . ord(substr($sStr, $a, 1)) . ';';
        }

        return $sOut;
    }

    /**
     * @TODO       : wirklich? Über additionalHeaderData wird unter anderem auch CSS eingefügt!
     * @deprecated use JSLoader
     */
    public function additionalHeaderData($sData, $sKey = false, $bFirstPos = false, $sBefore = false, $sAfter = false)
    {
        $this->getJSLoader()->additionalHeaderData($sData, $sKey, $bFirstPos, $sBefore, $sAfter);
    }

    public function getAdditionalHeaderData()
    {
        if (TYPO3_MODE === 'FE') {
            return $GLOBALS['TSFE']->additionalHeaderData;
        } else {
            return $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ameos_formidable']['context']['be_headerdata'];
        }
    }

    /**
     * @deprecated use $form->getJSLoader()->inline2TempFile()
     */
    public function inline2TempFile($str, $ext, $sDesc = '')
    {
        return $this->getJSLoader()->inline2TempFile($str, $ext, $sDesc);
    }

    public function issetAdditionalHeaderData($sKey)
    {
        if (TYPO3_MODE === 'FE') {
            return isset($GLOBALS['TSFE']->additionalHeaderData[$sKey]);
        } else {
            return isset($this->_oParent->doc->inDocStylesArray[$sKey]);
        }
    }

    public function cleanBeforeSession()
    {
        $this->getDataHandler()->cleanBeforeSession();
        $this->oRenderer->cleanBeforeSession();

        reset($this->aORenderlets);
        $aKeys = array_keys($this->aORenderlets);
        reset($aKeys);
        while (list(, $sKey) = each($aKeys)) {
            if (isset($this->aORenderlets[$sKey]) && !$this->aORenderlets[$sKey]->hasParent()) {
                $this->aORenderlets[$sKey]->cleanBeforeSession();
            }
        }

        reset($this->aODataSources);
        while (list($sKey, ) = each($this->aODataSources)) {
            $this->aODataSources[$sKey]->cleanBeforeSession();
        }

        $this->getRunnable()->cleanBeforeSession();

        unset($this->oSandBox->oForm);
        //den jsWrapper brauchen wir nicht. er enthält eine configuration und
        //die wiederrum enthält ein CObj. Wenn nun ameos_formidable
        //installiert ist, sind im CObj Hooks enthalten, die zu Fehlern beim
        //wiederherstellen der Form führen.
        $this->oJs->unsetForm();
        unset($this->_oParent);
        unset($this->oParent);
        unset($this->oMajixEvent);

        // wir müssen das config array mit cachen,
        // das ts lässt sich sonst nicht wieder herstellen
        $this->configurations = gzcompress(serialize($this->configurations->getConfigArray()), 1);

        $this->cObj = null;

        $this->oSandBox = serialize($this->oSandBox);
        $this->aDebug = array();
        $this->_aSubXmlCache = array();
        $this->aInitTasksUnobtrusive = array();
        $this->aInitTasks = array();
        $this->aInitTasksOutsideLoad = array();
        $this->aInitTasksAjax = array();
        $this->aPostInitTasks = array();
        $this->aPostInitTasksAjax = array();
        $this->aOnloadEvents = array(
            'client' => array(),
            'ajax' => array(),
        );
        $this->aCurrentRdtStack = array();
    }

    public function _clearFormInSession()
    {
        if (is_array($GLOBALS['_SESSION']['ameos_formidable']['hibernate'])
            && array_key_exists($this->formid, $GLOBALS['_SESSION']['ameos_formidable']['hibernate'])
        ) {
            unset($GLOBALS['_SESSION']['ameos_formidable']['hibernate'][$this->formid]);
        }
    }

    public function _getSessionDataHashKey()
    {
        return $this->_getSafeLock(
            $GLOBALS['TSFE']->id . '||' . $this->formid    // (unique but stable accross refreshes)
        );
    }

    /**
     * Das ist der Startaufruf eines Ajax-Requests. Der Aufruf erfolgt aus formidableajax.php
     *
     * @param formidableajax $oRequest : ...
     *
     * @return string
     */
    public function handleAjaxRequest(&$oRequest)
    {
        if ($oRequest->aRequest['servicekey'] == 'ajaxevent') {
            $this->oMajixEvent =& $oRequest;
            $oThrower =& $oRequest->getThrower();

            $sEventId = $oRequest->aRequest['eventid'];
            if (!array_key_exists($sEventId, $this->aAjaxEvents)) {
                $oRequest->denyService('Unknown Event ID ' . $sEventId);
            }

            if ($oThrower !== false && $this->_isTrueVal($this->aAjaxEvents[$sEventId]['event']['syncvalue'])) {
                // Das Element wird auf den aktuellen Wert gesetzt
                $oThrower->setValue($oRequest->aRequest['params']['sys_syncvalue']);
                unset($oRequest->aRequest['params']['sys_syncvalue']);
            }

            if ($this->getRunnable()->isRunnable($this->aAjaxEvents[$sEventId]['event'])) {
                // Hier geht es wohl um zusätzliche Parameter für den Event
                $iNbParams = 0;
                if ($oRequest->aRequest['trueargs'] !== false) {
                    $aArgs =& $oRequest->aRequest['trueargs'];
                    $iNbParams = count($aArgs);
                }

                if ($oThrower === false) {
                    $aArgs = array();
                    $aArgs[0] = $this->aAjaxEvents[$sEventId]['event']; // Wir ersetzen den ersten Parameter
                    $aArgs[1] = $oRequest->aRequest['params'];

                    return call_user_func_array(array($this->getRunnable(), 'callRunnable'), $aArgs);
                }

                // Hier ist kommt der Call von einem Widget in der Seite
                $oObject =& $oThrower;
                // logic: for back-compat, when trueargs is empty, we pass parameters as we always did
                // if trueargs set, we replicate arguments

                $aArgs = is_array($aArgs) ? $aArgs : array();
                if (!$iNbParams) {
                    array_unshift($aArgs, $oRequest->aRequest['params']);
                }
                array_unshift($aArgs, $oObject, $this->aAjaxEvents[$sEventId]['event']);

                return call_user_func_array(array($this->getRunnable(), 'callRunnableWidget'), $aArgs);
            }
        } elseif ($oRequest->aRequest['servicekey'] == 'ajaxservice') {
            $sServiceId = $oRequest->aRequest['serviceid'];
            if (!array_key_exists($sServiceId, $this->aAjaxServices)) {
                $oRequest->denyService('Unknown Service ID ' . $sServiceId);
            }

            if ($this->getRunnable()->isRunnable($this->aAjaxServices[$sServiceId]['definition'])) {
                if ($oRequest->aRequest['trueargs'] !== false) {
                    $aArgs =& $oRequest->aRequest['trueargs'];
                    $iNbParams = count($aArgs);
                } else {
                    $iNbParams = 0;
                }

                // if trueargs set, we replicate arguments
                switch ($iNbParams) {
                    case 0: {
                        $mRes = $this->getRunnable()->callRunnable($this->aAjaxServices[$sServiceId]['definition']);
                        break;
                    }
                    case 1: {
                        $mRes = $this->getRunnable()->callRunnable($this->aAjaxServices[$sServiceId]['definition'], $aArgs[0]);
                        break;
                    }
                    case 2: {
                        $mRes = $this->getRunnable()->callRunnable(
                            $this->aAjaxServices[$sServiceId]['definition'],
                            $aArgs[0],
                            $aArgs[1]
                        );
                        break;
                    }
                    case 3: {
                        $mRes = $this->getRunnable()->callRunnable(
                            $this->aAjaxServices[$sServiceId]['definition'],
                            $aArgs[0],
                            $aArgs[1],
                            $aArgs[2]
                        );
                        break;
                    }
                    case 4: {
                        $mRes = $this->getRunnable()->callRunnable(
                            $this->aAjaxServices[$sServiceId]['definition'],
                            $aArgs[0],
                            $aArgs[1],
                            $aArgs[2],
                            $aArgs[3]
                        );
                        break;
                    }
                    case 5: {
                        $mRes = $this->getRunnable()->callRunnable(
                            $this->aAjaxServices[$sServiceId]['definition'],
                            $aArgs[0],
                            $aArgs[1],
                            $aArgs[2],
                            $aArgs[3],
                            $aArgs[4]
                        );
                        break;
                    }
                    case 6: {
                        $mRes = $this->getRunnable()->callRunnable(
                            $this->aAjaxServices[$sServiceId]['definition'],
                            $aArgs[0],
                            $aArgs[1],
                            $aArgs[2],
                            $aArgs[3],
                            $aArgs[4],
                            $aArgs[5]
                        );
                        break;
                    }
                    case 7: {
                        $mRes = $this->getRunnable()->callRunnable(
                            $this->aAjaxServices[$sServiceId]['definition'],
                            $aArgs[0],
                            $aArgs[1],
                            $aArgs[2],
                            $aArgs[3],
                            $aArgs[4],
                            $aArgs[5],
                            $aArgs[6]
                        );
                        break;
                    }
                    case 8: {
                        $mRes = $this->getRunnable()->callRunnable(
                            $this->aAjaxServices[$sServiceId]['definition'],
                            $aArgs[0],
                            $aArgs[1],
                            $aArgs[2],
                            $aArgs[3],
                            $aArgs[4],
                            $aArgs[5],
                            $aArgs[6],
                            $aArgs[7]
                        );
                        break;
                    }
                    case 9: {
                        $mRes = $this->getRunnable()->callRunnable(
                            $this->aAjaxServices[$sServiceId]['definition'],
                            $aArgs[0],
                            $aArgs[1],
                            $aArgs[2],
                            $aArgs[3],
                            $aArgs[4],
                            $aArgs[5],
                            $aArgs[6],
                            $aArgs[7],
                            $aArgs[8]
                        );
                        break;
                    }
                    case 10: {
                        $mRes = $this->getRunnable()->callRunnable(
                            $this->aAjaxServices[$sServiceId]['definition'],
                            $aArgs[0],
                            $aArgs[1],
                            $aArgs[2],
                            $aArgs[3],
                            $aArgs[4],
                            $aArgs[5],
                            $aArgs[6],
                            $aArgs[7],
                            $aArgs[8],
                            $aArgs[9]
                        );
                        break;
                    }
                    default: {
                        $this->mayday('CallRunneable: can not declare more than 10 arguments.');
                        break;
                    }
                }
            }

            return $this->array2json($mRes);
        }
    }

    public function &getFromContext($sFormId)
    {
        $sExecMode = tx_mkforms_util_Div::getEnvExecMode();
        if ($sExecMode === 'EID') {
            // ajax context
            // getting form in session
            tx_rnbase::load('tx_mkforms_session_Factory');
            $sesMgr = tx_mkforms_session_Factory::getSessionManager();
            $form = $sesMgr->restoreForm($sFormId);

            return is_object($form) ? $form : false;
        }

        return false;
    }

    public function majixStatic($sMethod, $mData, $sFormId, $sElementId)
    {
        $aExecuter = $this->buildMajixExecuter(
            $sMethod,
            $mData,
            $sElementId
        );

        $aExecuter['formid'] = $sFormId;

        return $aExecuter;
    }

    /**
     * @param string $sJs
     * @param array $aParams
     *
     * @return array
     */
    public function majixExecJs($sJs, $aParams = array())
    {
        $aContext = array();

        $aListData = $this->oDataHandler->getListData();
        if (!empty($aListData)) {
            $aContext['currentrow'] = $aListData['uid'];
        }

        return $this->buildMajixExecuter(
            'execJs',
            $sJs,
            $this->formid,
            array(
                'context' => $aContext,
                'params' => $aParams
            )
        );
    }

    public function addMajixOnload($aMajixTasks)
    {
        $this->aOnloadEvents['client'][] = array(
            'name' => 'Event added by addMajixOnload()',
            'eventdata' => $aMajixTasks
        );
    }

    /**
     *
     * @param string $sMethod
     * @param mixed $mData
     * @param string $sElementId
     * @param array $mDataBag
     * @return array
     */
    public function buildMajixExecuter($sMethod, $mData, $sElementId, $mDataBag = array())
    {
        return array(
            'method' => $sMethod,
            'data' => $mData,
            'object' => $sElementId,
            'databag' => $mDataBag
        );
    }

    public function majixSubmit()
    {
        return $this->buildMajixExecuter(
            'submitFull',
            null,
            $this->formid
        );
    }

    public function majixSubmitRefresh()
    {
        return $this->majixRefresh();
    }

    public function majixSubmitSearch()
    {
        return $this->buildMajixExecuter(
            'submitSearch',
            null,
            $this->formid
        );
    }

    public function majixRefresh()
    {
        return $this->buildMajixExecuter(
            'submitRefresh',
            null,
            $this->formid
        );
    }

    public function majixScrollTo($sName)
    {
        return $this->buildMajixExecuter(
            'scrollTo',
            $sName,
            $this->formid
        );
    }

    public function majixSendToPage($sUrl)
    {
        return $this->buildMajixExecuter(
            'sendToPage',
            $sUrl,
            $this->formid
        );
    }

    public function majixForceDownload($sFilePath)
    {
        $sWebPath = (!tx_mkforms_util_Div::isAbsWebPath($sFilePath)) ? tx_mkforms_util_Div::toWebPath($sFilePath) : $sFilePath;

        $aParams = array();
        $aParams['url'] = $sWebPath;

        return $this->buildMajixExecuter(
            'openPopup',
            $aParams,
            $this->formid
        );
    }

    public function majixOpenPopup($mParams)
    {
        $aParams = array();

        if (is_string($mParams)) {
            $aParams['url'] = $mParams;
        } else {
            $aParams = $mParams;
        }

        return $this->buildMajixExecuter(
            'openPopup',
            $aParams,
            $this->formid
        );
    }

    public function majixDebug($sMessage)
    {
        return $this->buildMajixExecuter(
            'debug',
            tx_mkforms_util_Div::viewMixed($sMessage),
            $this->formid
        );
    }

    public function majixRequestNewI18n($sTableName, $iRecordUid, $iLangUid)
    {
        return $this->buildMajixExecuter(
            'requestNewI18n',
            array(
                'tablename' => $sTableName,
                'recorduid' => $iRecordUid,
                'languid' => $iLangUid,
                'hash' => $this->_getSafeLock('requestNewI18n' . ':' . $sTableName . ':' . $iRecordUid . ':' . $iLangUid),
            ),
            $this->formid
        );
    }

    public function majixRequestEdition($iRecordUid, $sTableName = false)
    {
        if ($sTableName === false) {
            $sTableName = $this->oDataHandler->tablename();
        }

        if ($sTableName !== false) {
            return $this->buildMajixExecuter(
                'requestEdition',
                array(
                    'tablename' => $sTableName,
                    'recorduid' => $iRecordUid,
                    'hash' => $this->_getSafeLock('requestEdition' . ':' . $sTableName . ':' . $iRecordUid),
                ),
                $this->formid
            );
        }
    }

    public function majixExecOnNextPage($aTask)
    {
        return $this->buildMajixExecuter(
            'execOnNextPage',
            $aTask,
            $this->formid
        );
    }

    public function majixGetLocalAnchor()
    {
        return $this->buildMajixExecuter(
            'getLocalAnchor',
            array(),
            'tx_ameosformidable'
        );
    }

    public function xhtmlUrl($sUrl)
    {
        return str_replace('&', '&amp;', $sUrl);
    }

    public function sendToPage($sUrl)
    {
        if (is_numeric($sUrl)) {
            $sUrl = tx_mkforms_util_Div::toWebPath(
                $this->getCObj()->typoLink_URL(
                    array(
                        'parameter' => $sUrl
                    )
                )
            );
        }

        header('Location: ' . $sUrl);
        die();
    }

    public function reloadCurrentUrl()
    {
        $this->sendToPage(
            Tx_Rnbase_Utility_T3General::getIndpEnv('TYPO3_REQUEST_URL')
        );
    }

    public function forceDownload($sAbsPath, $sFileName = false)
    {
        if ($sFileName === false) {
            $sFileName = basename($sAbsPath);
        }

        header('Expires: Mon, 01 Jul 1997 00:00:00 GMT'); // some day in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Content-type: application/x-download');
        header('Content-Disposition: attachment; filename=' . $sFileName);
        header('Content-Transfer-Encoding: binary');
        fpassthru(fopen($sAbsPath, 'r'));
        die();
    }

    public function isUserObj($mMixed)
    {
        return is_array($mMixed) && array_key_exists('userobj', $mMixed);
    }

    public function hasCodeBehind($mMixed)
    {
        return is_array($mMixed) && array_key_exists('exec', $mMixed);
    }

    public function isRunneable($mMixed)
    {
        return tx_mkforms_util_Runnable::isRunnable($mMixed);
    }

    /**
     * Ruf irgendwas auf...
     *
     * @deprecated
     * @todo remove!
     */
    public function callRunneable($mMixed)
    {
        return $this->getRunnable()->callRunnable($mMixed);
    }

    public function clearSearchForm($sSearchRdtName, $sFormId = false)
    {
        if ($sFormId === false) {
            $sFormId = $this->formid;
        }

        if ($sFormId === $this->formid) {
            if (array_key_exists($sSearchRdtName, $this->aORenderlets)) {
                $this->aORenderlets[$sSearchRdtName]->clearFilters();

                return;
            }
        }

        // else
        tx_mkforms_session_Factory::getSessionManager()->initialize();
        $GLOBALS['_SESSION']['ameos_formidable']['applicationdata']['rdt_lister'][$sFormId][$sSearchRdtName]['criterias']
            = array();
    }

    public function backendHeaders(&$oModule)
    {
        $oModule->content = str_replace(
            array(
                '<!--###POSTJSMARKER###-->',
                $oModule->doc->form,
            ),
            array(
                "<!-- FORMIDABLE JS FWK begin-->\n" . implode(
                    "\n",
                    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ameos_formidable']['context']['be_headerdata']
                ) . "\n<!-- FORMIDABLE JS FWK end-->\n<!--###POSTJSMARKER###-->",
                '<!-- DEFAULT FORM NEUTRALIZED BY FORMIDABLE ' . $oModule->doc->form . '-->',
            ),
            $oModule->content
        );
    }

    public function convertAccents($sStr)
    {
        return html_entity_decode(
            preg_replace(
                '/&([a-zA-Z])(uml|acute|grave|circ|tilde|slash|ring|elig|cedil);/',
                '$1',
                htmlentities($sStr, ENT_COMPAT, 'UTF-8')
            )
        );
    }

    public function removeNonAlnum($sStr)
    {
        // removes everything but a-z, A-Z, 0-9
        return ereg_replace(
            '[^<>[:alnum:]]',
            '',
            $sStr
        );
    }

    public function generatePassword($iLength = 6)
    {
        $aLetters = array(
            'cons' => 'aeiouy',
            'voy' => 'bcdfghjklmnpqrstvwxz',
        );

        $sPassword = '';
        $sType = 'cons';

        for ($k = 0; $k < $iLength; $k++) {
            $sType = ($sType === 'cons') ? 'voy' : 'cons';
            $iNbLetters = strlen($aLetters[$sType]);
            $sPassword .= $aLetters[$sType]{rand(0, ($iNbLetters - 1))};
        }

        return $sPassword;
    }

    public function isRenderlet(&$mObj)
    {
        if (is_object($mObj) && ($mObj instanceof formidable_mainrenderlet)) {
            return true;
        }

        return false;
    }

    /**
     * Wird beim Ajax-Call aufgerufen. Warum auch immer...
     *
     * @param array $aRequest
     */
    public function archiveAjaxRequest($aRequest)
    {
        array_push($this->aAjaxArchive, $aRequest);
    }

    public function getPreviousAjaxRequest()
    {
        if (!empty($this->aAjaxArchive)) {
            return $this->aAjaxArchive[(count($this->aAjaxArchive) - 1)];
        }

        return false;
    }

    public function getPreviousAjaxParams()
    {
        if (($aPrevRequest = $this->getPreviousAjaxRequest()) !== false) {
            return $aPrevRequest['params'];
        }

        return false;
    }

    public function div_autoLogin($iUserId)
    {
        if (tx_mkforms_util_Div::getEnvExecMode() === 'FE') {
            $rSql = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                '*',
                'fe_users',
                'uid=\'' . $iUserId . '\''
            );

            if (($aUser = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($rSql)) !== false) {
                $GLOBALS['TSFE']->fe_user->createUserSession($aUser);
                $GLOBALS['TSFE']->fe_user->loginSessionStarted = true;
                $GLOBALS['TSFE']->fe_user->user = $GLOBALS['TSFE']->fe_user->fetchUserSession();

                return true;
            }
        }

        return false;
    }

    public function wrapImplode($sWrap, $aData, $sGlue = '')
    {
        $aRes = array();
        reset($aData);
        while (list($iKey, ) = each($aData)) {
            if (is_string($aData[$iKey])) {
                $aRes[] = str_replace('|', $aData[$iKey], $sWrap);
            }
        }

        return implode($sGlue, $aRes);
    }

    public function div_rteToHtml($sRteHtml, $sTable = '', $sColumn = '')
    {
        $pageTSConfig = $GLOBALS['TSFE']->getPagesTSconfig();

        $aConfig = $pageTSConfig['RTE.']['default.']['FE.'];
        $aSpecConf['rte_transform']['parameters'] = array(
            'flag' => 'rte_enabled',
            'mode' => 'ts'
        );

        $aDataArray = array(
            $sColumn => $sRteHtml,
        );

        /**
 * @var $rte TYPO3\CMS\Backend\Rte\AbstractRte
*/
        $rte = tx_rnbase::makeInstance(
            tx_rnbase_util_Typo3Classes::getAbstractRteClass()
        );

        return $rte->transformContent(
            'rte',
            $sRteHtml,
            $sTable,
            $sColumn,
            $aDataArray,
            $aSpecConf,
            $aConfig,
            '',
            0
        );
    }

    public function devlog($sMessage, $iPad, $bCallStack = false)
    {
    }

    public function div_camelize($sString)
    {
        $aParts = explode('-', $sString);
        $iLen = count($aParts);
        if ($iLen == 1) {
            return $aParts[0];
        }

        if ($sString{0} === '-') {
            $sCamelized = strtoupper($aParts[0]{0}) . substr($aParts[0], 1);
        } else {
            $sCamelized = $aParts[0];
        }

        for ($i = 1; $i < $iLen; $i++) {
            $sCamelized .= strtoupper($aParts[$i]{0}) . substr($aParts[$i], 1);
        }

        return $sCamelized;
    }

    public function div_camelizeKeys($aData)
    {
        $aRes = array();
        reset($aData);
        while (list($sKey, ) = each($aData)) {
            $aRes[$this->div_camelize($sKey)] = $aData[$sKey];
        }

        reset($aRes);

        return $aRes;
    }

    public function div_arrayToCsvFile($aData, $sFilePath = false, $sFSep = ';', $sLSep = "\r\n", $sStringWrap = '"')
    {
        if ($sFilePath === false) {
            $sFilePath = Tx_Rnbase_Utility_T3General::tempnam('csv-' . strftime('%Y.%m.%d-%Hh%Mm%Ss' . '-')) . '.csv';
        } else {
            $sFilePath = tx_mkforms_util_Div::toServerPath($sFilePath);
        }

        tx_mkforms_util_Div::fileWriteBin(
            $sFilePath,
            self::div_arrayToCsvString(
                $aData,
                $sFSep,
                $sLSep,
                $sStringWrap
            ),
            false
        );

        return $sFilePath;
    }

    public function div_arrayToCsvString($aData, $sFSep = ';', $sLSep = "\r\n", $sStringWrap = '"')
    {
        // CSV class taken from http://snippets.dzone.com/posts/show/3128
        require_once(tx_rnbase_util_Extensions::extPath('mkforms') . 'res/shared/php/csv/class.csv.php');

        $oCsv = new CSV(
            $sFSep,
            $sLSep,
            $sStringWrap
        );
        $oCsv->setArray($aData);

        return $oCsv->getContent();
    }

    public function div_getHeadersForUrl($sUrl)
    {
        $aRes = array();

        if (($sHeaders = Tx_Rnbase_Utility_T3General::getUrl($sUrl, 2)) !== false) {
            $aHeaders = Tx_Rnbase_Utility_Strings::trimExplode("\n", $sHeaders);

            reset($aHeaders);
            while (list($sKey, $sLine) = each($aHeaders)) {
                if ($sKey === 0) {
                    $aRes['Status'] = $sLine;
                } else {
                    if (trim($sLine) !== '') {
                        $aHeaderLine = explode(':', $sLine);
                        $sHeaderKey = trim(array_shift($aHeaderLine));
                        $sHeaderVal = trim(implode(':', $aHeaderLine));

                        $aRes[$sHeaderKey] = $sHeaderVal;
                    }
                }
            }
        }

        reset($aRes);

        return $aRes;
    }

    public function &cb($sName)
    {
        if (array_key_exists($sName, $this->aCB)) {
            return $this->aCB[$sName];
        }

        return false;
    }

    /**
     * @return formidableajax|bool
     */
    public function getMajix()
    {
        return $this->oMajixEvent;
    }

    public function &getMajixSender()
    {
        return $this->getMajixThrower();
    }

    public function &getMajixThrower()
    {
        if ($this->oMajixEvent !== false) {
            return $this->oMajixEvent->getThrower();
        }

        return false;
    }

    public function pushCurrentRdt(&$oRdt)
    {
        $this->aCurrentRdtStack[] =& $oRdt;
    }

    public function &getCurrentRdt()
    {
        if (empty($this->aCurrentRdtStack)) {
            return false;
        }

        return $this->aCurrentRdtStack[(count($this->aCurrentRdtStack) - 1)];
    }

    public function &pullCurrentRdt()
    {
        if (empty($this->aCurrentRdtStack)) {
            return false;
        }

        return array_pop($this->aCurrentRdtStack);
    }

    public function isDomEventHandler($sHandler)
    {
        $aList = array(
            'onabort',        // Refers to the loading of an image that is interrupted.
            'onblur',            // Refers to an element losing the focus of the web browser.
            'onchange',        // Refers to a content is change, usually inside a text input box.
            'onclick',            // Refers to when an object is clicked.
            'ondblclick',        // Refers to when an object is double clicked.
            'onerror',            // Refers to when an error occurs.
            'onfocus',            // Refers to when an element is given focus.
            'onkeydown',        // Refers to when a keyboard key is pressed down.
            'onkeypress',        // Refers to when a keyboard key is pressed and/or held down.
            'onkeyup',            // Refers to when a keyboard key is released.
            'onload',            // Refers to when a web page or image loads.
            'onmousedown',        // Refers to when the mouse button is pressed down.
            'onmousemove',        // Refers to when the mouse is moved.
            'onmouseout',        // Refers to when the mouse is moved away from an element.
            'onmouseover',        // Refers to when the mouse moves over an element.
            'onmouseup',        // Refers to when the mouse button is released.
            'onreset',            // Refers to when a reset button is clicked.
            'onresize',        // Refers to when a window is resized.
            'onselect',        // Refers to when an element is selected.
            'onsubmit',        // Refers to when a submit button is clicked.
            'onunload',        // document is unloaded
            'oncut',            // something is cut
            'oncopy',            // something is copied
            'onpaste',            // something is pasted
            'onbeforecut',        // before something is cut
            'onbeforecopy',        // before something is copied
            'onbeforepaste',    // before something is pasted
        );

        return in_array(strtolower(trim($sHandler)), $aList);
    }

    // declare*() methods below are meant to smoothen transition between 0.7.x and 1.0/2.0+
    public function declareDataHandler()
    {
        if (TYPO3_MODE === 'BE' && tx_rnbase_util_Extensions::isLoaded('seminars')) {
            echo '<br /><br /><b>Warning</b>: you are using Formidable version <i>'
                . $GLOBALS['EM_CONF']['ameos_formidable']['version'] . '</i> with Seminars, requiring version 0.7.0.<br />';
        }
    }

    /**
     * Liefert eine Instanz von tx_mkforms_util_Templates
     *
     * @return tx_mkforms_util_Templates
     */
    public function getTemplateTool()
    {
        if (!$this->templateTool) {
            tx_rnbase::load('tx_mkforms_util_Templates');
            $this->templateTool = tx_mkforms_util_Templates::createInstance($this);
        }

        return $this->templateTool;
    }

    /**
     * Liefert eine Instanz von tx_mkforms_util_Validation
     *
     * @return tx_mkforms_util_Validation
     */
    public function getValidationTool()
    {
        if (!$this->validationTool) {
            tx_rnbase::load('tx_mkforms_util_Validation');
            $this->validationTool = tx_mkforms_util_Validation::createInstance($this);
        }

        return $this->validationTool;
    }

    //////////////////////////////////////////
    // Ab hier neue Methode aus mkameos
    //////////////////////////////////////////

    /**
     * Liefert die aktuelle EntryID. Der Aufruf ist hier etwas einfacher als über den DataHandler. Außerdem
     * wird im CreationMode automatisch die newEntryId geliefert.
     *
     * @return int
     */
    public function getEntryId()
    {
        $entryId = (int)$this->getDataHandler()->entryId;
        // Im CreationMode steht die EntryID in einer anderen Variablen
        $entryId = $entryId ? $entryId : (int)$this->getDataHandler()->newEntryId;

        return $entryId;
    }

    /**
     * Returns the current renderer
     *
     * @return formidable_mainrenderer
     */
    public function getRenderer()
    {
        return $this->oRenderer;
    }

    /**
     * Returns the current data handler
     *
     * @return formidable_maindatahandler
     */
    public function getDataHandler()
    {
        return $this->oDataHandler;
    }

    /**
     * Liefert die Namen der vorhandenen Renderlets
     *
     * @return array[string]
     */
    public function getWidgetNames()
    {
        return array_keys($this->aORenderlets);
    }

    /**
     * Liefert das gewünschte Widget.
     * Der Name kann dabei auch nicht qualifiziert angegeben werden. Es wird dann das erste Widget
     * mit dem entsprechenden Namen geliefert.
     *
     * @param string  $name
     * @param bool $qualified
     *
     * @return formidable_mainrenderlet
     */
    public function getWidget($name, $qualified = true)
    {
        if (array_key_exists($name, $this->aORenderlets)) {
            return $this->aORenderlets[$name];
        }
        if (!$qualified) {
            return false;
        }

        $aKeys = array_keys($this->aORenderlets);
        reset($aKeys);
        while (list(, $sKey) = each($aKeys)) {
            if ($this->aORenderlets[$sKey]->getName() === $name) {
                return $this->aORenderlets[$sKey];
            }
        }

        return false;
    }

    /**
     * @deprecated use getWidget()
     *
     * @param string $sName
     *
     * @return formidable_mainrenderlet
     */
    public function &rdt($sName)
    {
        return $this->getWidget($sName);
    }

    /**
     * Validierungsfehler per Javascript initialisieren
     *
     * @param array   $errors
     * @param string  $msgDiv
     * @param bool $remove
     */
    public function attachErrorsByJS($errors, $msgDiv, $remove = false)
    {
        if ($remove && empty($errors)) {
            $errors = array('noErrors' => true);
        }
        if (empty($errors)) {
            return;
        }

        $errs = tx_mkforms_util_Json::getInstance()->encode($errors);
        $script
            = '
            Formidable.f("' . $this->getFormId() . '").handleValidationErrors(' . $errs . ',"' . $msgDiv . '");
        ';
        $this->attachPostInitTask($script, 'error messages');
    }

    /**
     * Setzt alle vorhandenen Fehler zurück.
     */
    public function clearValidationErrors()
    {
        $this->_aValidationErrors = array();
        $this->_aValidationErrorsByHtmlId = array();
        $this->_aValidationErrorsInfos = array();
        $this->_aValidationErrorsTypes = array();
    }

    /**
     * Liefert die gewünschte DataSource. Wenn die DS nicht vorhanden ist, wird eine Exception geworfen.
     *
     * @param string $name
     *
     * @return formidable_maindatasource
     * @throws tx_mkforms_exception_DataSourceNotFound
     */
    public function &getDataSource($name)
    {
        if (!array_key_exists($name, $this->aODataSources)) {
            tx_rnbase::load('tx_mkforms_exception_DataSourceNotFound');
            throw new tx_mkforms_exception_DataSourceNotFound('Missing DS: ' . $name);
        }

        return $this->aODataSources[$name];
    }

    /**
     * Im HTTP-Request auch die GET-Daten auslesen. Dies wird benötigt, falls
     * Daten über den Pager angezeigt werden.
     *
     * @param bool $bUrlDecode
     */
    public function useGP($bUrlDecode = false)
    {
        $this->_useGP = true;
        $this->_useGPWithUrlDecode = $bUrlDecode;
    }

    /**
     * Gab es Validierungsfehle
     *
     * @return    bool
     */
    public function hasValidationErrors()
    {
        return count($this->_aValidationErrors) > 0;
    }

    /**
     * setzt die session id für den CSRF schutz
     * und dessen request token
     *
     * @param string $sSessionId
     */
    public function setSessionId($sSessionId)
    {
        $this->sSessionId = $sSessionId;
    }

    /**
     * gibt die gesetzte session id zurück.
     * sollte die session id des fe users sein.
     * wird für den request token im CSRF schutz verwendet
     *
     * @return string
     */
    public function getSessionId()
    {
        return $this->sSessionId;
    }

    /**
     * gibt einen token zurück, der aus der session id
     * und der form id generiert wird. wird verwendet
     * um einen CSRF Schutz zu implementieren.
     * Formulare können nur von dem Nutzer abgesendet werden
     * der sie erstellt hat
     *
     * @return string
     */
    public function generateRequestToken()
    {
        return $this->getSafeLock($this->getSessionId() . $this->getFormId());
    }

    /**
     * Prüft ob der übermittelte request token
     * valide ist. (zum aktuellen Nutzer passt)
     * wenn dieser nicht mit dem aktuellen token
     *
     * @return    bool
     */
    protected function validateRequestToken()
    {
        $aPost = $this->_getRawPost();

        return (array_key_exists('MKFORMS_REQUEST_TOKEN', $aPost)
            && $aPost['MKFORMS_REQUEST_TOKEN'] == $this->getRequestTokenFromSession());
    }

    /**
     * leifert den in der Session gespeicherten request token.
     * also der, der erwartet wird
     *
     * @return string
     */
    protected function getRequestTokenFromSession()
    {
        $aSessionData = $GLOBALS['TSFE']->fe_user->getKey('ses', 'mkforms');

        return $aSessionData['requestToken'][$this->getFormId()];
    }

    /**
     * @return int
     */
    public function getCreationTimestamp()
    {
        $sessionData = $GLOBALS['TSFE']->fe_user->getKey('ses', 'mkforms');

        return $sessionData['creationTimestamp'][$this->getFormId()];
    }

    /**
     * Liefert die Methode, mit der das Formular abgesendetw erden soll
     *
     * @return string GET or POST
     */
    public function getFormMethod()
    {
        $method = strtoupper(trim($this->getConfigXML()->get('/meta/form/method')));

        return $method === tx_mkforms_util_Constants::FORM_METHOD_GET ? tx_mkforms_util_Constants::FORM_METHOD_GET : tx_mkforms_util_Constants::FORM_METHOD_POST;
    }

    /**
     * Liefert den content type, mit der das Formular abgesendet werden soll
     *
     * @return string
     */
    public function getFormEnctype()
    {
        $enctype = trim($this->getConfigXML()->get('/meta/form/enctype'));
        switch ($enctype) {
            case tx_mkforms_util_Constants::FORM_ENCTYPE_APPLICATION_WWW_FORM_URLENCODED:
                return tx_mkforms_util_Constants::FORM_ENCTYPE_APPLICATION_WWW_FORM_URLENCODED;
            case tx_mkforms_util_Constants::FORM_ENCTYPE_TEXT_PLAIN:
                return tx_mkforms_util_Constants::FORM_ENCTYPE_TEXT_PLAIN;
            case tx_mkforms_util_Constants::FORM_ENCTYPE_MULTIPART_FORM_DATA:
                return tx_mkforms_util_Constants::FORM_ENCTYPE_MULTIPART_FORM_DATA;
            default:
                return $this->getFormMethod() === tx_mkforms_util_Constants::FORM_METHOD_POST ? tx_mkforms_util_Constants::FORM_ENCTYPE_MULTIPART_FORM_DATA : tx_mkforms_util_Constants::FORM_ENCTYPE_APPLICATION_WWW_FORM_URLENCODED;
        }
    }

    /**
     * @return boolean
     */
    public function isCsrfProtectionActive()
    {
        $formAttributes = $this->getConfigXML()->get('/meta/form');
        switch (true) {
            // When the plugin is cached it makes no sense to generate a CSRF token.
            // Otherwise we would create a fe_user session which might for example
            // not be desired when using a proxy cache like varnish as a fe_typo_user
            // cookie would be created. Furthermore it would lead to exceptions after
            // the first submit for all users but the first one as the csrf token
            // submitted could never be correct.
            case !$this->getConfigurations()->isPluginUserInt():
                $csrfProtectionActive = false;
                break;
            case isset($formAttributes['csrfprotection']):
                $csrfProtectionActive = $formAttributes['csrfprotection'];
                break;
            default:
                $csrfProtectionActive = $this->getConfTS('csrfProtection');
                break;
        }

        return (boolean) $csrfProtectionActive;
    }

    /**
     * @param string $storeFormInSession
     */
    public function setStoreFormInSession($storeFormInSession = true)
    {
        $this->storeFormInSession = $storeFormInSession;

        if ($this->storeFormInSession && !$this->bTestMode) {
            tx_mkforms_session_Factory::getSessionManager()->initialize();
        }

        return $this;
    }
}

if (defined('TYPO3_MODE')
    && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/class.tx_ameosformidable.php']
) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/class.tx_ameosformidable.php']);
}
