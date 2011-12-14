<?php

require_once(t3lib_extMgm::extPath('mkforms') . "api/class.mainscriptingmethods.php");

class formidable_templatemethods extends formidable_mainscriptingmethods {
	
	private static $cache = null;
	
	/**
	 * @return tx_rnbase_cache_TYPO3Cache
	 */
	private function getCache(){
		if (is_null(self::$cache))
			self::$cache = tx_rnbase::makeInstance('tx_rnbase_cache_TYPO3Cache', 'mkforms_rdt_tmpl');
		return self::$cache;
	}
	
	function method_rdt($mData, $aParams) {
		if(!is_string($aParams[0]) || $aParams[0] === AMEOSFORMIDABLE_LEXER_FAILED) {
			return AMEOSFORMIDABLE_LEXER_BREAKED;
		}

		if(
			($oRdt = $this->oForm->getRdtForTemplateMethod($mData)) !== FALSE
			&& array_key_exists($aParams[0], $oRdt->aChilds)
		  ) {
			return $oRdt->aChilds[$aParams[0]];
		} elseif(($oRdt = $this->oForm->getWidget($aParams[0])) !== FALSE) {
			return $this->getHtmlBag($oRdt);
		}
		return AMEOSFORMIDABLE_LEXER_BREAKED;
	}

	function method_rdtValue($mData, $aParams) {
		if(!is_string($aParams[0]) || $aParams[0] === AMEOSFORMIDABLE_LEXER_FAILED) {
			return AMEOSFORMIDABLE_LEXER_BREAKED;
		}
		if(($oRdt = $this->oForm->getWidget($aParams[0])) !== FALSE) {
			return $oRdt->getValue();
		}
		return AMEOSFORMIDABLE_LEXER_BREAKED;
	}

	/**
	 * gets the html bag of a Widget, include caching for template methods
	 * 
	 * @param formidable_mainrenderlet $oRdt
	 * @return array
	 */
	private function getHtmlBag($oRdt){
		$cache = $this->getCache();
		$cacheId = $oRdt->getAbsName() . 'htmlbag';
		
		if($cache->has($cacheId)) return $cache->get($cacheId);
		$htmlBag = $this->oForm->oRenderer->processHtmlBag( $oRdt->render(), $oRdt );
		$cache->set($cacheId,$htmlBag);
		
		return $htmlBag;
	}
	
	function method_switch($mData, $aParams) {
		if($mData === TRUE) {
			return $aParams[0];
		} else {
			return $aParams[1];
		}
	}

	function method_wrapInnerHTML($mData, $aParams) {
		$sTagName = $this->oForm->oHtml->getFirstTagName($mData, TRUE);
		$sTag = $this->oForm->oHtml->getFirstTag($mData);
		$sInnerHtml = $this->oForm->oHtml->removeFirstAndLastTag($mData);
		$sInnerHtml = str_replace(
			'|',
			$sInnerHtml,
			$aParams[0]
		);

		return $sTag . $sInnerHtml . '</' . $sTagName . '>';
	}

	function method_echo($mData, $aParams) {
		return $aParams[0];
	}

	function method_equals($mData, $aParams) {
		if($mData == $aParams[0]) return TRUE;
		return FALSE;
	}

	function method_greaterThan($mData, $aParams) {
		if($mData > $aParams[0]) return TRUE;
		return FALSE;
	}
	function method_lessThan($mData, $aParams) {
		if($mData < $aParams[0]) return TRUE;
		return FALSE;
	}

	function method_nl2br($mData, $aParams) {
		return nl2br(tx_ameosformidable::templateDataAsString($mData));
	}

	function method_urlencode($mData, $aParams) {
		return urlencode(tx_ameosformidable::templateDataAsString($mData));
	}

	function method_rawurlencode($mData, $aParams) {
		return rawurlencode(tx_ameosformidable::templateDataAsString($mData));
	}

	function method_trim($mData, $aParams) {
		return trim(tx_ameosformidable::templateDataAsString($mData));
	}

	function method_upper($mData, $aParams) {
		return strtoupper(tx_ameosformidable::templateDataAsString($mData));
	}

	function method_lower($mData, $aParams) {
		return strtolower(tx_ameosformidable::templateDataAsString($mData));
	}

	function method_ucfirst($mData, $aParams) {
		return ucfirst(tx_ameosformidable::templateDataAsString($mData));
	}

