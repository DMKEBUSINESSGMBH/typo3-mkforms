<?php
/**
 * Plugin 'rdt_passthru' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_widgets_passthru_Main extends formidable_mainrenderlet
{
    public function _render()
    {
        return '';
    }

    public function _sqlSearchClause($sValue, $sFieldPrefix = '', $sFieldName = '', $bRec = true)
    {
        return $sFieldPrefix.$this->getConfigValue('/name')." = '".$sValue."'";
    }

    public function _listable()
    {
        return false;
    }

    public function maySubmit()
    {
        return false;
    }
}
