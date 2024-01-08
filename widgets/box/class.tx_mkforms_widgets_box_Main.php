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
    public $aLibs = [
        'rdt_box_class' => 'Resources/Public/JavaScript/widgets/box/box.js',
    ];
    public $aPossibleCustomEvents = [
        'ondragdrop',
        'ondraghover',
    ];

    public $oDataSource = false;
    public $sDsKey = false;

    public function _render()
    {
        $sHtml = ($this->oForm->isRunneable($this->aElement['html'] ?? '')) ? $this->getForm()->getRunnable()->callRunnableWidget($this, $this->aElement['html']) : $this->_navConf('/html');
        $sHtml = $this->oForm->_substLLLInHtml($sHtml);

        $sMode = $this->_navConf('/mode');
        if (false === $sMode) {
            $sMode = 'div';
        } else {
            $sMode = strtolower(trim($sMode));
            if ('' === $sMode) {
                $sMode = 'div';
            } elseif ('none' === $sMode || 'inline' === $sMode) {
                $sMode = 'inline';
            }
        }

        if ($this->hasData()) {
            $sValue = $this->getValue();

            if (!$this->_emptyFormValue($sValue) && $this->hasData() && !$this->hasValue()) {
                $sHtml = $this->getValueForHtml($sValue);
            }

            $sName = $this->_getElementHtmlName();
            $sId = $this->_getElementHtmlId().'_value';
            $sHidden = '<input type="hidden" name="'.$sName.'" id="'.$sId.'" value="'.$this->getValueForHtml($sValue).'" />';
        } elseif ($this->isDataBridge()) {
            $sDBridgeName = $this->_getElementHtmlName().'[databridge]';
            $sDBridgeId = $this->_getElementHtmlId().'_databridge';
            $sSignature = $this->dbridge_getCurrentDsetSignature();
            $sHidden = '<input type="hidden" name="'.$sDBridgeName.'" id="'.$sDBridgeId.'" value="'.htmlspecialchars($sSignature).'" />';
        }

        if ('inline' !== $sMode) {
            $sBegin = '<'.$sMode." id='".$this->_getElementHtmlId()."' ".$this->_getAddInputParams().'>';
            $sEnd = '</'.$sMode.'>';
        } else {
            $sBegin = '<!--BEGIN:BOX:inline:'.$this->_getElementHtmlId().'-->';
            $sEnd = '<!--END:BOX:inline:'.$this->_getElementHtmlId().'-->';
        }

        $aChilds = $this->renderChildsBag();
        $aChilds = $this->processBeforeDisplay($aChilds);
        $sCompiledChilds = $this->renderChildsCompiled($aChilds);

        // allowed because of $bCustomIncludeScript = TRUE
        $this->includeScripts(
            [
                'hasdata' => $this->hasData(),
            ]
        );

        if (false !== ($mDraggable = $this->_navConf('/draggable'))) {
            $aConf = [];

            if (is_array($mDraggable)) {
                if (true === $this->_defaultTrue('/draggable/use')) {
                    $bDraggable = true;
                    $aConf['revert'] = $this->_defaultFalse('/draggable/revert');

                    if (false !== ($sHandle = $this->_navConf('/draggable/handle'))) {
                        $aConf['handle'] = $this->oForm->aORenderlets[$sHandle]->_getElementHtmlId();
                    }

                    if (false !== ($sConstraint = $this->_navConf('/draggable/constraint'))) {
                        $aConf['constraint'] = strtolower($sConstraint);
                    }
                }
            } else {
                $bDraggable = true;
            }

            if (true === $bDraggable) {
                $sHtmlId = $this->_getElementHtmlId();

                $sJson = $this->oForm->array2json($aConf);

                $sScript = '
new Draggable("'.$sHtmlId.'", '.$sJson.');
';

                $this->oForm->attachInitTask($sScript);
            }
        }

        if (false !== ($mDroppable = $this->_navConf('/droppable'))) {
            $aConf = [];

            if (is_array($mDroppable)) {
                if (true === $this->_defaultTrue('/droppable/use')) {
                    $bDroppable = true;

                    if (false !== ($sAccept = $this->_navConf('/droppable/accept'))) {
                        $aConf['accept'] = $sAccept;
                    }

                    if (false !== ($sContainment = $this->_navConf('/droppable/containment'))) {
                        $aConf['containment'] = Sys25\RnBase\Utility\Strings::trimExplode($sContainment);
                        foreach ($aConf['containment'] as $iKey => &$value) {
                            $value = $this->oForm->aORenderlets[$value]->_getElementHtmlId();
                        }
                    }

                    if (false !== ($sHoverClass = $this->_navConf('/droppable/hoverclass'))) {
                        $aConf['hoverclass'] = $sHoverClass;
                    }

                    if (false !== ($sOverlap = $this->_navConf('/droppable/overlap'))) {
                        $aConf['overlap'] = $sOverlap;
                    }

                    if (false !== ($bGreedy = $this->_defaultFalse('/droppable/greedy'))) {
                        $aConf['greedy'] = $bGreedy;
                    }
                }
            } else {
                $bDroppable = true;
            }

            if (true === $bDroppable) {
                $sHtmlId = $this->_getElementHtmlId();

                if (array_key_exists('ondragdrop', $this->aCustomEvents)) {
                    $sJs = implode("\n", $this->aCustomEvents['ondragdrop']);
                    $aConf['onDrop'] = 'function() {'.$sJs.'}';
                }

                if (array_key_exists('ondraghover', $this->aCustomEvents)) {
                    $sJs = implode("\n", $this->aCustomEvents['ondraghover']);
                    $aConf['onHover'] = 'function() {'.$sJs.'}';
                }

                $sJson = $this->oForm->array2json($aConf);

                $sScript = '
Droppables.add("'.$sHtmlId.'", '.$sJson.');
';

                $this->oForm->attachInitTask($sScript);
            }
        }

        $aHtmlBag = [
            '__compiled' => $this->_displayLabel('').$sBegin.$sHtml.$sCompiledChilds.$sEnd,
            'html' => $sHtml,
            'box.' => [
                'begin' => $sBegin,
                'end' => $sEnd,
                'mode' => $sMode,
            ],
            'childs' => $aChilds,
        ];

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
            return '' === trim($sValue);
        }

        return true;
    }

    public function hasValue()
    {
        return false !== $this->_navConf('/data/value') || false !== $this->_navConf('/data/defaultvalue');
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
            foreach ($this->aChilds as $child) {
                $child->doAfterListRender($oListObject);
            }
        }
    }

    public function processBeforeDisplay($aChilds)
    {
        if (false !== ($aBeforeDisplay = $this->_navConf('/beforedisplay')) && $this->oForm->isRunneable($aBeforeDisplay)) {
            $aChilds = $this->getForm()->getRunnable()->callRunnableWidget($this, $aBeforeDisplay, $aChilds);
        }

        return $aChilds;
    }
}