	function method_ucwords($mData, $aParams) {
		return ucwords(tx_ameosformidable::templateDataAsString($mData));
	}

	function method_debug($mData, $aParams) {
		if(is_array($mData)) {
			if(array_key_exists('help', $mData)) {
				unset($mData['help']);
			}

			reset($mData);
			while(list($sKey,) = each($mData)) {
				if(is_array($mData[$sKey]) && array_key_exists('__compiled', $mData[$sKey])) {
					ksort($mData[$sKey]);
				}
			}
		}
		
		return tx_mkforms_util_Div::viewMixed($mData);
	}

	function method_displayCond($mData, $aParams) {
		if(tx_ameosformidable::templateDataAsString($mData) === '') {
			return 'display: none;';
		}

		return '';
	}

	function method_concat($mData, $aParams) {
		return trim(tx_ameosformidable::templateDataAsString($mData) . implode(' ', $aParams));
	}

	function method_wrap($mData, $aParams) {
		return str_replace(
			'|',
			trim(tx_ameosformidable::templateDataAsString($mData)),
			$aParams[0]
		);
	}

	function method_toHex($mData, $aParams) {
		return implode(':', explode(
			' ',
			trim(
				chunk_split(
					strtoupper(
						bin2hex($mData)
					),
					2,
					' '
				)
			)
		));
	}

	function method_extPath($mData, $aParams) {
		return t3lib_extMgm::extPath($aParams[0]);
	}

	function method_toWebPath($mData, $aParams) {
		return $this->oForm->toWebPath(trim(tx_ameosformidable::templateDataAsString($mData)));
	}
	
	function method_getLLL($mData, $aParams) {
		return $this->method_getLLLabel($mData, $aParams);
	}
	
	function method_getLLLabel($mData, $aParams) {
		return $this->oForm->getConfigXML()->getLLLabel(trim(tx_ameosformidable::templateDataAsString($aParams[0])));
	}

	function method_extract($mData, $aParams) {
		$aRes = array();
		if(is_array($mData)) {
			reset($aParams);
			while(list(, $sKeyName) = each($aParams)) {
				if(array_key_exists($sKeyName, $mData)) {
					$aRes[$sKeyName] = $mData[$sKeyName];
				}
			}
		}

		return $aRes;
	}

	function method_implode($mData, $aParams) {
		//man kann nicht direkt ein komma angegeben da dieses in 
		//mkforms_util_Templates rausgeparsed wird. wenn also nichts
		//gesetzt wird, dann nehmen wir das komma als connector
		if(!isset($aParams[0])) $aParams[0] = ', ';
		if(is_array($mData)) {
			return implode($aParams[0], $mData);
		}

		return '';
	}

	function method_isTrue($mData, $aParams) {
		if($mData === TRUE) {
			return TRUE;
		}

		return FALSE;
	}

	function method_isFalse($mData, $aParams) {
		if($mData === FALSE) {
			return TRUE;
		}

		return FALSE;
	}

	function method_isNotTrue($mData, $aParams) {
		if($mData !== TRUE) {
			return TRUE;
		}

		return FALSE;
	}

	function method_isNotFalse($mData, $aParams) {
		if($mData !== FALSE) {
			return TRUE;
		}

		return FALSE;
	}

	function method_ifIsTrue($mData, $aParams) {
		if($mData === TRUE) {
			return TRUE;
		}

		return AMEOSFORMIDABLE_LEXER_BREAKED;
	}

	function method_ifIsFalse($mData, $aParams) {
		if($mData === FALSE) {
			return TRUE;
		}

		return AMEOSFORMIDABLE_LEXER_BREAKED;
	}

	function method_isLoggedIn($mData, $aParams) {
		if($GLOBALS['TSFE']->fe_user->user['uid']){
			return TRUE;
		}
		
		return FALSE;
	}

	function method_strlen($mData, $aParams) {
		return strlen($mData);
	}
	function method_substr($mData, $aParams) {
		return t3lib_div::fixed_lgd_cs(
			tx_ameosformidable::templateDataAsString($mData),
			$aParams[0],
			$aParams[1]
		);
	}

	function method_fixed_lgd($mData, $aParams) {
		return $this->method_substr($mData, $aParams);
	}
	
