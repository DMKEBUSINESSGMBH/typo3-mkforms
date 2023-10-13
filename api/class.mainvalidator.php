<?php

class formidable_mainvalidator extends formidable_mainobject
{
    public function _matchConditions($aConditions = false)
    {
        if (false === $aConditions) {
            $aConditions = $this->aElement;
        }

        return $this->oForm->getConfig()->matchConditions($aConditions);
    }

    /**
     * @param formidable_mainrenderlet $oRdt
     *
     * @return bool
     */
    public function validate(&$oRdt)
    {
        $mValue = $oRdt->getValue();
        if (is_array($mValue) && !$oRdt->bArrayValue) {
            // Bei Widgets aus dem Lister und Uploads ist der Wert ein Array
            foreach ($mValue as $key => $value) {
                $oRdt->setIteratingId($key);
                $this->validateWidget($oRdt, $value);
            }
            $oRdt->setIteratingId();
        } else {
            $this->validateWidget($oRdt, $mValue);
        }
    }

    /**
     * @param formidable_mainrenderlet $oRdt
     * @param mixed                    $mValue
     *
     * @return bool
     */
    public function validateWidget(&$oRdt, $mValue)
    {
        $sAbsName = $oRdt->getAbsName();
        $aKeys = array_keys($this->getConfigValue('/'));
        reset($aKeys);
        foreach ($aKeys as $sKey) {
            if ($oRdt->hasError()) {
                break;
            }
            if (!$this->canValidate($oRdt, $sKey, $mValue)) {
                continue;
            }

            /***********************************************************************
             *
             *    /required
             *
             ***********************************************************************/

            if ('r' === $sKey[0] && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($sKey, 'required')) {
                if ($this->_isEmpty($oRdt, $mValue)) {
                    if (false !== ($mMessage = $this->getConfigValue('/'.$sKey.'/message'))
                        && $this->oForm->isRunneable(
                            $mMessage
                        )
                    ) {
                        $mMessage = $oRdt->callRunneable($mMessage);
                    }

                    $this->getForm()->_declareValidationError(
                        $sAbsName,
                        'STANDARD:required',
                        $this->oForm->getConfigXML()->getLLLabel($mMessage)
                    );

                    break;
                }
            }

            /***********************************************************************
             *
             *    /authentified
             *
             ***********************************************************************/

            if ('a' === $sKey[0] && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($sKey, 'authentified')) {
                if (!$this->_isAuthentified()) {
                    $message = $this->oForm->getConfigXML()->getLLLabel($this->getConfigValue('/'.$sKey.'/message/'));
                    $this->oForm->_declareValidationError(
                        $sAbsName,
                        'STANDARD:authentified',
                        $message
                    );

                    break;
                }
            }

            /***********************************************************************
             *
             *    /maxsize
             *
             ***********************************************************************/

            if ((('m' === $sKey[0]) && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($sKey, 'maxsize')
                && !\Sys25\RnBase\Utility\Strings::isFirstPartOfStr(
                    $sKey,
                    'maxsizebychars'
                ))
            ) {
                $iMaxSize = (int) $this->getConfigValue('/'.$sKey.'/value/');

                if ($this->_isTooLong($mValue, $iMaxSize)) {
                    $message = $this->oForm->getConfigXML()->getLLLabel($this->getConfigValue('/'.$sKey.'/message/'));
                    $this->oForm->_declareValidationError(
                        $sAbsName,
                        'STANDARD:maxsize',
                        $message
                    );

                    break;
                }
            }

            /***********************************************************************
             *
             *    /maxsizebychars
             *
             ***********************************************************************/

            if ('m' === $sKey[0] && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($sKey, 'maxsizebychars')) {
                $iMaxSize = (int) $this->getConfigValue('/'.$sKey.'/value/');
                $sEncoding = $this->getConfigValue('/'.$sKey.'/encoding/');

                if ($this->_isTooLongByChars($mValue, $iMaxSize, $sEncoding)) {
                    $message = $this->oForm->getConfigXML()->getLLLabel($this->getConfigValue('/'.$sKey.'/message/'));
                    $this->oForm->_declareValidationError(
                        $sAbsName,
                        'STANDARD:maxsizebychars',
                        $message
                    );

                    break;
                }
            }

            /***********************************************************************
             *
             *    /onerdthasalvaue
             *
             ***********************************************************************/

            if ('o' === $sKey[0] && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($sKey, 'onerdthasavalue')) {
                $sRdt = $this->getConfigValue('/'.$sKey.'/rdt/');

                if ($this->_oneRdtHasAValue($mValue, $sRdt)) {
                    $message = $this->oForm->getConfigXML()->getLLLabel($this->getConfigValue('/'.$sKey.'/message/'));
                    $this->oForm->_declareValidationError(
                        $sAbsName,
                        'STANDARD:onerdthasalvaue',
                        $message
                    );

                    break;
                }
            }

            /***********************************************************************
             *
             *    /minsize
             *
             ***********************************************************************/

            if ('m' === $sKey[0] && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($sKey, 'minsize')) {
                $iMinSize = (int) $this->getConfigValue('/'.$sKey.'/value/');

                if ($this->_isTooSmall($mValue, $iMinSize)) {
                    $message = $this->oForm->getConfigXML()->getLLLabel($this->getConfigValue('/'.$sKey.'/message/'));
                    $this->oForm->_declareValidationError(
                        $sAbsName,
                        'STANDARD:minsize',
                        $message
                    );

                    break;
                }
            }

            /***********************************************************************
             *
             *    /size
             *
             ***********************************************************************/

            if ('s' === $sKey[0] && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($sKey, 'size')) {
                $iSize = (int) $this->getConfigValue('/'.$sKey.'/value/');

                if (!$this->_sizeIs($mValue, $iSize)) {
                    $message = $this->oForm->getConfigXML()->getLLLabel($this->getConfigValue('/'.$sKey.'/message/'));
                    $this->oForm->_declareValidationError(
                        $sAbsName,
                        'STANDARD:size',
                        $message
                    );

                    break;
                }
            }

            /***********************************************************************
             *
             *    /sameas
             *
             ***********************************************************************/

            if ('s' === $sKey[0] && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($sKey, 'sameas')) {
                $sameas = trim($this->getConfigValue('/'.$sKey.'/value/'));

                if (array_key_exists($sameas, $this->oForm->aORenderlets)) {
                    $samevalue = $this->oForm->aORenderlets[$sameas]->getValue();

                    if (!$this->_isSameAs($mValue, $samevalue)) {
                        $message = $this->oForm->getConfigXML()->getLLLabel($this->getConfigValue('/'.$sKey.'/message/'));
                        $this->oForm->_declareValidationError(
                            $sAbsName,
                            'STANDARD:sameas',
                            $message
                        );

                        break;
                    }
                }
            }

            /***********************************************************************
             *
             *    /email
             *
             ***********************************************************************/

            if ('e' === $sKey[0] && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($sKey, 'email')) {
                if (!$this->_isEmail($mValue)) {
                    $message = $this->oForm->getConfigXML()->getLLLabel($this->getConfigValue('/'.$sKey.'/message/'));
                    $this->oForm->_declareValidationError(
                        $sAbsName,
                        'STANDARD:email',
                        $message
                    );

                    break;
                }
            }

            /***********************************************************************
             *
             *    /userobj
             *
             * @deprecated; use custom instead
             *
             ***********************************************************************/

            if ('u' === $sKey[0] && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($sKey, 'userobj')) {
                $this->oForm->mayday(
                    'WIDGET ['.$oRdt->getName()
                    .'] <b>/validator:STANDARD/userobj is deprecated.</b> Use /validator:STANDARD/custom instead.'
                );
            }

            /***********************************************************************
             *
             *    /unique
             *
             ***********************************************************************/

            if ('u' === $sKey[0] && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($sKey, 'unique')) {
                // field value has to be unique in the database
                // checking this

                if (!$this->_isUnique($oRdt, $mValue)) {
                    $this->oForm->_declareValidationError(
                        $sAbsName,
                        'STANDARD:unique',
                        $this->oForm->getConfigXML()->getLLLabel($this->getConfigValue('/'.$sKey.'/message/'))
                    );

                    break;
                }
            }

            /***********************************************************************
             *
             *    /custom
             *
             ***********************************************************************/

            if ('c' === $sKey[0] && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($sKey, 'custom')) {
                $mCustom = $this->getConfigValue('/'.$sKey);
                if ($this->oForm->isRunneable($mCustom)) {
                    if (true !== ($mResult = $this->getForm()->getRunnable()->callRunnable(
                        $mCustom,
                        ['value' => $mValue, 'widget' => $oRdt]
                    ))
                    ) {
                        if (is_string($mResult)) {
                            $message = $this->getForm()->getConfigXML()->getLLLabel($mResult);
                        } else {
                            $message = $this->getForm()->getConfigXML()->getLLLabel($this->getConfigValue('/'.$sKey.'/message/'));
                        }

                        $this->oForm->_declareValidationError(
                            $sAbsName,
                            'STANDARD:custom',
                            $message
                        );

                        break;
                    }
                }
            }
        }
    }

