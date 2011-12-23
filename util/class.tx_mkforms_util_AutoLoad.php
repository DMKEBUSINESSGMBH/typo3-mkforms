<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 das MedienKombinat GmbH <kontakt@das-medienkombinat.de>
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
 * Wenn Klassen noch nicht geladen wurden, gibt es PHP-Warnungen!
 * Also laden wir die jeweiligen Klassen nach.
 *
 * ACHTUNG: dies ist nur ein Fallback!
 * 	idealerweise sollten solche Klassen mit dem Loader geladen werden.
 * 	Mit dem Loader geladene oder instanzierte Objekte
 * 	werden automatich vor dem Wiederherstellen geladen:
 * 		$form->getObjectLoader()->load($sClass, $sPath = false);
 * 		$form->getObjectLoader()->makeInstance($sClass, $sPath = false);
 *
 * @author Michael Wagner <michael.wagner@das-medienkombinat.de>
 */
class tx_mkforms_util_AutoLoad {
//	/**
//	 * @var 	bool 	Wurden die xclasses bereits geladen?
//	 */
//	private static $bXclassLoaded = false;

	/**
	 * @var 	string 	der alte php.ini wert für unserialize_callback_func.
	 */
	private static $sUnserializeCallbackFuncOld = false;
	/**
	 * @var 	string 	wird als Message im log ausgegeben
	 */
	private static $sMessage = false;
	
	public static function setMessage($msg='') {
		self::$sMessage = $msg;
	}
	
	/**
	 * registriert eine unserialize_callback_func
	 */
	public static function registerUnserializeCallbackFunc(){
		if(!self::$sUnserializeCallbackFuncOld)
			self::$sUnserializeCallbackFuncOld = ini_get('unserialize_callback_func');
		ini_set('unserialize_callback_func', 'mkformsUnserializeCallbackFunc');
	}
	
	/**
	 * stellt die alte unserialize_callback_func wieder her
	 */
	public static function restoreUnserializeCallbackFunc(){
		ini_set('unserialize_callback_func', self::$sUnserializeCallbackFuncOld);
	}
	
//	/**
//	 * lädt xclasses
//	 */
//	public static function loadXClasses(){
//		if(self::$bXclassLoaded) { return; }
//		global $TYPO3_CONF_VARS;
//		foreach($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS'] as $sKey => $sPath) {
//			// xclasses nur von den basisextensions laden!?
//			if(
//				t3lib_div::isFirstPartOfStr($sKey, 't3lib')
//				|| t3lib_div::isFirstPartOfStr($sKey, 'tslib')
//			)
//			{	require_once($sPath); }
//
//		}
//		self::$bXclassLoaded = true;
//	}
	
	/**
	 * Wird von serialize aufgerufen, wenn eine Klasse nicht geladen ist
	 * @param string $sClassName
	 */
	public static function unserializeCallbackFunc($sClassName){
		// das funktioniert so nicht, da die Klassen,
		// welche überschrieben werden nicht zwangsläufig geladen sein müssen > fatal error
//		self::loadXClasses();
		
		$msg = false;
		try { // klasse laden
			
			// Hook um andere klassen zu laden, xclasses beispielsweise.
			tx_rnbase_util_Misc::callHook('mkforms','autoload_unserialize_callback_func',
				array('class' => &$sClassName), $this);
			
			if(!class_exists($sClassName))
				tx_rnbase::load($sClassName);
			
		} catch (Exception $e) {
			$msg = $e->getMessage();
		}
		
		// nachricht bauen
		$msg = (self::$sMessage ? self::$sMessage.LF : '') .
				($msg ? $msg : (
					$sClassName . ( class_exists($sClassName)
						? ' musste mit der unserializeCallbackFunc geladen werden.'
						// fallback, msg wird sicher durch die exception bereits gesetzt sein
						: ' konnte mit der unserializeCallbackFunc nicht geladen werden!'
					)
				)
			);
		
//		print_r(array(
//				$sClassName,
//				class_exists($sClassName),
//				$msg,
//				'DEBUG: '.__METHOD__.' Line: '.__LINE__
//			)); // @TODO: remove me
		
		//noch loggen
		tx_rnbase::load('tx_rnbase_util_Logger');
		// warning ins log schreiben, wenn die klasse geladen wurde
		if(class_exists($sClassName) && tx_rnbase_util_Logger::isWarningEnabled()) {
			tx_rnbase_util_Logger::warn($msg, 'mkforms');
		}
		// fatal log schreiben, wenn die klasse nicht geladen werden konnte
		elseif(!class_exists($sClassName) && tx_rnbase_util_Logger::isFatalEnabled()) {
			tx_rnbase_util_Logger::fatal($msg, 'mkforms');
		}
	}
}

/**
 * Wird von serialize aufgerufen, wenn eine Klasse nicht geladen ist
 * @param string $sClassName
 * @see tx_mkforms_util_AutoLoad::unserializeCallbackFunc()
 */
function mkformsUnserializeCallbackFunc($sClassName){
	tx_mkforms_util_AutoLoad::unserializeCallbackFunc($sClassName);
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/util/class.tx_mkforms_util_AutoLoad.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/util/class.tx_mkforms_util_AutoLoad.php']);
}
?>