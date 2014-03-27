<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 das MedienKombinat GmbH <kontakt@das-medienkombinat.de>
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
 * @author Hannes Bochmann
 */
class tx_mkforms_util_DamUpload {

	/**
	 * es wird $formParameters[damWidget] benötigt
	 *
	 * @param array $formParameters
	 * @param tx_mkforms_forms_Base $form
	 *
	 * @throws RuntimeException
	 *
	 * 	@return array
	 */
	public function getUploadsByWidget($formParameters, tx_mkforms_forms_Base $form) {
		if(!$formParameters['damWidget']) {
			throw new RuntimeException(
				'$formParameters[\'damWidget\'] konfigurieren',
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['baseExceptionCode'] . 31
			);
		}

		$damPics = $form->getWidget($formParameters['damWidget'])->getDamPics();

		return isset($damPics['rows']) ? $damPics['rows'] : array();
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/util/class.tx_mkforms_util_DamUpload.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/util/class.tx_mkforms_util_DamUpload.php']);
}
?>