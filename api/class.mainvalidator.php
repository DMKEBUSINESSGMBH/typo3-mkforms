<?php

class formidable_mainvalidator extends formidable_mainobject {

	function _matchConditions($aConditions = FALSE) {
		if ($aConditions === FALSE) {
			$aConditions = $this->aElement;
		}

		return $this->oForm->getConfig()->matchConditions($aConditions);
	}

	/**
	 *
	 * @param    formidable_mainrenderlet $oRdt
	 *
	 * @return    boolean
	 */
	function validate(&$oRdt) {

		$mValue = $oRdt->getValue();
		if (is_array($mValue) && !$oRdt->bArrayValue) {
			// Bei Widgets aus dem Lister und Uploads ist der Wert ein Array
			foreach ($mValue as $key => $value) {
				$oRdt->setIteratingId($key);
				$this->validateWidget($oRdt, $value);
			}
			$oRdt->setIteratingId();
		} else {
			$this->validateWidget($oRdt, $mValue);
		}
	}

	/**
	 *
	 * @param    formidable_mainrenderlet $oRdt
	 * @param    mixed                    $mValue
	 *
	 * @return    boolean
	 */
	function validateWidget(&$oRdt, $mValue) {

		$sAbsName = $oRdt->getAbsName();
		$aKeys = array_keys($this->_navConf('/'));
		reset($aKeys);
		while (!$oRdt->hasError() && list(, $sKey) = each($aKeys)) {

			if (!$this->canValidate($oRdt, $sKey, $mValue)) {
				continue;
			}

			/***********************************************************************
			 *
			 *    /required
			 *
			 ***********************************************************************/

			if ($sKey{0} === 'r' && Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sKey, 'required')) {
				if ($this->_isEmpty($oRdt, $mValue)) {
					if (($mMessage = $this->_navConf('/' . $sKey . '/message')) !== FALSE
						&& $this->oForm->isRunneable(
							$mMessage
						)
					) {
						$mMessage = $oRdt->callRunneable($mMessage);
					}

					$this->getForm()->_declareValidationError(
						$sAbsName,
						'STANDARD:required',
						$this->oForm->getConfigXML()->getLLLabel($mMessage)
					);

					break;
				}
			}

			/***********************************************************************
			 *
			 *    /authentified
			 *
			 ***********************************************************************/

			if ($sKey{0} === 'a' && Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sKey, 'authentified')) {
				if (!$this->_isAuthentified()) {
					$message = $this->oForm->getConfigXML()->getLLLabel($this->_navConf('/' . $sKey . '/message/'));
					$this->oForm->_declareValidationError(
						$sAbsName,
						'STANDARD:authentified',
						$message
					);

					break;
				}
			}

			/***********************************************************************
			 *
			 *    /maxsize
			 *
			 ***********************************************************************/

