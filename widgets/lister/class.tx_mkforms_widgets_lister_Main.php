<?php
/**
 * Plugin 'rdt_lister' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */
tx_rnbase::load('tx_rnbase_util_Templates');
tx_rnbase::load('tx_rnbase_util_Typo3Classes');

class tx_mkforms_widgets_lister_Main extends formidable_mainrenderlet
{
    public $aLibs = [
        'rdt_lister_class' => 'res/js/lister.js',
    ];

    public $uidColumn = false;

    public $sMajixClass = 'Lister';
    public $sAttachPostInitTask = 'init';

    public $iteratingChilds = true;

    public $bCustomIncludeScript = true;

    public $oDataStream = false;
    public $sDsType = false;

    /**
     * @var array|bool
     */
    public $aOColumns = false;

    public $aChilds = false;        // reference to aOColumns

    /**
     * @var array|bool
     */
    public $aPager = false;

    public $aLimitAndSort = false;
    public $bDefaultTemplate = false;
    public $bNoTemplate = false;
    public $bResetPager = false;
    public $mCurrentSelected = false;

    public $aRdtByRow = [];

    public $iCurRowNum = false;
    public $iTempPage = false;

    public $aListerData = false;

    private function fetchListerData($bForce = false)
    {
        $bForce = true; // @TODO: wurde noch nicht getestet, deswegen forcen wir via default
        if (!$bForce && $this->aListerData !== false) {
            return $this->aListerData;
        }

        //wird benötigt um die lister data zu holen
        $this->_initDataStream();
        $this->_initLimitAndSort();

        $ok = false;
        while (!$ok) {
            $aData = $this->_fetchData(
                $aConfig = [
                    'page' => ($this->aLimitAndSort['curpage'] - 1),
                    'perpage' => $this->aLimitAndSort['rowsperpage'],
                    'sortcolumn' => $this->aLimitAndSort['sortby'],
                    'sortdirection' => $this->aLimitAndSort['sortdir'],
                    'iteratingid' => $this->getIteratingId(),
                ]
            );

            $ok = (count($aData['results']) || $this->aLimitAndSort['curpage'] === 0);

            // No data for this page? Try page before if this exists!
            if (!$ok) {
                $this->aLimitAndSort['curpage']--;
            }
        }
        $this->aListerData = $aData;

        return $aData;
    }

    public function _render()
    {
        $this->aRdtByRow = [];

        $aData = $this->fetchListerData();
        if ((int)$aData['numrows'] === 0) {
            if (($mEmpty = $this->_navConf('/ifempty')) !== false) {
                if (is_array($mEmpty)) {
                    if ($this->getForm()->_defaultTrue('/process', $mEmpty) === false) {
                        // nicht verarbeiten!
                        return [
                                '__compiled' => '',
                                'pager.' => ['numrows' => 0,]
                        ];
                    }

                    if ($this->getForm()->_navConf('/message', $mEmpty) !== false) {
                        // dieNachricht auslesen
                        if ($this->getForm()->isRunneable($mEmpty['message'])) {
                            $sMessage = $this->getForm()->getRunnable()->callRunnableWidget($this, $mEmpty['message']);
                        } else {
                            $sMessage = $mEmpty['message'];
                        }
                        $sMessage = $this->getForm()->_substLLLInHtml($sMessage);
                        $sOut = $this->getForm()->getConfigXML()->getLLLabel($sMessage);
                    }

                    // einen wrap um die leer nachricht?
                    if (($mWrap = $this->_navConf('/wrap', $mEmpty)) !== false) {
                        $mWrap = $this->getForm()->getRunnable()->callRunnableWidget($this, $mWrap);
                        $sOut = str_replace('|', $sOut, $mWrap);
                    }
                } else {
                    // nur die nachricht ausgeben
                    $sOut = $this->oForm->getConfigXML()->getLLLabel($sMessage);
                }
                $aHtmlBag = [
                    '__compiled' => $this->_wrapIntoContainer($sOut),
                    'pager.' => ['numrows' => 0,]
                ];
            } else {
                $aHtmlBag = [
                    '__compiled' => '',
                    'pager.' => ['numrows' => 0,]
                ];
            }
        } else {
            $this->_initPager($aData['numrows']);

            $sAddParams = $this->_getAddInputParams();

            $aHtmlBag = [
                '__compiled' => $this->_wrapIntoContainer($this->_renderList($aData), $sAddParams),
                'addparams' => $sAddParams,
                'pager.' => [
                    'display' => ($this->aPager['display'] === true) ? '1' : '0',
                    'page' => $this->aPager['page'],
                    'pagemax' => $this->aPager['pagemax'],
                    'offset' => $this->aPager['offset'],
                    'numrows' => $this->aPager['numrows'],
                    'links.' => [
                        'first' => $this->aPager['links']['first'],
                        'prev' => $this->aPager['links']['prev'],
                        'next' => $this->aPager['links']['next'],
                        'last' => $this->aPager['links']['last'],
                    ],
                    'rowsperpage' => $this->aLimitAndSort['rowsperpage'],
                    'limitoffset' => $this->aLimitAndSort['limitoffset'],
                    'limitdisplayed' => $this->aLimitAndSort['limitdisplayed'],
                    'sortby' => $this->aLimitAndSort['sortby'],
                    'sortdir' => $this->aLimitAndSort['sortdir'],
                ],
            ];
        }

        $this->includeScripts(
            [
                'rdtbyrow' => $this->aRdtByRow,
                'columns' => array_keys($this->aOColumns),
                'isajaxlister' => $this->isAjaxLister(),
                'selected' => $this->mCurrentSelected,
                'sort' => [
                    'column' => $this->aLimitAndSort['sortby'],
                    'direction' => $this->aLimitAndSort['sortdir'],
                ],
                'pages' => $this->aPager['numrows'],
                'repaintfirst' => $this->synthetizeAjaxEventCb(
                    'onclick',
                    "rdt('" . $this->getAbsName() . "').repaintFirst()",
                    false,
                    false
                ),
                'repaintprev' => $this->synthetizeAjaxEventCb(
                    'onclick',
                    "rdt('" . $this->getAbsName() . "').repaintPrev()",
                    false,
                    false
                ),
                'repaintnext' => $this->synthetizeAjaxEventCb(
                    'onclick',
                    "rdt('" . $this->getAbsName() . "').repaintNext()",
                    false,
                    false
                ),
                'repaintlast' => $this->synthetizeAjaxEventCb(
                    'onclick',
                    "rdt('" . $this->getAbsName() . "').repaintLast()",
                    false,
                    false
                ),
                'repainttosite' => $this->synthetizeAjaxEventCb(
                    'onclick',
                    "rdt('" . $this->getAbsName() . "').repaintToSite()",
                    'sys_event.pagenum',
                    false
                ),
                'repaintsortby' => $this->synthetizeAjaxEventCb(
                    'onclick',
                    "rdt('" . $this->getAbsName() . "').repaintSortBy()",
                    'sys_event.sortcol, sys_event.sortdir',
                    false
                ),
            ]
        );

        return $aHtmlBag;
    }

    public function _wrapIntoContainer($sHtml, $sAddParams = '')
    {
        if ($this->isInline()) {
            $sBegin = '<!--BEGIN:LISTER:inline:' . $this->_getElementHtmlId() . '-->';
            $sEnd = '<!--END:LISTER:inline:' . $this->_getElementHtmlId() . '-->';

            return $sBegin . $sHtml . $sEnd;
        } elseif ($this->bDefaultTemplate === true) {
            return '<div id="' . $this->_getElementHtmlId() . '" class="ameosformidable-rdtlister-defaultwrap"' . $sAddParams . '>' . $sHtml . '</div>';
        } elseif ($this->bNoTemplate === true) {
            return $sHtml;
        } else {
            return '<div id="' . $this->_getElementHtmlId() . '"' . $sAddParams . '>' . $sHtml . '</div>';
        }
    }

    public function isInline()
    {
        return $this->_navConf('/mode') === 'inline';
    }