	function method_fixed_lgd_word($mData, $aParams) {
		$sStr = tx_ameosformidable::templateDataAsString($mData);

		if(strlen($sStr) <= $aParams[0]) {
			return $sStr;
		}

		if(strlen($aParams[1]) > $aParams[0]) {
			return $aParams[1];
		}

		$sStr = substr($sStr,0,$aParams[0]-strlen($aParams[1])+1);
		return substr($sStr,0,strrpos($sStr,' ')).$aParams[1];
	}
	
	function method_striptags($mData, $aParams) {
		return $this->method_strip_tags($mData, $aParams);
	}
	
	function method_strip_tags($mData, $aParams) {
		return strip_tags(tx_ameosformidable::templateDataAsString($mData));
	}
	
	function method_formData($mData, $aParams) {
		if(!empty($aParams)) {
			return $this->oForm->oDataHandler->getThisFormData($aParams[0]);
		} else {
			return $this->oForm->oDataHandler->getFormData();
		}
	}
	
	function method_storedData($mData, $aParams) {
		if(!empty($aParams)) {
			return $this->oForm->oDataHandler->getStoredData($aParams[0]);
		} else {
			return $this->oForm->oDataHandler->getStoredData();
		}
	}
	
	function method_listData($mData, $aParams) {
		return $this->method_rowData($mData, $aParams);
	}
	
	function method_rowData($mData, $aParams) {
		if(!empty($aParams)) {
			return $this->oForm->oDataHandler->getListData($aParams[0]);
		}
		
		return $this->oForm->oDataHandler->getListData();
	}
	
	function method_rteToHtml($mData, $aParams) {
		return $this->oForm->div_rteToHtml(
			tx_ameosformidable::templateDataAsString($mData),
			'',
			''
		);
	}
	
	function method_debug_trail($mData, $aParams) {
		return t3lib_div::debug_trail();
	}
	
	function method_hsc($mData, $aParams) {
		return htmlspecialchars(tx_ameosformidable::templateDataAsString($mData));
	}
	
	function method_htmlentities($mData, $aParams) {
		return htmlentities(
			tx_ameosformidable::templateDataAsString($mData),
			array_key_exists(0, $aParams) ? $aParams[0] : ENT_COMPAT,	// quote style
			array_key_exists(1, $aParams) ? $aParams[1] : 'ISO-8859-1'	// returned charset
		);
	}
	
	function method_replace($mData, $aParams) {
		return str_replace($aParams[0], $aParams[1], tx_ameosformidable::templateDataAsString($mData));
	}
	
	function method_strftime($mData, $aParams) {
		if(($sFormat = trim($aParams[0])) === '') {
			$sFormat = '%Y/%m/%d';
		}
		
		return strftime(
			$sFormat,
			intval(trim(tx_ameosformidable::templateDataAsString($mData)))
		);
	}
	
	function method_isSubmitted($mData, $aParams) {
		return $this->oForm->oDataHandler->_isSubmitted();
	}
	
	function method_isTestSubmitted($mData, $aParams) {
		return $this->oForm->oDataHandler->_isTestSubmitted();
	}
	
	function method_isDraftSubmitted($mData, $aParams) {
		return $this->oForm->oDataHandler->_isDraftSubmitted();
	}
	
	function method_isRefreshSubmitted($mData, $aParams) {
		return $this->oForm->oDataHandler->_isRefreshSubmitted();
	}
	
	function method_isClearSubmitted($mData, $aParams) {
		return $this->oForm->oDataHandler->_isClearSubmitted();
	}
	
	function method_isSearchSubmitted($mData, $aParams) {
		return $this->oForm->oDataHandler->_isSearchSubmitted();
	}
	
	function method_isFullySubmitted($mData, $aParams) {
		return $this->method_isFullSubmitted($mData, $aParams);
	}
	
	function method_isFullSubmitted($mData, $aParams) {
		return $this->oForm->oDataHandler->_isFullySubmitted();
	}
	
	function method_allIsValid($mData, $aParams) {
		return $this->oForm->oDataHandler->_allIsValid();
	}
	
	function method_and($mData, $aParams) {
		$mData = tx_ameosformidable::templateDataAsString($mData);
		return tx_ameosformidable::templateDataAsString($mData) && $aParams[0];
	}
	
	function method_persistHidden($mData, $aParams) {
		if(($oRdt =& $this->oForm->getRdtForTemplateMethod($mData)) !== FALSE) {
			return $oRdt->persistHidden();
		}
		
		return AMEOSFORMIDABLE_LEXER_BREAKED;
	}
	