    /**
     * Prüft, ob dependsOn gesetzt wurde.
     * Dependson enthält den Namen des Renderlets.
     * Es wird nur Validiert,
     * wenn dieses Renderlet existiert und false ist.
     *
     * @param formidable_mainrenderlet $oRdt
     * @param string                   $sKey
     * @param mixed                    $mValue
     *
     * @return bool
     */
    protected function canValidate(&$oRdt, $sKey, $mValue)
    {
        if (false !== ($mSkip = $this->_defaultFalse('/'.$sKey.'/skipifempty'))) {
            if ($this->_isEmpty($oRdt, $mValue)) {
                return false;
            }
        }
        if (false !== ($mSkipIf = $this->getConfigValue('/'.$sKey.'/skipif'))) {
            $mSkipIf = \Sys25\RnBase\Utility\Strings::trimExplode(',', $mSkipIf);
            if (in_array($mValue, $mSkipIf)) {
                return false;
            }
        }
        // Prüfen ob eine Validierung aufgrund des Dependson Flags statt finden soll
        if (!$this->checkDependsOn($oRdt, $sKey)) {
            return false;
        }

        return true;
    }

    /**
     * Prüft, ob dependsOn gesetzt wurde.
     * Dependson enthält den Namen des Renderlets.
     * Es wird nur Validiert,
     * wenn dieses Renderlet existiert und false ist.
     *
     * werden mehrere renderlets definiert (, getrent).
     * so müssen alle den dependsonif wert haben, damit nicht validiert wird.
     *
     * @param formidable_mainrenderlet $oRdt
     * @param string                   $sKey
     *
     * @return bool wahr, wenn validiert werden kann
     */
    protected function checkDependsOn(&$oRdt, $sKey)
    {
        // skip validation, if hidden because dependancy empty
        if ($this->_defaultFalse('/'.$sKey.'/onlyifisvisiblebydependancies')
            && !$oRdt->isVisibleBecauseDependancyEmpty()
        ) {
            return false;
        }
        if (false !== ($mDependsOn = $this->getConfigValue('/'.$sKey.'/dependson'))) {
            $mDependsOn = $this->getForm()->getRunnable()->callRunnable($mDependsOn);

            // Der Validator wird nur ausgeführt, wenn das Flag-Widget einen Wert hat.
            $widget = &$this->getForm()->getWidget($mDependsOn);

            if ($widget) {
                $negate = false;
                //@TODO: dependsonifnot integrieren
                if (false !== ($aDependsOnIf = $this->getConfigValue('/'.$sKey.'/dependsonif'))) {
                    $aDependsOnIf = $this->getForm()->getRunnable()->callRunnable($aDependsOnIf);
                    $aDependsOnIf = is_array($aDependsOnIf) ? $aDependsOnIf : \Sys25\RnBase\Utility\Strings::trimExplode(',', $aDependsOnIf, 1);
                    $negate = true;
                } else {
                    // default false values
                    $aDependsOnIf = ['', 0, '0', null, false, []];
                }

                // IteratingId bei einem Renderlet im Lister setzen.
                if ($widget->getParent()->iteratingChilds) {
                    $widget->setIteratingId($oRdt->getIteratingId());
                }

                $mValue = $widget->getValue();

                // die iterating Id wieder löschen!
                $widget->setIteratingId();

                //bei einem array von Werten (zb select mit multiple = 1)
                //prüfen wir ob einer der array Werte dem Wert in
                //dependsonif entspricht
                if (is_array($mValue)) {
                    $inArray = false;
                    foreach ($mValue as $mTempValue) {
                        if (in_array($mTempValue, $aDependsOnIf, $this->_defaultTrue('/'.$sKey.'/dependsonifstrict'))) {
                            $inArray = true; //treffer?
                            break;
                        }
                    }
                } else {
                    $inArray = in_array(
                        $mValue,
                        $aDependsOnIf,
                        $this->_defaultTrue('/'.$sKey.'/dependsonifstrict')
                    );
                }

                if (($inArray != $negate)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function _isEmpty(&$oRdt, $mValue)
    {
        return $oRdt->_emptyFormValue($mValue);
    }

    /**
     * Prüft die Länge von Strings auf Byte-Ebene
     * das bedeutet das Multi-Byte Zeichen nicht als 1 Zeichen gezählt werden.
     *
     * @param $mValue
     * @param $maxSize
     */
    public function _isTooLong($mValue, $maxSize)
    {
        if (is_array($mValue)) {
            return count($mValue) > $maxSize;
        }

        return strlen(trim($mValue)) > $maxSize;
    }

    /**
     * Prüft die Länge von Strings auf Zeichen-Ebene
     * das bedeutet das Multi-Byte Zeichen als 1 Zeichen gezählt werden.
     *
     * @author Hannes Bochmann <dev@dmk-business.de>
     *
     * @param mixed  $mValue
     * @param int    $iMaxSize
     * @param string $sEncoding
     */
    public function _isTooLongByChars($mValue, $iMaxSize, $sEncoding = 'utf8')
    {
        //zur Sicherheit weiterer Fallback :)
        $sEncoding = (empty($sEncoding)) ? 'utf8' : $sEncoding;

        if (is_array($mValue)) {
            return count($mValue) > $iMaxSize;
        }

        return mb_strlen(trim($mValue), $sEncoding) > $iMaxSize;
    }

    public function _isTooSmall($mValue, $minSize)
    {
        if (is_array($mValue)) {
            return count($mValue) < $minSize;
        }

        return strlen(trim($mValue)) < $minSize;
    }

    public function _sizeIs($mValue, $iSize)
    {
        if (is_array($mValue)) {
            return count($mValue) == (int) $iSize;
        }

        return strlen(trim($mValue)) == $iSize;
    }

    public function _isSameAs($mValue1, $mValue2)
    {
        return $mValue1 === $mValue2;
    }

    public function _isEmail($mValue)
    {
        return '' == trim($mValue) || \Sys25\RnBase\Utility\Strings::validEmail($mValue);
    }

    public function _isAuthentified()
    {
        return is_array(($aUser = $GLOBALS['TSFE']->fe_user->user)) && array_key_exists('uid', $aUser)
            && (int) $aUser['uid'] > 0;
    }

    /**
     * @param formidable_mainrenderlet $oRdt
     * @param mixed                    $mValue
     *
     * @return bool
     */
    public function _isUnique(&$oRdt, $mValue)
    {
        $sDeleted = '';

        if (false !== ($sTable = $this->getConfigValue('/unique/tablename'))) {
            if (false === ($sField = $this->getConfigValue('/unique/field'))) {
                $sField = $oRdt->getName();
            }

            $sKey = false;
        } else {
            if ($oRdt->hasDataBridge() && ('DB' === $oRdt->oDataBridge->oDataSource->_getType())) {
                $sKey = $oRdt->oDataBridge->oDataSource->sKey;
                $sTable = $oRdt->oDataBridge->oDataSource->sTable;
                $sField = $oRdt->dbridged_mapPath();
            } else {
                $aDhConf = $this->oForm->_navConf('/control/datahandler/');
                $sKey = $aDhConf['keyname'];
                $sTable = $aDhConf['tablename'];
                $sField = $oRdt->getName();
            }

            if (true === $this->_defaultFalse('/unique/deleted/')) {
                $sDeleted = ' AND deleted != 1';
            }
        }

        $mValue = addslashes($mValue);

        if ($oRdt->hasDataBridge()) {
            $oDset = $oRdt->dbridged_getCurrentDsetObject();
            if ($oDset->isAnchored()) {
                $sWhere = $sField." = '".$mValue."' AND ".$sKey." != '".$oDset->getKey()."'".$sDeleted;
            } else {
                $sWhere = $sField." = '".$mValue."'".$sDeleted;
            }
        } else {
            if ($this->oForm->oDataHandler->_edition()) {
                $sWhere
                    =
                    $sField." = '".$mValue."' AND ".$sKey." != '".$this->oForm->oDataHandler->_currentEntryId()."'"
                    .$sDeleted;
            } else {
                $sWhere = $sField." = '".$mValue."'".$sDeleted;
            }
        }

        $rs = \Sys25\RnBase\Database\Connection::getInstance()->doSelect(
            'count(*) as nbentries',
            $sTable,
            ['where' => $sWhere]
        );

        if ($rs[0]['nbentries'] > 0) {
            return false;
        }

        return true;
    }

    /**
     * Validiert das mindestens eines der beiden Elemente einen Wert hat (renderlet, welches in "rdt"
     * angegeben wurde oder selbst).
     *
     * @param array              $params
     * @param tx_ameosformidable $form
     *
     * @return bool
     */
    public function _oneRdtHasAValue($mValue, $sRdt)
    {
        $widget = &$this->getForm()->getWidget($sRdt);

        //abhähniges feld existiert, ist geklickt oder widget selbst ist nicht leer
        if (($widget && $widget->getValue()) || !empty($mValue)) {
            return false;
        } else {
            return true;
        }
    }
}
