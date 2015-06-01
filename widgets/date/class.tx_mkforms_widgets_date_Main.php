<?php
/**
 * Plugin 'rdt_date' for the 'ameos_formidable' extension.
 *
 * @author	Jerome Schneider <typo3dev@ameos.com>
 */


class tx_mkforms_widgets_date_Main extends formidable_mainrenderlet {

	var $aLibs = array(
		'rdt_date_class' => 'res/js/date.js',
	);

	var $sMajixClass = 'Date';
	var $sAttachPostInitTask = 'initCal';
	var $bCustomIncludeScript = TRUE;

	function _render() {

		$this->_includeLibraries();

		$sUnflattenHscValue = htmlspecialchars(
			$this->_unFlatten(
				$this->getValue()
			)
		);

		$sUnflattenHscValueForHtml = $this->getValueForHtml($sUnflattenHscValue);

		$iTstamp = $this->_flatten(
			$this->getValue()
		);

		$sLabel = $this->getLabel();

		$sTriggerId = $this->getTriggerId();
		$sTrigger = " <img src='" . t3lib_div::getIndpEnv("TYPO3_SITE_URL") . $this->sExtRelPath . "res/lib/js_calendar/img.gif' id='" . $sTriggerId . "' style='cursor: pointer;' alt='Pick date' /> ";

		$this->_initJs();

		if($this->_allowManualEdition()) {

			$sInput = "<input type=\"text\" name=\"" . $this->_getElementHtmlName() . "\" id=\"" . $this->_getElementHtmlId() . "\" value=\"" . $sUnflattenHscValueForHtml . "\"" . $this->_getAddInputParams() . " />";

		} else {

			$sSpanId = 'showspan_' . $this->_getElementHtmlId();

			if($this->_emptyFormValue($sUnflattenHscValue)) {
				$sDisplayed = $this->getEmptyString();
			} else {
				$sDisplayed = $sUnflattenHscValueForHtml;
			}



			$sInput =	"<span id='" . $sSpanId . "' " . $this->_getAddInputParams() . '>'
					.	$sDisplayed
					.	'</span>'
					.	'<input type="hidden" name="' . $this->_getElementHtmlName() . '" id="' . $this->_getElementHtmlId() . '" value="' . $iTstamp . '" />';
		}

		$sCompiled =
				$this->_displayLabel($sLabel)
			.	$sInput
			.	$sTrigger;

		return array(
			'__compiled' => $sCompiled,
			'input' => $sInput . ' ' . $sTrigger,
			'input.' => array(
				'textbox' => $sInput,
				'textbox.' => array(
					'emptystring' => $sEmptyString,
				),
				'datefield' => $sInput,
				'trigger' => $sTrigger,
			),
			'trigger' => $sTrigger,
			'trigger.' => array(
				'id' => $sTriggerId,
				'tag' => $sTrigger,
			),
			'value.' => array(
				'timestamp' => $iTstamp,
				'readable' => $sUnflattenHscValue,
			)
		);
	}

	function getEmptyString() {

		if(($sEmptyString = $this->_navConf('/data/datetime/emptystring')) !== FALSE) {

			if($this->oForm->isRunneable($sEmptyString)) {
				$sEmptyString = $this->getForm()->getRunnable()->callRunnableWidget($this, $sEmptyString);
			}

			if($sEmptyString !== FALSE) {
				return $sEmptyString;
			}
		}

		return '...';
	}

	function _renderReadOnly() {
		$aHtmlBag = parent::_renderReadOnly();
		$aHtmlBag['value.']['readable'] = $this->_getHumanReadableValue($aHtmlBag['value']);
		return $aHtmlBag;
	}

	private function getTriggerId() {
		return $this->_getElementHtmlId().'_trigger';
	}

	function _getFormat() {

		$mFormat = $this->_navConf('/data/datetime/format/');

		if($this->oForm->isRunneable($mFormat)) {
			$mFormat = $this->getForm()->getRunnable()->callRunnableWidget($this, $mFormat);
		}

		if(!$mFormat) {
			$mFormat = '%Y/%m/%d';
		}

		return $mFormat;
	}

	function _initJs() {

		$sFormat = $this->_getFormat();
		$bTime		= $this->oForm->_defaultFalse('/data/datetime/displaytime/', $this->aElement);
		$sFieldName	= $this->oForm->formid . '::' . $this->_getName();
		$sHtmlId	= $this->_getElementHtmlId();

		$aConfig = array(
			'inputField'	=> $sHtmlId,	// id of the input field
			'ifFormat'		=> $sFormat,					// format of the input field
			'showsTime'		=> $bTime,						// will display a time selector
			'button'		=> $this->getTriggerId(),		// trigger for the calendar (button ID)
			'singleClick'	=> true,						// single-click mode
			'step'			=> 1,							// show all years in drop-down boxes (instead of every other year as default)
		);

		if(!$this->_allowManualEdition()) {
			if($bTime) {
				$aConfig['ifFormat'] = '%s';
			} else {
				if($this->shouldConvertToTimestamp() === TRUE) {
					$aConfig['ifFormat'] = '%@';
				} else {
					$aConfig['ifFormat'] = $this->_getFormat();
				}
			}
			$aConfig['displayArea'] = 'showspan_' . $sHtmlId;
			$aConfig['daFormat'] = $sFormat;
		}

		$this->includeScripts(array(
			'calendarconf' => $aConfig,
			'emptystring' => $this->getEmptyString(),
			'converttotimestamp' => $this->shouldConvertToTimestamp(),
			'allowmanualedition' => $this->_allowManualEdition(),
		));
	}

