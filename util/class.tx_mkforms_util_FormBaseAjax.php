<?php
/**
 * @author Michael Wagner
 *
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
 * Some static util functions für ajax calls.
 *
 * @author Michael Wagner
 */
class tx_mkforms_util_FormBaseAjax extends tx_mkforms_util_FormBase
{
    /**
     * Fügt einen Task zum Parameter majixActionsAfterFinish hinzu.
     *
     * @param array  $params
     * @param string $renderletName
     * @param string $majixAction
     *
     * @return array $params
     */
    public static function addMajixAction(array $params, $renderletName, $majixAction, $prepend = false)
    {
        if (isset($params['majixActionsAfterFinish'])) {
            if ($prepend) {
                $params['majixActionsAfterFinish'] = ' , '.$params['majixActionsAfterFinish'];
            } else {
                $params['majixActionsAfterFinish'] .= ' , ';
            }
        } else {
            $params['majixActionsAfterFinish'] = '';
        }

        if ($prepend) {
            $params['majixActionsAfterFinish'] = $renderletName.'|'.$majixAction.$params['majixActionsAfterFinish'];
        } else {
            $params['majixActionsAfterFinish'] .= $renderletName.'|'.$majixAction;
        }

        return $params;
    }

    /**
     * Explode given param into options (separated by ',') and suboptions (separated by '|').
     *
     * (Optional) suboptions are returned as array of the respective main option.
     *
     * @TODO: auslagern!?
     *
     * @param string $param
     * @param bool   $forceSubOptionArray Force sub options to be an array, even if only one suboption exists
     *
     * @return array
     */
    public static function explodeParam($param, $forceSubOptionArray = false)
    {
        $result = [];
        $foo = preg_split('/[\s]*,[\s]*/', $param, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($foo as $f) {
            $bar = \Sys25\RnBase\Utility\Strings::trimExplode('|', $f);
            // Return suboptions as array, but remain scalar value if no suboption was identified
            $result[] = ($forceSubOptionArray || count($bar) > 1) ? $bar : $bar[0];
        }

        return $result;
    }

    /**
     * Build ajax return values according to params.
     *
     * The following option(s) in $params are evaluated:
     * * 'majixActionsAfterFinish':
     *      Comma-separated list of renderletName|majixAction tuples (separated by pipe "|", e.g. "success|displayBlock"),
     *      with the following parts:
     *      * renderletName:    Name of the renderlet on which the given action is to be performed. Does not need
     *                          to be fully qualified according to usual mkforms getWidget() behaviour.
     *      * setStatusMessage  Refreshes the StatusMessage
     *                          example: <param get="setStatusMessage::status__statusSaved|LLL:EXT:mkhogafe/locallang.xml:header_form_saved"/>
     *      * majixAction:      Name of method to be executed -
     *
     * @see formidable_mainrenderlet->majix* functions
     *                          Give names like "displayNone", "hidden" etc. in order to call "majixDisplayNone()",
     *                          "majixHidden()" etc. respectively.
     *                          Additionally evaluated function name:
     *                          * "refresh":    Refreshing implies updating values (using $data matching by field-name!)
     *                                          and repainting the given renderlet.
     *                                          NOTICE: For this option only FULLY QUALIFIED renderlet names are allowed!
     *
     * @param array              $params
     * @param tx_ameosformidable $form
     * @param array              &$data  needed for refresh
     *
     * @return array
     */
    protected static function buildAjaxReturn(array $params, tx_ameosformidable $form, array &$data = [])
    {
        $return = [];

        /*
         * Aktualisiert die Statusnachicht mit der Erfolgsmeldung.
         * <param get="setStatusMessage::status__statusSaved|LLL:label_form_saved" />
         */
        foreach (self::explodeParam($params['setStatusMessage']) as $message) {
            if ($widget = $form->getWidget($message[0])) {
                if (!empty($message[1])) { // wenn eine nachricht enthalten, ggf LLL ersetzen und dann ausgeben
                    self::setValueToWidget($widget, $form->getConfig()->getLLLabel($message[1]), $widget->_isSubmitted());
                }
                $return[] = $widget->majixRepaint();
            }
        }

        // Die einzelnen actions auslesen
        $displayActions = [];
        foreach (self::explodeParam($params['majixActionsAfterFinish']) as $option) {
            $displayActions[] = [
                                        'renderlet' => $option[0],
                                        'command' => $option[1],
                                        'params' => isset($option[2]) ? [$option[2]] : [],
                                        'conditions' => isset($option[3]) ? $option[3] : null,
                                ];
        }
        // actions rendern
        foreach ($displayActions as $action) {
            switch ($action['renderlet']) {
                case '__form__':
                    // die action der form aufrufen
                    $return[] = call_user_func_array(
                        [
                                                        $form,
                                                        'majix'.ucfirst($action['command']),
                                                    ],
                        $action['params']
                    );

                    break;

                default:
                    if (!empty($action['command']) && ($widget = $form->getWidget($action['renderlet'])) &&
                        // condition prüfen
                        (empty($action['conditions']) || $foo = self::evalSecureExpression($action['conditions'], $form))
                        ) {
                        /*
                         * 'refresh' gibt es nicht als majixAction.
                         * 'refresh' führt repaint aus, aktualisiert allerdings vorher die Daten des Renderlets
                         */
                        if ('refresh' == $action['command']) {
                            $aData = self::multipleTableStructure2DeepArray(
                                $data,
                                $form,
                                $action['renderlet']
                            );

                            if (empty($aData) && isset($params[$widget->getName()])) {
                                $aData = $params[$widget->getName()];
                            }

                            // If data available for that renderlet, fill it:
                            if (!empty($aData) || !is_array($aData)) {
                                self::setValueToWidget($widget, $aData, $widget->_isSubmitted());
                            }
                            $return[] = $widget->majixRepaint();
                        } else {
                            // Just call the requested action
                            $return[] = call_user_func_array(
                                [
                                                                $widget,
                                                                'majix'.ucfirst($action['command']),
                                                            ],
                                $action['params']
                            );
                        }
                    }
            }
        }

        return $return;
    }

    /**
     * Perform majix operations.
     *
     * Actually, this is just an alias for the protected function self::buildAjaxReturn
     * in order to give XML forms the possibility to perform majix operations as only action.
     *
     * @param array              $params
     * @param tx_ameosformidable $form
     *
     * @return string
     */
    public static function renderMajixOperations(array $params, tx_ameosformidable $form)
    {
        return self::buildAjaxReturn($params, $form);
    }

    /**
     * Saves a value to renderlet.
     *
     * if $setChildsExplizit the value was set to each child explicitly.
     *      If a Form was submitted, a setValue on Parent renderlet was ignored by MKFORMS.
     *      So we have to set every renderlet explizit.
     *
     * @param mixed $mWidget
     * @param mixed $mValue
     * @param bool  $setChildsExplizit
     */
    protected static function setValueToWidget($mWidget, $mValue, $setChildsExplizit = false)
    {
        if ($setChildsExplizit && is_array($mValue)) {
            if (is_object($mWidget)) {
                if ($mWidget->hasChilds()) {
                    // Set Value for Childs renderlets
                    self::setValueToWidget($mWidget->getChilds(), $mValue, $setChildsExplizit);
                } elseif (array_key_exists($mWidget->getName(), $mValue)) {
                    // Set Value if exist
                    $mWidget->setValue($mValue[$mWidget->getName()]);
                }
            } elseif (is_array($mWidget)) {
                // Set Value of all Childs renderlets
                foreach ($mWidget as $oWidget) {
                    if (array_key_exists($oWidget->getName(), $mValue)) {
                        self::setValueToWidget($oWidget, $mValue[$oWidget->getName()], $setChildsExplizit);
                    } else {
                        self::setValueToWidget($oWidget, $mValue, $setChildsExplizit);
                    }
                }
            }
        } elseif (is_object($mWidget)) {
            // Set Value without ignoreSubmit
            $mWidget->setValue($mValue);
        }
    }

    /**
     * Form instance stored for regex callback functions.
     *
     * @var tx_ameosformidable
     */
    private static $evalFormContainer;

    /**
     * Evaluate secure expression.
     *
     * This function evaluates expressions mainly defined in xml forms.
     * Usual PHP syntax for easy expressions is supported, but no function
     * calls are allowed.
     *
     * Note that no deeper SYNTAX checks (e.g. matching opening and closing brackets)
     * are performed - only formal correctness of the given expression is checked!
     *
     * Values within the expression to be used are masked the following ways:
     * * rdt:{renderlet_name}:          value of renderlet with the given name, e.g. rdt:box__uid
     * * val:100 or val:'some string':  scalar value
     *
     * The following examples are evaluated successfully (not necessarily TRUE!):
     * * "rdt:add__blah", "(rdt:add__blah)", "rdt:add__blah != val:123", "(val:123)!=(val:12.3)",
     * * "val:123 == val:123", "(val:123)!=(val:12.3E12)", "(val:123)!=(val:12.3E-3)",
     * * "rdt:add__blah != (val:123)", "(rdt:add__blah) != val:123",
     * * "(rdt:add__blah) != (val:123 or val:123)", "rdt:add__blah + val:123",
     * * "rdt:add__blah != val:123 + val:456", "val:456 + rdt:add__blah != val:123 * val:456",
     * * "rdt:add__blah == !val:123 + val:456", "rdt:add__blah != val:\'foobar \'",
     * * "rdt:add__blah != val:+123", "rdt:add__blah or val:+123",',
     * * "! rdt:add__blah == !val:123 + val:456", "! rdt:add__blah != val:\'foobar \'",
     * * "!( rdt:add__blah == rdt:add_blubb)",
     * * "(rdt:add__blah == rdt:add_blubb) or (rdt:add__blah >= rdt:add_blubb)",
     * * "(rdt:add__blah == rdt:add_blubb) xor !(rdt:add__blah >= rdt:add_blubb)",
     * * "(rdt:add__blah == rdt:add_blubb) xor !rdt:add__blah >= rdt:add_blubb",
     * * "(rdt:add__blah == rdt:add_blubb) or ((rdt:add__blah >= rdt:add_blubb) and (rdt:add__blah >= val:123))",
     * * "(rdt:add__blah == rdt:add_blubb) AND (rdt:add__blah >= rdt:add_blubb)"
     * while the following don't, as they could not be parsed correctly:
     * * "rdt:foo==10" (missing 'val' -> 'val:10'),
     * * "! rdt:add__blah != val:+a123" (invalid integer value)
     * * "rdt:add__blah == rdt:add_blubbandrdt:add__blah != val:123" (missing spaces)
     *
     * @param string             $expression
     * @param tx_ameosformidable $form
     *
     * @return mixed
     */
    protected function evalSecureExpression($expression, tx_ameosformidable $form)
    {
        // Pattern parts

        $pat_spc = '[\s]*?';                        // Optional space
        $pat_neg = '(\!?'.$pat_spc.')';            // Optional logical negator

        // Optional brackets
        $pat_brcksO = '(\(*'.$pat_spc.')';
        $pat_brcksC = '('.$pat_spc.'\)*)';

        // Operators
        $pat_opCmp = '==|\!=|<>|>=|<=';                // Comparison operators
        $pat_opLgc = ' and | or | xor ';            // Logical operators
        $pat_opArit = '\+|\-|\*|\/';                // Arithmetic operators
        // All operators
        $pat_op = $pat_spc.'('.$pat_opCmp.'|'.$pat_opArit.'|'.$pat_opLgc.')'.$pat_spc;

        // Expression values
        $pat_rdt = '(rdt\:([\w\-_]+))';                // Renderlet
        $pat_sclr = '(val\:([\+\-]?[\d\.]+(E[\+\-]?[\d\.]+)?|\'.*?\'))';
        // Scalar value (float or 'string')
        // All expression values
        $pat_val = '('.$pat_neg.'('.$pat_sclr.'|'.$pat_rdt.'))';

        // Complete expression value with negator and brackets
        $pat_valCmpl = $pat_spc.$pat_neg.$pat_brcksO.$pat_neg.$pat_val.$pat_brcksC;

        // Arbitrary logical combinations of expression values
        $pattern = $pat_valCmpl.'('.$pat_op.$pat_valCmpl.')*?';

        $matches = [];
        if (preg_match('/^'.$pattern.'$/i', $expression, $matches)) {
            $expr = $matches[0];
            self::$evalFormContainer = $form;
            // Replace expression values
            $expr = preg_replace_callback($pat_sclr, [self, 'replaceValue'], $expr);
            $expr = preg_replace_callback($pat_rdt, [self, 'replaceRdt'], $expr);
        } else {
            throw new Exception('tx_mkhogafe_forms_php_base->evalSecureExpression(): invalid / insecure expression!');
        }
        // Return evaluated expression
        return eval('return '.$expr.';');
    }

    private function replaceValue($val)
    {
        return $val[1];
    }

    private function replaceRdt($val)
    {
        if (is_object($w = self::$evalFormContainer->getWidget($val[1]))) {
            $wVal = $w->getValue();
            if (is_numeric($wVal)) {
                return $wVal;
            }

            return '\''.$wVal.'\'';
        } // Given widget not found
        else {
            return null;
        }
    }

    /**
     * Repaint dependent field.
     *
     * example
     *  <onchange runat="ajax" syncValue="true" cache="false">
     *      <userobj extension="tx_mkhogafe_util_FormTool" method="repaintDependencies">
     *          <params><param name="me" value="wish-div__wish__fields__jobreqs-job" /></params>
     *      </userobj>
     *  </onchange>
     *
     * example for dependencies in a lister:
     *  <codeBehind type="php" name="formTool"path="EXT:mkhogafe/util/class.tx_mkhogafe_util_FormTool.php:tx_mkhogafe_util_FormTool"/>
     *  <onchange
     *      runat="ajax"
     *      cache="false"
     *      exec="formTool.repaintDependencies()"
     *      >
     *      <params>
     *          <param get="rowData::joboffers-uid" />
     *          <param get="uidParam::joboffers-uid" />
     *          <param get="me::joboffers-div__joboffers__joboffers-list__joboffers-job" />
     *      </params>
     *  </onchange>
     *
     * @param array              $params
     * @param tx_ameosformidable $form
     */
    public static function repaintDependencies(array $params, tx_ameosformidable $form)
    {
        if (!isset($params['me'])) {
            return;
        }
        if ($widget = $form->getWidget($params['me'])) {
            if (isset($params['uidParam']) && isset($params[$params['uidParam']])) {
                $widget->setIteratingId($params[$params['uidParam']]);
            }
            if (isset($params['sys_syncvalue'])) {
                $widget->setValue($params['sys_syncvalue']);
            }

            return $widget->majixRepaintDependancies();
        }
    }

    /**
     * Merge still FLATTENED(!) data of several tables into a deep renderlet structure.
     *
     * Resulting field name format: tablename-fieldname, with "-" being the given $sep.
     * Fields defined in more than one table are represented multiple:
     * * Once per table:
     *   * table1-fieldname
     *   * table2-fieldname
     *   * etc.
     * * Additionally in a key named like this: table1-table2-table3-fieldname
     *   * Table names are sorted alphabetically!
     *   * If more than one table returns a non-empty value, the first non-empty one
     *     is used where "first" is based on the order of the tables in $data.
     *
     * ### Note that fields from several tables with identical field names
     *      overwrite each other in the multiple table field! ###
     *
     * @param array                    $srcData         Flat array of data
     * @param tx_ameosformidable       $form
     * @param formidable_mainrenderlet $targetRenderlet The target renderlet instance
     *
     * @return array
     */
    public static function convertFlatDataToRenderletStructure(
        array $srcData,
        tx_ameosformidable $form,
        formidable_mainrenderlet $targetRenderlet
    ) {
        $res = [];
        $trName = $targetRenderlet->getName();
        // Is data available for that special renderlet field?
        // Just return this scalar value!
        if (array_key_exists($trName, $srcData)) {
            return $srcData[$trName];
        }
        // else

        foreach ($targetRenderlet->aChilds as $child) {
            $childData = self::convertFlatDataToRenderletStructure($srcData, $form, $child);
            if (!is_null($childData)) {
                $res[$child->getName()] = $childData;
            }
        }
        if (empty($res)) {
            return null;
        }

        return $res;
    }

    /**
     * Prüft ob das dependsonflag gesetzt ist und gibt das widget davon zurück.
     *
     * @param $params
     * @param $form
     *
     * @return widget
     *
     * @throws tx_mklib_exception_InvalidConfiguration
     */
    public static function checkDependsOnFlag(array $params, $form)
    {
        // Der Validator wird nur ausgeführt, wenn das Flag-Widget einen Wert hat.
        if (empty($params['dependsonflag'])) {
            throw \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mklib_exception_InvalidConfiguration', __METHOD__.': Der Parameter $params[\'dependsonflag\'] wurde nicht gesetzt!');
        }

        return $form->getWidget($params['dependsonflag']);
    }
}
