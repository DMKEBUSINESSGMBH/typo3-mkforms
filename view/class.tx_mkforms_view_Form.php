<?php
/**
 * 	@package tx_mkforms
 *  @subpackage tx_mkforms_view
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
 */

/**
 * benötigte Klassen einbinden
 */
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
	 * @param 	string 						$template
	 * @param 	ArrayObject					$viewData
	 * @param 	tx_rnbase_configurations	$configurations
	 * @param 	tx_rnbase_util_FormatUtil	$formatter
	 * @return 	mixed						Ready rendered output or HTTP redirect
	 */
	public function createOutput($template, &$viewData, &$configurations, &$formatter, $redirectToLogin = false) {
		// Wrnn konfiguriert, einen redirekt nach erfolgreichem absenden der form durchführen
		$this->handleRedirect($viewData, $configurations);

		$confId = $this->getController()->getConfId();

		$markerArray = $subpartArray  = $wrappedSubpartArray = array();

		// Wir holen die Daten von der Action ab
		// @TODO: mal auslagern! (handleFormData)
		if ($data =& $viewData->offsetGet('formData')) {
			// Successfully filled in form?
			if (is_array($data)) {
				// else:

				$markerArrays = $subpartArrays  = $wrappedSubpartArrays = array();

				foreach ($data as $key => $values) {
					$currentMarkerPrefix = strtoupper($key).'_';
					$currentConfId = $confId.$key.'.';
					/*
					 * @TODO: bei den Values sollte man auch Objekte übergeben können und die
					 * Markerklasse wird konfiguriert
					 * @TODO: Lister ausgeben
					 * @TODO: sollte über markerklassen geregelt werden!
					 *
					 * mwagner: Das hier ist eine generische Ausgabe.
					 * Markerklassen oder Listen sind nicht nötig.
					 * Wenn spezielle Markerklassen und Listen benötigt werden,
					 * müssen für den speziellen Fall die add Methoden
					 * oder die globale addAdditionalMarkers Methode angelegt werden!
					 */
					if(tx_rnbase_util_BaseMarker::containsMarker($template, $currentMarkerPrefix)) {
						$currentSubpartArray = $currentWrappedSubpartArray = array();
						$currentMarkerArray = $formatter->getItemMarkerArrayWrapped(
								$values, $currentConfId, 0, $currentMarkerPrefix, null);
						// wir suchen für jede Tabelle eine parse Methode in der Kindklasse!
						$method = 'add'.tx_mkforms_util_Div::toCamelCase($key).'Markers';
						if(method_exists($this, $method)) {
							$template = $this->{$method}(
									$values, $currentMarkerPrefix,
									$currentMarkerArray, $currentSubpartArray, $currentWrappedSubpartArray,
									$currentConfId, $formatter, $template, $viewData
								);
						}
						if(!empty($currentMarkerArray))
							$markerArrays[] = $currentMarkerArray;
						if(!empty($currentSubpartArray))
							$subpartArrays[] = $currentSubpartArray;
						if(!empty($currentWrappedSubpartArray))
							$wrappedSubpartArrays[] = $currentWrappedSubpartArray;
					}
				}
				// die marker arrays zusammenführen
				$markerArray = empty($markerArrays) ? array()
					: call_user_func_array('array_merge', $markerArrays);
				$subpartArray =	empty($subpartArrays) ? array()
					: call_user_func_array('array_merge', $subpartArrays);
				$wrappedSubpartArray = empty($wrappedSubpartArrays) ? array()
					: call_user_func_array('array_merge', $wrappedSubpartArrays);
			}
		}
		//
		$template = $this->addAdditionalMarkers($data, 'DATA',
			$markerArray, $subpartArray, $wrappedSubpartArray,
			$confId, $formatter, $template, $viewData
		);

		$markerArray['###FORM###'] = $viewData->offsetGet('form');
		$out = tx_rnbase_util_Templates::substituteMarkerArrayCached(
				$template, $markerArray, $subpartArray, $wrappedSubpartArray);

		// Fehler ausgeben, wenn gesetzt.
		if(strlen($errors = $viewData->offsetGet('errors')) > 0){
			$out .= $errors;
		}

		return $out;
	}

	/**
	 * Beispiel Methode um susätzliche marker zu füllen oder das Template zu parsen!
	 *
	 * @param 	array 						$data
	 * @param 	string 						$markerPrefix
	 * @param 	array 						$markerArray
	 * @param 	array 						$subpartArray
	 * @param 	array 						$wrappedSubpartArray
	 * @param 	tx_rnbase_util_FormatUtil 	$formatter
	 * @param 	string 						$template
	 * @return 	string
	 */
	protected function addAdditionalMarkers(
			$data, $markerPrefix,
			&$markerArray, &$subpartArray, &$wrappedSubpartArray,
			$confId, &$formatter, $template, $viewData) {


		// links parsen
		// @TODO: über rnbase simple marker parsen?
		$linkIds = $formatter->getConfigurations()->getKeyNames($confId.'links.');
		foreach($linkIds as $linkId) {
			$params = $formatter->getConfigurations()->get($confId.'links.'.$linkId.'.params.');
			tx_rnbase_util_BaseMarker::initLink(
					$markerArray, $subpartArray, $wrappedSubpartArray,
					$formatter, $confId, $linkId, $markerPrefix,
						empty($params) ? array() : $params,
					$template
				);
		}

		return $template;
	}

	/**
	 * Gibt es einen Redirect? Bei Bedarf kann diese Methode
	 * in einem eigenen View überschrieben werden
	 *
	 * @param 	ArrayObject 				$viewData
	 * @param 	tx_rnbase_configurations 	$configurations
	 * @return 	void
	 */
	protected function handleRedirect(&$viewData, &$configurations) {
		$confId = $this->getController()->getConfId();
		if (
			// redirect if fully submitted
			$viewData->offsetGet('fullySubmitted')
			// if there are no validation errors
			&& !$viewData->offsetGet('hasValidationErrors')
			// and redirect configured
			&& (
				$configurations->getBool($confId.'redirect') ||
				$configurations->get($confId.'redirect.pid')
			)
		) {
			// Speichern wir die Sessiondaten vor dem Redirect? Die würden sonst verloren gehen!
			$GLOBALS['TSFE']->fe_user->storeSessionData();

			$link = $this->createRedirectLink($viewData, $configurations, $confId);
			$link->redirect();
			// Und tschüss, ab hier passiert nix mehr.
			exit('REDIRECTED!');
		}
	}
	/**
	 * Erzeugt den Link für den Redirect. Kind-Klassen haben die Möglchkeit diese Methode zu überschreiben.
	 * @param ArrayObject $viewData
	 * @param tx_rnbase_configurations $configurations
	 * @param string $confId
	 */
	protected function createRedirectLink($viewData, $configurations, $confId) {
		$params	= $viewData->offsetGet('redirect_parameters');
		$link = $configurations->createLink();
		$link->initByTS($configurations, $confId.'redirect.', is_array($params) ? $params : array());
		return $link;
	}

	/**
	 * Subpart der im HTML-Template geladen werden soll. Dieser wird der Methode
	 * createOutput automatisch als $template übergeben.
	 *
	 * @return string
	 */
	public function getMainSubpart() {
		tx_rnbase::load('tx_rnbase_action_BaseIOC');
		$controller = $this->getController();
		if($controller instanceof tx_rnbase_action_BaseIOC) {
			$subpart = $this->getController()->getConfigurations()->get($this->getController()->getConfId().'mainSubpart');
		}
		return $subpart ? $subpart : '###DATA###';
	}
}
