<?php
/**
 * Plugin 'rdt_box' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_widgets_tab_Main extends formidable_mainrenderlet
{
    public function _render()
    {
        if (false === ($sContentAbsName = $this->getConfigValue('/content'))) {
            $this->oForm->mayday('renderlet:TAB <b>'.$this->getAbsName().'</b> - requires <b>/content</b> to be set to the absolute name of a renderlet.');
        }

        if (!array_key_exists($sContentAbsName, $this->oForm->aORenderlets)) {
            $this->oForm->mayday('renderlet:TAB <b>'.$this->getAbsName().'</b> - <b>/content</b> points to the renderlet absolutely named <b>'.$sContentAbsName.'</b>, that does not exist within the application.');
        }

        $sBegin = '<li id="'.$this->_getElementHtmlId().'" '.$this->_getAddInputParams().'>';
        $sBegin .= '<a class="rdttab" href="'.\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI').'#'.$this->oForm->aORenderlets[$sContentAbsName]->_getElementHtmlId().'">';

        $sEnd = '</a>';
        $sEnd .= '</li>';

        if ($this->hasChilds()) {
            $aChilds = $this->renderChildsBag();
            $sCompiledChilds = $this->renderChildsCompiled(
                $aChilds
            );
        } else {
            $sCompiledChilds = $this->getLabel();
        }

        $aHtmlBag = [
            '__compiled' => $this->_displayLabel($sLabel).$sBegin.$sCompiledChilds.$sEnd,
            'childs' => $aChilds,
        ];

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

    public function majixReplaceData($aData)
    {
        return $this->buildMajixExecuter(
            'replaceData',
            $aData
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
}
