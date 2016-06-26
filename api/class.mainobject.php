<?php

class formidable_mainobject
{

    var $oForm = null;

    var $aElement = null;

    var $sExtPath = null;

    var $sExtRelPath = null;

    var $sExtWebPath = null;

    var $aObjectType = null;

    var $sXPath = null;

    var $sNamePrefix = false;

    function _init(&$oForm, $aElement, $aObjectType, $sXPath, $sNamePrefix = false)
    {

        $this->oForm =& $oForm;
        $this->aElement = $aElement;
        $this->aObjectType = $aObjectType;

        $this->sExtPath = $aObjectType['PATH'];
        $this->sExtRelPath = $aObjectType['RELPATH'];
        $this->sExtWebPath = Tx_Rnbase_Utility_T3General::getIndpEnv('TYPO3_SITE_URL') . $this->sExtRelPath;

        $this->sXPath = $sXPath;

        $this->sNamePrefix = $sNamePrefix;

        $this->conf = $this->getForm()->getConfTS($aObjectType['OBJECT'] . '.' . $aObjectType['EXTKEY'] . '.');
        $this->conf = $this->conf ? $this->conf : array();
    }

    /**
     * Returns the form
     *
     * @return tx_ameosformidable
     */
    protected function &getForm()
    {
        return $this->oForm;
    }

    function _getType()
    {
        return $this->aElement['type'];
    }

    /**
     *
     * @param string $path
     * @param string $aConf
     * @return unknown
     * @deprecated use getConfigValue
     */
    function _navConf($path, $aConf = false)
    {
        if ($aConf !== false) {
            return $this->getForm()->_navConf($path, $aConf);
        }

        return $this->getForm()->_navConf($path, $this->aElement);
    }

    /**
     * Read value from objects form definition
     * @param string $path
     */
    protected function getConfigValue($path)
    {
        return $this->getForm()->_navConf($path, $this->aElement);
    }

    function _isTrue($sPath, $aConf = false)
    {
        return $this->_isTrueVal(
            $this->_navConf($sPath, $aConf)
        );
    }

    public function isTrue($sPath, $aConf = false)
    {
        return $this->_isTrue($sPath, $aConf);
    }

    function _isFalse($sPath)
    {
        $mValue = $this->getConfigValue($sPath);

        if ($mValue !== false) {
            return $this->_isFalseVal($mValue);
        } else {
            return false;    // if not found in conf, the searched value is not FALSE, so _isFalse() returns FALSE !!!!
        }
    }

    /**
     *
     * @param string $sPath
     * @return boolean
     */
    public function isFalse($sPath)
    {
        return $this->_isFalse($sPath);
    }

    function _isTrueVal($mVal)
    {
        $mVal = $this->callRunneable($mVal);

        return (($mVal === true) || ($mVal == '1') || (strtoupper($mVal) == 'TRUE'));
    }

    function isTrueVal($mVal)
    {
        return $this->_isTrueVal($mVal);
    }

    function _isFalseVal($mVal)
    {
        if ($this->oForm->isRunneable($mVal)) {
            $mVal = $this->callRunneable($mVal);
        }

        return (($mVal == false) || (strtoupper($mVal) == 'FALSE'));
    }

    function isFalseVal($mVal)
    {
        return $this->_isFalseVal($mVal);
    }

    function _defaultTrue($sPath, $aConf = false)
    {

        if ($this->_navConf($sPath, $aConf) !== false) {
            return $this->_isTrue($sPath, $aConf);
        } else {
            return true;    // TRUE as a default
        }
    }

    function _defaultFalse($sPath, $aConf = false)
    {
        if ($this->_navConf($sPath, $aConf) !== false) {
            return $this->_isTrue($sPath, $aConf);
        } else {
            return false;    // FALSE as a default
        }
    }

    /**
     * alias for _defaultTrue()
     */
    function defaultTrue($sPath, $aConf = false)
    {
        return $this->_defaultTrue($sPath, $aConf);
    }

    /**
     * alias for _defaultFalse()
     */
    function defaultFalse($sPath, $aConf = false)
    {
        return $this->_defaultFalse($sPath, $aConf);
    }

    function _defaultTrueMixed($sPath)
    {
        if (($mMixed = $this->_navConf($sPath)) !== false) {
            if (strtoupper($mMixed) !== 'TRUE' && strtoupper($mMixed) !== 'FALSE') {
                return $mMixed;
            }

            return $this->_isTrue($sPath);
        } else {
            return true;    // TRUE as a default
        }
    }

    function defaultTrueMixed($sPath)
    {
        return $this->_defaultTrueMixed($sPath);
    }

    function _defaultFalseMixed($sPath)
    {
        if (($mMixed = $this->_navConf($sPath)) !== false) {
            if (strtoupper($mMixed) !== 'TRUE' && strtoupper($mMixed) !== 'FALSE') {
                return $mMixed;
            }

            return $this->_isTrue($sPath);
        } else {
            return false;    // FALSE as a default
        }
    }

    function defaultFalseMixed($sPath)
    {
        return $this->_defaultFalseMixed($sPath);
    }

    // this has to be static !!!
    function loaded(&$aParams)
    {
    }

    function cleanBeforeSession()
    {
        $this->baseCleanBeforeSession();
        unset($this->oForm);
    }

    function baseCleanBeforeSession()
    {
    }

    function awakeInSession(&$oForm)
    {
        $this->oForm =& $oForm;
    }

    function setParent(&$oParent)
    {
        /* nothing in main object */
    }

    /**
     *  TODO: Diese Methode entfernen
     * Alternativer Aufruf:
     * return $this->getForm()->getRunnable()->callRunnable($mMixed, $this);
     */
    function &callRunneable($mMixed)
    {
        $aArgs = func_get_args();
        if ($this->getForm()->getRunnable()->isUserObj($mMixed)) {
            $aArgs[] =& $this;
        }
        $ref = $this->getForm()->getRunnable();
        $mRes = call_user_func_array(array($ref, 'callRunnable'), $aArgs);

        return $mRes;
    }

    function getName()
    {
        return $this->aObjectType['CLASS'];
    }
}
