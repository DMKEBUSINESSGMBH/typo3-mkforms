<?php

	define('AMEOSFORMIDABLE_VALUE_NOT_SET', 'AMEOSFORMIDABLE_VALUE_NOT_SET');

	class formidable_mainrenderlet extends formidable_mainobject {

		var $__aCacheItems = array();

		var $aChilds		= array();
		var $aDependants	= array();
		var $aDependsOn		= array();
		var $bChild			= FALSE;

		var $aLibs = array();
		var $sMajixClass = '';
		// define methodname, if a specific init method in the js should be called, after dom is ready.
		var $sAttachPostInitTask = '';
		var $bCustomIncludeScript = FALSE;	// TRUE if the renderlet needs to handle script inclusion itself

		var $aSkin = FALSE;

		var $iteratingId = null;
		var $iteratingChilds = false;

		var $sCustomElementId = FALSE;		// if != FALSE, will be used instead of generated HTML id ( useful for checkbox-group renderlet )
		var $aPossibleCustomEvents = array();
		var $aCustomEvents = array();
		var $oRdtParent = FALSE;
		var $sRdtParent = FALSE;	// store the parent-name while in session-hibernation

		var $aForcedItems = FALSE;
		var $bAnonymous = FALSE;
		var $bHasBeenSubmitted = FALSE;
		var $bHasBeenPosted = FALSE;
		var $mForcedValue;
		var $bForcedValue = FALSE;

		var $bIsDataBridge = FALSE;
		var $bHasDataBridge = FALSE;
		var $oDataSource = FALSE;	// connection to datasource object, for databridge renderlets
		var $sDataSource = FALSE;		// hibernation state
		var $oDataBridge = FALSE;	// connection to databridge renderlet, plain renderlets
		var $sDataBridge = FALSE;		// hibernation state
		var $aDataBridged = array();
		var $aDataSetSignatures = array();	// dataset signature, hash on this rdt-htmlid for sliding accross iterations in lister (as it contains the current row uid when iterating)

		var $sDefaultLabelClass = 'label';
		var $bVisible = TRUE;	// should the renderlet be visible in the page ?

		var $bArrayValue = false; // the value can be an array or not

		var $aStatics = array(
			'type' => AMEOSFORMIDABLE_VALUE_NOT_SET,
			'namewithoutprefix' => AMEOSFORMIDABLE_VALUE_NOT_SET,
			'elementHtmlName' => array(),
			'elementHtmlNameWithoutFormId' => array(),
			'elementHtmlId' => array(),
			'elementHtmlIdWithoutFormId' => array(),
			'hasParent' => AMEOSFORMIDABLE_VALUE_NOT_SET,
			'hasSubmitted' => array(),
			'dbridge_getSubmitterAbsName' => AMEOSFORMIDABLE_VALUE_NOT_SET,
			'rawpostvalue' => array(),
			'dsetMapping' => AMEOSFORMIDABLE_VALUE_NOT_SET,
		);

		protected static $token = ''; // enthält einen eindeutigen String, um beispielsweise link tags zu trennen

		/**
		 * @var boolean
		 */
		protected $wasValidated = false;

		var $aEmptyStatics = array();

		function _init(&$oForm, $aElement, $aObjectType, $sXPath, $sNamePrefix = FALSE) {
			parent::_init($oForm, $aElement, $aObjectType, $sXPath, $sNamePrefix);
			$this->aEmptyStatics = $this->aStatics;

			$this->sDefaultLabelClass = $oForm->sDefaultWrapClass.'-'.$this->sDefaultLabelClass;

			$this->initDataSource();
			if(($this->oDataBridge =& $this->getDataBridgeAncestor()) !== FALSE) {
				$this->bHasDataBridge = TRUE;
				$this->oDataBridge->aDataBridged[] = $this->getAbsName();
			}

			$this->initChilds();
			$this->initProgEvents();
		}

		function initChilds($bReInit = FALSE) {
			if($this->mayHaveChilds() && $this->hasChilds()) {

				$sXPath = $this->sXPath . 'childs/';
				$this->aChilds =& $this->oForm->_makeRenderlets(
					$this->oForm->_navConf($sXPath),
					$sXPath,
					TRUE,	// $bChilds ?
					$this,
					$bReInit	// set to TRUE if existing renderlets need to be overwritten
				);					// used in rdt_modalbox->majixShowBox() for re-init before render
			}
		}

		/**
		 * Initialisiert das Attribut "dependson" eines Widgets
		 * @return unknown_type
		 */
		function initDependancies() {
			if(($sDeps = $this->_navConf('/dependson')) === FALSE) return;
			$aDeps = t3lib_div::trimExplode(',', trim($sDeps));

			reset($aDeps);
			while(list(, $sDep) = each($aDeps)) {

				if(array_key_exists($sDep, $this->oForm->aORenderlets)) {
					$this->aDependsOn[] = $sDep;
					$this->oForm->aORenderlets[$sDep]->aDependants[] = $this->getAbsName();
				} else {
					$mRes = $this->oForm->resolveForInlineConf($sDep);
					if($this->oForm->isRenderlet($mRes)) {
						$sAbsName = $mRes->getAbsName();
						$this->aDependsOn[] = $sAbsName;
						$this->oForm->aORenderlets[$sAbsName]->aDependants[] = $this->getAbsName();
					}
				}
			}
		}

		function cleanStatics() {
			if($this->mayHaveChilds() && $this->hasChilds()) {
				$aChildsKeys = array_keys($this->aChilds);
				reset($aChildsKeys);
				while(list(, $sKey) = each($aChildsKeys)) {
					$this->aChilds[$sKey]->cleanStatics();
				}
			}

			unset($this->aStatics['elementHtmlName']);
			unset($this->aStatics['elementHtmlNameWithoutFormId']);
			unset($this->aStatics['elementHtmlId']);
			unset($this->aStatics['elementHtmlIdWithoutFormId']);
			unset($this->aStatics['hasSubmitted']);
			unset($this->aStatics['dbridge_getSubmitterAbsName']);
			$this->aStatics['elementHtmlName'] = $this->aEmptyStatics['elementHtmlName'];
			$this->aStatics['elementHtmlNameWithoutFormId'] = $this->aEmptyStatics['elementHtmlNameWithoutFormId'];
			$this->aStatics['elementHtmlId'] = $this->aEmptyStatics['elementHtmlId'];
			$this->aStatics['elementHtmlIdWithoutFormId'] = $this->aEmptyStatics['elementHtmlIdWithoutFormId'];
			$this->aStatics['hasSubmitted'] = $this->aEmptyStatics['hasSubmitted'];
			$this->aStatics['dbridge_getSubmitterAbsName'] = $this->aEmptyStatics['dbridge_getSubmitterAbsName'];
			$this->aStatics['dsetMapping'] = $this->aEmptyStatics['dsetMapping'];

		}

		function doBeforeIteration(&$oIterating) {
			$this->cleanStatics();
		}

		function doAfterIteration() {
			$this->cleanStatics();
		}

		function doBeforeIteratingRender(&$oIterating) {
			if($this->mayBeDataBridge()) {
				$this->initDatasource();
				$this->processDataBridge();
			}
		}

		function doAfterIteratingRender(&$oIterating) {
		}

		function doBeforeNonIteratingRender(&$oIterating) {
			$this->cleanStatics();

			if(!$this->hasParent() && $this->mayBeDataBridge()) {
				$this->processDataBridge();
			}
		}

		function doAfterNonIteratingRender(&$oIterating) {

		}

		function doBeforeListRender(&$oListObject) {
			// nothing here
		}
		/**
		 * Das wird vom Lister aufgerufen. Er initialisiert damit die einzelnen Spalten
		 * @param tx_mkforms_widgets_lister_Main $oListObject
		 */
		function doAfterListRender(&$oListObject) {
			$init = array(
				'iterating' => true,
				'iterator' => $oListObject->_getElementHtmlId()
			);
			$this->includeScripts($init);
		}

		/**
		 * // abstract method
		 * Sehr seltsame abstrakte Methode...
		 */
		function initDataSource() {

			if(!$this->mayBeDataBridge()) {
				return FALSE;
			}

			if(($sDs = $this->_navConf('/datasource/use')) !== FALSE) {

				if(!array_key_exists($sDs, $this->oForm->aODataSources)) {
					$this->oForm->mayday('renderlet:' . $this->_getType() . "[name='" . $this->getName() . "'] bound to unknown datasource '<b>" . $sDs . "</b>'.");
				}

				$this->oDataSource =& $this->oForm->aODataSources[$sDs];
				$this->bIsDataBridge = TRUE;

				if((($oIterableAncestor = $this->getIterableAncestor()) !== FALSE) && !$oIterableAncestor->isIterating()) {
					// is iterable but not iterating, so no datasource initialization
					return FALSE;
				}

				if(($sKey = $this->dbridge_getPostedSignature(TRUE)) !== FALSE) {
					// found a posted signature for this databridge
						// using given signature
				} elseif(($sKey = $this->_navConf('/datasource/key')) !== FALSE) {
					if($this->oForm->isRunneable($sKey)) {
						$sKey = $this->getForm()->getRunnable()->callRunnableWidget($this, $sKey);
					}
				} else {
					$sKey = 'new';
				}

				if($sKey === FALSE) {
					$this->oForm->mayday('renderlet:' . $this->_getType() . "[name='" . $this->getName() . "'] bound to datasource '<b>" . $sDs . "</b>' is missing a valid key to connect to data.");
				}

				$sSignature = $this->oDataSource->initDataSet($sKey);
				$this->aDataSetSignatures[$this->_getElementHtmlId()] = $sSignature;
			}
		}

		/**
		 * Returns a token string.
		 * @return string
		 */
		protected static function getToken() {
			if(!self::$token)
				self::$token = md5(microtime());
			return self::$token;
		}

		/**
		 * Liefert das Parent-Widget
		 * @return formidable_mainrenderlet
		 */
		public function getParent() {
			return $this->oRdtParent;
		}
		function hasParent() {
			return ($this->oRdtParent !== FALSE && is_object($this->oRdtParent));
		}
		/**
		 * Returns true if widget has iterating childs. This is normally true for type Lister.
		 * @return true
		 */
		public function hasIteratingChilds(){
			return $this->iteratingChilds;
		}
		/**
		 * Returns all childs
		 * @return array[formidable_mainrenderlet] or empty array
		 */
		public function getChilds() {
			return $this->aChilds;
		}
		function isChildOf($sRdtName) {
			return ($this->hasParent() && ($this->oRdtParent->getAbsName() === $sRdtName));
		}

		function isDescendantOf($sRdtName) {

			if($this->hasParent() && $sRdtName !== $this->getAbsName()) {

				$sCurrent = $this->getAbsName();

				if($this->oForm->aORenderlets[$sCurrent]->isChildOf($sRdtName) === TRUE) {
					return TRUE;
				}

				while(array_key_exists($sCurrent, $this->oForm->aORenderlets) && $this->oForm->aORenderlets[$sCurrent]->hasParent()) {

					$sCurrent = $this->oForm->aORenderlets[$sCurrent]->oRdtParent->getAbsName();
					if(array_key_exists($sCurrent, $this->oForm->aORenderlets) && $this->oForm->aORenderlets[$sCurrent]->isChildOf($sRdtName)) {
						return TRUE;
					}
				}
			}

			return FALSE;
		}

		function isAncestorOf($sAbsName) {
			if(array_key_exists($sAbsName, $this->oForm->aORenderlets) && $this->oForm->aORenderlets[$sAbsName]->isDescendantOf($this->getAbsName())) {
				return TRUE;
			}

			return FALSE;
		}

		function hasBeenPosted() {
			return $this->bHasBeenPosted;
		}

		function hasBeenSubmitted() {
			if($this->hasDataBridge()) {
				return FALSE;
			}

			return $this->hasBeenPosted();
		}

		function hasBeenDeeplyPosted() {

			$bHasBeenPosted = $this->hasBeenPosted();

			if(!$bHasBeenPosted && $this->mayHaveChilds() && $this->hasChilds()) {
				$aChildKeys = array_keys($this->aChilds);
				reset($aChildKeys);
				while(!$bHasBeenPosted && (list(, $sKey) = each($aChildKeys))) {
					$bHasBeenPosted = $bHasBeenPosted && $this->aChilds[$sKey]->hasBeenDeeplyPosted();
				}
			}

			return $bHasBeenPosted;
		}

		function hasBeenDeeplySubmitted() {
			if($this->hasDataBridge()) {
				return FALSE;
			}

			return $this->hasBeenDeeplyPosted();
		}

		function isAnonymous() {
			return $this->bAnonymous !== FALSE;
		}

		function checkPoint(&$aPoints, array &$options = array()) {
			/* nothing by default */
		}

		function initProgEvents() {
			if(($aEvents = $this->_getProgServerEvents()) !== FALSE) {

				reset($aEvents);
				while(list($sEvent, $aEvent) = each($aEvents)) {

					if($aEvent['runat'] == 'server') {

						$aDefinedEvent = $aEvent;

						$sEventId = $this->oForm->_getServerEventId($this->_getName(), $aEvent);	// before any modif to get the *real* eventid

						$aNeededParams = array();

						if(array_key_exists('params', $aEvent) && is_string($aEvent['params'])) {
							$aNeededParams = t3lib_div::trimExplode(',', $aEvent['params']);
							$aEvent['params'] = $aNeededParams;
						}

						$this->oForm->aServerEvents[$sEventId] = array(
							'eventid' => $sEventId,
							'trigger' => $sEvent,
							'when' => (array_key_exists('when', $aEvent) ? $aEvent['when'] : 'after-init'),	// default when : end
							'event' => $aEvent,
							'params' => $aNeededParams,
							'raw' => $aDefinedEvent,
						);
					}

				}
			}
		}

		function _getProgServerEvents() {
			return FALSE;
		}

	/**
	 * Widgets können hier entscheiden, ob zusätzliche JS-Dateien selbst eingebunden werden.
	 * Bei True ist das Widget für die Einbindung verantwortlich. Bei False wird der Standard eingebunden.
	 * @return boolean
	 */
	protected function isCustomIncludeScript() {
		return $this->bCustomIncludeScript;
	}
		function render($bForceReadonly = FALSE) {

			if((($oIterating = $this->getIteratingAncestor()) !== FALSE)) {
				$this->doBeforeIteratingRender($oIterating);
			} else {
				$this->doBeforeNonIteratingRender($oIterating);
			}

			if($bForceReadonly === TRUE || $this->_readonly()) {
				$mRendered = $this->_renderReadOnly();
			} else {
				$mRendered = $this->_render();
			}

			$this->includeLibs();

			if(!$this->isCustomIncludeScript()) {
				$this->includeScripts();
			}

			$this->attachCustomEvents();

			if($oIterating !== FALSE) {
				$this->doAfterIteratingRender($oIterating);
			} else {
				$this->doAfterNonIteratingRender($oIterating);
			}

			return $mRendered;
		}

		function _render() {
			return $this->getLabel();
		}

		function renderWithForcedValue($mValue) {
			$this->forceValue($mValue);
			$mRendered = $this->render();
			$this->unForceValue();

			return $mRendered;
		}

		function forceValue($mValue) {
			$this->mForcedValue = $mValue;
			$this->bForcedValue = TRUE;
		}

		function unForceValue() {
			$this->mForcedValue = FALSE;
			$this->bForcedValue = FALSE;
		}

		function renderReadOnlyWithForcedValue($mValue) {
			$this->forceValue($mValue);
			$mRendered = $this->render(TRUE);
			$this->unForceValue();
			return $mRendered;
		}

		function _renderReadOnly() {

			$mValue = $this->getValue();
			$mHuman = $this->_getHumanReadableValue($mValue);

			$value = 1;
			if($this->hasParent() && $this->getParent()->hasIteratingChilds()) {
				// Im Lister schreiben wir bei readOnly den echten Wert in das hidden-Feld.
				// Theoretisch sollte das aber immer möglich sein.
				$value = $mValue;
			}
			$sPostFlag = '<input type="hidden" id="' . $this->_getElementHtmlId() . '" name="' . $this->_getElementHtmlName() . '" value="'.$value.'" />';
			$sCompiled = $this->wrapForReadOnly($mHuman) . $sPostFlag;

			$mHtml = array(
				'__compiled' => $sCompiled,
				'additionalinputparams' => $this->_getAddInputParams($sId),
				'value' => $mValue,
				'value.' => array(
					'nl2br' => nl2br(strval($mValue)),
					'humanreadable' => $mHuman,
				)
			);

			if(($sListHeader = $this->_navConf('/listheader')) !== FALSE) {
				$mHtml['listheader'] = $this->oForm->getConfig()->getLLLabel($sListHeader);
			}

			if(!is_array($mHtml['__compiled'])) {
				$mHtml['__compiled'] = $this->_displayLabel($this->getLabel()) . $mHtml['__compiled'];
			}

			$this->includeLibs();

			return $mHtml;
		}

		function wrapForReadOnly($sHtml) {
			$aAdditionalParams = array(
					'class' => 'readonly',
					'style' => 'display: none;',
					'autocomplete' => ''
				);
			if($this->isVisible() === FALSE || $this->_shouldHideBecauseDependancyEmpty()) {
				$aAdditionalParams['style'] = 'display: none;';
			}
			// an die htmlid ein _readonly hängen,
			// um später gezieht drauf zugreifen zu können
			// und den code valide zu halten, da das hiddenfield die gleiche id trägt.
			return '<span id="' . $this->_getElementHtmlId() . '_readonly" '.$this->_getAddInputParams($aAdditionalParams).'>' . $sHtml . '</span>';
		}

		function _displayLabel($sLabel, $aConfig = FALSE) {
			if($this->oForm->oRenderer->bDisplayLabels) {
				return $this->getLabelTag($sLabel, $aConfig);
			}

			return '';
		}

		function getLabel($sLabel = FALSE, $sDefault=FALSE) {
			$sRes = '';

			if($sLabel === FALSE) {
				if(($sLabel = $this->_navConf('/label')) !== FALSE) {
					$sLabel = $this->getForm()->getRunnable()->callRunnable($sLabel);
					$sRes = $this->getForm()->getConfig()->getLLLabel($sLabel);
				} else {
					if($this->getForm()->sDefaultLLLPrefix !== FALSE) {
						// trying to automap label
						$sKey = 'LLL:' . $this->getAbsName() . '.label';
						$sRes = $this->getForm()->getConfig()->getLLLabel($sKey);
					}
				}
			} else {
				$sRes = $this->oForm->getConfig()->getLLLabel($sLabel);
			}

			if(trim($sRes) === '' && $sDefault !== FALSE) {
				$sRes = $this->getLabel($sDefault);
			}

			if(($sLabelWrap = $this->_navConf('/labelwrap')) !== FALSE) {
				if($this->oForm->isRunneable($sLabelWrap)) {
					$sLabelWrap = $this->getForm()->getRunnable()->callRunnableWidget($this, $sLabelWrap);
				}

				if(!$this->oForm->_isFalseVal($sLabelWrap)) {
					$sRes = str_replace('|', $sRes, $sLabelWrap);
				}
			}

			return '' . $sRes;
		}

		function getLabelTag($sLabel, $aConfig = FALSE) {
			if(trim($sLabel) === '') return '';

			// nur Label ohne Tag ausgeben
			if($this->_navConf("/addnolabeltag") === TRUE || $this->_navConf("/addnolabeltag") == 'true'){
				return $sLabel;
			}

			$sHtmlId = ($aConfig !== FALSE && $aConfig['sId']) ? $aConfig['sId'] : $this->_getElementHtmlId();
			$sLabelId = $sHtmlId . '_label';
			$aClasses = array();
			$aClasses[] = $this->sDefaultLabelClass;

			if(($sLabelClass = $this->defaultTrue('/labelidclass', $aConfig)) !== FALSE) {
				$aClasses[] = $sLabelId;
			}

			if($this->defaultTrue('/labelfor', $aConfig) !== FALSE) {
				$forAttribute = !$this->_readOnly()
					? ' for="' . $sHtmlId . '"' : '';
			}

			if(($sLabelCustom = $this->_navConf('/labelcustom', $aConfig)) !== FALSE) {
				$sLabelCustom .= ' '.trim($sLabelCustom);
			} else { $sLabelCustom = ''; }

			if(($sLabelClass = $this->_navConf('/labelclass', $aConfig)) !== FALSE) {
				if($this->oForm->isRunneable($sLabelClass)) {
					$aClasses[] = $this->getForm()->getRunnable()->callRunnable($sLabelClass);
				} else {
					$aClasses[] = $sLabelClass;
				}
			}

			if($this->getForm()->getRenderer()->defaultFalse('autordtclass') === TRUE) {
				$aClasses[] = $this->getName() . '_label';
			}

			$aClasses = array_unique($aClasses);

			if($this->hasError()) {
				$aError = $this->getError();
				$aClasses[] = 'hasError';
				$aClasses[] = 'hasError' . ucfirst($aError['info']['type']);
			}

			$sClassAttribute = (count($aClasses) === 0) ? '' : ' ' . implode(' ', $aClasses);

			if(($sLabelStyle = $this->_navConf('/labelstyle', $aConfig)) !== FALSE) {
				$sLabelStyle = $this->getForm()->getRunnable()->callRunnable($sLabelStyle);
			}

			if($this->isVisible() === FALSE || $this->_shouldHideBecauseDependancyEmpty()) {
				$sLabelStyle .= 'display: none;';
			}
			$sLabelStyle = empty($sLabelStyle) ? '' : ' style="'.$sLabelStyle.'"';

			return '<label id="' . $sLabelId . '"' . $sLabelStyle . ' class="' . $sClassAttribute . '"' . $forAttribute . $sLabelCustom . '>' . $sLabel . "</label>\n";
		}

		function _getType() {

			if($this->aStatics['type'] === AMEOSFORMIDABLE_VALUE_NOT_SET) {
				$this->aStatics['type'] = $this->_navConf('/type');
			}

			return $this->aStatics['type'];
		}

		function _getName() {
			return $this->_getNameWithoutPrefix();
		}

		/**
		 * Liefert den Namen des Widgets. Dies ist immer der Name ohne den kompletten Pfad, falls
		 * das Widget in einer Box liegt.
		 */
		function getName() {
			return $this->_getName();
		}

		function _getNameWithoutPrefix() {
			if($this->aStatics['namewithoutprefix'] === AMEOSFORMIDABLE_VALUE_NOT_SET) {
				$this->aStatics['namewithoutprefix'] = $this->_navConf('/name');
			}

			return $this->aStatics['namewithoutprefix'];
		}

		function getId() {	// obsolete as of revision 1.0.193SVN
			return $this->getAbsName();
		}

		function getAbsName($sName = FALSE) {

			if($sName === FALSE) {
				$sName = $this->_getNameWithoutPrefix();
			}

			$sPrefix = '';

			if($this->hasParent()) {
				$sPrefix = $this->getParent()->getAbsName();
			}

			if($sPrefix === '') {
				return $sName;
			}

			return $sPrefix . AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN . $sName . AMEOSFORMIDABLE_NESTED_SEPARATOR_END;
		}

		function getNameRelativeTo(&$oRdt) {
			$sOurAbsName = $this->getAbsName();
			$sTheirAbsName = $oRdt->getAbsName();

			return $this->oForm->relativizeName($sOurAbsName, $sTheirAbsName);
		}

		function dbridged_getNameRelativeToDbridge() {
			return $this->getNameRelativeTo($this->oDataBridge);
		}

	/**
	 * Liefert bei verschachtelten Elementen den HTML-Name eines Kind-Objektes. Der Aufruf muss
	 * an die jeweilige Box erfolgen
	 * @param formidable_mainrenderlet $child
	 * @return string
	 */
	protected function getElementHtmlName4Child($child) {
		$childId = $child->_getNameWithoutPrefix();
		$htmlId = $this->_getElementHtmlName(); // ID Box/Lister
		$htmlId .= '[' . $childId . ']';
		return $htmlId;
	}

		/**
		 * Liefert den HTML-Namen des Elements
		 * <input type="xyz" name="..." />
		 * @param $sName
		 * @return string
		 */
		function _getElementHtmlName($sName = FALSE) {

			if($sName === FALSE) {
				$sName = $this->_getNameWithoutPrefix();
			}

			if(!array_key_exists($sName, $this->aStatics['elementHtmlName'])) {
				$sPrefix = '';

				if($this->hasParent()) {
					$parent = $this->getParent();
					$this->aStatics['elementHtmlName'][$sName] = $parent->getElementHtmlName4Child($this);
				} else {
					$sPrefix = $this->oForm->formid;
					$this->aStatics['elementHtmlName'][$sName] = $sPrefix . '[' . $sName . ']';
				}
			}

			return $this->aStatics['elementHtmlName'][$sName];
		}

		function _getElementHtmlNameWithoutFormId($sName = FALSE) {

			if($sName === FALSE) {
				$sName = $this->_getNameWithoutPrefix();
			}

			$sRes = '';

			if(!array_key_exists($sName, $this->aStatics['elementHtmlNameWithoutFormId'])) {
				if($this->hasParent()) {
					$sRes = $this->oRdtParent->_getElementHtmlNameWithoutFormId() . '[' . $sName . ']';
				} else {
					$sRes = $sName;
				}

				$this->aStatics['elementHtmlNameWithoutFormId'][$sName] = $sRes;
			}

			return $this->aStatics['elementHtmlNameWithoutFormId'][$sName];
		}

	/**
	 * Liefert bei verschachtelten Elementen die HTML-ID eines Kind-Objektes. Der Aufruf muss
	 * an die jeweilige Box erfolgen
	 * @param formidable_mainrenderlet $child
	 * @return string
	 */
	protected function getElementHtmlId4Child($child, $withForm = true, $withIteratingId = true) {
		$childId = $child->_getNameWithoutPrefix();
		$htmlId = $this->buildHtmlId($withForm, $withIteratingId); // ID Box/Lister
		$htmlId .= AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN . $childId . AMEOSFORMIDABLE_NESTED_SEPARATOR_END;
		return $htmlId;
	}

	/**
	 * Liefert die ID des Widgets. Diese ist normalerweise gleich der HTML-ID. Es gibt aber den Sonderfall im
	 * ListerSelect. Da haben die einzelnen RadioButtons eine andere HTML-ID als die ButtonGroup. Für die
	 * Übernahme der Daten im DataHandler wird die ID der Group benötigt.
	 * Bisher ein Aufruf im main_datahandler::getRdtValue_submit_edition
	 * @param $sId
	 * @return string
	 */
	function getElementId($withForm = true) {
		return $this->_getElementHtmlId(FALSE, $withForm);
	}

	/**
	 * Liefert die HTML-ID des Elements
	 * <input type="xyz" id="..." />
	 * @param $sId
	 * @param boolean $withForm
	 * @param boolean $withIteratingId
	 * @return string
	 */
	function _getElementHtmlId($sId = FALSE, $withForm = true, $withIteratingId = true, $t = false) {

		if($sId === FALSE) {
			$sId = $this->_getNameWithoutPrefix();
		}
		if(strlen($this->getIteratingId()) && $withIteratingId){
			$sId = AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN . $this->getIteratingId() . AMEOSFORMIDABLE_NESTED_SEPARATOR_END .
				AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN . $sId . AMEOSFORMIDABLE_NESTED_SEPARATOR_END;
		}

		$cacheKey = $sId . '-' . intval($withForm) . '-' . intval($withIteratingId);
		if(!array_key_exists($cacheKey, $this->aStatics['elementHtmlId'])) {
			$this->aStatics['elementHtmlId'][$cacheKey] = $this->buildHtmlId($withForm, $withIteratingId);
		}

		return $this->aStatics['elementHtmlId'][$cacheKey];
	}

	public function setIteratingId($id=null){
		$this->iteratingId = $id;
	}
	public function getIteratingId(){
		return $this->iteratingId;
	}

	protected function buildHtmlId($withForm = true, $withIteratingId = true) {
		$sId = $this->_getNameWithoutPrefix();

		$ret = '';
		if($this->hasParent()) {
			$parent = $this->getParent();
			$ret = $parent->getElementHtmlId4Child($this, $withForm, $withIteratingId);
		} else {
			$sPrefix = $withForm ? $this->oForm->formid . AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN : '';
			$ret = $sPrefix . $sId . AMEOSFORMIDABLE_NESTED_SEPARATOR_END;
		}

		return $ret;
	}

	/**
	 * @deprecated use getElementHtmlId(false)
	 */
	function _getElementHtmlIdWithoutFormId($sId = FALSE) {
		if($sId === FALSE) {
			$sId = $this->_getNameWithoutPrefix();
		}

		if(!array_key_exists($sId, $this->aStatics['elementHtmlIdWithoutFormId'])) {
			$this->aStatics['elementHtmlIdWithoutFormId'][$sId] = $this->buildHtmlId(false);
		}

		return $this->aStatics['elementHtmlIdWithoutFormId'][$sId];
	}

		function &getIterableAncestor() {
			if($this->hasParent()) {
				if($this->oRdtParent->isIterable()) {
					return $this->oRdtParent;
				} else {
					return $this->oRdtParent->getIterableAncestor();
				}
			}

			return FALSE;
		}

		function &getIteratingAncestor() {

			if($this->hasParent()) {
				if($this->oRdtParent->isIterable() && $this->oRdtParent->isIterating()) {
					return $this->oRdtParent;
				} else {
					return $this->oRdtParent->getIteratingAncestor();
				}
			}

			return FALSE;
		}

		function &getDataBridgeAncestor() {

			if($this->hasParent()) {
				if($this->oRdtParent->isDataBridge()) {
					return $this->oRdtParent;
				} else {
					return $this->oRdtParent->getDataBridgeAncestor();
				}
			}

			return FALSE;
		}

		function _getElementCssId($sId = FALSE) {
			return str_replace(
				array('.'),
				array('\.'),
				$this->_getElementHtmlId($sId)
			);
		}




		function __getDefaultValue() {

			$mValue = $this->_navConf('/data/defaultvalue/');

			if($this->oForm->isRunneable($mValue)) {
				// here bug corrected thanks to Gary Wong @ Spingroup
				// see http://support.typo3.org/projects/formidable/m/typo3-project-formidable-defaultvalue-bug-in-text-renderlet-365454/
				$mValue = $this->getForm()->getRunnable()->callRunnableWidget($this, $mValue);
			}

			return $this->_substituteConstants($mValue);
		}

		function declareCustomValidationErrors() {

		}

		/**
		 * Die Methode liefert den Wert der Fest für ein Feld im XML definiert ist
		 *
		 * @return den Wert oder FALSE
		 */
		function __getValue() {
			if(($mValue = $this->_navConf('/data/value/')) !== FALSE) {
				$mValue = $this->getForm()->getRunnable()->callRunnable($mValue);
			}
			return $this->_substituteConstants($mValue);
		}

		function setValue($mValue) {
			$sAbsName = $this->getAbsName();
			$sAbsPath = str_replace(AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN, '/', $sAbsName);

			$this->getForm()->setDeepData(
				$sAbsPath,
				$this->getForm()->getDataHandler()->__aFormData,
				$mValue
			);

			$this->mForcedValue = $mValue;
			$this->bForcedValue = TRUE;
			$this->wasValidated = FALSE;//falls Abhängigkeiten bestehen
		}

		function _getListValue() {

			$mValue = $this->_navConf('/data/listvalue/');

			if(is_array($mValue)) {

				// on vrifie si on doit appeler un userobj pour rcuprer la valeur par dfaut

				if($this->oForm->isRunneable($mValue)) {
					$mValue = $this->getForm()->getRunnable()->callRunnableWidget($this, $mValue);
				} else {
					$mValue = '';
				}
			}

			return $this->_substituteConstants($mValue);
		}

		function _getValue() {
			$this->oForm->mayday("_getValue() is deprecated");
			if($this->bForcedValue === TRUE) {
				return $this->mForcedValue;
			} else {
				return $this->getForm()->getDataHandler()->getRdtValue(
					$this->getAbsName()
				);
			}
		}

		function getValue() {
			$ret = $this->getForm()->getDataHandler()->getRdtValue($this->getAbsName());
			// Bei Widgets aus dem Lister haben wir eine IteratingId und als Ergebnis ein Array
			if(is_array($ret) && $this->getIteratingId()) {
				$ret = $ret[$this->getIteratingId()];
			//wir müssen XSS nur bei strings entfernen und wenn es gewünscht ist
			}elseif (is_string($ret) && $this->sanitize()) {
				$ret = t3lib_div::removeXSS($ret);
			}

			return $ret;
		}

		/**
		 * Soll bei getValue XSS entfernt werden?
		 * default ja
		 * @return boolean
		 */
		protected function sanitize() {
			return $this->defaultTrue('/sanitize');
		}

		function getValueForHtml($mValue = FALSE) {
			if($mValue === FALSE) {
				$mValue = $this->getValue();
			}

			if(is_string($mValue)) {
				tx_rnbase::load('tx_mkforms_util_Templates');
				$mValue =
					tx_mkforms_util_Templates::sanitizeStringForTemplateEngine(htmlspecialchars($mValue));
			}

			return $mValue;
		}

		function refreshValue() {
			if ($this->bForcedValue && $this->isForcedValueOnRefresh()) {
				return;
			}
			$value = $this->getForm()->getDataHandler()->getRdtValue_noSubmit_noEdit($this->getAbsName());
			$this->setValue($value);
		}

		function isForcedValueOnRefresh() {
			return $this->isTrue('/data/forcedvalueonrefresh');
		}

		function _substituteConstants($sValue) {

			if($sValue === 'CURRENT_TIMESTAMP') {
				$sValue = time();
			} elseif($sValue === 'CURRENT_PAGEID') {
				// front end only
				$sValue = $GLOBALS['TSFE']->id;
			} elseif($sValue === 'CURRENT_USERID') {
				// front end only
				$sValue = $GLOBALS['TSFE']->fe_user->user['uid'];
			}

			return $sValue;
		}

		function _getAddInputParamsArray($aAdditional = array()) {
			$aAddParams = array();

			if(!is_array($aAdditional)) {
				$aAdditional = array();
			}

			if(!array_key_exists('style', $aAdditional)) {
				$aAdditional['style'] = '';
			}

			if(!array_key_exists('class', $aAdditional)) {
				$aAdditional['class'] = '';
			}

			if(($sClass = trim($this->_getClasses(false,true,$aAdditional['class']))) !== '') {
				$aAddParams[] = $sClass;
			}

			if(($sStyle = trim($this->_getStyle(FALSE, $aAdditional['style']))) !== '') {
				$aAddParams[] = $sStyle;
			}

			if(($sCustom = trim($this->_getCustom())) !== '') {
				$aAddParams[] = $sCustom;
			}

			if(($sEvents = trim($this->_getEvents())) !== '') {
				$aAddParams[] = $sEvents;
			}

			if(($placeHolder = trim($this->_getPlaceholder())) !== '') {
				$aAddParams[] = $placeHolder;
			}

			/*
				disabled-property for renderlets patch by Manuel Rego Casasnovas
				@see http://lists.netfielders.de/pipermail/typo3-project-formidable/2007-December/000803.html
			*/

			if(($sDisabled = trim($this->_getDisabled())) !== '') {
				$aAddParams[] = $sDisabled;
			}

			if(($sTitle = $this->_navConf('/title')) !== FALSE) {
				if($this->oForm->isRunneable($sTitle)) {
					$sTitle = $this->getForm()->getRunnable()->callRunnableWidget($this, $sTitle);
				}

				$sTitle = $this->oForm->_substLLLInHtml($sTitle);

				if(trim($sTitle) !== '') {
					$aAddParams[] = "title=\"" . strip_tags(str_replace("\"", "\\\"", $sTitle)) . "\"";
					if(($bTooltip = $this->defaultFalse('/tooltip')) !== FALSE) {

						$this->oForm->getJSLoader()->loadTooltip();
						$sId = $this->_getElementHtmlId();

						$sJsOptions = $this->oForm->array2json(array(
							'mouseFollow' => FALSE,
							'content' => $sTitle,
						));

						$sJs =<<<TOOLTIP

	new Tooltip(Formidable.f("{$this->oForm->formid}").o("{$sId}").domNode(), {$sJsOptions});

TOOLTIP;
						$this->oForm->attachPostInitTask(
							$sJs,
							$sId . ' tooltip initialization'
						);
					}
				}
			}

			if(($sHtmlAutoComplete = $this->htmlAutocomplete()) !== '' && !array_key_exists('autocomplete', $aAdditional)) {
				$aAddParams[] = $sHtmlAutoComplete;
			}

			#print_r($aAddParams);
			return $aAddParams;
		}

		function _getAddInputParams($aAdditional = array()) {

			$aAddParams = $this->_getAddInputParamsArray($aAdditional);

			if(count($aAddParams) > 0) {
				$sRes = ' ' . implode(' ', $aAddParams) . ' ';
			} else {
				$sRes = '';
			}

			return $sRes;
		}

		function _getCustom($aConf = FALSE) {

			if(($mCustom = $this->_navConf('/custom/', $aConf)) !== FALSE) {
				if($this->oForm->isRunneable($mCustom)) {
					$mCustom = $this->getForm()->getRunnable()->callRunnableWidget($this, $mCustom);
				}

				return ' ' . $mCustom . ' ';
			}

			return '';
		}

		/**
		 *
		 * @return string
		 */
		protected function _getPlaceholder() {
			$placeholder = '';
			if(($placeholder = $this->_navConf('/placeholder/')) !== FALSE) {
				if($this->oForm->isRunneable($placeholder)) {
					$placeholder = $this->getForm()->getRunnable()->callRunnableWidget(
						$this, $placeholder
					);
				}

				$placeholder = $this->getForm()->getConfig()->getLLLabel($placeholder);

				$placeholder = ' placeholder="' . $placeholder . '" ';
			}

			return $placeholder;
		}

		/**
		 * Prüft, ob der Wert $mValue im Array $aHideIf enthalten ist.
		 *
		 * @param mixed $mValue
		 * @param array $aHideIf
		 * @return boolean
		 */
		function _isDependancyValue($mValue, $aHideIf) {
			if($aHideIf===false) {
				return true;
			}
			if(!is_array($mValue)) {
				$mValue = array($mValue);
			}
			foreach($mValue as $sValue) {
				if(in_array($sValue, $aHideIf)){
					return TRUE;
				}
			}
			return FALSE;
		}

		function _shouldHideBecauseDependancyEmpty($bCheckParent=FALSE) {
			$bOrZero = $sIs = $sIsNot = FALSE;
			if(
				   (($bEmpty = $this->_defaultFalse('/hideifdependancyempty')) === TRUE)
				|| (($bOrZero = $this->_defaultFalse('/hideifdependancyemptyorzero')) === TRUE)
				|| (($sIs = $this->_navConf('/hideifdependancyis')) !== FALSE)
				|| (($sIsNot = $this->_navConf('/hideifdependancyisnot')) !== FALSE)
			   ) {
				if($this->hasDependancies()) {
				   	// bei hideIfDependancyIs & hideIfDependancyIsNot sind mehrere Werte Kommasepariert möglich.
				   	$sIs = $sIs ? t3lib_div::trimExplode(',', trim($sIs)) : FALSE;
				   	$sOperator = $this->_navConf('/hideifoperator');
				   	$sOperator = ($sOperator === FALSE || strtoupper($sOperator) != 'OR') ? 'AND' : 'OR';
				   	$bHide = FALSE;
				   	$sIsNot = $sIsNot ? t3lib_div::trimExplode(',', trim($sIsNot)) : FALSE;
				   	$sIsHiddenD = $this->_defaultFalse('/hideifdependancyishiddenbecausedependancy');
					reset($this->aDependsOn);
					while(list(, $sKey) = each($this->aDependsOn)) {
						if(	// ausblenden wenn,
								// Element nicht existiert
							   !array_key_exists($sKey, $this->oForm->aORenderlets)
							|| !is_object($oRdt = $this->oForm->aORenderlets[$sKey])
								// wenn das element selbst durch dependances versteckt is
							|| ($sIsHiddenD && $oRdt->_shouldHideBecauseDependancyEmpty(true))
								// der Wert leer ist
							|| ( $bEmpty && $oRdt->isValueEmpty())
								// der Wert 0 ist
							|| ($bOrZero === TRUE && (intval($oRdt->getValue()) === 0))
								// der Wert eines der angegebenen Werte hat
							|| ($sIs !== FALSE && $this->_isDependancyValue($oRdt->getValue(), $sIs) )
								// der Wert eines der angegebenen Werte nicht hat
							|| ($sIsNot !== FALSE && !$this->_isDependancyValue($oRdt->getValue(), $sIsNot) )
						   ) {
							$bHide = TRUE;
							if ($sOperator == 'AND') break;
						}
						elseif (
							$sOperator == 'OR'
							&& array_key_exists($sKey, $this->oForm->aORenderlets)
							&& is_object($oRdt = $this->oForm->aORenderlets[$sKey])
						) {
							$bHide = FALSE;
							break;
						}
					}
					if ($bHide)
						return $bHide;
				}
			}
			if($bCheckParent && $this->hasParent()){
				return $this->getParent()->_shouldHideBecauseDependancyEmpty($bCheckParent);
			}
			return FALSE;
		}

		function _getStyleArray($aConf=FALSE, $sAddStyle='') {

			$sStyle = '';

			if(($mStyle = $this->_navConf('/style/', $aConf)) !== FALSE) {
				if($this->oForm->isRunneable($mStyle)) {
					$mStyle = $this->getForm()->getRunnable()->callRunnableWidget($this, $mStyle);
				}

				$sStyle = str_replace('"', "'", $mStyle);
			}

			if($this->isVisible() === FALSE || $this->_shouldHideBecauseDependancyEmpty()) {
				$sAddStyle .= 'display: none;';
			}

			$aStyles = $this->explodeStyle($sStyle);

			if(trim($sAddStyle) !== '') {
				$aStyles = array_merge(
					$aStyles,
					$this->explodeStyle($sAddStyle)
				);
			}

			reset($aStyles);
			return $aStyles;
		}

		function explodeStyle($sStyle) {

			$aStyles = array();

			if(trim($sStyle) !== '') {
				$aTemp = t3lib_div::trimExplode(';', $sStyle);
				reset($aTemp);
				while(list($sKey,) = each($aTemp)) {
					if(trim($aTemp[$sKey]) !== '') {
						$aStyleItem = t3lib_div::trimExplode(':', $aTemp[$sKey]);
						$aStyles[$aStyleItem[0]] = $aStyleItem[1];
					}
				}
			}

			reset($aStyles);
			return $aStyles;
		}

		function buildStyleProp($aStyles) {
			$aRes = array();

			reset($aStyles);
			while(list($sProp, $sVal) = each($aStyles)) {
				$aRes[] = $sProp . ': ' . $sVal;
			}

			reset($aRes);
			if(count($aRes) > 0) {
				return ' style="' . implode('; ', $aRes) . ';" ';
			}

			return '';
		}

		function _getStyle($aConf = FALSE, $sAddStyle = '') {

			$aStyles = $this->_getStyleArray($aConf = FALSE, $sAddStyle = '');
			return $this->buildStyleProp($aStyles);
		}

	/**
	 * Prüft die Option hideIf. Damit kann man Widgets ausblenden, wenn sie einen bestimmten Wert haben
	 *
	 * @param formidable_mainrenderlet $widget
	 * @return boolean
	 */
	protected function isHideIf($widget) {
		$val = $this->getForm()->getConfig()->get('/hideif', $this->aElement);
		if($val !== FALSE) {
			$cmpValue = $this->getForm()->getRunnable()->callRunnableWidget($this, $val);
			return $cmpValue == $widget->getValue();
		}
		return false;
	}

	function isVisible() {
		if (!($this->bVisible && !$this->isHideIf($this))) {
			return FALSE;
		}
		$visible = $this->_navConf('/visible');
		if($this->getForm()->isRunneable($visible)) {
			return $this->getForm()->getRunnable()->callRunnableWidget($this, $visible);
		}
		return $visible === FALSE ? TRUE : $this->_isTrueVal($visible);
	}

		function setVisible() {
			$this->bVisible = TRUE;
		}

		function setInvisible() {
			$this->bVisible = FALSE;
		}

		function isValueEmpty() {
			return is_array($mValue = $this->getValue()) ? empty($mValue) : trim($mValue) === '';
		}

		function isDataEmpty() {
			return $this->isValueEmpty();
		}

		function _getClassesArray($aConf = FALSE, $bIsRdt = TRUE) {
			$aClasses = array();

			if(($mClass = $this->_navConf('/class/', $aConf)) !== FALSE) {
				if($this->oForm->isRunneable($mClass)) {
					$mClass = $this->getForm()->getRunnable()->callRunnableWidget($this, $mClass);
				}

				if(is_string($mClass) && (trim($mClass) !== '')) {
					$aClasses = t3lib_div::trimExplode(' ', $mClass);
				}
			}

			if($bIsRdt === TRUE) {
				if($this->oForm->oRenderer->defaultFalse('autordtclass') === TRUE) {
					$aClasses[] = $this->getName();
				}

				if($this->hasError()) {
					$aError = $this->getError();
					$aClasses[] = 'hasError';
					$aClasses[] = 'hasError' . ucfirst($aError['info']['type']);
				}
			}

			reset($aClasses);
			return $aClasses;
		}

		function _getClasses($aConf = FALSE, $bIsRdt = TRUE, $sAdditional = '') {

			$aClasses = $this->_getClassesArray($aConf, $bIsRdt);

			if(strlen($sAdditional))
				$aClasses[] = $sAdditional;

			if(count($aClasses) === 0) {
				$sClassAttribute = '';
			} else {
				$sClassAttribute = ' class="' . implode(' ', $aClasses) . '" ';
			}

			return $sClassAttribute;
		}

		/*
			disabled-property for renderlets patch by Manuel Rego Casasnovas
			@see http://lists.netfielders.de/pipermail/typo3-project-formidable/2007-December/000803.html
		*/

		function _getDisabled() {

			if($this->_defaultFalse('/disabled/')) {
				return ' disabled="disabled" ';
			}

			return '';
		}

		function fetchServerEvents() {
			$aEvents = array();
			$aGrabbedEvents = $this->oForm->__getEventsInConf($this->aElement);
			reset($aGrabbedEvents);
			while(list(, $sEvent) = each($aGrabbedEvents)) {
				if(($mEvent = $this->_navConf('/' . $sEvent . '/')) !== FALSE) {
					if(is_array($mEvent)) {

						$sRunAt = trim(strtolower((array_key_exists('runat', $mEvent) && in_array($mEvent['runat'], array('inline', 'client', 'ajax', 'server'))) ? $mEvent['runat'] : 'client'));

						if(($iPos = strpos($sEvent, '-')) !== FALSE) {
							$sEventName = substr($sEvent, 0, $iPos);
						} else {
							$sEventName = $sEvent;
						}

						if($sRunAt === 'server') {
							$sEventId = $this->oForm->_getServerEventId(
								$this->getAbsName(),
								array($sEventName => $mEvent)
							);	// before any modif to get the *real* eventid

							$aNeededParams = array();

							if(array_key_exists('params', $mEvent)) {
								if(is_string($mEvent['params'])) {

									$aTemp = t3lib_div::trimExplode(',', $mEvent['params']);
									reset($aTemp);
									while(list($sKey,) = each($aTemp)) {
										$aNeededParams[] = array(
											'get' => $aTemp[$sKey],
											'as' => FALSE,
										);
									}
								} else {
									// the new syntax
									// <params><param get='uid' as='uid' /></params>
									$aNeededParams = $mEvent['params'];
								}
							}

							reset($aNeededParams);

							$sWhen = $this->oForm->_navConf('/when', $mEvent);
							if($sWhen === FALSE) {
								$sWhen = 'end';
							}

							tx_rnbase::load('tx_rnbase_util_Debug');
							if(!in_array($sWhen, $this->oForm->aAvailableCheckPoints)) {
								$this->oForm->mayday("SERVER EVENT on <b>" . $sEventName . " " . $this->getAbsName() . "</b>: defined checkpoint (when='" . $sWhen . "') does not exists; Available checkpoints are: <br /><br />" . tx_rnbase_util_Debug::viewArray($this->oForm->aAvailableCheckPoints));
							}

							$bEarlyBird = FALSE;

							if(array_search($sWhen, $this->oForm->aAvailableCheckPoints) < array_search('after-init-renderlets', $this->oForm->aAvailableCheckPoints)) {
								if($sWhen === 'start') {
									$bEarlyBird = TRUE;
								} else {
									$this->oForm->mayday("SERVER EVENT on <b>" . $sEventName . " " . $this->getAbsName() . "</b>: defined checkpoint (when='" . $sWhen . "') triggers too early in the execution to be catchable by a server event.<br />The first checkpoint available for server event is <b>after-init-renderlets</b>. <br /><br />The full list of checkpoints is: <br /><br />" . tx_rnbase_util_Debug::viewArray($this->oForm->aAvailableCheckPoints));
								}
							}

							$this->oForm->aServerEvents[$sEventId] = array(
								'name' => $this->getAbsName(),
								'eventid' => $sEventId,
								'trigger' => $sEventName,
								'when' => $sWhen,	// default when : end
								'event' => $mEvent,
								'params' => $aNeededParams,
								'raw' => array($sEventName => $mEvent),
								'earlybird' => $bEarlyBird,
							);
						}
					}
				}
			}
		}

		function _getEventsArray() {

			$aEvents = array();

			$aGrabbedEvents = $this->oForm->__getEventsInConf($this->aElement);

			reset($aGrabbedEvents);
			while(list(, $sEvent) = each($aGrabbedEvents)) {

				if(($mEvent = $this->_navConf('/' . $sEvent . '/')) !== FALSE) {

					if(is_array($mEvent)) {

						$sRunAt = (array_key_exists('runat', $mEvent) && in_array($mEvent['runat'], array('js', 'inline', 'client', 'ajax', 'server'))) ? $mEvent['runat'] : 'client';

						if(($iPos = strpos($sEvent, '-')) !== FALSE) {
							$sEventName = substr($sEvent, 0, $iPos);
						} else {
							$sEventName = $sEvent;
						}

						switch($sRunAt) {
							case 'server': {
								$sEventId = $this->oForm->_getServerEventId(
									$this->getAbsName(),
									array($sEventName => $mEvent)
								);

								$aTempListData = $this->oForm->oDataHandler->_getListData();

								$aEvent = $this->oForm->oRenderer->_getServerEvent(
									$this->getAbsName(),
									$mEvent,
									$sEventId,
									($aTempListData === FALSE ? array() : $aTempListData)
								);

								break;
							}
							case 'ajax': {

								$sEventId = $this->oForm->_getAjaxEventId(
									$this->getAbsName(),
									array($sEventName => $mEvent)
								);

								$aTemp = array(
									'name' => $this->getAbsName(),
									'eventid' => $sEventId,
									'trigger' => $sEventName,
									'cache' => $this->oForm->_defaultTrue('/cache', $mEvent),
									'event' => $mEvent,
								);

								if(!array_key_exists($sEventId, $this->oForm->aAjaxEvents)) {
									$this->oForm->aAjaxEvents[$sEventId] = $aTemp;
								}

								if($sEvent === 'onload') {
									if(!array_key_exists($sEventId, $this->oForm->aOnloadEvents['ajax'])) {
										$this->oForm->aOnloadEvents['ajax'][$sEventId] = $aTemp;
									}
								}


								if($this->oForm->_defaultFalse('/needparent', $mEvent) === TRUE) {
									$this->oForm->bStoreParentInSession = TRUE;
								}

								$aEvent = $this->oForm->oRenderer->_getAjaxEvent(
									$this,
									$mEvent,
									$sEventName
								);

								// an ajax event is declared
								// we have to store this form in session
								// for serving ajax requests

								$this->oForm->bStoreFormInSession = TRUE;

								$GLOBALS['_SESSION']['ameos_formidable']['ajax_services']['tx_ameosformidable']['ajaxevent'][$this->_getSessionDataHashKey()] = array(
									'requester' => array(
										'name' => 'tx_ameosformidable',
										'xpath' => '/',
									),
								);

								break;
							}
							case 'js': {

								if($this->oForm->isRunneable($mEvent)) {

									if($sEventName !== 'onload'){
										$aEvent = $this->getForm()->getRunnable()->callRunnableWidget($this, $mEvent);

										$aEvent = $this->oForm->oRenderer->_getClientEvent(
											$this->_getElementHtmlId(),
											$mEvent,
											$aEvent,
											$sEventName
										);
									} else {
										if($this->oForm->isRunneable($mEvent)) {
											$aEvent = $this->getForm()->getRunnable()->callRunnableWidget($this, $mEvent);
										} else {
											$aEvent = $mEvent;
										}

										$this->oForm->aOnloadEvents['client']['onload:' . $this->_getElementHtmlIdWithoutFormId()] = array(
											'name' => $this->_getElementHtmlId(),
											'event' => $mEvent,
											'eventdata' => $aEvent
										);
									}

								}
								break;
							}
							case 'client': {

								// array client mode event

								if($sEventName !== 'onload') {
									// Bei Client-Calls erlauben wir auch Parameter. Diese werden aus dem Event geholt
									$params = array();
									if(is_array($mEvent) && array_key_exists('params', $mEvent)) {
										$params = tx_mkforms_util_Div::extractParams($mEvent['params']);
									}
									if($this->oForm->isRunneable($mEvent)) {
										$aEvent = $this->getForm()->getRunnable()->callRunnableWidget($this, $mEvent, $params);

										if(is_array($aEvent)) {
											// event is an array of tasks to execute on js objects
											$aEvent = $this->oForm->oRenderer->_getClientEvent(
												$this->_getElementHtmlId(),
												$mEvent,
												$aEvent,
												$sEventName
											);
										} else {
											// event has been converted from userobj to custom string event
										}

									} else {
										if(array_key_exists('refresh', $mEvent)) {
											$aEvent = $this->_getEventRefresh($mEvent['refresh']);
										} elseif(array_key_exists('submit', $mEvent)) {
											$aEvent = $this->_getEventSubmit();
										}
									}
								} else {

									if($this->oForm->isRunneable($mEvent)) {
										$aEvent = $this->getForm()->getRunnable()->callRunnableWidget($this, $mEvent);
									} else {
										$aEvent = $mEvent;
									}

									$this->getForm()->aOnloadEvents['client']['onload:' . $this->_getElementHtmlIdWithoutFormId()] = array(
										'name' => $this->_getElementHtmlId(),
										'event' => $mEvent,
										'eventdata' => $aEvent
									);
								}
								break;
							}
							case 'inline': {

								if($this->oForm->isRunneable($mEvent)) {
									$aEvent = $this->getForm()->getRunnable()->callRunnableWidget($this, $mEvent);
								} else {
									$aEvent = $mEvent['__value'];
								}

								break;
							}
						}
					} else {

						$aEvent = $mEvent;
					}

					if($sEventName !== 'onload' && !$this->isCustomEventHandler($sEventName)) {

						if(!$this->oForm->isDomEventHandler($sEventName)) {
							$sEventName = 'formidable:' . $sEventName;
						}

						if(!array_key_exists($sEventName, $aEvents)) {
							$aEvents[$sEventName] = array();
						}

						$aEvents[$sEventName][] = $aEvent;
					} elseif($this->isCustomEventHandler($sEventName)) {

						$this->aCustomEvents[$sEventName][] = $aEvent;
					}
				}
			}

			if($this->aSkin && $this->skin_declaresHook('geteventsarray')) {

				$aEvents = $this->getForm()->getRunnable()->callRunnableWidget($this,
					$this->aSkin['submanifest']['hooks']['geteventsarray'],
					array(
						'object' => &$this,
						'events' => $aEvents
					)
				);
			}

			reset($aEvents);
			return $aEvents;
		}

		function alterAjaxEventParams($aParams) {
			return $aParams;
		}

		function isCustomEventHandler($sEvent) {
			return in_array(
				$sEvent,
				$this->aPossibleCustomEvents
			);
		}

		function skin_declaresHook($sHook) {
			return	$this->aSkin
					&& array_key_exists('hooks', $this->aSkin['submanifest'])
					&& array_key_exists($sHook, $this->aSkin['submanifest']['hooks'])
					&& $this->oForm->isRunneable($this->aSkin['submanifest']['hooks'][$sHook]);
		}

		function _getEvents() {

			$aHtml = array();
			$aEvents = $this->_getEventsArray();

			if(!empty($aEvents)) {
				reset($aEvents);
				while(list($sEvent, $aEvent) = each($aEvents)) {

					if($sEvent == 'custom') {
						$aHtml[] = implode(' ', $aEvent);
					} else {
						if($this->oForm->bInlineEvents === TRUE) {
							$aHtml[] = $sEvent . "='" . $this->oForm->oRenderer->wrapEventsForInlineJs($aEvent) . "'";
						} else {
							$this->attachEvents($sEvent, $aEvent);
						}
					}
				}
			}

			return ' ' . implode(' ', $aHtml) . ' ';
		}

		function attachEvents($sEvent, $aEvents) {
			$sEventHandler = strtolower(str_replace('on', '', $sEvent));
			$sFunction = implode(";\n", $aEvents);
			$sElementId = $this->_getElementHtmlId();

			if($sEventHandler === 'click' && $this->_getType() === 'LINK') {
				$sAppend = 'MKWrapper.stopEvent(event);';
			}

			$sEvents =<<<JAVASCRIPT
Formidable.f("{$this->oForm->formid}").attachEvent("{$sElementId}", "{$sEventHandler}", function(event) {{$sFunction};{$sAppend}});
JAVASCRIPT;

			if(tx_mkforms_util_Div::getEnvExecMode() === 'EID') {
				$this->oForm->aRdtEventsAjax[$sEvent . '-' . $sElementId] = $sEvents;
			} else {
				$this->oForm->aRdtEvents[$sEvent . '-' . $sElementId] = $sEvents;
			}
		}

		function attachCustomEvents() {

			$sHtmlId = $this->_getElementHtmlId();

			reset($this->aPossibleCustomEvents);
			while(list(, $sEvent) = each($this->aPossibleCustomEvents)) {
				if(array_key_exists($sEvent, $this->aCustomEvents)) {

					$sJs = implode("\n", $this->aCustomEvents[$sEvent]);
					$sScript =<<<JAVASCRIPT
Formidable.f("{$this->oForm->formid}").o("{$sHtmlId}").addHandler("{$sEvent}", function() {{$sJs}});
JAVASCRIPT;
					$this->oForm->attachPostInitTask($sScript);
				}
			}
		}

		function _getEventRefresh($mRefresh) {

			if(is_array($mRefresh)) {

				if(($mAction = $this->oForm->_navConf('/action', $mRefresh)) !== FALSE) {

					if($this->oForm->isRunneable($mAction)) {
						$mAction = $this->getForm()->getRunnable()->callRunnableWidget($this, $mAction);
					}
				}

				return $this->oForm->oRenderer->_getRefreshSubmitEvent(
					$this->oForm->_navConf('/formid', $mRefresh),
					$mAction
				);

			} elseif($this->oForm->_isTrueVal($mRefresh) || empty($mRefresh)) {
				return $this->oForm->oRenderer->_getRefreshSubmitEvent();
			}
		}

		function _getEventSubmit() {
			return $this->oForm->oRenderer->_getFullSubmitEvent();
		}

		/**
		 * Erzeugt einen eindeutigen String. Der wird wohl bei Ajax-Calls verwendet.
		 * @return unknown_type
		 */
		function _getSessionDataHashKey() {
			return $this->getForm()->_getSafeLock(
				$GLOBALS['TSFE']->id . '||' . $this->oForm->formid
			);
		}

		function forceItems($aItems) {
			$this->aForcedItems = $aItems;
		}

		function _getItems() {

			if($this->aForcedItems !== FALSE) {
				reset($this->aForcedItems);
				return $this->aForcedItems;
			}

			$elementname = $this->_getName();

			$aItems = array();
			$aXmlItems = array();
			$aUserItems = array();

			if(($bFromTCA = $this->_defaultFalse('/data/items/fromtca')) === TRUE) {
				t3lib_div::loadTCA($this->oForm->oDataHandler->tableName());
				if(($aItems = $this->oForm->_navConf('columns/' . $this->_getName() . '/config/items', $GLOBALS['TCA'][$this->oForm->oDataHandler->tableName()])) !== FALSE) {
					$aItems = $this->oForm->_tcaToRdtItems($aItems);
				}
			} else {

				$aXmlItems = $this->_navConf('/data/items/');

				if(!is_array($aXmlItems)) {
					$aXmlItems = array();
				}

				reset($aXmlItems);
				while(list($sKey, ) = each($aXmlItems)) {

					if($this->oForm->isRunneable($aXmlItems[$sKey]['caption'])) {
						$aXmlItems[$sKey]['caption'] = $this->getForm()->getRunnable()->callRunnableWidget($this,
							$aXmlItems[$sKey]['caption']
						);
					}

					if($this->oForm->isRunneable($aXmlItems[$sKey]['value'])) {
						$aXmlItems[$sKey]['value'] = $this->getForm()->getRunnable()->callRunnableWidget($this,
							$aXmlItems[$sKey]['value']
						);
					}

					if(array_key_exists('custom', $aXmlItems[$sKey])) {
						if($this->oForm->isRunneable($aXmlItems[$sKey]['custom'])) {
							$aXmlItems[$sKey]['custom'] = $this->getForm()->getRunnable()->callRunnableWidget($this,
								$aXmlItems[$sKey]['custom']
							);
						}
					}

					if(array_key_exists('labelcustom', $aXmlItems[$sKey])) {
						if($this->oForm->isRunneable($aXmlItems[$sKey]['labelcustom'])) {
							$aXmlItems[$sKey]['labelcustom'] = $this->getForm()->getRunnable()->callRunnableWidget($this,
								$aXmlItems[$sKey]['labelcustom']
							);
						}
					}

					$aXmlItems[$sKey]['caption'] = $this->oForm->getConfig()->getLLLabel($aXmlItems[$sKey]['caption']);
					$aXmlItems[$sKey]['value'] = $this->_substituteConstants($aXmlItems[$sKey]['value']);

				}

				reset($aXmlItems);
				$aUserItems = array();
				$aData = $this->_navConf('/data/');
				if($this->oForm->isRunneable($aData)) {
					// @TODO: iterating id mit übergeben $params['config']['iteratingid']
					$aUserItems = $this->getForm()->getRunnable()->callRunnableWidget($this, $aData);
				}

				$aDb = $this->_navConf('/db/');
				if (is_array($aDb)) {
					// Get database table
					if(($mTable = $this->_navConf('/db/table/')) !== FALSE) {
						if($this->oForm->isRunneable($mTable)) {
							$mTable = $this->getForm()->getRunnable()->callRunnableWidget($this, $mTable);
						}
					}

					// Get value field, otherwise uid will be used as value
					if(($mValueField = $this->_navConf('/db/value/')) !== FALSE) {
						if($this->oForm->isRunneable($mValueField)) {
							$mValueField = $this->getForm()->getRunnable()->callRunnableWidget($this, $mValueField);
						}
					} else {
						$mValueField = 'uid';
					}

					// Get where part
					if(($mWhere = $this->_navConf('/db/where/')) !== FALSE) {
						if($this->oForm->isRunneable($mWhere)) {
							$mWhere = $this->getForm()->getRunnable()->callRunnableWidget($this, $mWhere);
						}
					}

					if (($this->_defaultFalse('/db/static/') === TRUE) &&
						(t3lib_extMgm::isLoaded('static_info_tables'))) {
						// If it is a static table
						$aDbItems = $this->__getItemsStaticTable($mTable, $mValueField, $mWhere);
					} else {
						// Get caption field
						if(($mCaptionField = $this->_navConf('/db/caption/')) !== FALSE) {
							if($this->oForm->isRunneable($mCaptionField)) {
								$mCaptionField = $this->getForm()->getRunnable()->callRunnableWidget($this, $mCaptionField);
							}
						} else {
							if (($mCaptionField = $this->oForm->_navConf($mTable . '/ctrl/label', $GLOBALS['TCA'])) === FALSE) {
								$mCaptionField = 'uid';
							}
						}

						// Build the query with value and caption fields
						$sFields = $mValueField . ' as value, ' . $mCaptionField . ' as caption';

						// Get the items
						$aDbItems = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($sFields, $mTable, $mWhere, '', 'caption');
					}
				}

				$aItems = $this->_mergeItems($aXmlItems, $aUserItems);
				$aItems = $this->_mergeItems($aItems, $aDbItems);
			}

			if(!is_array($aItems)) {
				$aItems = array();
			}

			if(($mAddBlank = $this->_defaultFalseMixed('/addblank')) !== FALSE) {
				if($this->oForm->isRunneable($mAddBlank)) {
					$mAddBlank = $this->oForm->callRunnable($mAddBlank);
				}

				if($mAddBlank === TRUE) {
					$sCaption = '';
				} else {
					$sCaption = $this->getForm()->getConfig()->getLLLabel($mAddBlank);
				}

				if(($mBlankValue = $this->_defaultFalseMixed('/blankvalue')) === FALSE) {
					$mBlankValue = '';
				}

				array_unshift($aItems, array(
					'caption' => $sCaption,
					'value' => $mBlankValue,
				));
			}

			reset($aItems);
			return $aItems;
		}

		function _mergeItems($aXmlItems, $aUserItems) {

			if(!is_array($aXmlItems)) { $aXmlItems = array();}
			if(!is_array($aUserItems)) { $aUserItems = array();}

			$aItems = array_merge($aXmlItems, $aUserItems);

			if(is_array($aItems) && sizeof($aItems) > 0) {
				reset($aItems);
				return $aItems;
			}

			return array();
		}

		function _flatten($mData) {
			return $mData;
		}

		function _unFlatten($sData) {
			return $sData;
		}

		function _getHumanReadableValue($data) {
			return $data;
		}

		function _emptyFormValue($value) {
			if(is_array($value)) {
				return empty($value);
			} else {
				return (strlen(trim($value)) == 0);
			}
		}

		function _sqlSearchClause($sValue, $sFieldPrefix = '', $sFieldName = '', $bRec = TRUE) {

			$sTable = $this->oForm->oDataHandler->tableName();

			if($sFieldName === '') {
				$sName = $this->_getName();
			} else {
				$sName = $sFieldName;
			}

			$sSql = $sFieldPrefix . $sName . " LIKE '%" . $GLOBALS["TYPO3_DB"]->quoteStr($sValue, $sTable) . "%'";

			if($bRec === TRUE) {
				$sSql = $this->overrideSql(
					$sValue,
					$sFieldPrefix,
					$sName,
					$sSql
				);
			}

			return $sSql;
		}

		function overrideSql($sValue, $sFieldPrefix, $sFieldName, $sSql) {
			$sTable = $this->oForm->oDataHandler->tableName();

			if($sFieldName === '') {
				$sName = $this->_getName();
			} else {
				$sName = $sFieldName;
			}

			$aFields = array($sName);

			if(($aConf = $this->_navConf('/search/')) !== FALSE) {

				if(array_key_exists('onfields', $aConf)) {

					if($this->oForm->isRunneable($aConf['onfields'])) {
						$sOnFields = $this->getForm()->getRunnable()->callRunnableWidget($this, $aConf['onfields']);
					} else {
						$sOnFields = $aConf['onfields'];
					}

					$aFields = t3lib_div::trimExplode(',', $sOnFields);
					reset($aFields);
				} else {
					$aFields = array($sName);
				}

				if(array_key_exists('overridesql', $aConf)) {

					if($this->oForm->isRunneable($aConf['overridesql'])) {
						$aSql = array();
						reset($aFields);
						while(list(, $sField) = each($aFields)) {

							$aSql[] = $this->getForm()->getRunnable()->callRunnableWidget($this,
								$aConf['overridesql'],
								array(
									'name'		=> $sField,
									'table'		=> $sTable,
									'value'		=> $sValue,
									'prefix'	=> $sFieldPrefix,
									'defaultclause' => $this->_sqlSearchClause(
										$sValue,
										$sFieldPrefix,
										$sField,
										$bRec = FALSE
									),
								)
							);
						}

						if(!empty($aSql)) {
							$sSql = ' (' . implode(' OR ', $aSql) . ') ';
						}
					} else {
						$sSql = $aConf['overridesql'];
					}

					$sSql = str_replace('|', $sValue, $sSql);
				} else {

					if(array_key_exists('mode', $aConf)) {
						if((is_array($aConf['mode']) && array_key_exists('startswith', $aConf['mode'])) || $aConf['mode'] == 'startswith') {
							// on effectue la recherche sur le dbut des champs avec LIKE A%

							$sValue = trim($sValue);
							$aSql = array();

							reset($aFields);
							while(list(, $sField) = each($aFields)) {
								if($sValue != 'number') {
									$aSql[] = '(' . $sFieldPrefix . $sField . " LIKE '" . $GLOBALS['TYPO3_DB']->quoteStr($sValue, $sTable) . "%')";
								} else {
									for($k = 0; $k < 10; $k++) {
										$aSql[] = '(' . $sFieldPrefix . $sField . " LIKE '" . $GLOBALS['TYPO3_DB']->quoteStr($k, $sTable) . "%')";
									}
								}
							}

							if(!empty($aSql)) {
								$sSql = ' (' . implode(' OR ', $aSql) . ') ';
							}

						} elseif((is_array($aConf['mode']) && (array_key_exists('googlelike', $aConf['mode']) || array_key_exists('orlike', $aConf['mode']))) || $aConf['mode'] == 'googlelike' || $aConf['mode'] == 'orlike') {
							// on doit effectuer la recherche comme le ferait google :)
							// comportement : recherche AND sur "espaces", "+", ","
							//				: gestion des pluriels
							//				: recherche full text si "jj kjk jk"

							$sValue = str_replace(array(' ', ',', ' and ', ' And ', ' aNd ', ' anD ', ' AnD ', ' ANd ', ' aND ', ' AND ', ' et ', ' Et ', ' eT ', ' ET '), '+', trim($sValue));
							$aWords = t3lib_div::trimExplode('+', $sValue);

							if(is_array($aConf['mode']) && array_key_exists('handlepluriels', $aConf['mode'])) {
								reset($aWords);
								while(list($sKey, $sWord) = each($aWords)) {
									if(strtolower(substr($sWord, -1, 1)) === 's') {
										$aWords[$sKey] = substr($sWord, 0, (strlen($sWord) - 1));
									}
								}
							}

							$aSql = array();

							reset($aFields);
							while(list(, $sField) = each($aFields)) {

								$aTemp = array();

								reset($aWords);
								while(list($iKey, $sWord) = each($aWords)) {
									$aTemp[] = $sFieldPrefix . $sField . " LIKE '%" . $GLOBALS['TYPO3_DB']->quoteStr($sWord, $sTable) . "%' ";
								}

								if(!empty($aTemp)) {
									if((is_array($aConf['mode']) && array_key_exists('orlike', $aConf['mode'])) || $aConf['mode'] == 'orlike') {
										$aSql[] = '(' . implode(' OR ', $aTemp) . ')';
									} else {
										$aSql[] = '(' . implode(' AND ', $aTemp) . ')';
									}
								}
							}

							if(!empty($aSql)) {
								$sSql = ' (' . implode(' OR ', $aSql) . ') ';
							}
						} elseif((is_array($aConf['mode']) && array_key_exists('and', $aConf['mode'])) || strtoupper($aConf['mode']) == 'AND') {
							$sValue = trim($sValue);
							$aSql = array();

							reset($aFields);
							while(list(, $sField) = each($aFields)) {
								$aSql[] = $this->_sqlSearchClause(
									$sValue,
									$sFieldPrefix,
									$sField,
									$bRec = FALSE
								);
							}

							if(!empty($aSql)) {
								$sSql = ' (' . implode(' AND ', $aSql) . ') ';
							}
						} else {
							$this->oForm->mayday("renderlet:" . $this->_getType() . "[name=" . $this->getName() . "] - given /search/mode does not exist; should be one of 'startswith', 'googlelike', 'orlike'");
						}
					} else {	/* default mode */

						$sValue = trim($sValue);
						$aSql = array();

						reset($aFields);
						while(list(, $sField) = each($aFields)) {
							$aSql[] = $this->_sqlSearchClause(
								$sValue,
								$sFieldPrefix,
								$sField,
								$bRec = FALSE
							);
						}

						if(!empty($aSql)) {
							$sSql = ' (' . implode(' OR ', $aSql) . ') ';
						}
					}
				}
			}

			return $sSql;
		}

		function _renderOnly($bForAjax=false) {
			return $this->_isTrue('/renderonly/') || $this->i18n_shouldNotTranslate();
		}

		function hasData($bForAjax=false) {
			return $this->_defaultFalse('/hasdata') || ($this->_renderOnly($bForAjax) === FALSE);
		}

		function _activeListable() {		// listable as an active HTML FORM field or not in the lister
			return $this->_defaultFalse('/activelistable/');
		}

		function _listable() {
			return $this->_defaultTrue('/listable/');
		}

		function _translatable() {
			return $this->_defaultTrue('/i18n/translate/');
		}

		function i18n_shouldNotTranslate() {
			return		$this->oForm->oDataHandler->i18n()	// DH handles i18n ?
					&&	!$this->oForm->oDataHandler->i18n_currentRecordUsesDefaultLang()	// AND record is NOT in default language
					&&	!$this->_translatable();	// AND renderlet is NOT translatable
		}

		function _hideableIfNotTranslatable() {
			return $this->_defaultFalse('/i18n/hideifnottranslated');
		}

		function i18n_hideBecauseNotTranslated() {
			if($this->i18n_shouldNotTranslate()) {
				return $this->_hideableIfNotTranslatable();
			}

			return FALSE;
		}

		function _hasToValidateForDraft() {
			return $this->_defaultFalse('/validatefordraft/');
		}

		function _debugable() {
			return $this->_defaultTrue('/debugable/');
		}

		function _readOnly() {
			return ($this->_isTrue('/readonly/')) || $this->i18n_shouldNotTranslate();
		}

		function _searchable() {
			return $this->_defaultTrue('/searchable/');
		}

		function _virtual() {
			return in_array(
				$this->_getName(),
				$this->oForm->oDataHandler->__aVirCols
			);
		}

		// alias of _hasThrown(), for convenience
		function hasThrown($sEvent, $sWhen = FALSE) {
			return $this->_hasThrown($sEvent, $sWhen);
		}

		function _hasThrown($sEvent, $sWhen = FALSE) {

			$sEvent = strtolower($sEvent);
			if($sEvent{0} !== 'o' || $sEvent{1} !== 'n') {
				// events should always start with on
				$sEvent = 'on' . $sEvent;
			}

			if(array_key_exists($sEvent, $this->aElement) && array_key_exists('runat', $this->aElement[$sEvent]) && $this->aElement[$sEvent]['runat'] == 'server') {
				$aEvent = $this->aElement[$sEvent];
			} elseif(($aProgEvents = $this->_getProgServerEvents()) !== FALSE && array_key_exists($sEvent, $aProgEvents)) {
				$aEvent = $aProgEvents[$sEvent];
			} else {
				return FALSE;
			}

			if($sWhen === FALSE || $aEvent[$sEvent]['when'] == $sWhen) {

				$aP = $this->oForm->_getRawPost();

				if(array_key_exists('AMEOSFORMIDABLE_SERVEREVENT', $aP)) {
					if(array_key_exists($aP['AMEOSFORMIDABLE_SERVEREVENT'], $this->oForm->aServerEvents)) {
						$sEventId = $this->oForm->_getServerEventId(
							$this->getAbsName(),
							$this->oForm->aServerEvents[$aP['AMEOSFORMIDABLE_SERVEREVENT']]['raw']
						);

						return ($sEventId === $aP['AMEOSFORMIDABLE_SERVEREVENT']);
					}
				}
			}

			return FALSE;
		}

	/**
	 * Liefert den Namen der JS-Klasse des Widgets
	 * @return string
	 */
	protected function getMajixClass() {
		return $this->sMajixClass;
	}

	/**
	 * Liefert die JS-Dateien, die für ein Widget eingebunden werden sollen.
	 * @return array
	 */
	protected function getJSLibs() {
		return $this->aLibs;
	}
	/**
	 * Bindet die notwendigen JS-Dateien für ein Widget ein. Diese werden aus der Instanzvariablen aLibs gelesen.
	 */
	function includeLibs() {
		$aLibs = $this->getJSLibs();
		$oJsLoader = $this->getForm()->getJSLoader();
		if(!$oJsLoader->useJs() || empty($aLibs)) return;

		reset($aLibs);
		foreach($aLibs As $sKey => $sLib) {
			$this->getForm()->additionalHeaderData(
				'<script type="text/javascript" src="' . $oJsLoader->getScriptPath($this->sExtWebPath . $sLib) . '"></script>',
				$sKey
			);
		}
	}

	function includeScripts($aConfig = array()) {

		$sClass = $this->getMajixClass() ? $this->getMajixClass() : 'RdtBaseClass';
		$aChildsIds = array();

		if($this->mayHaveChilds() && $this->hasChilds()) {

			$aKeys = array_keys($this->aChilds);
			reset($aKeys);
			while(list(, $sKey) = each($aKeys)) {
				$aChildsIds[$sKey] = $this->aChilds[$sKey]->_getElementHtmlId();
			}
		}

		if($this->hasParent()) {
			$sParentId = $this->oRdtParent->_getElementHtmlId();
		} else {
			$sParentId = FALSE;
		}

		$sHtmlId = $this->_getElementHtmlId();
		$sJson = tx_mkforms_util_Json::getInstance()->encode(
			array_merge(
				array(
					'id' => $sHtmlId,
					'localname' => $this->getName(),
					'name' => $this->_getElementHtmlName(),
					'namewithoutformid' => $this->_getElementHtmlNameWithoutFormId(),
					'idwithoutformid' => $this->_getElementHtmlId(),
					'iteratingid' => strlen($this->getIteratingId()) ? $this->getIteratingId() : false,
					'formid' => $this->oForm->formid,
					'_rdts' => $aChildsIds,
					'parent' => $sParentId,
					'error' => $this->getError(),
					'abswebpath' => $this->sExtWebPath,
				),
				$aConfig
			)
		);

		$sScript = 'Formidable.Context.Forms["' . $this->oForm->formid . '"]' .
			'.Objects["' . $sHtmlId . '"] = new Formidable.Classes.' . $sClass .
			'(' .PHP_EOL . $sJson . PHP_EOL . ')';

		$this->getForm()->attachInitTask(
			$sScript,
			$sClass . ' ' . $sHtmlId . ' initialization',
			$sHtmlId
		);


		// attach post init script?
		if (!empty($this->sAttachPostInitTask)) {
			$this->getForm()->attachPostInitTask(
				'Formidable.f("' . $this->oForm->formid . '")' .
					'.o("' . $this->_getElementHtmlIdWithoutFormId() . '")' .
					'.' . $this->sAttachPostInitTask . '();' . PHP_EOL,
				'postinit ' . $sClass . ' ' . $sHtmlId . ' initialization',
				$this->_getElementHtmlId()
			);
		}

	}

		function mayHaveChilds() {
			return FALSE;
		}

		function hasChilds() {
			return isset($this->aElement['childs']);
		}

		function isChild() {
			return $this->bChild;
		}

		function mayBeDataBridge() {
			return FALSE;
		}

		function isDataBridge() {
			return $this->mayBeDataBridge() && $this->bIsDataBridge === TRUE;
		}

		function hasDataBridge() {
			return $this->bHasDataBridge;
		}

		function renderChildsBag() {

			$aRendered = array();

			if($this->mayHaveChilds() && $this->hasChilds()) {

				reset($this->aChilds);
				while(list($sName, ) = each($this->aChilds)) {
					$oRdt =& $this->aChilds[$sName];
					if($this->bForcedValue === TRUE && is_array($this->mForcedValue) && array_key_exists($sName, $this->mForcedValue)) {
						// parent may have childs
							// AND has forced value
							// AND value is a nested array of values
							// AND subvalue for current child exists in the data array
								// => forcing subvalue for this child

						// Prüfen, ob dieses Renderlet bereits eine ForcedValue hat,
						// wenn ja merken wir uns diese
						$mOldForceValue = $oRdt->bForcedValue ? $oRdt->mForcedValue : false;
						$oRdt->forceValue($this->mForcedValue[$sName]);
						$aRendered[$sName] = $this->oForm->_renderElement($oRdt);

						if($mOldForceValue == FALSE) {	// Das Renderlet hatte keine eigene ForcedValue
							$oRdt->unForceValue();
						} else { 						// Die eigene ForcedValue zurücksetzen
							$oRdt->forceValue($mOldForceValue);
						}
					} else {
						$aRendered[$sName] = $this->oForm->_renderElement($oRdt);
					}

				}
			}

			// adding prerendered renderlets in the html bag
			$sAbsName = $this->getAbsName();
			$sAbsPath = str_replace('.', '.childs.', $sAbsName);
			$sAbsPath = str_replace('.', '/', $sAbsPath);

			if(($mValue = $this->oForm->navDeepData($sAbsPath, $this->oForm->aPreRendered)) !== FALSE) {
				if(is_array($mValue) && array_key_exists('childs', $mValue)) {
					$aRendered = t3lib_div::array_merge_recursive_overrule(
						$aRendered,
						$mValue['childs']
					);
				}
			}

			reset($aRendered);
			return $aRendered;
		}

		function renderChildsCompiled($aChildsBag) {

			if(($this->_navConf('/childs/template/path')) !== FALSE) {
				// templating childs
					// mechanism:
					// childs can be templated if name of parent renderlet is present in template as a subpart marker
					// like for instance with renderlet:BOX name="mybox", subpart will be <!-- ###mybox### begin--> My childs here <!-- ###mybox### end-->

				$aTemplate = $this->_navConf('/childs/template');

				$sPath = $this->oForm->toServerPath($this->oForm->_navConf('/path', $aTemplate));

				if(!file_exists($sPath)) {
					$this->oForm->mayday("renderlet:" . $this->_getType() . "[name=" . $this->getName() . "] - The given template file path (<b>'" . $sPath . "'</b>) doesn't exists.");
				} elseif(is_dir($sPath)) {
					$this->oForm->mayday("renderlet:" . $this->_getType() . "[name=" . $this->getName() . "] - The given template file path (<b>'" . $sPath . "'</b>) is a directory, and should be a file.");
				} elseif(!is_readable($sPath)) {
					$this->oForm->mayday("renderlet:" . $this->_getType() . "[name=" . $this->getName() . "] - The given template file path exists but is not readable.");
				}

				if(($sSubpart = $this->oForm->_navConf('/subpart', $aTemplate)) === FALSE) {
					$sSubpart = $this->getName();
				}

				$mHtml = t3lib_parsehtml::getSubpart(
					t3lib_div::getUrl($sPath),
					$sSubpart
				);

				if(trim($mHtml) == '') {
					$this->oForm->mayday("renderlet:" . $this->_getType() . "[name=" . $this->getName() . "] - The given template (<b>'" . $sPath . "'</b> with subpart marquer <b>'" . $sSubpart . "'</b>) <b>returned an empty string</b> - Check your template");
				}


				return $this->oForm->getTemplateTool()->parseTemplateCode(
					$mHtml,
					$aChildsBag,
					array(),
					FALSE
				);
			} else {

				if($this->oForm->oRenderer->_getType() === 'TEMPLATE') {

					// child-template is not defined, but maybe is it implicitely the same as current template renderer ?
					if(($sSubpartName = $this->_navConf('/childs/template/subpart')) === FALSE) {
						$sSubpartName = $this->getName();
					}

					$sSubpartName = str_replace('#', '', $sSubpartName);

					$sSubpart = $this->oForm->oHtml->getSubpart(
						$this->oForm->oRenderer->getTemplateHtml(),
						'###' . $sSubpartName . '###'
					);

					$aTemplateErrors = array();
					$aCompiledErrors = array();
					$aDeepErrors = $this->getDeepErrorRelative();
					reset($aDeepErrors);
					while(list($sKey,) = each($aDeepErrors)) {

						$sTag = $this->oForm->oRenderer->wrapErrorMessage($aDeepErrors[$sKey]['message']);

						$aCompiledErrors[] = $sTag;

						$aTemplateErrors[$sKey] = $aDeepErrors[$sKey]['message'];
						$aTemplateErrors[$sKey . '.'] = array(
							'tag' => $sTag,
							'info' => $aDeepErrors[$sKey]['info'],
						);
					}

					$aChildsBag['errors'] = $aTemplateErrors;
					$aChildsBag['errors']['__compiled'] = $this->oForm->oRenderer->compileErrorMessages($aCompiledErrors);

					if(!empty($sSubpart)) {
						$sRes = $this->oForm->getTemplateTool()->parseTemplateCode(
							$sSubpart,
							$aChildsBag,
							array(),
							FALSE
						);

						return $sRes;
					}
				}

				$sCompiled = '';
				$bRenderErrors = $this->defaultTrue('/rendererrors');

				reset($aChildsBag);
				while(list($sName, $aBag) = each($aChildsBag)) {
					if($sName{0}=='e' && $sName=='errors' && !$bRenderErrors) continue;
					if(!$this->shouldAutowrap()) {
						$sCompiled .= "\n" . $aBag['__compiled'];
					} else {
						$sCompiled .= "\n<div class='".$this->getForm()->sDefaultWrapClass."-rdtwrap'>" . $aBag['__compiled'] . "</div>";
					}
				}

				return $sCompiled;
			}
		}

		function shouldAutowrap() {
			return $this->_defaultTrue('/childs/autowrap/');
		}


		function buildMajixExecuter($sMethod, $aData = array()) {
			return $this->oForm->buildMajixExecuter(
				$sMethod,
				$aData,
				$this->_getElementHtmlId()
			);
		}

		function majixDoNothing() {
			return $this->buildMajixExecuter('doNothing');
		}

		function majixDisplayBlock() {
			return $this->buildMajixExecuter('displayBlock');
		}

		function majixDisplayNone() {
			return $this->buildMajixExecuter('displayNone');
		}

		function majixDisplayDefault() {
			return $this->buildMajixExecuter('displayDefault');
		}

		function majixVisible() {
			return $this->buildMajixExecuter('visible');
		}

		function majixHidden() {
			return $this->buildMajixExecuter('hidden');
		}

		function majixDisable() {
			return $this->buildMajixExecuter('hidden');
		}

		function majixEnable() {
			return $this->buildMajixExecuter('enable');
		}

		function majixReplaceData($sData) {
			return $this->buildMajixExecuter(
				'replaceData',
				$sData
			);
		}

		function majixReplaceLabel($sLabel) {
			return $this->buildMajixExecuter(
				'replaceLabel',
				$this->oForm->getConfig()->getLLLabel($sLabel)
			);
		}

		function majixClearData() {
			return $this->buildMajixExecuter(
				'clearData'
			);
		}

		function majixClearValue() {
			return $this->buildMajixExecuter(
				'clearValue'
			);
		}

		function majixSetValue($sValue) {
			return $this->buildMajixExecuter(
				'setValue',
				$sValue
			);
		}

		function majixUserChanged($sValue) {
			return $this->buildMajixExecuter(
				'userChanged',
				$sValue
			);
		}

		function majixFx($sEffect, $aParams = array()) {
			return $this->buildMajixExecuter(
				'Fx',
				array(
					'effect' => $sEffect,
					'params' => $aParams,
				)
			);
		}

		function majixFocus() {
			return $this->buildMajixExecuter(
				'focus'
			);
		}

		function majixScrollTo() {
			return $this->oForm->majixScrollTo(
				$this->_getElementHtmlId()
			);
		}

		function majixSetErrorStatus($aError = array()) {
			return $this->buildMajixExecuter(
				'setErrorStatus',
				$aError
			);
		}

		function majixRemoveErrorStatus() {
			return $this->buildMajixExecuter(
				'removeErrorStatus'
			);
		}


		function majixSubmitSearch() {
			return $this->buildMajixExecuter(
				'triggerSubmit',
				'search'
			);
		}

		function majixSubmitFull() {
			return $this->buildMajixExecuter(
				'triggerSubmit',
				'full'
			);
		}

		function majixSubmitClear() {
			return $this->buildMajixExecuter(
				'triggerSubmit',
				'clear'
			);
		}

		function majixSubmitRefresh() {
			return $this->buildMajixExecuter(
				'triggerSubmit',
				'refresh'
			);
		}

		function majixSubmitDraft() {
			return $this->buildMajixExecuter(
				'triggerSubmit',
				'draft'
			);
		}


		function skin_init($sMode) {

			if(($aSkin = $this->_navConf('/skin')) !== FALSE) {

				if(($aManifest = $this->skin_getManifest($aSkin)) !== FALSE) {

					reset($aManifest);
					if(array_key_exists($this->aObjectType['OBJECT'], $aManifest['skin'])) {

						reset($aManifest['skin'][$this->aObjectType['OBJECT']]);
						while(list(, $aSubManifest) = each($aManifest['skin'][$this->aObjectType['OBJECT']])) {
							if($aSubManifest['type'] == $this->aObjectType['TYPE']) {

								$aModes = t3lib_div::trimExplode(',', $aSubManifest['modes']);
								if(in_array($sMode, $aModes)) {

									$this->aSkin = array(
										'declaredskin'	=> $aSkin,
										'manifest'		=> $aManifest,
										'submanifest'	=> $aSubManifest,
										'mode'			=> $sMode,
										'template'		=> array(
											'full'		=> '',
											'compiled'	=> '',
											'channels'	=> array(),
										)
									);

									// getting template and channels
									if(array_key_exists('template', $this->aSkin['submanifest']['resources'])) {
										$sSrc = $this->aSkin['manifest']['control']['serverpath'] . tx_mkforms_util_Div::removeStartingSlash($this->aSkin['submanifest']['resources']['template']['file']['src']);
										if(file_exists($sSrc) && is_readable($sSrc)) {



											$this->aSkin['template']['full'] = t3lib_parsehtml::getSubpart(
												t3lib_div::getUrl($sSrc),
												$this->aSkin['submanifest']['resources']['template']['subpart']
											);

											if(($aChannels = $this->oForm->_navConf('/channels', $this->aSkin['submanifest']['resources']['template'])) !== FALSE) {
												reset($aChannels);
												while(list(, $aChannel) = each($aChannels)) {

													$this->aSkin['template']['channels'][$aChannel['name']] = $this->oForm->getTemplateTool()->parseTemplateCode(
														t3lib_parsehtml::getSubpart(
															$this->aSkin['template']['full'],
															'###CHANNEL:' . $aChannel['name'] . '###'
														),	// HTML code
														$this->aSkin['template']['channels'],	// substitute tags
														array(),	// exclude tags
														FALSE		// don't clean non replaced {tags}
													);
												}

												$this->aSkin['template']['compiled'] = $this->oForm->getTemplateTool()->parseTemplateCode(
													t3lib_parsehtml::getSubpart(
														$this->aSkin['template']['full'],
														'###COMPILED###'
													),
													$this->aSkin['template']['channels'],
													array(),
													FALSE
												);
											}
										}
									}

									break;
								}
							}
						}
					}
				}
			}
		}

		function skin_apply($aHtmlBag, $aDefaultHtmlBag) {

			if($this->aSkin !== FALSE) {

				$this->skin_includeCss(
					$this->aSkin['declaredskin'],
					$this->aSkin['manifest'],
					$this->aSkin['submanifest'],
					$aSkinFeed,
					$this->aSkin['sMode']
				);

				// applying template

				if(!empty($this->aSkin['template']['channels'])) {

					reset($this->aSkin['template']['channels']);
					while(list($sName, ) = each($this->aSkin['template']['channels'])) {
						$aHtmlBag[$sName] = $this->oForm->getTemplateTool()->parseTemplateCode(
							$this->aSkin['template']['channels'][$sName],
							$aHtmlBag,
							array(),
							FALSE
						);
					}

					$aHtmlBag['__compiled'] = $this->oForm->getTemplateTool()->parseTemplateCode(
						$this->aSkin['template']['compiled'],
						$aHtmlBag,
						array(),
						FALSE
					);
				}

				reset($aHtmlBag);
				return $aHtmlBag;
			} else {

				reset($aDefaultHtmlBag);
				while(list($sName, ) = each($aDefaultHtmlBag)) {
					$aDefaultHtmlBag[$sName] = $this->oForm->getTemplateTool()->parseTemplateCode(
						$aDefaultHtmlBag[$sName],
						array_merge($aHtmlBag, $aDefaultHtmlBag),
						array(),
						FALSE
					);
				}

				return array_merge($aHtmlBag, $aDefaultHtmlBag);
			}
		}

		function skin_getManifest($aSkin) {

			if(($sSrc = $this->oForm->_navConf('/src', $aSkin)) !== FALSE) {

				$sHash = md5($sSrc);

				if(!array_key_exists($sHash, $this->oForm->aSkinManifests)) {
					$sDir = $this->oForm->toServerPath($sSrc);
					$sPath = $sDir . 'manifest.xml';

					if(file_exists($sPath) && is_readable($sPath)) {
						tx_rnbase::load('tx_mkforms_util_XMLParser');
						$this->oForm->aSkinManifests[$sHash] = tx_mkforms_util_XMLParser::getXml($sPath, $isSubXml, $bPlain);
						if(array_key_exists('skin', $this->oForm->aSkinManifests[$sHash])) {

							$this->oForm->aSkinManifests[$sHash]['control'] = array(
								'serverpath'	=> $sDir,
								'webpath'		=> $this->oForm->toWebPath($sDir),
								'manifest.xml'	=> $sPath,
							);

							return $this->oForm->aSkinManifests[$sHash];
						}
					}
				} else {
					return $this->oForm->aSkinManifests[$sHash];
				}
			}

			return FALSE;
		}

		function skin_includeCss($aSkinDeclaration, $aManifest, $aObjectManifest, $aSkinFeed, $sMode) {

			if(($aCssFiles = $this->oForm->_navConf('/resources/css/', $aObjectManifest)) !== FALSE) {
				reset($aCssFiles);
				while(list(, $aCssFile) = each($aCssFiles)) {

					$sCssPath = $aManifest['control']['webpath'] . tx_mkforms_util_Div::removeStartingSlash($aCssFile['src']);
					$sCssTag = '<link rel="stylesheet" type="text/css" media="all" href="' . $sCssPath . '" />';

					if(array_key_exists('wrap', $aCssFile)) {
						$sCssTag = str_replace('|', $sCssTag, $aCssFile['wrap']);
					}

					$this->oForm->additionalHeaderData(
						$sCssTag,
						md5($sCssPath)
					);
				}
			}
		}

		function defaultWrap() {
			return $this->_defaultTrue('/defaultwrap');
		}

		function hideIfJs() {
			return $this->_defaultFalse('/hideifjs');
		}

		function displayOnlyIfJs() {
			return $this->_defaultFalse('/displayonlyifjs');
		}

		function baseCleanBeforeSession() {

			$sThisAbsName = $this->getAbsName();	// keep it before being unable to calculate it

			if($this->hasChilds() && isset($this->aChilds) && is_array($this->aChilds)) {
				$aChildKeys = array_keys($this->aChilds);
				reset($aChildKeys);
				while(list(, $sKey) = each($aChildKeys)) {
					$this->aChilds[$sKey]->cleanBeforeSession();
				}
			}

			if($this->hasParent()) {
				$this->sRdtParent = $this->oRdtParent->getAbsName();
				unset($this->oRdtParent);	// TODO: reconstruct ajax-side
				$this->oRdtParent = FALSE;
			}

			if($this->isDataBridge()) {
				$aKeys = array_keys($this->aDataBridged);
				reset($aKeys);
				while(list(, $sKey) = each($aKeys)) {
					$sAbsName = $this->aDataBridged[$sKey];
					if(array_key_exists($sAbsName, $this->oForm->aORenderlets)) {
						$this->oForm->aORenderlets[$sAbsName]->sDataBridge = $sThisAbsName;
						unset($this->oForm->aORenderlets[$sAbsName]->oDataBridge);
						$this->oForm->aORenderlets[$sAbsName]->oDataBridge = FALSE;
					}
				}

				$this->sDataSource = $this->oDataSource->getName();
				unset($this->oDataSource);
				$this->oDataSource = FALSE;
			}

			unset($this->aStatics);
			$this->aStatics = $this->aEmptyStatics;
			$this->aCustomEvents = array();
		}

		function awakeInSession(&$oForm) {
			$this->oForm =& $oForm;

			if($this->sRdtParent !== FALSE) {
				$this->oRdtParent =& $this->oForm->aORenderlets[$this->sRdtParent];
				$this->sRdtParent = FALSE;
			}

			if($this->sDataSource !== FALSE) {
				$this->oDataSource =& $this->oForm->aODataSources[$this->sDataSource];
				$this->sDataSource = FALSE;
			}

			if($this->sDataBridge !== FALSE) {
				$this->oDataBridge =& $this->oForm->aORenderlets[$this->sDataBridge];
				$this->sDataBridge = FALSE;
			}
		}

		function hasSubmitted($sFormId = FALSE, $sAbsName = FALSE) {

			/*	algorithm:
				if isNaturalSubmitter()
					=> TRUE
					natural submitters are posting their value when submitting
					so we have to check for this value in the returned data array
				else if form is submitted and the submitterId == this renderletId
					=> TRUE
					every other renderlet might submit using a javascript submit event
					during the javascript processing, the submitter id is stored in the hidden field AMEOSFORMIDABLE_SUBMITTER
					right before the submit of the form
					so we may just check if the posted id corresponds to this renderlet id
			*/

			$bRes = FALSE;

			$aSubmitValues = array(
				AMEOSFORMIDABLE_EVENT_SUBMIT_FULL,
				AMEOSFORMIDABLE_EVENT_SUBMIT_REFRESH,
				AMEOSFORMIDABLE_EVENT_SUBMIT_TEST,
				AMEOSFORMIDABLE_EVENT_SUBMIT_DRAFT,
				AMEOSFORMIDABLE_EVENT_SUBMIT_CLEAR,
				AMEOSFORMIDABLE_EVENT_SUBMIT_SEARCH,
			);

			$mPostValue = $this->getRawPostValue($sFormId, $sAbsName);

			if($sFormId === FALSE && $sAbsName === FALSE) {
				$sElementHtmlId = $this->_getElementHtmlId();
				if(array_key_exists($sElementHtmlId, $this->aStatics['hasSubmitted'])) {
					return $this->aStatics['hasSubmitted'][$sElementHtmlId];
				}
			}


			if($this->maySubmit() && $this->isNaturalSubmitter()) {
				// handling the special case of natural submitter for accessibility reasons
				if($mPostValue !== FALSE) {
					$bRes = TRUE;
				}
			} else {
				if($this->oForm->oDataHandler->_isSubmitted($sFormId)) {
					$sSubmitter = $this->oForm->oDataHandler->getSubmitter($sFormId);
					if($sSubmitter === $this->_getElementHtmlIdWithoutFormId()) {
						$bRes = TRUE;
					}
				}
			}

			if($sFormId === FALSE && $sAbsName === FALSE) {
				$this->aStatics['hasSubmitted'][$sElementHtmlId] = $bRes;
			}

			return $bRes;
		}

		function getRawPostValue($sFormId = FALSE, $sAbsName = FALSE) {

			if($sFormId === FALSE) {
				$sFormId = $this->oForm->formid;
				if($sAbsName === FALSE) {
					$sDataId = $this->_getElementHtmlIdWithoutFormId();
				} else {
					$sDataId = $this->oForm->aORenderlets[$sAbsName]->_getElementHtmlIdWithoutFormId();
				}
			} else {
				$sDataId = $sAbsName;
			}

			if(!array_key_exists($sDataId, $this->aStatics['rawpostvalue'])) {
				$this->aStatics['rawpostvalue'][$sDataId] = FALSE;
				$aP = $this->oForm->_getRawPost($sFormId);
				$sAbsPath = str_replace('.', '/', $sDataId);

				if(($mData = $this->oForm->navDeepData($sAbsPath, $aP)) !== FALSE) {
					$this->aStatics['rawpostvalue'][$sDataId] = $mData;
				}
			}

			return $this->aStatics['rawpostvalue'][$sDataId];
		}

		function wrap($sHtml) {
			if(($mWrap = $this->_navConf('/wrap')) !== FALSE) {
				if($this->oForm->isRunneable($mWrap)) {
					$mWrap = $this->getForm()->getRunnable()->callRunnableWidget($this, $mWrap);
				}

				return $this->getForm()->getCObj()->noTrimWrap($sHtml, $mWrap);
			}

			return $sHtml;
		}

		function getFalse() {
			return FALSE;
		}

		function getTrue() {
			return TRUE;
		}

		/**
		 * Legt fest, ob das Widget verarbeitet wird. Wenn false wird es komplett ignoriert
		 * @return boolean
		 */
		function shouldProcess() {

			$mProcess = $this->_navConf('/process');

			if($mProcess !== FALSE) {
				if($this->oForm->isRunneable($mProcess)) {

					$mProcess = $this->getForm()->getRunnable()->callRunnableWidget($this, $mProcess);

					if($mProcess === FALSE) {
						return FALSE;
					}
				} elseif($this->oForm->_isFalseVal($mProcess)) {
					return FALSE;
				}
				// Soll die dependsOn Konfiguration genutzt werden?
				if(strtolower($mProcess) == 'dependson') {
					if($this->_shouldHideBecauseDependancyEmpty()) {
						return FALSE;
					}
				}
			}

			$aUnProcessMap = $this->oForm->_navConf('/control/factorize/switchprocess');
			if($this->oForm->isRunneable($aUnProcessMap)) {
				$aUnProcessMap = $this->getForm()->getRunnable()->callRunnableWidget($this, $aUnProcessMap);
			}

			if(is_array($aUnProcessMap) && array_key_exists($this->_getName(), $aUnProcessMap)) {
				return $aUnProcessMap[$this->_getName()];
			}

			return TRUE;
		}

		function handleAjaxRequest(&$oRequest) {
			/* specialize me */
		}

		function setParent(&$oParent) {
			$this->oRdtParent =& $oParent;
		}

		function addCssClass($sNewClass) {

			if(($sClass = $this->_navConf('/class')) !== FALSE) {
				$sClass = trim($sClass);
				$aClasses = t3lib_div::trimExplode(' ', $sClass);
			} else {
				$aClasses = array();
			}

			$aClasses[] = $sNewClass;
			$this->aElement['class'] = implode(' ', array_unique($aClasses));
		}

		function filterUnProcessed() {

			if($this->mayHaveChilds() && $this->hasChilds()) {

				if(isset($this->aChilds)) {
					$aChildKeys = array_keys($this->aChilds);
					reset($aChildKeys);
					while(list(, $sChildName) = each($aChildKeys)) {
						$this->aChilds[$sChildName]->filterUnProcessed();
					}
				}

				if(isset($this->aOColumns)) {
					$aChildKeys = array_keys($this->aOColumns);
					reset($aChildKeys);
					while(list(, $sChildName) = each($aChildKeys)) {
						$this->aOColumns[$sChildName]->filterUnProcessed();
					}
				}
			}

			if($this->shouldProcess() === FALSE) {
				$this->unsetRdt();
			}
		}
		/**
		 * Unsets the rdt corresponding to the given name
		 * Also unsets it's childs if any, and it's validators-errors if any
		 *
		 * @param	string		$sName: ...
		 * @return	void
		 */
		function unsetRdt() {


			if($this->mayHaveChilds() && $this->hasChilds()) {

				if(isset($this->aChilds)) {
					$aChildKeys = array_keys($this->aChilds);
					reset($aChildKeys);
					while(list(, $sChildName) = each($aChildKeys)) {
						$this->aChilds[$sChildName]->unsetRdt();
						unset($this->aChilds[$sChildName]);
					}
				}

				if(isset($this->aOColumns)) {
					$aChildKeys = array_keys($this->aOColumns);
					reset($aChildKeys);
					while(list(, $sChildName) = each($aChildKeys)) {
						$this->aOColumns[$sChildName]->unsetRdt();
						unset($this->aOColumns[$sChildName]);
					}
				}

			}

			if($this->hasDataBridge()) {
				# if the renderlet is registered in a databridge, we have to remove it
				$iKey = array_search($this->getAbsName(), $this->oDataBridge->aDataBridged);
				unset($this->oDataBridge->aDataBridged[$iKey]);
			}

			// unsetting events
				// onload events
			$sName = $this->getAbsName();

			$aAjaxOnloadEventsKeys = array_keys($this->oForm->aOnloadEvents['ajax']);
			while(list(, $sKey) = each($aAjaxOnloadEventsKeys)) {
				if($this->oForm->aOnloadEvents['ajax'][$sKey]['name'] === $sName) {
					unset($this->oForm->aOnloadEvents['ajax'][$sKey]);
				}
			}

			$this->cancelError();

			if($this->hasParent()) {
				unset($this->oRdtParent->aChilds[$this->getName()]);
			}

			unset($this->oForm->aORenderlets[$sName]);
			unset($this->oForm->oDataHandler->__aFormData[$sName]);
			unset($this->oForm->oDataHandler->__aFormDataManaged[$sName]);

			$sDeepPath = str_replace('.', '.childs.', $sName);
			$sDeepPath = str_replace('.', '/', $sDeepPath);
			$this->oForm->setDeepData(
				$sDeepPath,
				$this->oForm->aPreRendered,
				array(),
				TRUE	// $bMergeIfArray
			);
		}

		function majixRepaint() {
			$aHtmlBag = $this->render();

			return $this->buildMajixExecuter(
				'repaint',
				$aHtmlBag['__compiled']
			);
		}

		function majixRepaintInner() {

			$aHtmlBag = $this->render();
			$sHtml = '';
			foreach($aHtmlBag['childs'] as $child)
				$sHtml .= $child['__compiled'];

			return $this->buildMajixExecuter(
				'repaintInner',
				$sHtml
			);
		}

		function majixRemove() {
			return $this->buildMajixExecuter(
				'remove'
			);
		}

		function hasDependants() {
			return (count($this->aDependants) > 0);
		}

		function hasDependancies() {
			return (count($this->aDependsOn) > 0);
		}
		/**
		 * Hier werden die abhängigen Widgets informiert, daß sich der Wert des aktuellen Widgets geändert hat.
		 * Von den Widgets wird dann refreshValue() und majixRepaint() aufgerufen.
		 * Der Rest sind rekursive Aufrufe für die Kinder und weitere abhängige Widgets.
		 * @param $aTasks
		 * @return array
		 */
		function majixRepaintDependancies($aTasks = FALSE) {

			if($aTasks !== FALSE) {
				// this is a php-hack to allow optional yet passed-by-ref arguments
				$aTasks =& $aTasks[0];
			}

			if(!is_array($aTasks)) {
				$aTasks = array();
			}
			if($this->hasDependants()) {
				reset($this->aDependants);
				while(list(, $sAbsName) = each($this->aDependants)) {

					$widget = $this->getForm()->getWidget($sAbsName);
					if(is_object($widget)){
					    $widget->refreshValue();
					    $widget->setIteratingId( $this->getIteratingId() );
					    $aTasks[] = $widget->majixRepaint();

					    if($widget->hasDependants()) {
						    // Rekursion, falls das Widget ebenfalls abhängige Widgets hat
						    $widget->majixRepaintDependancies(array(&$aTasks));
					    }

					    if($widget->hasChilds()) {
						    $aChildKeys = array_keys($widget->aChilds);
						    reset($aChildKeys);
						    while(list(, $sChild) = each($aChildKeys)) {
							    $widget->aChilds[$sChild]->majixRepaintDependancies(array(&$aTasks));
						    }
					    }
					    $widget->setIteratingId();
					}
				}
			}
			reset($aTasks);
			return $aTasks;
		}

		function processDataBridge() {
			if($this->mayHaveChilds() && $this->hasChilds()) {

				if(isset($this->aChilds)) {
					$aChildKeys = array_keys($this->aChilds);
					reset($aChildKeys);
					while(list(, $sChildName) = each($aChildKeys)) {

						if($this->aChilds[$sChildName]->_isSubmittedForValidation()) {
							$this->aChilds[$sChildName]->validate();
						}

						$this->aChilds[$sChildName]->processDataBridge();
					}
				}

				if(isset($this->aOColumns)) {
					$aChildKeys = array_keys($this->aOColumns);
					reset($aChildKeys);
					while(list(, $sChildName) = each($aChildKeys)) {

						if($this->aOColumns[$sChildName]->_isSubmittedForValidation()) {
							$this->aOColumns[$sChildName]->validate();
						}

						$this->aOColumns[$sChildName]->processDataBridge();
					}
				}
			}

			if($this->isDataBridge() && $this->oDataSource->writable() && $this->dbridge_isFullySubmitted()) {
				if($this->dbridge_allIsValid()) {
					$sSignature = $this->dbridge_getCurrentDsetSignature();

					$aKeys = array_keys($this->aDataBridged);
					reset($aKeys);
					while(list(, $iKey) = each($aKeys)) {
						$sAbsName = $this->aDataBridged[$iKey];
						if($sAbsName === FALSE || (!$this->oForm->aORenderlets[$sAbsName]->_renderOnly() && !$this->oForm->aORenderlets[$sAbsName]->_readOnly())) {

							$sMappedPath = $this->dbridge_mapPath($sAbsName);

							if($sMappedPath !== FALSE) {
								$this->oDataSource->dset_setCellValue(
									$sSignature,
									$sMappedPath,
									$this->oForm->aORenderlets[$sAbsName]->getValue(),
									$sAbsName
								);
							}
						}
					}

					$this->oDataSource->dset_writeDataSet($sSignature);
				}
			}
		}

		function dbridge_allIsValid() {
			$bValid = TRUE;

			if($this->isDataBridge()) {
				$sThisAbsName = $this->getAbsName();
				$aErrorKeys = array_keys($this->oForm->_aValidationErrors);
				reset($aErrorKeys);
				while($bValid && list(, $sAbsName) = each($aErrorKeys)) {
					if(array_key_exists($sAbsName, $this->oForm->aORenderlets) && $this->oForm->aORenderlets[$sAbsName]->isDescendantOf($sThisAbsName)) {
						$bValid = FALSE;
					}
				}
			}

			return $bValid;
		}

		function dbridge_getRdtValueInDataSource($sAbsName) {
			$sRelName = $this->oForm->aORenderlets[$sAbsName]->getNameRelativeTo($this);
			$sPath = str_replace('.', '/', $sRelName);

			$sSignature = $this->dbridge_getCurrentDsetSignature();
			if(($mData = $this->oForm->navDeepData($sPath, $this->oDataSource->aODataSets[$sSignature]->getData())) !== FALSE) {
				return $mData;
			}

			return '';
		}

		function dbridge_getSubmitterAbsName() {
			if($this->aStatics['dbridge_getSubmitterAbsName'] !== AMEOSFORMIDABLE_VALUE_NOT_SET) {
				return $this->aStatics['dbridge_getSubmitterAbsName'];
			}

			$aKeys = array_keys($this->aDataBridged);
			reset($aKeys);
			while(list(, $iKey) = each($aKeys)) {
				$sAbsName = $this->aDataBridged[$iKey];

				if($this->oForm->aORenderlets[$sAbsName]->hasSubmitted()) {
					$this->aStatics['dbridge_getSubmitterAbsName'] = $sAbsName;
					return $sAbsName;
				}
			}

			$this->aStatics['dbridge_getSubmitterAbsName'] = FALSE;
			return FALSE;
		}

		function dbridge_globalSubmitable() {
			return $this->_defaultFalse('/datasource/globalsubmit');
		}

		function dbridge_isSubmitted() {
			if(($this->dbridge_getSubmitterAbsName() !== FALSE) || $this->dbridge_globalSubmitable()) {
				return $this->oForm->oDataHandler->_isSubmitted();
			}

			return FALSE;
		}

		function dbridge_isClearSubmitted() {
			if(($this->dbridge_getSubmitterAbsName() !== FALSE) || $this->dbridge_globalSubmitable()) {
				return $this->oForm->oDataHandler->_isClearSubmitted();
			}

			return FALSE;
		}

		function dbridge_isFullySubmitted() {
			if(($this->dbridge_getSubmitterAbsName() !== FALSE) || $this->dbridge_globalSubmitable()) {
				return $this->oForm->oDataHandler->_isFullySubmitted();
			}

			return FALSE;
		}

		function dbridge_mapPath($sAbsName) {
			# first, see if a mapping has been explicitely set on the renderlet
			if(($sPath = $this->oForm->aORenderlets[$sAbsName]->_navConf('/map')) !== FALSE) {
				if($this->oForm->isRunneable($sPath)) {
					$sPath = $this->getForm()->getRunnable()->callRunnableWidget($this, $sPath);
				}

				if($sPath !== FALSE) {
					return $sPath;
				}
			}

			# then, see if a mapping has been set in the databridge-level /mapping property
			if(($aMapping = $this->dbridge_getMapping()) !== FALSE) {
				$sRelName = $this->oForm->aORenderlets[$sAbsName]->dbridged_getNameRelativeToDbridge();

				$aKeys = array_keys($aMapping);
				reset($aKeys);
				while(list(, $iKey) = each($aKeys)) {
					if($aMapping[$iKey]['rdt'] === $sRelName) {
						$sPath = $aMapping[$iKey]['data'];
						return str_replace('.', '/', $sPath);
					}
				}
			}

			# finaly, we give a try to the automapping feature
			return $this->oDataSource->dset_mapPath(
				$this->dbridge_getCurrentDsetSignature(),
				$this,
				$sAbsName
			);
		}

		function dbridged_mapPath() {
			return $this->oDataBridge->dbridge_mapPath($this->getAbsName());
		}

		function dbridge_getMapping() {
			if($this->aStatics['dsetMapping'] === AMEOSFORMIDABLE_VALUE_NOT_SET) {
				if(($aMapping = $this->_navConf('/datasource/mapping')) !== FALSE) {
					if($this->oForm->isRunneable($aMapping)) {
						$aMapping = $this->getForm()->getRunnable()->callRunnableWidget($this, $aMapping);
					}

					if(is_array($aMapping)) {
						$this->aStatics['dsetMapping'] = $aMapping;
						reset($this->aStatics['dsetMapping']);
					} else {
						$this->aStatics['dsetMapping'] = FALSE;
					}
				} else {
					$this->aStatics['dsetMapping'] = FALSE;
				}
			}

			return $this->aStatics['dsetMapping'];
		}

		function _isSubmittedForValidation() {
			return $this->_isSubmitted() && (
				$this->_isFullySubmitted() ||
				$this->_isTestSubmitted()
			);
		}

		function _isSubmitted() {

			if($this->isDataBridge()) {
				return $this->dbridge_isSubmitted();
			}

			if($this->hasDataBridge()) {
				return $this->oDataBridge->dbridge_isSubmitted();
			}

			return $this->oForm->oDataHandler->_isSubmitted();
		}

		function _isClearSubmitted() {

			if($this->isDataBridge()) {
				return $this->dbridge_isClearSubmitted();
			}

			if($this->hasDataBridge()) {
				return $this->oDataBridge->dbridge_isClearSubmitted();
			}

			return $this->getForm()->getDataHandler()->_isClearSubmitted();
		}

		function _isFullySubmitted() {
			if($this->isDataBridge()) {
				return $this->dbridge_isFullySubmitted();
			}

			if($this->hasDataBridge()) {
				return $this->oDataBridge->dbridge_isFullySubmitted();
			}

			return $this->oForm->oDataHandler->_isFullySubmitted();
		}

		function _isRefreshSubmitted() {
			if(!$this->hasDataBridge() || ($this->oDataBridge->dbridge_getSubmitterAbsName() !== FALSE) || $this->oDataBridge->dbridge_globalSubmitable()) {
				return $this->oForm->oDataHandler->_isRefreshSubmitted();
			}

			return FALSE;
		}

		function _isTestSubmitted() {
			if(!$this->hasDataBridge() || ($this->oDataBridge->dbridge_getSubmitterAbsName() !== FALSE) || $this->oDataBridge->dbridge_globalSubmitable()) {
				return $this->oForm->oDataHandler->_isTestSubmitted();
			}

			return FALSE;
		}

		function _isDraftSubmitted() {
			if(!$this->hasDataBridge() || ($this->oDataBridge->dbridge_getSubmitterAbsName() !== FALSE) || $this->oDataBridge->dbridge_globalSubmitable()) {
				return $this->oForm->oDataHandler->_isDraftSubmitted();
			}

			return FALSE;
		}

		function _isSearchSubmitted() {
			if(!$this->hasDataBridge() || ($this->oDataBridge->dbridge_getSubmitterAbsName() !== FALSE) || $this->oDataBridge->dbridge_globalSubmitable()) {
				return $this->oForm->oDataHandler->_isSearchSubmitted();
			}

			return FALSE;
		}

		function _edition() {

			if($this->isDataBridge()) {
				return $this->dbridge_edition();
			}

			if($this->hasDataBridge()) {
				return $this->dbridged_edition();
			}

			return $this->oForm->oDataHandler->_edition();
		}

		function dbridge_edition() {
			if(($sSignature = $this->dbridge_getCurrentDsetSignature()) !== FALSE) {
				if(array_key_exists($sSignature, $this->oDataSource->aODataSets)) {
					return $this->oDataSource->aODataSets[$sSignature]->isAnchored();
				}
			}

			return FALSE;
		}

		function dbridged_edition() {
			return $this->oDataBridge->dbridge_edition();
		}

		function maySubmit() {
			return TRUE;
		}

		function isNaturalSubmitter() {
			return FALSE;
		}

		function dbridge_getPostedSignature($bDecode = TRUE) {
			if($this->isDataBridge()) {

				$sName = $this->getAbsName() . '.databridge';
				$sPath = str_replace('.', '/', $sName);

				if(($sSignature = $this->oForm->navDeepData($sPath, $this->oForm->_getRawPost())) !== FALSE) {
					$sSignature = trim($sSignature);

					if($sSignature === '') {
						return FALSE;
					}

					if($bDecode === TRUE) {
						return $this->oDataSource->dset_decodeSignature($sSignature);
					} else {
						return $sSignature;
					}
				}
			}

			return FALSE;
		}

		function dbridge_getCurrentDsetSignature() {
			return $this->aDataSetSignatures[$this->_getElementHtmlId()];
		}

		function &dbridge_getCurrentDsetObject() {
			return $this->oDataSource->aODataSets[$this->dbridge_getCurrentDsetSignature()];
		}

		function dbridged_getCurrentDsetSignature() {
			return $this->oDataBridge->dbridge_getCurrentDsetSignature();
		}

		function &dbridged_getCurrentDsetObject() {
			return $this->oDataBridge->dbridge_getCurrentDsetObject();
		}

		function dbridge_getCurrentDset() {
			$oDataSet =& $this->dbridge_getCurrentDsetObject();
			return $oDataSet->getDataSet();
		}

		function dbridged_getCurrentDset() {
			return $this->oDataBridge->dbridge_getCurrentDset();
		}

		function isIterating() {
			return FALSE;
		}

		function isIterable() {
			return FALSE;
		}

		function __getItemsStaticTable($sTable, $sValueField = 'uid', $sWhere = '') {
			// Get user language
			if(TYPO3_MODE == 'FE') {
				$sLang = $GLOBALS['TSFE']->lang;
			} else {
				$sLang = $GLOBALS['LANG']->lang;
			}

			// Get field names
			$aFieldNames = tx_staticinfotables_div::getTCAlabelField($sTable, TRUE, $sLang);
			$sFields = implode(', ', $aFieldNames);

			// Get data from static table
			$aRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($sValueField . ', ' . $sFields, $sTable, $sWhere, '', $sFields);

			$aItems =  array();

			if (empty($aRows)) {
				return $aItems;
			}

			// For each row
			foreach ($aRows as $aRow) {
				foreach ($aFieldNames as $sFieldName) {
					if ($aRow[$sFieldName]) { // If exists
						$sCaption = $aRow[$sFieldName];
						break;
					}
				}

				$aTmp = array(
					'caption' => $sCaption,
					'value' => $aRow[$sValueField]
				);

				array_push($aItems, $aTmp);
			}

			return $aItems;
		}

		function cancelError() {
			// removes potentialy thrown validation errors

			$sAbsName = $this->getAbsName();
			$sHtmlId = $this->_getElementHtmlIdWithoutFormId();

			unset($this->oForm->_aValidationErrors[$sAbsName]);
			unset($this->oForm->_aValidationErrorsByHtmlId[$sHtmlId]);
			unset($this->oForm->_aValidationErrorsInfos[$sHtmlId]);
		}

		function majixAddClass($sClass) {
			return $this->buildMajixExecuter(
				'addClass',
				$sClass
			);
		}

		function majixRemoveClass($sClass) {
			return $this->buildMajixExecuter(
				'removeClass',
				$sClass
			);
		}

		function majixRemoveAllClass() {
			return $this->buildMajixExecuter(
				'removeAllClass',
				$sClass
			);
		}

		function majixSetStyle($aStyles) {
			$aStyles = $this->oForm->div_camelizeKeys($aStyles);
			return $this->buildMajixExecuter(
				'setStyle',
				$aStyles
			);
		}

		function persistHidden() {
			return '<input type="hidden" id="' . $this->_getElementHtmlId() . '" name="' . $this->_getElementHtmlName() . '" value="' . htmlspecialchars($this->getValue()) . '" />';
		}

		function hasDeepError() {
			if($this->mayHaveChilds() && $this->hasChilds()) {
				$bHasErrors = FALSE;

				$aChildKeys = array_keys($this->aChilds);
				reset($aChildKeys);
				while(!$bHasErrors && (list(, $sKey) = each($aChildKeys))) {
					$bHasErrors = $bHasErrors || $this->aChilds[$sKey]->hasDeepError();
				}

				return $bHasErrors;
			}

			return $this->hasError();
		}

		/**
		 * Prüft, ob für das Widget schon Validation-Errors vorliegen
		 *
		 * @return boolean
		 */
		function hasError() {
			$sHtmlId = $this->_getElementHtmlIdWithoutFormId();
			if(array_key_exists($sHtmlId, $this->oForm->_aValidationErrorsByHtmlId)) {
				return TRUE;
			}

			return FALSE;
		}

		function getError() {
			if($this->hasError()) {
				$sAbsName = $this->getAbsName();
				$sHtmlId = $this->_getElementHtmlIdWithoutFormId();

				return array(
					'message' => $this->oForm->_aValidationErrorsByHtmlId[$sHtmlId],
					'info' => $this->oForm->_aValidationErrorsInfos[$sHtmlId],
				);
			}

			return FALSE;
		}

		function getDeepError() {
			$aErrors = array();
			$aErrors = $this->getDeepError_rec($aErrors);
			reset($aErrors);
			return $aErrors;
		}

		function getDeepErrorRelative() {
			$aErrors = array();
			$aErrorsRel = array();

			$aErrors = $this->getDeepError_rec($aErrors);

			reset($aErrors);
			while(list($sAbsName,) = each($aErrors)) {
				$aErrorsRel[$this->oForm->aORenderlets[$sAbsName]->getNameRelativeTo($this)] = $aErrors[$sAbsName];
			}

			reset($aErrorsRel);
			return $aErrorsRel;
		}

		function getDeepError_rec($aErrors) {

			if($this->mayHaveChilds() && $this->hasChilds()) {
				$aChildKeys = array_keys($this->aChilds);
				reset($aChildKeys);
				while((list(, $sKey) = each($aChildKeys))) {
					if($this->aChilds[$sKey]->hasError()) {
						$aErrors[$this->aChilds[$sKey]->getAbsName()] = $this->aChilds[$sKey]->getError();
					}

					$aErrors = $this->aChilds[$sKey]->getDeepError_rec($aErrors);
				}
			}

			if(($aThisError = $this->getError()) !== FALSE) {
				$aErrors[$this->getAbsName()] = $aThisError;
			}

			reset($aErrors);
			return $aErrors;
		}

		/**
		 * Validates the given Renderlet element
		 * Writes into $this->_aValidationErrors[] using tx_ameosformidable::_declareValidationError()
		 * @param	array		$aElement: details about the Renderlet element to validate, extracted from XML conf / used in formidable_mainvalidator::validate()
		 * @return boolean true wenn kein Fehler vorliegt
		 */
		function validate() {

			if(!$this->wasValidated) {
				$this->validateByPath('/');
				$this->validateByPath('/validators');
				$this->declareCustomValidationErrors();
				$this->wasValidated = TRUE;
			}

			return !$this->hasError();
		}

		function validateByPath($sPath) {
			if(!$this->hasError()) {
				$aConf = $this->_navConf($sPath);
				if(is_array($aConf) && !empty($aConf)) {

					$sAbsName = $this->getAbsName();

					while(!$this->hasError() && list($sKey, $aValidator) = each($aConf)) {
						if($sKey{0} === 'v' && $sKey{1} === 'a' && t3lib_div::isFirstPartOfStr($sKey, 'validator') && !t3lib_div::isFirstPartOfStr($sKey, 'validators')) {
							// the conf section exists
							// call validator
							$oValidator = $this->oForm->_makeValidator($aValidator);

							if($oValidator->_matchConditions()) {

								$bHasToValidate = TRUE;

								$aValidMap = $this->oForm->_navConf('/control/factorize/switchvalidation');
								if($this->oForm->isRunneable($aValidMap)) {
									$aValidMap = $this->getForm()->getRunnable()->callRunnableWidget($this, $aValidMap);
								}

								if(is_array($aValidMap) && array_key_exists($sAbsName, $aValidMap)) {
									$bHasToValidate = $aValidMap[$sAbsName];
								}

								if($bHasToValidate === TRUE) {
									$oValidator->validate($this);
								}
							}
						}
					}
				}
			}
		}


		function synthetizeAjaxEventUserobj($sEventHandler, $sPhp, $mParams=FALSE, $bCache=TRUE, $bSyncValue=FALSE) {
			return $this->oForm->oRenderer->synthetizeAjaxEvent(
				$this,
				$sEventHandler,
				FALSE,
				$sPhp,
				$mParams,
				$bCache,
				$bSyncValue
			);
		}

		function synthetizeAjaxEventCb($sEventHandler, $sCb, $mParams=FALSE, $bCache=TRUE, $bSyncValue=FALSE) {
			return $this->oForm->oRenderer->synthetizeAjaxEvent(
				$this,
				$sEventHandler,
				$sCb,
				FALSE,
				$mParams,
				$bCache,
				$bSyncValue
			);
		}

		function htmlAutocomplete() {
			if($this->mayHtmlAutocomplete()) {
				if($this->shouldHtmlAutocomplete()) {
					return '';
				} else {
					return ' autocomplete="off" ';
				}
			}

			return '';	// if rdt may not htmlautocomplete, no need to counter-indicate it
		}

		function shouldHtmlAutocomplete() {
			return $this->defaultFalse('/htmlautocomplete');
		}

	function mayHtmlAutocomplete() {
		return FALSE;
	}

	/**
	 * übermittelte werte überprüfen. z.b. sollten alle Felder
	 * auch als Renderlet im XML vorhanden sein.
	 *
	 * @param array $aGP | merged $_GET , $_POST
	 */
	public function checkValue(&$aGP) {
		//wenn das übergeben renderlet gar keine childs hat
		//dann gibt es auch nix zu prüfen. da das rdt offentsichtlich vorhanden ist!
		if(!$this->hasChilds()){
			return;
		}

		//Jeden übermittelten überprüfen ob es dazu ein widget gibt. wenn der wert ein array
		if(!empty($aGP) && is_array($aGP)){
			foreach ($aGP as $rdtName => $rdtValue) {
				$absRdtName = $this->getAbsName() . AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN .$rdtName;

				//wenn in der übergeben array ein eintrag enthalten ist, der nicht
				//durch ein widget repräsentiert wird, entfernen wir ihn um Manipulationen
				//zu verhinden
				if(!isset($this->getForm()->aORenderlets[$absRdtName]))
					unset($aGP[$rdtName]);
				else
					$this->getForm()->aORenderlets[$absRdtName]->checkValue($aGP[$rdtName]);
			}
		}
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/class.mainrenderlet.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/class.mainrenderlet.php']);
}
