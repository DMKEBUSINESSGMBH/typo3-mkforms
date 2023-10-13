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
        return false !== $this->getConfigValue('/set');
    }

    public function initDataSet($sKey)
    {
        $oDataSet = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('formidable_maindataset');

        if ('new' === $sKey) {
            // new record to create
            $oDataSet->initFloating($this);
        } else {
            // existing record to grab

            if (false === $this->getConfigValue('/get')) {
                $oDataSet->initAnchored(
                    $this,
                    [],
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
        if (false !== ($aGet = $this->getConfigValue('/get'))) {
            if ($this->oForm->isRunneable($aGet)) {
                $aGet = $this->callRunneable(
                    $aGet,
                    ['key' => $sKey]
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
        if (false !== ($aSet = $this->getConfigValue('/set'))) {
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

    public function &_fetchData($aConfig = [], $aFilters = [])
    {
        $aResults = [];
        $iNumRows = 0;

        return [
            'numrows' => $iNumRows,
            'results' => &$aResults,
        ];
    }

    public function dset_alwaysNeedsToBeWritten()
    {
        return true;
    }
}
