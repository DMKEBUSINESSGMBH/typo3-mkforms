<?php

class tx_ameosformidable_pi extends Tx_Rnbase_Frontend_Plugin
{
    public $extKey = 'ameos_formidable';

    public $oForm = false;

    public $aFormConf = false;

    public $sXmlPath = false;

    public function main($content, $conf)
    {
        $this->conf = $conf;
        $this->pi_setPiVarDefaults();
        $this->pi_loadLL();

        $sConfPath = trim(
            $this->pi_getFFvalue(
                $this->cObj->data['pi_flexform'],
                'tspath'
            )
        );

        require_once PATH_formidableapi;

        if ('' !== $sConfPath) {
            $aCurZone = &$GLOBALS['TSFE']->tmpl->setup;

            $aPath = explode('.', $sConfPath);
            reset($aPath);
            foreach ($aPath as $sSegment) {
                if (array_key_exists($sSegment.'.', $aCurZone)) {
                    $aCurZone = &$aCurZone[$sSegment.'.'];
                } else {
                    return '<strong>Formidable: TS path not found in template</strong>';
                }
            }

            $this->aFormConf = $aCurZone;
        } else {
            $sConfPath = trim(
                $this->pi_getFFvalue(
                    $this->cObj->data['pi_flexform'],
                    'xmlpath'
                )
            );

            if ('' !== $sConfPath) {
                $this->sXmlPath = tx_ameosformidable::toServerPath($sConfPath);
            } else {
                if (array_key_exists('xmlpath', $this->conf)) {
                    $this->sXmlPath = tx_ameosformidable::toServerPath($this->conf['xmlpath']);
                } else {
                    return '<strong>Formidable: TS or XML pathes not defined</strong>';
                }
            }
        }

        return true;
    }

    public function render()
    {
        // init+render

        require_once tx_rnbase_util_Extensions::extPath('mkforms').'api/class.tx_ameosformidable.php';
        $this->oForm = tx_rnbase::makeInstance('tx_ameosformidable');
        if (false === $this->sXmlPath) {
            $this->oForm->initFromTs(
                $this,
                $this->aFormConf
            );
        } else {
            $this->oForm->init(
                $this,
                $this->sXmlPath
            );
        }

        return $this->pi_wrapInBaseClass($this->oForm->render());
    }
}