    /**
     * Liefert die DataSource des Listers
     * @return formidable_maindatasource
     */
    public function getDataSource()
    {
        $this->_initDataStream();

        return $this->oDataStream;
    }
    public function _initDataStream()
    {
        if ($this->oDataStream !== false) {
            return;
        }

        if (($sDsToUse = $this->_navConf('/searchform/use')) === false) {
            if (($sDsToUse = $this->_navConf('/datasource/use')) === false) {
                $this->oForm->mayday('RENDERLET LISTER <b>' . $this->_getName() . '</b> - requires <b>/datasource/use</b> OR <b>/searchform/use</b> to be properly set. Check your XML conf.');
            } else {
                if (!array_key_exists($sDsToUse, $this->oForm->aODataSources)) {
                    $this->oForm->mayday('RENDERLET LISTER <b>' . $this->_getName() . '</b> - refers to undefined datasource \'' . $sDsToUse . "'. Check your XML conf.");
                } else {
                    $this->oDataStream =& $this->getForm()->getDataSource($sDsToUse);
                    $this->sDsType = 'datasource';
                }
            }
        } else {
            if ($this->oForm->isRunneable($sDsToUse)) {
                $sDsToUse = $this->getForm()->getRunnable()->callRunnableWidget($this, $sDsToUse);
            }

            $oRdt = $this->getForm()->getWidget($sDsToUse);

            if ($oRdt === false) {
                $this->getForm()->mayday('RENDERLET LISTER - refers to undefined searchform \'' . $sDsToUse . "'. Check your XML conf.");
            } elseif (($sDsType = $oRdt->_getType()) !== 'SEARCHFORM') {
                $this->getForm()->mayday("RENDERLET LISTER - defined searchform <b>'" . $sDsToUse . "'</b> is not of <b>SEARCHFORM</b> type, but of <b>" . $sDsType . '</b> type');
            } else {
                $this->oDataStream =& $this->getForm()->aORenderlets[$oRdt->getAbsName()];
                $this->sDsType = 'searchform';
                if ($this->oDataStream->shouldUpdateCriterias()) {
                    $this->bResetPager = true;
                }
            }
        }
    }

    public function _initLimitAndSort()
    {
        if ($this->aLimitAndSort === false) {
            $iCurPage = $this->_getPage();
            if ($this->iTempPage !== false) {
                $iCurPage = $this->iTempPage;
                $this->iTempPage = false;
            }

            $iRowsPerPage = 5;    // default value

            if (($mRowsPerPage = $this->_navConf('/pager/rows/perpage')) !== false) {
                if ($this->oForm->isRunneable($mRowsPerPage)) {
                    $mRowsPerPage = $this->getForm()->getRunnable()->callRunnableWidget($this, $mRowsPerPage);
                }

                if ((int)$mRowsPerPage > 0) {
                    $iRowsPerPage = $mRowsPerPage;
                } elseif ((int)$mRowsPerPage === -1) {
                    $iRowsPerPage = 1000000;
                }
            }

            $aSort = $this->_getSortColAndDirection();

            if (trim($aSort['col']) !== '' && array_key_exists($aSort['col'], $this->aOColumns)) {
                if (($sRealSortCol = $this->aOColumns[$aSort['col']]->_navConf('/sortcol')) === false) {
                    $sRealSortCol = $aSort['col'];
                }
            } else {
                $sRealSortCol = $aSort['col'];
            }

            $this->aLimitAndSort = [
                'curpage' => $iCurPage,
                'rowsperpage' => $iRowsPerPage,
                'limitoffset' => ($iCurPage - 1) * $iRowsPerPage,
                'limitdisplayed' => $iRowsPerPage,
                'sortby' => $sRealSortCol,
                'sortdir' => $aSort['dir'],
            ];
        }
    }

    public function getPageForLineNumber($iNum)
    {
        if ((int)$this->aLimitAndSort['rowsperpage'] !== 0) {
            $iPageMax = (ceil($iNum / $this->aLimitAndSort['rowsperpage']));
        } else {
            $iPageMax = 0;
        }

        return $iPageMax;
    }

    public function shouldAvoidPageOneInUrl()
    {
        return $this->defaultTrue('/pager/avoidpageoneinurl');
    }

    public function _initPager($iNumRows)
    {
        $iPageMax = $this->getPageForLineNumber($iNumRows);
        $bDisplay = ($iPageMax > 1 || $this->_defaultFalse('/pager/alwaysdisplay'));

        // generating javascript links & functions
        $sLinkFirst = $sLinkPrev = $sLinkNext = $sLinkLast = '';

        if ($iPageMax >= 1) {
            if ($this->aLimitAndSort['curpage'] > 1) {
                if ($this->shouldAvoidPageOneInUrl()) {
                    $sLinkFirst = $this->_buildLink([], ['page' => 1]);
                } else {
                    $sLinkFirst = $this->_buildLink(['page' => 1]);
                }

                if ($this->aLimitAndSort['curpage'] > 2) {
                    $sLinkPrev = $this->_buildLink([
                        'page' => $this->aLimitAndSort['curpage'] - 1
                    ]);
                } else {
                    $sLinkPrev = $sLinkFirst;
                }
            }

            // print 'next' link only if we're not
            // on the last page

            if ($this->aLimitAndSort['curpage'] < $iPageMax) {
                $sLinkNext = $this->_buildLink([
                    'page' => ($this->aLimitAndSort['curpage'] + 1)
                ]);

                $sLinkLast = $this->_buildLink([
                    'page' => $iPageMax
                ]);
            }
        }

        $iPage = ($iPageMax == 0) ? 0 : $this->aLimitAndSort['curpage'];
        $bAlwaysFullWidth = false;

        $aWindow = [];

        if (($mWindow = $this->_navConf('/pager/window')) !== false && $iNumRows > 0) {
            if ($this->oForm->isRunneable($mWindow)) {
                $iWindowWidth = $this->getForm()->getRunnable()->callRunnableWidget($this, $mWindow);
            } elseif (is_array($mWindow) && (($mWidth = $this->_navConf('/pager/window/width')) !== false)) {
                if ($this->oForm->isRunneable($mWidth)) {
                    $mWidth = $this->getForm()->getRunnable()->callRunnableWidget($this, $mWidth);
                }

                $iWindowWidth = (int)$mWidth;

                if (($mAlwaysFullWidth = $this->_defaultFalse('/pager/window/alwaysfullwidth')) !== false) {
                    if ($this->oForm->isRunneable($mAlwaysFullWidth)) {
                        $mAlwaysFullWidth = $this->getForm()->getRunnable()->callRunnableWidget($this, $mAlwaysFullWidth);
                    }

                    $bAlwaysFullWidth = $mAlwaysFullWidth;
                }
            } else {
                $iWindowWidth = $mWindow;
            }

            if ($iWindowWidth !== false) {
                // generating something like < 24 25 *26* 27 28 >

                /*
                    window pager patch by Manuel Rego Casasnovas
                    @see http://lists.netfielders.de/pipermail/typo3-project-formidable/2008-January/000816.html
                */


                $iStart = $iPage - ($iWindowWidth - 1);
                if ($iStart < 1) {
                    $iStart = 1;
                }

                if ($iStart == 1) {
                    $sLinkFirst = '';
                }

                $iEnd = $iPage + ($iWindowWidth - 1);

                if ($iEnd > $iPageMax) {
                    $iEnd = $iPageMax;
                }

                if ($iEnd == $iPageMax) {
                    $sLinkLast = '';
                }

                if (($iPageMax + 1) < $iWindowWidth) {
                    $iEnd = $iWindowWidth;
                }


                for ($k = $iStart; $k <= $iEnd; $k++) {
                    if ($k <= $iPageMax) {
                        $aWindow[$k] = $this->_buildLink([
                            'page' => $k
                        ]);
                    }
                }
            }
        }

        $this->aPager = [
            'display'    =>    $bDisplay,
            'numrows'    =>    $iNumRows,
            'offset'    =>    $this->aLimitAndSort['limitoffset'],
            'page'        =>    $iPage,
            'pagemax'    =>    $iPageMax,
            'rowsperpage' => $this->aLimitAndSort['rowsperpage'],
            'links'        =>    [
                'first'        =>    $sLinkFirst,
                'prev'        =>    $sLinkPrev,
                'next'        =>    $sLinkNext,
                'last'        =>    $sLinkLast,
            ],
            'window'    =>    $aWindow,
            'alwaysfullwidth' => $bAlwaysFullWidth,
        ];
    }

