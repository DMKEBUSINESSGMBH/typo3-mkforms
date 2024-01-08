<?php

class formidableajax
{
    /**
     * @var array
     */
    public $aRequest = [];
    public $aConf = false;
    public $aSession = [];
    public $aHibernation = [];
    /**
     * @var tx_ameosformidable
     */
    public $oForm;

    public function getRequestData()
    {
        return $this->aRequest;
    }

    /**
     * Validate access. PHP will die if access is not allowed.
     *
     * @param array $request
     */
    private function validateAccess($request)
    {
        // TODO: Das Formular muss für Ajax raus aus der Session!!
        if (!(array_key_exists('_SESSION', $GLOBALS) && array_key_exists('ameos_formidable', $GLOBALS['_SESSION']))) {
            $this->denyService('SESSION is not started !');

            return false;
        }
        if (!array_key_exists($this->aRequest['object'], $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['ajax_services'])) {
            $this->denyService('no object found: '.$this->aRequest['object']);
        }

        if (!array_key_exists($this->aRequest['servicekey'], $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['ajax_services'][$this->aRequest['object']])) {
            $this->denyService('no service key');
        }
        // requested service exists

        if (!is_array($GLOBALS['_SESSION']['ameos_formidable']['ajax_services'][$this->aRequest['object']][$this->aRequest['servicekey']])
            || !array_key_exists($this->aRequest['safelock'], $GLOBALS['_SESSION']['ameos_formidable']['ajax_services'][$this->aRequest['object']][$this->aRequest['servicekey']])
        ) {
            $this->denyService('no safelock');
        }
    }

    public function init()
    {
        $this->ttStart = microtime(true);
        $this->ttTimes = [];

        $this->aRequest = [
            'safelock' => Sys25\RnBase\Utility\T3General::_GP('safelock'),
            'object' => Sys25\RnBase\Utility\T3General::_GP('object'),
            'servicekey' => Sys25\RnBase\Utility\T3General::_GP('servicekey'),
            'eventid' => Sys25\RnBase\Utility\T3General::_GP('eventid'),
            'serviceid' => Sys25\RnBase\Utility\T3General::_GP('serviceid'),
            'value' => stripslashes(Sys25\RnBase\Utility\T3General::_GP('value')),
            'formid' => Sys25\RnBase\Utility\T3General::_GP('formid'),
            'thrower' => Sys25\RnBase\Utility\T3General::_GP('thrower'),
            'arguments' => Sys25\RnBase\Utility\T3General::_GP('arguments'),
            'trueargs' => Sys25\RnBase\Utility\T3General::_GP('trueargs'),
            'pageId' => (int) Sys25\RnBase\Utility\T3General::_GP('pageId'),
        ];

        $sesMgr = tx_mkforms_session_Factory::getSessionManager();
        $sesMgr->initialize();

        // TODO: es muss möglich sein freie PHP-Scripte per Ajax aufzurufen

        // valid session data
        $this->validateAccess($this->aRequest);

        // proceed then
        $this->aConf = &$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['ajax_services'][$this->aRequest['object']][$this->aRequest['servicekey']]['conf'];
        // Ein Array mit dem Key "requester"
        // Wird NIE verwenden...
        $this->aSession = &$GLOBALS['_SESSION']['ameos_formidable']['ajax_services'][$this->aRequest['object']][$this->aRequest['servicekey']][$this->aRequest['safelock']];

        $formid = $this->aRequest['formid'];

        // Hier wird ein Array mit verschiedenen Objekten und Daten aus der Session geladen.
        $aHibernation = &$GLOBALS['_SESSION']['ameos_formidable']['hibernate'][$formid];

        // Die TSFE muss vor dem Form wieder hergestellt werden, damit die LANG stimmt
        $this->initTSFE($formid, $sesMgr, $aHibernation, $this->aRequest['pageId']);

        // Das Formular aus der Session holen.
        $start = microtime(true);
        $this->oForm = $sesMgr->restoreForm($formid);
        $this->ttTimes['frest'] = microtime(true) - $start;
        if (!$this->oForm) {
            $this->denyService(
                'no hibernate; Check those things: Have you Cookies enabled? Does the caching configuration for mkforms exist?'.
                'Default caching through the database can be activated in the extension manager. Please refer to '.
                'EXT:mkforms/ext_localconf.php on how to configure caching with another backend.'
            );
        }

        $sesMgr->setForm($this->oForm);
        $formid = $this->oForm->getFormId();

        if ($this->aConf['initBEuser']) {
            $this->_initBeUser();
        }

        $start = microtime(true);
        $aRdtKeys = array_keys($this->oForm->aORenderlets);
        reset($aRdtKeys);
        foreach ($aRdtKeys as $sKey) {
            if (is_object($this->oForm->aORenderlets[$sKey])) {
                $this->oForm->aORenderlets[$sKey]->awakeInSession($this->oForm);
            }
        }
        $this->ttTimes['wgtrest'] = microtime(true) - $start;

        $start = microtime(true);
        reset($this->oForm->aODataSources);
        foreach ($this->oForm->aODataSources as $sKey => $notNeeded) {
            $this->oForm->aODataSources[$sKey]->awakeInSession($this->oForm);
        }
        $this->ttTimes['dsrest'] = microtime(true) - $start;

        $this->aRequest['params'] = $this->oForm->json2array($this->aRequest['value']);
        $this->aRequest['trueargs'] = $this->oForm->json2array($this->aRequest['trueargs']);

        $this->ttTimes['init'] = microtime(true) - $this->ttStart;

        return true;
    }

