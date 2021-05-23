<?php
/**
 * Plugin 'rdt_progressbar' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_widgets_progressbar_Main extends formidable_mainrenderlet
{
    public $aLibs = [
        'rdt_progressbar_class' => 'res/js/progressbar.js',
    ];

    public $sMajixClass = 'ProgressBar';
    public $bCustomIncludeScript = true;

    /**
     * @var array[]|bool
     */
    public $aSteps = false;

    public function _render()
    {
        $fValue = $this->getValue();
        $fMin = $this->getMinValue();
        $fMax = $this->getMaxValue();
        $iWidth = $this->getPxWidth();
        $fPercent = $this->getPercent();
        $bEffects = $this->defaultFalse('/effects');

        $sBegin = "<div id='".$this->_getElementHtmlId()."' ".$this->_getAddInputParams().'>';
        $sEnd = '</div>';

        // allowed because of $bCustomIncludeScript = TRUE
        $this->includeScripts(
            [
                'min' => $fMin,
                'max' => $fMax,
                'precision' => $iPrecision,
                'value' => $fValue,
                'percent' => $fPercent,
                'width' => $iWidth,
                'steps' => $this->aSteps,
                'effects' => $bEffects,
            ]
        );

        if (false === ($aStep = $this->getStep($fValue))) {
            $sProgressLabel = $fPercent.'%';
        } else {
            $sProgressLabel = $aStep['label'];
        }

        $aHtmlBag = [
            '__compiled' => $sBegin.'<span>'.$sProgressLabel.'</span>'.$sEnd,
        ];

        return $aHtmlBag;
    }

    public function getMaxValue()
    {
        if (false !== ($mMax = $this->_navConf('/max'))) {
            $mMax = $this->getForm()->getRunnable()->callRunnable($mMax);
        }

        return (float) $mMax;
    }

    public function getMinValue()
    {
        if (false !== ($mMin = $this->_navConf('/min'))) {
            $mMin = $this->getForm()->getRunnable()->callRunnable($mMin);
        }

        return (float) $mMin;
    }

    public function getPrecision()
    {
        if (false !== ($iPrecision = $this->oForm->_navConf('/precision'))) {
            $iPrecision = 0;
        }

        return (int) $iPrecision;
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

    public function _activeListable()
    {
        return false;
    }

    public function getStep($iValue)
    {
        $this->initSteps();

        foreach ($this->aSteps as $aStep) {
            if ($aStep['value'] <= $iValue) {
                return $aStep;
            }
        }

        return false;
    }

    public function initSteps()
    {
        if (false === $this->aSteps) {
            $aResSteps = [];
            if (false !== ($aSteps = $this->_navConf('/steps'))) {
                foreach ($aSteps as $aStep) {
                    $aResSteps[$aStep['value']] = [
                        'value' => $aStep['value'],
                        'label' => $this->oForm->getLLLabel($aStep['label']),
                        'className' => $aStep['class'],
                    ];
                }

                krsort($aResSteps);
            }

            reset($aResSteps);
            $this->aSteps = $aResSteps;
        }
    }

    public function getValue()
    {
        $fValue = (float) parent::getValue();

        $fMin = $this->getMinValue();
        $fMax = $this->getMaxValue();

        if ($fValue < $fMin) {
            $fValue = $fMin;
        }

        if ($fValue > $fMax) {
            $fValue = $fMax;
        }

        return $fValue;
    }

    public function _getClassesArray($aConf = false)
    {
        $aClasses = parent::_getClassesArray($aConf);
        $mValue = $this->getValue();
        $aStep = $this->getStep($mValue);

        $aClasses[] = $aStep['className'];

        return $aClasses;
    }

    public function getPxWidth()
    {
        if (false !== ($mWidth = $this->_navConf('/width'))) {
            $mWidth = (int) $this->getForm()->getRunnable()->callRunnable($mWidth);

            if (0 === ($mWidth = (int) $mWidth)) {
                $mWidth = false;
            }
        }

        return $mWidth;
    }

    public function getPercent()
    {
        $fValue = $this->getValue();
        $fMin = $this->getMinValue();
        $fMax = $this->getMaxValue();
        $iPrecision = $this->getPrecision();

        if ($fMax === $fMin) {
            return 100;
        }

        return round(($fValue / ($fMax - $fMin)) * 100, $iPrecision);
    }

    public function _getStyleArray()
    {
        $aStyles = parent::_getStyleArray();

        $iWidth = $this->getPxWidth();

        if (false !== $iWidth) {
            $iStepWidth = round((($iWidth * $this->getPercent()) / 100), 0);
            $aStyles['width'] = $iStepWidth.'px';
        }

        if ($this->defaultTrue('/usedefaultstyle')) {
            if (!array_key_exists('border', $aStyles) && !array_key_exists('border-width', $aStyles)) {
                $aStyles['border-width'] = '2px';
            }

            if (!array_key_exists('border', $aStyles) && !array_key_exists('border-color', $aStyles)) {
                $aStyles['border-color'] = 'silver';
            }

            if (!array_key_exists('border', $aStyles) && !array_key_exists('border-style', $aStyles)) {
                $aStyles['border-style'] = 'solid';
            }

            if (!array_key_exists('text-align', $aStyles)) {
                $aStyles['text-align'] = 'center';
            }

            if (!array_key_exists('overflow', $aStyles)) {
                $aStyles['overflow'] = 'hidden';
            }
        }

        reset($aStyles);

        return $aStyles;
    }
}
