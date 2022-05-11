<?php

defined('TYPO3') || exit('Access denied.');

$rulesForFrontend = unserialize('a:1:{s:5:"value";i:516;}');

\DMK\MkSanitizedParameters\Rules::addRulesForFrontend($rulesForFrontend);