    /**
     * @param string                      $formid
     * @param tx_mkforms_session_IManager $sesMgr
     * @param array                       $aHibernation
     * @param int                         $pageId
     *
     * @todo no support for virtualizeFE option since typo3 9.5 right now. If no support is needed/added this method can be
     * removed when making mkforms compatible to TYPO3 11.
     */
    private function initTSFE($formid, $sesMgr, $aHibernation, int $pageId)
    {
        if ($this->aConf['virtualizeFE']) {
            // Hier wird eine TSFE erstellt. Das hängt vom jeweiligen Ajax-Call ab.
            $start = microtime(true);
            // Der sesMgr verwendet hier das FORM um die PID zu ermitteln
            $feConfig = $sesMgr->restoreFeConfig($formid, $pageId);
            $sesMgr->restoreFeSetup($formid, $pageId);
            // Das dauert hier echt lang. Ca. 70% der Init-Zeit
            $this->ttTimes['fecrest'] = microtime(true) - $start;
            $context = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TYPO3\CMS\Core\Context\Context::class);
            $context->setAspect(
                'language',
                new TYPO3\CMS\Core\Context\LanguageAspect(
                    $aHibernation['sys_language_uid'],
                    $aHibernation['sys_language_content'],
                    $context->getAspect('language')->getOverlayType(),
                    $context->getAspect('language')->getFallbackChain()
                )
            );

            $GLOBALS['TSFE']->config = $feConfig;
            $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'] = $aHibernation['sys_language_uid'];
            $GLOBALS['TSFE']->tmpl->setup['config.']['tx_ameosformidable.'] = $aHibernation['formidable_tsconfig'];

            $GLOBALS['TSFE']->config['config']['language'] = $aHibernation['lang'];
            $GLOBALS['TSFE']->id = $aHibernation['pageid'];
            $GLOBALS['TSFE']->spamProtectEmailAddresses = $aHibernation['spamProtectEmailAddresses'];
            $GLOBALS['TSFE']->config['config']['spamProtectEmailAddresses_atSubst'] = $aHibernation['spamProtectEmailAddresses_atSubst'];
            $GLOBALS['TSFE']->config['config']['spamProtectEmailAddresses_lastDotSubst'] = $aHibernation['spamProtectEmailAddresses_lastDotSubst'];
        }
    }

    public function handleRequest(): string
    {
        $this->oForm->aInitTasksAjax = [];
        $this->oForm->aPostInitTasksAjax = [];
        $this->oForm->aRdtEventsAjax = [];

        if ('ajaxservice' == $this->aRequest['servicekey']) {
            // Hier kommt direkt ein String
            $sJson = $this->getForm()->handleAjaxRequest($this);
        } else {
            // Hier kommt ein Array...
            if ('tx_ameosformidable' == $this->aRequest['object']) {
                $aData = $this->getForm()->handleAjaxRequest($this);
            } else {
                $thrower = $this->getWhoThrown();
                $widget = $this->getForm()->getWidget($thrower);
                if (!$widget) {
                    throw new Exception('Widget '.htmlspecialchars($thrower).' not found!');
                }
                $aData = $widget->handleAjaxRequest($this);
            }

            if (!is_array($aData)) {
                $aData = [];
            }

            $this->ttTimes['complete'] = (microtime(true) - $this->ttStart);

            // bei werten wie 1.59740447998E-5 wirft es sehr schnell JS Fehler!
            // Deswegen wandeln wie die erstmal in Strings um.
            $ttTimes = [];
            foreach ($this->ttTimes as $key => $time) {
                $ttTimes[$key] = (string) $time;
            }

            $sJson = tx_mkforms_util_Json::getInstance()->encode(
                [
                    'init' => $this->oForm->aInitTasksAjax,
                    'postinit' => $this->oForm->aPostInitTasksAjax,
                    'attachevents' => $this->oForm->aRdtEventsAjax,
                    // wenn die header als html (ajax damupload) ausgeliefert werden,
                    // machen die script tags das json kaputt, wir müssen diese also encoden.
                    // wir ersetzen nur die klammern
                    'attachheaders' => str_replace(
                        ['<', '>'],
                        ['%3C', '%3E'],
                        $this->oForm->getJSLoader()->getAjaxHeaders()
                    ),
                    'tasks' => $aData,
                    'time' => $ttTimes,
                ]
            );
        }

        $this->archiveRequest($this->aRequest);

        if (false === ($sCharset = $this->oForm->_navConf('charset', $this->oForm->aAjaxEvents[$this->aRequest['eventid']]['event'] ?? []))) {
            if (false === ($sCharset = $this->oForm->_navConf('/meta/ajaxcharset'))) {
                $sCharset = 'UTF-8';
            }
        }

        $sesMgr = tx_mkforms_session_Factory::getSessionManager();
        $sesMgr->persistForm(true);

        return $sJson;
    }

    /**
     * @return tx_ameosformidable
     */
    public function getForm()
    {
        return $this->oForm;
    }

    /**
     * Die Methode wird noch in ameos_formidable::handleAjaxRequest aufgerufen.
     *
     * @param string $sMessage
     */
    public function denyService($sMessage)
    {
        header('Content-Type: text/plain; charset=UTF-8');
        exit('{/* SERVICE DENIED: '.$sMessage.' */}');
    }

    /**
     * @return Exception|object|string
     *
     * @todo no support since typo3 9.5 right now. If no support is needed/added this method can be
     * removed when making mkforms compatible to TYPO3 11.
     */
    public function _initBeUser()
    {
        global $BE_USER, $_COOKIE;

        $TSFE = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            Sys25\RnBase\Utility\Typo3Classes::getTypoScriptFrontendControllerClass(),
            $GLOBALS['TYPO3_CONF_VARS'],
            0,
            0
        );
        $TSFE->connectToDB();

        // *********
        // BE_USER
        // *********
        $BE_USER = '';
        if ($_COOKIE['be_typo_user']) {        // If the backend cookie is set, we proceed and checks if a backend user is logged in.
            // the value this->formfield_status is set to empty in order to disable login-attempts to the backend account through this script
            $BE_USER = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(Sys25\RnBase\Utility\Typo3Classes::getFrontendBackendUserAuthenticationClass());    // New backend user object
            $BE_USER->lockIP = $GLOBALS['TYPO3_CONF_VARS']['BE']['lockIP'];
            $BE_USER->start();            // Object is initialized
            $BE_USER->unpack_uc('');
            if ($BE_USER->user['uid']) {
                $BE_USER->fetchGroupData();
                $TSFE->beUserLogin = 1;
            }
            if ($BE_USER->checkLockToIP() && $BE_USER->checkBackendAccessSettingsFromInitPhp()) {
                $BE_USER->extInitFeAdmin();
                if ($BE_USER->extAdmEnabled) {
                    $LANG = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('language');
                    $LANG->init($BE_USER->uc['lang']);

                    $BE_USER->extSaveFeAdminConfig();
                    // Setting some values based on the admin panel
                    $TSFE->forceTemplateParsing = $BE_USER->extGetFeAdminValue('tsdebug', 'forceTemplateParsing');
                    $TSFE->displayEditIcons = $BE_USER->extGetFeAdminValue('edit', 'displayIcons');
                    $TSFE->displayFieldEditIcons = $BE_USER->extGetFeAdminValue('edit', 'displayFieldIcons');

                    if (Sys25\RnBase\Utility\T3General::_GP('ADMCMD_editIcons')) {
                        $TSFE->displayFieldEditIcons = 1;
                        $BE_USER->uc['TSFE_adminConfig']['edit_editNoPopup'] = 1;
                    }
                    if (Sys25\RnBase\Utility\T3General::_GP('ADMCMD_simUser')) {
                        $BE_USER->uc['TSFE_adminConfig']['preview_simulateUserGroup'] = (int) Sys25\RnBase\Utility\T3General::_GP('ADMCMD_simUser');
                        $BE_USER->ext_forcePreview = 1;
                    }
                    if (Sys25\RnBase\Utility\T3General::_GP('ADMCMD_simTime')) {
                        $BE_USER->uc['TSFE_adminConfig']['preview_simulateDate'] = (int) Sys25\RnBase\Utility\T3General::_GP('ADMCMD_simTime');
                        $BE_USER->ext_forcePreview = 1;
                    }

                    // Include classes for editing IF editing module in Admin Panel is open
                    if (($BE_USER->extAdmModuleEnabled('edit') && $BE_USER->extIsAdmMenuOpen('edit')) || 1 == $TSFE->displayEditIcons) {
                        $TSFE->includeTCA();
                        if ($BE_USER->extIsEditAction()) {
                            $BE_USER->extEditAction();
                        }
                        if ($BE_USER->extIsFormShown()) {
                        }
                    }

                    if ($TSFE->forceTemplateParsing || $TSFE->displayEditIcons || $TSFE->displayFieldEditIcons) {
                        $TSFE->set_no_cache();
                    }
                }
            } else {    // Unset the user initialization.
                $BE_USER = '';
                $TSFE->beUserLogin = 0;
            }
        } elseif ($TSFE->ADMCMD_preview_BEUSER_uid) {
            // the value this->formfield_status is set to empty in order to disable login-attempts to the backend account through this script
            $BE_USER = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(Sys25\RnBase\Utility\Typo3Classes::getFrontendBackendUserAuthenticationClass());    // New backend user object
            $BE_USER->userTS_dontGetCached = 1;
            $BE_USER->setBeUserByUid($TSFE->ADMCMD_preview_BEUSER_uid);
            $BE_USER->unpack_uc('');
            if ($BE_USER->user['uid']) {
                $BE_USER->fetchGroupData();
                $TSFE->beUserLogin = 1;
            } else {
                $BE_USER = '';
                $TSFE->beUserLogin = 0;
            }
        }

        return $BE_USER;
    }

    public function getWhoThrown()
    {
        $sThrower = $this->aRequest['thrower'];
        $aWho = explode(AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN, $sThrower);

        if (count($aWho) > 1) {
            array_shift($aWho);

            return implode(AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN, $aWho);
        }

        return false;
    }

    public function getThrower()
    {
        if (false !== ($sWho = $this->getWhoThrown())) {
            if (array_key_exists($sWho, $this->oForm->aORenderlets)) {
                return $this->oForm->aORenderlets[$sWho];
            }
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getParams()
    {
        return $this->aRequest['params'];
    }

    public function getParam($sParamName)
    {
        if (array_key_exists($sParamName, $this->aRequest['params'])) {
            return $this->aRequest['params'][$sParamName];
        }

        return false;
    }

    public function archiveRequest($aRequest)
    {
        $this->getForm()->archiveAjaxRequest($aRequest);
    }

    public function getPreviousRequest()
    {
        return $this->oForm->getPreviousAjaxRequest();
    }

    public function getPreviousParams()
    {
        return $this->oForm->getPreviousAjaxParams();
    }

    public function run(Psr\Http\Message\ServerRequestInterface $request): Psr\Http\Message\ResponseInterface
    {
        $json = '';
        try {
            if (false === $this->init()) {
                $this->denyService(); // Damit wird der Prozess beendet.
                exit;
            }

            return new TYPO3\CMS\Core\Http\HtmlResponse($this->handleRequest());
        } catch (Exception $e) {
            if (ini_get('display_errors')) {
                TYPO3\CMS\Core\Utility\DebugUtility::debug(
                    [
                        'da',
                        $e->getMessage(),
                        Sys25\RnBase\Utility\Logger::isWarningEnabled(),
                    ],
                    __METHOD__.' Zeile:'.__LINE__
                );
            }
            if (Sys25\RnBase\Utility\Logger::isWarningEnabled()) {
                $request = $this instanceof formidableajax ? $this->getRequestData() : 'unkown';
                $widgets = $this instanceof formidableajax && is_object($this->getForm()) ? $this->getForm()->getWidgetNames() : [];
                Sys25\RnBase\Utility\Logger::warn(
                    'Exception in ajax call',
                    'mkforms',
                    [
                        'Exception Message' => $e->getMessage(),
                        'Exception Trace' => $e->getTraceAsString(),
                        'Request' => $request,
                        'Widgets' => $widgets,
                    ]);
            }
        }

        return new TYPO3\CMS\Core\Http\NullResponse();
    }
}
