<?php

class formidable_mainrenderer extends formidable_mainobject
{
    public $aCustomHidden = null;

    public $bFormWrap = true;

    public $bValidation = true;

    /**
     * @var bool
     */
    public $bDisplayLabels = true;

    public function _init(&$oForm, $aElement, $aObjectType, $sXPath, $sNamePrefix = false)
    {
        parent::_init($oForm, $aElement, $aObjectType, $sXPath);

        $this->_setDisplayLabels(!$this->getForm()->_isFalse('/meta/displaylabels'));
        $this->_setFormWrap(!$this->getForm()->_isFalse('/meta/formwrap'));
    }

    public function _render($aRendered)
    {
        return $this->_wrapIntoForm(implode("<br />\n", $aRendered));
    }

    public function _wrapIntoDebugContainer($aHtmlBag, &$oRdt)
    {
        $sName = $oRdt->getAbsName();

        $sHtml
            = <<<TEMPLATE
                <div class="ameosformidable_debugcontainer_void">
                    <div style="pointer: help;" class="ameosformidable_debughandler_void">{$oRdt->aElement['type']}:{$sName}</div>
                    {$aHtmlBag['__compiled']}
                </div>
TEMPLATE;
        $aHtmlBag['__compiled'] = $sHtml;

        if (array_key_exists('input', $aHtmlBag)) {
            $sHtml
                = <<<TEMPLATE
                <div class="ameosformidable_debugcontainer_void">
                    <div style="pointer: help;" class="ameosformidable_debughandler_void">{$sName}.input</div>
                    {$aHtmlBag['input']}
                </div>
TEMPLATE;
            $aHtmlBag['input'] = $sHtml;
        }

        return $aHtmlBag;
    }