	function _flatten($mData) {

		if(!$this->_emptyFormValue($mData)) {

			if($this->shouldConvertToTimestamp()) {

				if(!$this->__isTimestamp($mData)) {
					// on convertit la date en timestamp
					// on commence par r�cup�rer la configuration du format de date utilis�

					$sFormat = $this->_getFormat();
					$result = $this->__date2tstamp($mData, $sFormat);

					return $result;
				}
			}
		} else {
			return '';
		}

		return $mData;
	}

	function _unFlatten($mData) {

		if($this->__isTimestamp($mData)) {
			return $this->__tstamp2date($mData);
		}

		return $mData;
	}

	function __isTimestamp($mData) {
		return (('' . intval($mData)) === ('' . $mData));
	}

	function _allowManualEdition() {
		return
			$this->_defaultFalse('/data/datetime/allowmanualedition')
			|| $this->_defaultFalse('/allowmanualedition');
	}

	function __date2tstamp($strdate, $format) {
		// strptime
		$aAvailableTokens = array(
			'%a', '%A', '%b', '%B', '%C', '%d', '%e',
			'%H', '%I', '%j', '%k', '%l', '%m', '%M',
			'%n', '%p', '%P', '%S', '%s', '%t', '%W',
			'%u', '%w', '%y', '%Y', '%%'
		);

		$aShortMonth = array(
			'C' => array (
				'Jan' => '01',
				'Feb' => '02',
				'Mar' => '03',
				'Apr' => '04',
				'May' => '05',
				'Jun' => '06',
				'Jul' => '07',
				'Aug' => '08',
				'Sep' => '09',
				'Oct' => '10',
				'Nov' => '11',
				'Dec' => '12'
			),
			'fr_FR' => array (
				'Jan' => '01',
				'Fev' => '02',
				'Mar' => '03',
				'Avr' => '04',
				'Mai' => '05',
				'Juin' => '06',
				'Juil' => '07',
				'Aout' => '08',
				'Sep' => '09',
				'Oct' => '10',
				'Nov' => '11',
				'Dec' => '12'
			),
			'de_DE' => array (
				'Jan' => '01',
				'Feb' => '02',
				'M�r' => '03',
				'Apr' => '04',
				'May' => '05',
				'Jun' => '06',
				'Jul' => '07',
				'Aug' => '08',
				'Sep' => '09',
				'Okt' => '10',
				'Nov' => '11',
				'Dez' => '12'
			)
		);

/*
%a				short name of the day (local)
%A				full name of the day (local)
%b				short month name (local)
%B        full month name (local)
%C        century number
%d        the day of the month (00 ... 31)
%e        the day of the month (0 ... 31)
%H        hour (00 ... 24)
%I        hour (01 ... 12)
%j        day of the year (000 ... 366)
%k        hour (0 ... 23)
%l        hour (1 ... 12)
%m        month (01 ... 12)
%M        mInute (00 ... 59)
%n        a newline character
%p        "PM" or "AM"
%P        "pm" or "am"
%s        number of seconds since Unix Epoch
%S        second (00 ... 59)
%t        a tab character
%W        the week number
%u        the day of the week (1 ... 7, 1 = MON)
%w        the day of the week (0 ... 6, 0 = SUN)
%y        year without the century (00 ... 99)
%Y        year including the century (eg. 1976)
%%        a literal % character
*/
		// on d�termine les s�parateurs
		$aSeparateurs = array();
		$separateurs = str_replace($aAvailableTokens, '', $format);

		if(strlen($separateurs) > 0) {
			for($k = 0; $k <= strlen($separateurs); $k++) {
				if(!in_array($separateurs[$k], $aSeparateurs)) {
					$aSeparateurs[] = $separateurs[$k];
				}
			}
		}
		$aFormat = explode('#', str_replace($aSeparateurs, '#', $format));
		$aTokens = explode('#', str_replace($aSeparateurs, '#', $strdate));

		$aDate = array();
		foreach($aFormat as $index => $format) {
			$aDate[$format] = $aTokens[$index];
		}
		reset($aDate);

		$day = strftime('%d');
		$month = strftime('%m');
		$year = strftime('%Y');
		$hour = 0;
		$minute = 0;
		$second = 0;

		if(array_key_exists('%d', $aDate)) {
			$day = $aDate['%d'];
		} elseif(array_key_exists('%e', $aDate)) {
			$day = $aDate['%e'];
		}

		if(array_key_exists('%m', $aDate)) {
			$month = $aDate['%m'];
		}

		if(array_key_exists('%Y', $aDate)) {
			$year = $aDate['%Y'];
		}

		if(array_key_exists('%H', $aDate)) {
			$hour = $aDate['%H'];
		}

		if(array_key_exists('%M', $aDate)) {
			$minute = $aDate['%M'];
		}

		if(array_key_exists('%S', $aDate)) {
			$second = $aDate['%S'];
		}

		$tstamp = mktime($hour, $minute, $second, $month, $day , $year);
		return $tstamp;
	}

