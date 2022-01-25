<?php
/**
 * Plugin 'rdt_accordion' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_widgets_accordion_Main extends formidable_mainrenderlet
{
    public $aLibs = [
        'rdt_accordion_lib' => 'res/js/accordion-fixed.js',
        'rdt_accordion_class' => 'res/js/accordion.js',
    ];

    public $sMajixClass = 'Accordion';
    public $bCustomIncludeScript = true;

    public $aPossibleCustomEvents = [
        'ontabopen',
        'ontabclose',
        'ontabchange',
    ];

    public function _render()
    {
        $aConf = [];
        if (false !== ($sSpeed = $this->_navConf('/speed'))) {
            $aConf['resizeSpeed'] = (int) $sSpeed;
        }

        if (false !== ($sClassToggle = $this->_navConf('/classtoggle'))) {
            $aConf['classNames']['toggle'] = $sClassToggle;
        } else {
            $aConf['classNames']['toggle'] = 'accordion_toggle';
        }

        if (false !== ($sClassToggleActive = $this->_navConf('/classtoggleactive'))) {
            $aConf['classNames']['toggleActive'] = $sClassToggleActive;
        } else {
            $aConf['classNames']['toggleActive'] = 'accordion_toggle_active';
        }

        if (false !== ($sClassContent = $this->_navConf('/classcontent'))) {
            $aConf['classNames']['content'] = $sClassContent;
        } else {
            $aConf['classNames']['content'] = 'accordion_content';
        }

        if (false !== ($sWidth = $this->_navConf('/width'))) {
            $aConf['defaultSize']['width'] = $sWidth;
        }

        if (false !== ($sHeight = $this->_navConf('/height'))) {
            $aConf['defaultSize']['height'] = $sHeight;
        }

        if (false !== ($sDirection = $this->_navConf('/direction'))) {
            $aConf['direction'] = $sDirection;
        }

        // Gibt an, ob alle geöffneten Accordionelemende geschlossen werden sollen, befor das geklickte geöffnet wird.
        $aConf['closeactive'] = $this->defaultTrue('/closeactive');

        if (false !== ($sEvent = $this->_navConf('/event'))) {
            $sEvent = strtolower(trim($sEvent));
            if (\Sys25\RnBase\Utility\Strings::isFirstPartOfStr($sEvent, 'on')) {
                $sEvent = substr($sEvent, 2);
            }

            $aConf['onEvent'] = $sEvent;
        }

        foreach ($this->aChilds as $child) {
            // even is toggle
            $aConf['accordions'][] = $child->_getElementHtmlId();

            if ('horizontal' === $aConf['direction']) {
                $child->addCssClass('rdtaccordion_toggle_horizontal');
            } else {
                $child->addCssClass('rdtaccordion_toggle');
            }

            $child->addCssClass($aConf['classNames']['toggle']);

            // odd is content
            $child->addCssClass('rdtaccordion_content');
            if ('horizontal' === $aConf['direction']) {
                $child->addCssClass('rdtaccordion_content_horizontal');
            } else {
                $child->addCssClass('rdtaccordion_content');
            }
        }

        if ('horizontal' === $aConf['direction']) {
            $sStyle = <<<STYLE
.rdtaccordion_toggle_horizontal {
	/* REQUIRED */
	float: left;	/* This make sure it stays horizontal */
	/* REQUIRED */
}
.rdtaccordion_content_horizontal {
	overflow: hidden;
	/* REQUIRED */
	float: left;	/* This make sure it stays horizontal */
	/* REQUIRED */
}
STYLE;
        } else {
            $sStyle = <<<STYLE
.rdtaccordion_toggle {}
.rdtaccordion_content { overflow: hidden;}
STYLE;
        }

        $this->oForm->additionalHeaderData(
            $this->oForm->inline2TempFile(
                $sStyle,
                'css',
                'CSS required by rdt_accordion'
            ),
            'rdt_accordion_'.$aConf['direction'].' required_css'
        );

        $this->oForm->getJSLoader()->loadScriptaculous();

        $this->includeScripts([
            'libconf' => $aConf,
        ]);

        $sAddInputParams = $this->_getAddInputParams();

        $aChilds = $this->renderChildsBag();
        $sCompiledChilds = $this->renderChildsCompiled($aChilds);

        return [
            '__compiled' => '<div id="'.$this->_getElementHtmlId().'" '.$sAddInputParams.'>'.$sCompiledChilds.'</div>',
            'childs' => $aChilds,
        ];
    }

    public function majixSetActiveTab($sTab)
    {
        return $this->buildMajixExecuter(
            'setActiveTab',
            $sTab
        );
    }

    public function majixCloseTab($sTab)
    {
        return $this->buildMajixExecuter(
            'close',
            $sTab
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

    public function renderOnly()
    {
        return true;
    }

    public function _renderReadOnly()
    {
        return $this->_render();
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
