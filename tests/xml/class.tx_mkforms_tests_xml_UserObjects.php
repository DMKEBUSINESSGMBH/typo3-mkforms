<?php
/**
 *  Copyright notice
 *
 *  (c) 2011 Michael Wagner <dev@dmk-business.de>
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
 * zum Testen von ajaxcalls in renderlets.xml im FE
 */
class tx_mkforms_tests_xml_UserObjects extends tx_mkforms_util_FormBaseAjax
{

    /**
     * Gibt die Parameter aus.
     *
     * @param array                 $params
     * @param tx_ameosformidable    $form
     * @return string
     */
    public function getParams4Ajax(array $params, tx_ameosformidable $form)
    {
        if ($params['flatten']) {
            $params['flatten'] = self::flatArray2MultipleTableStructure($params, $form);
        }
        if ($params['multiple'] && $params['flatten']) {
            $params['multiple'] = self::multipleTableStructure2FlatArray($params['flatten'], $form);
        }
        if ($params['deep'] && $params['flatten']) {
            $params['deep'] = self::multipleTableStructure2DeepArray($params['flatten'], $form, 'fieldset__texte');
        }
        $params['flatten'] = $params['flatten'] ? $params['flatten'] : array();
        $params['flatten']['textarea'] = 'neuer langer Teste Text für die Textarea';
        tx_mkforms_util_Div::debug4ajax($params, 'DEBUG: '.__METHOD__.' Line: '.__LINE__); // @TODO: remove me

        return self::buildAjaxReturn($params, $form, $params['flatten']);
    }
}
