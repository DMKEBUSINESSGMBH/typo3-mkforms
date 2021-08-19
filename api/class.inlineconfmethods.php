<?php

class formidable_inlineconfmethods extends formidable_mainscriptingmethods
{
    public function &method_this(&$oRdt, $aParams)
    {
        return $oRdt;
    }

    public function &method_parent(&$oRdt, $aParams)
    {
        if ($this->oForm->isRenderlet($oRdt)) {
            if ($oRdt->hasParent()) {
                return $oRdt->oRdtParent;
            }
        }

        return AMEOSFORMIDABLE_LEXER_BREAKED;
    }

    public function &method_brother(&$oRdt, $aParams)
    {
        if ($this->oForm->isRenderlet($oRdt)) {
            $oParent = &$this->method_parent($oRdt, $aParams);
            if ($this->oForm->isRenderlet($oParent)) {
                return $this->method_child(
                    $oParent,
                    $aParams
                );
            }
        }

        return AMEOSFORMIDABLE_LEXER_BREAKED;
    }

    public function method_getAbsName(&$oRdt, $aParams)
    {
        if ($this->oForm->isRenderlet($oRdt)) {
            return $oRdt->getAbsName();
        }

        return AMEOSFORMIDABLE_LEXER_BREAKED;
    }

    public function &method_child(&$oRdt, $aParams)
    {
        if ($this->oForm->isRenderlet($oRdt)) {
            if ($oRdt->hasChilds()) {
                if (array_key_exists($aParams[0], $oRdt->aChilds)) {
                    return $oRdt->aChilds[$aParams[0]];
                }
            }
        }

        return AMEOSFORMIDABLE_LEXER_BREAKED;
    }

    public function &method_rdt($oRdt, $aParams)
    {
        return $this->oForm->rdt($aParams[0]);
    }
}
