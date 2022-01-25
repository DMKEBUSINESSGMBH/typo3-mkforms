<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Radu Cocieru <radu@cocieru.com>
*  (c) 2006 Luc Muller <l.muller@ameos.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Plugin 'rdt_captcha' for the 'ameos_formidable' extension.
 *
 * @author  Luc Muller <l.muller@ameos.com>
 */
class tx_mkforms_widgets_captcha_Main extends formidable_mainrenderlet
{
    public $sMajixClass = 'Captcha';
    public $bCustomIncludeScript = true;
    public $aLibs = [
        'rdt_captcha_class' => 'res/js/captcha.js',
    ];

    /**
     * @return tx_mkforms_widgets_captcha_Main
     */
    public function __construct()
    {
        tx_mkforms_session_Factory::getSessionManager()->initialize();
    }

    public function _render()
    {
        $this->_MakeCaptchaConfig();

        $_SESSION['cryptdir'] = $this->sExtRelPath.'res/lib/';
        $iSID = session_id();

        $aCaptcha = [];
        $aCaptcha['img'] = '<img id="'.$this->_getElementHtmlId().'img" src="'.$_SESSION['cryptdir'].'cryptographp.php?cfg=0&amp;'.$iSID.'" alt="captcha" />';

        $reload = 1;
        $this->oForm->additionalHeaderData($sScript, $this->_getElementHtmlId());
        $sReloadId = $this->_getElementHtmlId().'_reload';

        if ($this->_navConf('/reloadpic') && is_string(trim(strtolower($this->aElement['reloadpic'])))) {
            $aCaptcha['reload'] = '<a id="'.$sReloadId.'" title="'.(1 == $reload ? '' : $reload).'" style="cursor:pointer"><img src="'.$this->_getPathReload(trim(strtolower($this->aElement['reloadpic']))).'" class="captchapic" /></a>';
        } else {
            $aCaptcha['reload'] = '<a id="'.$sReloadId.'" title="'.(1 == $reload ? '' : $reload).'" style="cursor:pointer"><img src="'.$_SESSION['cryptdir'].'images/reload.png" alt="reload" /></a>';
        }

        $sCopy = $this->oForm->getConfigXML()->getLLLabel($this->aElement['copylabel']);
        $sInput = '<input type="text" name="'.$this->_getElementHtmlName().'" id="'.$this->_getElementHtmlId().'" '.$this->_getAddInputParams().' />';

        // allowed because of $bCustomIncludeScript = TRUE
        $this->includeScripts(
            [
                'reloadurl' => tx_mkforms_util_Div::toWebPath(
                    $_SESSION['cryptdir'].'cryptographp.php?cfg=0&amp;'.$iSID
                ),
            ]
        );

        $aHtmlBag = [
            '__compiled' => $this->_displayLabel($this->getLabel()).$aCaptcha['img'].$aCaptcha['reload'].''.$sCopy.''.$sInput,
            'image' => $aCaptcha['img'],
            'reload' => $aCaptcha['reload'],
            'copylabel' => $sCopy,
            'input' => $sInput,
        ];

        return $aHtmlBag;
    }

    public function _renderOnly($bForAjax = false)
    {
        // bei ajax requests muss hier false zurückgegeben werden,
        // da sonst der captcha ignoriert wird
        return $bForAjax ? false : true;
    }

    public function declareCustomValidationErrors()
    {
        if ($this->getForm()->getDataHandler()->_isFullySubmitted() || $this->_hasToValidateForDraft()) {
            $this->chk_crypt($this->getValue());
        }
    }

    public function _getPathReload()
    {
        if (false !== ($sPath = $this->_navConf('/reloadpic/'))) {
            if ($this->oForm->isRunneable($sPath)) {
                $sPath = $this->getForm()->getRunnable()->callRunnableWidget($this, $sPath);
            }

            if (\Sys25\RnBase\Utility\Strings::isFirstPartOfStr($sPath, 'EXT:')) {
                $sPath = \Sys25\RnBase\Utility\T3General::getIndpEnv('TYPO3_SITE_URL').
                    str_replace(
                        \Sys25\RnBase\Utility\T3General::getIndpEnv('TYPO3_DOCUMENT_ROOT'),
                        '',
                        \Sys25\RnBase\Utility\T3General::getFileAbsFileName($sPath)
                    );
            }
        }

        return $sPath;
    }