    public function _wrapIntoForm($html)
    {
        $oForm = &$this->getForm();
        $iFormId = $oForm->getFormId();

        if (!empty($this->getForm()->getDataHandler()->newEntryId)) {
            $iEntryId = $this->getForm()->getDataHandler()->newEntryId;
        } else {
            $iEntryId = $this->getForm()->getDataHandler()->_currentEntryId();
        }

        $hidden_entryid = $this->_getHiddenEntryId($iEntryId);
        $hidden_custom = $this->_getHiddenCustom();

        $sSysHidden = '<input type="hidden" name="'.$iFormId.'[AMEOSFORMIDABLE_SERVEREVENT]" id="'.$iFormId
            .'_AMEOSFORMIDABLE_SERVEREVENT" />'.'<input type="hidden" name="'.$iFormId
            .'[AMEOSFORMIDABLE_SERVEREVENT_PARAMS]" id="'.$iFormId.'_AMEOSFORMIDABLE_SERVEREVENT_PARAMS" />'
            .'<input type="hidden" name="'.$iFormId.'[AMEOSFORMIDABLE_SERVEREVENT_HASH]" id="'.$iFormId
            .'_AMEOSFORMIDABLE_SERVEREVENT_HASH" />'.'<input type="hidden" name="'.$iFormId
            .'[AMEOSFORMIDABLE_ADDPOSTVARS]" id="'.$iFormId.'_AMEOSFORMIDABLE_ADDPOSTVARS" />'
            .'<input type="hidden" name="'.$iFormId.'[AMEOSFORMIDABLE_VIEWSTATE]" id="'.$iFormId
            .'_AMEOSFORMIDABLE_VIEWSTATE" />'.'<input type="hidden" name="'.$iFormId.'[AMEOSFORMIDABLE_SUBMITTED]" id="'
            .$iFormId.'_AMEOSFORMIDABLE_SUBMITTED"  value="'.AMEOSFORMIDABLE_EVENT_SUBMIT_FULL.'"/>'
            .'<input type="hidden" name="'.$iFormId.'[AMEOSFORMIDABLE_SUBMITTER]" id="'.$iFormId
            .'_AMEOSFORMIDABLE_SUBMITTER" />';

        //CSRF Schutz integrieren
        if ($this->getForm()->isCsrfProtectionActive()) {
            $sRequestToken = $this->getForm()->generateRequestToken();
            $sSysHidden .= '<input type="hidden" name="'.$iFormId.'[MKFORMS_REQUEST_TOKEN]" id="'.$iFormId
                .'_MKFORMS_REQUEST_TOKEN" value="'.$sRequestToken.'" />';
            //der request token muss noch in die session damit wir ihn prüfen können.
            //mit bestehenden mergen
            $aSessionData = $GLOBALS['TSFE']->fe_user->getKey('ses', 'mkforms');
            $aSessionData['requestToken'][$iFormId] = $sRequestToken;
            $GLOBALS['TSFE']->fe_user->setKey('ses', 'mkforms', $aSessionData);
            $GLOBALS['TSFE']->fe_user->storeSessionData();
        }

        if (false !== ($sStepperId = $oForm->_getStepperId())) {
            $sSysHidden
                .=
                '<input type="hidden" name="AMEOSFORMIDABLE_STEP" id="AMEOSFORMIDABLE_STEP" value="'.$oForm->_getStep().'" />'
                .'<input type="hidden" name="AMEOSFORMIDABLE_STEP_HASH" id="AMEOSFORMIDABLE_STEP_HASH" value="'
                .$oForm->_getSafeLock($oForm->_getStep()).'" />';
        }

        if ($this->bFormWrap) {
            $formonsubmit = '';
            $formcustom = '';

            $formid = ' id="'.$iFormId.'" ';

            $formAction = $oForm->getFormAction();

            // @TODO: support für Codebehind implementieren!
            if (false !== ($sOnSubmit = $oForm->_navConf('/meta/form/onsubmit'))) {
                $formonsubmit = ' onSubmit="'.$sOnSubmit.'" ';
            }

            if (false !== ($sCustom = $oForm->_navConf('/meta/form/custom'))) {
                if ($oForm->isRunneable($sCustom)) {
                    $sCustom = $oForm->getRunnable()->callRunnableWidget($oForm, $sCustom);
                }
                $formcustom = ' '.$sCustom.' ';
            }

            if (false !== ($sClass = $oForm->_navConf('/meta/form/class'))) {
                $formcustom .= ' class="'.$sClass.'" ';
            }

            $wrapForm = ['', ''];
            if (false !== ($sWrap = $oForm->getConfigXML()->get('/meta/form/wrap'))) {
                $wrapForm = \Sys25\RnBase\Utility\T3General::trimExplode('|', $sWrap);
            }

            if (tx_mkforms_util_Constants::FORM_METHOD_GET === $oForm->getFormMethod()) {
                $sSysHidden .= $this->getHiddenFieldsForUrlParams($formAction);
            }

            $xssSafeUrl = htmlspecialchars($formAction);
            $formBegin
                = $wrapForm[0].'<form enctype="'.$oForm->getFormEnctype().'" '.' action="'.$xssSafeUrl
                .'" '.$formid.$formonsubmit.$formcustom.' method="'.$oForm->getFormMethod().'">';
            $formEnd = $hiddenFields.'</form>'.$wrapForm[1];
        } else {
            $formBegin = $formEnd = '';
        }

        $aHtmlBag = [
            'SCRIPT' => '',
            'FORMBEGIN' => $formBegin,
            'CONTENT' => $html,
            // in P for XHTML validation
            'HIDDEN' => '<p style="position:absolute; top:-5000px; left:-5000px;">'.$hidden_entryid.$hidden_custom.$sSysHidden
                .'</p>',
            'FORMEND' => $formEnd,
        ];

        reset($aHtmlBag);

        return $aHtmlBag;
    }

