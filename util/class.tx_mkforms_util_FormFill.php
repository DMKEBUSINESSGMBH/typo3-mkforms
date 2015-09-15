<?php
/**
 * 	@package tx_mkforms
 *  @subpackage tx_mkforms_util
 *  @author Michael Wagner
 *
 *  Copyright notice
 *
 *  (c) 2015 DMK E-BUSINESS GmbH <dev@dmk-business.de>
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
 * @package tx_mkforms
 * @subpackage tx_mkforms_util
 * @author Michael Wagner
 */
class tx_mkforms_util_FormFill {


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
			static::getRowsFromDataBase($params, $form),
			'__caption__',
			'__value__'
		);
	}

	/**
	 * Bestimmte Datensätze aus der DB auslesen
	 *
	 * @param array $params
	 * @param tx_ameosformidable $form
	 * @todo Eigene Exceptions nutzen (nicht von mklib)
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
				$tab = isset($params['dependsOn']['dbtable'])
					? $params['dependsOn']['dbtable']
					: (
						is_array($table)
							? $table['tablename']
							: $table
					)
				;
				if (
					isset($params['options']['where'])
					&& $params['options']['where']
				) {
					$params['options']['where'] .= ' AND ';
				} else {
					$params['options']['where'] = '';
				}

				$params['options']['where'] .= $params['dependsOn']['dbfield'] . '=' .
				$GLOBALS['TYPO3_DB']->fullQuoteStr($val, $tab);
			}
		}

		if (is_array($params['table'])) {
			$table = array(
				$params['table']['from'],
				$params['table']['tablename'],
				isset($params['table']['alias'])
					? $params['table']['alias']
					: null
			);
		} else {
			$table = $params['table'];
		}

		// wenn der Wert von dem wir abhängen leer ist, suchen wir nicht
		if(
			empty($params['dependsOn'])
			|| (!empty($params['dependsOn']) && !empty($val))
		) {
			$rows = tx_rnbase_util_DB::doSelect(
				$params['valueField'].' as __value__,'.$params['captionField'] . ' as __caption__',
				$table,
				isset($params['options']) ? $params['options'] : null,
				isset($params['debug']) ? $params['debug'] : null
			);
		}

		return $rows;
	}


}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/util/class.tx_mkforms_util_FormBase.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/util/class.tx_mkforms_util_FormBase.php']);
}
