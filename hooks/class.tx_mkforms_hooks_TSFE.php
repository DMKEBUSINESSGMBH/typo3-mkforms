<?php

class tx_mkforms_hooks_TSFE {
	function contentPostProc_output() {
		if (isset($GLOBALS['tx_ameosformidable']) && isset($GLOBALS['tx_ameosformidable']['headerinjection'])) {

			reset($GLOBALS['tx_ameosformidable']['headerinjection']);
			while (list(, $aHeaderSet) = each($GLOBALS['tx_ameosformidable']['headerinjection'])) {
				$GLOBALS['TSFE']->content = str_replace(
					$aHeaderSet['marker'],
					implode("\n", $aHeaderSet['headers']) . "\n" . $aHeaderSet['marker'],
					$GLOBALS['TSFE']->content
				);
			}
		}
	}
}
