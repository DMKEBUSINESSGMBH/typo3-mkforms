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