    public function _buildLink($aParams, $aExcludeParams = [])
    {
        $aRdtParams = [
            $this->oForm->formid => [
                $this->_getElementHtmlId() => $aParams
            ]
        ];

        $sEnvMode = tx_mkforms_util_Div::getEnvExecMode();
        if ($sEnvMode === 'BE') {
            $sBaseUrl = Tx_Rnbase_Utility_T3General::getIndpEnv('TYPO3_REQUEST_URL');
            $aQueryParts = parse_url($sBaseUrl);
            $aParams = [];
            if ($aQueryParts['query']) {
                parse_str($aQueryParts['query'], $aParams);
            }
        } elseif ($sEnvMode === 'EID') {
            $sBaseUrl = Tx_Rnbase_Utility_T3General::getIndpEnv('HTTP_REFERER');
            $aQueryParts = parse_url($sBaseUrl);
            $aParams = [];
            if ($aQueryParts['query']) {
                parse_str($aQueryParts['query'], $aParams);
            }
        } elseif ($sEnvMode === 'FE') {
            $aParams = Tx_Rnbase_Utility_T3General::_GET();
        }


        $aFullParams = tx_rnbase_util_Arrays::mergeRecursiveWithOverrule(
            $aParams,
            $aRdtParams
        );


        if (!empty($aExcludeParams) || !empty($this->oForm->aParamsToRemove)) {
            $aRdtParamsExclude = [
                $this->oForm->formid => [
                    $this->_getElementHtmlId() => $aExcludeParams
                ]
            ];


            $aRdtParamsExclude = tx_rnbase_util_Arrays::mergeRecursiveWithOverrule(
                $aRdtParamsExclude,
                $this->oForm->aParamsToRemove
            );
                // excluding also params that have been marked as "please remove"
                // like what's done for the form action when setting get-params
                // to alter the search

            $aPathes = $this->oForm->implodePathesForArray($aRdtParamsExclude);
            foreach ($aPathes as $sPath) {
                $this->oForm->unsetDeepData(
                    $sPath,
                    $aFullParams
                );
            }
        }

        if (array_key_exists('cHash', $aFullParams)) {
            unset($aFullParams['cHash']);
        }

        if ($this->oForm->_defaultFalse('/cachehash', $this->aElement) === true) {
            $aFullParams['cHash'] = Tx_Rnbase_Utility_T3General::shortMD5(
                serialize(
                    Tx_Rnbase_Utility_T3General::cHashParams(
                        Tx_Rnbase_Utility_T3General::implodeArrayForUrl('', $aFullParams)
                    )
                )
            );
        }

        if ($sEnvMode === 'BE' || $sEnvMode === 'EID') {
            return $this->oForm->xhtmlUrl(
                Tx_Rnbase_Utility_T3General::linkThisUrl(
                    $sBaseUrl,
                    $aFullParams
                )
            );
        } elseif ($sEnvMode === 'FE') {
            if (array_key_exists('id', $aFullParams)) {
                unset($aFullParams['id']);
            }

            return $this->getForm()->getCObj()->typoLink_URL([
                'parameter' => $GLOBALS['TSFE']->id,
                'additionalParams' => Tx_Rnbase_Utility_T3General::implodeArrayForUrl(
                    '',
                    $aFullParams
                )
            ]);
        }
    }

    public function &_fetchData($aConfig = false, $aFilters = false)
    {
        if ($aConfig === false) {
            $this->_initDataStream(); // Datenquelle laden
            $this->initColumns();    // Widgets für Spalten erstellen
            $this->aLimitAndSort = false;
            $this->_initLimitAndSort();

            $aConfig = [
                    'page' => ($this->aLimitAndSort['curpage'] - 1),
                    'perpage' => $this->aLimitAndSort['rowsperpage'],
                    'sortcolumn' => $this->aLimitAndSort['sortby'],
                    'sortdirection' => $this->aLimitAndSort['sortdir'],
            ];
        }

        $aFilters = is_array($aFilters) ? $aFilters : [];

        // zusätzliche parameter besorgen
        $aParams = $this->_navConf('/datasource/config');
        $aParams = $aParams === false ? $this->_navConf('/datasource/params') : $aParams;
        $aConfig = is_array($aParams) ? $this->getForm()->getRunnable()
                ->parseParams($aParams, $aConfig) : $aConfig;

        return $this->getDataSource()->fetchData($aConfig, $aFilters);
    }

    public function &_renderList(&$aRows)
    {
        $aTemplate = $this->_getTemplate();
        $this->_renderList_displayRows($aTemplate, $aRows);
        $this->_renderList_displayPager($aTemplate);
        $this->_renderList_displaySortHeaders($aTemplate);

        foreach ($this->aOColumns as $sColumn => $_) {
            $aTemplate['html'] = str_replace(
                '{' . $sColumn . '.label}',
                $this->getListHeader($sColumn),
                $aTemplate['html']
            );
        }

        $aTemplate['html'] = $this->oForm->_substLLLInHtml($aTemplate['html']);

        // including styles and CSS files

        if ($aTemplate['styles'] !== '') {
            if ($this->bDefaultTemplate === true) {
                $sComment = 'Stylesheet of DEFAULT TEMPLATE for renderlet:LISTER ' . $this->_getName();
                $sKey = 'tx_ameosformidable_renderletlister_defaultstyle';
            } else {
                $sComment = 'Dynamic stylesheet for renderlet:LISTER ' . $this->_getName();
                $sKey = 'tx_ameosformidable_renderletlister_dynamicstyle_' . $this->_getName();
            }

            $this->oForm->additionalHeaderData(
                $this->oForm->inline2TempFile($aTemplate['styles'], 'css', $sComment),
                $sKey
            );
        }

        if ($aTemplate['cssfile'] !== '') {
            $this->oForm->additionalHeaderData(
                '<!-- CSS file for renderlet:LISTER ' . $this->_getName() . " -->\n<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $aTemplate['cssfile'] . "\" />\n\n",
                'tx_ameosformidable_renderletlister_cssfile_' . $this->_getName()
            );
        }

        return $aTemplate['html'];
    }

    public function getListHeader($sColumn)
    {
        if (($sLabel = $this->aOColumns[$sColumn]->_navConf('/listheader')) === false) {
            $sAutoMap = 'LLL:' . $this->aOColumns[$sColumn]->getAbsName() . '.listheader';
            if ($this->oForm->sDefaultLLLPrefix !== false && (($sAutoLabel = $this->oForm->getConfigXML()->getLLLabel($sAutoMap)) !== '')) {
                return $sAutoLabel;
            }

            if (($sLabel = $this->aOColumns[$sColumn]->getLabel()) === '') {
                return '';
            }
        }

        $sLabel = $this->getForm()->getRunnable()->callRunnable($sLabel);

        return $this->oForm->getConfigXML()->getLLLabel($sLabel);
    }

    public function _renderList_displayRows(&$aTemplate, &$aRows)
    {
        $aRowsHtml = [];
        if ($this->bNoTemplate !== true) {
            $aAltRows = [];
            $aRowsHtml = [];
            $sRowsPart = tx_rnbase_util_Templates::getSubpart($aTemplate['html'], '###ROWS###');

            if ($aTemplate['default'] === true) {
                $sAltList = '###ROW1###, ###ROW2###';
            } elseif (($sAltRows = $aTemplate['alternaterows']) !== false && $this->oForm->isRunneable($sAltRows)) {
                $sAltList = $this->getForm()->getRunnable()->callRunnableWidget($this, $sAltRows);
            } elseif (($sAltList = $aTemplate['alternaterows']) === false) {
                $this->oForm->mayday('RENDERLET LISTER <b>' . $this->_getName() . '</b> requires /template/alternaterows to be properly set. Please check your XML configuration');
            }

            $aAltList = Tx_Rnbase_Utility_Strings::trimExplode(',', $sAltList);
            foreach ($aAltList as $sAltSubpart) {
                $aAltRows[] = tx_rnbase_util_Templates::getSubpart($sRowsPart, $sAltSubpart);
            }

            $iNbAlt = sizeof($aAltRows);
        }

        foreach ($this->aOColumns as $column) {
            $column->doBeforeListRender($this);
        }

        $iRowNum = 0;
        $this->iCurRowNum = 0;

        $aTableCols = false;
        reset($aRows);
        foreach ($aRows['results'] as $iIndex => $aCurRow) {
            $this->iCurRowNum = $iRowNum;
            $iRowUid = $aCurRow[$this->getUidColumn()];

            if ($aTableCols === false) {
                $aTableCols = array_keys($aCurRow);
                reset($aTableCols);
            }

            $this->__aCurRow = $aCurRow;
            array_push($this->oForm->oDataHandler->__aListData, $aCurRow);
            $aCurRow = $this->processBeforeRender($aCurRow);
            $aCurRow = $this->_refineRow($aCurRow);
            $aCurRow = $this->processBeforeDisplay($aCurRow);

            $this->__aCurRow = [];

            $aCurRow = $this->filterUnprocessedColumns($aCurRow, $aTableCols);

            if ($this->bNoTemplate === true) {
                foreach ($this->aOColumns as $sCol => $_) {
                    $sRowHtml = $aCurRow[$sCol]['__compiled'];
                }
            } else {
                if ($this->mCurrentSelected !== false && $iRowUid == $this->mCurrentSelected) {
                    $aCurRow['rowclass'] = 'row-selected';
                } else {
                    $aCurRow['rowclass'] = 'row-unselected ';
                }

                $sRowHtml = $this->oForm->getTemplateTool()->parseTemplateCode(
                    $aAltRows[$iRowNum % $iNbAlt],        // current alternate subpart for row
                    $aCurRow
                );
            }

            $aRowsHtml[] = $this->rowWrap($sRowHtml);
            array_pop($this->oForm->oDataHandler->__aListData);

            $iRowNum++;
        }

        $this->iCurRowNum = false;

        $aColKeys = array_keys($this->aOColumns);
        reset($aColKeys);
        // Jetzt wird der JS-Code für die Widgets in der Liste eingefügt. Diese werden als "iterating" gekennzeichnet.
        foreach ($this->aOColumns as $sName => $widget) {
            $widget->doAfterListRender($this);
        }

        if ($this->bNoTemplate === false) {
            if ($this->_defaultTrue('/template/allowincompletesequence') === false) {
                $iNbResultsOnThisPage = count($aRows['results']);
                if (($iNbResultsOnThisPage % $iNbAlt) !== 0) {
                    for ($k = $iRowNum % $iNbAlt; $k < $iNbAlt; $k++) {
                        $aRowsHtml[] = $this->oForm->getTemplateTool()->parseTemplateCode(
                            $aAltRows[$k],        // current alternate subpart for row
                            [],
                            [],
                            false
                        );
                    }
                }
            }

            $aTemplate['html'] = tx_rnbase_util_Templates::substituteSubpart(
                $aTemplate['html'],
                '###ROWS###',
                implode('', $aRowsHtml),
                false,
                false
            );
        } else {
            $aTemplate['html'] = implode($aRowsHtml);
        }
    }