    /**
     * Erzeugt anhand von einer URL hidden Felder, welche mit übergeben werden.
     * Dabei werden die Get-Parameter aus der Action URL entfernt.
     * Das ist wichtig, wenn das Formular mit GET abgeschickt wird
     * und die Action URL bereits GET Paremeter enthält.
     * Die in der URL enthaltenen Parameter gehen verloren!
     *
     * @param string $url
     *
     * @return string
     */
    protected function getHiddenFieldsForUrlParams(&$url)
    {
        $formId = $this->getForm()->getFormId();
        $sysHidden = '';
        $params = [];

        if (false !== strpos($url, '?')) {
            $params = substr($url, strpos($url, '?') + 1);
            $params = \Sys25\RnBase\Utility\T3General::explodeUrl2Array($params);
            $url = substr($url, 0, strpos($url, '?'));
        }
        // alle Parameter als Hidden Felder bereitstellen
        foreach ($params as $name => $value) {
            // nur für die Parameter, die nicht zum Formular gehören
            // @TODO: die widgets prüfen, nicht nur den parametername!
            if (\Sys25\RnBase\Utility\T3General::isFirstPartOfStr($name, $formId.'[')) {
                continue;
            }
            $name = htmlspecialchars($name);
            $value = htmlspecialchars($value);
            $sysHidden .= '<input type="hidden" name="'.$name.'" value="'.$value.'" />';
        }

        return $sysHidden;
    }

    public function _getFullSubmitEvent()
    {
        return "Formidable.f('".$this->getForm()->getFormId()."').submitFull();";
    }

    public function _getRefreshSubmitEvent()
    {
        return "Formidable.f('".$this->getForm()->getFormId()."').submitRefresh();";
    }

    public function _getDraftSubmitEvent()
    {
        return "Formidable.f('".$this->getForm()->getFormId()."').submitDraft();";
    }

    public function _getTestSubmitEvent()
    {
        return "Formidable.f('".$this->getForm()->getFormId()."').submitTest();";
    }

    public function _getClearSubmitEvent()
    {
        return "Formidable.f('".$this->getForm()->getFormId()."').submitClear();";
    }

    public function _getSearchSubmitEvent()
    {
        return "Formidable.f('".$this->getForm()->getFormId()."').submitSearch();";
    }

    public function _getServerEvent($sRdtAbsName, $aEvent, $sEventId, $aData = [])
    {
        // $aData is typicaly the current row if in lister

        $sJsParam = 'false';
        $sHash = 'false';
        $aGrabbedParams = [];
        $aFullEvent = $this->getForm()->aServerEvents[$sEventId];

        if (true === $aFullEvent['earlybird']) {
            // registering absolute name,
            // this will help when early-processing the event
            $aGrabbedParams['_sys_earlybird'] = [
                'absname' => $aFullEvent['name'],
                'xpath' => tx_mkforms_util_Div::removeEndingSlash($this->getForm()->aORenderlets[$aFullEvent['name']]->sXPath).'/'
                    .$aFullEvent['trigger'],
            ];
        }

        reset($aFullEvent['params']);
        foreach ($aFullEvent['params'] as $sKey => $notNeeded) {
            $sParam = $aFullEvent['params'][$sKey]['get'];

            if (array_key_exists($sParam, $aData)) {
                $aGrabbedParams[$sParam] = $aData[$sParam];
            } else {
                $aGrabbedParams[] = $sParam;
            }
        }

        if (!empty($aGrabbedParams)) {
            $sJsParam = base64_encode(serialize($aGrabbedParams));
            $sHash = '\''.$this->getForm()->_getSafeLock($sJsParam).'\'';
            $sJsParam = '\''.$sJsParam.'\'';
        }

        $sConfirm = 'false';
        if (array_key_exists('confirm', $aEvent) && trim('' !== $aEvent['confirm'])) {
            // charset problem patched by Nikitim S.M
            // http://support.typo3.org/projects/formidable/m/typo3-project-formidable-russian-locals-doesnt-work-int-formidable-20238-i-wrote-the-solvation/p/15/

            $sConfirm = '\''.addslashes(
                $this->getForm()->getConfigXML()->getLLLabel(
                    $aEvent['confirm']
                )
            ).'\'';
        }

        if (false !== ($sSubmitMode = $this->getForm()->_navConf('submit', $aEvent))) {
            switch ($sSubmitMode) {
                case 'full':
                    return "Formidable.f('".$this->getForm()->getFormId()."').executeServerEvent('".$sEventId
                    ."', Formidable.SUBMIT_FULL, ".$sJsParam.', '.$sHash.', '.$sConfirm.');';
                    break;

                case 'refresh':
                    return "Formidable.f('".$this->getForm()->getFormId()."').executeServerEvent('".$sEventId
                    ."', Formidable.SUBMIT_REFRESH, ".$sJsParam.', '.$sHash.', '.$sConfirm.');';
                    break;

                case 'draft':
                    return "Formidable.f('".$this->getForm()->getFormId()."').executeServerEvent('".$sEventId
                    ."', Formidable.SUBMIT_DRAFT, ".$sJsParam.', '.$sHash.', '.$sConfirm.');';
                    break;

                case 'test':
                    return "Formidable.f('".$this->getForm()->getFormId()."').executeServerEvent('".$sEventId
                    ."', Formidable.SUBMIT_TEST, ".$sJsParam.', '.$sHash.', '.$sConfirm.');';
                    break;

                case 'search':
                    return "Formidable.f('".$this->getForm()->getFormId()."').executeServerEvent('".$sEventId
                    ."', Formidable.SUBMIT_SEARCH, ".$sJsParam.', '.$sHash.', '.$sConfirm.');';
                    break;
            }
        } else {
            // default: REFRESH
            return "Formidable.f('".$this->getForm()->getFormId()."').executeServerEvent('".$sEventId
            ."', Formidable.SUBMIT_REFRESH , ".$sJsParam.', '.$sHash.', '.$sConfirm.');';
        }
    }

