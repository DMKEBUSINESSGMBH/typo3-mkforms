<?php
/**
 * Plugin 'rdt_box' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_widgets_box_Main extends formidable_mainrenderlet
{
    public $sMajixClass = 'Box';
    public $bCustomIncludeScript = true;
    public $aLibs = array(
        'rdt_box_class' => 'res/js/box.js',
    );
    public $aPossibleCustomEvents = array(
        'ondragdrop',
        'ondraghover',
    );

    public $oDataSource = false;
    public $sDsKey = false;

    public function _render()
    {
        $sHtml = ($this->oForm->isRunneable($this->aElement['html'])) ? $this->getForm()->getRunnable()->callRunnableWidget($this, $this->aElement['html']) : $this->_navConf('/html');
        $sHtml = $this->oForm->_substLLLInHtml($sHtml);

        $sMode = $this->_navConf('/mode');
        if ($sMode === false) {
            $sMode = 'div';
        } else {
            $sMode = strtolower(trim($sMode));
            if ($sMode === '') {
                $sMode = 'div';
            } elseif ($sMode === 'none' || $sMode === 'inline') {
                $sMode = 'inline';
            }
        }

        if ($this->hasData()) {
            $sValue = $this->getValue();

            if (!$this->_emptyFormValue($sValue) && $this->hasData() && !$this->hasValue()) {
                $sHtml = $this->getValueForHtml($sValue);
            }

            $sName = $this->_getElementHtmlName();
            $sId = $this->_getElementHtmlId() . '_value';
            $sHidden = '<input type="hidden" name="' . $sName . '" id="' . $sId . '" value="' . $this->getValueForHtml($sValue) . '" />';
        } elseif ($this->isDataBridge()) {
            $sDBridgeName = $this->_getElementHtmlName() . '[databridge]';
            $sDBridgeId = $this->_getElementHtmlId() . '_databridge';
            $sSignature = $this->dbridge_getCurrentDsetSignature();
            $sHidden = '<input type="hidden" name="' . $sDBridgeName . '" id="' . $sDBridgeId . '" value="' . htmlspecialchars($sSignature) . '" />';
        }

        if ($sMode !== 'inline') {
            $sBegin = '<' . $sMode . " id='" . $this->_getElementHtmlId() . "' " . $this->_getAddInputParams() . '>';
            $sEnd = '</' . $sMode . '>' . $sHidden;
        } else {
            $sBegin = '<!--BEGIN:BOX:inline:' . $this->_getElementHtmlId() . '-->';
            $sEnd = '<!--END:BOX:inline:' . $this->_getElementHtmlId() . '-->';
        }

        $aChilds = $this->renderChildsBag();
        $aChilds = $this->processBeforeDisplay($aChilds);
        $sCompiledChilds = $this->renderChildsCompiled($aChilds);

        // allowed because of $bCustomIncludeScript = TRUE
        $this->includeScripts(
            array(
                'hasdata' => $this->hasData(),
            )
        );

        if (($mDraggable = $this->_navConf('/draggable')) !== false) {
            $aConf = array();

            if (is_array($mDraggable)) {
                if ($this->_defaultTrue('/draggable/use') === true) {
                    $bDraggable = true;
                    $aConf['revert'] = $this->_defaultFalse('/draggable/revert');

                    if (($sHandle = $this->_navConf('/draggable/handle')) !== false) {
                        $aConf['handle'] = $this->oForm->aORenderlets[$sHandle]->_getElementHtmlId();
                    }

                    if (($sConstraint = $this->_navConf('/draggable/constraint')) !== false) {
                        $aConf['constraint'] = strtolower($sConstraint);
                    }
                }
            } else {
                $bDraggable = true;
            }

            if ($bDraggable === true) {
                $sHtmlId = $this->_getElementHtmlId();

                $sJson = $this->oForm->array2json($aConf);

                $sScript = '
new Draggable("' . $sHtmlId . '", ' . $sJson . ');
';

                $this->oForm->attachInitTask($sScript);
            }
        }

        if (($mDroppable = $this->_navConf('/droppable')) !== false) {
            $aConf = array();

            if (is_array($mDroppable)) {
                if ($this->_defaultTrue('/droppable/use') === true) {
                    $bDroppable = true;

                    if (($sAccept = $this->_navConf('/droppable/accept')) !== false) {
                        $aConf['accept'] = $sAccept;
                    }

                    if (($sContainment = $this->_navConf('/droppable/containment')) !== false) {
                        $aConf['containment'] = Tx_Rnbase_Utility_Strings::trimExplode($sContainment);
                        reset($aConf['containment']);
                        while (list($iKey, ) = each($aConf['containment'])) {
                            $aConf['containment'][$iKey] = $this->oForm->aORenderlets[$aConf['containment'][$iKey]]->_getElementHtmlId();
                        }
                    }

                    if (($sHoverClass = $this->_navConf('/droppable/hoverclass')) !== false) {
                        $aConf['hoverclass'] = $sHoverClass;
                    }

                    if (($sOverlap = $this->_navConf('/droppable/overlap')) !== false) {
                        $aConf['overlap'] = $sOverlap;
                    }

                    if (($bGreedy = $this->_defaultFalse('/droppable/greedy')) !== false) {
                        $aConf['greedy'] = $bGreedy;
                    }
                }
            } else {
                $bDroppable = true;
            }

            if ($bDroppable === true) {
                $sHtmlId = $this->_getElementHtmlId();

                if (array_key_exists('ondragdrop', $this->aCustomEvents)) {
                    $sJs = implode("\n", $this->aCustomEvents['ondragdrop']);
                    $aConf['onDrop'] = 'function() {' . $sJs . '}';
                }

                if (array_key_exists('ondraghover', $this->aCustomEvents)) {
                    $sJs = implode("\n", $this->aCustomEvents['ondraghover']);
                    $aConf['onHover'] = 'function() {' . $sJs . '}';
                }

                $sJson = $this->oForm->array2json($aConf);

                $sScript = '
Droppables.add("' . $sHtmlId . '", ' . $sJson . ');
';

                $this->oForm->attachInitTask($sScript);
            }
        }

        $aHtmlBag = array(
            '__compiled' => $this->_displayLabel($sLabel) . $sBegin . $sHtml . $sCompiledChilds . $sEnd,
            'html' => $sHtml,
            'box.' => array(
                'begin' => $sBegin,
                'end' => $sEnd,
                'mode' => $sMode,
            ),
            'childs' => $aChilds
        );

        return $aHtmlBag;
    }

    public function mayBeDataBridge()
    {
        return true;
    }

    public function setHtml($sHtml)
    {
        $this->aElement['html'] = $sHtml;
    }

    public function _readOnly()
    {
        return true;
    }

    public function _renderOnly($bForAjax = false)
    {
        return $this->_defaultTrue('/renderonly/');
    }

    public function _renderReadOnly()
    {
        return $this->_render();
    }

    public function _activeListable()
    {
        return $this->oForm->_defaultTrue('/activelistable/', $this->aElement);
    }

    public function _debugable()
    {
        return $this->oForm->_defaultFalse('/debugable/', $this->aElement);
    }

    public function majixReplaceData($aData)
    {
        return $this->buildMajixExecuter(
            'replaceData',
            $aData
        );
    }

    public function majixSetHtml($sData)
    {
        return $this->buildMajixExecuter(
            'setHtml',
            $this->oForm->_substLLLInHtml($sData)
        );
    }

    public function majixSetValue($sData)
    {
        return $this->buildMajixExecuter(
            'setValue',
            $sData
        );
    }

    public function majixToggleDisplay()
    {
        return $this->buildMajixExecuter(
            'toggleDisplay'
        );
    }

    public function mayHaveChilds()
    {
        return true;
    }

    public function _emptyFormValue($sValue)
    {
        if ($this->hasData()) {
            return (trim($sValue) === '');
        }

        return true;
    }

    public function hasValue()
    {
        return ($this->_navConf('/data/value') !== false || $this->_navConf('/data/defaultvalue') !== false);
    }

    public function _searchable()
    {
        if ($this->hasData()) {
            return $this->_defaultTrue('/searchable/');
        }

        return $this->_defaultFalse('/searchable/');
    }

    public function doAfterListRender(&$oListObject)
    {
        parent::doAfterListRender($oListObject);

        if ($this->hasChilds()) {
            $aChildKeys = array_keys($this->aChilds);
            reset($aChildKeys);
            while (list(, $sKey) = each($aChildKeys)) {
                $this->aChilds[$sKey]->doAfterListRender($oListObject);
            }
        }
    }

    public function processBeforeDisplay($aChilds)
    {
        if (($aBeforeDisplay = $this->_navConf('/beforedisplay')) !== false && $this->oForm->isRunneable($aBeforeDisplay)) {
            $aChilds = $this->getForm()->getRunnable()->callRunnableWidget($this, $aBeforeDisplay, $aChilds);
        }

        return $aChilds;
    }
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_box/api/class.tx_rdtbox.php']) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_box/api/class.tx_rdtbox.php']);
}
