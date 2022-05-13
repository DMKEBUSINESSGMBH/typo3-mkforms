<?php
/**
 * Plugin 'rdt_date' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_widgets_date_Main extends formidable_mainrenderlet
{
    public $aLibs = [
        'rdt_date_class' => 'Resources/Public/JavaScript/widgets/date/date.js',
    ];

    public $sMajixClass = 'Date';
    public $sAttachPostInitTask = 'initCal';
    public $bCustomIncludeScript = true;

    /**
     * @var string[]
     */
    private $allowedDateFormatParts = [
        '%a', '%A', '%b', '%B', '%C', '%d', '%e',
        '%H', '%I', '%j', '%k', '%l', '%m', '%M',
        '%n', '%p', '%P', '%S', '%s', '%t', '%W',
        '%u', '%w', '%y', '%Y', '%%',
    ];

    protected function _render()
    {
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
        $resourcesPath = \TYPO3\CMS\Core\Utility\PathUtility::getAbsoluteWebPath(
            \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(
                'EXT:mkforms/Resources/Public/JavaScript/widgets/date/lib'
            )
        );
        $sTrigger = " <img src='".$resourcesPath."/js_calendar/img.gif' id='".$sTriggerId."' style='cursor: pointer;' alt='Pick date' /> ";

        $this->_initJs();

        if ($this->_allowManualEdition()) {
            $sInput = '<input type="text" name="'.$this->_getElementHtmlName().'" id="'.$this->_getElementHtmlId().'" value="'.$sUnflattenHscValueForHtml.'"'.$this->_getAddInputParams().' />';
        } else {
            $sSpanId = 'showspan_'.$this->_getElementHtmlId();

            if ($this->_emptyFormValue($sUnflattenHscValue)) {
                $sDisplayed = $this->getEmptyString();
            } else {
                $sDisplayed = $sUnflattenHscValueForHtml;
            }

            $sInput = "<span id='".$sSpanId."' ".$this->_getAddInputParams().'>'
                    .$sDisplayed
                    .'</span>'
                    .'<input type="hidden" name="'.$this->_getElementHtmlName().'" id="'.$this->_getElementHtmlId().'" value="'.$iTstamp.'" />';
        }

        $sCompiled =
                $this->_displayLabel($sLabel)
            .$sInput
            .$sTrigger;

        return [
            '__compiled' => $sCompiled,
            'input' => $sInput.' '.$sTrigger,
            'input.' => [
                'textbox' => $sInput,
                'textbox.' => [
                    'emptystring' => '',
                ],
                'datefield' => $sInput,
                'trigger' => $sTrigger,
            ],
            'trigger' => $sTrigger,
            'trigger.' => [
                'id' => $sTriggerId,
                'tag' => $sTrigger,
            ],
            'value.' => [
                'timestamp' => $iTstamp,
                'readable' => $sUnflattenHscValue,
            ],
        ];
    }

    public function getEmptyString()
    {
        if (false !== ($sEmptyString = $this->_navConf('/data/datetime/emptystring'))) {
            if ($this->oForm->isRunneable($sEmptyString)) {
                $sEmptyString = $this->getForm()->getRunnable()->callRunnableWidget($this, $sEmptyString);
            }

            if (false !== $sEmptyString) {
                return $sEmptyString;
            }
        }

        return '...';
    }

    protected function _renderReadOnly()
    {
        $aHtmlBag = parent::_renderReadOnly();
        $aHtmlBag['value.']['readable'] = $this->_getHumanReadableValue($aHtmlBag['value']);

        return $aHtmlBag;
    }

    private function getTriggerId()
    {
        return $this->_getElementHtmlId().'_trigger';
    }

    protected function _getFormat()
    {
        $mFormat = $this->_navConf('/data/datetime/format/');

        if ($this->oForm->isRunneable($mFormat)) {
            $mFormat = $this->getForm()->getRunnable()->callRunnableWidget($this, $mFormat);
        }

        if (!$mFormat) {
            $mFormat = '%Y/%m/%d';
        }

        return $mFormat;
    }

    protected function _initJs()
    {
        $sFormat = $this->_getFormat();
        $bTime = $this->oForm->_defaultFalse('/data/datetime/displaytime/', $this->aElement);
        $sHtmlId = $this->_getElementHtmlId();

        $aConfig = [
            'inputField' => $sHtmlId,    // id of the input field
            'ifFormat' => $sFormat,                    // format of the input field
            'showsTime' => $bTime,                        // will display a time selector
            'button' => $this->getTriggerId(),        // trigger for the calendar (button ID)
            'singleClick' => true,                        // single-click mode
            'step' => 1,                            // show all years in drop-down boxes (instead of every other year as default)
        ];

        if (!$this->_allowManualEdition()) {
            if ($bTime) {
                $aConfig['ifFormat'] = '%s';
            } else {
                if (true === $this->shouldConvertToTimestamp()) {
                    $aConfig['ifFormat'] = '%@';
                } else {
                    $aConfig['ifFormat'] = $this->_getFormat();
                }
            }
            $aConfig['displayArea'] = 'showspan_'.$sHtmlId;
            $aConfig['daFormat'] = $sFormat;
        }

        $this->includeScripts([
            'calendarconf' => $aConfig,
            'emptystring' => $this->getEmptyString(),
            'converttotimestamp' => $this->shouldConvertToTimestamp(),
            'allowmanualedition' => $this->_allowManualEdition(),
        ]);
    }

    public function _flatten($mData)
    {
        if ($this->_emptyFormValue($mData)) {
            return '';
        }

        if ($this->oForm->_defaultTrue('/data/datetime/converttotimestamp/', $this->aElement) && !$this->_isTimestamp($mData)) {
            $sFormat = $this->_getFormat();
            $result = $this->_date2tstamp($mData, $sFormat);
        } else {
            $result = $mData;
        }

        return $result;
    }

    public function _unFlatten($mData)
    {
        if ($this->_isTimestamp($mData)) {
            return $this->_tstamp2date($mData);
        }

        return $mData;
    }

    private function _isTimestamp($mData)
    {
        return (''.(int) $mData) === (''.$mData);
    }

    protected function _allowManualEdition()
    {
        return
            $this->_defaultFalse('/data/datetime/allowmanualedition')
            || $this->_defaultFalse('/allowmanualedition');
    }

    /**
     * Converts a date string into a UNIX timestamp.
     *
     * @param string $dateAsString
     * @param string $dateFormat
     *
     * @return int the date as a UNIX timestamp
     */
    private function _date2tstamp($dateAsString, $dateFormat)
    {
        /* @var string[] $dateFormatSeparators */
        $dateFormatSeparators = [];

        /* @var string $concatenatedDateFormatSeparators */
        $concatenatedDateFormatSeparators = str_replace($this->allowedDateFormatParts, '', $dateFormat);
        if ('' !== $concatenatedDateFormatSeparators) {
            $nonUniqueSeparators = str_split($concatenatedDateFormatSeparators);
            $dateFormatSeparators = array_unique($nonUniqueSeparators);
        }
        $dateFormatParts = explode('#', str_replace($dateFormatSeparators, '#', $dateFormat));
        $dateParts = explode('#', str_replace($dateFormatSeparators, '#', $dateAsString));

        /* @var int[] $datePartsByFormatCode */
        $datePartsByFormatCode = [];
        foreach ($dateFormatParts as $index => $dateFormat) {
            $datePartsByFormatCode[$dateFormat] = (int) $dateParts[$index];
        }

        if (array_key_exists('%d', $datePartsByFormatCode)) {
            $day = $datePartsByFormatCode['%d'];
        } elseif (array_key_exists('%e', $datePartsByFormatCode)) {
            $day = $datePartsByFormatCode['%e'];
        } else {
            $currentDay = (int) strftime('%d');
            $day = $currentDay;
        }

        $currentMonth = (int) strftime('%m');
        if (array_key_exists('%m', $datePartsByFormatCode)) {
            $month = $datePartsByFormatCode['%m'];
        } else {
            $month = $currentMonth;
        }

        $currentYear = (int) strftime('%Y');
        if (array_key_exists('%Y', $datePartsByFormatCode)) {
            $year = $datePartsByFormatCode['%Y'];
        } else {
            $year = $currentYear;
        }

        if (array_key_exists('%H', $datePartsByFormatCode)) {
            $hour = $datePartsByFormatCode['%H'];
        } else {
            $hour = 0;
        }

        if (array_key_exists('%M', $datePartsByFormatCode)) {
            $minute = $datePartsByFormatCode['%M'];
        } else {
            $minute = 0;
        }

        if (array_key_exists('%S', $datePartsByFormatCode)) {
            $second = $datePartsByFormatCode['%S'];
        } else {
            $second = 0;
        }

        return mktime($hour, $minute, $second, $month, $day, $year);
    }

    public function _getHumanReadableValue($data)
    {
        return $this->_unFlatten($data);
    }

    protected function _tstamp2date($data)
    {
        if ($this->shouldConvertToTimestamp()) {
            if (0 != (int) $data) {
                // il s'agit d'un champ timestamp
                // on convertit le timestamp en date lisible

                $elementname = $this->_navConf('/name/');
                $format = $this->_getFormat();

                if (false !== ($locale = $this->_navConf('/data/datetime/locale/'))) {
                    $sCurrentLocale = setlocale(LC_TIME, 0);

                    // From the documentation of setlocale: "If locale is zero or "0", the locale setting
                    // is not affected, only the current setting is returned."

                    setlocale(LC_TIME, $locale);
                }

                if (false === $this->_defaultFalse('/data/datetime/gmt')) {
                    $sDate = strftime($format, $data);
                } else {
                    $sDate = gmstrftime($format, $data);
                }

                $this->oForm->_debug($data.' in '.$format.' => '.$sDate, 'AMEOS_FORMIDABLE_RDT_DATE '.$elementname.' - TIMESTAMP TO DATE CONV.');

                if (false !== $locale) {
                    setlocale(LC_TIME, $sCurrentLocale);
                }

                return $sDate;
            } else {
                return '';
            }
        }

        return $data;
    }

    /**
     * Checks whether $value is non-empty.
     *
     * @param string $value
     *
     * @return bool
     */
    public function _emptyFormValue($value)
    {
        return '' === trim($value);
    }

    protected function _sqlSearchClause($sValue, $sFieldPrefix = '', $sName = '', $bRec = true)
    {
        if ('' === $sName) {
            $sName = $this->_getName();
        }

        $sFieldName = $sFieldPrefix.$sName;
        $sComparison = (false !== ($sTemp = $this->_navConf('/sql/comparison'))) ? $sTemp : '=';
        $sComparison = (false !== ($sTemp = $this->_navConf('/search/comparison'))) ? $sTemp : $sComparison;

        $sSql = '(('.$sFieldName." - '".$sValue."') ".$sComparison.' 0)';

        if (true === $bRec) {
            $sSql = $this->overrideSql(
                $sValue,
                $sFieldPrefix,
                $sName,
                $sSql
            );
        }

        return $sSql;
    }

    protected function _includeLibraries()
    {
        if ($this->oForm->issetAdditionalHeaderData('mkforms_date_includeonce')) {
            return;
        }

        $sLang = ('default' != \Sys25\RnBase\Utility\Environment::getCurrentLanguageKey()) ?? 'en';
        $resourcesPath = \TYPO3\CMS\Core\Utility\PathUtility::getAbsoluteWebPath(
            \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(
                'EXT:mkforms/Resources/Public/JavaScript/widgets/date/lib'
            )
        );
        $sAbsLangFile = $resourcesPath.'/js_calendar/lang/calendar-'.$sLang.'.js';
        if (!file_exists($sAbsLangFile)) {
            $sLang = 'en';
        }
        $sLangFile = $resourcesPath.'/js_calendar/lang/calendar-'.$sLang.'.js';

        $oJsLoader = $this->getForm()->getJSLoader();
        $css = '';
        if ($cssFile = $this->getForm()->getConfTS('renderlets.date.css')) {
            $cssFile = \TYPO3\CMS\Core\Utility\PathUtility::getAbsoluteWebPath(\Sys25\RnBase\Utility\Files::getFileAbsFileName($cssFile));
            $cssFile = $oJsLoader->getScriptPath($cssFile, 'css');
            $css = empty($cssFile) ? '' : '<link rel="stylesheet" type="text/css" media="all" href="'.$cssFile.'" />';
        }

        $oJsLoader->additionalHeaderData(
            (
                '<script src="'.$oJsLoader->getScriptPath($resourcesPath.'/js_calendar/calendar.js').'"></script>'.
                '<script src="'.$oJsLoader->getScriptPath($sLangFile).'"></script>'.
                '<script src="'.$oJsLoader->getScriptPath($resourcesPath.'/js_calendar/calendar-setup.js').'"></script>'.
                $css
            ),
            'mkforms_date_includeonce'
        );
    }

    public function shouldConvertToTimestamp()
    {
        return
            $this->_defaultTrue('/data/datetime/converttotimestamp')
            && $this->_defaultTrue('/converttotimestamp');
    }

    public function getValue()
    {
        $mValue = parent::getValue();

        if ($this->_allowManualEdition() && $this->shouldConvertToTimestamp()) {
            if (!$this->_emptyFormValue($mValue)) {
                return $this->_date2tstamp(
                    $mValue,
                    $this->_getFormat()
                );
            }

            return '';
        }

        return $mValue;
    }
}
