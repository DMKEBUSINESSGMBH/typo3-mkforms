<?php

/**
 * Plugin 'ds_phparray' for the 'ameos_formidable' extension.
 *
 * @author    Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_ds_phparray_Main extends formidable_maindatasource
{
    public $aSource = false;

    public $aPosByUid = false;

    public $aConfig = [];

    public $aFilters = [];

    public $iTotalRows = 0;

    public function &_fetchData($aConfig = [], $aFilters = [])
    {
        $this->aConfig = &$aConfig;
        $this->aFilters = &$aFilters;

        $this->initBinding($aConfig, $aFilters);

        return [
            'numrows' => $this->iTotalRows,
            'results' => &$this->aSource,
        ];
    }

    private function initBinding($aConfig, $aFilters)
    {
        if ($this->getForm()->getRunnable()->isRunnable(($aBindsTo = $this->_navConf('/bindsto')))) {
            $params = ['config' => $aConfig, 'filters' => $aFilters];
            $this->aSource = &$this->getForm()->getRunnable()->callRunnable($aBindsTo, $params, $this);

            if (!is_array($this->aSource)) {
                $this->aSource = [];
                $this->iTotalRows = 0;
            } else {
                $this->iTotalRows = count($this->aSource);
            }
        }

        $this->_sortSource();
        $this->_limitSource();
    }

    public function _sortSource()
    {
        if ('' !== trim($this->aConfig['sortcolumn'])) {
            $aSorted = [];

            reset($this->aSource);
            $named_hash = [];

            foreach ($this->aSource as $key => $fields) {
                $named_hash[$key] = $fields[$this->aConfig['sortcolumn']];
            }

            if ('desc' === $this->aConfig['sortdirection']) {
                arsort($named_hash, $flags = 0);
            } else {
                asort($named_hash, $flags = 0);
            }

            $k = 1;
            $this->aPosByUid = [];

            foreach ($named_hash as $key => $val) {
                $aSorted[$key] = $this->aSource[$key];
                $this->aPosByUid[$aSorted[$key]['uid']] = $k;
                ++$k;
            }

            reset($this->aPosByUid);

            return $this->aSource = &$aSorted;
        } else {
            $k = 1;
            $this->aPosByUid = [];
            $aKeys = array_keys($this->aSource);

            reset($aKeys);
            foreach ($aKeys as $sKey) {
                $this->aPosByUid[$this->aSource[$sKey]['uid']] = $k;
                ++$k;
            }

            reset($this->aPosByUid);
        }
    }

    public function _limitSource()
    {
        $aLimit = $this->_getRecordWindow(
            $this->aConfig['page'],
            $this->aConfig['perpage']
        );

        $this->aSource = array_slice(
            $this->aSource,
            $aLimit['offset'],
            $aLimit['nbdisplayed']
        );
    }

    public function getRowNumberForUid($iUid)
    {
        if (array_key_exists($iUid, $this->aPosByUid)) {
            return $this->aPosByUid[$iUid];
        }

        return false;
    }
}

if (defined('TYPO3_MODE')
    && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/ds/phparray/class.tx_mkforms_ds_phparray_Main.php']
) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/ds/phparray/class.tx_mkforms_ds_phparray_Main.php'];
}