    public function rowWrap($sHtmlRow)
    {
        if (($sWrap = $this->_navConf('/columns/wrap')) !== false) {
            if ($this->oForm->isRunneable($sWrap)) {
                $sWrap = $this->getForm()->getRunnable()->callRunnableWidget($this, $sWrap);
            }

            if (is_string($sWrap)) {
                return str_replace('|', $sHtmlRow, $this->oForm->_substLLLInHtml($sWrap));
            }
        }

        return $sHtmlRow;
    }

    public function processBeforeRender($aRow)
    {
        if (($aBeforeRender = $this->_navConf('/beforerender')) !== false && $this->oForm->isRunneable($aBeforeRender)) {
            $aRow = $this->getForm()->getRunnable()->callRunnableWidget($this, $aBeforeRender, $aRow);
        }

        return $aRow;
    }

    public function processBeforeDisplay($aRow)
    {
        if (($aBeforeDisplay = $this->_navConf('/beforedisplay')) !== false && $this->oForm->isRunneable($aBeforeDisplay)) {
            $aRow = $this->getForm()->getRunnable()->callRunnableWidget($this, $aBeforeDisplay, $aRow);
        }

        return $aRow;
    }

    /**
     * @param array $aRow
     * @param array $aDataSetCols
     *
     * @return mixed
     */
    public function filterUnprocessedColumns($aRow, $aDataSetCols)
    {
        foreach ($aRow as $sKey => $_) {
            if ($sKey !== $this->getUidColumn() && !array_key_exists($sKey, $this->aOColumns) && in_array($sKey, $aDataSetCols)) {
                unset($aRow[$sKey]);
            }
        }

        reset($aRow);

        return $aRow;
    }

    public function _renderList_displayPager(&$aTemplate)
    {
        $sHtmlId = $this->_getElementHtmlId();
        $sPagerHtmlId = $sHtmlId.'_pager';

        if (($mHtml = $this->_navConf('/pager/html')) !== false) {
            if ($this->oForm->isRunneable($mHtml)) {
                $mHtml = $this->getForm()->getRunnable()->callRunnableWidget($this, $mHtml, $this->aPager);
            }

            $sPager = $mHtml;
        } elseif ($this->aPager['display'] === true) {
            $sPager = $aTemplate['pager'];
            $aLinks = [];

            $sPager = $this->_parseThrustedTemplateCode(
                $sPager,
                [
                    'page' => $this->aPager['page'],
                    'pagemax' => $this->aPager['pagemax'],
                    'numrows' => $this->aPager['numrows']
                ],
                [],
                false
            );


            foreach ($this->aPager['links'] as $sWhich => $sLink) {
                if ($sLink !== '') {
                    $aLinks[$sWhich] = $this->oForm->getTemplateTool()->parseTemplateCode(
                        tx_rnbase_util_Templates::getSubpart($sPager, '###LINK' . strtoupper($sWhich) . '###'),
                        [
                            'link' => $sLink,
                            'linkid' => $sHtmlId . '_pagelink_' . strtolower($sWhich)
                        ],
                        [],
                        false
                    );
                } else {
                    $aLinks[$sWhich] = '';
                }
            }

            $sPager = tx_rnbase_util_Templates::substituteSubpart($sPager, '###LINKFIRST###', $aLinks['first'], false, false);
            $sPager = tx_rnbase_util_Templates::substituteSubpart($sPager, '###LINKPREV###', $aLinks['prev'], false, false);
            $sPager = tx_rnbase_util_Templates::substituteSubpart($sPager, '###LINKNEXT###', $aLinks['next'], false, false);
            $sPager = tx_rnbase_util_Templates::substituteSubpart($sPager, '###LINKLAST###', $aLinks['last'], false, false);

            // generating window
            $sWindow = '';
            if (!empty($this->aPager['window'])) {
                $sWindow = tx_rnbase_util_Templates::getSubpart($aTemplate['pager'], '###WINDOW###');
                $sLinkNo = tx_rnbase_util_Templates::getSubpart($sWindow, '###NORMAL###');
                $sLinkAct = tx_rnbase_util_Templates::getSubpart($sWindow, '###ACTIVE###');
                $sMoreBefore = tx_rnbase_util_Templates::getSubpart($sWindow, '###MORE_BEFORE###');
                $sMoreAfter = tx_rnbase_util_Templates::getSubpart($sWindow, '###MORE_AFTER###');
                if ($this->aPager['alwaysfullwidth'] === true) {
                    if (trim(($sLinkDisabled = tx_rnbase_util_Templates::getSubpart($sWindow, '###DISABLED###'))) === '') {
                        $this->oForm->mayday(
                            'RENDERLET ' . $this->_getType() . ' <b>' . $this->_getName() . '</b> - In your pager\'s template, you have to provide a <b>###DISABLED###</b> subpart inside the <b>###WINDOW###</b> subpart when defining <b>/window/alwaysFullWidth=TRUE</b>'
                        );
                    }
                }

                $aLinks = [];

                reset($this->aPager['window']);
                if (key($this->aPager['window']) > 2 && trim($sMoreBefore) !== '') {
                    $aLinks[] = $sMoreBefore;
                }

                foreach ($this->aPager['window'] as $iPageNum => $sLink) {
                    $sPageNumLinkHtmlId = $sPagerHtmlId . '_' . $iPageNum;

                    if ($sLink === false) {
                        $aLinks[] = $this->_parseThrustedTemplateCode(
                            $sLinkAct,
                            [
                                'link' => $sLink,
                                'page' => $iPageNum,
                                'id' => $sPageNumLinkHtmlId,
                            ]
                        );
                    } elseif ($this->aPager['page'] == $iPageNum) {
                        $aLinks[] = $this->_parseThrustedTemplateCode(
                            $sLinkAct,
                            [
                                'link' => $sLink,
                                'page' => $iPageNum,
                                'id' => $sPageNumLinkHtmlId,
                            ]
                        );
                    } else {
                        $aLinks[] = $this->_parseThrustedTemplateCode(
                            $sLinkNo,
                            [
                                'link' => $sLink,
                                'page' => $iPageNum,
                                'id' => $sPageNumLinkHtmlId,
                            ]
                        );
                    }
                }

                end($this->aPager['window']);
                if ((key($this->aPager['window']) < ($this->aPager['pagemax'] - 1)) && trim($sMoreAfter) !== '') {
                    $aLinks[] = $sMoreAfter;
                }

                $sLinks = implode(' ', $aLinks);

                $sWindow = tx_rnbase_util_Templates::substituteSubpart($sWindow, '###WINDOWLINKS###', $sLinks, false, false);
            }

            $sPager = tx_rnbase_util_Templates::substituteSubpart($sPager, '###WINDOW###', $sWindow, false, false);

            $sPager = '<div id="'.$sPagerHtmlId.'">'.$sPager.'</div>';
        } else {
            $sPager = '';
        }

        $aTemplate['html'] = $this->_parseThrustedTemplateCode(
            $aTemplate['html'],
            [
                'PAGER' => $sPager
            ],
            [],
            false
        );
    }

