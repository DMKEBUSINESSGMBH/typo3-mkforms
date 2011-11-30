<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 René Nitzsche (nitzsche@das-medienkombinat.de)
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
tx_rnbase::load('tx_mkforms_session_IManager');

/**
 * A session manager that uses php session to store data
 *
 * Relevante Daten:
 * - ajax_services:  Widgets, die Ajax nutzen
 * - hibernate: Das serialisierte Formular
 * Pfad: hibernate/formid/
 *   object - das serialisierte Formular
 *   runningobjects - alle verwendeten Formobjekte inklusive Datahandler und Renderer
 *   tsfe_config - das Typoscript-Array
 *
 */
class tx_mkforms_session_PHPSessionManager implements tx_mkforms_session_IManager {
	private $form;
	public function __construct() {
		session_start();
		$this->init();
	}
	private function init() {
		if(!array_key_exists('ameos_formidable', $GLOBALS['_SESSION'])) {

			$GLOBALS['_SESSION']['ameos_formidable'] = array();
			$GLOBALS['_SESSION']['ameos_formidable']['ajax_services'] = array();
			$GLOBALS['_SESSION']['ameos_formidable']['ajax_services']['tx_ameosformidable'] = array();
			$GLOBALS['_SESSION']['ameos_formidable']['ajax_services']['tx_ameosformidable']['ajaxevent'] = array();

			$GLOBALS['_SESSION']['ameos_formidable']['hibernate'] = array();

			$GLOBALS['_SESSION']['ameos_formidable']['applicationdata'] = array();
		}
	}
	public function setForm($form) {
		$this->form = $form;
	}
	/**
	 * Returns the form instance
	 * @return tx_ameosformidable
	 */
	public function getForm() {
		return $this->form;
	}

	/**
	 * Restores form from session
	 * @return tx_ameosformidable or false
	 */
	public function restoreForm($formid) {
		if(!array_key_exists($formid, $GLOBALS['_SESSION']['ameos_formidable']['hibernate'])) return false;
		$aHibernation =& $GLOBALS['_SESSION']['ameos_formidable']['hibernate'][$formid];
		$this->loadRunningObjects($aHibernation);
		$this->loadParent($aHibernation);

		$oForm = unserialize(gzuncompress($aHibernation['object']));
		$oForm->_includeSandBox();	// rebuilding class
		$oForm->oSandBox = unserialize($oForm->oSandBox);
		$oForm->oSandBox->oForm =& $oForm;

		$oForm->oDataHandler->oForm =& $oForm;
		$oForm->oRenderer->oForm =& $oForm;
		$oForm->getRunnable()->initCodeBehinds();

		return $oForm;
	}
	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$aHibernation: ...
	 * @return	[type]		...
	 */
	private function loadRunningObjects(&$aHibernation) {
		tx_rnbase::load('tx_mkforms_util_Loader');
		$aRObjects =& $aHibernation['runningobjects'];
		reset($aRObjects);
		while(list(, $aObject) = each($aRObjects)) {
			tx_mkforms_util_Loader::loadObject($aObject['internalkey'],$aObject['objecttype']);
		}
	}
	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$aHibernation: ...
	 * @return	[type]		...
	 */
	private function loadParent(&$aHibernation) {
		if($aHibernation['parent'] !== FALSE) {
			$sClassPath = $aHibernation['parent']['classpath'];
			require_once($sClassPath);
		}
	}

	public function persistForm() {
		$form = $this->getForm();
		if(!$form) throw new Exception('No form found to persist!');
		$form->cleanBeforeSession();
		if(tx_mkforms_util_Div::getEnvExecMode() === 'BE') {
			$sLang = $GLOBALS['LANG']->lang;
		} else {
			$sLang = $GLOBALS['TSFE']->lang;
		}
		$formId = $this->getForm()->getFormId();
//		$compressed = serialize(gzcompress(serialize($this),9));
//		t3lib_div::debug(strlen($compressed),'class.tx_ameosformidable.php : '); // TODO: remove me
		$GLOBALS['_SESSION']['ameos_formidable']['hibernate'][$formId] = array(
			'object' => gzcompress(serialize($this->getForm()),9),
			'xmlpath' => $this->getForm()->_xmlPath,
			'runningobjects' => $this->getForm()->getObjectLoader()->getRunningObjects($formId),
			'loadedClasses' => $this->getForm()->getObjectLoader()->getLoadedClasses($formId),
			'sys_language_uid' => intval($GLOBALS['TSFE']->sys_language_uid),
			'sys_language_content' => intval($GLOBALS['TSFE']->sys_language_content),
			'tsfe_config' => $GLOBALS['TSFE']->config,
			'pageid' => $GLOBALS['TSFE']->id,
			'lang' => $sLang,
			'spamProtectEmailAddresses' => $GLOBALS['TSFE']->spamProtectEmailAddresses,
			'spamProtectEmailAddresses_atSubst' => $GLOBALS['TSFE']->config['config']['spamProtectEmailAddresses_atSubst'],
			'spamProtectEmailAddresses_lastDotSubst' => $GLOBALS['TSFE']->config['config']['spamProtectEmailAddresses_lastDotSubst'],
			'parent' => FALSE,
			'formidable_tsconfig' => $GLOBALS['TSFE']->tmpl->setup['config.']['tx_ameosformidable.'],
		);

		if($this->bStoreParentInSession === TRUE) {

			$sClass = get_class($this->getForm()->getParent());
			$aParentConf = $GLOBALS['TSFE']->tmpl->setup['plugin.'][$sClass . '.'];

			$GLOBALS['_SESSION']['ameos_formidable']['hibernate'][$formId]['parent'] = array(
				'classpath' => tx_mkforms_util_Div::removeEndingSlash(
					t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT')) . '/' . tx_mkforms_util_Div::removeStartingSlash($aParentConf['includeLibs']),
			);
		}

		// Warning for large sessions
		tx_rnbase::load('tx_rnbase_util_Logger');
		if(tx_rnbase_util_Logger::isNoticeEnabled()) {
			$sessionLen = strlen(serialize($GLOBALS['_SESSION']));
			if($sessionLen > 900000) {
				tx_rnbase_util_Logger::notice('Alert: Large session size!', 'mkforms', array('Size'=>$sessionLen, 'PHP-SessionID'=> session_id(), 'FormId' => $formId));
			}
// 			// nicht gut für den live betrieb, lieber mal in die devlog schauen!
// 			if ($_REQUEST['debug']==1)
// 				t3lib_div::debug($sessionLen,'Das ganze _SESSION in Bytes in ' . $formId );
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/util/class.tx_mkforms_session_PHPSessionManager.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/util/class.tx_mkforms_session_PHPSessionManager.php']);
}
?>
