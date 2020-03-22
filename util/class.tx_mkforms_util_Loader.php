<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 René Nitzsche (dev@dmk-business.de)
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
 ***************************************************************/

/**
 * Loading classes.
 */
class tx_mkforms_util_Loader
{
    private $formId = false;
    private $runningObjects = [];
    private $loadedClasses = [];
    private static $instances = [];

    /**
     * constructor.
     *
     * @param string $formid
     */
    public function __construct($formid = false)
    {
        $this->formId = $formid;
    }

    /**
     * Liefert eine Instanz der Klasse für ein bestimmtes Form.
     *
     * @param string $formid
     *
     * @return tx_mkforms_util_Loader
     */
    public static function getInstance($formid)
    {
        if (!array_key_exists((string) $formid, self::$instances)) {
            self::$instances[$formid] = new tx_mkforms_util_Loader((string) $formid);
        }

        return self::$instances[$formid];
    }

    /**
     * Makes and initializes an object.
     *
     * @param array  $aElement: conf for this object instance
     * @param array  $sNature:  renderers, datahandlers, ...
     * @param string $sXPath:   xpath where this conf is declared
     *
     * @return object
     */
    public function &makeObject($aElement, $objectType, $sXPath, $form, $sNamePrefix = false, $aOParent = [])
    {
        $objectKey = $aElement['type'];
        $aObj = self::loadObject($objectKey, $objectType, $form);

        // Das Objekt speichern, damit es bei einer Serialisierung nicht vergessen wird
        $this->runningObjects[$objectType.'::'.$objectKey] = ['internalkey' => $objectKey, 'objecttype' => $objectType];
        // calls tx_myrdtclass::loaded();
        // params are not passed by ref with call_user_func, so have to pass an array with &
        $aLoadedObjects = &$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms'][$objectType];
        $params = ['form' => &$form];
        call_user_func_array([$aLoadedObjects[$objectKey]['CLASS'], 'loaded'], [&$params]);

        if (!is_array($aObj)) {
            tx_mkforms_util_Div::mayday('TYPE '.$aElement['type'].' is not associated to any '.$objectType);
        }

        $oObj = tx_rnbase::makeInstance($aObj['CLASS']);

        if (!empty($aOParent) && is_object($aOParent[0])) {
            $oObj->setParent($aOParent[0]);
        }

        $oObj->_init($form, $aElement, $aObj, $sXPath, $sNamePrefix);

        return $oObj;
    }

    /**
     * Hier wird eine PHP-Klasse für ein Form-Element geladen. Diese Methode ist static. Sie wird bei der
     * normalen Form-Erstellung verwendet und bei Ajax-Calls.
     *
     * @param string $objectKey
     * @param string $objectType
     *
     * @return unknown
     */
    public static function loadObject($objectKey, $objectType)
    {
        $declaredObjects = &$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms']['declaredobjects'][$objectType];
        if (!is_array($declaredObjects) || !array_key_exists($objectKey, $declaredObjects)) {
            return false;
        }

        $aLoadedObjects = &$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms'][$objectType];
        if (!is_array($aLoadedObjects) || !array_key_exists($objectKey, $aLoadedObjects)) {
            $aTemp = [
                'EXTKEY' => $declaredObjects[$objectKey]['key'],
                'CLASS' => $declaredObjects[$objectKey]['key'],
                'TYPE' => $objectKey,
                'BASE' => $declaredObjects[$objectKey]['base'],
                'OBJECT' => $objectType,
            ];

            try {
                $aTemp['PATH'] = tx_mkforms_util_Div::getExtPath($aTemp['EXTKEY']);
                $aTemp['RELPATH'] = tx_mkforms_util_Div::getExtRelPath($aTemp['EXTKEY']);
                if (true === $aTemp['BASE'] && file_exists($aTemp['PATH'].'ext_localconf.php')) {
                    $aTemp['LOCALCONFPATH'] = $aTemp['PATH'].'ext_localconf.php';
                }
            }
            catch (\Exception $e) {
                if (!class_exists($aTemp['CLASS'])) {
                    throw $e;
                }
            }

            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mkforms'][$objectType][$aTemp['TYPE']] = $aTemp;

            if (isset($aTemp['LOCALCONFPATH']) && file_exists($aTemp['LOCALCONFPATH'])) {
                require_once $aTemp['LOCALCONFPATH'];
            }

            $aLoadedObjects[$objectKey] = $aTemp;
        }

        return $aLoadedObjects[$objectKey];
    }

