<?php

defined('TYPO3_MODE') || exit('Access denied.');

$rulesForFrontend = unserialize('a:1:{s:5:"value";i:516;}');

tx_mksanitizedparameters_Rules::addRulesForFrontend($rulesForFrontend);
