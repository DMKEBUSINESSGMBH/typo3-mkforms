<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2015 DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
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
 *
 * tx_mkforms_validator_timetracking_Main
 *
 * @package         TYPO3
 * @subpackage      mkforms
 * @author          Hannes Bochmann
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
class tx_mkforms_validator_timetracking_Main extends formidable_mainvalidator
{

    /**
     * @param   formidable_mainrenderlet $renderlet
     * @return  boolean
     */
    public function validate(formidable_mainrenderlet $renderlet)
    {
        $validationKeys = array_keys($this->_navConf('/'));
        reset($validationKeys);

        while (!$renderlet->hasError() && list(, $validationKey) = each($validationKeys)) {
            if (!$this->canValidate($renderlet, $validationKey, $renderlet->getValue())) {
                break;
            }

            if ($this->formSendTooFast($validationKey, $renderlet)) {
                break;
            }

            if ($this->formSendTooSlow($validationKey, $renderlet)) {
                break;
            }
        }
    }

    /**
     * @param string $validationKey
     * @param formidable_mainrenderlet $renderlet
     * @return boolean
     */
    protected function formSendTooFast($validationKey, formidable_mainrenderlet $renderlet)
    {
        $formSendTooFast = false;
        if ($validationKey{0} === 't' &&
            Tx_Rnbase_Utility_Strings::isFirstPartOfStr($validationKey, 'toofast')
        ) {
            $timeNeededToSendForm = $GLOBALS['EXEC_TIME'] - $this->getForm()->getCreationTimestamp();
            if ($timeNeededToSendForm < $this->getThresholdByValidationKey($validationKey)) {
                $this->declareValidationErrorByValidationKey($validationKey, $renderlet);
                $formSendTooFast = true;
            }
        }

        return $formSendTooFast;
    }

    /**
     * @param string $validationKey
     * @param formidable_mainrenderlet $renderlet
     * @return boolean
     */
    protected function formSendTooSlow($validationKey, formidable_mainrenderlet $renderlet)
    {
        $formSendTooSlow = false;
        if ($validationKey{0} === 't' &&
            Tx_Rnbase_Utility_Strings::isFirstPartOfStr($validationKey, 'tooslow')
        ) {
            if ($this->getForm()->getCreationTimestamp() <
                ($GLOBALS['EXEC_TIME'] - $this->getThresholdByValidationKey($validationKey))
            ) {
                $this->declareValidationErrorByValidationKey($validationKey, $renderlet);
                $formSendTooSlow = true;
            }
        }

        return $formSendTooSlow;
    }

    /**
     * @param string $validationKey
     * @param formidable_mainrenderlet $renderlet
     */
    protected function declareValidationErrorByValidationKey(
        $validationKey,
        formidable_mainrenderlet $renderlet
    ) {
        $this->getForm()->_declareValidationError(
            $renderlet->getAbsName(),
            'TIMERACKING:' . $validationKey,
            $this->getForm()->getConfigXML()->getLLLabel(
                $this->_navConf('/' . $validationKey . '/message')
            )
        );
    }

    /**
     *
     * @param string $validationKey
     * @throws InvalidArgumentException
     *
     * @return int
     */
    protected function getThresholdByValidationKey($validationKey)
    {
        if (!$threshold = $this->_navConf('/' . $validationKey . '/threshold')) {
            throw new InvalidArgumentException(
                'Please provide the threshold parameter for the tooFast validation'
            );
        }

        return $threshold;
    }
}
