<?php
/**
 * Plugin 'rdt_progressbar' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_widgets_progressbar_Main extends formidable_mainrenderlet
{

    var $aLibs = array(
        "rdt_progressbar_class" => "res/js/progressbar.js",
    );

    var $sMajixClass = "ProgressBar";
    var $bCustomIncludeScript = true;
    var $aSteps = false;

    function _render()
    {

        $fValue = $this->getValue();
        $fMin = $this->getMinValue();
        $fMax = $this->getMaxValue();
        $iWidth = $this->getPxWidth();
        $fPercent = $this->getPercent();
        $bEffects = $this->defaultFalse("/effects");

        $sBegin = "<div id='" . $this->_getElementHtmlId() . "' " . $this->_getAddInputParams() . ">";
        $sEnd = "</div>";

        // allowed because of $bCustomIncludeScript = TRUE
        $this->includeScripts(
            array(
                "min" => $fMin,
                "max" => $fMax,
                "precision" => $iPrecision,
                "value" => $fValue,
                "percent" => $fPercent,
                "width" => $iWidth,
                "steps" => $this->aSteps,
                "effects" => $bEffects,
            )
        );

        if (($aStep = $this->getStep($fValue)) === false) {
            $sProgressLabel = $fPercent . "%";
        } else {
            $sProgressLabel = $aStep["label"];
        }

        $aHtmlBag = array(
            "__compiled" => $sBegin . "<span>" . $sProgressLabel . "</span>" . $sEnd,
        );

        return $aHtmlBag;
    }

    function getMaxValue()
    {
        $mMax = 100;

        if (($mMax = $this->_navConf("/max")) !== false) {
            $mMax = $this->getForm()->getRunnable()->callRunnable($mMax);
        }

        return floatval($mMax);
    }

    function getMinValue()
    {
        $mMin = 0;

        if (($mMin = $this->_navConf("/min")) !== false) {
            $mMin = $this->getForm()->getRunnable()->callRunnable($mMin);
        }

        return floatval($mMin);
    }

    function getPrecision()
    {
        $iPrecision = 0;

        if (($iPrecision = $this->oForm->_navConf("/precision")) !== false) {
            $iPrecision = intval($mPrecision);
        }

        return intval($iPrecision);
    }

    function _readOnly()
    {
        return true;
    }

    function _renderOnly()
    {
        return true;
    }

    function _renderReadOnly()
    {
        return $this->_render();
    }

    function _activeListable()
    {
        return false;
    }

    function getStep($iValue)
    {

        $this->initSteps();

        reset($this->aSteps);
        while (list(, $aStep) = each($this->aSteps)) {
            if ($aStep["value"] <= $iValue) {
                return $aStep;
            }
        }

        return false;
    }

    function initSteps()
    {
        if ($this->aSteps === false) {
            $aResSteps = array();
            if (($aSteps = $this->_navConf("/steps")) !== false) {
                reset($aSteps);
                while (list(, $aStep) = each($aSteps)) {
                    $aResSteps[$aStep["value"]] = array(
                        "value" => $aStep["value"],
                        "label" => $this->oForm->getLLLabel($aStep["label"]),
                        "className" => $aStep["class"],
                    );
                }

                krsort($aResSteps);
            }

            reset($aResSteps);
            $this->aSteps = $aResSteps;
        }
    }

    function getValue()
    {
        $fValue = floatval(parent::getValue());

        $fMin = $this->getMinValue();
        $fMax = $this->getMaxValue();

        if ($fValue < $fMin) {
            $mValue = $fMin;
        }

        if ($fValue > $fMax) {
            $fValue = $fMax;
        }

        return $fValue;
    }

    function _getClassesArray($aConf = false)
    {
        $aClasses = parent::_getClassesArray($aConf);
        $mValue = $this->getValue();
        $aStep = $this->getStep($mValue);

        $aClasses[] = $aStep["className"];
        return $aClasses;
    }

    function getPxWidth()
    {
        $mWidth = false;

        if (($mWidth = $this->_navConf("/width")) !== false) {
            $mWidth = intval($this->getForm()->getRunnable()->callRunnable($mWidth));

            if (($mWidth = intval($mWidth)) === 0) {
                $mWidth = false;
            }
        }

        return $mWidth;
    }

    function getPercent()
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

    function _getStyleArray()
    {

        $aStyles = parent::_getStyleArray();

        $iWidth = $this->getPxWidth();

        if ($iWidth !== false) {
            $iStepWidth = round((($iWidth * $this->getPercent()) / 100), 0);
            $aStyles["width"] = $iStepWidth . "px";
        }

        if ($this->defaultTrue("/usedefaultstyle")) {
            if (!array_key_exists("border", $aStyles) && !array_key_exists("border-width", $aStyles)) {
                $aStyles["border-width"] = "2px";
            }

            if (!array_key_exists("border", $aStyles) && !array_key_exists("border-color", $aStyles)) {
                $aStyles["border-color"] = "silver";
            }

            if (!array_key_exists("border", $aStyles) && !array_key_exists("border-style", $aStyles)) {
                $aStyles["border-style"] = "solid";
            }

            if (!array_key_exists("text-align", $aStyles)) {
                $aStyles["text-align"] = "center";
            }

            if (!array_key_exists("overflow", $aStyles)) {
                $aStyles["overflow"] = "hidden";
            }
        }

        reset($aStyles);
        return $aStyles;
    }
}
