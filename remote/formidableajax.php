<?php

// Exit, if script is called directly (must be included via eID in index_ts.php)
if (!defined ('PATH_typo3conf')) 	die ('Could not access this script directly!');

class formidableajax {

	var $aRequest	= array();
	var $aConf		= FALSE;
	var $aSession	= array();
	var $aHibernation = array();
	/**
	 * @var tx_ameosformidable
	 */
	var $oForm		= null;

	public function getRequestData() {
		return $this->aRequest;
	}

	/**
	 * Validate access. PHP will die if access is not allowed.
	 * @param array $request
	 */
	private function validateAccess($request) {
		// TODO: Das Formular muss für Ajax raus aus der Session!!
		if(!(array_key_exists('_SESSION', $GLOBALS) && array_key_exists('ameos_formidable', $GLOBALS['_SESSION']))) {
			$this->denyService('SESSION is not started !');
			return false;
		}
		if(!array_key_exists($this->aRequest['object'], $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['ajax_services'])) {
			$this->denyService('no object found: ' . $this->aRequest['object']);
		}

		if(!array_key_exists($this->aRequest['servicekey'], $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['ajax_services'][$this->aRequest['object']])) {
			$this->denyService('no service key');
		}
		// requested service exists

		if(	!is_array($GLOBALS['_SESSION']['ameos_formidable']['ajax_services'][$this->aRequest['object']][$this->aRequest['servicekey']]) ||
			!array_key_exists($this->aRequest['safelock'], $GLOBALS['_SESSION']['ameos_formidable']['ajax_services'][$this->aRequest['object']][$this->aRequest['servicekey']])
		) {
			$this->denyService('no safelock');
		}
	}

