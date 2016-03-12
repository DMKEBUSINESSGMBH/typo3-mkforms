<?php
defined('TYPO3_MODE') || die('Access denied.');

tx_rnbase::load('tx_mksanitizedparameters_Rules');

$rulesForFrontend = unserialize('a:1:{s:5:"value";i:516;}');

tx_mksanitizedparameters_Rules::addRulesForFrontend($rulesForFrontend);
