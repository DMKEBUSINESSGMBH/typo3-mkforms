<?php

require_once(tx_rnbase_util_Extensions::extPath('mkforms') . 'api/class.mainscriptingmethods.php');
tx_rnbase::load('tx_rnbase_cache_Manager');

class formidable_templatemethods extends formidable_mainscriptingmethods
{
    private static $cache = null;

    /**
     * @return tx_rnbase_cache_TYPO3Cache
     */
    private function getCache()
    {
        if (is_null(self::$cache)) {
            self::$cache = tx_rnbase_cache_Manager::getCache('mkforms_rdt_tmpl');
        }

        return self::$cache;
    }

    public function method_getFormId()
    {
        return $this->getForm()->getFormId();
    }

    public function method_rdt($mData, $aParams)
    {
        if (!is_string($aParams[0]) || $aParams[0] === AMEOSFORMIDABLE_LEXER_FAILED) {
            return AMEOSFORMIDABLE_LEXER_BREAKED;
        }

        if (($oRdt = $this->oForm->getRdtForTemplateMethod($mData)) !== false
            && array_key_exists($aParams[0], $oRdt->aChilds)
        ) {
            return $oRdt->aChilds[$aParams[0]];
        } elseif (($oRdt = $this->oForm->getWidget($aParams[0])) !== false) {
            return $this->getHtmlBag($oRdt);
        }

        return AMEOSFORMIDABLE_LEXER_BREAKED;
    }

    public function method_rdtValue($mData, $aParams)
    {
        if (!is_string($aParams[0]) || $aParams[0] === AMEOSFORMIDABLE_LEXER_FAILED) {
            return AMEOSFORMIDABLE_LEXER_BREAKED;
        }
        if (($oRdt = $this->oForm->getWidget($aParams[0])) !== false) {
            return $oRdt->getValue();
        }

        return AMEOSFORMIDABLE_LEXER_BREAKED;
    }

    /**
     * gets the html bag of a Widget, include caching for template methods
     *
     * @param formidable_mainrenderlet $oRdt
     *
     * @return array
     */
    private function getHtmlBag($oRdt)
    {
        $cache = $this->getCache();
        $cacheId = $oRdt->getAbsName() . 'htmlbag';

        if ($cache->has($cacheId)) {
            return $cache->get($cacheId);
        }
        $htmlBag = $this->oForm->oRenderer->processHtmlBag($oRdt->render(), $oRdt);
        $cache->set($cacheId, $htmlBag);

        return $htmlBag;
    }

    public function method_switch($mData, $aParams)
    {
        if ($mData === true) {
            return $aParams[0];
        } else {
            return $aParams[1];
        }
    }

    public function method_wrapInnerHTML($mData, $aParams)
    {
        $sTagName = $this->oForm->oHtml->getFirstTagName($mData, true);
        $sTag = $this->oForm->oHtml->getFirstTag($mData);
        $sInnerHtml = $this->oForm->oHtml->removeFirstAndLastTag($mData);
        $sInnerHtml = str_replace(
            '|',
            $sInnerHtml,
            $aParams[0]
        );

        return $sTag . $sInnerHtml . '</' . $sTagName . '>';
    }

    public function method_echo($mData, $aParams)
    {
        return $aParams[0];
    }

    public function method_equals($mData, $aParams)
    {
        if ($mData == $aParams[0]) {
            return true;
        }

        return false;
    }

    public function method_greaterThan($mData, $aParams)
    {
        if ($mData > $aParams[0]) {
            return true;
        }

        return false;
    }

    public function method_lessThan($mData, $aParams)
    {
        if ($mData < $aParams[0]) {
            return true;
        }

        return false;
    }

    protected function templateDataAsString($mData, $aParams = array())
    {
        return $this->getForm()->templateDataAsString($mData);
    }

    public function method_nl2br($mData, $aParams)
    {
        return nl2br($this->templateDataAsString($mData));
    }

    public function method_urlencode($mData, $aParams)
    {
        return urlencode($this->templateDataAsString($mData));
    }

    public function method_rawurlencode($mData, $aParams)
    {
        return rawurlencode($this->templateDataAsString($mData));
    }

    public function method_trim($mData, $aParams)
    {
        return trim($this->templateDataAsString($mData));
    }

    public function method_upper($mData, $aParams)
    {
        return strtoupper($this->templateDataAsString($mData));
    }

