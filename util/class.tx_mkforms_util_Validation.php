<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 René Nitzsche (dev@dmk-business.de)
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
 * Verarbeitet die Validierung eines Formulars
 */
class tx_mkforms_util_Validation
{
    private $form;

    private function __construct($form)
    {
        $this->form = $form;
    }


    /**
     * Liefert das Formular
     *
     * @return tx_ameosformidable
     */
    private function getForm()
    {
        return $this->form;
    }

    /**
     * Validiert ein Set von Widgets. Als Parameter wird ein Namen der Widgets erwartet. Sollte ein Name keinem
     * Widget entsprechend, dann wird es ignoriert.
     *
     * @param array $widgetNames ein Array (widgetName => value)
     * @return array Array der Fehler oder ein leeres Array
     */
    public function validateWidgets4Ajax($widgetNames)
    {
        $this->getForm()->clearValidationErrors();
        $widgets = [];

        // erstmal den neuen Wert setzen
        foreach ($widgetNames as $name => $value) {
            $widget = $this->getForm()->getWidget($name);
            if (!$widget || !$widget->isVisibleBecauseDependancyEmpty()) {
                continue;
            }
            $widget->cancelError();
            $widget->setValue($value);
            $widgets[] = $widget;
        }

        foreach ($widgets as $widget) {
            $widget->validate();
        }

        return $this->isAllValid() ? [] : $this->getForm()->_aValidationErrorsByHtmlId;
    }
    /**
     * Prüft, ob Validierungsfehler von Renderlets vorliegen. Das funktioniert aber erst, wenn
     * eine Validierung gestartet wurde.
     * @return bool
     */
    public function isAllValid()
    {
        return (count($this->getForm()->_aValidationErrors) == 0);
    }

    /**
     * @param tx_mkforms_forms_IForm $form
     * @return tx_mkforms_util_Validation
     */
    public static function createInstance(tx_mkforms_forms_IForm $form)
    {
        $runnable = new tx_mkforms_util_Validation($form);

        return $runnable;
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/util/class.tx_mkforms_util_Validation.php']) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/util/class.tx_mkforms_util_Validation.php']);
}