    public function synthetizeAjaxEvent(
        &$oRdt,
        $sEventHandler,
        $sCb = false,
        $sPhp = false,
        $mParams = false,
        $bCache = true,
        $bSyncValue = false
    ) {
        $aEvent = [
            'runat' => 'ajax',
            'cache' => (int) $bCache,    // intval because FALSE would be bypassed by navconf
            'syncvalue' => (int) $bSyncValue,    // same reason
            'params' => $mParams,
        ];

        if (false !== $sCb) {
            $aEvent['exec'] = $sCb;
        } elseif (false !== $sPhp) {
            $aEvent['userobj']['php'] = $sPhp;
        }

        $sRdtAbsName = $oRdt->getAbsName();
        $sEventId = $this->getForm()->_getAjaxEventId(
            $sRdtAbsName,
            [$sEventHandler => $aEvent]
        );

        $this->getForm()->aAjaxEvents[$sEventId] = [
            'name' => $sRdtAbsName,
            'eventid' => $sEventId,
            'trigger' => $sEventHandler,
            'cache' => (int) $bCache,    // because FALSE would be bypassed by navconf
            'event' => $aEvent,
        ];

        return $this->_getAjaxEvent(
            $oRdt,
            $aEvent,
            $sEventHandler
        );
    }

    /**
     * Baut den JS-Aufruf für einen Ajax-Event zusammen.
     *
     * @param formidable_mainrenderlet $oRdt
     * @param array                    $aEvent
     * @param string                   $sEvent
     *
     * @return string
     */
    public function _getAjaxEvent(&$oRdt, $aEvent, $sEvent)
    {
        $sRdtName = $oRdt->getAbsName();

        $sEventId = $this->getForm()->_getAjaxEventId(
            $sRdtName,
            [$sEvent => $aEvent]
        );

        $sRdtId = $oRdt->_getElementHtmlId();
        $sHash = $oRdt->_getSessionDataHashKey();
        $bSyncValue = $this->getForm()->_defaultFalse('/syncvalue', $aEvent);
        $bCache = $this->getForm()->_defaultTrue('/cache', $aEvent);
        $bPersist = $this->getForm()->_defaultFalse('/persist', $aEvent);
        $sTrigerTinyMCE = $this->getForm()->_navConf('/trigertinymce', $aEvent);

        $sConfirm = 'false';
        if (array_key_exists('confirm', $aEvent) && trim('' !== $aEvent['confirm'])) {
            $sConfirm = '\''.addslashes(
                $this->getForm()->getConfigXML()->getLLLabel(
                    $aEvent['confirm']
                )
            ).'\'';
        }

        $aParams = [];
        $aParamsCollection = [];
        $aRowParams = [];

        if (false !== ($mParams = $this->getForm()->_navConf('/params', $aEvent))) {
            if (is_string($mParams)) {
                // Das ist der Normalfall. Die Parameter als String
                $aTemp = \Sys25\RnBase\Utility\T3General::trimExplode(',', $mParams);
                reset($aTemp);
                foreach ($aTemp as $sParam) {
                    $aParamsCollection[] = [
                        'get' => $sParam,
                        'as' => false,
                    ];
                }
            } else {
                // Anscheinend kann die Methode auch direkt mit einem Array aufgerufen werden...
                // Das könnte die neue Syntax zwei Zeilen weiter sein...
                $aParamsCollection = array_values($mParams);
            }

            // the new syntax
            // <params><param get="this()" as="this" /></params>

            reset($aParamsCollection);
            foreach ($aParamsCollection as $param) {
                // Hier werden die Parameter für einen Ajax-Request verarbeitet.

                $sParam = $param['get'];
                $sAs = $param['as'];
                if (\Sys25\RnBase\Utility\T3General::isFirstPartOfStr($sParam, 'rowData::')) {
                    $sParamName = substr($sParam, 9);
                    $aRowParams[$sParamName] = '';
                    if (false !== ($sValue = $this->getForm()->getDataHandler()->_getListData($sParamName))) {
                        $aRowParams[$sParamName] = $sValue;
                    }
                } elseif (\Sys25\RnBase\Utility\T3General::isFirstPartOfStr($sParam, 'rowInput::')) {
                    $sParamName = substr($sParam, 10);
                    /* replacing *id* with *id for this row*; will be handled by JS framework */

                    // note: _getAjaxEvent() is called when in rows rendering for list
                    // _getElementHtmlId() on a renderlet is designed to return the correct html id for the input of this row in such a case
                    $sAs = (false === $sAs) ? $sParamName : $sAs;

                    if (array_key_exists($sParamName, $this->getForm()->aORenderlets)) {
                        $aParams[] = 'rowInput::'.$sAs.'::'.$this->getForm()->getWidget($sParamName)->_getElementHtmlId();
                    }
                } elseif (\Sys25\RnBase\Utility\T3General::isFirstPartOfStr($sParam, 'sys_event.')) {
                    $aParams[] = $sParam;
                } elseif (array_key_exists($sParam, $this->getForm()->aORenderlets)) {
                    $sAs = (!$sAs) ? $sParam : $sAs;
                    $aParams[] = 'rowInput::'.$sAs.'::'.$this->getForm()->getWidget($sParam)->_getElementHtmlId();
                } elseif ('$this' === $sParam) {
                    $aParams[] = 'rowInput::this::'.$oRdt->getAbsName();
                } elseif (false !== strstr($sParam, AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN.'*')) {
                    // Shortcut um alle Werte einer Box übergeben
                    // Wir benötigen alle Renderlets mit einem bestimmten Prefix
                    $names = tx_mkforms_util_Div::findKeysWithPrefix(
                        array_flip($this->getForm()->getWidgetNames()),
                        substr($sParam, 0, strpos($sParam, '*'))
                    );
                    foreach ($names as $name) {
                        $widget = $this->getForm()->getWidget($name);
                        if ($widget
                            && $widget->hasData(/*$bForAjax*/
                                true
                            )
                        ) { // Nur Widget mit Daten übernehmen. (Keine Buttons usw.)
                            // Beim sammeln von Daten nie die IteratingId einbeziehen,
                            // wir wollen alle Daten, nicht nur von dem ersten Feld!
                            $sAs = $widget->getAbsName();
                            $aParams[] = 'rowInput::'.$sAs.'::'.$widget->_getElementHtmlId(/*def*/
                                    false, /*def*/
                                true, /*no iterating id*/
                                false
                            );
                        }
                    }
                } elseif (false !== strstr($sParam, '::')) {
                    // Ein freier Parameter
                    $aParams[] = $sParam;
                } else {
                    // $oRdt will be $mData in the majixmethods class
                    // Hier wird wohl nach einer Majix-Methode gesucht, die ein Widget zurückliefert
                    $mResult = $this->getForm()->resolveForMajixParams($sParam, $oRdt);
                    if ($this->getForm()->isRenderlet($mResult)) {
                        $sAs = $param['as'];
                        $aParams[] = 'rowInput::'.$sAs.'::'.$mResult->getAbsName();
                    } else {
                        debug($mResult, $sParam);
                    }
                }
            }
        }

        if (true === $bSyncValue) {
            // Im Lister müssen wir aufpassen. Da muss der Name des Zeilen-Widgets gesetzt werden!
            $aParams[] = 'rowInput::sys_syncvalue::'.$oRdt->getElementId(false);
        }

        $aAjaxEventParams = $oRdt->alterAjaxEventParams(
            [
                'eventname' => $sEvent,
                'eventid' => $sEventId,
                'hash' => $sHash,
                'cache' => $bCache,
                'persist' => $bPersist,
                'syncvalue' => $bSyncValue,
                'params' => $aParams,
                'row' => $aRowParams,
                'trigertinymce' => $sTrigerTinyMCE,
            ]
        );
        $sJsonParams = $this->getForm()->array2json($aAjaxEventParams['params']);
        $sJsonRowParams = $this->getForm()->array2json($aAjaxEventParams['row']);

        return "try{arguments;} catch(e) {arguments=[];} Formidable.f('".$this->getForm()->getFormId()."').executeAjaxEvent('"
        .$aAjaxEventParams['eventname']."', '".$sRdtId."', '".$aAjaxEventParams['eventid']."', '"
        .$aAjaxEventParams['hash']."', ".(($aAjaxEventParams['cache']) ? 'true' : 'false').', '
        .(($aAjaxEventParams['persist']) ? 'true' : 'false').', '.(($aAjaxEventParams['trigertinymce']) ?
            '"'.$aAjaxEventParams['trigertinymce'].'"' : 'false').', '.$sJsonParams.', '.$sJsonRowParams
        .', arguments, '.$sConfirm.');';
    }