	function init() {
		$this->ttStart = microtime(true);
		$this->ttTimes = array();

		$this->aRequest = array(
			'safelock'		=> t3lib_div::_GP('safelock'),
			'object'		=> t3lib_div::_GP('object'),
			'servicekey'	=> t3lib_div::_GP('servicekey'),
			'eventid'		=> t3lib_div::_GP('eventid'),
			'serviceid'		=> t3lib_div::_GP('serviceid'),
			'value'			=> stripslashes(t3lib_div::_GP('value')),
			'formid'		=> t3lib_div::_GP('formid'),
			'thrower'		=> t3lib_div::_GP('thrower'),
			'arguments'		=> t3lib_div::_GP('arguments'),
			'trueargs'		=> t3lib_div::_GP('trueargs'),
		);

		// Wir starten zuerst die DB, damit das Caching funktioniert
		tslib_eidtools::connectDB();
		tx_rnbase::load('tx_mkforms_session_Factory');
		$sesMgr = tx_mkforms_session_Factory::getSessionManager();

		// TODO: es muss möglich sein freie PHP-Scripte per Ajax aufzurufen

		// valid session data
		$this->validateAccess($this->aRequest);

		// proceed then
		$this->aConf =& $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['ajax_services']
											[$this->aRequest['object']][$this->aRequest['servicekey']]['conf'];
		// Ein Array mit dem Key "requester"
		// Wird NIE verwenden...
		$this->aSession =&	$GLOBALS['_SESSION']['ameos_formidable']['ajax_services']
											[$this->aRequest['object']][$this->aRequest['servicekey']][$this->aRequest['safelock']];

		if(!tx_rnbase_util_TYPO3::isTYPO62OrHigher()) {
			require_once(PATH_tslib . 'class.tslib_content.php');
		}
		tx_rnbase::load('tx_mkforms_forms_Base');
		tx_rnbase::load('tx_mkforms_util_Div');
		tx_rnbase::load('tx_mkforms_util_Config');
		tx_rnbase::load('tx_mkforms_util_Validation');
		tx_rnbase::load('tx_mkforms_ds_db_Main');

		// Hier wird ein Array mit verschiedenen Objekten und Daten aus der Session geladen.
		$aHibernation =& $GLOBALS['_SESSION']['ameos_formidable']['hibernate'][$this->aRequest['formid']];
		// Das Formular aus der Session holen.
		$start = microtime(true);
		$this->oForm =& $sesMgr->restoreForm($this->aRequest['formid']);
		$this->ttTimes['frest'] = microtime(true) - $start;
		if(!$this->oForm) {
			$this->denyService('no hibernate; Check: that you have cookies enabled; that the formidable is NOT CACHED; Please configure the Cache for mkforms. @see ext_localconf.php');
		}

		$sesMgr->setForm($this->oForm);
		$formid = $this->oForm->getFormId();

		if($this->aConf['virtualizeFE']) {
			// Hier wird eine TSFE erstellt. Das hängt vom jeweiligen Ajax-Call ab.
			$start = microtime(true);
			$feConfig = $sesMgr->restoreFeConfig($formid);
			$feSetup = $sesMgr->restoreFeSetup($formid);
			// Das dauert hier echt lang. Ca. 70% der Init-Zeit
			tx_mkforms_util_Div::virtualizeFE($feConfig, $feSetup);
			$this->ttTimes['fecrest'] = microtime(true) - $start;

			$GLOBALS['TSFE']->config = $feConfig;
			$GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'] = $aHibernation['sys_language_uid'];
			$GLOBALS['TSFE']->tmpl->setup['config.']['tx_ameosformidable.'] = $aHibernation['formidable_tsconfig'];
			$GLOBALS['TSFE']->sys_language_uid = $aHibernation['sys_language_uid'];
			$GLOBALS['TSFE']->sys_language_content = $aHibernation['sys_language_content'];
			$GLOBALS['TSFE']->lang = $aHibernation['lang'];
			$GLOBALS['TSFE']->id = $aHibernation['pageid'];
			$GLOBALS['TSFE']->spamProtectEmailAddresses = $aHibernation['spamProtectEmailAddresses'];
			$GLOBALS['TSFE']->config['config']['spamProtectEmailAddresses_atSubst'] = $aHibernation['spamProtectEmailAddresses_atSubst'];
			$GLOBALS['TSFE']->config['config']['spamProtectEmailAddresses_lastDotSubst'] = $aHibernation['spamProtectEmailAddresses_lastDotSubst'];
		}

		if($this->aConf['initBEuser']) {
			$this->_initBeUser();
		}

		$start = microtime(true);
		$aRdtKeys = array_keys($this->oForm->aORenderlets);
		reset($aRdtKeys);
		while(list(,$sKey) = each($aRdtKeys)) {
			if(is_object($this->oForm->aORenderlets[$sKey])) {
				$this->oForm->aORenderlets[$sKey]->awakeInSession($this->oForm);
			}
		}
		$this->ttTimes['wgtrest'] = microtime(true) - $start;

		$start = microtime(true);
		reset($this->oForm->aODataSources);
		while(list($sKey,) = each($this->oForm->aODataSources)) {
			$this->oForm->aODataSources[$sKey]->awakeInSession($this->oForm);
		}
		$this->ttTimes['dsrest'] = microtime(true) - $start;

		$this->aRequest['params'] = $this->oForm->json2array($this->aRequest['value']);
		$this->aRequest['trueargs'] = $this->oForm->json2array($this->aRequest['trueargs']);

		$this->ttTimes['init'] = microtime(true) - $this->ttStart;
		return TRUE;
	}


	function _initFeUser() {
		tslib_eidtools::initFeUser();
	}

