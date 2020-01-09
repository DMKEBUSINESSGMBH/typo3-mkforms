<?php
/**
 * Plugin 'rdt_ticker' for the 'ameos_formidable' extension.
 *
 * @author  Loredana Zeca <typo3dev@ameos.com>
 */
class tx_mkforms_widgets_ticker_Main extends formidable_mainrenderlet
{
    public $aLibs = [
        'rdt_ticker_class' => 'res/js/ticker.js',
    ];

    public $sMajixClass = 'Ticker';

    public $bCustomIncludeScript = true;

    public $oDataStream = false;
    public $aDatasource = false;

    public $sSeparatorHtml = '';

    public $aConfig = false;
    public $aLimitAndSort = false;

    public function _render()
    {
        $this->_initLimitAndSort();
        $this->_initDatasource();

        if (false === ($sWidth = $this->_navConf('/width'))) {
            $sWidth = '450';
        }

        if (false === ($sHeight = $this->_navConf('/height'))) {
            $sHeight = '18';
        }

        if (false === ($sScrollMode = $this->_navConf('/scrolling/mode')) || ('horizontal' !== $sScrollMode && 'vertical' !== $sScrollMode)) {
            $sScrollMode = 'horizontal';
        }

        switch ($sScrollMode) {
            case 'horizontal':
                if (false === ($sScrollDirection = $this->_navConf('/scrolling/direction')) || ('left' !== $sScrollDirection && 'right' !== $sScrollDirection)) {
                    $sScrollDirection = 'left';
                }
                $this->sSeparatorHtml = "<div id='".$this->_getElementHtmlId().".clear' style='border:medium none; clear:both; font-size:1px; height:1px; line-height:1px;'><hr style='position:absolute; top:-50000px;' /></div>";
                break;
            case 'vertical':
                if (false === ($sScrollDirection = $this->_navConf('/scrolling/direction')) || ('top' !== $sScrollDirection && 'bottom' !== $sScrollDirection)) {
                    $sScrollDirection = 'top';
                }
                break;
        }

        if (false === ($sScrollStartDelay = $this->_navConf('/scrolling/startdelay'))) {
            $sScrollStartDelay = '2500';
        }

        if (false === ($sScrollNextDelay = $this->_navConf('/scrolling/nextdelay'))) {
            $sScrollNextDelay = '100';
        }

        if (false === ($sScrollAmount = $this->_navConf('/scrolling/amount'))) {
            $sScrollAmount = ('top' === $sScrollDirection || 'left' === $sScrollDirection) ? -1 : 1;
        } else {
            $sScrollAmount = ('top' === $sScrollDirection || 'left' === $sScrollDirection) ? -($sScrollAmount) : $sScrollAmount;
        }

        if (false === ($sScrollOverflow = $this->_navConf('/scrolling/overflow'))) {
            $sScrollOverflow = 'hidden';
        }

        if (false === ($sOffsetTop = $this->_navConf('/offsettop'))) {
            $sOffsetTop = '0';
        }

        if (false === ($sOffsetLeft = $this->_navConf('/offsetleft'))) {
            $sOffsetLeft = '0';
        }

        if (false === ($sBackground = $this->_navConf('/background'))) {
            $sBackground = 'none';
        }
        if (false === ($sBgColor = $this->_navConf('/bgcolor'))) {
            $sBgColor = 'transparent';
        }

        if (false === ($sBorder = $this->_navConf('/border'))) {
            $sBorder = 'none';
        }
        if (false === ($sBorderColor = $this->_navConf('/bordercolor'))) {
            $sBorderColor = 'white';
        }

        $this->aConfig = [
            'width' => $sWidth,
            'height' => $sHeight,
            'item' => [
                'width' => $this->_navConf('/itemwidth'),
                'height' => $this->_navConf('/itemheight'),
                'style' => $this->_navConf('/itemstyle'),
            ],
            'scroll' => [
                'mode' => $sScrollMode,
                'direction' => $sScrollDirection,
                'startDelay' => $sScrollStartDelay,
                'nextDelay' => $sScrollNextDelay,
                'amount' => $sScrollAmount,
                'stop' => (bool) $this->oForm->_isTrueVal($this->_navConf('/scrolling/stop')),
                'overflow' => $sScrollOverflow,
            ],
            'offset' => [
                'top' => $sOffsetTop,
                'left' => $sOffsetLeft,
            ],
            'background' => $sBackground,
            'bgcolor' => $sBgColor,
            'border' => $sBorder,
            'bordercolor' => $sBorderColor,
        ];

        $sLabel = $this->oForm->getConfigXML()->getLLLabel($this->_navConf('/label'));

        $sHtml = $this->_renderList();
        $sBox1 = "<div id='".$this->_getElementHtmlId().".1'>".$sHtml.'</div>';
        $sBox2 = "<div id='".$this->_getElementHtmlId().".2'>".$sHtml.'</div>';
        $sHtml = "<div id='".$this->_getElementHtmlId()."'>".$this->_displayLabel($sLabel).$sBox1.$sBox2.'</div>';
        $sHtml = '<div '.$this->_getAddInputParams().'>'.$sHtml.'</div>';

        $aHtmlBag = &$this->aConfig;
        $aHtmlBag['__compiled'] = $sHtml;
        $aHtmlBag['html'] = $sHtml;

        // allowed because of $bCustomIncludeScript = TRUE
        $this->includeScripts($this->aConfig);

        return $aHtmlBag;
    }

