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
 * Some static util functions.
 *
 * @author Michael Wagner
 */
class tx_mkforms_util_FormBase
{
    /**
     * Liefert das Parent-Objekt.
     * Wahlweise wird nach einer existierenden Methode geprüft.
     *
     * @param tx_ameosformidable $form
     * @param string             $method
     *
     * @return tx_mkforms_action_FormBase
     */
    protected static function getParent(tx_ameosformidable $form, $method = false)
    {
        $oParent = $form->getParent();
        if ($method && (!is_object($oParent) || !method_exists($oParent, $method) || !is_callable([$oParent, $method]))) {
            return null;
        }

        return $oParent;
    }

    /**
     * Der Datahandler!
     *
     * Wenn in einem Formular ein Datahandler mittels this->fillform integriert wurde,
     * (@see tx_mkforms_action_FormBase::fillForm)
     * Kann this bei AjaxCalls nicht mehr gefunden werden.
     * Somit wird der Datahandler nicht aufgerufen werden uns es wid kein Array geliefert.
     * Wir müssen also den Datahandler abfangen,
     * schauen ob Parent gesetzt ist (die Action)
     * und entsprechend fillForm aufrufen.
     *
     * @param array              $params
     * @param tx_ameosformidable $form
     *
     * @return array
     */
    public function fillForm(array $params, tx_ameosformidable $form)
    {
        return is_object($oParent = self::getParent($form, 'fillForm')) ? $oParent->fillForm($params, $form) : [];
    }

    /**
     * ruft processForm auf, wenn ein Parent object gesetzt ist.
     *
     * @param array              $params
     * @param tx_ameosformidable $form
     */
    public function processForm(array $params, tx_mkforms_forms_Base $form)
    {
        if (is_object($oParent = self::getParent($form, 'processForm'))) {
            $oParent->processForm($params, $form);
        }
    }

    /**
     * Useful to render fal image in IMAGE widget:
     * <renderlet:IMAGE name="uploadfield1" treatIdAsReference="true">
     *   <path>
     *     <userobj extension="tx_mkforms_util_FormBase" method="loadFirstReferenceUid">
     *        <params>
     *          <param name="tablename" value="tx_myext_table" />
     *          <param name="refField" value="logo" />
     *        </params>
     *       </userobj>
     *   </path>.
     *
     * @param array                 $params
     * @param tx_mkforms_forms_Base $form
     *
     * @return number
     */
    public function loadFirstReferenceUid(array $params, tx_mkforms_forms_Base $form)
    {
        $refTable = $params['tablename'];
        $refUid = $params['uid'];
        $refField = $params['refField'];
        $refUid = $refUid ? $refUid : $form->getDataHandler()->entryId;
        if (!$refUid) {
            return null;
        }

        $ref = \Sys25\RnBase\Utility\TSFAL::getFirstReference($refTable, $refUid, $refField);
        if (is_object($ref)) {
            return $ref->getUid();
        }

        return null;
    }

    /**
     * Flatten a deep array to one single level.
     *
     * This method traverses an array recursively and
     * simply collects all fields.
     *
     * Note that there is no collision protection,
     * meaning that fields with the exactly same name
     * overwrite each other.
     *
     * This however is accepted as there is usually no
     * reason to use one single, fully table-qualified
     * field multiple.
     *
     * @param array $data
     * @param array $aWidgetNames
     *
     * @return array
     */
    public static function flattenArray($data, $aWidgetNames = false)
    {
        $flattenData = [];
        foreach ($data as $key => $value) {
            if (is_array($value) && (false === $aWidgetNames || !in_array($key, $aWidgetNames))) {
                $value = self::flattenArray($value, $aWidgetNames);
                $flattenData = array_merge($flattenData, $value);
            } else {
                $flattenData[$key] = $value;
            }
        }

        return $flattenData;
    }

    /**
     * Liefert das Konfigurations-Objekt.
     *
     * @param tx_ameosformidable       $form
     * @param Sys25\RnBase\Configuration\Processor $configurations
     *
     * @return Sys25\RnBase\Configuration\Processor
     */
    protected static function getConfigurations(tx_ameosformidable $form, Sys25\RnBase\Configuration\Processor $configurations = null)
    {
        if (!is_object($configurations)) {
            $configurations = (is_object($oParent = self::getParent($form, 'getConfiguration'))) ? $oParent->getConfiguration() : $form->getConfigurations();
        }

        return $configurations;
    }