    public function method_lower($mData, $aParams)
    {
        return strtolower($this->templateDataAsString($mData));
    }

    public function method_ucfirst($mData, $aParams)
    {
        return ucfirst($this->templateDataAsString($mData));
    }

    public function method_ucwords($mData, $aParams)
    {
        return ucwords($this->templateDataAsString($mData));
    }

    public function method_debug($mData, $aParams)
    {
        if (is_array($mData)) {
            if (array_key_exists('help', $mData)) {
                unset($mData['help']);
            }

            reset($mData);
            foreach ($mData as $sKey => $notNeeded) {
                if (is_array($mData[$sKey]) && array_key_exists('__compiled', $mData[$sKey])) {
                    ksort($mData[$sKey]);
                }
            }
        }

        return tx_mkforms_util_Div::viewMixed($mData);
    }

    public function method_displayCond($mData, $aParams)
    {
        if ($this->templateDataAsString($mData) === '') {
            return 'display: none;';
        }

        return '';
    }

    public function method_concat($mData, $aParams)
    {
        return trim($this->templateDataAsString($mData) . implode(' ', $aParams));
    }

    public function method_wrap($mData, $aParams)
    {
        return str_replace(
            '|',
            trim($this->templateDataAsString($mData)),
            $aParams[0]
        );
    }

    public function method_toHex($mData, $aParams)
    {
        return implode(
            ':',
            explode(
                ' ',
                trim(
                    chunk_split(
                        strtoupper(
                            bin2hex($mData)
                        ),
                        2,
                        ' '
                    )
                )
            )
        );
    }

    public function method_extPath($mData, $aParams)
    {
        return tx_rnbase_util_Extensions::extPath($aParams[0]);
    }

    public function method_toWebPath($mData, $aParams)
    {
        return $this->oForm->toWebPath(trim($this->templateDataAsString($mData)));
    }

    public function method_getLLL($mData, $aParams)
    {
        return $this->method_getLLLabel($mData, $aParams);
    }

    public function method_getLLLabel($mData, $aParams)
    {
        return $this->oForm->getConfigXML()->getLLLabel(trim($this->templateDataAsString($aParams[0])));
    }

    public function method_getTs($mData, $aParams)
    {
        return $this->getForm()->getConfTS($aParams[0]);
    }

    public function method_extract($mData, $aParams)
    {
        $aRes = array();
        if (is_array($mData)) {
            reset($aParams);
            foreach ($aParams as $sKeyName) {
                if (array_key_exists($sKeyName, $mData)) {
                    $aRes[$sKeyName] = $mData[$sKeyName];
                }
            }
        }

        return $aRes;
    }

    public function method_implode($mData, $aParams)
    {
        //man kann nicht direkt ein komma angegeben da dieses in
        //mkforms_util_Templates rausgeparsed wird. wenn also nichts
        //gesetzt wird, dann nehmen wir das komma als connector
        if (!isset($aParams[0])) {
            $aParams[0] = ', ';
        }
        if (is_array($mData)) {
            return implode($aParams[0], $mData);
        }

        return '';
    }

    public function method_isTrue($mData, $aParams)
    {
        if ($mData === true) {
            return true;
        }

        return false;
    }

    public function method_isFalse($mData, $aParams)
    {
        if ($mData === false) {
            return true;
        }

        return false;
    }

    public function method_isNotTrue($mData, $aParams)
    {
        if ($mData !== true) {
            return true;
        }

        return false;
    }

    public function method_isNotFalse($mData, $aParams)
    {
        if ($mData !== false) {
            return true;
        }

        return false;
    }

    public function method_ifIsTrue($mData, $aParams)
    {
        if ($mData === true) {
            return true;
        }

        return AMEOSFORMIDABLE_LEXER_BREAKED;
    }

    public function method_ifIsFalse($mData, $aParams)
    {
        if ($mData === false) {
            return true;
        }

        return AMEOSFORMIDABLE_LEXER_BREAKED;
    }

    public function method_isLoggedIn($mData, $aParams)
    {
        if ($GLOBALS['TSFE']->fe_user->user['uid']) {
            return true;
        }

        return false;
    }

    public function method_strlen($mData, $aParams)
    {
        return strlen($mData);
    }

    public function method_substr($mData, $aParams)
    {
        return Tx_Rnbase_Utility_T3General::fixed_lgd_cs(
            $this->templateDataAsString($mData),
            $aParams[0],
            $aParams[1]
        );
    }

