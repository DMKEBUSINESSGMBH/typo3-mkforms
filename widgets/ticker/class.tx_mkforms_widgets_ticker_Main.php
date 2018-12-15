<?php
/**
 * Plugin 'rdt_ticker' for the 'ameos_formidable' extension.
 *
 * @author  Loredana Zeca <typo3dev@ameos.com>
 */
tx_rnbase::load('tx_rnbase_util_Templates');
tx_rnbase::load('tx_rnbase_util_Typo3Classes');

class tx_mkforms_widgets_ticker_Main extends formidable_mainrenderlet
{
    public $aLibs = array(
        'rdt_ticker_class' => 'res/js/ticker.js',
    );

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

        if (($sWidth = $this->_navConf('/width')) === false) {
            $sWidth = '450';
        }

        if (($sHeight = $this->_navConf('/height')) === false) {
            $sHeight = '18';
        }

        if (($sScrollMode = $this->_navConf('/scrolling/mode')) === false || ($sScrollMode !== 'horizontal' && $sScrollMode !== 'vertical')) {
            $sScrollMode = 'horizontal';
        }

        switch ($sScrollMode) {
            case 'horizontal':
                if (($sScrollDirection = $this->_navConf('/scrolling/direction')) === false || ($sScrollDirection !== 'left' && $sScrollDirection !== 'right')) {
                    $sScrollDirection = 'left';
                }
                $this->sSeparatorHtml = "<div id='".$this->_getElementHtmlId().".clear' style='border:medium none; clear:both; font-size:1px; height:1px; line-height:1px;'><hr style='position:absolute; top:-50000px;' /></div>";
                break;
            case 'vertical':
                if (($sScrollDirection = $this->_navConf('/scrolling/direction')) === false || ($sScrollDirection !== 'top' && $sScrollDirection !== 'bottom')) {
                    $sScrollDirection = 'top';
                }
                break;
        }

        if (($sScrollStartDelay = $this->_navConf('/scrolling/startdelay')) === false) {
            $sScrollStartDelay = '2500';
        }

        if (($sScrollNextDelay = $this->_navConf('/scrolling/nextdelay')) === false) {
            $sScrollNextDelay = '100';
        }

        if (($sScrollAmount = $this->_navConf('/scrolling/amount')) === false) {
            $sScrollAmount = ($sScrollDirection === 'top' || $sScrollDirection === 'left') ? -1 : 1;
        } else {
            $sScrollAmount = ($sScrollDirection === 'top' || $sScrollDirection === 'left') ? -($sScrollAmount) : $sScrollAmount;
        }

        if (($sScrollOverflow = $this->_navConf('/scrolling/overflow')) === false) {
            $sScrollOverflow = 'hidden';
        }


        if (($sOffsetTop = $this->_navConf('/offsettop')) === false) {
            $sOffsetTop = '0';
        }

        if (($sOffsetLeft = $this->_navConf('/offsetleft')) === false) {
            $sOffsetLeft = '0';
        }

        if (($sBackground = $this->_navConf('/background')) === false) {
            $sBackground = 'none';
        }
        if (($sBgColor = $this->_navConf('/bgcolor')) === false) {
            $sBgColor = 'transparent';
        }

        if (($sBorder = $this->_navConf('/border')) === false) {
            $sBorder = 'none';
        }
        if (($sBorderColor = $this->_navConf('/bordercolor')) === false) {
            $sBorderColor = 'white';
        }

        $this->aConfig = array(
            'width' => $sWidth,
            'height' => $sHeight,
            'item' => array(
                'width' => $this->_navConf('/itemwidth'),
                'height' => $this->_navConf('/itemheight'),
                'style' => $this->_navConf('/itemstyle'),
            ),
            'scroll' => array(
                'mode' => $sScrollMode,
                'direction' => $sScrollDirection,
                'startDelay' => $sScrollStartDelay,
                'nextDelay' => $sScrollNextDelay,
                'amount' => $sScrollAmount,
                'stop' => (bool)$this->oForm->_isTrueVal($this->_navConf('/scrolling/stop')),
                'overflow' => $sScrollOverflow,
            ),
            'offset' => array(
                'top' => $sOffsetTop,
                'left' => $sOffsetLeft,
            ),
            'background' => $sBackground,
            'bgcolor' => $sBgColor,
            'border' => $sBorder,
            'bordercolor' => $sBorderColor,
        );

        $sLabel = $this->oForm->getConfigXML()->getLLLabel($this->_navConf('/label'));

        $sHtml = $this->_renderList();
        $sBox1 = "<div id='" . $this->_getElementHtmlId() . ".1'>" . $sHtml . '</div>';
        $sBox2 = "<div id='" . $this->_getElementHtmlId() . ".2'>" . $sHtml . '</div>';
        $sHtml = "<div id='" . $this->_getElementHtmlId() . "'>" . $this->_displayLabel($sLabel) . $sBox1 . $sBox2 . '</div>';
        $sHtml = '<div ' . $this->_getAddInputParams() . '>' . $sHtml . '</div>';


        $aHtmlBag =& $this->aConfig;
        $aHtmlBag['__compiled'] = $sHtml;
        $aHtmlBag['html'] = $sHtml;

        // allowed because of $bCustomIncludeScript = TRUE
        $this->includeScripts($this->aConfig);

