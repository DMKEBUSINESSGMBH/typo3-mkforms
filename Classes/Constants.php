<?php

if (!defined('SERVICES_JSON_SLICE')) {
    /**
     * Marker constant for Services_JSON::decode(), used to flag stack state
     */
    define('SERVICES_JSON_SLICE', 1);

    /**
     * Marker constant for Services_JSON::decode(), used to flag stack state
     */
    define('SERVICES_JSON_IN_STR', 2);

    /**
     * Marker constant for Services_JSON::decode(), used to flag stack state
     */
    define('SERVICES_JSON_IN_ARR', 3);

    /**
     * Marker constant for Services_JSON::decode(), used to flag stack state
     */
    define('SERVICES_JSON_IN_OBJ', 4);

    /**
     * Marker constant for Services_JSON::decode(), used to flag stack state
     */
    define('SERVICES_JSON_IN_CMT', 5);

    /**
     * Behavior switch for Services_JSON::decode()
     */
    define('SERVICES_JSON_LOOSE_TYPE', 16);

    /**
     * Behavior switch for Services_JSON::decode()
     */
    define('SERVICES_JSON_SUPPRESS_ERRORS', 32);

    define('AMEOSFORMIDABLE_VALUE_NOT_SET', 'AMEOSFORMIDABLE_VALUE_NOT_SET');

    define('AMEOSFORMIDABLE_EVENT_SUBMIT_FULL', 'AMEOSFORMIDABLE_EVENT_SUBMIT_FULL');
    define('AMEOSFORMIDABLE_EVENT_SUBMIT_REFRESH', 'AMEOSFORMIDABLE_EVENT_SUBMIT_REFRESH');
    define('AMEOSFORMIDABLE_EVENT_SUBMIT_TEST', 'AMEOSFORMIDABLE_EVENT_SUBMIT_TEST');
    define('AMEOSFORMIDABLE_EVENT_SUBMIT_DRAFT', 'AMEOSFORMIDABLE_EVENT_SUBMIT_DRAFT');
    define('AMEOSFORMIDABLE_EVENT_SUBMIT_CLEAR', 'AMEOSFORMIDABLE_EVENT_SUBMIT_CLEAR');
    define('AMEOSFORMIDABLE_EVENT_SUBMIT_SEARCH', 'AMEOSFORMIDABLE_EVENT_SUBMIT_SEARCH');

    define('AMEOSFORMIDABLE_LEXER_VOID', 'AMEOSFORMIDABLE_LEXER_VOID');
    define('AMEOSFORMIDABLE_LEXER_FAILED', 'AMEOSFORMIDABLE_LEXER_FAILED');
    define('AMEOSFORMIDABLE_LEXER_BREAKED', 'AMEOSFORMIDABLE_LEXER_BREAKED');

    define('AMEOSFORMIDABLE_XPATH_FAILED', 'AMEOSFORMIDABLE_XPATH_FAILED');
    define('AMEOSFORMIDABLE_TS_FAILED', 'AMEOSFORMIDABLE_TS_FAILED');

    define('AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN', '__');
    define('AMEOSFORMIDABLE_NESTED_SEPARATOR_END', '');

    define('AMEOSFORMIDABLE_NOTSET', 'AMEOSFORMIDABLE_NOTSET');
}