    /**
     * Liefert ein Array mit den geladenen Objekten des Formulars. Dieses wird bei Ajax-Calls benötigt, um das Formular
     * wieder zu erstellen.
     *
     * @return array
     */
    public function getRunningObjects($formid = false)
    {
        $formid = $formid ? $formid : $this->formId;
        if ('EID' == tx_mkforms_util_Div::getEnvExecMode()) {
            // Bei Ajax-Call kommen die Daten aus der Session
            tx_mkforms_session_Factory::getSessionManager()->initialize();

            return $GLOBALS['_SESSION']['ameos_formidable']['hibernate'][$formid]['runningobjects'];
        }

        return $this->runningObjects;
    }

    public static function loadRunningObjects(array &$aRObjects)
    {
        reset($aRObjects);
        foreach ($aRObjects as $aObject) {
            self::loadObject($aObject['internalkey'], $aObject['objecttype']);
        }
    }

    /**
     * Load a t3 class and make an instance.
     *
     * @param   string      classname
     * @param   mixed       optional more parameters for constructor
     *
     * @return object instance of the class or false if it fails
     *
     * @see         tx_rnbase::makeInstance
     * @see         load()
     */
    public function makeInstance($sClass, $sPath = false)
    {
        $ret = false;
        if ($this->load($sClass, $sPath)) {
            $args = func_get_args();
            unset($args[1]); // path entfernen
            $ret = call_user_func_array(['tx_rnbase', 'makeInstance'], $args);
        }

        return $ret;
    }

    /**
     * Load the class file.
     *
     * @param   string      classname or path matching for the type of loader
     * @param string $sPath path to the file
     *
     * @return bool true if successfull, false otherwise
     *
     * @see     tx_rnbase::load
     */
    public function load($sClass, $sPath = false)
    {
        if (!array_key_exists($sClass, $this->loadedClasses)) {
            if ($sPath) {
                require_once $sPath;
            }
        }
        $this->loadedClasses[$sClass] = $sPath ? $sPath : true;

        return $this->loadedClasses[$sClass];
    }

    /**
     * Liefert ein Array mit den geladenen Objekten des Formulars. Dieses wird bei Ajax-Calls benötigt, um das Formular
     * wieder zu erstellen.
     *
     * @return array
     */
    public function getLoadedClasses($formid = false)
    {
        $formid = $formid ? $formid : $this->formId;
        if ('EID' == tx_mkforms_util_Div::getEnvExecMode()) {
            // Bei Ajax-Call kommen die Daten aus der Session
            tx_mkforms_session_Factory::getSessionManager()->initialize();

            return $GLOBALS['_SESSION']['ameos_formidable']['hibernate'][$formid]['loadedClasses'];
        }
        $loadedClasses = $this->loadedClasses;
        // Wenn eine FormId angegeben wurde müssen wir die Klassen
        // mit dem allgemeinen Loader mergen, da nicht alle mit der FormId geladen werden.
        if ($formid) {
            $loadedClasses = array_merge($loadedClasses, self::getInstance('')->getLoadedClasses());
        }

        return $loadedClasses;
    }

    public static function loadLoadedClasses(array &$aRObjects)
    {
        reset($aRObjects);
        foreach ($aRObjects as $sClass => $sPath) {
            if (is_string($sPath)) {
                require_once $sPath;
            }
        }
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/util/class.tx_mkforms_util_Loader.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/util/class.tx_mkforms_util_Loader.php'];
}