	public function handleRequest() {
		$this->oForm->aInitTasksAjax = array();
		$this->oForm->aPostInitTasksAjax = array();
		$this->oForm->aRdtEventsAjax = array();

		if($this->aRequest['servicekey'] == 'ajaxservice') {
			// Hier kommt direkt ein String
			$sJson = $this->getForm()->handleAjaxRequest($this);
		} else {
			// Hier kommt ein Array...
			if($this->aRequest['object'] == 'tx_ameosformidable') {
				$aData = $this->getForm()->handleAjaxRequest($this);
			} else {
				$thrower = $this->getWhoThrown();
				$widget = $this->getForm()->getWidget($thrower);
				if(!$widget) throw new Exception('Widget '.htmlspecialchars($thrower) . ' not found!');
				$aData = $widget->handleAjaxRequest($this);
			}

			if(!is_array($aData)) {
				$aData = array();
			}

			tx_rnbase::load('tx_mkforms_util_Json');
			$this->ttTimes['complete'] = (microtime(true) - $this->ttStart);

			// bei werten wie 1.59740447998E-5 wirft es sehr schnell JS Fehler!
			// Deswegen wandeln wie die erstmal in Strings um.
			$ttTimes = array();
			foreach ($this->ttTimes as $key => $time)
				$ttTimes[$key] = strval($time);

			$sJson = tx_mkforms_util_Json::getInstance()->encode (
				array(
					'init' => $this->oForm->aInitTasksAjax,
					'postinit' => $this->oForm->aPostInitTasksAjax,
					'attachevents' => $this->oForm->aRdtEventsAjax,
					// wenn die header als html (ajax damupload) ausgeliefert werden,
					// machen die script tags das json kaputt, wir müssen diese also encoden.
					// wir ersetzen nur die klammern
					'attachheaders' => str_replace(
							array('<', '>'),
							array('%3C', '%3E'),
							$this->oForm->getJSLoader()->getAjaxHeaders()
						),
					'tasks' => $aData,
					'time' => $ttTimes,
				)
			);
		}

		$this->archiveRequest($this->aRequest);

		if(($sCharset = $this->oForm->_navConf('charset', $this->oForm->aAjaxEvents[$this->aRequest['eventid']]['event'])) === FALSE) {
			if(($sCharset = $this->oForm->_navConf('/meta/ajaxcharset')) === FALSE) {
				$sCharset = 'UTF-8';
			}
		}

		$sesMgr = tx_mkforms_session_Factory::getSessionManager();
		$sesMgr->persistForm(true);

		// text/plain Will der IE nicht, deswegen text/html, damit sollten alle Browser klar kommen.
		header('Content-Type: text/html; charset=' . $sCharset);
		die($sJson);
	}

	/**
	 *
	 * @return tx_ameosformidable
	 */
	public function getForm() {
		return $this->oForm;
	}
	/**
	 * Die Methode wird noch in ameos_formidable::handleAjaxRequest aufgerufen.
	 *
	 * @param String $sMessage
	 */
	public function denyService($sMessage) {

		header('Content-Type: text/plain; charset=UTF-8');
		die('{/* SERVICE DENIED: ' . $sMessage . ' */}');
	}

