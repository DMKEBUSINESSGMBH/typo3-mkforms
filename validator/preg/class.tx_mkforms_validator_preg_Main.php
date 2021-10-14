<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 J�r�my Lecour (jeremy.lecour@nurungrandsud.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Plugin 'va_preg' for the 'ameos_formidable' extension.
 *
 * @author  J�r�my Lecour <jeremy.lecour@nurungrandsud.com>
 */
class tx_mkforms_validator_preg_Main extends formidable_mainvalidator
{
    /**
     * Beispiel Regex:.
     *
     * keine Zahlen erlaubt: !/[0-9]/
     * nur Zahlen erlaubt: /[0-9]/
     *
     * (non-PHPdoc)
     *
     * @see formidable_mainvalidator::validate()
     */
    public function validate(&$oRdt)
    {
        $sAbsName = $oRdt->getAbsName();
        $sValue = $oRdt->getValue();

        if ('' === $sValue) {
            // never evaluate if value is empty
            // as this is left to STANDARD:required
            return;
        }

        $aKeys = array_keys($this->getConfigValue('/'));
        reset($aKeys);
        foreach ($aKeys as $sKey) {
            // Prüfen ob eine Validierung aufgrund des Dependson Flags statt finden soll
            if ($oRdt->hasError() || !$this->canValidate($oRdt, $sKey, $sValue)) {
                break;
            }

            //pattern
            if ('p' === $sKey[0] && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($sKey, 'pattern')) {
                $sPattern = $this->getConfigValue('/'.$sKey.'/value');

                if (!$this->_isValid($sPattern)) {
                    $this->oForm->mayday('<b>validator:PREG</b> on renderlet '.$sAbsName.': the given regular expression pattern seems to be not valid');
                }

                if (!$this->_isMatch($sPattern, $sValue)) {
                    $this->oForm->_declareValidationError(
                        $sAbsName,
                        'PREG:pattern',
                        $this->oForm->getConfigXML()->getLLLabel($this->getConfigValue('/'.$sKey.'/message'))
                    );

                    break;
                }
            }
        }
    }

    public function _isValid($sPattern)
    {
        return preg_match('/!*\/[^\/]+\//', $sPattern);
    }

    public function _isMatch($sPattern, $value)
    {
        if ('' == $value) {
            return true;
        }
        if ('!' == $sPattern[0]) {
            return !preg_match(substr($sPattern, 1), $value);
        }

        return preg_match($sPattern, $value);
    }
}