    private function _parseThrustedTemplateCode($sHtml, $aTags, $aExclude = [], $bClearNotUsed = true, $aLabels = [])
    {
        return $this->getForm()->getTemplateTool()->parseTemplateCode($sHtml, $aTags, $aExclude, $bClearNotUsed, $aLabels, $bThrusted = true);
    }

    public function _renderList_displaySortHeaders(&$aTemplate)
    {
        $sListHtmlId = $this->_getElementHtmlId();

        foreach ($this->aOColumns as $sColumn => $_) {
            $sSubpart = '###SORT_' . $sColumn . '###';

            if (($sSortHtml = trim(tx_rnbase_util_Templates::getSubpart($aTemplate['html'], $sSubpart))) != '') {
                $sSortHtml = $this->oForm->_substLLLInHtml($sSortHtml);

                if ($this->aOColumns[$sColumn]->_defaultTrue('/sort') === true) {
                    $sNewDir = 'asc';
                    $sLabelDir = '';
                    $sCssClass = 'sort-no';
                    $sSortSymbol = '';

                    if (($this->aLimitAndSort['sortby'] === $sColumn)) {
                        if ($this->aLimitAndSort['sortdir'] === 'desc') {
                            $sNewDir = 'asc';
                            $sLabelDir = (($this->aTemplate['default'] === true) ? ' [Z-a]' : '');
                            $sCssClass = 'sort-act-desc';
                            $sSortSymbol = '&#x25BC;';
                        } else {
                            $sNewDir = 'desc';
                            $sLabelDir = (($this->aTemplate['default'] === true) ? ' [a-Z]' : '');
                            $sCssClass = 'sort-act-asc';
                            $sSortSymbol = '&#x25B2;';
                        }
                    }

                    $sLink = $this->_buildLink([
                        'sort' => $sColumn . '-' . $sNewDir
                    ]);

                    if (($sHeader = $this->getListHeader($sColumn)) !== '') {
                        $sAccesTitle = '{LLL:EXT:ameos_formidable/api/base/rdt_lister/res/locallang/locallang.xml:sortby} &quot;' . strip_tags($sHeader) . '&quot; {LLL:EXT:ameos_formidable/api/base/rdt_lister/res/locallang/locallang.xml:sort.' . $sNewDir . '}';
                    } else {
                        $sAccesTitle = '{LLL:EXT:ameos_formidable/api/base/rdt_lister/res/locallang/locallang.xml:sort} {LLL:EXT:ameos_formidable/api/base/rdt_lister/res/locallang/locallang.xml:sort.' . $sNewDir . '}';
                    }

                    if (($this->defaultFalse('pager/sort/useunicodegeometricshapes')) == false) {
                        $sSortSymbol = '';
                    }

                    $sTag = '<a id="' . $sListHtmlId . '_sortlink_' . $sColumn . '" href="' . $sLink . '" title="' . $sAccesTitle . '" class="' . $sColumn . '_sort ' . $sCssClass . '">' . $sSortHtml . $sLabelDir . $sSortSymbol . '</a>';
                } else {
                    $sTag = $sSortHtml;
                }

                $aTemplate['html'] = tx_rnbase_util_Templates::substituteSubpart(
                    $aTemplate['html'],
                    $sSubpart,
                    $sTag,
                    false,
                    false
                );
            }
        }
    }

    public function &_getTemplate()
    {
        $aRes = [
            'default' => false,
            'html' => '',
            'styles' => '',
            'cssfile' => '',
            'pager' => '',
            'alternaterows' => false,
        ];

        if ((($aTemplate = $this->_navConf('/template')) === false) || (($this->bNoTemplate = $this->_defaultFalse('/template/notemplate')) === true)) {
            if ($this->bNoTemplate === false) {
                // no template defined, building default lister template
                $aRes = $this->__buildDefaultTemplate();
                $this->bDefaultTemplate = true;
                $this->bNoTemplate = false;
            } else {
                $aRes = [
                    'default' => false,
                ];

                $this->bDefaultTemplate = false;
                $this->bNoTemplate = true;
            }
        } else {
            if ($this->oForm->isRunneable($aTemplate)) {
                $aTemplate = $this->getForm()->getRunnable()->callRunnableWidget($this, $aTemplate);
            }

            if (is_array($aTemplate) && array_key_exists('path', $aTemplate)) {
                if ($this->oForm->isRunneable($aTemplate['path'])) {
                    $aTemplate['path'] = $this->getForm()->getRunnable()->callRunnableWidget($this, $aTemplate['path']);
                }
            } else {
                $this->oForm->mayday('RENDERLET LISTER <b>' . $this->_getName() . '</b> - Template defined, but <b>/template/path</b> is missing. Please check your XML configuration');
            }

            if (is_array($aTemplate) && array_key_exists('subpart', $aTemplate)) {
                if ($this->oForm->isRunneable($aTemplate['subpart'])) {
                    $aTemplate['subpart'] = $this->getForm()->getRunnable()->callRunnableWidget($this, $aTemplate['subpart']);
                }
            } else {
                $this->oForm->mayday('RENDERLET LISTER <b>' . $this->_getName() . '</b> - Template defined, but <b>/template/subpart</b> is missing. Please check your XML configuration');
            }

            if (is_array($aTemplate) && array_key_exists('alternaterows', $aTemplate)) {
                if ($this->oForm->isRunneable($aTemplate['alternaterows'])) {
                    $aTemplate['alternaterows'] = $this->getForm()->getRunnable()->callRunnableWidget($this, $aTemplate['alternaterows']);
                }
                $aRes['alternaterows'] = $aTemplate['alternaterows'];
            }

            $aTemplate['path'] = tx_mkforms_util_Div::toServerPath($aTemplate['path']);


            if (file_exists($aTemplate['path'])) {
                if (is_readable($aTemplate['path'])) {
                    $aRes['html'] = tx_rnbase_util_Templates::getSubpart(
                        Tx_Rnbase_Utility_T3General::getUrl($aTemplate['path']),
                        $aTemplate['subpart']
                    );

                    if (trim($aRes['html']) === '') {
                        $this->_autoTemplateMayday($aTemplate, true);
                    }
                } else {
                    $this->oForm->mayday('RENDERLET LISTER <b>' . $this->_getName() . '</b> - the given template file \'<b>' . $aTemplate['path'] . "</b>' isn't readable. Please check permissions for this file.");
                }
            } else {
                $this->_autoTemplateMayday($aTemplate);
            }

            /* managing styles and CSS file */

            if (array_key_exists('cssfile', $aTemplate)) {
                if ($this->oForm->isRunneable($aTemplate['cssfile'])) {
                    $aTemplate['cssfile'] = $this->getForm()->getRunnable()->callRunnableWidget($this, $aTemplate['cssfile']);
                }

                $aRes['cssfile'] = tx_mkforms_util_Div::toWebPath($aTemplate['cssfile']);
            }

            /* styles after css-file to eventually override css-file directives */
            if (array_key_exists('styles', $aTemplate)) {
                if ($this->oForm->isRunneable($aTemplate['styles'])) {
                    $aTemplate['styles'] = $this->getForm()->getRunnable()->callRunnableWidget($this, $aTemplate['styles']);
                }

                $aRes['styles'] = $aTemplate['styles'];
            }


            /* get pager */

            if (($aPagerTemplate = $this->_navConf('/pager/template')) !== false) {
                if (is_array($aPagerTemplate) && array_key_exists('path', $aPagerTemplate)) {
                    if ($this->oForm->isRunneable($aPagerTemplate['path'])) {
                        $aPagerTemplate['path'] = $this->getForm()->getRunnable()->callRunnableWidget($this, $aPagerTemplate['path']);
                    }
                } else {
                    $this->oForm->mayday('RENDERLET LISTER <b>' . $this->_getName() . '</b> - Template for PAGER is defined, but <b>/pager/template/path</b> is missing. Please check your XML configuration');
                }

                if (is_array($aPagerTemplate) && array_key_exists('subpart', $aPagerTemplate)) {
                    if ($this->oForm->isRunneable($aPagerTemplate['subpart'])) {
                        $aPagerTemplate['subpart'] = $this->getForm()->getRunnable()->callRunnableWidget($this, $aPagerTemplate['subpart']);
                    }
                } else {
                    $this->oForm->mayday('RENDERLET LISTER <b>' . $this->_getName() . '</b> - Template for PAGER defined, but <b>/pager/template/subpart</b> is missing. Please check your XML configuration');
                }

                $aPagerTemplate['path'] = tx_mkforms_util_Div::toServerPath($aPagerTemplate['path']);

                if (file_exists($aPagerTemplate['path'])) {
                    if (is_readable($aPagerTemplate['path'])) {
                        $aRes['pager'] = tx_rnbase_util_Templates::getSubpart(
                            Tx_Rnbase_Utility_T3General::getUrl($aPagerTemplate['path']),
                            $aPagerTemplate['subpart']
                        );

                        if (trim($aRes['pager']) === '') {
                            $this->_autoPagerMayday($aPagerTemplate, true);
                        }
                    } else {
                        $this->oForm->mayday('RENDERLET LISTER <b>' . $this->_getName() . '</b> - the given template file for PAGER \'<b>' . $aPagerTemplate['path'] . "</b>' isn't readable. Please check permissions for this file.");
                    }
                } else {
                    $this->_autoPagerMayday($aPagerTemplate);
                }
            }
        }

        reset($aRes);

        return $aRes;
    }

