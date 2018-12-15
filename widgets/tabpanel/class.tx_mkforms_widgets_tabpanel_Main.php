<?php
/**
 * Plugin 'rdt_box' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_widgets_tabpanel_Main extends formidable_mainrenderlet
{
    public $sMajixClass = 'TabPanel';
    public $aLibs = array(
        'rdt_tabpanel_lib' => 'res/js/libs/control.tabs.2.1.1.js',
        'rdt_tabpanel_class' => 'res/js/tabpanel.js',
    );
    public $bCustomIncludeScript = true;

    public function _render()
    {
        $sBegin = '<ul id="' . $this->_getElementHtmlId() . '" ' . $this->_getAddInputParams() . ' onmouseup="this.blur()">';
        $sEnd = '</ul>';

        $aTabs = array();
        foreach ($this->aChilds as $sName => $oRdt) {
            if ($oRdt->_getType() == 'TAB') {
                $sId = $oRdt->_getElementHtmlId();
                $aTabs[$sId] = array(
                    'name' => $sName,
                    'label' => $this->getLabel(),
                    'htmlid' => $sId,
                );
            }
        }

        $visible = true;
        $aConfig = array(
            'setClassOnContainer' => 'false',
            'activeClassName' => 'active',
            'defaultTab' => 'first',
            'linkSelector' => 'li a.rdttab',
            'visible' => &$visible,
            'tabs' => $aTabs,
        );

        if (($aUserConfig = $this->_navConf('config')) !== false) {
            if (array_key_exists('activeclassname', $aUserConfig)) {
                $aConfig['activeClassName'] = $aUserConfig['activeclassname'];
            }
            if (array_key_exists('setclassoncontainer', $aUserConfig)) {
                $aConfig['setclassoncontainer'] = $aUserConfig['setclassoncontainer'];
            }

            if (array_key_exists('defaulttab', $aUserConfig)) {
                if (array_key_exists($aUserConfig['defaulttab'], $this->aChilds)) {
                    // tab id
                    $aConfig['defaultTab'] = $this->oForm->aORenderlets[$this->aChilds[$aUserConfig['defaulttab']]->_navConf('/content')]->_getElementHtmlId();
                } else {
                    // first, last, none
                    $aConfig['defaultTab'] = $aUserConfig['defaulttab'];
                }
            }
        }

        $aChilds = $this->renderChildsBag();

        $hideIfChildsBagCount = array();
        if ($this->_navConf('hideifchildsbagcount') !== false) {
            $hideIfChildsBagCount = $this->_navConf('hideifchildsbagcount');
            $hideIfChildsBagCount = Tx_Rnbase_Utility_Strings::trimExplode(',', $hideIfChildsBagCount);
            $hideIfChildsBagCount = array_flip($hideIfChildsBagCount);
        }

        $visible = !array_key_exists(count($aChilds), $hideIfChildsBagCount);

        if ($visible) {
            $sCompiledChilds = $this->renderChildsCompiled(
                $aChilds
            );
            $compiled = $this->_displayLabel($sLabel) . $sBegin . $sCompiledChilds . $sEnd;
        }

        $this->includeScripts(
            array(
                'libconfig' => $aConfig,
                'tabs' => $aTabs
            )
        );


        $aHtmlBag = array(
            '__compiled' => $aConfig['visible'] ? $compiled : '',
            'ul.' => array(
                'begin' => $sBegin,
                'end' => $sEnd,
            ),
            'childs' => $aChilds
        );

        return $aHtmlBag;
    }

    public function _readOnly()
    {
        return true;
    }

    public function _renderOnly($bForAjax = false)
    {
        return true;
    }

    public function _renderReadOnly()
    {
        return $this->_render();
    }

    public function _debugable()
    {
        return $this->oForm->_defaultFalse('/debugable/', $this->aElement);
    }

    public function majixSetActiveTab($sTab)
    {
        return $this->buildMajixExecuter(
            'setActiveTab',
            $this->oForm->aORenderlets[$this->aChilds[$sTab]->_navConf('/content')]->_getElementHtmlId()
        );
    }

    public function majixNextTab()
    {
        return $this->buildMajixExecuter(
            'next'
        );
    }

    public function majixPreviousTab()
    {
        return $this->buildMajixExecuter(
            'previous'
        );
    }

    public function majixFirstTab()
    {
        return $this->buildMajixExecuter(
            'first'
        );
    }

    public function majixLastTab()
    {
        return $this->buildMajixExecuter(
            'last'
        );
    }

    public function mayHaveChilds()
    {
        return true;
    }

    public function shouldAutowrap()
    {
        return $this->oForm->_defaultFalse('/childs/autowrap/');
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_tabpanel/api/class.tx_rdttabpanel.php']) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_tabpanel/api/class.tx_rdttabpanel.php']);
}
