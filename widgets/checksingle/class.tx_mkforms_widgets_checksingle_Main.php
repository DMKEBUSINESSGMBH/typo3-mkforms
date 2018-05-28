<?php
/**
 * Plugin 'rdt_checksingle' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_widgets_checksingle_Main extends formidable_mainrenderlet
{
    public $sMajixClass = 'CheckSingle';
    // define methodname, if a specific init method in the js should be called, after dom is ready.
    public $sAttachPostInitTask = 'initialize';
    public $aLibs = array(
        'rdt_checksingle_class' => 'res/js/checksingle.js',
    );
    public $sDefaultLabelClass = 'label-inline';

    public function _render()
    {
        $sChecked = '';

        $iValue = $this->getValue();

        if ($iValue === 1) {
            $sChecked = ' checked="checked" ';
        }

        // wenn eine checkbox nicht gecheckt wurde, wird sie nicht übertragen.
        // um dieses problem zu umgehen, fügen wir ein hidden Feld mit der eigentlichen ID
        // ein. dieses wird beim klick jeweils gesetzt damit immer der richtige wert übertragen wird
        $aConfig = false;
        if ($this->getForm()->getJSLoader()->mayLoadJsFramework()) {
            $sInput = sprintf(
                '<input type="hidden" name="%1$s" id="%2$s" %4$s value="%3$s" />',
                $this->_getElementHtmlName(),
                $this->_getElementHtmlId(),
                $iValue,
                $this->_getAddInputParams()
            );
            $sInput .= sprintf(
                '<input type="checkbox" id="%2$s_checkbox" %3$s %4$s value="1" />',
                $this->_getElementHtmlName(),
                $this->_getElementHtmlId(),
                $sChecked,
                $this->_getAddInputParams()
            );
            // damit das Label auf die checkbox zeigt
            $aConfig['sId'] = $this->_getElementHtmlId() . '_checkbox';
        } else {
            $sInput .= sprintf(
                '<input type="checkbox" name="%1$s" id="%2$s" %3$s %4$s value="1" />',
                $this->_getElementHtmlName(),
                $this->_getElementHtmlId(),
                $sChecked,
                $this->_getAddInputParams()
            );
        }

        $sLabelFor = $this->_displayLabel(
            $this->getLabel(),
            $aConfig
        );

        $renderLabelFirst = $this->isTrue('renderlabelfirst');

        $aHtmlBag = array(
            '__compiled'        => ($renderLabelFirst ? $sLabelFor . $sInput :  $sInput . $sLabelFor),
            'input'                => $sInput,
            'checked'            => $sChecked,
            'value' => $iValue,
            'value.' => array(
                'humanreadable' => $this->_getHumanReadableValue($iValue)
            ),
        );

        return $aHtmlBag;
    }

    /*
        internationalization of checked labels thanks to Manuel Rego Casanovas
        http://lists.netfielders.de/pipermail/typo3-project-formidable/2007-May/000343.html
    */

    public function _getCheckedLabel()
    {
        $mCheckedLabel = $this->_navConf('/labels/checked/');

        return ($mCheckedLabel) ? $this->oForm->getConfigXML()->getLLLabel($mCheckedLabel) : 'Y';
    }

    public function _getNonCheckedLabel()
    {
        $mNonCheckedLabel = $this->_navConf('/labels/nonchecked/');

        return  ($mNonCheckedLabel) ? $this->oForm->getConfigXML()->getLLLabel($mNonCheckedLabel) : 'N';
    }

    public function _getHumanReadableValue($data)
    {
        if ((int)$data === 1) {
            return $this->_getCheckedLabel();
        }

        return $this->_getNonCheckedLabel();
    }

    /*
        END internationalization of checked labels
    */

    public function majixCheck()
    {
        return $this->buildMajixExecuter(
            'check'
        );
    }

    public function majixUnCheck()
    {
        return $this->buildMajixExecuter(
            'unCheck'
        );
    }

    public function getValue()
    {
        return (int)parent::getValue();
    }

    public function isChecked()
    {
        return $this->getValue() === 1;
    }

    public function check()
    {
        $this->setValue(1);
    }

    public function unCheck()
    {
        $this->setValue(0);
    }

    public function hasBeenPosted()
    {
        if ($this->getForm()->getJSLoader()->mayLoadJsFramework()) {
            return $this->bHasBeenPosted;
        } else {
            // problem here: checkbox don't post anything if not checked and no JS Framework
            // to determine if checkbox has been checked, we have to look around then
            return $this->_isSubmitted();
        }
    }

    public function _emptyFormValue($iValue)
    {
        return((int)$iValue === 0);
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_checksingle/api/class.tx_rdtchecksingle.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_checksingle/api/class.tx_rdtchecksingle.php'];
}
