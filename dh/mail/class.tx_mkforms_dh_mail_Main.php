<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 20013-2016 DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Datahandler um anhand von den Formulardaten E-Mails zu versenden.
 *
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mkforms_dh_mail_Main extends formidable_maindatahandler
{
    /**
     * Nimmt die Formulardaten und generiert daraus eine E-Mail.
     *
     * @param bool $bShouldProcess
     *
     * @TODO: die einzelnen engines sollten ausgelagert werden!
     */
    public function _doTheMagic($bShouldProcess = true)
    {
        // Nur, wenn das Formular abgesendet wurde
        if (!($bShouldProcess && $this->getForm()->getValidationTool()->isAllValid())) {
            return;
        }

        $data = $this->getFlattenFormData();

        // wir erzeugen keine E-Mail, nur den debug!
        if ($this->defaultFalse('/debugdata')) {
            tx_rnbase_util_Debug::debug($data, 'Flatten Data for Model:');

            return;
        }

        $engine = $this->findEngine();

        $params = [];
        $params[0] = $this->getDataModel($data);

        $success = call_user_func_array($engine, $params);

        if ($success) {
            $this->getForm()->getRunnable()->callRunnable(
                $this->_navConf('/onsuccess')
            );
        }
    }

    /**
     * Flacht verschachtelte Strukturen ab.
     *
     * Aus <renderlet:BOX name="main"><childs><renderlet:TEXT name="sub" label="" /></childs></renderlet:BOX>
     * wird dann anstelle von array('main'=>array('sub'=>'value'))
     * ein flaches array array('main_sub'=>'value')
     *
     * @param array|null $data
     *
     * @return array
     */
    private function getFlattenFormData($data = null)
    {
        $data = is_array($data) ? $data : $this->getFormData();
        $flatten = [];
        foreach ($data as $field => $value) {
            if (is_array($value)) {
                foreach ($this->getFlattenFormData($value) as $subField => $subValue) {
                    $flatten[$field.'_'.$subField] = $subValue;
                }
            } else {
                $flatten[$field] = $value;
            }
        }

        return $flatten;
    }

    /**
     * Returns the engine callback to use.
     *
     * @return mixed Callable
     */
    private function findEngine()
    {
        $engine = $this->_navConf('/engine');
        $method = 'send'.ucfirst($engine);
        if (method_exists($this, $method)) {
            return [$this, $method];
        } else {
            $this->getForm()->mayday(
                'Invalid engine "'.$engine.'" configured.'.
                ' Valid engines are mkmailer.'.
                ' Excample: '.htmlentities(
                    '<datahandler:MAIL engine="mkmailer" />'
                )
            );
        }
    }

    /**
     * Liefert ein Model mit den zu rendernden Daten.
     *
     * @param array $record
     *
     * @return tx_rnbase_model_base
     */
    private function getDataModel(array $record)
    {
        $class = $this->_navConf('/model');

        if ($class) {
            $class = $this->getForm()->getRunnable()->callRunnable($class);
        } else {
            $class = 'Tx_Rnbase_Domain_Model_Data';
        }

        return tx_rnbase::makeInstance($class, $record);
    }

    /**
     * Liefert die konfigurierte Empfängeradresse.
     *
     * @return string
     */
    private function getMailTo()
    {
        $mail = $this->_navConf('/mailto');

        if (!$mail) {
            $this->getForm()->mayday(
                'No mail to defined.'.
                ' Excample: '.htmlentities(
                    '<datahandler:MAIL mailTo="electronic@mail.net" />'
                )
            );
        }

        return $this->getForm()->getRunnable()->callRunnable($mail);
    }

    /**
     * Liefert die konfigurierte Absenderadresse.
     *
     * @return string
     */
    private function getMailFrom()
    {
        $mail = $this->_navConf('/mailfrom');

        if ($mail) {
            $mail = $this->getForm()->getRunnable()->callRunnable($mail);
        } else {
            $mail = get_cfg_var('sendmail_from');
        }

        return $mail;
    }

    /**
     * Liefert die konfigurierte Absendername.
     *
     * @return string
     */
    private function getMailFromName()
    {
        $mail = $this->_navConf('/mailfromname');

        if ($mail) {
            $mail = $this->getForm()->getRunnable()->callRunnable($mail);
        } else {
            $mail = $this->getMailFrom();
        }

        return $mail;
    }

    /**
     * Returns a template object.
     *
     * @TODO: add own model/interface, currently the template model from mkmailer is used.
     *
     * @return tx_mkmailer_models_Template
     */
    private function getTemplateObject()
    {
        $templateObj = $this->buildTemplateObject();
        if ($templateObj instanceof tx_mkmailer_models_Template) {
            return $templateObj;
        }

        // Das E-Mail-Template holen
        $template = $this->_navConf('/mkmailer/template/key');
        // fallback, check the old deprecated config!
        if (!$template) {
            $template = $this->_navConf('/mkmailer/templatekey');
            if ($template) {
                Tx_Rnbase_Utility_T3General::deprecationLog(
                    'MKFORMS ('.$this->getForm()->_xmlPath.'):'.
                    ' config key "/mkmailer/templatekey" is deprecated,'.
                    ' use "/mkmailer/template/key" instead.'
                );
            }
        }
        if (!$template) {
            // check for direct content, instead of a template key.
            $this->getForm()->mayday(
                'No template key defined.'.
                ' Excample: '.LF.htmlentities(
                    '<datahandler:MAIL engine="mkmailer">'.LF.
                    '    <mkmailer>'.LF.
                    '        <template key="general-contact" />'.LF.
                    '    </mkmailer>'.LF.
                    '</datahandler:MAIL>'
                )
            );
        }
        try {
            $templateObj = tx_mkmailer_util_ServiceRegistry::getMailService()->getTemplate($template);
        } catch (tx_mkmailer_exceptions_NoTemplateFound $e) {
            $this->getForm()->mayday($e->getMessage());
        }

        return $templateObj;
    }

    /**
     * Builds a teplate object by xml config.
     *
     * @return tx_mkmailer_models_Template
     */
    private function buildTemplateObject()
    {
        /* @var $templateObj tx_mkmailer_models_Template */
        $templateObj = tx_rnbase::makeInstance(
            'tx_mkmailer_models_Template'
        );

        // set the contents
        foreach (['subject', 'contenttext', 'contenthtml'] as $key) {
            $content = $this->_navConf('/mkmailer/template/'.$key);
            if (is_array($content)) {
                $content = tx_rnbase_util_Templates::getSubpartFromFile(
                    $content['file'],
                    $content['subpart']
                );
            }
            $templateObj->setProperty($key, (string) $content);
        }

        if ($this->_navConf('/mailfrom')) {
            $templateObj->setProperty(
                'mail_from',
                $this->_navConf('/mailfrom')
            );
        }
        if ($this->_navConf('/mailfromname')) {
            $templateObj->setProperty(
                'mail_fromName',
                $this->_navConf('/mailfromname')
            );
        }

        // the template is only valid with a subject and contenttext!
        if (!$templateObj->getSubject() || !$templateObj->getContenttext()) {
            return null;
        }

        return $templateObj;
    }

    /**
     * Sends a mail via mkmailer.
     *
     * @param Tx_Rnbase_Domain_Model_DataInterface $model
     */
    protected function sendMkmailer(
        Tx_Rnbase_Domain_Model_DataInterface $model
    ) {
        // Das E-Mail-Template holen
        $templateObj = $this->getTemplateObject();

        // den E-Mail-Empfänger erzeugen
        /* @var $receiver tx_mkmailer_receiver_Email */
        $receiver = tx_rnbase::makeInstance(
            // @TODO: den receiver konfigurierbar machen!
            'tx_mkmailer_receiver_Email',
            $this->getMailTo()
        );

        $this->parseMail($templateObj, $model);

        // Einen E-Mail-Job anlegen.
        /* @var $job tx_mkmailer_mail_MailJob */
        $job = tx_rnbase::makeInstance(
            'tx_mkmailer_mail_MailJob',
            [$receiver],
            $templateObj
        );

        // solte über das mailtemplate gesteuert werden!
        // optional kann das überschreiben erzwungen werden.
        if ($this->defaultFalse('/mkmailer/forcemailfrom')) {
            $job->setFrom(
                tx_rnbase::makeInstance(
                    'tx_mkmailer_mail_Address',
                    $this->getMailFrom(),
                    $this->getMailFromName()
                )
            );
        }

        // Hook to handle data before sending
        tx_rnbase_util_Misc::callHook(
            'mkforms',
            'dh_mail_beforeSpoolMailJob',
            [
                'job' => &$job,
                'templateObj' => $templateObj,
                'model' => $model,
                'receiver' => $receiver,
                'form' => $this->getForm(),
            ],
            $this
        );

        // E-Mail für den versand in die Queue legen.
        $service = tx_mkmailer_util_ServiceRegistry::getMailService();

        if ($this->_defaultTrue('/mkmailer/usequeue')) {
            $service->spoolMailJob($job);
        } else {
            $service->executeMailJob(
                $job,
                $this->getForm()->getConfigurations(),
                'sendmails.'
            );
        }

        return true;
    }

    /**
     * Parses the content of the mail.
     *
     * @param object                               $content With setter for subject contenttext, contenthtml
     * @param Tx_Rnbase_Domain_Model_DataInterface $model
     */
    protected function parseMail(
        $content,
        Tx_Rnbase_Domain_Model_DataInterface $model
    ) {
        // Die Daten in die E-Mail Rendern.
        foreach ([
            'subject',
            'contenttext',
            'contenthtml',
            'mail_from',
            'mail_fromName',
            'mail_bcc',
        ] as $key) {
            if (!$content->getProperty($key)) {
                continue;
            }
            $content->setProperty(
                $key,
                $this->parseContent(
                    $content->getProperty($key),
                    $model,
                    $key.'.'
                )
            );
        }
    }

    /**
     * Parses the data into the content.
     *
     * @param string                               $content
     * @param Tx_Rnbase_Domain_Model_DataInterface $model
     * @param string                               $fieldId
     *
     * @return string
     */
    protected function parseContent(
        $content,
        Tx_Rnbase_Domain_Model_DataInterface $model,
        $fieldId = ''
    ) {
        // @TODO: make marker class configurable
        $markerClass = tx_rnbase::makeInstance('tx_rnbase_util_SimpleMarker');
        $formatter = $this->getForm()->getConfigurations()->getFormatter();

        $itemName = $this->_navConf('/mkmailer/itemname');
        $itemName = $itemName ? $itemName : 'item';

        $confId = $this->_navConf('/mkmailer/markerconfid');
        if (empty($confId)) {
            $confId = $this->getForm()->getConfId().'sendmail.'.strtolower($itemName).'.';
        }

        $content = $markerClass->parseTemplate(
            $content,
            $model,
            $formatter,
            $confId.$fieldId,
            strtoupper($itemName)
        );

        return $content;
    }
}

if (defined('TYPO3_MODE')
    && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['/mkforms/dh/mail/class.tx_mkforms_dh_mail_Main.php']
) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['/mkforms/dh/mail/class.tx_mkforms_dh_mail_Main.php'];
}
