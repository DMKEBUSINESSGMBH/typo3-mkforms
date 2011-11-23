<?php
/**
 * 	@package tx_mkforms
 *  @subpackage tx_mkforms_view
 *  @author Michael Wagner
 *
 *  Copyright notice
 *
 *  (c) 2011 Michael Wagner <michael.wagner@das-medienkombinat.de>
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
 */

/**
 * benötigte Klassen einbinden
 */
require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_rnbase_view_Base');
tx_rnbase::load('tx_rnbase_util_Link');
tx_rnbase::load('tx_rnbase_util_Templates');

/**
 * Generic form view
 *
 * @package tx_mkforms
 * @subpackage tx_mkforms_view
 * @author Michael Wagner
 */
class tx_mkforms_view_Form extends tx_rnbase_view_Base {

	/**
	 * Do the output rendering.
	 *
	 * As this is a generic view which can be called by
	 * many different actions we need the actionConfId in
	 * $viewData in order to read its special configuration,
	 * including redirection options etc.
	 *
	 * @param string $template
	 * @param tx_lib_spl_arrayObject	$viewData
	 * @param tx_rnbase_configurations	$configurations
	 * @param tx_rnbase_util_FormatUtil	$formatter
	 * @return mixed					Ready rendered output or HTTP redirect
	 */
	public function createOutput($template, &$viewData, &$configurations, &$formatter, $redirectToLogin = false) {
		$confId = $this->getController()->getConfId();

		// Wir holen die Daten von der Action ab
		if ($data =& $viewData->offsetGet('formData')) {
			// Successfully filled in form?
			if (is_array($data)) {
				$this->handleRedirect($data, $viewData, $configurations);
				// else:

				$markerArrays = array();

				foreach ($data as $key => $value) {
					// @TODO: Lister ausgeben
					$markerArrays[] = $formatter->getItemMarkerArrayWrapped(
											$value,
											$confId.$key.'.',
											0,
											strtoupper($key).'_',
											null
										);
				}
			}
			$markerArray = !empty($markerArrays) ? call_user_func_array('array_merge', $markerArrays) : array();
		}
		else $markerArray = array();

		$markerArray['###FORM###'] = $viewData->offsetGet('form');
		$out = tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markerArray);
		
		// Fehler ausgeben, wenn gesetzt.
		if(strlen($errors = $viewData->offsetGet('errors')) > 0){
			$out .= $errors;
		}
		
		return $out;
	}
	
	/**
	 * Gibt es einen Redirect? Bei Bedarf kann diese Methode
	 * in einem eigenen View überschrieben werden
	 *
	 * @param array $data
	 * @param tx_lib_spl_arrayObject $viewData
	 * @param tx_rnbase_configurations $configurations
	 *
	 * @return void
	 */
	protected function handleRedirect($data, &$viewData, &$configurations) {
		$confId = $this->getController()->getConfId();
		if (
			// redirect if fully submitted
			$viewData->offsetGet('fullySubmitted')
			// and redirect configured
			&& (
				$configurations->getBool($confId.'redirect') ||
				$configurations->get($confId.'redirect.pid')
			)
		) {
			// Speichern wir die Sessiondaten vor dem Redirect? Die würden sonst verlorgen gehen!
			if($configurations->getBool($confId.'redirect.storeSessionData'))
				$GLOBALS['TSFE']->fe_user->storeSessionData();
			
			$link = $configurations->createLink();
			$link->initByTS($configurations, $confId.'redirect.', array());
			$link->redirect();
			// Und tschüss, ab hier passiert nix mehr.
			exit('REDIRECTED!');
		}
	}

	/**
	 * Subpart der im HTML-Template geladen werden soll. Dieser wird der Methode
	 * createOutput automatisch als $template übergeben.
	 *
	 * @return string
	 */
	public function getMainSubpart() {
		return '###DATA###';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/view/class.tx_mkforms_view_Form.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/view/class.tx_mkforms_view_Form.php']);
}