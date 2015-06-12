<?php
/**
 * 	@package tx_mkforms
 *  @subpackage tx_mkforms_util
 *  @author Michael Wagner
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

require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');

/**
 * Some static util functions.
 *
 * @package tx_mkforms
 * @subpackage tx_mkforms_util
 * @author Michael Wagner
 */
class tx_mkforms_util_FormBase {

	/**
	 * Liefert das Parent-Objekt.
	 * Wahlweise wird nach einer existierenden Methode geprüft.
	 *
	 * @param 	tx_ameosformidable 	$form
	 * @param 	string 				$method
	 * @return 	tx_mkforms_action_FormBase
	 */
	protected static function getParent(tx_ameosformidable $form, $method = false){
		$oParent = $form->getParent();
		if($method && (!method_exists($oParent, $method) || !is_callable(array($oParent, $method)))) {
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
	 * @param 	array					$params
	 * @param 	tx_ameosformidable		$form
	 * @return 	array
	 */
	public function fillForm(array $params, tx_ameosformidable $form) {
		return is_object($oParent = self::getParent($form, 'fillForm'))
					? $oParent->fillForm($params, $form) : array();
	}

	/**
	 * ruft processForm auf, wenn ein Parent object gesetzt ist
	 * @param 	array					$params
	 * @param 	tx_ameosformidable		$form
	 * @return 	void
	 */
	public function processForm(array $params, tx_ameosformidable $form) {
		if(is_object($oParent = self::getParent($form, 'processForm'))) {
			$oParent->processForm($params, $form);
		}
	}

	/**
	 * Flatten a deep array to one single level
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
	 * @param 	array 	$data
	 * @param 	array 	$aWidgetNames
	 * @return 	array
	 */
	public static function flattenArray($data, $aWidgetNames = false) {
		$flattenData = array();
		foreach ($data as $key => $value) {
			if (is_array($value) && ($aWidgetNames === false || !in_array($key, $aWidgetNames))) {
				$value = self::flattenArray($value, $aWidgetNames);
				$flattenData = array_merge($flattenData, $value);
			}
			else {
				$flattenData[$key] = $value;
			}
		}
		return $flattenData;
	}

	/**
	 * Liefert das Konfigurations-Objekt
	 * @param 	tx_ameosformidable 			$form
	 * @param 	tx_rnbase_configurations 	$configurations
	 * @return 	tx_rnbase_configurations
	 */
	protected static function &getConfigurations(tx_ameosformidable &$form, tx_rnbase_configurations &$configurations = null){

		if(!is_object($configurations)){
			$configurations = (is_object($oParent = self::getParent($form, 'getConfiguration')))
								? $oParent->getConfiguration() : $form->getConfigurations();
		}

		return $configurations;
	}
	/**
	 * Liefert die confId ohne formconf.
	 * @param 	tx_ameosformidable 			$form
	 * @param 	string 						$confId
	 * @return 	string
	 */
	protected static function getConfId(tx_ameosformidable &$form, &$confId = ''){

		if(empty($confId)) {
			$confId = (is_object($oParent = self::getParent($form, 'getConfId')))
								? $oParent->getConfId() : $form->getConfId();
			// formconfig. abschneiden, wenn die id vom formobjekt geholt wird!!!
			if(strpos($confId, 'formconfig.') !== false){
				$confId = substr($confId,0, strlen($confId) - strlen('formconfig.'));
			}
		}

		return $confId;
	}

	/**
	 *
	 * @param 	tx_rnbase_configurations 	$configurations
	 * @param 	string 						$confId
	 * @return 	string
	 */
	protected function getFieldSeparator(tx_rnbase_configurations &$configurations, &$confId){
		$separator = $configurations->get($confId.'fieldSeparator');
		return $separator ? $separator : '-';
	}

	/**
	 * Convert flat array of data (e.g. received on a save event) into an array of subarrays
	 * with each subarray containing a table's data
	 *
	 * @param 	array 						$data
	 * @param 	tx_ameosformidable 			$form
	 * @param 	tx_rnbase_configurations 	$configurations
	 * @param 	string 						$confId
	 * @return 	array
	 */
	public static function flatArray2MultipleTableStructure(
							array $data,
							tx_ameosformidable &$form,
							tx_rnbase_configurations &$configurations = null,
							$confId = ''
						) {

		// Konfiguration und Id besorgen, falls nicht übergeben.
		$configurations = self::getConfigurations($form, $configurations);
		$confId = self::getConfId($form, $confId);

		$aWidgets = $flattenData = array();

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
		foreach($form->getWidgetNames() as $sWidget) {
			$oWidget = $form->getWidget($sWidget);
			// Wenn keine Kindselemente oder ein Lister
			if (!$oWidget->hasChilds() || $oWidget->iteratingChilds) {
				$aWidget = explode($sFieldSeparator, $sWidget);
				$sWidget = end($aWidget);
				// wird nur gebraucht, falls flattenArray aufgerufen wird
				$aWidgets[] = $sWidget;
				$mValue = $oWidget->getValue();
				// Wurde ein Button gedrückt? welcher?
				if($oWidget->isNaturalSubmitter() && $mValue) {
					$flattenData['submitmode'] = $oWidget->getSubmitMode();
					$form->setSubmitter($sWidget, $flattenData['submitmode']);
				}
				// Datumsfelder zusätzlich für MySQL konvertieren!
				elseif($oWidget->sMajixClass == 'Date' && $mValue) {
					tx_rnbase::load('tx_rnbase_util_Dates');
					$flattenData[$sWidget.'_mysql'] = tx_rnbase_util_Dates::date_tstamp2mysql($mValue);
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

		if ($configurations->get($confId.'addPostVars', true) || $data['addPostVars']) {
			$flattenData['addpostvars'] = $form->getAddPostVars();
		}

		$separator = self::getFieldSeparator($configurations, $confId);
		// Die Felder in Ihre Tabellennamen aufsplitten
		$flattenData = self::explodeArrayWithArrayKeys($flattenData, $separator);

		return $flattenData;
	}

	/**
	 * Convert data of several tables into a flat array of fields, e.g. used by data handlers
	 *
	 * @param array $data	Data of several tables, each table's data in an own subarray
	 * @param array $params	Parameters defining conversion options ('dateFields') etc.
	 * @return array
	 */
	public static function multipleTableStructure2FlatArray(
							array $data,
							tx_ameosformidable &$form,
							tx_rnbase_configurations &$configurations = null,
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
	 * Convert data of several tables into a deep array of fields to deeply fill a renderlet
	 *
	 * @param 	array					$data	Data of several tables, each table's data in an own subarray
	 * @param 	tx_ameosformidable		$form
	 * @param 	mixed 					$mTargetRenderlet Kann das Objekt oder den Namen enthalten
	 * @return 	array
	 */
	public static function multipleTableStructure2DeepArray(array $data, tx_ameosformidable $form, $mTargetRenderlet=false) {
		if(is_string($mTargetRenderlet)){
			$mTargetRenderlet = $form->getWidget($mTargetRenderlet);
		}
		if(!is_object($mTargetRenderlet)) return array();
		$data = self::multipleTableStructure2FlatArray($data, $form);
		$data = self::convertFlatDataToRenderletStructure(
						$data,
						$form,
						$mTargetRenderlet
					);

		return $data;
	}

	/**
	 * Eliminate renderlet path info from the given data
	 *
	 * @param array $renderletData
	 * @param tx_ameosformidable $form
	 * @return array
	 */
	public static function removePathFromWidgetData(array $widgetData, tx_mkforms_forms_Base $form) {
		$res = array();
		foreach ($widgetData as $key=>$value) {
			if (is_object($widget=$form->getWidget($key)))
				$res[$widget->getName()] = $value;
		}
		return $res;
	}

	/**
	 * Merge still FLATTENED(!) data of several tables into a deep renderlet structure
	 *
	 * Resulting field name format: tablename-fieldname, with "-" being the given $sep.
	 * Fields defined in more than one table are represented multiple:
	 * * Once per table:
	 * 	 * table1-fieldname
	 *   * table2-fieldname
	 *   * etc.
	 * * Additionally in a key named like this: table1-table2-table3-fieldname
	 *   * Table names are sorted alphabetically!
	 *   * If more than one table returns a non-empty value, the first non-empty one
	 *     is used where "first" is based on the order of the tables in $data.
	 *
	 * ### Note that fields from several tables with identical field names
	 * 		overwrite each other in the multiple table field! ###
	 *
	 * @param array						$srcData			Flat array of data
	 * @param tx_ameosformidable		$form
	 * @param formidable_mainrenderlet	$targetRenderlet	The target renderlet instance
	 * @return array
	 */
	private static function convertFlatDataToRenderletStructure(
								array $srcData,
								tx_ameosformidable $form,
								formidable_mainrenderlet $targetRenderlet
							) {
		$res = array();
		$trName = $targetRenderlet->getName();
		// Is data available for that special renderlet field?
		// Just return this scalar value!
		if (array_key_exists($trName, $srcData)) return $srcData[$trName];

		foreach ($targetRenderlet->getChilds() as $child) {
			$childData = self::convertFlatDataToRenderletStructure($srcData, $form, $child);
			if (!is_null($childData)) $res[$child->getName()] = $childData;
		}
		if (empty($res)) return null;
		return $res;
	}

	/**
	 * Splittet die Formdaten in ihre Tabellennamen auf
	 *
	 * Die Tabelle steht immer vor dem Feld (tabelle-spalte)
	 * Expected field name format: tablename-fieldname, with "-" being the given $sep.
	 * It is also possible to give more than one table for a field: table1-table2-table3-fieldname
	 *
	 * @param array		$data
	 * @param string	$separator		Wie wurde Feld von Tebelle getrennt.
	 * @param string	$defaultTable	Wenn keine Tabelle definiert ist wirk das als default. False für keine Tabelle.
	 * @return array	Nested data array, each table with its own key
	 */
	public static function explodeArrayWithArrayKeys(array $data, $separator='-') {
		$splitData = array();
		foreach ($data as $key=>$value) {
			if (is_array($value)) {
				$value = self::explodeArrayWithArrayKeys($value, $separator);
				// bei z.B. Listern werden die Daten immer in ein zusätzliches Array geschrieben!
				if(count($value) === 1 && is_numeric($key) && ($key === reset(array_keys($value)))) {
					$value = $value[$key];
				}
			}
			$keys = explode($separator, $key);
			$fieldKey = end($keys);
			// Ein oder mehrere Tabellen im Titel
			if (count($keys) > 1) {
				// Bei z.B. Checkboxen werden die Werte immer in einen zusätzlichem Array, item-n übergeben.
				if(count($keys) === 2 && is_numeric($fieldKey) && $keys[0] == 'item') {
					$splitData[$fieldKey] = $value;
				}
				// Den Feldnamen in die Tabellen umsetzen.
				else {
					for ($i = 0; $i < count($keys)-1; $i++) {
						$k = $keys[$i];
						if (!isset($splitData[$k])) $splitData[$k] = array();
						$splitData[$k][$fieldKey] = $value;
					}
				}
			}
			// Multivalue, @TODO: es gibt doch keine Felder mit einem numerischen Namen oder?
			elseif(is_numeric($key)) {
				$splitData[$key] = $value;
			}
			// freies Feld
			else {
				$splitData[$fieldKey] = $value;
			}
		}
		return $splitData;
	}
	/**
	 * @TODO: fertig umsetzen und integrieren, fals nötig!
	 *
	 * @param 	array $data
	 * @param 	mixed $value
	 * @param 	string $key
	 * @param 	string $subKey
	 * @return 	void
	 */
	private static function explodeArrayWithArrayKeys_setValue(array &$data, $value, $key, $subKey=false) {
		if ($subKey && !isset($data[$key])) {
			$data[$key] = array();
		}
		$keyData = &$data;
		if($subKey) {
			$keyData = &$keyData[$key];
			$key = $subKey;
		}

		// Wurde bereits gesetzt
		if(array_key_exists($key, $keyData)){
			// in ein Array umwandeln, wenn noch nicht geschehen
			if (!is_array($keyData[$key])) {
				$keyData[$key] = array( $keyData[$key] );
			}
			$keyData[$key][] = $value;
		}
		// wert erstmals setzen
		else {
			$keyData[$subKey] = $value;
		}
	}

	/**
	 * Merge data of several arrays into one single data array
	 *
	 * Resulting field name format: arrayname-fieldname, with "-" being the given $sep.
	 * Fields defined in more than one array are represented multiple:
	 * * Once per array:
	 * 	 * array1-fieldname
	 *   * array2-fieldname
	 *   * etc.
	 * * Additionally in a key named like this: array1-array2-array3-fieldname
	 *   * Array names are sorted alphabetically!
	 *   * If more than one array returns a non-empty value, the first non-empty one
	 *     is used where "first" is based on the order of the arrays in $data.
	 *
	 * ### Note that fields from several arrays with identical field names
	 * 		overwrite each other in the multiple table field! ###
	 *
	 * @param array		$data			Nested data array, each array with its own key
	 * @param string	$separator		Separator string which divides array name from field name
	 * @return array
	 */
	public static function flattenMultipleArrays(array $data, $separator='-') {
		$tmp = array();
		$res = array();
		foreach ($data as $table=>$fields) {
			if (is_array($fields)) {
				foreach ($fields as $key=>$value) {
					if (!array_key_exists($key, $tmp)) {
						$tmp[$key] = array('tables' => array(), 'value' => '');
					}
					$tmp[$key]['tables'][] = $table;
					if (empty($tmp[$key]['value'])) $tmp[$key]['value'] = $value;
					$res[$table.$separator.$key] = $value;
				}
			}
			// Für dieses Feld wurde keine Tabelle angegeben
			else {
				$res[$table] = $fields;
			}
		}


		foreach ($tmp as $fieldname => $data) {
			if (count($data['tables']) == 1 && $data['tables'][0] == $defaultPrefix)
				$res[$fieldname] = $data['value'];
			else {
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
		}
		return $res;
	}

	/**
	 * Fügt zu einem Array werte hinzu oder löscht diese.
	 * Zum löschen unset oder null übergeben!
	 * @param 	array 	$data
	 * @param 	array 	$fields
	 * @return rray
	 */
	public function addFields(array $data, array $fields){

		foreach ($fields as $fieldKey => $fieldValue) {
			// sub config gefunden, weiter machen!
			if((substr($sPath, -1) === '.')) {
				continue;
			}
			// wenn es ein array ist, weiterleiten
			if(is_array($data[$fieldKey])) {
				$data[$fieldKey] = self::addFields($data[$fieldKey], $fields);
			}
			else {
				$bUnset = $fieldValue == 'unset';
				// Der Wert soll gelöscht werden.
				if ($bUnset && isset($data[$fieldKey])) {
					unset($data[$fieldKey]);
				}
				// Den Wert setzen oder Überschreiben
				elseif(!$bUnset) {
					$bOverride = isset($fields[$fieldKey.'.']) && isset($fields[$fieldKey.'.']['override']) && (intval($fields[$fieldKey.'.']['override']) > 0);
					if (!isset($data[$fieldKey]) || $bOverride) {
						$fieldValue = ($fieldValue == 'null') ? null : $fieldValue;
						$data[$fieldKey] = $fieldValue;
					}
				}
			}
		}
		return $data;
	}

	/**
	 * Liefert die Daten aus dem gegebenen Widget für einen Lister
	 *
	 * @param array $aParams
	 * @param tx_ameosformidable $oForm
	 */
	public static function getListerData($aParams, $oForm) {
		$aStoredData = $oForm->getDataHandler()->_getStoredData($aParams['rdt']);
		return (!empty($aStoredData)) ? array($aStoredData) : null;
	}

	/**
	* Bestimmte Datensätze aus der DB auslesen
	*
	* @param array $params
	* @param tx_ameosformidable $form
	* @todo 	Eigene Exceptions nutzen (nicht von mklib)
	* @return array
	*/
	public static function getRowsFromDataBase(array $params, tx_ameosformidable $form){
		//erstmal prüfen ob alle notwendigen params gesetzt wurden
		if(empty($params['table']) || empty($params['valueField']) || empty($params['captionField']))
			throw new InvalidArgumentException(
				'tx_mkforms_util_FormBaseAjax->getItemsFromDb():'.
				' Bitte gib die Parameter "table", "valueField" und "captionField" an.'
			);
		if (isset($params['dependsOn']) && (empty($params['dependsOn']['dbfield']) || empty($params['dependsOn']['formfield']))){
			throw new InvalidArgumentException(
				'tx_mkforms_util_FormBaseAjax->getItemsFromDb():'.
				' Wenn du $params["dependsOn"] angibst musst du auch $params["dependsOn"]["dbfield"] und $params["dependsOn"]["formfield"] angeben!'
			);
		}else{
			if ($widget = $form->getWidget($params['dependsOn']['formfield'])) {
				$val = $widget->getValue();

				// Use another table?
				$tab = isset($params['dependsOn']['dbtable']) ? $params['dependsOn']['dbtable'] :
				(is_array($table) ? $table['tablename'] : $table);
				if (isset($params['options']['where']) && $params['options']['where'])
				$params['options']['where'] .= ' AND ';
				else $params['options']['where'] = '';

				global $GLOBALS;
				$params['options']['where'] .= $params['dependsOn']['dbfield'] . '=' .
				$GLOBALS['TYPO3_DB']->fullQuoteStr($val, $tab);
			}
		}

		if (is_array($params['table'])) {
			$table = array(
				$params['table']['from'],
				$params['table']['tablename'],
				isset($params['table']['alias']) ? $params['table']['alias'] : null
			);
		}
		else $table = $params['table'];

		// wenn der Wert von dem wir abhängen leer ist, suchen wir nicht
		if(empty($params['dependsOn']) || (!empty($params['dependsOn']) && !empty($val))) {
			$rows = tx_rnbase_util_DB::doSelect(
				$params['valueField'].' as __value__,'.$params['captionField'] . ' as __caption__',
				$table,
				isset($params['options']) ? $params['options'] : null,
				isset($params['debug']) ? $params['debug'] : null
			);
		}

		return $rows;
	}

	/**
	 * Bestimmte Datensätze aus der DB auslesen und diese für Renderlets aufbereitet zurückgeben
	 *
	 * Expected parameters in $params:
	 * * 'table':			Mandatory:	String or array of tables with keys 'from' (complete from-clause including aliases etc.), 'tablename' (name of first table) and (optionally) 'alias' (alias of first table)
	 * * 'valueField':		Mandatory:	Field representing the item's value. May be a calculated SQL expression - but WITHOUT 'as fieldalias' part!!! Implicitely used alias is '__value__', can be used e.g. for sorting.
	 * * 'captionField':	Mandatory:	Field representing the item's label aka caption. May be a calculated SQL expression - but WITHOUT 'as fieldalias' part!!! Implicitely used alias is '__caption__', can be used e.g. for sorting.
	 * * 'options':			Optional:	Array of options which are directly passed to tx_rnbase_util_DB::doSelect
	 * * 'dependsOn':		Optional:	Array of options for dependent fields: array('formfieldname' => form fields which's value is used, 'dbfield' => dedicated database field, 'dbtable'(optional) => real name of the table of the dedicated database field (needed for complex searches with JOINs; otherwise $params['table'] is used.)). Note that either used table needs to be defined in $TCA!
	 * * 'debug':			Optional:	Flag whether SQL query is executed in debug mode
	 * @see tx_rnbase_util_DB::doSelect
	 *
	 * Complete example:
	 * 	<params>
	 * 		<param name="table" from="tx_mkhoga_applicants as app join fe_users on app.feuser=fe_users.uid join tx_mkhoga_contacts as c on app.contact=c.uid" tablename="tx_mkhoga_applicants" alias="app" />
	 *		<param name="valueField" value="app.uid" />
	 *		<param name="captionField" value="c.lastname" />
	 *		<param name="options">
	 *			<where>2=2</where>
	 *			<orderby>uid asc</orderby>
	 *		</param>
	 * 		<param name="dependsOn" formfield="-trade" dbField="trade" dbTableName="tx_mkhoga_types" />
	 *		<param name="debug" value="1" />
	 *	</params
	 *
	 * @param array $params
	 * @param tx_ameosformidable $form
	 * @todo 	Eigene Exceptions nutzen (nicht von mklib)
	 * @return array
	 */
	public static function getItemsFromDb(array $params, tx_ameosformidable $form){
		return tx_mkforms_util_Div::arrayToRdtItems(
			static::getRowsFromDataBase($params, $form), '__caption__', '__value__'
		);
	}

	/**
	 * Macht aus arrays eine kommaseparierte Liste
	 *
	 * @param array $aData
	 * @return void
	 */
	public static function implodeListerData(&$aData) {
		foreach ($aData as &$aTempData) {
			if(is_array($aTempData)){
				foreach ($aTempData as &$mValue){
					if(is_array($mValue)) $mValue = implode(',', $mValue);
				}
			}
		}
	}
}
