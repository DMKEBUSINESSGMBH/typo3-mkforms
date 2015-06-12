<?php

	if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

	require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
	tx_rnbase::load('tx_mkforms_util_Div');
	tx_rnbase::load('tx_mkforms_util_Constants');
	tx_rnbase::load('tx_rnbase_configurations');

	if(t3lib_extMgm::isLoaded('mksanitizedparameters')){
		require_once(t3lib_extMgm::extPath($_EXTKEY).'ext_rules.php');
	}

	// Predefine cache
	if(!is_array($TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['mkforms']) &&
	tx_rnbase_configurations::getExtensionCfgValue('mkforms', 'activateCache') ) {
		$TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['mkforms'] = array(
				'frontend' => 'TYPO3\CMS\Core\Cache\Frontend\VariableFrontend',
				'backend' => 'TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend',
				'options' => array(
				)
		);
	}

	// defines the Formidable ajax content-engine ID
	// TODO: Anpassen!
	$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include'][tx_mkforms_util_Div::getAjaxEId()] = 'EXT:mkforms/remote/formidableajax.php';

	if(TYPO3_MODE === 'FE') {
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = 'EXT:mkforms/hooks/class.tx_mkforms_hooks_TSFE.php:&tx_mkforms_hooks_TSFE->contentPostProc_output';
	}

	if (!defined('PATH_tslib')) {
		if (@is_dir(PATH_site.TYPO3_mainDir.'sysext/cms/tslib/')) {
			define('PATH_tslib', PATH_site.TYPO3_mainDir.'sysext/cms/tslib/');
		} elseif (@is_dir(PATH_site.'tslib/')) {
			define('PATH_tslib', PATH_site.'tslib/');
		}
	}


	if(!is_array($GLOBALS['EM_CONF']) || !array_key_exists($_EXTKEY, $GLOBALS['EM_CONF'])) {
		require_once(t3lib_extMgm::extPath('mkforms', 'ext_emconf.php'));
	}

		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['declaredobjects']['datasources'] = array(
			'DB'				=> array('key' => 'tx_mkforms_ds_db_Main'),
			'PHPARRAY'			=> array('key' => 'tx_mkforms_ds_phparray_Main'),
			'PHP'				=> array('key' => 'tx_mkforms_ds_php_Main'),
			'CONTENTREPOSITORY'	=> array('key' => 'tx_mkforms_ds_contentrepository_Main'),
		);
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['declaredobjects']['actionlets'] = array(
			'REDIRECT'	=> array('key' => 'tx_mkforms_action_redirect_Main'),
			'USEROBJ'	=> array('key' => 'tx_mkforms_action_userobj_Main'),
		);

		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['declaredobjects']['datahandlers'] = array(
			'DB'		=> array('key' => 'tx_mkforms_dh_db_Main'),
			'DBMM'		=> array('key' => 'tx_mkforms_dh_dbmm_Main'),
			'RAW'		=> array('key' => 'tx_mkforms_dh_raw_Main'),
			'STANDARD'	=> array('key' => 'tx_mkforms_dh_std_Main'),
			'MAIL'		=> array('key' => 'tx_mkforms_dh_mail_Main'),
			'VOID'		=> array('key' => 'tx_mkforms_dh_void_Main'),
		);

		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['declaredobjects']['renderers'] = array(
			'STANDARD'	=> array('key' => 'tx_mkforms_renderer_std_Main',		'base' => TRUE),
			'BACKEND'	=> array('key' => 'tx_mkforms_renderer_be_Main',		'base' => TRUE),
			'TEMPLATE'	=> array('key' => 'tx_mkforms_renderer_template_Main',	'base' => TRUE),
			'VOID'		=> array('key' => 'tx_mkforms_renderer_void_Main',		'base' => TRUE),
			'FLUID'		=> array('key' => 'tx_mkforms_renderer_fluid_Main',		'base' => TRUE),
		);

		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['declaredobjects']['renderlets'] = array(
			'CHECKBOX'		=> array('key' => 'tx_mkforms_widgets_checkbox_Main'),
			'CHECKSINGLE'	=> array('key' => 'tx_mkforms_widgets_checksingle_Main'),
			'DATE'			=> array('key' => 'tx_mkforms_widgets_date_Main'),
			'HIDDEN'		=> array('key' => 'tx_mkforms_widgets_hidden_Main'),
			'LISTBOX'		=> array('key' => 'tx_mkforms_widgets_listbox_Main'),
			'PASSTHRU'		=> array('key' => 'tx_mkforms_widgets_passthru_Main'),
			'PASSWORD'		=> array('key' => 'tx_mkforms_widgets_pwd_Main'),
			'RADIOBUTTON'	=> array('key' => 'tx_mkforms_widgets_radio_Main'),
			'LISTERSELECT'	=> array('key' => 'tx_mkforms_widgets_listerselect_Main'),
			'SUBMIT'		=> array('key' => 'tx_mkforms_widgets_submit_Main'),
			'RESET'			=> array('key' => 'tx_mkforms_widgets_reset_Main'),
			'TEXT'			=> array('key' => 'tx_mkforms_widgets_text_Main'),

			'BUTTON'		=> array('key' => 'tx_mkforms_widgets_button_Main'),
			'IMAGE'			=> array('key' => 'tx_mkforms_widgets_img_Main'),
			'TEXTAREA'		=> array('key' => 'tx_mkforms_widgets_txtarea_Main'),
			'BOX'			=> array('key' => 'tx_mkforms_widgets_box_Main'),
			'LABEL'			=> array('key' => 'tx_mkforms_widgets_label_Main'),
			'LINK'			=> array('key' => 'tx_mkforms_widgets_link_Main'),
			'CHOOSER'		=> array('key' => 'tx_mkforms_widgets_chooser_Main'),
			'CAPTCHA'		=> array('key' => 'tx_mkforms_widgets_captcha_Main'),
			'DEWPLAYER'		=> array('key' => 'tx_mkforms_widgets_dewplayer_Main'),
			'MODALBOX'		=> array('key' => 'tx_mkforms_widgets_modalbox_Main'),
			'TABPANEL'		=> array('key' => 'tx_mkforms_widgets_tabpanel_Main'),
			'TAB'			=> array('key' => 'tx_mkforms_widgets_tab_Main'),
			'I18N'			=> array('key' => 'tx_mkforms_widgets_i18n_Main'),
			'SEARCHFORM'	=> array('key' => 'tx_mkforms_widgets_searchform_Main'),
			'LISTER'		=> array('key' => 'tx_mkforms_widgets_lister_Main'),
			'UPLOAD'		=> array('key' => 'tx_mkforms_widgets_upload_Main'),
			'SELECTOR'		=> array('key' => 'tx_mkforms_widgets_selector_Main'),
			'SWFUPLOAD'		=> array('key' => 'tx_mkforms_widgets_swfupload_Main'),
			'DAMUPLOAD'		=> array('key' => 'tx_mkforms_widgets_damupload_Main'),
			'MEDIAUPLOAD'	=> array('key' => 'tx_mkforms_widgets_damupload_Main'),
			'ACCORDION'		=> array('key' => 'tx_mkforms_widgets_accordion_Main'),
			'PROGRESSBAR'	=> array('key' => 'tx_mkforms_widgets_progressbar_Main'),
			'TICKER'		=> array('key' => 'tx_mkforms_widgets_ticker_Main'),
			'AUTOCOMPLETE'	=> array('key' => 'tx_mkforms_widgets_autocomplete_Main'),
			'MODALBOX2'		=> array('key' => 'tx_mkforms_widgets_modalbox2_Main'),
			'JSTREE'		=> array('key' => 'tx_mkforms_widgets_jstree_Main'),
			'FLUIDVIEWHELPER' => array('key' => 'tx_mkforms_widgets_fluidviewhelper_Main'),
		);

		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['declaredobjects']['validators'] = array(
			'DB'		=> array('key' => 'tx_mkforms_validator_db_Main'),
			'STANDARD'	=> array('key' => 'tx_mkforms_validator_std_Main'),
			'FILE'		=> array('key' => 'tx_mkforms_validator_file_Main'),
			'PREG'		=> array('key' => 'tx_mkforms_validator_preg_Main'),
			'NUM'		=> array('key' => 'tx_mkforms_validator_num_Main'),
		);

		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['ajax_services']['tx_ameosformidable']['ajaxevent']['conf'] = array(
			'virtualizeFE'	=> TRUE,
			'initBEuser'	=> FALSE,
		);

		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['ajax_services']['tx_ameosformidable']['ajaxservice']['conf'] = array(
			'virtualizeFE'	=> TRUE,
			'initBEuser'	=> FALSE,
		);

		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['ajax_services']['rdt_ajaxlist']['content']['conf'] = array(
			'virtualizeFE'	=> TRUE,
			'initBEuser'	=> FALSE,
		);

		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['ajax_services']['widget_damupload']['upload']['conf'] = array(
			'virtualizeFE'	=> TRUE,
			'initBEuser'	=> FALSE,
		);

		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['ajax_services']['rdt_swfupload']['upload']['conf'] = array(
			'virtualizeFE'	=> FALSE,
			'initBEuser'	=> FALSE,
		);

		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['ajax_services']['rdt_autocomplete']['lister']['conf'] = array(
			'virtualizeFE'	=> FALSE,
			'initBEuser'	=> FALSE,
		);

//das ist nur eine info für entwickler welcher basis exception code
//für diese extension verwendet wird. in diesem fall 200.
//also könnte ein valider exception code dieser extension 2001 sein
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['baseExceptionCode'] = 200;

?>