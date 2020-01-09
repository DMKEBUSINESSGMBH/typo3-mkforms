<?php

class formidable_maindataset extends formidable_mainobject
{
    public $aData = false;

    public $sKey = false;

    public $oDataSource = false;

    public $aChangedCells = [];        // stores the new value for cells of data that have changed

    public $aChangedCellsBefore = []; // stores the previous value for cells of data that have changed

    public $bFloating = false;

    public function initFloating(&$oDataSource, $aData = [])
    {
        $this->setFloating();
        $this->_initInternals($oDataSource, $aData);
    }

    public function initAnchored(&$oDataSource, $aData, $sKey)
    {
        $this->setAnchored();
        $this->_initInternals($oDataSource, $aData);
        $this->sKey = $sKey;
    }

    public function _initInternals(&$oDataSource, $aData)
    {
        $this->oForm = &$oDataSource->oForm;
        $this->aData = $aData;
        $this->oDataSource = &$oDataSource;
        $this->aChangedCells = [];
    }

    public function getKey()
    {
        return $this->sKey;
    }

    public function getData()
    {
        reset($this->aData);

        return $this->aData;
    }

    public function setCellValue($sPath, $mValue)
    {
        $mPreviousValue = $this->oForm->setDeepData(
            $sPath,
            $this->aData,
            $mValue
        );

        if (false !== $mPreviousValue && $mPreviousValue !== $mValue) {
            $this->aChangedCells[$sPath] = $mValue;
            $this->aChangedCellsBefore[$sPath] = $mPreviousValue;
        }
    }

    public function getCellValue($sPath)
    {
        $sPath = str_replace('.', '/', $sPath);
        if (false !== ($aRes = $this->oForm->navDeepData($sPath, $this->aData))) {
            return $aRes;
        }

        return false;
    }

    public function needsToBeWritten()
    {
        return $this->somethingHasChanged();
    }

    public function somethingHasChanged()
    {
        return count($this->aChangedCells) > 0;
    }

    public function cellHasChanged($sPath)
    {
        return array_key_exists($sPath, $this->aChangedCells);
    }

    public function setFloating()
    {
        $this->bFloating = true;
    }

    public function setAnchored()
    {
        $this->bFloating = false;
    }

    public function isFloating()
    {
        return $this->bFloating;
    }

    public function isAnchored()
    {
        return !$this->isFloating();
    }

    public function getSignature()
    {
        return base64_encode($this->oForm->_getSafeLock($this->sKey).':'.$this->sKey);
    }

    public function baseCleanBeforeSession()
    {
        unset($this->oDataSource);
        $this->oDataSource = false;
    }

    public function getDataSet()
    {
        return [
            'mode' => $this->isFloating() ? 'create' : 'update',
            'key' => $this->getKey(),
            'data' => $this->aData,
        ];
    }
}