    public function wrapEventsForInlineJs($aEvents)
    {
        $aJson = [];
        reset($aEvents);
        foreach ($aEvents as $sJs) {
            $aJson[] = rawurlencode($sJs);
        }

        return 'Formidable.executeInlineJs('.$this->getForm()->array2json($aJson).');';
    }

    public function _getClientEvent($sObjectId, $aEvent = [], $aEventData, $sEvent)
    {
        if (empty($aEventData)) {
            $aEventData = [];
        }

        $aParams = [];
        if (false !== ($mParams = $this->getForm()->_navConf('/params', $aEvent))) {
            if (!is_array($mParams)) {
                $aParams[] = $mParams;
            } else {
                foreach ($mParams as $key => $param) {
                    if (is_array($param)) {
                        // Ein freier Parameter
                        $aParams[$param['name']] = $this->getForm()->getConfigXML()->getLLLabel($param['value']);
                    } elseif (is_string($param)) {
                        $aParams[$key] = $this->getForm()->getConfigXML()->getLLLabel($param);
                    }
                }
            }
            $aEventData['data']['params'] = [$aParams];
        }

        $sData = $this->getForm()->array2json(
            [
                'init' => [],            // init and attachevents are here for majix-ajax compat
                'attachevents' => [],
                'tasks' => $aEventData,
            ]
        );

        $bPersist = $this->getForm()->_defaultFalse('/persist', $aEvent);

        $sConfirm = 'false';
        if (array_key_exists('confirm', $aEvent) && trim('' !== $aEvent['confirm'])) {
            $sConfirm = '\''.addslashes(
                $this->getForm()->getConfigXML()->getLLLabel(
                    $aEvent['confirm']
                )
            ).'\'';
        }

        return "Formidable.f('".$this->getForm()->getFormId()."').executeClientEvent('".$sObjectId."', ".(($bPersist) ? 'true' : 'false').", {$sData}, '".$sEvent."', arguments, ".$sConfirm.');';
    }

