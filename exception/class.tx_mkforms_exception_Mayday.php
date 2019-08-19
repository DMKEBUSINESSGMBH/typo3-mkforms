<?php

/**
 * Exception for mayday.
 *
 * @author     Michael Wagner <dev@dmk-business.de>
 */
class tx_mkforms_exception_Mayday extends tx_rnbase_util_Exception
{
    /**
     * Liefert den Stacktrace und konvertiert ihn (htmlspecialchars).
     * Verhindert das die Exception-E-Mail zerstört werden,
     * da hier immer unvollständiger HTML-Code enthalten ist!
     *
     * @return string
     */
    public function __toString()
    {
        $stack = parent::__toString();

        // html  konvertieren, damit die exception mail nicht zerstört wird!
        return htmlspecialchars($stack);
    }
}

if (defined('TYPO3_MODE')
    && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/exception/class.tx_mkforms_exception_Mayday.php']
) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/exception/class.tx_mkforms_exception_Mayday.php'];
}