    public function &_renderList()
    {
        if ($this->aDatasource) {        // if this ticker has a datasource that must be used to generate his content
            if (0 == $this->aDatasource['numrows']) {
                $sRowsHtml = '';
            } else {
                $sTemplate = $this->_getTemplate();
                $aAltRows = $this->_getRowsSubpart($sTemplate);
                $iNbAlt = count($aAltRows);

                $aRows = $this->aDatasource['results'];
                foreach ($aRows as $i => $aRow) {
                    $aCurRow = $this->_refineRow($aRow);
                    $sRowHtml = $this->oForm->getTemplateTool()->parseTemplateCode(
                        $aAltRows[$i % $iNbAlt],        // alternate rows
                        $aCurRow
                    );
                    $sRowHtml = '<div class="ameosformidable-rdtticker-item">'.$sRowHtml.'</div>';
                    $aRowsHtml[] = $sRowHtml;
                }
                $sRowsHtml = implode('', $aRowsHtml);
            }

            $sHtml = tx_rnbase_util_Templates::substituteSubpart(
                $sTemplate,
                '###ROWS###',
                $sRowsHtml,
                false,
                false
            );
            $sHtml .= $this->sSeparatorHtml;
        } elseif (false !== ($this->_navConf('/html'))) {        // if this ticker has a html as content
            $sHtml = ($this->oForm->isRunneable($this->aElement['html'])) ? $this->getForm()->getRunnable()->callRunnableWidget($this, $this->aElement['html']) : $this->_navConf('/html');
            $sHtml = $this->oForm->_substLLLInHtml($sHtml).$this->sSeparatorHtml;
        } else {
            $this->oForm->mayday('RENDERLET TICKER <b>'.$this->_getName().'</b> requires /datasource or /html to be properly set. Please check your XML configuration');
        }

        return $sHtml;
    }

    public function &_refineRow($aData)
    {
        array_push($this->oForm->oDataHandler->__aListData, $aData);
        foreach ($this->aChilds as $sName => $oChild) {
            $this->aChilds[$sName]->setValue($aData[$sName]);
        }
        $aCurRow = $this->renderChildsBag();
        array_pop($this->oForm->oDataHandler->__aListData);

        return $aCurRow;
    }

    public function &_getRowsSubpart($sTemplate)
    {
        $aRowsTmpl = [];

        if (false !== ($sAltRows = $this->_navConf('/template/alternaterows')) && $this->oForm->isRunneable($sAltRows)) {
            $sAltList = $this->getForm()->getRunnable()->callRunnableWidget($this, $sAltRows);
        } elseif (false === ($sAltList = $this->_navConf('/template/alternaterows'))) {
            $this->oForm->mayday('RENDERLET TICKER <b>'.$this->_getName().'</b> requires /template/alternaterows to be properly set. Please check your XML configuration');
        }

        $aAltList = Tx_Rnbase_Utility_Strings::trimExplode(',', $sAltList);
        if (sizeof($aAltList) > 0) {
            $sRowsPart = tx_rnbase_util_Templates::getSubpart($sTemplate, '###ROWS###');

            foreach ($aAltList as $sAltSubpart) {
                $aRowsTmpl[] = tx_rnbase_util_Templates::getSubpart($sRowsPart, $sAltSubpart);
            }
        }

        return $aRowsTmpl;
    }