    public function _getHiddenEntryId($entryId)
    {
        if (!empty($entryId)) {
            return '<input type = "hidden" id="'.$this->_getHiddenHtmlId('AMEOSFORMIDABLE_ENTRYID').'" name="'
            .$this->_getHiddenHtmlName('AMEOSFORMIDABLE_ENTRYID').'" value="'.$entryId.'" />';
        }

        return '';
    }

    public function _getHiddenCustom()
    {
        if (is_array($this->aCustomHidden) && sizeof($this->aCustomHidden) > 0) {
            return implode('', $this->aCustomHidden);
        }

        return '';
    }

    public function _setHiddenCustom($name, $value)
    {
        if (!is_array($this->aCustomHidden)) {
            $this->aCustomHidden = [];
        }

        $this->aCustomHidden[$name]
            = '<input type="hidden" id="'.$this->_getHiddenHtmlId($name).'" name="'.$this->_getHiddenHtmlName($name)
            .'" value="'.$value.'" />';
    }

    public function _getHiddenHtmlName($sName)
    {
        return $this->getForm()->getFormId().'['.$sName.']';
    }

    public function _getHiddenHtmlId($sName)
    {
        return $this->getForm()->getFormId().'_'.$sName;
    }

    public function _setFormWrap($bWrap)
    {
        $this->bFormWrap = $bWrap;
    }

