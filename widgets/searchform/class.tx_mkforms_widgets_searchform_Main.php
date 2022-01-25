<?php
/**
 * Plugin 'rdt_searchform' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_widgets_searchform_Main extends formidable_mainrenderlet
{
    public $oDataSource = false;

    /**
     * @var array|bool
     */
    public $aCriterias = false;
    public $aFilters = false;

    /**
     * @var array|bool
     */
    public $aDescendants = false;

    public function _init(&$oForm, $aElement, $aObjectType, $sXPath, $sNamePrefix = false)
    {
        parent::_init($oForm, $aElement, $aObjectType, $sXPath);
        $this->_initDescendants();    // early init (meaning before removing unprocessed rdts)
    }

    public function _render()
    {
        $this->_initData();

        $aChildBags = $this->renderChildsBag();
        $sCompiledChilds = $this->renderChildsCompiled($aChildBags);

        if ($this->isRemoteReceiver() && !$this->mayDisplayRemoteReceiver()) {
            return [
                '__compiled' => '',
            ];
        }

        return [
            '__compiled' => $this->_displayLabel($sLabel).$sCompiledChilds,
            'childs' => $aChildBags,
        ];
    }

    public function getDescendants()
    {
        $aDescendants = [];
        $sMyName = $this->getAbsName();

        foreach ($this->oForm->aORenderlets as $sName => $renderlet) {
            if ($renderlet->isDescendantOf($sMyName)) {
                $aDescendants[] = $sName;
            }
        }

        return $aDescendants;
    }

    public function _initDescendants($bForce = false)
    {
        if (true === $bForce || false === $this->aDescendants) {
            $this->aDescendants = $this->getDescendants();
        }
    }

    public function _initData()
    {
        $this->_initDescendants(true);    // done in _init(), re-done here to filter out unprocessed rdts
        $this->_initCriterias();    // if submitted, take from post ; if not, take from session
                                    // and inject values into renderlets
        $this->_initFilters();
        $this->_initDataSource();
    }

    public function mayHaveChilds()
    {
        return true;
    }

    public function isRemoteSender()
    {
        return 'sender' === $this->_navConf('/remote/mode');
    }

    public function isRemoteReceiver()
    {
        return 'receiver' === $this->_navConf('/remote/mode');
    }

    public function _initDataSource()
    {
        if ($this->isRemoteSender()) {
            return;
        }

        if (false === $this->oDataSource) {
            if (false === ($sDsToUse = $this->_navConf('/datasource/use'))) {
                $this->oForm->mayday('RENDERLET SEARCHFORM - requires /datasource/use to be properly set. Check your XML conf.');
            } elseif (!array_key_exists($sDsToUse, $this->oForm->aODataSources)) {
                $this->oForm->mayday("RENDERLET SEARCHFORM - refers to undefined datasource '".$sDsToUse."'. Check your XML conf.");
            }

            $this->oDataSource = &$this->oForm->aODataSources[$sDsToUse];
        }
    }

    public function clearFilters()
    {
        foreach ($this->aDescendants as $sName) {
            $this->oForm->aORenderlets[$sName]->setValue('');
        }

        $this->aCriterias = false;
        tx_mkforms_session_Factory::getSessionManager()->initialize();
        $aAppData = &$GLOBALS['_SESSION']['ameos_formidable']['applicationdata'];
        $aAppData['rdt_lister'][$this->oForm->formid][$this->getAbsName()]['criterias'] = [];

        if ($this->isRemoteReceiver()) {
            $aAppData['rdt_lister'][$this->getRemoteSenderFormId()][$this->getRemoteSenderAbsName()]['criterias'] = [];
        }
    }

    public function getCriterias()
    {
        return $this->aCriterias;
    }

    public function getRemoteSenderFormId()
    {
        if ($this->isRemoteReceiver()) {
            if (false !== ($sSenderFormId = $this->_navConf('/remote/senderformid'))) {
                return $sSenderFormId;
            }
        }

        return false;
    }

    public function getRemoteSenderAbsName()
    {
        if ($this->isRemoteReceiver()) {
            if (false !== ($sSenderAbsName = $this->_navConf('/remote/senderabsname'))) {
                return $sSenderAbsName;
            }
        }

        return false;
    }

    public function _initCriterias()
    {
        if (false === $this->aCriterias) {
            $bUpdate = false;

            if ($this->isRemoteReceiver()) {
                if (false === ($sFormId = $this->getRemoteSenderFormId())) {
                    $this->oForm->mayday('RENDERLET SEARCHFORM - requires /remote/senderFormId to be properly set. Check your XML conf.');
                }

                if (false === ($sSearchAbsName = $this->getRemoteSenderAbsName())) {
                    $this->oForm->mayday('RENDERLET SEARCHFORM - requires /remote/senderAbsName to be properly set. Check your XML conf.');
                }
            } else {
                $sFormId = $this->oForm->formid;
                $sSearchAbsName = $this->getAbsName();
            }

            $this->aCriterias = [];

            tx_mkforms_session_Factory::getSessionManager()->initialize();
            $aAppData = &$GLOBALS['_SESSION']['ameos_formidable']['applicationdata'];

            if (!array_key_exists('rdt_lister', $aAppData)) {
                $aAppData['rdt_lister'] = [];
            }

            if (!array_key_exists($sFormId, $aAppData['rdt_lister'])) {
                $aAppData['rdt_lister'][$sFormId] = [];
            }

            if (!array_key_exists($sSearchAbsName, $aAppData['rdt_lister'][$sFormId])) {
                $aAppData['rdt_lister'][$sFormId][$sSearchAbsName] = [];
            }

            if (!array_key_exists('criterias', $aAppData['rdt_lister'][$sFormId][$sSearchAbsName])) {
                $aAppData['rdt_lister'][$sFormId][$sSearchAbsName]['criterias'] = [];
            }

            if ($this->shouldUpdateCriteriasClassical()) {
                $bUpdate = true;

                if ($this->isRemoteReceiver()) {
                    // set in session
                    foreach ($this->aDescendants as $sAbsName) {
                        $sRelName = $this->oForm->aORenderlets[$sAbsName]->getNameRelativeTo($this);
                        $sRemoteAbsName = $sSearchAbsName.'.'.$sRelName;
                        $this->aCriterias[$sRemoteAbsName] = $this->oForm->aORenderlets[$sAbsName]->getValue();
                    }
                } else {
                    // set in session
                    foreach ($this->aDescendants as $sAbsName) {
                        if (!$this->oForm->aORenderlets[$sAbsName]->hasChilds()) {
                            $this->aCriterias[$sAbsName] = $this->oForm->aORenderlets[$sAbsName]->getValue();
                        }
                    }
                }
            } elseif ($this->shouldUpdateCriteriasRemoteReceiver()) {
                $bUpdate = true;
                if ($this->isRemoteReceiver()) {
                    // set in session

                    $aRawPost = $this->oForm->_getRawPost($sFormId);

                    foreach ($this->aDescendants as $sAbsName) {
                        $sRelName = $this->oForm->aORenderlets[$sAbsName]->getNameRelativeTo($this);
                        $sRemoteAbsName = $sSearchAbsName.'.'.$sRelName;
                        $sRemoteAbsPath = str_replace('.', '/', $sRemoteAbsName);

                        $mValue = $this->oForm->navDeepData($sRemoteAbsPath, $aRawPost);
                        $this->aCriterias[$sRemoteAbsName] = $mValue;
                        $this->oForm->aORenderlets[$sAbsName]->setValue($mValue);    // setting value in receiver
                    }
                }
            }

            if (true === $bUpdate) {
                if ($this->_getParamsFromGET()) {
                    $aGet = (\Sys25\RnBase\Utility\T3General::_GET($sFormId)) ? \Sys25\RnBase\Utility\T3General::_GET($sFormId) : [];

                    foreach ($aGet as $sAbsName => $_) {
                        if (array_key_exists($sAbsName, $this->oForm->aORenderlets)) {
                            $this->aCriterias[$sAbsName] = $aGet[$sAbsName];

                            $this->oForm->aORenderlets[$sAbsName]->setValue(
                                $this->aCriterias[$sAbsName]
                            );

                            $aTemp = [
                                $sFormId => [
                                    $sAbsName => 1,
                                ],
                            ];

                            $this->oForm->setParamsToRemove($aTemp);
                        }
                    }
                }

                $aAppData['rdt_lister'][$sFormId][$sSearchAbsName]['criterias'] = $this->aCriterias;
            } else {
                // take from session
                $this->aCriterias = $aAppData['rdt_lister'][$sFormId][$sSearchAbsName]['criterias'];

                if ($this->isRemoteReceiver()) {
                    if (false === ($sFormId = $this->getRemoteSenderFormId())) {
                        $this->oForm->mayday('RENDERLET SEARCHFORM - requires /remote/senderFormId to be properly set. Check your XML conf.');
                    }

                    if (false === ($sSearchAbsName = $this->getRemoteSenderAbsName())) {
                        $this->oForm->mayday('RENDERLET SEARCHFORM - requires /remote/senderAbsName to be properly set. Check your XML conf.');
                    }

                    foreach ($this->aCriterias as $sAbsName => $_) {
                        $sRelName = $this->oForm->relativizeName(
                            $sAbsName,
                            $sSearchAbsName
                        );

                        $sLocalAbsName = $this->getAbsName().'.'.$sRelName;
                        if (array_key_exists($sLocalAbsName, $this->oForm->aORenderlets)) {
                            $this->oForm->aORenderlets[$sLocalAbsName]->setValue(
                                $this->aCriterias[$sAbsName]
                            );
                        }
                    }
                } else {
                    foreach ($this->aCriterias as $sAbsName => $_) {
                        if (array_key_exists($sAbsName, $this->oForm->aORenderlets)) {
                            $this->oForm->aORenderlets[$sAbsName]->setValue(
                                $this->aCriterias[$sAbsName]
                            );
                        }
                    }
                }
            }
        }
    }

    public function shouldUpdateCriteriasRemoteReceiver()
    {
        if ($this->isRemoteReceiver()) {
            if (false === ($sFormId = $this->getRemoteSenderFormId())) {
                $this->oForm->mayday('RENDERLET SEARCHFORM - requires /remote/senderFormId to be properly set. Check your XML conf.');
            }

            if (false === ($sSearchAbsName = $this->getRemoteSenderAbsName())) {
                $this->oForm->mayday('RENDERLET SEARCHFORM - requires /remote/senderAbsName to be properly set. Check your XML conf.');
            }

            if ($this->oForm->oDataHandler->_isSearchSubmitted($sFormId) || $this->oForm->oDataHandler->_isFullySubmitted($sFormId)) {    // full submit to allow no-js browser to search
                foreach ($this->aDescendants as $sAbsName) {
                    $sRelName = $this->oForm->aORenderlets[$sAbsName]->getNameRelativeTo($this);
                    $sRemoteAbsName = $sSearchAbsName.'.'.$sRelName;

                    if ($this->oForm->aORenderlets[$sAbsName]->hasSubmitted($sFormId, $sRemoteAbsName)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function shouldUpdateCriteriasClassical()
    {
        if (true === $this->oForm->oDataHandler->_isSubmitted()) {
            foreach ($this->aDescendants as $sAbsName) {
                if (array_key_exists($sAbsName, $this->oForm->aORenderlets) &&
                    $this->oForm->aORenderlets[$sAbsName]->hasSubmitted() &&
                    $this->oForm->oDataHandler->_isSearchSubmitted()) {    // the mode is not determined by the renderlet anymore, but rather by the datahandler (one common submit per page, anyway)
                    return true;
                }
            }
        } else {
            if ($this->_getParamsFromGET()) {
                $aGet = (\Sys25\RnBase\Utility\T3General::_GET($this->oForm->formid)) ? \Sys25\RnBase\Utility\T3General::_GET($this->oForm->formid) : [];
                $aIntersect = array_intersect(array_keys($aGet), array_keys($this->oForm->aORenderlets));

                return count($aIntersect) > 0;    // are there get params in url matching at least one criteria in the searchform ?
            }
        }

        return false;
    }

    public function shouldUpdateCriterias()
    {
        if (!$this->isRemoteReceiver()) {
            return $this->shouldUpdateCriteriasClassical();
        }

        return false;
    }

    public function mayDisplayRemoteReceiver()
    {
        return $this->isRemoteReceiver() && !$this->_defaultTrue('/remote/invisible');
    }

    public function processBeforeSearch($aCriterias)
    {
        if (false !== ($aBeforeSearch = $this->_navConf('/beforesearch')) && $this->oForm->isRunneable($aBeforeSearch)) {
            $aCriterias = $this->getForm()->getRunnable()->callRunnableWidget($this, $aBeforeSearch, $aCriterias);
        }

        if (!is_array($aCriterias)) {
            $aCriterias = [];
        }

        return $aCriterias;
    }

    public function processAfterSearch($aResults)
    {
        if (false !== ($aAfterSearch = $this->_navConf('/aftersearch')) && $this->oForm->isRunneable($aAfterSearch)) {
            $aResults = $this->getForm()->getRunnable()->callRunnableWidget($this, $aAfterSearch, $aResults);
        }

        if (!is_array($aResults)) {
            $aResults = [];
        }

        return $aResults;
    }

    public function _initFilters()
    {
        if (false === $this->aFilters) {
            $this->aFilters = [];

            $aCriterias = $this->processBeforeSearch($this->aCriterias);
            reset($aCriterias);

            if ($this->isRemoteReceiver()) {
                if (false === ($sFormId = $this->getRemoteSenderFormId())) {
                    $this->oForm->mayday('RENDERLET SEARCHFORM - requires /remote/senderFormId to be properly set. Check your XML conf.');
                }

                if (false === ($sSearchAbsName = $this->getRemoteSenderAbsName())) {
                    $this->oForm->mayday('RENDERLET SEARCHFORM - requires /remote/senderAbsName to be properly set. Check your XML conf.');
                }

                foreach ($aCriterias as $sRdtName => $_) {
                    $sRelName = $this->oForm->relativizeName(
                        $sRdtName,
                        $sSearchAbsName
                    );

                    $sLocalAbsName = $this->getAbsName().'.'.$sRelName;

                    if (array_key_exists($sLocalAbsName, $this->oForm->aORenderlets)) {
                        $oRdt = &$this->oForm->aORenderlets[$sLocalAbsName];

                        if ($oRdt->_searchable()) {
                            $sValue = $oRdt->_flatten($aCriterias[$sRdtName]);

                            if (!$oRdt->_emptyFormValue($sValue)) {
                                $this->aFilters[] = $oRdt->_sqlSearchClause($sValue);
                            }
                        }
                    }
                }
            } else {
                foreach ($aCriterias as $sRdtName => $_) {
                    if (array_key_exists($sRdtName, $this->oForm->aORenderlets)) {
                        $oRdt = &$this->oForm->aORenderlets[$sRdtName];

                        if ($oRdt->_searchable()) {
                            $sValue = $oRdt->_flatten($aCriterias[$sRdtName]);

                            if (!$oRdt->_emptyFormValue($sValue)) {
                                $this->aFilters[] = $oRdt->_sqlSearchClause($sValue);
                            }
                        }
                    }
                }
            }

            reset($this->aFilters);
        }
    }

    public function &_getFilters()
    {
        $this->_initFilters();
        reset($this->aFilters);

        return $this->aFilters;
    }

    public function &fetchData($aConfig = [])
    {
        return $this->_fetchData($aConfig);
    }

    public function &_fetchData($aConfig = [])
    {
        return $this->processAfterSearch(
            $this->oDataSource->_fetchData(
                $aConfig,
                $this->_getFilters()
            )
        );
    }

    public function _renderOnly($bForAjax = false)
    {
        return $this->_defaultTrue('/renderonly');
    }

    public function _getParamsFromGET()
    {
        return $this->_defaultFalse('/paramsfromget');
    }

    public function _searchable()
    {
        return false;
    }
}