    public function _autoTemplateMayday($aTemplate, $bSubpartError = false)
    {

        /* ERROR message with automatic generated TEMPLATE and CSS */
        $aDefaultTemplate = $this->__buildDefaultTemplate('#' . $this->_getElementHtmlId());

        $sDefaultTemplate = htmlspecialchars($aDefaultTemplate['html']);
        $sDefaultStyles = htmlspecialchars($aDefaultTemplate['styles']);

        $sError = $bSubpartError ?
            'RENDERLET LISTER <b>' . $this->_getName() . "</b> - the given SUBPART '<b>" . $aTemplate['subpart'] . "</b>' doesn't exists." : 'RENDERLET LISTER <b>' . $this->_getName() . "</b> - the given TEMPLATE FILE '<b>" . $aTemplate['path'] . "</b>' doesn't exists.";

        $sMessage = <<<ERRORMESSAGE

	<div>{$sError}</div>
	<hr />
	<div>If you're going to create this template, these automatically generated html template and styles might be usefull</div>
	<h2>Automatic LIST template</h2>
	<div>Copy/paste this in <b>{$aTemplate["path"]}</b></div>
	<div style='color: black; background-color: #e6e6fa; border: 2px dashed #4682b4; font-family: Courier;'>
		<br />
<pre>
&lt;!-- {$aTemplate["subpart"]} begin--&gt;

{$sDefaultTemplate}

&lt;!-- {$aTemplate["subpart"]} end--&gt;
</pre>
		<br /><br />
	</div>
	<h2>Automatic CSS</h2>
	<div style='color: black; background-color: #e6e6fa; border: 2px dashed #4682b4;'><pre>{$sDefaultStyles}</pre></div>

ERRORMESSAGE;

        $this->oForm->mayday($sMessage);
    }

    public function _autoPagerMayday($aTemplate, $bSubpartError = false)
    {

        /* ERROR message for PAGER with automatic generated TEMPLATE */

        $sDefaultPager = htmlspecialchars($this->__getDefaultPager());

        $sError = $bSubpartError ?
            'RENDERLET LISTER <b>' . $this->_getName() . "</b> - the given SUBPART for PAGER '<b>" . $aTemplate['subpart'] . "</b>' doesn't exists." : 'RENDERLET LISTER <b>' . $this->_getName() . "</b> - the given TEMPLATE FILE for PAGER '<b>" . $aTemplate['path'] . "</b>' doesn't exists.";

        $sMessage = <<<ERRORMESSAGE

	<div>{$sError}</div>
	<hr />
	<div>If you're going to create this template, these automatically generated html template might be usefull</div>
	<h2>Automatic PAGER template</h2>
	<div>Copy/paste this in <b>{$aTemplate["path"]}</b></div>
	<div style='color: black; background-color: #e6e6fa; border: 2px dashed #4682b4; font-family: Courier;'>
		<br />
<pre>
&lt;!-- {$aTemplate["subpart"]} begin--&gt;
{$sDefaultPager}
&lt;!-- {$aTemplate["subpart"]} end--&gt;
</pre>
<br /><br />
	</div>

ERRORMESSAGE;

        $this->oForm->mayday($sMessage);
    }

    public function &__getDefaultPager()
    {
        $sPath = $this->sExtPath . 'res/html/default-template.html';
        $sSubPart = '###LISTPAGER###';

        return tx_rnbase_util_Templates::getSubpart(
            Tx_Rnbase_Utility_T3General::getUrl($sPath),
            $sSubPart
        );
    }

    public function &__buildDefaultTemplate($sCssPrefix = '.ameosformidable-rdtlister-defaultwrap')
    {
        $aRes = [
            'default' => true,
            'html' => '',
            'styles' => '',
            'cssfile' => '',
            'pager' => '',
        ];

        $aHtml = [
            'TOP' => [],
            'DATA' => [
                'ROW1' => [],
                'ROW2' => [],
            ],
        ];

        $sPath        = $this->sExtPath . 'res/html/default-template.html';
        $sSubpart    = '###LIST###';

        $aRes['html'] = tx_rnbase_util_Templates::getSubpart(
            Tx_Rnbase_Utility_T3General::getUrl($sPath),
            $sSubpart
        );

        /* including default styles in external CSS */

        $aRes['styles'] = $this->_parseThrustedTemplateCode(
            tx_rnbase_util_Templates::getSubpart(
                Tx_Rnbase_Utility_T3General::getUrl($sPath),
                '###STYLES###'
            ),
            [
                'PREFIX' => $sCssPrefix,
                'EXTPATH' => '/' . $this->sExtRelPath
            ],
            [],
            false
        );

        /*END of CSS */



        $sTopColumn = tx_rnbase_util_Templates::getSubpart($aRes['html'], '###TOPCOLUMN###');
        $sDataColumn1 = tx_rnbase_util_Templates::getSubpart($aRes['html'], '###DATACOLUMN1###');
        $sDataColumn2 = tx_rnbase_util_Templates::getSubpart($aRes['html'], '###DATACOLUMN2###');

        foreach ($this->aOColumns as $sColName => $_) {
            if ($this->_defaultTrue('/columns/listheaders') === true) {
                // building sorting header for this column

                if (($sHeader = $this->getListHeader($sColName)) === false) {
                    $sHeader = '{' . $sColName . '.label}';
                }

                $aHtml['TOP'][]    = $this->_parseThrustedTemplateCode(
                    $sTopColumn,
                    [
                        'COLNAME'        => $sColName,
                        'COLCONTENT'    => '<!-- ###SORT_' . $sColName . '### begin-->' . $sHeader . '<!-- ###SORT_' . $sColName . '### end-->'
                    ],
                    [],    // exclude
                    false        // bClearNotUsed
                );
            }

            // building data cells for this column
            $aTemp = [
                'COLNAME'        => $sColName,
                'COLCONTENT'    => '{' . $sColName . '}',
            ];

            $aHtml['DATA']['ROW1'][]    = $this->_parseThrustedTemplateCode(
                $sDataColumn1,
                $aTemp,
                [],
                false
            );

            $aHtml['DATA']['ROW2'][]    = $this->_parseThrustedTemplateCode(
                $sDataColumn2,
                $aTemp,
                [],
                false
            );
        }

        $aRes['html'] = tx_rnbase_util_Templates::substituteSubpart($aRes['html'], '###STYLES###', '', false, false);
        $aRes['html'] = tx_rnbase_util_Templates::substituteSubpart($aRes['html'], '###DATACOLUMN1###', implode('', $aHtml['DATA']['ROW1']), false, false);
        $aRes['html'] = tx_rnbase_util_Templates::substituteSubpart($aRes['html'], '###DATACOLUMN2###', implode('', $aHtml['DATA']['ROW2']), false, false);
        $aRes['html'] = tx_rnbase_util_Templates::substituteSubpart($aRes['html'], '###TOPCOLUMN###', implode('', $aHtml['TOP']), false, false);

        $aRes['html'] = $this->_parseThrustedTemplateCode(
            $aRes['html'],
            [
                'NBCOLS' => sizeof($this->aOColumns)
            ],
            [],
            false
        );

        /* RETRIEVING pager */

        $aRes['pager'] = $this->__getDefaultPager();

        return $aRes;
    }




    public function initChilds($bReInit = false)
    {
        $this->initColumns();
    }

