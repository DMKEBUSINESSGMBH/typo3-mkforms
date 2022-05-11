<?php
/**
 * Plugin 'va_num' for the 'ameos_formidable' extension.
 *
 * @author  Luc Muller <typo3dev@ameos.com>
 */
class tx_mkforms_validator_num_Main extends formidable_mainvalidator
{
    public function validate(&$oRdt)
    {
        $sAbsName = $oRdt->getAbsName();
        $mNum = $oRdt->getValue();

        if ('' === $mNum) {
            // never evaluate if value is empty
            // as this is left to STANDARD:required
            return;
        }

        $aKeys = array_keys($this->_navConf('/'));
        reset($aKeys);
        foreach ($aKeys as $sKey) {
            // Prüfen ob eine Validierung aufgrund des Dependson Flags statt finden soll
            if ($oRdt->hasError() || !$this->canValidate($oRdt, $sKey, $mNum)) {
                break;
            }

            /***********************************************************************
            *
            *	/isnum
            *
            ***********************************************************************/

            if ('i' === $sKey[0] && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($sKey, 'isnum')) {
                if (!$this->_checkIsNum($mNum)) {
                    $this->oForm->_declareValidationError(
                        $sAbsName,
                        'NUM:isnum',
                        $this->oForm->getConfigXML()->getLLLabel($this->_navConf('/'.$sKey.'/message'))
                    );

                    break;
                }
            }

            /***********************************************************************
            *
            *	/isbetween
            *
            ***********************************************************************/

            if ('i' === $sKey[0] && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($sKey, 'isbetween')) {
                $aBoundaries = \Sys25\RnBase\Utility\Strings::trimExplode(
                    ',',
                    $this->_navConf('/'.$sKey.'/value')
                );

                if (!$this->_checkIsIn($mNum, $aBoundaries)) {
                    $this->oForm->_declareValidationError(
                        $sAbsName,
                        'NUM:isbetween',
                        $this->oForm->getConfigXML()->getLLLabel($this->_navConf('/'.$sKey.'/message'))
                    );

                    break;
                }
            }

            /***********************************************************************
            *
            *	/islower
            *
            ***********************************************************************/

            if ('i' === $sKey[0] && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($sKey, 'islower')) {
                $aBoundaries = \Sys25\RnBase\Utility\Strings::trimExplode(
                    ',',
                    $this->_navConf('/'.$sKey.'/value')
                );

                if (!$this->_checkIsLow($mNum, $aBoundaries)) {
                    $this->oForm->_declareValidationError(
                        $sAbsName,
                        'NUM:islower',
                        $this->oForm->getConfigXML()->getLLLabel($this->_navConf('/'.$sKey.'/message'))
                    );

                    break;
                }
            }

            /***********************************************************************
            *
            *	/ishigher
            *
            ***********************************************************************/

            if ('i' === $sKey[0] && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($sKey, 'ishigher')) {
                $aBoundaries = \Sys25\RnBase\Utility\Strings::trimExplode(
                    ',',
                    $this->_navConf('/'.$sKey.'/value')
                );

                if (!$this->_checkIsHigh($mNum, $aBoundaries)) {
                    $this->oForm->_declareValidationError(
                        $sAbsName,
                        'NUM:ishigher',
                        $this->oForm->getConfigXML()->getLLLabel($this->_navConf('/'.$sKey.'/message'))
                    );

                    break;
                }
            }

            /***********************************************************************
            *
            *	/isfloat
            *
            ***********************************************************************/

            if ('i' === $sKey[0] && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($sKey, 'isfloat')) {
                if (!$this->_checkIsFloat($mNum)) {
                    $this->oForm->_declareValidationError(
                        $sAbsName,
                        'NUM:isfloat',
                        $this->oForm->getConfigXML()->getLLLabel($this->_navConf('/'.$sKey.'/message'))
                    );

                    break;
                }
            }

            /***********************************************************************
            *
            *	/isinteger
            *
            ***********************************************************************/

            if ('i' === $sKey[0] && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($sKey, 'isinteger')) {
                if (!$this->_checkIsInteger($mNum)) {
                    $this->oForm->_declareValidationError(
                        $sAbsName,
                        'NUM:isinteger',
                        $this->oForm->getConfigXML()->getLLLabel($this->_navConf('/'.$sKey.'/message'))
                    );

                    break;
                }
            }
        }
    }

    public function _checkIsNum($mNum)
    {
        return is_numeric($mNum);
    }

    public function _checkIsInteger($mNum)
    {
        return ctype_digit($mNum) && (int) $mNum == $mNum;
    }

    public function _checkIsIn($mNum, $aValues)
    {
        if ($this->_checkIsNum($mNum)) {
            return ($mNum >= min($aValues)) && ($mNum <= max($aValues));
        }

        return false;
    }

    public function _checkIsLow($mNum, $aValues)
    {
        if ($this->_checkIsNum($mNum)) {
            return $mNum < min($aValues);
        }

        return false;
    }

    public function _checkIsHigh($mNum, $aValues)
    {
        if ($this->_checkIsNum($mNum)) {
            return $mNum > max($aValues);
        }

        return false;
    }

    public function _checkIsFloat($mNum)
    {
        $split = explode('\.', $mNum);

        if (2 == count($split)) {
            if (ctype_digit($split[0]) && ctype_digit($split[1])) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
