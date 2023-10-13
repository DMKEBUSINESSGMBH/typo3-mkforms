<?php
/**
 * Plugin 'rdt_jstree' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_widgets_jstree_Main extends formidable_mainrenderlet
{
    public $aLibs = [
        'rdt_jstree_class' => 'res/js/jstree.js',
        'rdt_jstree_lib_class' => 'res/lib/js/AxentTree.js',
    ];

    public $sMajixClass = 'JsTree';
    public $aPossibleCustomEvents = [
        'onnodeclick',
        'onnodeopen',
        'onnodeclose',
    ];

    public $bCustomIncludeScript = true;

    public $aTreeData = [];

    public function _render()
    {
        $this->oForm->getJSLoader()->loadScriptaculousDragDrop();

        $this->oForm->additionalHeaderData(
            '<link rel="stylesheet" type="text/css" href="'.$this->sExtWebPath.'res/lib/css/tree.css" />',
            'rdt_jstree_lib_css'
        );

        $mValue = $this->getValue();
        $sLabel = $this->getLabel();
        $this->aTreeData = $this->_fetchData();
        $sTree = $this->renderTree($this->aTreeData);

        $sInput = '<ul id="'.$this->_getElementHtmlId().'" '.$this->_getAddInputParams().'>'.$sTree.'</ul>';

        $this->includeScripts([
            'value' => $mValue,
        ]);

        return [
            '__compiled' => $this->_displayLabel($sLabel).$sInput,
            'input' => $sInput,
            'label' => $sLabel,
            'value' => $mValue,
        ];
    }

    public function &_fetchData()
    {
        if (false === ($mData = $this->getConfigValue('/data')) || !$this->oForm->isRunneable($mData)) {
            $this->oForm->mayday('RENDERLET JSTREE <b>'.$this->_getName().'</b> - requires <b>/data</b> to be properly set with a runneable. Check your XML conf.');
        }

        return $this->getForm()->getRunnable()->callRunnable($mData);
    }

    public function renderTree($aData)
    {
        $aBuffer = [];
        $this->_renderTree($aData, $aBuffer);

        return implode("\n", $aBuffer);
    }

    public function _renderTree($aData, &$aBuffer)
    {
        reset($aData);

        $aBuffer[] = '<li>';
        $aBuffer[] = "<span><input type='hidden' value=\"".htmlspecialchars($aData['value']).'"/>'.$aData['caption'].'</span>';

        if (array_key_exists('childs', $aData)) {
            $aBuffer[] = '<ul>';

            foreach ($aData['childs'] as $child) {
                $this->_renderTree($child, $aBuffer);
            }

            $aBuffer[] = '</ul>';
        }

        $aBuffer[] = '</li>';
    }

    public function includeScripts($aConf = [])
    {
        parent::includeScripts($aConf);

        $sAbsName = $this->getAbsName();

        $sInitScript = <<<INITSCRIPT
			Formidable.f("{$this->oForm->formid}").o("{$sAbsName}").init();
INITSCRIPT;

        // initalization is made post-init
        // as when rendered in an ajax context in a modalbox,
        // the HTML is available *after* init tasks
        // as the modalbox HTML is added to the page using after init tasks !

        $this->oForm->attachPostInitTask(
            $sInitScript,
            'Post-init JSTREE initialization',
            $this->_getElementHtmlId()
        );
    }

    public function getSelectedLabel()
    {
        return $this->getNodeLabel(
            $this->getValue()
        );
    }

    public function getNodeLabel($iUid)
    {
        return $this->_getNodeLabel(
            $iUid,
            $this->aTreeData
        );
    }

    public function _getNodeLabel($iUid, $aData)
    {
        if ($aData['value'] == $iUid) {
            return $aData['caption'];
        }

        if (array_key_exists('childs', $aData) && is_array($aData['childs']) && !empty($aData['childs'])) {
            foreach ($aData['childs'] as $child) {
                if (false !== ($mRes = $this->_getNodeLabel($iUid, $child))) {
                    return $mRes;
                }
            }
        }

        return false;
    }

    public function getSelectedPath()
    {
        return $this->getPathForNode($this->getValue());
    }

    public function getPathForNode($iUid)
    {
        return implode('/', $this->getPathArrayForNode($iUid)).'/';
    }

    public function getPathArrayForNode($iUid)
    {
        $aNodes = [];    // only to allow pass-by-ref
        $this->_getPathArrayForNode(
            $iUid,
            ['childs' => [$this->aTreeData]],
            $aNodes
        );
        $aNodes = array_reverse($aNodes, true);
        reset($aNodes);

        return $aNodes;
    }

    /**
     * @param int     $iUid
     * @param array[] $aData
     * @param array   $aNodes
     *
     * @return bool
     */
    public function _getPathArrayForNode($iUid, $aData, &$aNodes)
    {
        if ($aData['value'] == $iUid) {
            return true;
        }

        if (array_key_exists('childs', $aData) && is_array($aData['childs']) && !empty($aData['childs'])) {
            foreach ($aData['childs'] as $child) {
                if ($this->_getPathArrayForNode($iUid, $child, $aNodes)) {
                    $aNodes[$child['value']] = $child['caption'];

                    return true;
                }
            }
        }

        return false;
    }
}