    public function method_fixed_lgd($mData, $aParams)
    {
        return $this->method_substr($mData, $aParams);
    }

    public function method_fixed_lgd_word($mData, $aParams)
    {
        $sStr = $this->templateDataAsString($mData);

        if (strlen($sStr) <= $aParams[0]) {
            return $sStr;
        }

        if (strlen($aParams[1]) > $aParams[0]) {
            return $aParams[1];
        }

        $sStr = substr($sStr, 0, $aParams[0] - strlen($aParams[1]) + 1);

        return substr($sStr, 0, strrpos($sStr, ' ')) . $aParams[1];
    }

    public function method_striptags($mData, $aParams)
    {
        return $this->method_strip_tags($mData, $aParams);
    }

    public function method_strip_tags($mData, $aParams)
    {
        return strip_tags($this->templateDataAsString($mData));
    }

    public function method_formData($mData, $aParams)
    {
        if (!empty($aParams)) {
            return $this->oForm->oDataHandler->getThisFormData($aParams[0]);
        } else {
            return $this->oForm->oDataHandler->getFormData();
        }
    }

    public function method_storedData($mData, $aParams)
    {
        if (!empty($aParams)) {
            return $this->oForm->oDataHandler->getStoredData($aParams[0]);
        } else {
            return $this->oForm->oDataHandler->getStoredData();
        }
    }

    public function method_listData($mData, $aParams)
    {
        return $this->method_rowData($mData, $aParams);
    }

    public function method_rowData($mData, $aParams)
    {
        if (!empty($aParams)) {
            return $this->oForm->oDataHandler->getListData($aParams[0]);
        }

        return $this->oForm->oDataHandler->getListData();
    }

    public function method_rteToHtml($mData, $aParams)
    {
        return $this->oForm->div_rteToHtml(
            $this->templateDataAsString($mData),
            '',
            ''
        );
    }

    public function method_debug_trail($mData, $aParams)
    {
        tx_rnbase::load('tx_rnbase_util_Debug');

        return tx_rnbase_util_Debug::getDebugTrail();
    }

    public function method_hsc($mData, $aParams)
    {
        return htmlspecialchars($this->templateDataAsString($mData));
    }

    public function method_htmlentities($mData, $aParams)
    {
        return htmlentities(
            $this->templateDataAsString($mData),
            array_key_exists(0, $aParams) ? $aParams[0] : ENT_COMPAT,    // quote style
            array_key_exists(1, $aParams) ? $aParams[1] : 'ISO-8859-1'    // returned charset
        );
    }

    public function method_replace($mData, $aParams)
    {
        return str_replace($aParams[0], $aParams[1], $this->templateDataAsString($mData));
    }

    public function method_strftime($mData, $aParams)
    {
        if (($sFormat = trim($aParams[0])) === '') {
            $sFormat = '%Y/%m/%d';
        }

        return strftime($sFormat, (int)trim($this->templateDataAsString($mData)));
    }

    public function method_isSubmitted($mData, $aParams)
    {
        return $this->oForm->oDataHandler->_isSubmitted();
    }

    public function method_isTestSubmitted($mData, $aParams)
    {
        return $this->oForm->oDataHandler->_isTestSubmitted();
    }

    public function method_isDraftSubmitted($mData, $aParams)
    {
        return $this->oForm->oDataHandler->_isDraftSubmitted();
    }

    public function method_isRefreshSubmitted($mData, $aParams)
    {
        return $this->oForm->oDataHandler->_isRefreshSubmitted();
    }

    public function method_isClearSubmitted($mData, $aParams)
    {
        return $this->oForm->oDataHandler->_isClearSubmitted();
    }

    public function method_isSearchSubmitted($mData, $aParams)
    {
        return $this->oForm->oDataHandler->_isSearchSubmitted();
    }

    public function method_isFullySubmitted($mData, $aParams)
    {
        return $this->method_isFullSubmitted($mData, $aParams);
    }

    public function method_isFullSubmitted($mData, $aParams)
    {
        return $this->oForm->oDataHandler->_isFullySubmitted();
    }

    public function method_allIsValid($mData, $aParams)
    {
        return $this->oForm->oDataHandler->_allIsValid();
    }

    public function method_and($mData, $aParams)
    {
        $mData = $this->templateDataAsString($mData);

        return $this->templateDataAsString($mData) && $aParams[0];
    }