    public function &_getTemplate()
    {
        if (false !== ($aTemplate = $this->_navConf('/template'))) {
            $sPath = Tx_Rnbase_Utility_T3General::getFileAbsFileName($this->oForm->_navConf('/path', $aTemplate));
            if (!file_exists($sPath)) {
                $this->oForm->mayday('renderlet:'.$this->_getType().'[name='.$this->getName()."] - The given template file path (<b>'".$sPath."'</b>) doesn't exists.");
            } elseif (is_dir($sPath)) {
                $this->oForm->mayday('renderlet:'.$this->_getType().'[name='.$this->getName()."] - The given template file path (<b>'".$sPath."'</b>) is a directory, and should be a file.");
            } elseif (!is_readable($sPath)) {
                $this->oForm->mayday('renderlet:'.$this->_getType().'[name='.$this->getName().'] - The given template file path exists but is not readable.');
            }

            if (false === ($sSubpart = $this->oForm->_navConf('/subpart', $aTemplate))) {
                $sSubpart = $this->getName();
            }

            $sHtml = tx_rnbase_util_Templates::getSubpart(
                Tx_Rnbase_Utility_T3General::getUrl($sPath),
                $sSubpart
            );

            if ('' == trim($sHtml)) {
                $this->oForm->mayday('renderlet:'.$this->_getType().'[name='.$this->getName()."] - The given template (<b>'".$sPath."'</b> with subpart marquer <b>'".$sSubpart."'</b>) <b>returned an empty string</b> - Check your template");
            }

            return $this->oForm->getTemplateTool()->parseTemplateCode(
                $sHtml,
                $aChildsBag,
                [],
                false
            );
        }
    }

    public function _initDatasource()
    {
        if (false !== ($sDsToUse = $this->_navConf('/datasource/use'))) {
            if (!array_key_exists($sDsToUse, $this->oForm->aODataSources)) {
                $this->oForm->mayday('RENDERLET TICKER <b>'.$this->_getName()."</b> - refers to undefined datasource '".$sDsToUse."'. Check your XML conf.");
            } else {
                $this->oDataStream = &$this->oForm->aODataSources[$sDsToUse];
                $this->aDatasource = $this->oDataStream->_fetchData($this->aLimitAndSort);
            }
        }
    }

    public function _initLimitAndSort()
    {
        if (false === ($sLimit = $this->_navConf('/datasource/limit'))) {
            $sLimit = '5';
        }

        if (false === ($sSortBy = $this->_navConf('/datasource/orderby'))) {
            $sSortBy = 'tstamp';
        }

        if (false === ($sSortDir = $this->_navConf('/datasource/orderdir'))) {
            $sSortDir = 'DESC';
        }

        $this->aLimitAndSort = [
            'perpage' => $sLimit,
            'sortcolumn' => $sSortBy,
            'sortdirection' => $sSortDir,
        ];
    }

    public function mayHaveChilds()
    {
        return true;
    }

    public function _getElementHtmlName($sName = false)
    {
        $sRes = parent::_getElementHtmlName($sName);
        $aData = &$this->oForm->oDataHandler->_getListData();

        if (!empty($aData)) {
            $sRes .= '['.$aData['uid'].']';
        }

        return $sRes;
    }

    public function _getElementHtmlNameWithoutFormId($sName = false)
    {
        $sRes = parent::_getElementHtmlNameWithoutFormId($sName);
        $aData = &$this->oForm->oDataHandler->_getListData();

        if (!empty($aData)) {
            $sRes .= '['.$aData['uid'].']';
        }

        return $sRes;
    }

    public function _getElementHtmlId($sId = false)
    {
        $sRes = parent::_getElementHtmlId($sId);

        $aData = &$this->oForm->oDataHandler->_getListData();
        if (!empty($aData)) {
            $sRes .= AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN.$aData['uid'].AMEOSFORMIDABLE_NESTED_SEPARATOR_END;
        }

        return $sRes;
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_ticker/api/class.tx_rdtticker.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_ticker/api/class.tx_rdtticker.php'];
}
