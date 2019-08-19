<?php

class formidable_mainobject
{
    public $oForm = null;

    public $aElement = null;

    public $sExtPath = null;

    public $sExtRelPath = null;

    public $sExtWebPath = null;

    public $aObjectType = null;

    /**
     * @var string|null
     */
    public $sXPath = null;

    public $sNamePrefix = false;

    /**
     * @param tx_mkforms_forms_Base $oForm
     * @param array                 $aElement
     * @param array                 $aObjectType
     * @param string                $sXPath
     * @param string                $sNamePrefix
     */
    public function _init(&$oForm, $aElement, $aObjectType, $sXPath, $sNamePrefix = false)
    {
        $this->oForm = &$oForm;
        $this->aElement = $aElement;
        $this->aObjectType = $aObjectType;

        $this->sExtPath = $aObjectType['PATH'];
        $this->sExtRelPath = $aObjectType['RELPATH'];
        $absRefPrefix = $oForm->getJSLoader()->getAbsRefPrefix();
        $this->sExtWebPath = $absRefPrefix.$this->sExtRelPath;

        $this->sXPath = $sXPath;

        $this->sNamePrefix = $sNamePrefix;

        $this->conf = $this->getForm()->getConfTS($aObjectType['OBJECT'].'.'.$aObjectType['EXTKEY'].'.');
        $this->conf = $this->conf ? $this->conf : array();
    }

    /**
     * Returns the form.
     *
     * @return tx_ameosformidable
     */
    protected function &getForm()
    {
        return $this->oForm;
    }

    public function _getType()
    {
        return $this->aElement['type'];
    }

    /**
     * @param string $path
     * @param string $aConf
     *
     * @return unknown
     *
     * @deprecated use getConfigValue
     */
    public function _navConf($path, $aConf = false)
    {
        if (false !== $aConf) {
            return $this->getForm()->_navConf($path, $aConf);
        }

        return $this->getForm()->_navConf($path, $this->aElement);
    }

    /**
     * Read value from objects form definition.
     *
     * @param string $path
     */
    protected function getConfigValue($path)
    {
        return $this->getForm()->_navConf($path, $this->aElement);
    }

    public function _isTrue($sPath, $aConf = false)
    {
        return $this->_isTrueVal(
            $this->_navConf($sPath, $aConf)
        );
    }

    public function isTrue($sPath, $aConf = false)
    {
        return $this->_isTrue($sPath, $aConf);
    }

    public function _isFalse($sPath)
    {
        $mValue = $this->getConfigValue($sPath);

        if (false !== $mValue) {
            return $this->_isFalseVal($mValue);
        } else {
            return false;    // if not found in conf, the searched value is not FALSE, so _isFalse() returns FALSE !!!!
        }
    }

    /**
     * @param string $sPath
     *
     * @return bool
     */
    public function isFalse($sPath)
    {
        return $this->_isFalse($sPath);
    }

    public function _isTrueVal($mVal)
    {
        $mVal = $this->callRunneable($mVal);

        return (true === $mVal) || ('1' == $mVal) || ('TRUE' == strtoupper($mVal));
    }

    public function isTrueVal($mVal)
    {
        return $this->_isTrueVal($mVal);
    }

    public function _isFalseVal($mVal)
    {
        if ($this->oForm->isRunneable($mVal)) {
            $mVal = $this->callRunneable($mVal);
        }

        return (false == $mVal) || ('FALSE' == strtoupper($mVal));
    }

    public function isFalseVal($mVal)
    {
        return $this->_isFalseVal($mVal);
    }

    public function _defaultTrue($sPath, $aConf = false)
    {
        if (false !== $this->_navConf($sPath, $aConf)) {
            return $this->_isTrue($sPath, $aConf);
        } else {
            return true;    // TRUE as a default
        }
    }

    public function _defaultFalse($sPath, $aConf = false)
    {
        if (false !== $this->_navConf($sPath, $aConf)) {
            return $this->_isTrue($sPath, $aConf);
        } else {
            return false;    // FALSE as a default
        }
    }

    /**
     * alias for _defaultTrue().
     */
    public function defaultTrue($sPath, $aConf = false)
    {
        return $this->_defaultTrue($sPath, $aConf);
    }

    /**
     * alias for _defaultFalse().
     */
    public function defaultFalse($sPath, $aConf = false)
    {
        return $this->_defaultFalse($sPath, $aConf);
    }

    public function _defaultTrueMixed($sPath)
    {
        if (false !== ($mMixed = $this->_navConf($sPath))) {
            if ('TRUE' !== strtoupper($mMixed) && 'FALSE' !== strtoupper($mMixed)) {
                return $mMixed;
            }

            return $this->_isTrue($sPath);
        } else {
            return true;    // TRUE as a default
        }
    }

    public function defaultTrueMixed($sPath)
    {
        return $this->_defaultTrueMixed($sPath);
    }

    public function _defaultFalseMixed($sPath)
    {
        if (false !== ($mMixed = $this->_navConf($sPath))) {
            if ('TRUE' !== strtoupper($mMixed) && 'FALSE' !== strtoupper($mMixed)) {
                return $mMixed;
            }

            return $this->_isTrue($sPath);
        } else {
            return false;    // FALSE as a default
        }
    }

    public function defaultFalseMixed($sPath)
    {
        return $this->_defaultFalseMixed($sPath);
    }

    // this has to be static !!!
    public static function loaded(&$aParams)
    {
    }

    public function cleanBeforeSession()
    {
        $this->baseCleanBeforeSession();
        unset($this->oForm);
    }

    public function baseCleanBeforeSession()
    {
    }

    public function awakeInSession(&$oForm)
    {
        $this->oForm = &$oForm;
    }

    public function setParent(&$oParent)
    {
        /* nothing in main object */
    }

    /**
     *  TODO: Diese Methode entfernen
     * Alternativer Aufruf:
     * return $this->getForm()->getRunnable()->callRunnable($mMixed, $this);.
     */
    public function &callRunneable($mMixed)
    {
        $aArgs = func_get_args();
        if ($this->getForm()->getRunnable()->isUserObj($mMixed)) {
            $aArgs[] = &$this;
        }
        $ref = $this->getForm()->getRunnable();
        $mRes = call_user_func_array(array($ref, 'callRunnable'), $aArgs);

        return $mRes;
    }

    public function getName()
    {
        return $this->aObjectType['CLASS'];
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/api/class.mainobject.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/api/class.mainobject.php'];
}