        return $aHtmlBag;
    }


    public function &_renderList()
    {
        if ($this->aDatasource) {        // if this ticker has a datasource that must be used to generate his content

            if ($this->aDatasource['numrows'] == 0) {
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
                    $sRowHtml = '<div class="ameosformidable-rdtticker-item">' . $sRowHtml . '</div>';
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
        } elseif (($this->_navConf('/html')) !== false) {        // if this ticker has a html as content

            $sHtml = ($this->oForm->isRunneable($this->aElement['html'])) ? $this->getForm()->getRunnable()->callRunnableWidget($this, $this->aElement['html']) : $this->_navConf('/html');
            $sHtml = $this->oForm->_substLLLInHtml($sHtml) . $this->sSeparatorHtml;
        } else {
            $this->oForm->mayday('RENDERLET TICKER <b>' . $this->_getName() . '</b> requires /datasource or /html to be properly set. Please check your XML configuration');
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
        $aRowsTmpl = array();

        if (($sAltRows = $this->_navConf('/template/alternaterows')) !== false && $this->oForm->isRunneable($sAltRows)) {
            $sAltList = $this->getForm()->getRunnable()->callRunnableWidget($this, $sAltRows);
        } elseif (($sAltList = $this->_navConf('/template/alternaterows')) === false) {
            $this->oForm->mayday('RENDERLET TICKER <b>' . $this->_getName() . '</b> requires /template/alternaterows to be properly set. Please check your XML configuration');
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
        if (($aTemplate = $this->_navConf('/template')) !== false) {
            $sPath = Tx_Rnbase_Utility_T3General::getFileAbsFileName($this->oForm->_navConf('/path', $aTemplate));
            if (!file_exists($sPath)) {
                $this->oForm->mayday('renderlet:' . $this->_getType() . '[name=' . $this->getName() . "] - The given template file path (<b>'" . $sPath . "'</b>) doesn't exists.");
            } elseif (is_dir($sPath)) {
                $this->oForm->mayday('renderlet:' . $this->_getType() . '[name=' . $this->getName() . "] - The given template file path (<b>'" . $sPath . "'</b>) is a directory, and should be a file.");
            } elseif (!is_readable($sPath)) {
                $this->oForm->mayday('renderlet:' . $this->_getType() . '[name=' . $this->getName() . '] - The given template file path exists but is not readable.');
            }

            if (($sSubpart = $this->oForm->_navConf('/subpart', $aTemplate)) === false) {
                $sSubpart = $this->getName();
            }

            $sHtml = tx_rnbase_util_Templates::getSubpart(
                Tx_Rnbase_Utility_T3General::getUrl($sPath),
                $sSubpart
            );

            if (trim($sHtml) == '') {
                $this->oForm->mayday('renderlet:' . $this->_getType() . '[name=' . $this->getName() . "] - The given template (<b>'" . $sPath . "'</b> with subpart marquer <b>'" . $sSubpart . "'</b>) <b>returned an empty string</b> - Check your template");
            }

            return $this->oForm->getTemplateTool()->parseTemplateCode(
                $sHtml,
                $aChildsBag,
                array(),
                false
            );
        }
    }

    public function _initDatasource()
    {
        if (($sDsToUse = $this->_navConf('/datasource/use')) !== false) {
            if (!array_key_exists($sDsToUse, $this->oForm->aODataSources)) {
                $this->oForm->mayday('RENDERLET TICKER <b>' . $this->_getName() . "</b> - refers to undefined datasource '" . $sDsToUse . "'. Check your XML conf.");
            } else {
                $this->oDataStream =& $this->oForm->aODataSources[$sDsToUse];
                $this->aDatasource = $this->oDataStream->_fetchData($this->aLimitAndSort);
            }
        }
    }

    public function _initLimitAndSort()
    {
        if (($sLimit = $this->_navConf('/datasource/limit')) === false) {
            $sLimit = '5';
        }

        if (($sSortBy = $this->_navConf('/datasource/orderby')) === false) {
            $sSortBy = 'tstamp';
        }

        if (($sSortDir = $this->_navConf('/datasource/orderdir')) === false) {
            $sSortDir = 'DESC';
        }

        $this->aLimitAndSort = array(
            'perpage' => $sLimit,
            'sortcolumn' => $sSortBy,
            'sortdirection' => $sSortDir,
        );
    }

    public function mayHaveChilds()
    {
        return true;
    }

    public function _getElementHtmlName($sName = false)
    {
        $sRes = parent::_getElementHtmlName($sName);
        $aData =& $this->oForm->oDataHandler->_getListData();

        if (!empty($aData)) {
            $sRes .= '[' . $aData['uid'] . ']';
        }

        return $sRes;
    }

    public function _getElementHtmlNameWithoutFormId($sName = false)
    {
        $sRes = parent::_getElementHtmlNameWithoutFormId($sName);
        $aData =& $this->oForm->oDataHandler->_getListData();

        if (!empty($aData)) {
            $sRes .= '[' . $aData['uid'] . ']';
        }

        return $sRes;
    }

    public function _getElementHtmlId($sId = false)
    {
        $sRes = parent::_getElementHtmlId($sId);

        $aData =& $this->oForm->oDataHandler->_getListData();
        if (!empty($aData)) {
            $sRes .= AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN . $aData['uid'] . AMEOSFORMIDABLE_NESTED_SEPARATOR_END;
        }

        return $sRes;
    }
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_ticker/api/class.tx_rdtticker.php']) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/rdt_ticker/api/class.tx_rdtticker.php']);
}
