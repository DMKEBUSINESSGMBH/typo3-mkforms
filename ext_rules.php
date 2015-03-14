<?php
defined('TYPO3_MODE') || die('Access denied.');

require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_mksanitizedparameters_Rules');

//$rulesForFrontend = array(
// 	'value'	=> FILTER_UNSAFE_RAW
//);
$rulesForFrontend =
	unserialize('a:1:{s:5:"value";i:516;}');

tx_mksanitizedparameters_Rules::addRulesForFrontend($rulesForFrontend);