    /**
     * Liefert die confId ohne formconf.
     *
     * @param tx_ameosformidable $form
     * @param string             $confId
     *
     * @return string
     */
    protected static function getConfId(tx_ameosformidable &$form, &$confId = '')
    {
        if (empty($confId)) {
            $confId = (is_object($oParent = self::getParent($form, 'getConfId'))) ? $oParent->getConfId() : $form->getConfId();
            // formconfig. abschneiden, wenn die id vom formobjekt geholt wird!!!
            if (false !== strpos($confId, 'formconfig.')) {
                $confId = substr($confId, 0, strlen($confId) - strlen('formconfig.'));
            }
        }

        return $confId;
    }

    /**
     * @param Sys25\RnBase\Configuration\Processor $configurations
     * @param string                   $confId
     *
     * @return string
     */
    protected static function getFieldSeparator(Sys25\RnBase\Configuration\Processor &$configurations, &$confId)
    {
        $separator = $configurations->get($confId.'fieldSeparator');

        return $separator ? $separator : '-';
    }

    /**
     * Convert flat array of data (e.g. received on a save event) into an array of subarrays
     * with each subarray containing a table's data.
     *
     * @param array                    $data
     * @param tx_ameosformidable       $form
     * @param Sys25\RnBase\Configuration\Processor $configurations
     * @param string                   $confId
     *
     * @return array
     */
    public static function flatArray2MultipleTableStructure(
        array $data,
        tx_ameosformidable &$form,
        Sys25\RnBase\Configuration\Processor &$configurations = null,
        $confId = ''
    ) {
        // Konfiguration und Id besorgen, falls nicht übergeben.
        $configurations = self::getConfigurations($form, $configurations);
        $confId = self::getConfId($form, $confId);

        $aWidgets = $flattenData = [];

        /*
         * Wir müssen die widgets durchgehen,
         * da wir ein Flaches Array benötigen,
         * aber Boxen vorhanden sein können.
         * 		ist: 	box1 -> box2 -> feld -> wert
         * 		soll: 	feld -> wert
         * @TODO: 	was ist besser, (performance testen!)
         * 			auf das Widget ein getValue um den Wert zu bekommen
         * 			oder flattenArray aufrufen, um die daten aus $data zu holen.
         *
         * 		Vorteile getValue():
         * 			# wird eh für zusätzliche angaben etc benötigt (datum, submit)
         * 			# es werden auch felder angegeben, welche leer sind, checkboxen etc.
         * 		Nachteile getValue():
         * 			# Felder aus Listern werden doppelt ausgegeben. einmal im lister und einmal im root.
         */
        $sFieldSeparator = AMEOSFORMIDABLE_NESTED_SEPARATOR_END.AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN;
        foreach ($form->getWidgetNames() as $sWidget) {
            $oWidget = $form->getWidget($sWidget);
            // Wenn keine Kindselemente oder ein Lister
            if (!$oWidget->hasChilds() || $oWidget->iteratingChilds) {
                $aWidget = explode($sFieldSeparator, $sWidget);
                $sWidget = end($aWidget);
                // wird nur gebraucht, falls flattenArray aufgerufen wird
                $aWidgets[] = $sWidget;
                $mValue = $oWidget->getValue();
                // Wurde ein Button gedrückt? welcher?
                if ($oWidget->isNaturalSubmitter() && $mValue) {
                    $flattenData['submitmode'] = $oWidget->getSubmitMode();
                    $form->setSubmitter($sWidget, $flattenData['submitmode']);
                } // Datumsfelder zusätzlich für MySQL konvertieren!
                elseif ('Date' == $oWidget->sMajixClass && $mValue) {
                    $flattenData[$sWidget.'_mysql'] = \Sys25\RnBase\Utility\Dates::date_tstamp2mysql($mValue);
                }
                // den Wert holen
                // @TODO: siehe todo oben
                $flattenData[$sWidget] = $mValue;
            }
        }

        /* Beispiel:
         * confid.addfields {
         * 		### setzt den wert title, wenn er noch nicht existiert
         * 		tabelle-title = neue Tabelle
         * 		### setzt den wert draft oder überschreibt ihn.
         * 		tabelle-draft = 1
         * 		tabelle-draft.override = 1
         * 		### entfernt den Wert. 'unset' führt unset() aus, 'null' setzt den Wert auf null
         * 		tabelle-uid = unset
         * }
         */
        $aAddFields = $configurations->get($confId.'addfields.', true);
        // Felder setzen, überschreiben oder löschen
        if (is_array($aAddFields) && count($aAddFields)) {
            $flattenData = self::addFields($flattenData, $aAddFields);
        }

        if ($configurations->get($confId.'addPostVars', true) || ($data['addPostVars'] ?? false)) {
            $flattenData['addpostvars'] = $form->getAddPostVars();
        }

        $separator = self::getFieldSeparator($configurations, $confId);
        // Die Felder in Ihre Tabellennamen aufsplitten
        $flattenData = self::explodeArrayWithArrayKeys($flattenData, $separator);

        return $flattenData;
    }

