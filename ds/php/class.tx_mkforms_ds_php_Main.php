<?php

/**
 * Plugin 'ds_php' for the 'ameos_formidable' extension.
 *
 * @author    Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_ds_php_Main extends formidable_maindatasource
{
    public $sKey = false;

    public function writable()
    {
        return false !== $this->_navConf('/set');
    }

    public function initDataSet($sKey)
    {
        $oDataSet = tx_rnbase::makeInstance('formidable_maindataset');

        if ('new' === $sKey) {
            // new record to create
            $oDataSet->initFloating($this);
        } else {
            // existing record to grab

            if (false === $this->_navConf('/get')) {
                $oDataSet->initAnchored(
                    $this,
                    array(),
                    $sKey
                );
            } else {
                if (false !== ($aDataSet = $this->getSyncData($sKey))) {
                    $oDataSet->initAnchored(
                        $this,
                        $aDataSet,
                        $sKey
                    );
                } else {
                    $this->oForm->mayday(
                        "datasource:PHP[name='".$this->getName()."'] No dataset matching key '".$sKey."' was found."
                    );
                }
            }
        }

        $sSignature = $oDataSet->getSignature();
        $this->aODataSets[$sSignature] = &$oDataSet;

        return $sSignature;
    }

    public function getSyncData($sKey)
    {
        if (false !== ($aGet = $this->_navConf('/get'))) {
            if ($this->oForm->isRunneable($aGet)) {
                $aGet = $this->callRunneable(
                    $aGet,
                    array('key' => $sKey)
                );
            } else {
                $this->oForm->mayday(
                    "datasource:PHP[name='".$this->getName()
                    ."'] /get has to be runnable (userobj, or reference to a code-behind)."
                );
            }
        } else {
            $this->oForm->mayday(
                "datasource:PHP[name='".$this->getName()."'] You have to provide a runnable on <b>/get</b>."
            );
        }

        return $aGet;
    }

    public function setSyncData($sSignature, $sKey, $aData)
    {
        if (false !== ($aSet = $this->_navConf('/set'))) {
            if ($this->oForm->isRunneable($aSet)) {
                $aSet = $this->callRunneable(
                    $aSet,
                    $this->aODataSets[$sSignature]->getDataSet()
                );
            } else {
                $this->oForm->mayday(
                    "datasource:PHP[name='".$this->getName()
                    ."'] /set has to be runnable (userobj, or reference to a code-behind)."
                );
            }
        }

        return $aSet;
    }

    public function &_fetchData($aConfig = array(), $aFilters = array())
    {
        $aResults = array();
        $iNumRows = 0;

        return array(
            'numrows' => $iNumRows,
            'results' => &$aResults,
        );
    }

    public function dset_alwaysNeedsToBeWritten()
    {
        return true;
    }
}

if (defined('TYPO3_MODE')
    && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/ds/php/class.tx_mkforms_ds_php_Main.php']
) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mkforms/ds/php/class.tx_mkforms_ds_php_Main.php'];
}