    private function initColumns()
    {
        if (is_array($this->aOColumns)) {
            return;
        }

        $this->aOColumns = [];
        if (($aColumns = $this->_navConf('/columns')) !== false && is_array($aColumns)) {
            $aColKeys = array_keys($aColumns);
            reset($aColKeys);
            foreach ($aColKeys as $sTagName) {
                if ($this->getForm()->getConfig()->defaultTrue('/process', $aColumns[$sTagName])) {
                    // Das "renderlet:" aus dem Type entfernen
                    $aColumns[$sTagName]['type'] = str_replace('renderlet:', '', $aColumns[$sTagName]['type']);

                    if (array_key_exists('name', $aColumns[$sTagName]) && (trim($aColumns[$sTagName]['name']) != '')) {
                        $bAnonymous = false;
                    } else {
                        // Hier wurde kein Name angegeben. Dieser wird somit dynamisch erstellt und nachträglich
                        // passend in die Config-Struktur gehängt
                        $sName = $this->getForm()->_getAnonymousName($aColumns[$sTagName]);
                        $this->aElement['columns'][$sTagName]['name'] = $sName;
                        $aColumns[$sTagName]['name'] = $sName;
                        $bAnonymous = true;
                    }

                    $oRdt =& $this->getForm()->_makeRenderlet(
                        $aColumns[$sTagName],
                        $this->sXPath . 'columns/' . $sTagName . '/',
                        $bChilds = true,
                        $this,
                        $bAnonymous,
                        $sNamePrefix
                    );

                    $sAbsName = $oRdt->getAbsName();
                    $sName = $oRdt->getName();
                    $this->getForm()->aORenderlets[$sAbsName] =& $oRdt;

                    // columns are localy stored without prefixing, of course
                    $this->aOColumns[$sName] =& $oRdt;
                    unset($oRdt);
                }
            }
        }

        $this->aChilds =& $this->aOColumns;
    }

    public function _getElementHtmlName($sName = false, $bAddCurRow = true)
    {
        $sRes = parent::_getElementHtmlName($sName);

        // die id anhängen, wenn daten vorhanden sind?
        if ($bAddCurRow) {
            // TODO: Das sollte sicher überarbeitet werden...
            // wird das überheupt benötigt?
            $aData =& $this->getForm()->getDataHandler()->_getListData();
            if (!empty($aData)) {
                $uidColumn = $this->getUidColumn();
                $sRes .= '[' . $aData[$uidColumn] . ']';
            }
        }

        return $sRes;
    }
    /**
     * Liefert den HTML-Namen des Listers.
     * @return string
     */
    public function getElementHtmlNameBase()
    {
        return parent::_getElementHtmlName(false);
    }
    public function _getElementHtmlNameWithoutFormId($sName = false, $bAddCurRow = true)
    {
        $sRes = parent::_getElementHtmlNameWithoutFormId($sName);

        // die id anhängen, wenn daten vorhanden sind?
        if ($bAddCurRow) {
            // TODO: Das sollte sicher überarbeitet werden...
            // wird das überheupt benötigt?
            $aData =& $this->oForm->oDataHandler->_getListData();
            if (!empty($aData)) {
                $uidColumn = $this->getUidColumn();
                $sRes .= '[' . $aData[$uidColumn] . ']';
            }
        }

        return $sRes;
    }


    /**
     * Im Lister müssen die Child-Elemente mit ihrer UID versehen werden. Wir überschreiben
     * die Basisklasse und passen den Code an. Die Integration des DataHandlers ist irgendwie
     * seltsam, aber es funktioniert erstmal...
     * @param formidable_mainrenderlet $child
     * @param bool $withForm
     * @param bool $withIteratingId
     * @return string
     */
    protected function getElementHtmlId4Child($child, $withForm = true, $withIteratingId = true)
    {
        $childId = $child->_getNameWithoutPrefix();
        $htmlId = $this->buildHtmlId($withForm, $withIteratingId); // ID Box/Lister
        if ($this->getForm()->getDataHandler()->isIterating() && $withIteratingId) {
            if (!empty($this->__aCurRow)) {
                // Die Spalte für die UID kann im XML gesetzt werden
                $uidColumn = $this->getUidColumn();
                $htmlId .= AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN . $this->__aCurRow[$uidColumn] . AMEOSFORMIDABLE_NESTED_SEPARATOR_END;
            } elseif (strlen($child->getIteratingId())) {
                $htmlId .= AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN . $child->getIteratingId() . AMEOSFORMIDABLE_NESTED_SEPARATOR_END;
            }
        }
        $htmlId .= AMEOSFORMIDABLE_NESTED_SEPARATOR_BEGIN . $childId . AMEOSFORMIDABLE_NESTED_SEPARATOR_END;

        return $htmlId;
    }

    /**
     * @TODO: entfernen, wenn in allen Projekten umgestellt.
     */
    private function ignoreListerNameIdBugFix()
    {
        static $ignore = -1;
        if ($ignore == -1) {
            tx_rnbase::load('tx_rnbase_configurations');
            $ignore = tx_rnbase_configurations::getExtensionCfgValue('mkforms', 'listerNameId');
            $ignore ? 1 : 0;
        }

        return $ignore;
    }

    /**
     * Im Lister müssen die Child-Elemente mit ihrerm UID versehen werden. Wir überschreiben
     * die Basisklasse und passen den Code an. Die Integration des DataHandlers ist irgendwie
     * seltsam, aber es funktioniert erstmal...
     * @param formidable_mainrenderlet $child
     * @return string
     */
    protected function getElementHtmlName4Child($child)
    {
        $childId = $child->_getNameWithoutPrefix();
        $htmlId = $this->_getElementHtmlName(false, $this->ignoreListerNameIdBugFix()); // ID Box/Lister
        if ($this->getForm()->getDataHandler()->isIterating()) {
            if (!empty($this->__aCurRow)) {
                // Die Spalte für die UID kann im XML gesetzt werden
                $uidColumn = $this->getUidColumn();
                $htmlId .= '[' . $this->__aCurRow[$uidColumn] . ']';
            } elseif (strlen($child->getIteratingId())) {
                $htmlId .= '[' . $child->getIteratingId() . ']';
            }
        }
        $htmlId .= '[' . $childId . ']';

        return $htmlId;
    }

    /**
     * Validates the Renderlet with all child elements
     * Writes into $this->_aValidationErrors[] using tx_ameosformidable::_declareValidationError()
     * @return boolean true wenn kein Fehler vorliegt
     */
    public function validate()
    {
        // immer erst den Lister selbst validieren
        $errors = !parent::validate();
        $this->aListerData = false;
        $aData = $this->fetchListerData();

        $aChilds = $this->getChilds();

        if ($this->getForm()->getDataHandler()->isIterating()) {
            foreach ($aData['results'] as $curRow => $aFields) {
                foreach ($aChilds as $sName => $oChild) {
                    $this->__aCurRow = $aFields;
                    $iRowUid = $aFields[$this->getUidColumn()];
                    /* @var $oChild formidable_mainrenderlet */
                    $oChild->setIteratingId($iRowUid);

                    $errors = $oChild->validate() ? $errors : false;

                    $this->__aCurRow = [];
                    $oChild->setIteratingId();
                }
            }
        }

        return !$errors;
    }

    /**
     * @param array $aRow
     * @return array
     */
    public function &_refineRow(&$aRow)
    {
        $sUid = $aRow[$this->getUidColumn()];

        // sollen werte aus den parametern genutzt werden?
        $bUseGP = $this->_defaultTrue('/usegp');

        if (is_array($aRow)) {
            $aColKeys = array_keys($this->aOColumns);
            reset($aColKeys);
            foreach ($aColKeys as $sName) {
                $this->aOColumns[$sName]->doBeforeIteration($this);
            }
            foreach ($aColKeys as $sName) {
                /* @var $oWidget formidable_mainrenderlet */
                $oWidget = $this->aOColumns[$sName];
                $oWidget->setIteratingId($sUid);

                $sAbsName = $oWidget->getAbsName();

                // @TODO: das wird nicht gehen, da hier die uid des reihe fehlt
                if (array_key_exists($sAbsName, $this->getForm()->aPreRendered)) {
                    $aRow[$sAbsName] = $this->getForm()->aPreRendered[$sAbsName];
                } else {
                    $this->aRdtByRow[$sUid][$sName] = $oWidget->_getElementHtmlId();

                    // Den Wert zuerst aus den Parametern holen, danach die datasource fragen
                    $mValue = ($bUseGP && $oWidget->_activeListable()) ? $oWidget->getValue() : null;

                    // wenn das Formular nicht abgeschickt wurde aber $bUseGP && $oWidget->_activeListable()
                    // gesetzt ist, dann ist $mValue null. Wenn das Formular abgeschickt wurde mit einem
                    // leeren Wert dann ist $mValue nicht null sondern ein leerer string. Somit wird die
                    // datasource bei gesetztem $bUseGP nur noch angesprochen wenn das Formular
                    // nicht abgeschickt wurde oder !$oWidget->_activeListable().
                    if (is_null($mValue) && array_key_exists($sName, $aRow)) {
                        $mValue = $aRow[$sName];
                    }

                    if ($oWidget->_activeListable()) {
                        $aRow[$sName] = $this->getForm()->getRenderer()->processHtmlBag(
                            $oWidget->renderWithForcedValue($mValue),
                            $oWidget
                        );
                    } else {
                        $aRow[$sName] = $this->getForm()->getRenderer()->processHtmlBag(
                            $oWidget->renderReadOnlyWithForcedValue($mValue),
                            $oWidget
                        );
                    }
                }

                $oWidget->setIteratingId();
            }
            foreach ($aColKeys as $sName) {
                $this->aOColumns[$sName]->doAfterIteration();
            }
        } else {
            $aRow = [];
        }

        reset($aRow);

        return $aRow;
    }

