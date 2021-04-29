<?php

if (!defined('TYPO3_MODE')) {
    exit('Access denied.');
}

if (tx_rnbase_util_Extensions::isLoaded('mksanitizedparameters')) {
    require_once tx_rnbase_util_Extensions::extPath('mkforms').'ext_rules.php';
}
// Predefine cache
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['mkforms'])
    && tx_rnbase_configurations::getExtensionCfgValue('mkforms', 'activateCache')
) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['mkforms'] = [
        'frontend' => 'TYPO3\CMS\Core\Cache\Frontend\VariableFrontend',
        'backend' => 'TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend',
        'options' => [],
    ];
}

// defines the Formidable ajax content-engine ID
// TODO: Anpassen!
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include'][tx_mkforms_util_Div::getAjaxEId()] = 'EXT:mkforms/remote/formidableajax.php';

if (TYPO3_MODE === 'FE') {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][]
        = 'tx_mkforms_hooks_TSFE->contentPostProc_output';
}

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['declaredobjects']['datasources'] = [
    'DB' => ['key' => 'tx_mkforms_ds_db_Main'],
    'PHPARRAY' => ['key' => 'tx_mkforms_ds_phparray_Main'],
    'PHP' => ['key' => 'tx_mkforms_ds_php_Main'],
    'CONTENTREPOSITORY' => ['key' => 'tx_mkforms_ds_contentrepository_Main'],
];
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['declaredobjects']['actionlets'] = [
    'REDIRECT' => ['key' => 'tx_mkforms_action_redirect_Main'],
    'USEROBJ' => ['key' => 'tx_mkforms_action_userobj_Main'],
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['declaredobjects']['datahandlers'] = [
    'DB' => ['key' => 'tx_mkforms_dh_db_Main'],
    'DBMM' => ['key' => 'tx_mkforms_dh_dbmm_Main'],
    'RAW' => ['key' => 'tx_mkforms_dh_raw_Main'],
    'STANDARD' => ['key' => 'tx_mkforms_dh_std_Main'],
    'MAIL' => ['key' => 'tx_mkforms_dh_mail_Main'],
    'VOID' => ['key' => 'tx_mkforms_dh_void_Main'],
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['declaredobjects']['renderers'] = [
    'STANDARD' => ['key' => 'tx_mkforms_renderer_std_Main', 'base' => true],
    'BACKEND' => ['key' => 'tx_mkforms_renderer_be_Main', 'base' => true],
    'TEMPLATE' => ['key' => 'tx_mkforms_renderer_template_Main', 'base' => true],
    'VOID' => ['key' => 'tx_mkforms_renderer_void_Main', 'base' => true],
    'FLUID' => ['key' => 'tx_mkforms_renderer_fluid_Main', 'base' => true],
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['declaredobjects']['renderlets'] = [
    'CHECKBOX' => ['key' => 'tx_mkforms_widgets_checkbox_Main'],
    'CHECKSINGLE' => ['key' => 'tx_mkforms_widgets_checksingle_Main'],
    'DATE' => ['key' => 'tx_mkforms_widgets_date_Main'],
    'HIDDEN' => ['key' => 'tx_mkforms_widgets_hidden_Main'],
    'LISTBOX' => ['key' => 'tx_mkforms_widgets_listbox_Main'],
    'PASSTHRU' => ['key' => 'tx_mkforms_widgets_passthru_Main'],
    'PASSWORD' => ['key' => 'tx_mkforms_widgets_pwd_Main'],
    'RADIOBUTTON' => ['key' => 'tx_mkforms_widgets_radio_Main'],
    'LISTERSELECT' => ['key' => 'tx_mkforms_widgets_listerselect_Main'],
    'SUBMIT' => ['key' => 'tx_mkforms_widgets_submit_Main'],
    'RESET' => ['key' => 'tx_mkforms_widgets_reset_Main'],
    'TEXT' => ['key' => 'tx_mkforms_widgets_text_Main'],
    'BUTTON' => ['key' => 'tx_mkforms_widgets_button_Main'],
    'IMAGE' => ['key' => 'tx_mkforms_widgets_img_Main'],
    'TEXTAREA' => ['key' => 'tx_mkforms_widgets_txtarea_Main'],
    'BOX' => ['key' => 'tx_mkforms_widgets_box_Main'],
    'LABEL' => ['key' => 'tx_mkforms_widgets_label_Main'],
    'LINK' => ['key' => 'tx_mkforms_widgets_link_Main'],
    'CHOOSER' => ['key' => 'tx_mkforms_widgets_chooser_Main'],
    'CAPTCHA' => ['key' => 'tx_mkforms_widgets_captcha_Main'],
    'DEWPLAYER' => ['key' => 'tx_mkforms_widgets_dewplayer_Main'],
    'MODALBOX' => ['key' => 'tx_mkforms_widgets_modalbox_Main'],
    'TABPANEL' => ['key' => 'tx_mkforms_widgets_tabpanel_Main'],
    'TAB' => ['key' => 'tx_mkforms_widgets_tab_Main'],
    'I18N' => ['key' => 'tx_mkforms_widgets_i18n_Main'],
    'SEARCHFORM' => ['key' => 'tx_mkforms_widgets_searchform_Main'],
    'LISTER' => ['key' => 'tx_mkforms_widgets_lister_Main'],
    'UPLOAD' => ['key' => 'tx_mkforms_widgets_upload_Main'],
    'SELECTOR' => ['key' => 'tx_mkforms_widgets_selector_Main'],
    'SWFUPLOAD' => ['key' => 'tx_mkforms_widgets_swfupload_Main'],
    'DAMUPLOAD' => ['key' => 'tx_mkforms_widgets_mediaupload_Main'],
    'MEDIAUPLOAD' => ['key' => 'tx_mkforms_widgets_mediaupload_Main'],
    'ACCORDION' => ['key' => 'tx_mkforms_widgets_accordion_Main'],
    'PROGRESSBAR' => ['key' => 'tx_mkforms_widgets_progressbar_Main'],
    'TICKER' => ['key' => 'tx_mkforms_widgets_ticker_Main'],
    'AUTOCOMPLETE' => ['key' => 'tx_mkforms_widgets_autocomplete_Main'],
    'MODALBOX2' => ['key' => 'tx_mkforms_widgets_modalbox2_Main'],
    'JSTREE' => ['key' => 'tx_mkforms_widgets_jstree_Main'],
    'FLUIDVIEWHELPER' => ['key' => 'tx_mkforms_widgets_fluidviewhelper_Main'],
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['declaredobjects']['validators'] = [
    'DB' => ['key' => 'tx_mkforms_validator_db_Main'],
    'STANDARD' => ['key' => 'tx_mkforms_validator_std_Main'],
    'FILE' => ['key' => 'tx_mkforms_validator_file_Main'],
    'PREG' => ['key' => 'tx_mkforms_validator_preg_Main'],
    'NUM' => ['key' => 'tx_mkforms_validator_num_Main'],
    'TIMETRACKING' => ['key' => 'tx_mkforms_validator_timetracking_Main'],
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['ajax_services']['tx_ameosformidable']['ajaxevent']['conf'] = [
    'virtualizeFE' => true,
    'initBEuser' => false,
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['ajax_services']['tx_ameosformidable']['ajaxservice']['conf'] = [
    'virtualizeFE' => true,
    'initBEuser' => false,
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['ajax_services']['rdt_ajaxlist']['content']['conf'] = [
    'virtualizeFE' => true,
    'initBEuser' => false,
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['ajax_services']['widget_mediaupload']['upload']['conf'] = [
    'virtualizeFE' => true,
    'initBEuser' => false,
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['ajax_services']['rdt_swfupload']['upload']['conf'] = [
    'virtualizeFE' => false,
    'initBEuser' => false,
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['ajax_services']['rdt_autocomplete']['lister']['conf'] = [
    'virtualizeFE' => false,
    'initBEuser' => false,
];

//das ist nur eine info für entwickler welcher basis exception code
//für diese extension verwendet wird. in diesem fall 200.
//also könnte ein valider exception code dieser extension 2001 sein
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['baseExceptionCode'] = 200;

require_once tx_rnbase_util_Extensions::extPath('mkforms', 'Classes/Constants.php');