	function _getHumanReadableValue($data) {
		return $this->_unFlatten($data);
	}

	function __tstamp2date($data) {

		if($this->shouldConvertToTimestamp()) {

			if(intval($data) != 0) {

				// il s'agit d'un champ timestamp
				// on convertit le timestamp en date lisible

				$elementname = $this->_navConf('/name/');
				$format = $this->_getFormat();

				if(($locale = $this->_navConf('/data/datetime/locale/')) !== FALSE) {

					$sCurrentLocale = setlocale(LC_TIME, 0);

					// From the documentation of setlocale: "If locale is zero or "0", the locale setting
					// is not affected, only the current setting is returned."

					setlocale(LC_TIME, $locale);
				}

				if($this->_defaultFalse('/data/datetime/gmt') === FALSE) {
					$sDate = strftime($format, $data);
				} else {
					$sDate = gmstrftime($format, $data);
				}

				$this->oForm->_debug($data . ' in ' . $format . ' => ' . $sDate, 'AMEOS_FORMIDABLE_RDT_DATE ' . $elementname . ' - TIMESTAMP TO DATE CONV.');

				if($locale !== FALSE) {
					setlocale(LC_TIME, $sCurrentLocale);
				}

				return $sDate;
			} else {
				return '';
			}
		}

		return $data;
	}

	function _emptyFormValue($value) {
		return intval($value) <= 0;
	}

	function _sqlSearchClause($sValue, $sFieldPrefix = '', $sName = '', $bRec = TRUE) {

		if($sName === '') {
			$sName = $this->_getName();
		}

		$sFieldName = $sFieldPrefix . $sName;
		$sComparison = (($sTemp = $this->_navConf('/sql/comparison')) !== FALSE) ? $sTemp : '=';
		$sComparison = (($sTemp = $this->_navConf('/search/comparison')) !== FALSE) ? $sTemp : $sComparison;

		$sSql = '((' . $sFieldName . " - '" . $sValue . "') " . $sComparison . ' 0)';

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

	function _includeLibraries() {
		if($this->oForm->issetAdditionalHeaderData('mkforms_date_includeonce'))
			return;

		$sLang = ($GLOBALS['TSFE']->lang == 'default') ? 'en' : $GLOBALS['TSFE']->lang;

		$sAbsLangFile = $this->sExtPath . 'res/lib/js_calendar/lang/calendar-' . $sLang . '.js';

		if(!file_exists($sAbsLangFile)) {
			$sLang = 'en';
		}

		$sLangFile = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . $this->sExtRelPath . 'res/lib/js_calendar/lang/calendar-' . $sLang . '.js';

		$sCssFile = t3lib_div::getIndpEnv("TYPO3_SITE_URL") . 'fileadmin/templates/css/template_calendar.css';

		$oJsLoader =$this->getForm()->getJSLoader();
		$oJsLoader->additionalHeaderData(
			'
				<script type="text/javascript" src="' . $oJsLoader->getScriptPath(t3lib_div::getIndpEnv("TYPO3_SITE_URL") . $this->sExtRelPath. 'res/lib/js_calendar/calendar.js') . '"></script>
				<script type="text/javascript" src="' . $oJsLoader->getScriptPath($sLangFile) . '"></script>
				<script type="text/javascript" src="' . $oJsLoader->getScriptPath(t3lib_div::getIndpEnv("TYPO3_SITE_URL") . $this->sExtRelPath . 'res/lib/js_calendar/calendar-setup.js') . '"></script>
				<link rel="stylesheet" type="text/css" media="all" href="' . $sCssFile . '" />

			',
			'mkforms_date_includeonce'
		);
	}

	function shouldConvertToTimestamp() {
		return
			$this->_defaultTrue('/data/datetime/converttotimestamp')
			&& $this->_defaultTrue('/converttotimestamp');
	}

	function getValue() {
		$mValue = parent::getValue();

		if($this->_allowManualEdition() && $this->shouldConvertToTimestamp()) {
			if(!$this->_emptyFormValue($mValue)) {
				return $this->__date2tstamp(
					$mValue,
					$this->_getFormat()
				);
			}

			return '';
		}

		return $mValue;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_date/api/class.tx_rdtdate.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_date/api/class.tx_rdtdate.php']);
}

?>