    public function _getPage()
    {
        if ($this->bResetPager !== true) {
            if ($this->aLimitAndSort !== false) {
                return $this->aLimitAndSort['curpage'];
            } else {
                $aGet = $this->oForm->oDataHandler->_G();
                $sName = $this->_getElementHtmlId();

                if (array_key_exists($sName, $aGet) && array_key_exists('page', $aGet[$sName])) {
                    return (($iPage = (int)$aGet[$sName]['page']) >= 1) ? $iPage : 1;
                }
            }
        }

        return 1;
    }

    public function _getSortColAndDirection()
    {
        if ($this->aLimitAndSort !== false) {
            $aRes = [
                'col' => $this->aLimitAndSort['sortby'],
                'dir' => $this->aLimitAndSort['sortdir'],
            ];
        } else {
            $aRes = [
                'col' => '',
                'dir' => '',
            ];

            $aGet = $this->oForm->oDataHandler->_G();
            $sName = $this->_getElementHtmlId();

            if (array_key_exists($sName, $aGet) && array_key_exists('sort', $aGet[$sName])) {
                $sSort = $aGet[$sName]['sort'];
                $aSort = explode('-', $sSort);

                if (sizeof($aSort) == 2) {
                    $sCol = $aSort[0];
                    if (!array_key_exists($sCol, $this->aOColumns)) {
                        $sCol = array_shift(array_keys($this->aOColumns));
                    }

                    $sDir = $aSort[1];
                    if (!in_array($sDir, ['asc', 'desc'])) {
                        $sDir = 'asc';
                    }
                }

                $aRes = [
                    'col' => $sCol,
                    'dir' => $sDir
                ];
            } elseif ($this->_navConf('/pager/sort') !== false) {
                if (($sSortCol = $this->_navConf('/pager/sort/column')) !== false) {
                    if ($this->oForm->isRunneable($sSortCol)) {
                        $aRes['col'] = $this->getForm()->getRunnable()->callRunnableWidget($this, $sSortCol);
                    } else {
                        $aRes['col'] = $sSortCol;
                    }
                }

                if (($sSortDir = $this->_navConf('/pager/sort/direction')) !== false) {
                    if ($this->oForm->isRunneable($sSortDir)) {
                        $aRes['dir'] = $this->getForm()->getRunnable()->callRunnableWidget($this, $sSortDir);
                    } else {
                        $aRes['dir'] = $sSortDir;
                    }
                }
            }
        }

        return $aRes;
    }

    public function mayHaveChilds()
    {
        return true;
    }

    public function hasChilds()
    {
        return true;
    }

    public function _activeListable()
    {
        return $this->oForm->_defaultTrue('/activelistable/', $this->aElement);
    }

    public function _renderOnly($bForAjax = false)
    {
        return true;
    }

    public function cleanBeforeSession()
    {
        unset($this->aChilds);
        $this->aChilds = false;

        foreach ($this->aOColumns as $sKey => &$sName) {
            if (is_object($sName)) {
                $sName = $sName->getAbsName();
                $this->oForm->aORenderlets[$sName]->cleanBeforeSession();
            }
        }

        $this->baseCleanBeforeSession();
        unset($this->oForm);
    }

    public function awakeInSession(&$oForm)
    {
        parent::awakeInSession($oForm);

        foreach ($this->aOColumns as $sKey => &$sName) {
            if (!is_object($sName)) {
                $sName = $this->oForm->aORenderlets[$sName];
            }
        }

        $this->aChilds =& $this->aOColumns;
    }

    /**
     * Liefert die aktuelle Zeile des Listers
     * @return array
     */
    public function getCurrentRow()
    {
        return $this->__aCurRow;
    }
    /**
     * Liefert die Spalte der DataSource, die die UID des Datensatzes enthält. Default ist 'uid'.
     * @return string
     */
    public function getUidColumn()
    {
        if (!$this->uidColumn) {
            $uidColumn = $this->getForm()->getConfig()->get('/uidcolumn', $this->aElement);
            $this->uidColumn = ($uidColumn !== false) ? $uidColumn : 'uid';
        }

        return $this->uidColumn;
    }
    /**
     * Liefert die UID der aktuellen Zeile des Listers
     * @return int
     */
    public function getCurrentRowUid()
    {
        return $this->__aCurRow[$this->getUidColumn()];
    }

    /**
     * Liefert den Index der aktuellen Zeile
     * @return int
     */
    private function getCurrentRowNum()
    {
        return $this->iCurRowNum;
    }


    public function isIterating()
    {
        return $this->getCurrentRowNum() !== false;
    }

    public function isIterable()
    {
        return true;
    }

    public function isAjaxLister()
    {
        return $this->defaultFalse('/ajaxlister');
    }

    public function setPage($iPage)
    {
        if ($this->aLimitAndSort === false) {
            $this->iTempPage = $iPage;
        } else {
            $this->aLimitAndSort['curpage'] = $iPage;
            $this->aLimitAndSort['limitoffset'] = (($iPage - 1) * (int)$this->aLimitAndSort['rowsperpage']);
        }
    }

    public function majixRepaintPage($iPage)
    {
        $this->setPage($iPage);

        return $this->majixRepaint();
    }

    /**
     * @return array
     */
    public function repaintFirst()
    {
        return $this->majixRepaintPage(1);
    }

    public function repaintPrev()
    {
        $iPage = $this->_getPage();

        return $this->majixRepaintPage($iPage - 1);
    }

    public function repaintNext()
    {
        $iPage = $this->_getPage();

        return $this->majixRepaintPage($iPage + 1);
    }

    public function repaintLast()
    {
        return $this->majixRepaintPage($this->aPager['pagemax']);
    }

    public function setSortColumn($sCol)
    {
        $this->aLimitAndSort['sortby'] = $sCol;
    }

    public function setSortDirection($sDir)
    {
        $this->aLimitAndSort['sortdir'] = $sDir;
    }

    public function repaintSortBy($aParams)
    {
        $this->setSortColumn($aParams['sys_event']['sortcol']);
        $this->setSortDirection($aParams['sys_event']['sortdir']);

        return $this->majixRepaint();
    }

    public function setCurrentPage($iPage)
    {
        $this->aLimitAndSort['curpage'] = $iPage;
    }

    public function repaintToSite($aParams)
    {
        $this->setCurrentPage($aParams['sys_event']['pagenum']);

        return $this->majixRepaint();
    }

    public function setSelected($iUid)
    {
        $this->mCurrentSelected = $iUid;
    }

    public function getPageNumberForUid($iUid)
    {
        if (($iPos = $this->oDataStream->getRowNumberForUid($iUid)) !== false) {
            return $this->getPageForLineNumber($iPos);
        }

        return false;
    }

    /**
     * (non-PHPdoc)
     * @see formidable_mainrenderlet::checkValue()
     */
    public function checkValue(&$aGP)
    {
        if (empty($aGP)) {
            return;
        }

        $aAllowedRdts = $this->getChilds();
        //in $aGP sind alle möglichen Lister Elemente.
        foreach ($aGP as $sRdtKey => &$aListerData) {
            //alle renderlets suchen, die nicht im XML angegeben sind
            if (is_array($aListerData)) {//normale Listerelemente
                $aUnknownRdts = array_diff(array_keys($aListerData), array_keys($aAllowedRdts));
                $aCurrentData =& $aListerData;
            } else { //z.B. selected
                $aUnknownRdts = array_diff([$sRdtKey], array_keys($aAllowedRdts));
                $aCurrentData =& $aGP;
            }

            //jetzt entfernen
            foreach ($aUnknownRdts as $sUnknownRdt) {
                unset($aCurrentData[$sUnknownRdt]);
            }
        }

        return;
    }

    public function getValue()
    {
        $value = parent::getValue();

        return $value;
    }
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/widgets/lister/class.tx_mkforms_widgets_lister_Main.php']) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/widgets/lister/class.tx_mkforms_widgets_lister_Main.php']);
}
