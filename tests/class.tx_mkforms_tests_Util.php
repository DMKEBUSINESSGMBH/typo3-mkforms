<?php
/**
 * 	@package tx_mklib
 *  @subpackage tx_mklib_tests
 *  @author Hannes Bochmann
 *
 *  Copyright notice
 *
 *  (c) 2010 Hannes Bochmann <hannes.bochmann@das-medienkombinat.de>
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
tx_rnbase::load('tx_mkforms_forms_Factory');
/**
 * Statische Hilfsmethoden für Tests
 *
 * @package tx_mklib
 * @subpackage tx_mklib_tests
 */
class tx_mkforms_tests_Util {

	/**
	 * @param boolean $force
	 */
	public static function getStaticTS($force = false){
		static $configArray = false;
		if(is_array($configArray) && !$force) {
			return $configArray;
		}
		t3lib_extMgm::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:mkforms/static/ts/setup.txt">');

		tx_rnbase::load('tx_rnbase_configurations');
		tx_rnbase::load('tx_rnbase_util_Misc');

		tx_rnbase_util_Misc::prepareTSFE(); // Ist bei Aufruf aus BE notwendig!

		/*
		 * pk: Danke mw
		 * getPagesTSconfig benutzt static Cache und bearbeitet nicht das was wir mit addPageTSConfig hinzugefügt haben.
		 * um das umzugehen, kann man das RootLine Parameter leer setzen.
		 * Siehe: TYPO3\CMS\Backend\Utility\BackendUtility:getPagesTSconfig();
		 */
		$tsConfig = t3lib_BEfunc::getPagesTSconfig(0,'');

		$configArray = $tsConfig['plugin.']['tx_mkforms.'];
		// für referenzen im TS!
		$GLOBALS['TSFE']->tmpl->setup['lib.']['mkforms.'] = $tsConfig['lib.']['mkforms.'];
		$GLOBALS['TSFE']->tmpl->setup['config.']['tx_mkforms.'] = $tsConfig['config.']['tx_mkforms.'];
		$GLOBALS['TSFE']->config['config.']['tx_mkforms.'] = $tsConfig['config.']['tx_mkforms.'];
		return $configArray;
	}

	/**
	 * Liefert ein Form Objekt
	 * Enter description here ...
	 */
	public static function getForm(
		$bCsrfProtection = true, $aConfigArray = array(), $parent = null
	) {
		$oForm = tx_mkforms_forms_Factory::createForm('generic');
		$oForm->setTestMode();

		$oParameters = tx_rnbase::makeInstance('tx_rnbase_parameters');
		$oConfigurations = tx_rnbase::makeInstance('tx_rnbase_configurations');
		if(!$aConfigArray){
			$aConfigArray = self::getDefaultFormConfig($bCsrfProtection);
		}
		$oConfigurations->init(
			$aConfigArray,
			$oConfigurations->getCObj(1),
			'mkforms', 'mkforms'
		);
		$oConfigurations->setParameters($oParameters);

		if(!$parent) {
			$parent = $this;
		}

		$oForm->init(
			$parent,
			$oConfigurations->get('generic.xml'),
			0,
			$oConfigurations,
			'generic.formconfig.'
		);

		// logoff für phpmyadmin deaktivieren
		/*
		 * Error in test case test_handleRequest
		 * in file C:\xampp\htdocs\typo3\typo3conf\ext\phpmyadmin\res\class.tx_phpmyadmin_utilities.php
		 * on line 66:
		 * Message:
		 * Cannot modify header information - headers already sent by (output started at C:\xampp\htdocs\typo3\typo3conf\ext\phpunit\mod1\class.tx_phpunit_module1.php:112)
		 *
		 * Diese Fehler passiert, wenn die usersession ausgelesen wird. der feuser hat natürlich keine.
		 * Das Ganze passiert in der t3lib_userauth->fetchUserSession.
		 * Dort wird t3lib_userauth->logoff aufgerufen, da keine session vorhanden ist.
		 * phpmyadmin klingt sich da ein und schreibt daten in die session.
		 */
		if(is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing']))
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing'] as $k=>$v){
				if($v = 'tx_phpmyadmin_utilities->pmaLogOff'){
					unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing'][$k]);
				}
			}

		return $oForm;
	}

	public static function getDefaultFormConfig($bCsrfProtection = true) {
		return array(
			'generic.' => array(
				'xml' => 'EXT:mkforms/tests/xml/renderlets.xml',
				'addfields.' => array(
						'widget-addfield' => 'addfield feld',
						'widget-remove' => 'unset',
					),
				'fieldSeparator' => '-',
				'addPostVars' => 1,
				'formconfig.' => array(
					'loadJsFramework' => 0, // formconfig für config check setzen.
					'csrfProtection' => $bCsrfProtection,
					'checkWidgetsExist' => 1,
				),

			)
		);
	}

	/**
	 * Setzt die werte aus dem array für die korrespondierenden widgets.
	 * bei boxen wird rekursiv durchgegangen.
	 *
	 * @param array $aData	|	Die Daten wie sie in processForm ankommen
	 * @param $oForm
	 * @return void
	 */
	public static function setWidgetValues($aData, $oForm) {
		foreach ($aData as $sName => $mValue){
			if(is_array($mValue)) self::setWidgetValues($mValue,$oForm);
			else $oForm->getWidget($sName)->setValue($mValue);
		}
	}

	/**
	 * @param string $formId
	 * @param array $formData
	 * @param string $requestToken
	 */
	public static function setRequestTokenForFormId(
		$formId, array &$formData, $requestToken = 's3cr3tT0k3n'
	) {
		$formData['MKFORMS_REQUEST_TOKEN'] = $requestToken;

		$GLOBALS['TSFE']->fe_user->setKey(
			'ses', 'mkforms',
			array('requestToken' =>
				array(
					$formId => $requestToken
				)
			)
		);
		$GLOBALS['TSFE']->fe_user->storeSessionData();
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mklib/tests/class.tx_mklib_tests_Util.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mklib/tests/class.tx_mklib_tests_Util.php']);
}