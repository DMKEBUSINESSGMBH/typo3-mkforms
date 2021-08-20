<?php
/**
 * @author Hannes Bochmann
 *
 *  Copyright notice
 *
 *  (c) 2010 Hannes Bochmann <dev@dmk-business.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 */

/**
 * benötigte Klassen einbinden.
 */
/**
 * Statische Hilfsmethoden für Tests.
 */
class tx_mkforms_tests_Util
{
    /**
     * @param bool $force
     */
    public static function getStaticTS($force = false)
    {
        static $configArray = false;
        if (is_array($configArray) && !$force) {
            return $configArray;
        }
        tx_rnbase_util_Extensions::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:mkforms/static/ts/setup.txt">');

        tx_rnbase_util_Misc::prepareTSFE(); // Ist bei Aufruf aus BE notwendig!

        /*
         * pk: Danke mw
         * getPagesTSconfig benutzt static Cache und bearbeitet nicht das was wir mit addPageTSConfig hinzugefügt haben.
         * um das umzugehen, kann man das RootLine Parameter leer setzen.
         * Siehe: TYPO3\CMS\Backend\Utility\BackendUtility:getPagesTSconfig();
         */
        $tsConfig = Tx_Rnbase_Backend_Utility::getPagesTSconfig(0, '');

        $configArray = $tsConfig['plugin.']['tx_mkforms.'];
        // für referenzen im TS!
        $GLOBALS['TSFE']->tmpl->setup['lib.']['mkforms.'] = $tsConfig['lib.']['mkforms.'];
        $GLOBALS['TSFE']->tmpl->setup['config.']['tx_mkforms.'] = $tsConfig['config.']['tx_mkforms.'];
        $GLOBALS['TSFE']->config['config.']['tx_mkforms.'] = $tsConfig['config.']['tx_mkforms.'];

        return $configArray;
    }

    /**
     * Liefert ein Form Objekt.
     *
     * @return tx_mkforms_forms_Base
     */
    public static function getForm(
        $bCsrfProtection = true,
        $aConfigArray = [],
        $parent = null,
        $oForm = null
    ) {
        if (null == $oForm) {
            $oForm = tx_mkforms_forms_Factory::createForm('generic');
            $oForm->setTestMode();
        }

        $oParameters = tx_rnbase::makeInstance('tx_rnbase_parameters');
        $oParameters->init('mkforms');

        $oConfigurations = tx_rnbase::makeInstance('tx_rnbase_configurations');
        if (!$aConfigArray) {
            $aConfigArray = self::getDefaultFormConfig($bCsrfProtection);
        }
        $oConfigurations->init(
            $aConfigArray,
            $oConfigurations->getCObj(1),
            'mkforms',
            'mkforms'
        );
        $oConfigurations->setParameters($oParameters);

        // the default behaiviour is to have a USER_INT plugin
        $contentObjectRendererClass = tx_rnbase_util_Typo3Classes::getContentObjectRendererClass();
        $oConfigurations->getCObj()->setUserObjectType($contentObjectRendererClass::OBJECTTYPE_USER_INT);

        if (!$parent) {
            $parent = new stdClass();
        }

        $oForm->init(
            $parent,
            $oConfigurations->get('generic.xml'),
            0,
            $oConfigurations,
            'generic.formconfig.'
        );

        // logoff für phpmyadmin deaktivieren
        /*
         * Error in test case test_handleRequest
         * in file C:\xampp\htdocs\typo3\typo3conf\ext\phpmyadmin\res\class.tx_phpmyadmin_utilities.php
         * on line 66:
         * Message:
         * Cannot modify header information - headers already sent by (output started at C:\xampp\htdocs\typo3\typo3conf\ext\phpunit\mod1\class.tx_phpunit_module1.php:112)
         *
         * Diese Fehler passiert, wenn die usersession ausgelesen wird. der feuser hat natürlich keine.
         * Das Ganze passiert in der t3lib_userauth->fetchUserSession.
         * Dort wird t3lib_userauth->logoff aufgerufen, da keine session vorhanden ist.
         * phpmyadmin klingt sich da ein und schreibt daten in die session.
         */
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing'] as $k => $v) {
                if ($v = 'tx_phpmyadmin_utilities->pmaLogOff') {
                    unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing'][$k]);
                }
            }
        }

        return $oForm;
    }

    public static function getDefaultFormConfig($bCsrfProtection = true)
    {
        return [
            'generic.' => [
                'xml' => 'EXT:mkforms/tests/xml/renderlets.xml',
                'addfields.' => [
                        'widget-addfield' => 'addfield feld',
                        'widget-remove' => 'unset',
                    ],
                'fieldSeparator' => '-',
                'addPostVars' => 1,
                'formconfig.' => [
                    'loadJsFramework' => 0, // formconfig für config check setzen.
                    'csrfProtection' => $bCsrfProtection,
                    'checkWidgetsExist' => 1,
                ],
            ],
        ];
    }

    /**
     * Setzt die werte aus dem array für die korrespondierenden widgets.
     * bei boxen wird rekursiv durchgegangen.
     *
     * @param array $aData |   Die Daten wie sie in processForm ankommen
     * @param $oForm
     */
    public static function setWidgetValues($aData, $oForm)
    {
        foreach ($aData as $sName => $mValue) {
            if (is_array($mValue)) {
                self::setWidgetValues($mValue, $oForm);
            } else {
                $oForm->getWidget($sName)->setValue($mValue);
            }
        }
    }

    /**
     * @param string $formId
     * @param array  $formData
     * @param string $requestToken
     */
    public static function setRequestTokenForFormId(
        $formId,
        array &$formData,
        $requestToken = 's3cr3tT0k3n'
    ) {
        $formData['MKFORMS_REQUEST_TOKEN'] = $requestToken;

        $GLOBALS['TSFE']->fe_user->setKey(
            'ses',
            'mkforms',
            ['requestToken' => [
                    $formId => $requestToken,
                ],
            ]
        );
        $GLOBALS['TSFE']->fe_user->storeSessionData();
    }

    /**
     * warning "Cannot modify header information" abfangen.
     *
     * Einige Tests lassen sich leider nicht ausführen:
     * "Cannot modify header information - headers already sent by"
     * Diese wird an unterschiedlichen stellen ausgelöst,
     * meißt jedoch bei Session operationen
     * Ab Typo3 6.1 laufend die Tests auch auf der CLI nicht.
     * Eigentlich gibt es dafür die runInSeparateProcess Anotation,
     * Allerdings funktioniert diese bei Typo3 nicht, wenn versucht wird
     * die GLOBALS in den anderen Prozess zu Übertragen.
     * Ein Deaktivierend er Übertragung führt dazu,
     * das Typo3 nicht initialisiert ist.
     *
     * Wir gehen also erst mal den Weg, den Fehler abzufangen.
     *
     * @param int    $errno
     * @param string $errstr
     * @param string $errfile
     * @param int    $errline
     * @param array  $errcontext
     *
     * @return mixed
     */
    public static function errorHandler($errno, $errstr, $errfile, $errline, $errcontext)
    {
        $ignoreMsg = 'Cannot modify header information - headers already sent by';
        if (false !== strpos($errstr, $ignoreMsg)) {
            // Don't execute PHP internal error handler
            return true;
        }

        return null;
    }
}
