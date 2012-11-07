<?php
	
	if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

	require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
	tx_rnbase::load('tx_mkforms_util_Div');
	
	// Predefine cache
	// This section has to be included in typo3conf/localconf.php!!
//	$TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations'] = array(
//	    'mkforms' => array(
//	        'backend' => 't3lib_cache_backend_DbBackend',
//	        'options' => array(
//	            'cacheTable' => 'tx_mkforms_cache',
//	            'tagsTable' => 'tx_mkforms_tags',
//	        )
//	    )
//	);
	#define("PATH_formidable", t3lib_extMgm::extPath("ameos_formidable"));
	#define("PATH_formidableapi", PATH_formidable . "api/class.tx_ameosformidable.php");

// mkforms: Konstanten PATH_formidable und PATH_formidableapi entfernt
//	if(file_exists(PATH_site . "typo3conf/ext/ameos_formidable") && is_dir(PATH_site . "typo3conf/ext/ameos_formidable")) {
//		define("PATH_formidable", PATH_site . "typo3conf/ext/ameos_formidable/");
//	}
//	
//	define("PATH_formidableapi", PATH_formidable . "api/class.tx_ameosformidable.php");


	// mkforms: define XCLASS to t3lib_tsparser entfernt
//	$TYPO3_CONF_VARS['FE']['XCLASS']['t3lib/class.t3lib_tsparser.php'] = PATH_formidable . "res/xclass/class.ux_t3lib_tsparser.php";
//	$TYPO3_CONF_VARS['BE']['XCLASS']['t3lib/class.t3lib_tsparser.php'] = PATH_formidable . "res/xclass/class.ux_t3lib_tsparser.php";

	// defines the Formidable ajax content-engine ID
	// TODO: Anpassen!
	$TYPO3_CONF_VARS['FE']['eID_include'][tx_mkforms_util_Div::getAjaxEId()] = 'EXT:mkforms/remote/formidableajax.php';
	
	if(TYPO3_MODE === 'FE') {
		$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = 'EXT:mkforms/hooks/class.tx_mkforms_hooks_TSFE.php:&tx_mkforms_hooks_TSFE->contentPostProc_output';
	}

	// defines content objects FORMIDABLE (cached) and FORMIDABLE_INT (not cached)
	// TODO: Prüfen!
//	$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_content.php']['cObjTypeAndClass'][] = array(
//		0 => 'FORMIDABLE',
//		1 => 'EXT:mkforms/api/class.user_ameosformidable_cobj.php:user_ameosformidable_cobj',
//	);
//	$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_content.php']['cObjTypeAndClass'][] = array(
//		0 => 'FORMIDABLE_INT',
//		1 => 'EXT:mkforms/api/class.user_ameosformidable_cobj.php:user_ameosformidable_cobj',
//	);

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
	
// TODO: Prüfen!
//	if(!array_key_exists('mkforms', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'])) {
//			require_once(t3lib_extMgm::extPath('mkforms', 'ext_emconf.php'));
//			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms'] = array(
//				'ext_emconf.php' => $EM_CONF[$_EXTKEY],
//				'declaredobjects' => array(
//					'validators' => array(),
//					'datahandlers' => array(),
//					'datasources' => array(),
//					'renderers' => array(),
//					'renderlets' => array(),
//					'actionlets' => array(),
//				),
//				'validators' => array(),
//				'datahandlers' => array(),
//				'datasources' => array(),
//				'renderers' => array(),
//				'renderlets' => array(),
//				'actionlets' => array(),
//				'ajax_services' => array(),
//				'context' => array(
//					'forms' => array(),
//					'be_headerdata' => array(),
//				)
//			);
//	}

		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['declaredobjects']['datasources'] = array(
			'DB'				=> array('key' => 'tx_mkforms_ds_db_Main'),
			'PHPARRAY'			=> array('key' => 'tx_mkforms_ds_phparray_Main'),
			'PHP'				=> array('key' => 'tx_mkforms_ds_php_Main'),
			'CONTENTREPOSITORY'	=> array('key' => 'tx_mkforms_ds_contentrepository_Main'),
		);
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['declaredobjects']['actionlets'] = array(
			#'MAIL'		=> array('key' => 'act_mail',		'base' => TRUE),	// deprecated
			'REDIRECT'	=> array('key' => 'tx_mkforms_action_redirect_Main'),
			'USEROBJ'	=> array('key' => 'tx_mkforms_action_userobj_Main'),
		);

		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['declaredobjects']['datahandlers'] = array(
			'DB'		=> array('key' => 'tx_mkforms_dh_db_Main'),
			#'LISTER'	=> array('key' => 'dh_lister',	'base' => TRUE),		// deprecated
			'RAW'		=> array('key' => 'tx_mkforms_dh_raw_Main'),
			'STANDARD'	=> array('key' => 'tx_mkforms_dh_std_Main'),
			'VOID'		=> array('key' => 'tx_mkforms_dh_void_Main'),
		);

		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['declaredobjects']['renderers'] = array(
			'STANDARD'	=> array('key' => 'tx_mkforms_renderer_std_Main',		'base' => TRUE),
			'BACKEND'	=> array('key' => 'tx_mkforms_renderer_be_Main',			'base' => TRUE),
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
#			'URL'			=> array('key' => 'rdt_url'),
			'TEXTAREA'		=> array('key' => 'tx_mkforms_widgets_txtarea_Main'),
			'BOX'			=> array('key' => 'tx_mkforms_widgets_box_Main'),
			'LINK'			=> array('key' => 'tx_mkforms_widgets_link_Main'),
			'CHOOSER'		=> array('key' => 'tx_mkforms_widgets_chooser_Main'),
			'CAPTCHA'		=> array('key' => 'tx_mkforms_widgets_captcha_Main'),
			'DEWPLAYER'		=> array('key' => 'tx_mkforms_widgets_dewplayer_Main'),
//			'TINYMCE'		=> array('key' => 'tx_mkforms_widgets_tinymce_Main'),
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
			'ACCORDION'		=> array('key' => 'tx_mkforms_widgets_accordion_Main'),
			'PROGRESSBAR'	=> array('key' => 'tx_mkforms_widgets_progressbar_Main'),
			'TICKER'		=> array('key' => 'tx_mkforms_widgets_ticker_Main'),
			'AUTOCOMPLETE'	=> array('key' => 'tx_mkforms_widgets_autocomplete_Main'),
			'MODALBOX2'		=> array('key' => 'tx_mkforms_widgets_modalbox2_Main'),
			'JSTREE'		=> array('key' => 'tx_mkforms_widgets_jstree_Main'),
		);

		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['declaredobjects']['validators'] = array(
			'DB'		=> array('key' => 'tx_mkforms_validator_db_Main'),
			'STANDARD'	=> array('key' => 'tx_mkforms_validator_std_Main'),
			'FILE'		=> array('key' => 'tx_mkforms_validator_file_Main'),
			'PREG'		=> array('key' => 'tx_mkforms_validator_preg_Main'),
			'NUM'		=> array('key' => 'tx_mkforms_validator_num_Main'),
		);

		/*tx_ameosformidable::declareAjaxService(
			'tx_ameosformidable',		// formidable object handling this service
			'ajaxevent',				// service key (for this object)
			TRUE,				// virtualize FE
			FALSE				// init BE USER
		);
		
		tx_ameosformidable::declareAjaxService(
			'rdt_ajaxlist',		// ajaxlist handling this service
			'content',			// service key (for this object)
			TRUE,				// virtualize FE
			FALSE				// init BE USER
		);*/

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