	function _initBeUser() {

		global $BE_USER, $_COOKIE;

		$temp_TSFEclassName = tx_rnbase::makeInstanceClassName('tslib_fe');
		$TSFE = new $temp_TSFEclassName($GLOBALS['TYPO3_CONF_VARS'],0,0);
		$TSFE->connectToDB();

		// *********
		// BE_USER
		// *********
		$BE_USER='';
		if ($_COOKIE['be_typo_user']) {		// If the backend cookie is set, we proceed and checks if a backend user is logged in.

					// the value this->formfield_status is set to empty in order to disable login-attempts to the backend account through this script
				$BE_USER = t3lib_div::makeInstance('t3lib_tsfeBeUserAuth');	// New backend user object
				$BE_USER->OS = TYPO3_OS;
				$BE_USER->lockIP = $GLOBALS['TYPO3_CONF_VARS']['BE']['lockIP'];
				$BE_USER->start();			// Object is initialized
				$BE_USER->unpack_uc('');
				if ($BE_USER->user['uid'])	{
					$BE_USER->fetchGroupData();
					$TSFE->beUserLogin = 1;
				}
				if ($BE_USER->checkLockToIP() && $BE_USER->checkBackendAccessSettingsFromInitPhp())	{
					$BE_USER->extInitFeAdmin();
					if ($BE_USER->extAdmEnabled)	{
						require_once(t3lib_extMgm::extPath('lang').'lang.php');
						$LANG = t3lib_div::makeInstance('language');
						$LANG->init($BE_USER->uc['lang']);

						$BE_USER->extSaveFeAdminConfig();
							// Setting some values based on the admin panel
						$TSFE->forceTemplateParsing = $BE_USER->extGetFeAdminValue('tsdebug', 'forceTemplateParsing');
						$TSFE->displayEditIcons = $BE_USER->extGetFeAdminValue('edit', 'displayIcons');
						$TSFE->displayFieldEditIcons = $BE_USER->extGetFeAdminValue('edit', 'displayFieldIcons');

						if (t3lib_div::_GP('ADMCMD_editIcons'))	{
							$TSFE->displayFieldEditIcons=1;
							$BE_USER->uc['TSFE_adminConfig']['edit_editNoPopup']=1;
						}
						if (t3lib_div::_GP('ADMCMD_simUser'))	{
							$BE_USER->uc['TSFE_adminConfig']['preview_simulateUserGroup']=intval(t3lib_div::_GP('ADMCMD_simUser'));
							$BE_USER->ext_forcePreview=1;
						}
						if (t3lib_div::_GP('ADMCMD_simTime'))	{
							$BE_USER->uc['TSFE_adminConfig']['preview_simulateDate']=intval(t3lib_div::_GP('ADMCMD_simTime'));
							$BE_USER->ext_forcePreview=1;
						}

							// Include classes for editing IF editing module in Admin Panel is open
						if (($BE_USER->extAdmModuleEnabled('edit') && $BE_USER->extIsAdmMenuOpen('edit')) || $TSFE->displayEditIcons == 1)	{
							$TSFE->includeTCA();
							if ($BE_USER->extIsEditAction())	{
								$BE_USER->extEditAction();
							}
							if ($BE_USER->extIsFormShown())	{
							}
						}

						if ($TSFE->forceTemplateParsing || $TSFE->displayEditIcons || $TSFE->displayFieldEditIcons)	{ $TSFE->set_no_cache(); }
					}

				} else {	// Unset the user initialization.
					$BE_USER='';
					$TSFE->beUserLogin=0;
				}
		} elseif ($TSFE->ADMCMD_preview_BEUSER_uid)	{

				// the value this->formfield_status is set to empty in order to disable login-attempts to the backend account through this script
			$BE_USER = t3lib_div::makeInstance('t3lib_tsfeBeUserAuth');	// New backend user object
			$BE_USER->userTS_dontGetCached = 1;
			$BE_USER->OS = TYPO3_OS;
			$BE_USER->setBeUserByUid($TSFE->ADMCMD_preview_BEUSER_uid);
			$BE_USER->unpack_uc('');
			if ($BE_USER->user['uid'])	{
				$BE_USER->fetchGroupData();
				$TSFE->beUserLogin = 1;
			} else {
				$BE_USER = '';
				$TSFE->beUserLogin = 0;
			}
		}

		return $BE_USER;
	}

	function getWhoThrown() {

		$sThrower = $this->aRequest['thrower'];
		$aWho = explode(AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN, $sThrower);

		if(count($aWho) > 1) {
			array_shift($aWho);
			return implode(AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN, $aWho);
		}

		return FALSE;
	}

	function &getThrower() {
		if(($sWho = $this->getWhoThrown()) !== FALSE) {
			if(array_key_exists($sWho, $this->oForm->aORenderlets)) {
				return $this->oForm->aORenderlets[$sWho];
			}
		}

		return FALSE;
	}

	function getParams() {
		return $this->aRequest['params'];
	}

	function getParam($sParamName) {
		if(array_key_exists($sParamName, $this->aRequest['params'])) {
			return $this->aRequest['params'][$sParamName];
		}

		return FALSE;
	}

	function archiveRequest($aRequest) {
		$this->getForm()->archiveAjaxRequest($aRequest);
	}

	function getPreviousRequest() {
		return $this->oForm->getPreviousAjaxRequest();
	}

	function getPreviousParams() {
		return $this->oForm->getPreviousAjaxParams();
	}
}

try {
	$oAjax = new formidableajax();
	if($oAjax->init() === FALSE) {
		$oAjax->denyService(); // Damit wird der Prozess beendet.
		die();
	}
	$ret = $oAjax->handleRequest();

} catch(Exception $e) {
	tx_rnbase::load('tx_rnbase_util_Logger');
	if(tx_rnbase_util_Logger::isWarningEnabled()) {
		$request = $oAjax instanceof formidableajax ? $oAjax->getRequestData() : 'unkown';
		$widgets = $oAjax instanceof formidableajax && is_object($oAjax->getForm()) ? $oAjax->getForm()->getWidgetNames() : array();
		tx_rnbase_util_Logger::warn('Exception in ajax call', 'mkforms', array('Exception' => $e, 'Request'=> $request, 'Widgets' => $widgets));
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/remote/formidableajax.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/remote/formidableajax.php']);
}
