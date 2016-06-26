<?php

class formidable_maindataset extends formidable_mainobject
{

    var $aData = false;

    var $sKey = false;

    var $oDataSource = false;

    var $aChangedCells = array();        // stores the new value for cells of data that have changed

    var $aChangedCellsBefore = array(); // stores the previous value for cells of data that have changed

    var $bFloating = false;

    function initFloating(&$oDataSource, $aData = array())
    {
        $this->setFloating();
        $this->_initInternals($oDataSource, $aData);
    }

    function initAnchored(&$oDataSource, $aData, $sKey)
    {
        $this->setAnchored();
        $this->_initInternals($oDataSource, $aData);
        $this->sKey = $sKey;
    }

    function _initInternals(&$oDataSource, $aData)
    {
        $this->oForm =& $oDataSource->oForm;
        $this->aData = $aData;
        $this->oDataSource =& $oDataSource;
        $this->aChangedCells = array();
    }

    function getKey()
    {
        return $this->sKey;
    }

    function getData()
    {
        reset($this->aData);

        return $this->aData;
    }

    function setCellValue($sPath, $mValue)
    {
        $mPreviousValue = $this->oForm->setDeepData(
            $sPath,
            $this->aData,
            $mValue
        );

        if ($mPreviousValue !== false && $mPreviousValue !== $mValue) {
            $this->aChangedCells[$sPath] = $mValue;
            $this->aChangedCellsBefore[$sPath] = $mPreviousValue;
        }
    }

    function getCellValue($sPath)
    {
        $sPath = str_replace(".", "/", $sPath);
        if (($aRes = $this->oForm->navDeepData($sPath, $this->aData)) !== false) {
            return $aRes;
        }

        return false;
    }

    function needsToBeWritten()
    {
        return $this->somethingHasChanged();
    }

    function somethingHasChanged()
    {
        return count($this->aChangedCells) > 0;
    }

    function cellHasChanged($sPath)
    {
        return array_key_exists($sPath, $this->aChangedCells);
    }

    function setFloating()
    {
        $this->bFloating = true;
    }

    function setAnchored()
    {
        $this->bFloating = false;
    }

    function isFloating()
    {
        return $this->bFloating;
    }

    function isAnchored()
    {
        return !$this->isFloating();
    }

    function getSignature()
    {
        return base64_encode($this->oForm->_getSafeLock($this->sKey) . ":" . $this->sKey);
    }

    function baseCleanBeforeSession()
    {
        unset($this->oDataSource);
        $this->oDataSource = false;
    }

    function getDataSet()
    {
        return array(
            "mode" => $this->isFloating() ? "create" : "update",
            "key" => $this->getKey(),
            "data" => $this->aData,
        );
    }
}
