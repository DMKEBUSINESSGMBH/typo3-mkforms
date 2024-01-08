<?php
/**
 * Plugin 'rdr_template' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_renderer_template_Main extends formidable_mainrenderer
{
    public $aCustomTags = [];
    public $aExcludeTags = [];
    public $sTemplateHtml = false;

    public function getTemplatePath()
    {
        $sPath = $this->_navConf('/template/path/');
        if ($this->getForm()->isRunneable($sPath)) {
            $sPath = $this->callRunneable(
                $sPath
            );
        }

        if (is_string($sPath)) {
            return tx_mkforms_util_Div::toServerPath($sPath);
        }

        return '';
    }

    public function getTemplateSubpart()
    {
        $sSubpart = $this->_navConf('/template/subpart/');
        if ($this->getForm()->isRunneable($sSubpart)) {
            $sSubpart = $this->callRunneable(
                $sSubpart
            );
        }

        return $sSubpart;
    }

    public function getTemplateHtml()
    {
        if (false === $this->sTemplateHtml) {
            $sPath = $this->getTemplatePath();

            if (!empty($sPath)) {
                if (!file_exists($sPath)) {
                    $this->getForm()->mayday('RENDERER TEMPLATE - Template file does not exist <b>'.$sPath.'</b>');
                }

                if (false !== ($sSubpart = $this->getTemplateSubpart())) {
                    $mHtml = Sys25\RnBase\Frontend\Marker\Templates::getSubpart(
                        TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($sPath),
                        $sSubpart
                    );

                    if ('' == trim($mHtml)) {
                        $this->getForm()->mayday("RENDERER TEMPLATE - The given template <b>'".$sPath."'</b> with subpart marker ".$sSubpart.' <b>returned an empty string</b> - Check your template');
                    }
                } else {
                    $mHtml = TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($sPath);
                    if ('' == trim($mHtml)) {
                        $this->getForm()->mayday("RENDERER TEMPLATE - The given template <b>'".$sPath."'</b> with no subpart marker <b>returned an empty string</b> - Check your template");
                    }
                }
            } elseif (false !== ($mHtml = $this->_navConf('/html'))) {
                if (is_array($mHtml)) {
                    if ($this->getForm()->isRunneable($mHtml)) {
                        $mHtml = $this->callRunneable($mHtml);
                    } else {
                        $mHtml = $mHtml['__value'];
                    }
                }

                if ('' == trim($mHtml)) {
                    $this->getForm()->mayday('RENDERER TEMPLATE - The given <b>/html</b> provides an empty string</b> - Check your template');
                }
            } else {
                $this->getForm()->mayday('RENDERER TEMPLATE - You have to provide either <b>/template/path</b> or <b>/html</b>');
            }

            $this->sTemplateHtml = $mHtml;
        }

        return $this->sTemplateHtml;
    }

    public function _render($aRendered)
    {
        $aRendered = $this->beforeDisplay($aRendered);

        $this->getForm()->_debug($aRendered, 'RENDERER TEMPLATE - rendered elements array');

        if (false === ($sErrorTag = $this->_navConf('/template/errortag/'))) {
            if (false === ($sErrorTag = $this->_navConf('/html/errortag'))) {
                $sErrorTag = 'errors';
            }
        }

        if ($this->getForm()->isRunneable($sErrorTag)) {
            $sErrorTag = $this->callRunneable(
                $sErrorTag
            );
        }

        $aErrors = [];
        $aCompiledErrors = [];
        $aErrorKeys = array_keys($this->getForm()->_aValidationErrors);
        foreach ($aErrorKeys as $sRdtName) {
            $sShortRdtName = $this->getForm()->aORenderlets[$sRdtName]->_getNameWithoutPrefix();
            if ('' !== trim($this->getForm()->_aValidationErrors[$sRdtName])) {
                $sWrapped = $this->wrapErrorMessage($this->getForm()->_aValidationErrors[$sRdtName]);
                $aErrors[$sShortRdtName] = $this->getForm()->_aValidationErrors[$sRdtName];
                $aErrors[$sShortRdtName.'.']['tag'] = $sWrapped;
                $aCompiledErrors[] = $sWrapped;
            }
        }

        if ('true' == strtolower(trim($this->_navConf('/template/errortagcompilednobr')))) {
            $aErrors['__compiled'] = implode('', $aCompiledErrors);
        } else {
            $aErrors['__compiled'] = implode('<br />', $aCompiledErrors);
        }

        $aErrors['__compiled'] = $this->compileErrorMessages($aCompiledErrors);

        $aErrors['cssdisplay'] = ($this->getForm()->oDataHandler->_allIsValid()) ? 'none' : 'block';

        $aRendered = $this->displayOnlyIfJs($aRendered);
        $aRendered[$sErrorTag] = $aErrors;

        $mHtml = $this->getTemplateHtml();
        $sForm = $this->getForm()->getTemplateTool()->parseTemplateCode(
            $mHtml,
            $aRendered,
            $this->aExcludeTags,
            $this->_defaultTrue('/template/clearmarkersnotused')
        );

        return $this->_wrapIntoForm($sForm);
    }

    public function beforeDisplay($aRendered)
    {
        if (false !== ($aUserObj = $this->_navConf('/beforedisplay/'))) {
            if ($this->getForm()->isRunneable($aUserObj)) {
                $aRendered = $this->callRunneable(
                    $aUserObj,
                    $aRendered
                );
            }
        }

        if (!is_array($aRendered)) {
            $aRendered = [];
        }

        reset($aRendered);

        return $aRendered;
    }

    public function cleanBeforeSession()
    {
        $this->sTemplateHtml = false;
        $this->baseCleanBeforeSession();
    }
}