    public function chk_crypt($code)
    {
        // V�rifie si le code est correct

        $code = addslashes($code);
        $code = ('true' == $_SESSION['rdt_captcha']['config']['difuplow'] ? $code : strtoupper($code));

        switch (strtoupper($_SESSION['rdt_captcha']['config']['cryptsecure'])) {
            case 'MD5':
                $code = md5($code);
                break;
            case 'SHA1':
                $code = sha1($code);
                break;
        }

        if ($_SESSION['cryptcode'] and ($_SESSION['cryptcode'] == $code)) {
            unset($_SESSION['cryptreload']);

            return true;
        } else {
            //thanks to Hauke Hain : localisation of error message;
            $sAutoKey = 'LLL:'.$this->getAbsName().'.error.nomatch';
            $sError = $this->getLabel($this->_navConf('/errormessage'), $sAutoKey);

            $_SESSION['cryptreload'] = true;
            $this->oForm->_declareValidationError(
                $this->getAbsName(),
                'STANDARD:nomatch',
                $sError
            );

            return false;
        }
    }

    public function _MakeCaptchaConfig()
    {
        ($this->aElement['width']) ? $_SESSION['rdt_captcha']['config']['width'] = $this->aElement['width'] : $_SESSION['rdt_captcha']['config']['width'] = 100;
        ($this->aElement['height']) ? $_SESSION['rdt_captcha']['config']['height'] = $this->aElement['height'] : $_SESSION['rdt_captcha']['config']['height'] = 30;

        /***************************************************
        *CONFIGURATION DU BACKGROUND
        ****************************************************/
        if ('' != trim($this->aElement['bgcolor'])) {
            if (is_string($this->aElement['bgcolor'])) {
                $aColors = explode(',', $this->aElement['bgcolor']);
                $aBgColors['red'] = $aColors['0'];
                $aBgColors['green'] = $aColors['1'];
                $aBgColors['blue'] = $aColors['2'];
            } elseif (is_array($this->aElement['bgcolor'])) {
                $aBgColors['red'] = $this->aElement['bgcolor']['red'];
                $aBgColors['green'] = $this->aElement['bgcolor']['green'];
                $aBgColors['blue'] = $this->aElement['bgcolor']['blue'];
            } else {
                $_SESSION['rdt_captcha']['config']['bgR'] = 255;
                $_SESSION['rdt_captcha']['config']['bgG'] = 255;
                $_SESSION['rdt_captcha']['config']['bgB'] = 255;
            }

            $_SESSION['rdt_captcha']['config']['bgR'] = trim($aBgColors['red']);
            $_SESSION['rdt_captcha']['config']['bgG'] = trim($aBgColors['green']);
            $_SESSION['rdt_captcha']['config']['bgB'] = trim($aBgColors['blue']);
        } else {
            $_SESSION['rdt_captcha']['config']['bgR'] = 255;
            $_SESSION['rdt_captcha']['config']['bgG'] = 255;
            $_SESSION['rdt_captcha']['config']['bgB'] = 255;
        }

        if ('' != trim($this->aElement['transparent_background'])) {
            if ('true' == strtolower($this->aElement['transparent_background'])) {
                $_SESSION['rdt_captcha']['config']['bgClear'] = true;
            } else {
                $_SESSION['rdt_captcha']['config']['bgClear'] = false;
            }
        } else {
            $_SESSION['rdt_captcha']['config']['bgClear'] = false;
        }

        if ('' != trim($this->aElement['frame'])) {
            if ('true' == strtolower($this->aElement['frame'])) {
                $_SESSION['rdt_captcha']['config']['bgFrame'] = true;
            } else {
                $_SESSION['rdt_captcha']['config']['bgFrame'] = false;
            }
        } else {
            $_SESSION['rdt_captcha']['config']['bgFrame'] = false;
        }

        /***************************************************
        *FIN DE LA CONFIGURATION DU BACKGROUND
        ****************************************************/

        /***************************************************
        *CONFIGURATION DU TEXTE
        ****************************************************/

        if (!empty($this->aElement['font']['fontcolor'])) {
            if (is_string($this->aElement['font']['fontcolor'])) {
                $aColors = explode(',', $this->aElement['font']['fontcolor']);
                $aFontColors['red'] = $aColors['0'];
                $aFontColors['green'] = $aColors['1'];
                $aFontColors['blue'] = $aColors['2'];
            } elseif (is_array($this->aElement['font']['fontcolor'])) {
                (is_numeric(trim($this->aElement['font']['fontcolor']['red']))) ? $aFontColors['red'] = $this->aElement['font']['fontcolor']['red'] : $aFontColors['red'] = 0;
                (is_numeric(trim($this->aElement['font']['fontcolor']['green']))) ? $aFontColors['green'] = $this->aElement['font']['fontcolor']['green'] : $aFontColors['green'] = 0;
                (is_numeric(trim($this->aElement['font']['fontcolor']['blue']))) ? $aFontColors['blue'] = $this->aElement['font']['fontcolor']['blue'] : $aFontColors['blue'] = 0;
            } else {
                $_SESSION['rdt_captcha']['config']['charR'] = 255;
                $_SESSION['rdt_captcha']['config']['charG'] = 0;
                $_SESSION['rdt_captcha']['config']['charB'] = 0;
            }

            $_SESSION['rdt_captcha']['config']['charR'] = trim($aFontColors['red']);
            $_SESSION['rdt_captcha']['config']['charG'] = trim($aFontColors['green']);
            $_SESSION['rdt_captcha']['config']['charB'] = trim($aFontColors['blue']);
        } else {
            $_SESSION['rdt_captcha']['config']['charR'] = 255;
            $_SESSION['rdt_captcha']['config']['charG'] = 0;
            $_SESSION['rdt_captcha']['config']['charB'] = 0;
        }

        if (isset($this->aElement['font']['fontcolor']['random'])) {
            if ('true' == strtolower($this->aElement['font']['fontcolor']['random'])) {
                $_SESSION['rdt_captcha']['config']['charcolorrnd'] = true;
            } else {
                $_SESSION['rdt_captcha']['config']['charcolorrnd'] = false;
            }
        } else {
            $_SESSION['rdt_captcha']['config']['charcolorrnd'] = false;
        }

        if (isset($this->aElement['font']['fontcolor']['level'])) {
            switch (strtolower(trim($this->aElement['font']['fontcolor']['level']))) {
                case 'darker':
                    $_SESSION['rdt_captcha']['config']['charcolorrndlevel'] = 1;
                    break;
                case 'dark':
                    $_SESSION['rdt_captcha']['config']['charcolorrndlevel'] = 2;
                    break;
                case 'light':
                    $_SESSION['rdt_captcha']['config']['charcolorrndlevel'] = 3;
                    break;
                case 'lighter':
                    $_SESSION['rdt_captcha']['config']['charcolorrndlevel'] = 4;
                    break;
                default:
                    $_SESSION['rdt_captcha']['config']['charcolorrndlevel'] = 1;
                    break;
            }
        } else {
            $_SESSION['rdt_captcha']['config']['charcolorrndlevel'] = 0;
        }

        if (isset($this->aElement['font']['fontcolor']['alpha']) && is_numeric($this->aElement['font']['fontcolor']['alpha'])) {
            if (is_numeric($this->aElement['font']['fontcolor']['alpha'] >= 127)) {
                $_SESSION['rdt_captcha']['config']['charclear'] = 127;
            } else {
                $_SESSION['rdt_captcha']['config']['charclear'] = $this->aElement['font']['fontcolor']['alpha'];
            }
        } else {
            $_SESSION['rdt_captcha']['config']['charclear'] = 0;
        }

        if ($this->aElement['font']['family'] && '' != trim($this->aElement['font']['family'])) {
            $_SESSION['rdt_captcha']['config']['tfont'] = explode(',', trim($this->aElement['font']['family']));
        } else {
            $_SESSION['rdt_captcha']['config']['tfont'] = ['luggerbu.ttf', 'WAVY.TTF', 'SCRAWL.TTF'];
        }

        if ($this->aElement['authchar']) {
            $_SESSION['rdt_captcha']['config']['charel'] = trim(strtoupper($this->aElement['authchar']));
        } else {
            $_SESSION['rdt_captcha']['config']['charel'] = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789';
        }

        if (false !== $this->_navConf('/font/size')) {
            $_SESSION['rdt_captcha']['config']['crypteasy'] = true;

            if (false !== $this->_navConf('/crypteasy/con')) {
                $_SESSION['rdt_captcha']['config']['charelc'] = trim(strtoupper($this->aElement['crypteasy']['con']));
            } else {
                $_SESSION['rdt_captcha']['config']['charelc'] = 'BCDFGHJKLMNPQRSTVWXZ';
            }

            if (false !== $this->_navConf('/crypteasy/vow')) {
                $_SESSION['rdt_captcha']['config']['charelv'] = trim(strtoupper($this->aElement['crypteasy']['vow']));
            } else {
                $_SESSION['rdt_captcha']['config']['charelv'] = 'AEIOUY';
            }
        } else {
            $_SESSION['rdt_captcha']['config']['crypteasy'] = false;
        }

        if ($this->aElement['difuplow']) {
            if ('true' == strtolower($this->aElement['difuplow'])) {
                $_SESSION['rdt_captcha']['config']['difuplow'] = 'true';
            } else {
                $_SESSION['rdt_captcha']['config']['difuplow'] = 'false';
            }
        } else {
            $_SESSION['rdt_captcha']['config']['difuplow'] = 'false';
        }

        if (false !== $this->_navConf('/nbchar')) {
            $aNum = explode('-', trim(strtolower($this->aElement['nbchar'])));
            $_SESSION['rdt_captcha']['config']['charnbmin'] = min($aNum);
            $_SESSION['rdt_captcha']['config']['charnbmax'] = max($aNum);
        } else {
            $_SESSION['rdt_captcha']['config']['charnbmin'] = 5;
            $_SESSION['rdt_captcha']['config']['charnbmax'] = 5;
        }

        if (false !== $this->_navConf('/charspace') && '' != trim($this->_navConf('/charspace'))) {
            $_SESSION['rdt_captcha']['config']['charspace'] = trim(strtolower($this->aElement['charspace']));
        } else {
            $_SESSION['rdt_captcha']['config']['charspace'] = '20';
        }

        if (false !== $this->_navConf('/font/size')) {
            $aNum = explode('-', trim(strtolower($this->aElement['font']['size'])));
            $_SESSION['rdt_captcha']['config']['charsizemin'] = min($aNum);
            $_SESSION['rdt_captcha']['config']['charsizemax'] = max($aNum);
        } else {
            $_SESSION['rdt_captcha']['config']['charsizemin'] = 12;
            $_SESSION['rdt_captcha']['config']['charsizemax'] = 12;
        }

        if (false !== $this->_navConf('/charangle')) {
            if (is_numeric(trim(strtolower($this->aElement['charangle'])))) {
                $_SESSION['rdt_captcha']['config']['charanglemax'] = trim(strtolower($this->aElement['charangle']));
            } else {
                $_SESSION['rdt_captcha']['config']['charanglemax'] = 0;
            }
        } else {
            $_SESSION['rdt_captcha']['config']['charanglemax'] = 0;
        }

        if (false !== $this->_navConf('/charup')) {
            if ('true' == $this->aElement['charup']) {
                $_SESSION['rdt_captcha']['config']['charup'] = true;
            } else {
                $_SESSION['rdt_captcha']['config']['charup'] = false;
            }
        } else {
            $_SESSION['rdt_captcha']['config']['charup'] = false;
        }

        /***************************************************
        *FIN DE LA CONFIGURATION DU TEXTE
        ****************************************************/

        /***************************************************
        *CONFIGURATION DU BRUIT
        ****************************************************/

        if (false !== $this->_navConf('/noise')) {
            if (false !== $this->_navConf('/noise/pixel')) {
                $aNum = explode('-', trim(strtolower($this->aElement['noise']['pixel'])));
                $_SESSION['rdt_captcha']['config']['noisepxmin'] = min($aNum);
                $_SESSION['rdt_captcha']['config']['noisepxmax'] = max($aNum);
            } else {
                $_SESSION['rdt_captcha']['config']['noisepxmin'] = 250;
                $_SESSION['rdt_captcha']['config']['noisepxmax'] = 250;
            }
            if (false !== $this->_navConf('/noise/line')) {
                $aNum = explode('-', trim(strtolower($this->aElement['noise']['line'])));
                $_SESSION['rdt_captcha']['config']['noiselinemin'] = min($aNum);
                $_SESSION['rdt_captcha']['config']['noiselinemax'] = max($aNum);
            } else {
                $_SESSION['rdt_captcha']['config']['noiselinemin'] = 3;
                $_SESSION['rdt_captcha']['config']['noiselinemax'] = 3;
            }

            if (false !== $this->_navConf('/noise/noisecolor')) {
                switch ($this->aElement['noise']['noisecolor']) {
                    case 'charcolor':
                        $_SESSION['rdt_captcha']['config']['noisecolorchar'] = true;
                        break;
                    case 'backcolor':
                        $_SESSION['rdt_captcha']['config']['noisecolorchar'] = false;
                        break;
                    default:
                        $_SESSION['rdt_captcha']['config']['noisecolorchar'] = true;
                        break;
                }
            }
        } else {
            $_SESSION['rdt_captcha']['config']['noisepxmin'] = 0;
            $_SESSION['rdt_captcha']['config']['noisepxmax'] = 0;

            $_SESSION['rdt_captcha']['config']['noiselinemin'] = 0;
            $_SESSION['rdt_captcha']['config']['noiselinemax'] = 0;

            $_SESSION['rdt_captcha']['config']['noisecolorchar'] = true;
        }

        // Kreise im Captcha
        if (false !== $this->_navConf('/circles')) {
            if (false !== $this->_navConf('/circles/minnumber')) {
                $_SESSION['rdt_captcha']['config']['nbcirclemin'] =
                $this->_navConf('/circles/minnumber');
            } else {
                $_SESSION['rdt_captcha']['config']['nbcirclemin'] = 1;
            }
            if (false !== $this->_navConf('/circles/maxnumber')) {
                $_SESSION['rdt_captcha']['config']['nbcirclemax'] =
                $this->_navConf('/circles/maxnumber');
            } else {
                $_SESSION['rdt_captcha']['config']['nbcirclemax'] = 1;
            }
        }

        /***************************************************
        *FIN DE LA CONFIGURATION DU BRUIT
        ****************************************************/

        /***************************************************
        *CONFIGURATION DU SYSTEME
        ****************************************************/

        if (false !== $this->_navConf('/imgtype')) {
            switch (trim(strtolower($this->aElement['imgtype']))) {
                case 'png':
                    $_SESSION['rdt_captcha']['config']['cryptformat'] = 'png';
                    break;
                case 'jpg':
                    $_SESSION['rdt_captcha']['config']['cryptformat'] = 'jpg';
                    break;
                case 'gif':
                    $_SESSION['rdt_captcha']['config']['cryptformat'] = 'gif';
                    break;
                default:
                    $_SESSION['rdt_captcha']['config']['cryptformat'] = 'png';
                    break;
            }
        } else {
            $_SESSION['rdt_captcha']['config']['cryptformat'] = 'png';
        }

        if ($this->aElement['cryptsecure']) {
            $this->aElement['cryptsecure'] = strtolower($this->aElement['cryptsecure']);
            switch ($this->aElement['cryptsecure']) {
                case 'md5':
                    $_SESSION['rdt_captcha']['config']['cryptsecure'] = 'md5';
                    break;
                case 'sha1':
                    $_SESSION['rdt_captcha']['config']['cryptsecure'] = 'sha1';
                    break;
                default:
                    $_SESSION['rdt_captcha']['config']['cryptsecure'] = 'md5';
                    break;
            }
        } else {
            $_SESSION['rdt_captcha']['config']['cryptsecure'] = 'md5';
        }

        if (false !== $this->_navConf('/timer')) {
            if (false !== $this->_navConf('/timer/time')) {
                if (is_numeric($this->aElement['timer']['time'])) {
                    $_SESSION['rdt_captcha']['config']['cryptusetimer'] = $this->aElement['timer']['time'];
                    if (false !== $this->_navConf('/timer/action')) {
                        switch (trim(strtolower($this->aElement['timer']['action']))) {
                            case 'image':
                                $_SESSION['rdt_captcha']['config']['cryptusertimererror'] = 2;
                                break;
                            case 'wait':
                                $_SESSION['rdt_captcha']['config']['cryptusertimererror'] = 3;
                                break;
                            default:
                                $_SESSION['rdt_captcha']['config']['cryptusertimererror'] = 2;
                                break;
                        }
                    } else {
                        $_SESSION['rdt_captcha']['config']['cryptusertimererror'] = 1;
                    }
                } else {
                    $_SESSION['rdt_captcha']['config']['cryptusetimer'] = 3;
                }
            } else {
                $_SESSION['rdt_captcha']['config']['cryptusetimer'] = 3;
                $_SESSION['rdt_captcha']['config']['cryptusertimererror'] = 3;
            }
        } else {
            $_SESSION['rdt_captcha']['config']['cryptusetimer'] = 0;
            $_SESSION['rdt_captcha']['config']['cryptusertimererror'] = 3;
        }

        if (false !== $this->_navConf('/maxattempt')) {
            if (is_numeric($this->aElement['maxattempt'])) {
                $_SESSION['rdt_captcha']['config']['cryptusemax'] = $this->aElement['maxattempt'];
            } else {
                $_SESSION['rdt_captcha']['config']['cryptusemax'] = 1000000000000000000;
            }
        } else {
            $_SESSION['rdt_captcha']['config']['cryptusemax'] = 1000000000000000000;
        }

        /***************************************************
        *FIN DE LA CONFIGURATION DU SYSTEME
        ****************************************************/

        return false;
    }

    public function majixReload()
    {
        return $this->buildMajixExecuter(
            'reload'
        );
    }
}