    /**
     * Convert data of several tables into a flat array of fields, e.g. used by data handlers.
     *
     * @param array $data   Data of several tables, each table's data in an own subarray
     * @param array $params parameters defining conversion options ('dateFields') etc
     *
     * @return array
     */
    public static function multipleTableStructure2FlatArray(
        array $data,
        tx_ameosformidable &$form,
        Sys25\RnBase\Configuration\Processor &$configurations = null,
        $confId = ''
    ) {
        // Konfiguration und Id besorgen, falls nicht übergeben.
        $configurations = self::getConfigurations($form, $configurations);
        $confId = self::getConfId($form, $confId);

        $separator = self::getFieldSeparator($configurations, $confId);
        // @TODO: Werte für beispielsweise die checkbox müssen kommasepariert für den datahandler angegeben werden.
        $flattenData = self::flattenMultipleArrays($data, $separator);

        // @see self::flatArray2MultipleTableStructure -> addfields
        $aAddFields = $configurations->get($confId.'addfields.', true);
        // Felder setzen, überschreiben oder löschen
        if (is_array($aAddFields) && count($aAddFields)) {
            $flattenData = self::addFields($flattenData, $aAddFields);
        }

        return $flattenData;
    }

    /**
     * Convert data of several tables into a deep array of fields to deeply fill a renderlet.
     *
     * @param array              $data             Data of several tables, each table's data in an own subarray
     * @param tx_ameosformidable $form
     * @param mixed              $mTargetRenderlet Kann das Objekt oder den Namen enthalten
     *
     * @return array
     */
    public static function multipleTableStructure2DeepArray(array $data, tx_ameosformidable $form, $mTargetRenderlet = false)
    {
        if (is_string($mTargetRenderlet)) {
            $mTargetRenderlet = $form->getWidget($mTargetRenderlet);
        }
        if (!is_object($mTargetRenderlet)) {
            return [];
        }
        $data = self::multipleTableStructure2FlatArray($data, $form);
        $data = self::convertFlatDataToRenderletStructure(
            $data,
            $form,
            $mTargetRenderlet
        );

        return $data;
    }