    public function _setValidation($bValidation)
    {
        $this->bValidation = $bValidation;
    }

    public function _getThisFormId()
    {
        return $this->getForm()->getFormId();
    }

    /**
     * @param bool $bDisplayLabels
     */
    public function _setDisplayLabels($bDisplayLabels)
    {
        $this->bDisplayLabels = $bDisplayLabels;
    }

    public function renderStyles()
    {
        if (false !== ($mStyle = $this->getConfigValue('/style'))) {
            $sUrl = false;
            $sStyle = false;

            if ($this->getForm()->isRunneable($mStyle)) {
                $sStyle = $this->callRunneable($mStyle);
            } elseif (is_array($mStyle) && array_key_exists('__value', $mStyle) && '' != trim($mStyle['__value'])) {
                $sStyle = $mStyle['__value'];
            } elseif (is_array($mStyle) && array_key_exists('url', $mStyle)) {
                if ($this->getForm()->isRunneable($mStyle['url'])) {
                    $sUrl = $this->callRunneable($mStyle['url']);
                } else {
                    $sUrl = $mStyle['url'];
                }

                if (true === $this->_defaultFalse('/style/rewrite')) {
                    if (!tx_mkforms_util_Div::isAbsWebPath($sUrl)) {
                        $sUrl = tx_mkforms_util_Div::toServerPath($sUrl);
                        $sStyle = \Sys25\RnBase\Utility\T3General::getUrl($sUrl);
                        $sUrl = false;
                    }
                }
            } elseif (is_string($mStyle)) {
                $sStyle = $mStyle;
            }

            if (false !== $sStyle) {
                reset($this->getForm()->aORenderlets);
                foreach ($this->getForm()->aORenderlets as $sName => $notNeeded) {
                    $oRdt = &$this->getForm()->aORenderlets[$sName];
                    $sStyle = str_replace(
                        [
                            '#'.$sName,
                            '{PARENTPATH}',
                        ],
                        [
                            '#'.$oRdt->_getElementCssId(),
                            $this->getForm()->_getParentExtSitePath(),
                        ],
                        $sStyle
                    );
                }

                $this->getForm()->additionalHeaderData(
                    $this->getForm()->inline2TempFile(
                        $sStyle,
                        'css',
                        "Form '".$this->getForm()->getFormId()."' styles"
                    )
                );
            }

            if (false !== $sUrl) {
                $sUrl = tx_mkforms_util_Div::toWebPath($sUrl);
                $this->getForm()->additionalHeaderData(
                    '<link rel="stylesheet" type="text/css" href="'.$sUrl.'" />'
                );
            }
        }
    }

