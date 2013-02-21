<?php
/**
 * @package tx_mkforms
 * @subpackage tx_mkforms_dh
 *
 * Copyright notice
 *
 * (c) 2013 das MedienKombinat GmbH <kontakt@das-medienkombinat.de>
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
 */

require_once t3lib_extMgm::extPath('rn_base', 'class.tx_rnbase.php');
tx_rnbase::load('tx_rnbase_action_BaseIOC');

/**
 * Datahandler um anhand von den Formulardaten E-Mails zu versenden.
 *
 * @see http://wiki.das-medienkombinat.de/index.php?title=Mkforms#datahandler:MAIL_als_toller_powermail_ersatz
 *
 * @package tx_mkforms
 * @subpackage tx_mkforms_dh
 * @author Michael Wagner <michael.wagner@das-medienkombinat.de>
 */
class tx_mkforms_dh_mail_Main extends formidable_maindatahandler {

	/**
	 * Nimmt die Formulardaten und generiert daraus eine E-Mail.
	 * @TODO: die einzelnen engines sollten ausgelagert werden!
	 */
	function _doTheMagic($bShouldProcess = TRUE) {

		// Nur, wenn das Formular abgesendet wurde
		if (!$bShouldProcess) {
			return;
		}

		$data = $this->getFlattenFormData();

		if ($this->defaultFalse('/debugdata')) {
			tx_rnbase_util_Debug::debug($data, 'Flatten Data for Model:');
			return ; // wir erzeugen keine E-Mail, nur den debug!
		}

		$sendMethod = $this->findEngineMethod();

		$params = array();
		$params[0] = $this->getDataModel($data);
		$ret = call_user_func_array(array($this, $sendMethod), $params);


	}

	/**
	 * Flacht verschachtelte Strukturen ab.
	 *
	 * Aus <renderlet:BOX name="main"><childs><renderlet:TEXT name="sub" label="" /></childs></renderlet:BOX>
	 * wird dann anstelle von array('main'=>array('sub'=>'value'))
	 * ein flaches array array('main_sub'=>'value')
	 *
	 * @param array $data
	 * @return array
	 */
	private function getFlattenFormData($data = null) {
		$data = is_array($data) ? $data : $this->getFormData();
		$flatten = array();
		foreach ($data as $field => $value) {
			if (is_array($value)) {
				foreach ($this->getFlattenFormData($value) as $subField => $subValue) {
					$flatten[$field.'_'.$subField] = $subValue;
				}
			}
			else {
				$flatten[$field] = $value;
			}
		}
		return $flatten;
	}

	/**
	 * @return string
	 */
	private function findEngineMethod() {
		$engine = $this->_navConf('/engine');
		$method = 'send'.ucfirst($engine);
		if (method_exists($this, $method)) {
			return $method;
		}
		else {
			$this->getForm()->mayday(
				'Invalid engine "'.$engine.'" configured.'.
				' Valid engines are mkmailer.'.
				' Excample: '.htmlentities('<datahandler:MAIL engine="mkmailer" />')
			);
		}
	}

	/**
	 * Liefert ein Model mit den zu rendernden Daten.
	 *
	 * @param array $record
	 * @return tx_rnbase_model_base
	 */
	private function getDataModel(array $record) {
		$class = $this->_navConf('/model');
		$class = $class ? $class : 'tx_rnbase_model_base';
		if (tx_rnbase::load($class)) {
			$model = tx_rnbase::makeInstance($class, $record);
		}
		else {
			$this->getForm()->mayday(
				'Model "'.$class.'" not found.'
			);
		}
		return $model;
	}

	/**
	 * Liefert die konfigurierte Empfängeradresse.
	 *
	 * @return string
	 */
	private function getMailTo() {
		$mail = $this->_navConf('/mailto');
		if ($mail) {
			return $mail;
		}
		$this->getForm()->mayday(
			'No mail to defined.' .
			' Excample: '.htmlentities('<datahandler:MAIL mailTo="electronic@mail.net" />')
		);
	}

