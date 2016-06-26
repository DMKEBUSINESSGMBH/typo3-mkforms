<?php
/**
 * Plugin 'rdt_box' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_widgets_tabpanel_Main extends formidable_mainrenderlet {

	var $sMajixClass = 'TabPanel';
	var $aLibs = array(
		'rdt_tabpanel_lib' => 'res/js/libs/control.tabs.2.1.1.js',
		'rdt_tabpanel_class' => 'res/js/tabpanel.js',
	);
	var $bCustomIncludeScript = TRUE;

	function _render() {

		$sBegin = '<ul id="' . $this->_getElementHtmlId() . '" ' . $this->_getAddInputParams() . ' onmouseup="this.blur()">';
		$sEnd = '</ul>';

		$sCssId = $this->_getElementCssId();

		$aTabs = array();
		$aHtmlTabs = array();
		reset($this->aChilds);
		while(list($sName, ) = each($this->aChilds)) {
			$oRdt =& $this->aChilds[$sName];

			if($oRdt->_getType() == 'TAB') {

				$sId = $oRdt->_getElementHtmlId();
				$aTabs[$sId] = array(
					'name' => $sName,
					'label' => $this->getLabel(),
					'htmlid' => $sId,
				);
			}
		}

		$visible = TRUE;
		$aConfig = array(
			'setClassOnContainer' => 'false',
			'activeClassName' => 'active',
			'defaultTab' => 'first',
			'linkSelector' => 'li a.rdttab',
			'visible' => &$visible,
			'tabs' => $aTabs,
		);

		if(($aUserConfig = $this->_navConf('config')) !== FALSE) {

			if(array_key_exists('activeclassname', $aUserConfig)) {
				$aConfig['activeClassName'] = $aUserConfig['activeclassname'];
			}
			if(array_key_exists('setclassoncontainer', $aUserConfig)) {
				$aConfig['setclassoncontainer'] = $aUserConfig['setclassoncontainer'];
			}

			if(array_key_exists('defaulttab', $aUserConfig)) {
				if(array_key_exists($aUserConfig['defaulttab'], $this->aChilds)) {
					// tab id
					$aConfig['defaultTab'] = $this->oForm->aORenderlets[$this->aChilds[$aUserConfig['defaulttab']]->_navConf('/content')]->_getElementHtmlId();
				} else {
					// first, last, none
					$aConfig['defaultTab'] = $aUserConfig['defaulttab'];
				}
			}

		}

		$aChilds = $this->renderChildsBag();

		$hideIfChildsBagCount = array();
		if($this->_navConf('hideifchildsbagcount') !== FALSE) {
			$hideIfChildsBagCount = $this->_navConf('hideifchildsbagcount');
			$hideIfChildsBagCount = Tx_Rnbase_Utility_Strings::trimExplode(',', $hideIfChildsBagCount);
			$hideIfChildsBagCount = array_flip($hideIfChildsBagCount);
		}

		$visible = !array_key_exists(count($aChilds), $hideIfChildsBagCount);

		if ($visible) {
			$sCompiledChilds = $this->renderChildsCompiled(
				$aChilds
			);
			$compiled = $this->_displayLabel($sLabel) . $sBegin . $sCompiledChilds . $sEnd;
		}

		$this->includeScripts(
			array(
				'libconfig' => $aConfig,
				'tabs' => $aTabs
			)
		);


		$aHtmlBag = array(
			'__compiled' => $aConfig['visible'] ? $compiled : '',
			'ul.' => array(
				'begin' => $sBegin,
				'end' => $sEnd,
			),
			'childs' => $aChilds
		);

		return $aHtmlBag;
	}

	function _readOnly() {
		return TRUE;
	}

	function _renderOnly() {
		return TRUE;
	}

	function _renderReadOnly() {
		return $this->_render();
	}

	function _debugable() {
		return $this->oForm->_defaultFalse('/debugable/', $this->aElement);
	}

	function majixSetActiveTab($sTab) {
		return $this->buildMajixExecuter(
			'setActiveTab',
			$this->oForm->aORenderlets[$this->aChilds[$sTab]->_navConf('/content')]->_getElementHtmlId()
		);
	}

	function majixNextTab() {
		return $this->buildMajixExecuter(
			'next'
		);
	}

	function majixPreviousTab() {
		return $this->buildMajixExecuter(
			'previous'
		);
	}

	function majixFirstTab() {
		return $this->buildMajixExecuter(
			'first'
		);
	}

	function majixLastTab() {
		return $this->buildMajixExecuter(
			'last'
		);
	}

	function mayHaveChilds() {
		return TRUE;
	}

	function shouldAutowrap() {
		return $this->oForm->_defaultFalse('/childs/autowrap/');
	}
}
