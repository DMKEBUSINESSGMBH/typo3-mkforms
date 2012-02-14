<?php
require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_mkforms_util_Div');

	class formidable_mainrenderer extends formidable_mainobject {

		var $aCustomHidden	= null;
		var $bFormWrap		= TRUE;
		var $bValidation	= TRUE;
		var $bDisplayLabels	= TRUE;

		function _init(&$oForm, $aElement, $aObjectType, $sXPath) {

			parent::_init($oForm, $aElement, $aObjectType, $sXPath);

			$this->_setDisplayLabels(!$this->getForm()->_isFalse('/meta/displaylabels'));
			$this->_setFormWrap(!$this->getForm()->_isFalse('/meta/formwrap'));
		}

		function _render($aRendered) {
			return $this->_wrapIntoForm(implode("<br />\n", $aRendered));
		}

		function _wrapIntoDebugContainer($aHtmlBag, &$oRdt) {

			$sName = $oRdt->getAbsName();

			$sHtml = <<<TEMPLATE
				<div class="ameosformidable_debugcontainer_void">
					<div style="pointer: help;" class="ameosformidable_debughandler_void">{$oRdt->aElement["type"]}:{$sName}</div>
					{$aHtmlBag["__compiled"]}
				</div>
TEMPLATE;
			$aHtmlBag['__compiled'] = $sHtml;

			if(array_key_exists('input', $aHtmlBag)) {

				$sInfos = rawurlencode($aHtmlBag['input']);
				$sHtml = <<<TEMPLATE
				<div class="ameosformidable_debugcontainer_void">
					<div style="pointer: help;" class="ameosformidable_debughandler_void">{$sName}.input</div>
					{$aHtmlBag["input"]}
				</div>
TEMPLATE;
				$aHtmlBag['input'] = $sHtml;

			}
			return $aHtmlBag;
		}

		function _wrapIntoForm($html) {

			$oForm = &$this->getForm();
			$iFormId = $oForm->getFormId();

			if(!empty($this->getForm()->getDataHandler()->newEntryId)) {
				$iEntryId = $this->getForm()->getDataHandler()->newEntryId;
			} else {
				$iEntryId = $this->getForm()->getDataHandler()->_currentEntryId();
			}

			$hidden_entryid		= $this->_getHiddenEntryId($iEntryId);
			$hidden_custom		= $this->_getHiddenCustom();
			
			$sSysHidden			=	'<input type="hidden" name="' . $iFormId . '[AMEOSFORMIDABLE_SERVEREVENT]" id="' . $iFormId . '_AMEOSFORMIDABLE_SERVEREVENT" />' .
									'<input type="hidden" name="' . $iFormId . '[AMEOSFORMIDABLE_SERVEREVENT_PARAMS]" id="' . $iFormId . '_AMEOSFORMIDABLE_SERVEREVENT_PARAMS" />' .
									'<input type="hidden" name="' . $iFormId . '[AMEOSFORMIDABLE_SERVEREVENT_HASH]" id="' . $iFormId . '_AMEOSFORMIDABLE_SERVEREVENT_HASH" />' .
									'<input type="hidden" name="' . $iFormId . '[AMEOSFORMIDABLE_ADDPOSTVARS]" id="' . $iFormId . '_AMEOSFORMIDABLE_ADDPOSTVARS" />' .
									'<input type="hidden" name="' . $iFormId . '[AMEOSFORMIDABLE_VIEWSTATE]" id="' . $iFormId . '_AMEOSFORMIDABLE_VIEWSTATE" />' .
									'<input type="hidden" name="' . $iFormId . '[AMEOSFORMIDABLE_SUBMITTED]" id="' . $iFormId . '_AMEOSFORMIDABLE_SUBMITTED"  value="'.AMEOSFORMIDABLE_EVENT_SUBMIT_FULL.'"/>' .
									'<input type="hidden" name="' . $iFormId . '[AMEOSFORMIDABLE_SUBMITTER]" id="' . $iFormId . '_AMEOSFORMIDABLE_SUBMITTER" />';

			//CSRF Schutz integrieren			
			if($this->getForm()->getConfTS('csrfProtection')){
				$sRequestToken = $oForm->getCsrfProtectionToken();
				$sSysHidden .= '<input type="hidden" name="' . $iFormId . '[MKFORMS_REQUEST_TOKEN]" id="' . $iFormId . '_MKFORMS_REQUEST_TOKEN" value="'.$sRequestToken.'" />';
				//der request token muss noch in die session damit wir ihn prüfen können.
				//mit bestehenden mergen
				$aSessionData = $GLOBALS['TSFE']->fe_user->getKey('ses', 'mkforms');
				$aSessionData['requestToken'][$iFormId] = $sRequestToken;
				$GLOBALS['TSFE']->fe_user->setKey('ses', 'mkforms', $aSessionData);
				$GLOBALS['TSFE']->fe_user->storeSessionData();
			}

			if(($sStepperId = $oForm->_getStepperId()) !== FALSE) {
				$sSysHidden .=	'<input type="hidden" name="AMEOSFORMIDABLE_STEP" id="AMEOSFORMIDABLE_STEP" value="' . $oForm->_getStep() . '" />' .
								'<input type="hidden" name="AMEOSFORMIDABLE_STEP_HASH" id="AMEOSFORMIDABLE_STEP_HASH" value="' . $oForm->_getSafeLock($oForm->_getStep()) . '" />';
			}

			$aHtmlBag =
				array(
					'SCRIPT'		=> '',
					'FORMBEGIN'		=> '',
					'CONTENT'		=> $html,
					/*'HIDDEN'		=> $hidden_entryid . $hidden_custom . $sSysHidden,*/
					// in P for XHTML validation
					'HIDDEN'		=> '<p style="position:absolute; top:-5000px; left:-5000px;">' . $hidden_entryid . $hidden_custom . $sSysHidden . '</p>',
					'FORMEND'		=> '',
				);

			if($this->bFormWrap) {

				$formid			= '';
				$formaction		= '';
				$formonsubmit	= '';
				$formmethod		= '';
				$formcustom		= '';

				/*$formid = " id=\"" . $iFormId . "\" name=\"" . $iFormId . "\" ";*/
				$formid = ' id="' . $iFormId . '" ';

				$formaction = ' action="' . $oForm->xhtmlUrl($oForm->getFormAction()) . '" ';

				if(($sOnSubmit = $oForm->_navConf('/meta/form/onsubmit')) !== FALSE) {
					$formonsubmit = ' onSubmit="' . $sOnSubmit . '" ';
				}

				if(($sCustom = $oForm->_navConf('/meta/form/custom')) !== FALSE) {
					$formcustom = ' ' . $sCustom . ' ';
				}
				if(($sClass = $oForm->_navConf('/meta/form/class')) !== FALSE) {
					$formcustom .= ' class="'.$sClass. '" ';
				}

				$aHtmlBag['FORMBEGIN']	=	'<form enctype="multipart/form-data" ' . $formid . $formaction . $formonsubmit . $formcustom . ' method="post">';
				$aHtmlBag['FORMEND']	=	'</form>';
			}
			reset($aHtmlBag);
			return $aHtmlBag;
		}

		function _getFullSubmitEvent() {
			return "Formidable.f('" . $this->getForm()->getFormId() . "').submitFull();";
		}

		function _getRefreshSubmitEvent() {
			return "Formidable.f('" . $this->getForm()->getFormId() . "').submitRefresh();";
		}

		function _getDraftSubmitEvent() {
			return "Formidable.f('" . $this->getForm()->getFormId() . "').submitDraft();";
		}

		function _getTestSubmitEvent() {
			return "Formidable.f('" . $this->getForm()->getFormId() . "').submitTest();";
		}

		function _getClearSubmitEvent() {
			return "Formidable.f('" . $this->getForm()->getFormId() . "').submitClear();";
		}

		function _getSearchSubmitEvent() {
			return "Formidable.f('" . $this->getForm()->getFormId() . "').submitSearch();";
		}

		function _getServerEvent($sRdtAbsName, $aEvent, $sEventId, $aData = array()) {

			// $aData is typicaly the current row if in lister

			$sJsParam = 'false';
			$sHash = 'false';
			$aGrabbedParams = array();
			$aFullEvent = $this->getForm()->aServerEvents[$sEventId];

			if($aFullEvent['earlybird'] === TRUE) {
				# registering absolute name,
					# this will help when early-processing the event
				#debug($aFullEvent, 'laaa');

				$aGrabbedParams['_sys_earlybird'] = array(
					'absname' => $aFullEvent['name'],
					'xpath' => tx_mkforms_util_Div::removeEndingSlash($this->getForm()->aORenderlets[$aFullEvent['name']]->sXPath) . '/' . $aFullEvent['trigger']
				);
			}

			#if(!empty($aData)) {
				reset($aFullEvent['params']);
				while(list($sKey,) = each($aFullEvent['params'])) {
					$sParam = $aFullEvent['params'][$sKey]['get'];

					if(array_key_exists($sParam, $aData)) {
						$aGrabbedParams[$sParam] = $aData[$sParam];
					} else {
						$aGrabbedParams[] = $sParam;
					}
				}
			#}

			if(!empty($aGrabbedParams)) {
				$sJsParam = base64_encode(serialize($aGrabbedParams));
				$sHash = '\'' . $this->getForm()->_getSafeLock($sJsParam) . '\'';
				$sJsParam = '\'' . $sJsParam . '\'';
			}

			#debug($sJsParam, 'sJsParam');

			$sConfirm = 'false';
			if(array_key_exists('confirm', $aEvent) && trim($aEvent['confirm'] !== '')) {

				/*$sConfirm = "'" . rawurlencode(
					$this->getForm()->getConfigXML()->getLLLabel(
						$aEvent["confirm"]
					)
				) . "'";*/

				// charset problem patched by Nikitim S.M
					// http://support.typo3.org/projects/formidable/m/typo3-project-formidable-russian-locals-doesnt-work-int-formidable-20238-i-wrote-the-solvation/p/15/

				$sConfirm = '\'' .
					addslashes($this->getForm()->getConfigXML()->getLLLabel(
						$aEvent['confirm']
					))
				. '\'';
			}

			if(($sSubmitMode = $this->getForm()->_navConf('submit', $aEvent)) !== FALSE) {
				switch($sSubmitMode) {
					case 'full' : {
						return "Formidable.f('" . $this->getForm()->getFormId() . "').executeServerEvent('" . $sEventId . "', Formidable.SUBMIT_FULL, " . $sJsParam . ", " . $sHash . ", " . $sConfirm . ");";
						break;
					}
					case 'refresh' : {
						return "Formidable.f('" . $this->getForm()->getFormId() . "').executeServerEvent('" . $sEventId . "', Formidable.SUBMIT_REFRESH, " . $sJsParam . ", " . $sHash . ", " . $sConfirm . ");";
						break;
					}
					case 'draft' : {
						return "Formidable.f('" . $this->getForm()->getFormId() . "').executeServerEvent('" . $sEventId . "', Formidable.SUBMIT_DRAFT, " . $sJsParam . ", " . $sHash . ", " . $sConfirm . ");";
						break;
					}
					case 'test' : {
						return "Formidable.f('" . $this->getForm()->getFormId() . "').executeServerEvent('" . $sEventId . "', Formidable.SUBMIT_TEST, " . $sJsParam . ", " . $sHash . ", " . $sConfirm . ");";
						break;
					}
					case 'search' : {
						return "Formidable.f('" . $this->getForm()->getFormId() . "').executeServerEvent('" . $sEventId . "', Formidable.SUBMIT_SEARCH, " . $sJsParam . ", " . $sHash . ", " . $sConfirm . ");";
						break;
					}
				}
			} else {
				// default: REFRESH
				return "Formidable.f('" . $this->getForm()->getFormId() . "').executeServerEvent('" . $sEventId . "', Formidable.SUBMIT_REFRESH , " . $sJsParam . ", " . $sHash . ", " . $sConfirm . ");";
			}
		}

		function synthetizeAjaxEvent(&$oRdt, $sEventHandler, $sCb=FALSE, $sPhp=FALSE, $mParams=FALSE, $bCache=TRUE, $bSyncValue=FALSE) {
			$aEvent = array(
				'runat' => 'ajax',
				'cache' => intval($bCache),	// intval because FALSE would be bypassed by navconf
				'syncvalue' => intval($bSyncValue),	// same reason
				'params' => $mParams,
			);

			if($sCb !== FALSE) {
				$aEvent['exec'] = $sCb;
			} elseif($sPhp !== FALSE) {
				$aEvent['userobj']['php'] = $sPhp;
			}

			$sRdtAbsName = $oRdt->getAbsName();
			$sEventId = $this->getForm()->_getAjaxEventId(
				$sRdtAbsName,
				array($sEventHandler => $aEvent)
			);

			$this->getForm()->aAjaxEvents[$sEventId] = array(
				'name' => $sRdtAbsName,
				'eventid' => $sEventId,
				'trigger' => $sEventHandler,
				'cache' => intval($bCache),	// because FALSE would be bypassed by navconf
				'event' => $aEvent,
			);

			return $this->_getAjaxEvent(
				$oRdt,
				$aEvent,
				$sEventHandler
			);
		}
		/**
		 * Baut den JS-Aufruf für einen Ajax-Event zusammen.
		 * @param formidable_mainrenderlet $oRdt
		 * @param array $aEvent
		 * @param String $sEvent
		 * @return String
		 */
		function _getAjaxEvent(&$oRdt, $aEvent, $sEvent) {

			$sRdtName = $oRdt->getAbsName();

			$sEventId = $this->getForm()->_getAjaxEventId(
				$sRdtName,
				array($sEvent => $aEvent)
			);

			$sRdtId = $oRdt->_getElementHtmlId();
			$sHash = $oRdt->_getSessionDataHashKey();
			$bSyncValue = $this->getForm()->_defaultFalse('/syncvalue', $aEvent);
			$bCache = $this->getForm()->_defaultTrue('/cache', $aEvent);
			$bPersist = $this->getForm()->_defaultFalse('/persist', $aEvent);
			$sTrigerTinyMCE = $this->getForm()->_navConf('/trigertinymce', $aEvent);

			$sConfirm = 'false';
			if(array_key_exists('confirm', $aEvent) && trim($aEvent['confirm'] !== '')) {

				$sConfirm = '\'' .
					addslashes($this->getForm()->getConfigXML()->getLLLabel(
						$aEvent['confirm']
					))
				. '\'';
			}

			$aParams = array();
			$aParamsCollection = array();
			$aRowParams = array();

			if(($mParams = $this->getForm()->_navConf('/params', $aEvent)) !== FALSE) {
				if(is_string($mParams)) {
					// Das ist der Normalfall. Die Parameter als String
					$aTemp = t3lib_div::trimExplode(',', $mParams);
					reset($aTemp);
					foreach($aTemp As $sParam) {
						$aParamsCollection[] = array(
							'get' => $sParam,
							'as' => FALSE,
						);
					}
				} else {
					// Anscheinend kann die Methode auch direkt mit einem Array aufgerufen werden...
					// Das könnte die neue Syntax zwei Zeilen weiter sein...
					$aParamsCollection = array_values($mParams);
				}
				#print_r(array($aParamsCollection, $oRdt->getAbsName()));

				// the new syntax
				// <params><param get="this()" as="this" /></params>

				reset($aParamsCollection);
//				while(list($sKey,) = each($aParamsCollection)) {
				foreach($aParamsCollection As $param) {
					// Hier werden die Parameter für einen Ajax-Request verarbeitet.

					$sParam = $param['get'];
					$sAs = $param['as'];
//					$sParam = $aParamsCollection[$sKey]['get'];
//					$sAs = $aParamsCollection[$sKey]['as'];
					if(t3lib_div::isFirstPartOfStr($sParam, 'rowData::')) {
						$sParamName = substr($sParam, 9);
						$aRowParams[$sParamName] = '';
						if(($sValue = $this->getForm()->getDataHandler()->_getListData($sParamName)) !== FALSE) {
							$aRowParams[$sParamName] = $sValue;
						}
					} elseif(t3lib_div::isFirstPartOfStr($sParam, 'rowInput::')) {
						$sParamName = substr($sParam, 10);
						/* replacing *id* with *id for this row*; will be handled by JS framework */

						// note: _getAjaxEvent() is called when in rows rendering for list
						// _getElementHtmlId() on a renderlet is designed to return the correct html id for the input of this row in such a case
						$sAs = ($sAs === FALSE) ? $sParamName : $sAs;

						if(array_key_exists($sParamName, $this->getForm()->aORenderlets)) {
							$aParams[] = 'rowInput::' . $sAs . '::' . $this->getForm()->getWidget($sParamName)->_getElementHtmlId();
						}
					} elseif(t3lib_div::isFirstPartOfStr($sParam, 'sys_event.')) {
						$aParams[] = $sParam;
					} elseif(array_key_exists($sParam, $this->getForm()->aORenderlets)) {
						$sAs = (!$sAs) ? $sParam : $sAs;
						$aParams[] = 'rowInput::' . $sAs . '::' . $this->getForm()->getWidget($sParam)->_getElementHtmlId();
					} elseif($sParam === '\$this') {
						$aParams[] = 'rowInput::this::' . $oRdt->getAbsName();
					} elseif(strstr($sParam, AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN.'*')) {
						// Shortcut um alle Werte einer Box übergeben
						// Wir benötigen alle Renderlets mit einem bestimmten Prefix
						$names = tx_mkforms_util_Div::findKeysWithPrefix(array_flip($this->getForm()->getWidgetNames()), substr($sParam,0,strpos($sParam,'*')));
						foreach($names As $name) {
							$widget = $this->getForm()->getWidget($name);
							if($widget && $widget->hasData(/*$bForAjax*/ true)) { // Nur Widget mit Daten übernehmen. (Keine Buttons usw.)
								// Beim sammeln von Daten nie die IteratingId einbeziehen,
								// wir wollen alle Daten, nicht nur von dem ersten Feld!
								$sAs = $widget->getAbsName();
								$aParams[] = 'rowInput::' . $sAs . '::' . $widget->_getElementHtmlId(/*def*/false, /*def*/true, /*no iterating id*/false);
							}
						}
					} elseif(strstr($sParam, '::')) {
						// Ein freier Parameter
						$aParams[] = $sParam;
					} else {

						$sAs = ($sAs === FALSE) ? $sParam : $sAs;
						// $oRdt will be $mData in the majixmethods class
						// Hier wird wohl nach einer Majix-Methode gesucht, die ein Widget zurückliefert
						$mResult = $this->getForm()->resolveForMajixParams($sParam,$oRdt);
						if($this->getForm()->isRenderlet($mResult)) {
							#debug('It's a renderlet');
							$sAs = $param['as'];
							$aParams[] = 'rowInput::' . $sAs . '::' . $mResult->getAbsName();
						} else {
							debug($mResult, $sParam);
						}
					}
				}
			}

			if($bSyncValue === TRUE) {
				// Im Lister müssen wir aufpassen. Da muss der Name des Zeilen-Widgets gesetzt werden!
				$aParams[] = 'rowInput::sys_syncvalue::' . $oRdt->getElementId(false);
			}

			$aAjaxEventParams = $oRdt->alterAjaxEventParams(array(
				'eventname' => $sEvent,
				'eventid' => $sEventId,
				'hash' => $sHash,
				'cache' => $bCache,
				'persist' => $bPersist,
				'syncvalue' => $bSyncValue,
				'params' => $aParams,
				'row' => $aRowParams,
				'trigertinymce' => $sTrigerTinyMCE,
			));
			$sJsonParams = $this->getForm()->array2json($aAjaxEventParams['params']);
			$sJsonRowParams = $this->getForm()->array2json($aAjaxEventParams['row']);
			return "try{arguments;} catch(e) {arguments=[];} Formidable.f('" . $this->getForm()->getFormId() . "').executeAjaxEvent('" . $aAjaxEventParams["eventname"] . "', '" . $sRdtId . "', '" . $aAjaxEventParams["eventid"] . "', '" . $aAjaxEventParams["hash"] . "', " . (($aAjaxEventParams["cache"]) ? "true" : "false") . ", " . (($aAjaxEventParams["persist"]) ? "true" : "false") . ", " . (($aAjaxEventParams["trigertinymce"]) ? '"'.$aAjaxEventParams["trigertinymce"].'"' : "false") . ", " . $sJsonParams . ", " . $sJsonRowParams . ", arguments, " . $sConfirm . ");";
		}

		function wrapEventsForInlineJs($aEvents) {

			$aJson = array();
			reset($aEvents);
			while(list(, $sJs) = each($aEvents)) {
				$aJson[] = rawurlencode($sJs);
			}

			return 'Formidable.executeInlineJs(' . $this->getForm()->array2json($aJson) . ');';
		}

		function _getClientEvent($sObjectId, $aEvent = array(), $aEventData, $sEvent) {

			if(empty($aEventData)) {
				$aEventData = array();
			}

			$aParams = array();
			if(($mParams = $this->getForm()->_navConf('/params', $aEvent)) !== FALSE) {
				if(!is_array($mParams))
					$aParams[] = $mParams;
				else {
					foreach($mParams As $key => $param) {
						if(is_array($param)) {
							// Ein freier Parameter
							$aParams[$param['name']] = $this->getForm()->getConfigXML()->getLLLabel($param['value']);
						} elseif(is_string($param)) {
							$aParams[$key] = $this->getForm()->getConfigXML()->getLLLabel($param);
						}
					}
				}
				$aEventData['data']['params'] = array($aParams);
			}

			$sData = $this->getForm()->array2json(
				array(
					'init' => array(),			// init and attachevents are here for majix-ajax compat
					'attachevents' => array(),
					'tasks' => $aEventData,
				)
			);

			$bPersist = $this->getForm()->_defaultFalse('/persist', $aEvent);

			$sConfirm = 'false';
			if(array_key_exists('confirm', $aEvent) && trim($aEvent['confirm'] !== '')) {

				$sConfirm = '\'' .
					addslashes($this->getForm()->getConfigXML()->getLLLabel(
						$aEvent['confirm']
					))
				. '\'';
			}

			return "Formidable.f('" . $this->getForm()->getFormId() . "').executeClientEvent('" . $sObjectId . "', " . (($bPersist) ? "true" : "false") . ", {$sData}, '" . $sEvent . "', arguments, " . $sConfirm . ");";
		}

		function _getHiddenEntryId($entryId) {

			if(!empty($entryId)) {
				return "<input type = \"hidden\" id=\"" . $this->_getHiddenHtmlId("AMEOSFORMIDABLE_ENTRYID") . "\" name=\"" . $this->_getHiddenHtmlName("AMEOSFORMIDABLE_ENTRYID") . "\" value=\"" . $entryId . "\" />";
			}

			return '';
		}

		function _getHiddenCustom() {
			if(is_array($this->aCustomHidden) && sizeof($this->aCustomHidden) > 0)
			{ return implode('', $this->aCustomHidden);}

			return '';
		}

		function _setHiddenCustom($name, $value) {

			if(!is_array($this->aCustomHidden)) {
				$this->aCustomHidden = array();
			}

			$this->aCustomHidden[$name] = "<input type=\"hidden\" id=\"" . $this->_getHiddenHtmlId($name) . "\" name=\"" . $this->_getHiddenHtmlName($name) . "\" value=\"" . $value . "\" />";
		}

		function _getHiddenHtmlName($sName) {
			return $this->getForm()->getFormId() . '[' . $sName . ']';
		}

		function _getHiddenHtmlId($sName) {
			return $this->getForm()->getFormId() . '_' . $sName;
		}

		function _setFormWrap($bWrap) {
			$this->bFormWrap = $bWrap;
		}

		function _setValidation($bValidation) {
			$this->bValidation = $bValidation;
		}

		function _getThisFormId() {
			return $this->getForm()->getFormId();
		}

		function _setDisplayLabels($bDisplayLabels) {
			$this->bDisplayLabels = $bDisplayLabels;
		}

		/*function _displayLabel($label) {
			return ($this->bDisplayLabels && (trim($label) != '')) ? '<div>' . $label . "</div>\n" : "";
		}*/

		function renderStyles() {

			if(($mStyle = $this->_navConf('/style')) !== FALSE) {

				$sUrl = FALSE;
				$sStyle = FALSE;

				if($this->getForm()->isRunneable($mStyle)) {
					$sStyle = $this->callRunneable($mStyle);
				} elseif(is_array($mStyle) && array_key_exists('__value', $mStyle) && trim($mStyle['__value']) != '') {
					$sStyle = $mStyle['__value'];
				} elseif(is_array($mStyle) && array_key_exists('url', $mStyle)) {
					if($this->getForm()->isRunneable($mStyle['url'])) {
						$sUrl = $this->callRunneable($mStyle['url']);
					} else {
						$sUrl = $mStyle['url'];
					}

					if($this->_defaultFalse('/style/rewrite') === TRUE) {

						if(!tx_mkforms_util_Div::isAbsWebPath($sUrl)) {
							$sUrl = tx_mkforms_util_Div::toServerPath($sUrl);
							$sStyle = t3lib_div::getUrl($sUrl);
							$sUrl = FALSE;
						}
					}
				} elseif(is_string($mStyle)) {
					$sStyle = $mStyle;
				}

				if($sStyle !== FALSE) {
					reset($this->getForm()->aORenderlets);
					while(list($sName, ) = each($this->getForm()->aORenderlets)) {
						$oRdt =& $this->getForm()->aORenderlets[$sName];
						$sStyle = str_replace(
							array(
								'#' . $sName,
								"{PARENTPATH}"
							),
							array(
								'#' . $oRdt->_getElementCssId(),
								$this->getForm()->_getParentExtSitePath()
							),
							$sStyle
						);
					}

					//$sStyle = str_replace(';', ' !important;', $sStyle);

					$this->getForm()->additionalHeaderData(
						$this->getForm()->inline2TempFile(
							$sStyle,
							'css',
							"Form '" . $this->getForm()->getFormId() . "' styles"
						)
					);
				}

				if($sUrl !== FALSE) {
					$sUrl = tx_mkforms_util_Div::toWebPath($sUrl);
					$this->getForm()->additionalHeaderData(
						'<link rel="stylesheet" type="text/css" href="' . $sUrl . '" />'
					);
				}
			}
		}

		function processHtmlBag($mHtml, &$oRdt) {

			//$sLabel = array_key_exists("label", $oRdt->aElement) ? $this->getForm()->getConfigXML()->getLLLabel($oRdt->aElement["label"]) : "";
			$sLabel = $oRdt->getLabel();

			if(is_string($mHtml)/* && (($mHtml = trim($mHtml)) !== "")*/) {		// can be empty with empty readonly

				$mHtml = array(
					'__compiled'	=> $mHtml
				);
			}

			if(!empty($mHtml) && array_key_exists('__compiled', $mHtml) && is_string($mHtml['__compiled'])/* && trim($mHtml["__compiled"]) !== ""*/) {

				if(($mWrap = $oRdt->_navConf('/wrap')) !== FALSE) {

					if($this->getForm()->isRunneable($mWrap)) {
						$mWrap = $this->callRunneable($mWrap);
					}

					$mWrap = $this->getForm()->_substLLLInHtml($mWrap);

					$mHtml['__compiled'] = str_replace('|', $mHtml['__compiled'], $mWrap);
					// wrap added for f.schossig	2006/08/29
				}

				if(!array_key_exists('label', $mHtml)) {
					$mHtml['label'] = $sLabel;
				}

				if(!array_key_exists('label.', $mHtml)) {
					$mHtml['label.'] = array();
				}

				if(!array_key_exists('tag', $mHtml['label.'])) {
					$mHtml['label.']['tag'] = $oRdt->getLabelTag($sLabel);
				}

				if(!array_key_exists('htmlname', $mHtml)) {
					$mHtml['htmlname'] = $oRdt->_getElementHtmlName();
				}

				if(!array_key_exists('htmlid', $mHtml)) {
					$mHtml['htmlid'] = $oRdt->_getElementHtmlId();
				}

				if(!array_key_exists('htmlid.', $mHtml)) {
					$mHtml['htmlid.'] = array();
				}

				if(!array_key_exists('withoutformid', $mHtml['htmlid.'])) {
					$mHtml['htmlid.']['withoutformid'] = $oRdt->_getElementHtmlIdWithoutFormId();
				}

				if(($aError = $oRdt->getError()) !== FALSE) {
					$mHtml['error'] = $aError['message'];
					$mHtml['error.'] = tx_mkforms_util_Div::addDots($aError);
					$sClass = $mHtml['htmlid.']['withoutformid'];
					$mHtml['error.']['message.']['tag'] = '<span class="rdterror error ' . $sClass . '" for="' . $mHtml['htmlid'] . '">' . $aError['message'] . '</span>';
					$mHtml['error.']['class'] = 'hasError';
				}

				if($oRdt->_readOnly() && !array_key_exists('readonly', $mHtml)) {
					$mHtml['readonly'] = TRUE;
				}

				/*
				$mHtml = $this->recombineHtmlBag(
					$mHtml,
					$oRdt
				);
				*/
				if($oRdt->_navConf('/recombine') !== FALSE) {
					$this->getForm()->mayday('[' . $oRdt->getName() . '] <b>/recombine is deprecated</b>. You should use template methods instead');
				}

				if($this->getForm()->bDebug && $oRdt->_debugable()) {
					$mHtml = $this->_wrapIntoDebugContainer(
						$mHtml,
						$oRdt
					);
				}
			} else {
				$mHtml = array();
			}

			reset($mHtml);
			return $mHtml;
		}

		function displayOnlyIfJs($aRendered) {

			$aRdts = array_keys($this->getForm()->aORenderlets);
			reset($aRdts);
			while(list(, $sRdt) = each($aRdts)) {
				if($this->getForm()->aORenderlets[$sRdt]->displayOnlyIfJs() === TRUE) {
					$sJson = tx_mkforms_util_Json::getInstance()->encode($aRendered[$sRdt]['__compiled']);
					$sId = $this->getForm()->aORenderlets[$sRdt]->_getElementHtmlId() . '_unobtrusive';
					$aRendered[$sRdt]['__compiled'] = '<span id="' . $sId . '"></span>';

					$this->getForm()->attachInitTaskUnobtrusive('
						if($("' . $sId . '")) {$("' . $sId . '").innerHTML=' . $sJson . ';}
					');
				}
			}

			return $aRendered;
		}

		function wrapErrorMessage($sMessage) {

			if($this->isTrue('/template/errortagcompilednowrap')) {
				return $sMessage;
			}

			if(($sErrWrap = $this->_navConf('/template/errortagwrap')) !== FALSE) {

				if($this->getForm()->isRunneable($sErrWrap)) {
					$sErrWrap = $this->callRunneable($sErrWrap);
				}

				$sErrWrap = $this->getForm()->_substLLLInHtml($sErrWrap);
			} else {
				$sErrWrap = '<span class="errors">|</span>';
			}

			return str_replace(
				'|',
				$sMessage,
				$sErrWrap
			);
		}

		function compileErrorMessages($aMessages) {
			if($this->defaultFalse('/template/errortagcompilednobr')) {
				return implode('', $aMessages);
			}

			return implode('<br />', $aMessages);
		}

	}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/class.mainrenderer.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/class.mainrenderer.php']);
}