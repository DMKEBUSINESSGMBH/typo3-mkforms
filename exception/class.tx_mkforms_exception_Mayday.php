<?php

/**
 * Exception for mayday.
 *
 * @author     Michael Wagner <dev@dmk-business.de>
 */
class tx_mkforms_exception_Mayday extends \Sys25\RnBase\Exception\AdditionalException
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