    /**
     * Eliminate renderlet path info from the given data.
     *
     * @param array                 $renderletData
     * @param tx_mkforms_forms_Base $form
     *
     * @return array
     */
    public static function removePathFromWidgetData(array $widgetData, tx_mkforms_forms_Base $form)
    {
        $res = [];
        foreach ($widgetData as $key => $value) {
            if (is_object($widget = $form->getWidget($key))) {
                $res[$widget->getName()] = $value;
            }
        }

        return $res;
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
    private static function convertFlatDataToRenderletStructure(
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

        foreach ($targetRenderlet->getChilds() as $child) {
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
     * Splittet die Formdaten in ihre Tabellennamen auf.
     *
     * Die Tabelle steht immer vor dem Feld (tabelle-spalte)
     * Expected field name format: tablename-fieldname, with "-" being the given $sep.
     * It is also possible to give more than one table for a field: table1-table2-table3-fieldname
     *
     * @param array  $data
     * @param string $separator    wie wurde Feld von Tebelle getrennt
     * @param string $defaultTable Wenn keine Tabelle definiert ist wirk das als default. False für keine Tabelle.
     *
     * @return array Nested data array, each table with its own key
     */
    public static function explodeArrayWithArrayKeys(array $data, $separator = '-')
    {
        $splitData = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = self::explodeArrayWithArrayKeys($value, $separator);
                // bei z.B. Listern werden die Daten immer in ein zusätzliches Array geschrieben!
                if (1 === count($value) && is_numeric($key) && ($key === reset(array_keys($value)))) {
                    $value = $value[$key];
                }
            }
            $keys = explode($separator, $key);
            $fieldKey = end($keys);
            // Ein oder mehrere Tabellen im Titel
            if (count($keys) > 1) {
                // Bei z.B. Checkboxen werden die Werte immer in einen zusätzlichem Array, item-n übergeben.
                if (2 === count($keys) && is_numeric($fieldKey) && 'item' == $keys[0]) {
                    $splitData[$fieldKey] = $value;
                } // Den Feldnamen in die Tabellen umsetzen.
                else {
                    for ($i = 0; $i < count($keys) - 1; ++$i) {
                        $k = $keys[$i];
                        if (!isset($splitData[$k])) {
                            $splitData[$k] = [];
                        }
                        $splitData[$k][$fieldKey] = $value;
                    }
                }
            } // Multivalue, @TODO: es gibt doch keine Felder mit einem numerischen Namen oder?
            elseif (is_numeric($key)) {
                $splitData[$key] = $value;
            } // freies Feld
            else {
                $splitData[$fieldKey] = $value;
            }
        }

        return $splitData;
    }

    /**
     * Merge data of several arrays into one single data array.
     *
     * Resulting field name format: arrayname-fieldname, with "-" being the given $sep.
     * Fields defined in more than one array are represented multiple:
     * * Once per array:
     *   * array1-fieldname
     *   * array2-fieldname
     *   * etc.
     * * Additionally in a key named like this: array1-array2-array3-fieldname
     *   * Array names are sorted alphabetically!
     *   * If more than one array returns a non-empty value, the first non-empty one
     *     is used where "first" is based on the order of the arrays in $data.
     *
     * ### Note that fields from several arrays with identical field names
     *      overwrite each other in the multiple table field! ###
     *
     * @param array  $data      Nested data array, each array with its own key
     * @param string $separator Separator string which divides array name from field name
     *
     * @return array
     */
    public static function flattenMultipleArrays(array $data, $separator = '-')
    {
        $tmp = [];
        $res = [];
        foreach ($data as $table => $fields) {
            if (is_array($fields)) {
                foreach ($fields as $key => $value) {
                    if (!array_key_exists($key, $tmp)) {
                        $tmp[$key] = ['tables' => [], 'value' => ''];
                    }
                    $tmp[$key]['tables'][] = $table;
                    if (empty($tmp[$key]['value'])) {
                        $tmp[$key]['value'] = $value;
                    }
                    $res[$table.$separator.$key] = $value;
                }
            } // Für dieses Feld wurde keine Tabelle angegeben
            else {
                $res[$table] = $fields;
            }
        }

        foreach ($tmp as $fieldname => $data) {
            sort($data['tables']);
            /*
             * @FIXME: hier kann es bei mehr als 2 Tabellen mehrere varianten geben.
             * $data['tables'] = array('tabelle1','tabelle2','tabelle3');
             * Momentan wird nur tabelle1-tabelle2-tabelle3-feld = ausgegeben.
             * richtig wäre zusätzlich noch
             * 	tabelle1-tabelle2-feld
             * 	tabelle1-tabelle3-feld
             * 	tabelle2-tabelle3-feld
             */
            $res[implode($separator, $data['tables']).$separator.$fieldname] = $data['value'];
        }

        return $res;
    }

    /**
     * Fügt zu einem Array werte hinzu oder löscht diese.
     * Zum löschen unset oder null übergeben!
     *
     * @param array $data
     * @param array $fields
     *
     * @return rray
     */
    public static function addFields(array $data, array $fields)
    {
        foreach ($fields as $fieldKey => $fieldValue) {
            // sub config gefunden, weiter machen!
            if ('.' === substr($sPath, -1)) {
                continue;
            }
            // wenn es ein array ist, weiterleiten
            if (is_array($data[$fieldKey])) {
                $data[$fieldKey] = self::addFields($data[$fieldKey], $fields);
            } else {
                $bUnset = 'unset' == $fieldValue;
                // Der Wert soll gelöscht werden.
                if ($bUnset && isset($data[$fieldKey])) {
                    unset($data[$fieldKey]);
                } // Den Wert setzen oder Überschreiben
                elseif (!$bUnset) {
                    $bOverride = isset($fields[$fieldKey.'.']) && isset($fields[$fieldKey.'.']['override']) && ((int) $fields[$fieldKey.'.']['override'] > 0);
                    if (!isset($data[$fieldKey]) || $bOverride) {
                        $fieldValue = ('null' == $fieldValue) ? null : $fieldValue;
                        $data[$fieldKey] = $fieldValue;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Liefert die Daten aus dem gegebenen Widget für einen Lister.
     *
     * @param array              $aParams
     * @param tx_ameosformidable $oForm
     */
    public static function getListerData($aParams, $oForm)
    {
        $aStoredData = $oForm->getDataHandler()->_getStoredData($aParams['rdt']);

        return (!empty($aStoredData)) ? [$aStoredData] : null;
    }

    /**
     * Bestimmte Datensätze aus der DB auslesen.
     *
     * @param array              $params
     * @param tx_ameosformidable $form
     *
     * @todo Eigene Exceptions nutzen (nicht von mklib)
     *
     * @deprecated use tx_mkforms_util_FormFill::getRowsFromDataBase
     *
     * @return array
     */
    public static function getRowsFromDataBase(array $params, tx_ameosformidable $form)
    {
        /* @var $formfill tx_mkforms_util_FormFill */
        $formfill = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mkforms_util_FormFill');

        return $formfill->getRowsFromDataBase($params, $form);
    }

    /**
     * Bestimmte Datensätze aus der DB auslesen und diese für Renderlets aufbereitet zurückgeben.
     *
     * @param array              $params
     * @param tx_ameosformidable $form
     *
     * @todo Eigene Exceptions nutzen (nicht von mklib)
     *
     * @deprecated use tx_mkforms_util_FormFill::getItemsFromDb()
     *
     * @return array
     */
    public function getItemsFromDb(array $params, tx_ameosformidable $form)
    {
        /* @var $formfill tx_mkforms_util_FormFill */
        $formfill = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mkforms_util_FormFill');

        return $formfill->getItemsFromDb($params, $form);
    }

    /**
     * Macht aus arrays eine kommaseparierte Liste.
     *
     * @param array $aData
     */
    public static function implodeListerData(&$aData)
    {
        foreach ($aData as &$aTempData) {
            if (is_array($aTempData)) {
                foreach ($aTempData as &$mValue) {
                    if (is_array($mValue)) {
                        $mValue = implode(',', $mValue);
                    }
                }
            }
        }
    }

    /**
     * die gewünschte configuration id einfach in $params['configurationId']
     * übergeben.
     *
     * zusätzlich kann konfiguriert werden ob der Wert zu einem boolschen
     * Wert gecastet werden soll. Das ist zum Beispiel nötig wenn die Funktion
     * im /process verwendet wird
     *
     * @param array                 $params
     * @param tx_mkforms_forms_Base $form
     *
     * @return bool
     *
     * @throws InvalidArgumentException
     */
    public static function getConfigurationValue(array $params, tx_mkforms_forms_Base $form)
    {
        if (!($params['configurationId'] ?? false)) {
            throw new InvalidArgumentException('Please provide the parameter for \'configurationId\'');
        }

        $configurationIdPrefix = ($params['prefixWithConfigurationIdOfForm'] ?? false) ? $form->getConfId() : '';
        $configurationValue = $form->getConfigurations()->get($configurationIdPrefix.$params['configurationId'], true);

        if ($params['castToBoolean'] ?? false) {
            $configurationValue = (bool) $configurationValue;
        }

        return $configurationValue;
    }
}
