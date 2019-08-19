<?php

class formidable_mainjscb
{
    public $aConf = array();

    public $oForm = null;

    public function init(&$oForm, $aConf)
    {
        $this->oForm = $oForm;
        $this->aConf = $aConf;
    }

    /**
     * @param string                 $sMethod name of js method to call
     * @param tx_mkforms_forms_IForm $oForm
     */
    public function majixExec($sMethod, $oForm)
    {
        $aArgs = func_get_args();
        $sMethod = array_shift($aArgs);
        array_shift($aArgs);

        return $oForm->buildMajixExecuter(
            'executeCbMethod',
            array(
                'cb' => $this->aConf,
                'method' => $sMethod,
                'params' => $aArgs,
            ),
            $oForm->getFormId()
        );
    }
}