	function method_hasError($mData, $aParams) {
		return $this->method_hasErrors($mData, $aParams);
	}
	
	function method_hasErrors($mData, $aParams) {
		if(($oRdt =& $this->oForm->getRdtForTemplateMethod($mData)) !== FALSE) {
			return $oRdt->hasDeepError();
		} else {
			return !$this->oForm->oDataHandler->_allIsValid();
		}

		return AMEOSFORMIDABLE_LEXER_BREAKED;
	}
	
	function method_includeCss($mData, $aParams) {
		$sFile = $this->oForm->toWebPath($aParams[0]);
		$this->oForm->additionalHeaderData(
			'<link rel="stylesheet" type="text/css" href="'. $sFile . '" />'
		);
	}
	
	function method_codeBehind($mData, $aParams) {
		if($oRunnable = $this->oForm->getRunnable()->getCodeBehind($aParams[0])) {
			return $oRunnable['object'];
		}

		return AMEOSFORMIDABLE_LEXER_BREAKED;
	}
	
	function method_cb($mData, $aParams) {
		return $this->method_codeBehind($mData, $aParams);
	}
	
	function method_imageMaxWidth($mData, $aParams) {
		//$mData = tx_ameosformidable::templateDataAsString($mData);
		//$sPath = tx_mkforms_util_Div::toServerPath($mData);
		$sPath = $mData['filepath.']['original.']['rel'];
		
		if(file_exists($sPath)) {
			require_once(PATH_t3lib . 'class.t3lib_stdgraphic.php');
			require_once(PATH_tslib . 'class.tslib_gifbuilder.php');
			// expecting typoscript

			$aImage = $GLOBALS['TSFE']->tmpl->setup['config.']['tx_mkforms.']['res.']['shared.']['xml.']['imageprocess.']['maxwh.'];
			$aImage['file.']['10.']['file'] = $sPath; //tx_mkforms_util_Div::removeStartingSlash($this->oForm->toRelPath($sPath));
			$aImage['file.']['10.']['file.']['maxW'] = $aParams[0];
			unset($aImage['file.']['10.']['file.']['maxH']);

			$sNewPath = $GLOBALS['TSFE']->cObj->IMG_RESOURCE($aImage);	// IMG_RESOURCE always returns relative path
			return $sNewPath;
		}

		return AMEOSFORMIDABLE_LEXER_BREAKED;
	}
	
	function method_parent($mData, $aParams) {
		return $this->oForm->_oParent;
	}
	
	function method_explode($mData, $aParams) {
		if(!isset($aParams[0])) {
			$sSep = ',';
		} else {
			$sSep = $aParams[0];
		}
		
		$aRes = t3lib_div::trimExplode(
			$sSep,
			tx_ameosformidable::templateDataAsString($mData)
		);
		
		reset($aRes);
		return $aRes;
	}
	
	/**
	 * cheacks if a property exists in htmlbag
	 * 
	 * @param mixed $mData
	 * @param array $aParams
	 * @return boolean
	 */
	function method_propertyExists($mData, $aParams){
		return array_key_exists($aParams[0], $mData);
	}
	function method_propertyNotExists($mData, $aParams){
		return !$this->method_propertyExists($mData, $aParams);
	}
	
	function method_isEmpty($mData, $aParams) {
		if(is_string($mData)) {
			$mData = trim($mData);
		}
		
		return empty($mData);
	}
	
	function method_isNotEmpty($mData, $aParams) {
		return !$this->method_isEmpty($mData, $aParams);
	}
	
	function method_isDate($mData, $aParams) {
		return ($mData!=='0000-00-00' && checkdate(@date('m', $mData), @date('d', $mData), @date('Y', $mData)));
	}
	
	function method_isNoDate($mData, $aParams) {
		return !$this->method_isDate($mData, $aParams);
	}
	
	function method_tstamp2Date($mData, $aParams) {
		if ($this->method_isDate($mData, $aParams))
			return date($aParams[0], $mData);
		return $mData;
	}
	
	function method_linkUrl($mData, $aParams) {
		return $this->oForm->getCObj()->typolink_URL(array(
			'parameter' => $aParams[0]
		));
	}
	
	function method_extConf($mData, $aParams) {
		return $this->oForm->getExtConfVal($aParams[0]);
	}
}

?>