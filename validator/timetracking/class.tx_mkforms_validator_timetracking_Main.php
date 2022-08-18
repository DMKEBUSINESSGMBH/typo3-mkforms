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
 * tx_mkforms_validator_timetracking_Main.
 *
 * @author          Hannes Bochmann
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
class tx_mkforms_validator_timetracking_Main extends formidable_mainvalidator
{
    /**
     * @param formidable_mainrenderlet $renderlet
     *
     * @return bool
     */
    public function validate(&$renderlet)
    {
        $validationKeys = array_keys($this->_navConf('/'));
        reset($validationKeys);

        foreach ($validationKeys as $validationKey) {
            if ($renderlet->hasError() || !$this->canValidate($renderlet, $validationKey, $renderlet->getValue())) {
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
     * @param string                   $validationKey
     * @param formidable_mainrenderlet $renderlet
     *
     * @return bool
     */
    protected function formSendTooFast($validationKey, formidable_mainrenderlet $renderlet)
    {
        $formSendTooFast = false;
        if ('t' === $validationKey[0] &&
            \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($validationKey, 'toofast')
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
     * @param string                   $validationKey
     * @param formidable_mainrenderlet $renderlet
     *
     * @return bool
     */
    protected function formSendTooSlow($validationKey, formidable_mainrenderlet $renderlet)
    {
        $formSendTooSlow = false;
        if ('t' === $validationKey[0] &&
            \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($validationKey, 'tooslow')
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
     * @param string                   $validationKey
     * @param formidable_mainrenderlet $renderlet
     */
    protected function declareValidationErrorByValidationKey(
        $validationKey,
        formidable_mainrenderlet $renderlet
    ) {
        $this->getForm()->_declareValidationError(
            $renderlet->getAbsName(),
            'TIMERACKING:'.$validationKey,
            $this->getForm()->getConfigXML()->getLLLabel(
                $this->_navConf('/'.$validationKey.'/message')
            )
        );
    }

    /**
     * @param string $validationKey
     *
     * @return int
     *
     * @throws InvalidArgumentException
     */
    protected function getThresholdByValidationKey($validationKey)
    {
        $threshold = $this->_navConf('/'.$validationKey.'/threshold');
        if ($this->getForm()->isRunneable($threshold)) {
            $threshold = $this->getForm()->getRunnable()->callRunnableWidget($this, $threshold);
        }

        if (!$threshold) {
            throw new InvalidArgumentException('Please provide the threshold parameter for the tooFast validation');
        }

        return $threshold;
    }

    public function handleAfterRenderCheckPoint()
    {
        // When the plugin is cached it makes no save the creation timestamp.
        // Otherwise we would create a fe_user session which might for example
        // not be desired when using a proxy cache like varnish as a fe_typo_user
        // cookie would be created. Furthermore it would lead to exceptions after
        // the first submit for all users but the first one as the creation timestamp
        // submitted could never be correct.
        if ($this->getForm()->getConfigurations()->isPluginUserInt()) {
            $sessionData = $GLOBALS['TSFE']->fe_user->getKey('ses', 'mkforms');
            $sessionData['creationTimestamp'][$this->getForm()->getFormId()] = $GLOBALS['EXEC_TIME'];
            $GLOBALS['TSFE']->fe_user->setKey('ses', 'mkforms', $sessionData);
            $GLOBALS['TSFE']->fe_user->storeSessionData();
        }
    }
}