    public function method_persistHidden($mData, $aParams)
    {
        if (($oRdt =& $this->oForm->getRdtForTemplateMethod($mData)) !== false) {
            return $oRdt->persistHidden();
        }

        return AMEOSFORMIDABLE_LEXER_BREAKED;
    }

    public function method_hasError($mData, $aParams)
    {
        // wurde der Methode direkt der Name eines Widgets übergeben?
        if ($aParams[0]) {
            $mData = $mData[$aParams[0]];
        }

        return $this->method_hasErrors($mData, $aParams);
    }

    public function method_hasErrors($mData, $aParams)
    {
        if (($oRdt =& $this->oForm->getRdtForTemplateMethod($mData)) !== false) {
            return $oRdt->hasDeepError();
        } else {
            return !$this->oForm->oDataHandler->_allIsValid();
        }
    }

    public function method_includeCss($mData, $aParams)
    {
        $sFile = $this->oForm->toWebPath($aParams[0]);
        $this->oForm->additionalHeaderData(
            '<link rel="stylesheet" type="text/css" href="' . $sFile . '" />'
        );
    }

    public function method_codeBehind($mData, $aParams)
    {
        if ($oRunnable = $this->oForm->getRunnable()->getCodeBehind($aParams[0])) {
            return $oRunnable['object'];
        }

        return AMEOSFORMIDABLE_LEXER_BREAKED;
    }

    public function method_cb($mData, $aParams)
    {
        return $this->method_codeBehind($mData, $aParams);
    }

    public function method_imageMaxWidth($mData, $aParams)
    {
        $sPath = $mData['filepath.']['original.']['rel'];

        if (file_exists($sPath)) {
            // expecting typoscript

            $aImage
                = $GLOBALS['TSFE']->tmpl->setup['config.']['tx_mkforms.']['res.']['shared.']['xml.']['imageprocess.']['maxwh.'];
            $aImage['file.']['10.']['file'] = $sPath;
            $aImage['file.']['10.']['file.']['maxW'] = $aParams[0];
            unset($aImage['file.']['10.']['file.']['maxH']);

            $sNewPath = $GLOBALS['TSFE']->cObj->cObjGetSingle('IMG_RESOURCE', $aImage);    // IMG_RESOURCE always returns relative path

            return $sNewPath;
        }

        return AMEOSFORMIDABLE_LEXER_BREAKED;
    }

    public function method_parent($mData, $aParams)
    {
        return $this->oForm->_oParent;
    }

    public function method_explode($mData, $aParams)
    {
        if (!isset($aParams[0])) {
            $sSep = ',';
        } else {
            $sSep = $aParams[0];
        }

        $aRes = Tx_Rnbase_Utility_Strings::trimExplode(
            $sSep,
            $this->templateDataAsString($mData)
        );

        reset($aRes);

        return $aRes;
    }

    /**
     * cheacks if a property exists in htmlbag
     *
     * @param mixed $mData
     * @param array $aParams
     *
     * @return bool
     */
    public function method_propertyExists($mData, $aParams)
    {
        return array_key_exists($aParams[0], $mData);
    }

    public function method_propertyNotExists($mData, $aParams)
    {
        return !$this->method_propertyExists($mData, $aParams);
    }

    public function method_isEmpty($mData, $aParams)
    {
        if (is_string($mData)) {
            $mData = trim($mData);
        }

        return empty($mData);
    }

    public function method_isNotEmpty($mData, $aParams)
    {
        return !$this->method_isEmpty($mData, $aParams);
    }

    public function method_isDate($mData, $aParams)
    {
        return ($mData !== '0000-00-00' && checkdate(@date('m', $mData), @date('d', $mData), @date('Y', $mData)));
    }

    public function method_isNoDate($mData, $aParams)
    {
        return !$this->method_isDate($mData, $aParams);
    }

    public function method_tstamp2Date($mData, $aParams)
    {
        if ($this->method_isDate($mData, $aParams)) {
            return date($aParams[0], $mData);
        }

        return $mData;
    }

    public function method_linkUrl($mData, $aParams)
    {
        return $this->oForm->getCObj()->typoLink_URL(
            array(
                'parameter' => $aParams[0]
            )
        );
    }

    public function method_extConf($mData, $aParams)
    {
        return $this->oForm->getExtConfVal($aParams[0]);
    }
}