	/**
	 * Liefert die konfigurierte Absenderadresse
	 *
	 * @return string
	 */
	private function getMailFrom() {
		$mail = $this->_navConf('/mailfrom');
		return $mail ? $mail : get_cfg_var('sendmail_from');
	}
	/**
	 * Liefert die konfigurierte Absendername
	 *
	 * @return string
	 */
	private function getMailFromName() {
		$mail = $this->_navConf('/mailfromname');
		return $mail ? $mail : $this->getMailFrom();
	}

	/**
	 *
	 * @param tx_rnbase_model_base $model
	 */
	protected function sendMkmailer(
		tx_rnbase_model_base $model
	) {

		// Das E-Mail-Template holen
		$template = $this->_navConf('/mkmailer/templatekey');
		if (!$template) {
			$this->getForm()->mayday(
				'No template key defined.' .
				' Excample: '.htmlentities('<datahandler:MAIL engine="mkmailer"><mkmailer templateKey="appelrath-template" /></datahandler:MAIL>')
			);
		}
		tx_rnbase::load('tx_mkmailer_util_ServiceRegistry');
		try {
			$templateObj = tx_mkmailer_util_ServiceRegistry
				::getMailService()->getTemplate($template);
		} catch (tx_mkmailer_exceptions_NoTemplateFound $e) {
			$this->getForm()->mayday($e->getMessage());
		}

		// den E-Mail-Empfänger erzeugen
		/* @var $receiver tx_mkmailer_receiver_Email */
		$receiver = tx_rnbase::makeInstance(
			// @TODO: den receiver konfigurierbar machen!
			'tx_mkmailer_receiver_Email',
			$this->getMailTo()
		);

		// Einen E-Mail-Job anlegen.
		/* @var $job tx_mkmailer_mail_MailJob */
		$job  = tx_rnbase::makeInstance(
			'tx_mkmailer_mail_MailJob',
			array($receiver),
			$templateObj
		);

		// Die Daten in die E-Mail Rendern.
		$markerClass = tx_rnbase::makeInstance('tx_rnbase_util_SimpleMarker');
		$formatter = $this->getForm()->getConfigurations()->getFormatter();
		$confId = $this->_navConf('/mkmailer/markerconfid');
		$confId = $confId ? $confId : $this->getForm()->getConfId().'sendmail.';
		$itemName = $this->_navConf('/mkmailer/itemname');
		$itemName = $itemName ? $itemName : 'item';

		//@TODO: auslagern! Das Parsen muss sicher auch in anderen methoden gemacht werden!
		$job->setSubject( // Betreff rendern.
			$markerClass->parseTemplate(
				$job->getSubject(),
				$model, $formatter,
				$confId.strtolower($itemName).'subject.',
				strtoupper($itemName)
			)
		);
		$job->setContentText( // Text Nachricht rendern.
			$markerClass->parseTemplate(
				$job->getContentText(),
				$model, $formatter,
				$confId.strtolower($itemName).'text.',
				strtoupper($itemName)
			)
		);
		$job->setContentHtml(  // HTML Nachricht rendern.
			$markerClass->parseTemplate(
				$job->getContentHtml(),
				$model, $formatter,
				$confId.strtolower($itemName).'html.',
				strtoupper($itemName)
			)
		);

		// solte über das mailtemplate gesteuert werden!
		// optional kann das überschreiben erzwungen werden.
		if ($this->defaultFalse('/mkmailer/forcemailfrom')) {
			$job->setFrom(
				tx_rnbase::makeInstance(
					'tx_mkmailer_mail_Address',
					$this->getMailFrom(), $this->getMailFromName()
				)
			);
		}

		// E-Mail für den versand in die Queue legen.
		tx_mkmailer_util_ServiceRegistry
			::getMailService()->spoolMailJob($job);

		return true;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['/mkforms/dh/mail/class.tx_mkforms_dh_mail_Main.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['/mkforms/dh/mail/class.tx_mkforms_dh_mail_Main.php']);
}