    public function processHtmlBag($mHtml, &$oRdt)
    {
        $sLabel = $oRdt->getLabel();

        if (is_string($mHtml)) {        // can be empty with empty readonly
            $mHtml = [
                '__compiled' => $mHtml,
            ];
        }

        if (!empty($mHtml) && array_key_exists('__compiled', $mHtml) && is_string($mHtml['__compiled'])) {
            if (false !== ($mWrap = $oRdt->_navConf('/wrap'))) {
                if ($this->getForm()->isRunneable($mWrap)) {
                    $mWrap = $this->callRunneable($mWrap);
                }

                $mWrap = $this->getForm()->_substLLLInHtml($mWrap);

                $mHtml['__compiled'] = str_replace('|', $mHtml['__compiled'], $mWrap);
                // wrap added for f.schossig	2006/08/29
            }

            if (!array_key_exists('label', $mHtml)) {
                $mHtml['label'] = $sLabel;
            }

            if (!array_key_exists('label.', $mHtml)) {
                $mHtml['label.'] = [];
            }

            if (!array_key_exists('tag', $mHtml['label.'])) {
                $mHtml['label.']['tag'] = $oRdt->getLabelTag($sLabel);
            }

            if (!array_key_exists('htmlname', $mHtml)) {
                $mHtml['htmlname'] = $oRdt->_getElementHtmlName();
            }

            if (!array_key_exists('htmlid', $mHtml)) {
                $mHtml['htmlid'] = $oRdt->_getElementHtmlId();
            }

            if (!array_key_exists('htmlid.', $mHtml)) {
                $mHtml['htmlid.'] = [];
            }

            if (!array_key_exists('withoutformid', $mHtml['htmlid.'])) {
                $mHtml['htmlid.']['withoutformid'] = $oRdt->_getElementHtmlIdWithoutFormId();
            }

            if (false !== ($aError = $oRdt->getError())) {
                $mHtml['error'] = $aError['message'];
                $mHtml['error.'] = tx_mkforms_util_Div::addDots($aError);
                $sClass = $mHtml['htmlid.']['withoutformid'];
                $mHtml['error.']['message.']['tag']
                    = '<span class="rdterror error '.$sClass.'" for="'.$mHtml['htmlid'].'">'.$aError['message']
                    .'</span>';
                $mHtml['error.']['class'] = 'hasError';
            }

            if ($oRdt->_readOnly() && !array_key_exists('readonly', $mHtml)) {
                $mHtml['readonly'] = true;
            }

            if (false !== $oRdt->_navConf('/recombine')) {
                $this->getForm()->mayday(
                    '['.$oRdt->getName().'] <b>/recombine is deprecated</b>. You should use template methods instead'
                );
            }

            if ($this->getForm()->bDebug && $oRdt->_debugable()) {
                $mHtml = $this->_wrapIntoDebugContainer(
                    $mHtml,
                    $oRdt
                );
            }
        } else {
            $mHtml = [];
        }

        reset($mHtml);

        return $mHtml;
    }

    public function displayOnlyIfJs($aRendered)
    {
        $aRdts = array_keys($this->getForm()->aORenderlets);
        reset($aRdts);
        foreach ($aRdts as $sRdt) {
            if (true === $this->getForm()->aORenderlets[$sRdt]->displayOnlyIfJs()) {
                $sJson = tx_mkforms_util_Json::getInstance()->encode($aRendered[$sRdt]['__compiled']);
                $sId = $this->getForm()->aORenderlets[$sRdt]->_getElementHtmlId().'_unobtrusive';
                $aRendered[$sRdt]['__compiled'] = '<span id="'.$sId.'"></span>';

                $this->getForm()->attachInitTaskUnobtrusive(
                    '
                        if($("'.$sId.'")) {$("'.$sId.'").innerHTML='.$sJson.';}
                    '
                );
            }
        }

        return $aRendered;
    }

    public function wrapErrorMessage($sMessage)
    {
        if ($this->isTrue('/template/errortagcompilednowrap')) {
            return $sMessage;
        }

        if (false !== ($sErrWrap = $this->getConfigValue('/template/errortagwrap'))) {
            if ($this->getForm()->isRunneable($sErrWrap)) {
                $sErrWrap = $this->callRunneable($sErrWrap);
            }

            $sErrWrap = $this->getForm()->_substLLLInHtml($sErrWrap);
        } else {
            $sErrWrap = '<span class="errors">|</span>';
        }

        return str_replace(
            '|',
            $sMessage,
            $sErrWrap
        );
    }

    public function compileErrorMessages($aMessages)
    {
        if ($this->defaultFalse('/template/errortagcompilednobr')) {
            $errorMessages = implode('', $aMessages);
        } else {
            $errorMessages = implode('<br />', $aMessages);
        }
        if ($errorMessages && false !== ($sErrContainerWrap = $this->getConfigValue('/template/errorcontainerwrap'))) {
            $errorMessages = str_replace('|', $errorMessages, $sErrContainerWrap);
        }

        return $errorMessages;
    }
}