			if ((($sKey{0} === 'm') && Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sKey, 'maxsize')
				&& !Tx_Rnbase_Utility_Strings::isFirstPartOfStr(
					$sKey,
					'maxsizebychars'
				))
			) {

				$iMaxSize = intval($this->_navConf('/' . $sKey . '/value/'));

				if ($this->_isTooLong($mValue, $iMaxSize)) {
					$message = $this->oForm->getConfigXML()->getLLLabel($this->_navConf('/' . $sKey . '/message/'));
					$this->oForm->_declareValidationError(
						$sAbsName,
						'STANDARD:maxsize',
						$message
					);

					break;
				}
			}

			/***********************************************************************
			 *
			 *    /maxsizebychars
			 *
			 ***********************************************************************/

			if ($sKey{0} === 'm' && Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sKey, 'maxsizebychars')) {

				$iMaxSize = intval($this->_navConf('/' . $sKey . '/value/'));
				$sEncoding = $this->_navConf('/' . $sKey . '/encoding/');

				if ($this->_isTooLongByChars($mValue, $iMaxSize, $sEncoding)) {
					$message = $this->oForm->getConfigXML()->getLLLabel($this->_navConf('/' . $sKey . '/message/'));
					$this->oForm->_declareValidationError(
						$sAbsName,
						'STANDARD:maxsizebychars',
						$message
					);

					break;
				}
			}

			/***********************************************************************
			 *
			 *    /onerdthasalvaue
			 *
			 ***********************************************************************/

			if ($sKey{0} === 'o' && Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sKey, 'onerdthasavalue')) {

				$sRdt = $this->_navConf('/' . $sKey . '/rdt/');

				if ($this->_oneRdtHasAValue($mValue, $sRdt)) {
					$message = $this->oForm->getConfigXML()->getLLLabel($this->_navConf('/' . $sKey . '/message/'));
					$this->oForm->_declareValidationError(
						$sAbsName,
						'STANDARD:onerdthasalvaue',
						$message
					);

					break;
				}
			}

			/***********************************************************************
			 *
			 *    /minsize
			 *
			 ***********************************************************************/

			if ($sKey{0} === 'm' && Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sKey, 'minsize')) {

				$iMinSize = intval($this->_navConf('/' . $sKey . '/value/'));

				if ($this->_isTooSmall($mValue, $iMinSize)) {
					$message = $this->oForm->getConfigXML()->getLLLabel($this->_navConf('/' . $sKey . '/message/'));
					$this->oForm->_declareValidationError(
						$sAbsName,
						'STANDARD:minsize',
						$message
					);

					break;
				}
			}

			/***********************************************************************
			 *
			 *    /size
			 *
			 ***********************************************************************/

			if ($sKey{0} === 's' && Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sKey, 'size')) {

				$iSize = intval($this->_navConf('/' . $sKey . '/value/'));

				if (!$this->_sizeIs($mValue, $iSize)) {
					$message = $this->oForm->getConfigXML()->getLLLabel($this->_navConf('/' . $sKey . '/message/'));
					$this->oForm->_declareValidationError(
						$sAbsName,
						'STANDARD:size',
						$message
					);

					break;
				}
			}

			/***********************************************************************
			 *
			 *    /sameas
			 *
			 ***********************************************************************/

			if ($sKey{0} === 's' && Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sKey, 'sameas')) {

				$sameas = trim($this->_navConf('/' . $sKey . '/value/'));

				if (array_key_exists($sameas, $this->oForm->aORenderlets)) {
					$samevalue = $this->oForm->aORenderlets[$sameas]->getValue();

					if (!$this->_isSameAs($mValue, $samevalue)) {
						$message = $this->oForm->getConfigXML()->getLLLabel($this->_navConf('/' . $sKey . '/message/'));
						$this->oForm->_declareValidationError(
							$sAbsName,
							'STANDARD:sameas',
							$message
						);

						break;
					}
				}
			}

			/***********************************************************************
			 *
			 *    /email
			 *
			 ***********************************************************************/

			if ($sKey{0} === 'e' && Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sKey, 'email')) {
				if (!$this->_isEmail($mValue)) {
					$message = $this->oForm->getConfigXML()->getLLLabel($this->_navConf('/' . $sKey . '/message/'));
					$this->oForm->_declareValidationError(
						$sAbsName,
						'STANDARD:email',
						$message
					);

					break;
				}
			}

			/***********************************************************************
			 *
			 *    /userobj
			 *
			 * @deprecated; use custom instead
			 *
			 ***********************************************************************/

			if ($sKey{0} === 'u' && Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sKey, 'userobj')) {
				$this->oForm->mayday(
					"WIDGET [" . $oRdt->getName()
					. "] <b>/validator:STANDARD/userobj is deprecated.</b> Use /validator:STANDARD/custom instead."
				);
			}

			/***********************************************************************
			 *
			 *    /unique
			 *
			 ***********************************************************************/

			if ($sKey{0} === 'u' && Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sKey, 'unique')) {
				// field value has to be unique in the database
				// checking this

				if (!$this->_isUnique($oRdt, $mValue)) {
					$this->oForm->_declareValidationError(
						$sAbsName,
						'STANDARD:unique',
						$this->oForm->getConfigXML()->getLLLabel($this->_navConf('/' . $sKey . '/message/'))
					);

					break;
				}
			}

			/***********************************************************************
			 *
			 *    /custom
			 *
			 ***********************************************************************/

			if ($sKey{0} === 'c' && Tx_Rnbase_Utility_Strings::isFirstPartOfStr($sKey, 'custom')) {
				$mCustom = $this->_navConf('/' . $sKey);
				if ($this->oForm->isRunneable($mCustom)) {

					if (($mResult = $this->getForm()->getRunnable()->callRunnable(
							$mCustom,
							array('value' => $mValue, 'widget' => $oRdt)
						)) !== TRUE
					) {
						if (is_string($mResult)) {
							$message = $this->getForm()->getConfigXML()->getLLLabel($mResult);
						} else {
							$message = $this->getForm()->getConfigXML()->getLLLabel($this->_navConf('/' . $sKey . '/message/'));
						}

						$this->oForm->_declareValidationError(
							$sAbsName,
							'STANDARD:custom',
							$message
						);

						break;
					}
				}
			}
		}
	}

	/**
	 * Prüft, ob dependsOn gesetzt wurde.
	 * Dependson enthält den Namen des Renderlets.
	 * Es wird nur Validiert,
	 * wenn dieses Renderlet existiert und false ist.
	 *
	 * @param    formidable_mainrenderlet $oRdt
	 * @param    string                   $sKey
	 * @param    mixed                    $mValue
	 *
	 * @return    boolean
	 */
	protected function canValidate(&$oRdt, $sKey, $mValue) {
		if (($mSkip = $this->_defaultFalse('/' . $sKey . '/skipifempty')) !== FALSE) {
			if ($this->_isEmpty($oRdt, $mValue)) {
				return FALSE;
			}
		}
		if (($mSkipIf = $this->_navConf('/' . $sKey . '/skipif')) !== FALSE) {
			$mSkipIf = Tx_Rnbase_Utility_Strings::trimExplode(',', $mSkipIf);
			if (in_array($mValue, $mSkipIf)) {
				return FALSE;
			}
		}
		// Prüfen ob eine Validierung aufgrund des Dependson Flags statt finden soll
		if (!$this->checkDependsOn($oRdt, $sKey)) {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Prüft, ob dependsOn gesetzt wurde.
	 * Dependson enthält den Namen des Renderlets.
	 * Es wird nur Validiert,
	 * wenn dieses Renderlet existiert und false ist.
	 *
	 * werden mehrere renderlets definiert (, getrent).
	 * so müssen alle den dependsonif wert haben, damit nicht validiert wird.
	 *
	 * @param    formidable_mainrenderlet $oRdt
	 * @param    string                   $sKey
	 *
	 * @return    boolean                        wahr, wenn validiert werden kann.
	 */
	protected function checkDependsOn(&$oRdt, $sKey) {
		// skip validation, if hidden because dependancy empty
		if ($this->_defaultFalse('/' . $sKey . '/onlyifisvisiblebydependancies')
			&& !$oRdt->isVisibleBecauseDependancyEmpty()
		) {
			return FALSE;
		}
		if (($mDependsOn = $this->_navConf('/' . $sKey . '/dependson')) !== FALSE) {
			$mDependsOn = $this->getForm()->getRunnable()->callRunnable($mDependsOn);

			// Der Validator wird nur ausgeführt, wenn das Flag-Widget einen Wert hat.
			$widget = &$this->getForm()->getWidget($mDependsOn);

			if ($widget) {

				$negate = FALSE;
				//@TODO: dependsonifnot integrieren
				if (($aDependsOnIf = $this->_navConf('/' . $sKey . '/dependsonif')) !== FALSE) {
					$aDependsOnIf = $this->getForm()->getRunnable()->callRunnable($aDependsOnIf);
					$aDependsOnIf = is_array($aDependsOnIf) ? $aDependsOnIf : Tx_Rnbase_Utility_Strings::trimExplode(',', $aDependsOnIf, 1);
					$negate = TRUE;
				} else {
					// default false values
					$aDependsOnIf = array('', 0, '0', NULL, FALSE, array());
				}

				// IteratingId bei einem Renderlet im Lister setzen.
				if ($widget->getParent()->iteratingChilds) {
					$widget->setIteratingId($oRdt->getIteratingId());
				}

				$mValue = $widget->getValue();

				// die iterating Id wieder löschen!
				$widget->setIteratingId();

				//bei einem array von Werten (zb select mit multiple = 1)
				//prüfen wir ob einer der array Werte dem Wert in
				//dependsonif entspricht
				if (is_array($mValue)) {
					$inArray = FALSE;
					foreach ($mValue as $mTempValue) {
						if (in_array($mTempValue, $aDependsOnIf)) {
							$inArray = TRUE;//treffer?
							break;
						}
					}
				} else {
					$inArray = in_array(
						$mValue,
						$aDependsOnIf,
						$this->_defaultTrue('/' . $sKey . '/dependsonifstrict')
					);
				}

				if (($inArray != $negate)) {
					return FALSE;
				}
			}
		}

		return TRUE;
	}

	function _isEmpty(&$oRdt, $mValue) {
		return $oRdt->_emptyFormValue($mValue);
	}

	/**
	 * Prüft die Länge von Strings auf Byte-Ebene
	 * das bedeutet das Multi-Byte Zeichen nicht als 1 Zeichen gezählt werden
	 *
	 * @param $mValue
	 * @param $maxSize
	 */
	function _isTooLong($mValue, $maxSize) {

		if (is_array($mValue)) {
			return (count($mValue) > $maxSize);
		}

		return (strlen(trim($mValue)) > $maxSize);
	}

	/**
	 * Prüft die Länge von Strings auf Zeichen-Ebene
	 * das bedeutet das Multi-Byte Zeichen als 1 Zeichen gezählt werden
	 *
	 * @author Hannes Bochmann <dev@dmk-business.de>
	 *
	 * @param mixed  $mValue
	 * @param int    $iMaxSize
	 * @param string $sEncoding
	 */
	function _isTooLongByChars($mValue, $iMaxSize, $sEncoding = 'utf8') {
		//zur Sicherheit weiterer Fallback :)
		$sEncoding = (empty($sEncoding)) ? 'utf8' : $sEncoding;

		if (is_array($mValue)) {
			return (count($mValue) > $iMaxSize);
		}

		return (mb_strlen(trim($mValue), $sEncoding) > $iMaxSize);
	}

	function _isTooSmall($mValue, $minSize) {

		if (is_array($mValue)) {
			return (count($mValue) < $minSize);
		}

		return (strlen(trim($mValue)) < $minSize);
	}

	function _sizeIs($mValue, $iSize) {

		if (is_array($mValue)) {
			return (count($mValue) == intval($iSize));
		}

		return (strlen(trim($mValue)) == $iSize);
	}

	function _isSameAs($mValue1, $mValue2) {
		return ($mValue1 === $mValue2);
	}

	function _isEmail($mValue) {
		return trim($mValue) == '' || Tx_Rnbase_Utility_Strings::validEmail($mValue);
	}

	function _isAuthentified() {
		return (is_array(($aUser = $GLOBALS['TSFE']->fe_user->user)) && array_key_exists('uid', $aUser)
			&& intval($aUser['uid']) > 0);
	}

	/**
	 *
	 * @param formidable_mainrenderlet $oRdt
	 * @param mixed $mValue
	 * @return boolean
	 */
	function _isUnique(&$oRdt, $mValue) {

		$sDeleted = '';

		if (($sTable = $this->_navConf('/unique/tablename')) !== FALSE) {
			if (($sField = $this->_navConf('/unique/field')) === FALSE) {
				$sField = $oRdt->getName();
			}

			$sKey = FALSE;
		} else {
			if ($oRdt->hasDataBridge() && ($oRdt->oDataBridge->oDataSource->_getType() === 'DB')) {
				$sKey = $oRdt->oDataBridge->oDataSource->sKey;
				$sTable = $oRdt->oDataBridge->oDataSource->sTable;
				$sField = $oRdt->dbridged_mapPath();
			} else {
				$aDhConf = $this->oForm->_navConf('/control/datahandler/');
				$sKey = $aDhConf['keyname'];
				$sTable = $aDhConf['tablename'];
				$sField = $oRdt->getName();
			}

			if ($this->_defaultFalse('/unique/deleted/') === TRUE) {
				$sDeleted = ' AND deleted != 1';
			}
		}

		$mValue = addslashes($mValue);

		if ($oRdt->hasDataBridge()) {
			$oDset = $oRdt->dbridged_getCurrentDsetObject();
			if ($oDset->isAnchored()) {
				$sWhere = $sField . " = '" . $mValue . "' AND " . $sKey . " != '" . $oDset->getKey() . "'" . $sDeleted;
			} else {
				$sWhere = $sField . " = '" . $mValue . "'" . $sDeleted;
			}
		} else {
			if ($this->oForm->oDataHandler->_edition()) {
				$sWhere
					=
					$sField . " = '" . $mValue . "' AND " . $sKey . " != '" . $this->oForm->oDataHandler->_currentEntryId() . "'"
					. $sDeleted;
			} else {
				$sWhere = $sField . " = '" . $mValue . "'" . $sDeleted;
			}
		}

		$sSql = $GLOBALS['TYPO3_DB']->SELECTquery(
			'count(*) as nbentries',
			$sTable,
			$sWhere
		);

		$rs = $GLOBALS['TYPO3_DB']->sql_fetch_assoc(
			$this->oForm->_watchOutDB(
				$GLOBALS['TYPO3_DB']->sql_query($sSql),
				$sSql
			)
		);

		if ($rs['nbentries'] > 0) {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Validiert das mindestens eines der beiden Elemente einen Wert hat (renderlet, welches in "rdt"
	 * angegeben wurde oder selbst)
	 *
	 * @param array              $params
	 * @param tx_ameosformidable $form
	 *
	 * @return boolean
	 */
	public function _oneRdtHasAValue($mValue, $sRdt) {
		$widget = &$this->getForm()->getWidget($sRdt);

		//abhähniges feld existiert, ist geklickt oder widget selbst ist nicht leer
		if (($widget && $widget->getValue()) || !empty($mValue)) {
			return FALSE;
		} else {
			return TRUE;
		}
	}
}