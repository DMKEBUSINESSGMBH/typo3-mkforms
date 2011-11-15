<?php
/**
 * 	@package tx_mkforms
 *  @subpackage tx_mkforms_action
 *  @author Michael Wagner
 *
 *  Copyright notice
 *
 *  (c) 2011 Michael Wagner <michael.wagner@das-medienkombinat.de>
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
 */
require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_rnbase_action_BaseIOC');
tx_rnbase::load('tx_mkforms_forms_Factory');

/**
 * Generic form action base class
 *
 * With the optional $parameter['uid'] the form is initialized.
 *
 * @package tx_mkforms
 * @subpackage tx_mkforms_action
 * @author Michael Wagner
 */
class tx_mkforms_action_FormBase extends tx_rnbase_action_BaseIOC {
	
	/**
	 * Form data
	 *
	 * @var array
	 */
	protected $filledForm = false;

	/**
	 * Form data
	 *
	 * @var array
	 */
	protected $preFilledForm = false;

	/**
	 * @var tx_rnbase_configurations
	 */
	private $configurations;
	
	/**
	 * @var tx_rnbase_parameters
	 */
	private $parameters;

	/**
	 * @var tx_ameosformidable
	 */
	private $form;
	
	/**
	 * Soll der Name des Templates als Name des Prefill Parameters genommen werden? Wenn nicht
	 * per default 'uid'
	 * @var boolean
	 */
	protected $bUseTemplateNameAsPrefillParamName = false;
	
	/**
	 * Enthält Fehlermeldungen (zurzeit vom configCheck).
	 * Diese werden im FE immer mit ausgegeben.
	 * 	(@TODO: ausgabe konfigurierbar machen!)
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Start the dance...
	 *
	 * @param 	tx_rnbase_parameters		$parameters
	 * @param 	tx_rnbase_configurations	$configurations
	 * @param 	tx_lib_spl_arrayObject		$viewData
	 * @return 	string
	 */
	public function handleRequest(&$parameters,&$configurations, &$viewData) {
		$this->configurations =& $configurations;
		$this->parameters =& $parameters;
		$this->form = tx_mkforms_forms_Factory::createForm('generic');
		$confId = $this->getConfId();
		
		// wir prüfen die konfiguration
		$this->configCheck($configurations, $confId);
		if(!empty($this->errors)) {
			return $this->configCheck($configurations, $confId);
		}
		
		// befinden wir uns in einem Test?
	    if($configurations->get($confId.'testmode')) {
	    	$this->form->setTestMode();
	    }
	    
		$this->form->init(
				$this,
				$this->getXmlPath(),
				$this->getPrefillUid(),
				$configurations,
				$confId.'formconfig.'
			);
		
		$viewData->offsetSet('form', $this->form->render());
		$viewData->offsetSet('fullySubmitted', $this->form->isFullySubmitted());
		
		if (is_array($this->filledForm)) {
			$viewData->offsetSet('formData', $this->filledForm);
		}
		
		// Needed in generic view! @todo: sure???
		$viewData->offsetSet('actionConfId', $confId);

		// Set Errors
		$viewData->offsetSet('errors', !empty($this->errors) ? $this->configCheck($configurations, $confId) : false);
		
	}
	
	/**
	 * Gibt den Pfad zum XML zurück
	 * @return string
	 */
	protected function getXmlPath() {
		return $this->getConfigurations()->get($this->getConfId().'xml');
	}

	/**
	 * Wir prüfen die Konfiguration
	 *
	 * @param 	tx_rnbase_configurations 	$configurations
	 * @param 	string 						$confId
	 */
	protected function configCheck($configurations, $confId) {
		
		if(!empty($this->errors)) {
			return '<div style="border:2px solid red; padding:10px; margin: 10px 0; color:red; background: wheat;">'.
				'<h1>MKFORMS - ACTION - FORMBASE</h1>'.
				'<ul><li>'.implode('</li><li>', $this->errors).'</li><ul>'.
			'</div>';
		}
		
		// wurde ein xml gesetzt
		$xmlPath = $configurations->get($confId.'xml');
		if(empty($xmlPath)) {
			$this->errors[] = 'No XML file found (TS: '.$confId.'xml).';
		}
		// existiert das xml
		$absXmlPath = t3lib_div::getFileAbsFileName($xmlPath);
		if(empty($absXmlPath) || !file_exists($absXmlPath)) {
			$this->errors[] = 'The given XML file path (' . $xmlPath . ') doesn\'t exists.';
		}
		
		// ist die formconfig gesetzt
		if(!is_array($configurations->get($confId.'formconfig.'))) {
			$this->errors[] = 'Formconfig not set (TS: '.$confId.'formconfig =< config.tx_mkforms).';
		}
		
		return $this->errors;
	}
	
	/**
	 * Process form data
	 *
	 * This method is called by mkforms via
	 *	<datahandler:RAW>
	 *		<callback>
	 *			<userobj extension="tx_mkforms_util_FormBase" method="processForm" />
	 *		</callback>
	 *	</datahandler:RAW>
	 *
	 * @param array					$data
	 * @param tx_ameosformidable	$form
	 * @param bool					$flattenData Useful for disabling the flattening of data by overwriting this method and calling parent::processForm($data, $form, false)
	 */
	public function processForm($data, &$form, $flattenData=true) {
		// Prepare data
		$confId = $this->getConfId();
		
		// Flatten array
		if ($flattenData) {
			tx_rnbase::load('tx_mkforms_util_FormBase');
			$data = tx_mkforms_util_FormBase::flatArray2MultipleTableStructure($data, $form, $this->getConfigurations(), $confId);
		}
		
		// Hook to handle data
		tx_rnbase_util_Misc::callHook('mkforms','action_formbase_before_processdata',
			array('data' => &$data), $this);
		
		// wir suchen für jede Tabelle eine Update Methode in der Kindklasse
		if($flattenData) {
			foreach($data as $sTable => $aFields){
				$method = 'process'.self::underscoreToCamelCase($sTable).'Data';
				if(method_exists($this, $method)) {
					$data[$sTable] = $this->{$method}($aFields);
				}
			}
		}
		$data = $this->processData($data);
		
		// Hook to handle data
		tx_rnbase_util_Misc::callHook('mkforms','action_formbase_after_processdata',
			array('data' => &$data), $this);
			
		// Fill $this->filledForm with all the post-processed and possibly completed data
		$this->setFormData($data);
	}
	
	/**
	 * @todo: in util div auslagern!
	 * @param unknown_type $sTable
	 */
	protected static function underscoreToCamelCase($sTable){
		$sCamelCase = '';
		foreach(explode('_', $sTable) as $sPart) {
			$sCamelCase .= ucfirst($sPart);
		}
		return $sCamelCase;
	}
	
	/**
	 * Actually process the data, e.g. save it to the table...
	 *
	 * @param 	array 	&$data Form data splitted by tables
	 * @return 	array
	 */
	protected function processData(array $data) {
		return $data;
	}

	/**
	 * Setzt die Formulardaten für den View.
	 *
	 * @param 	array	$data
	 */
	public function setFormData($data = false){
		$this->filledForm = is_array($data) ? $data : false;
	}
	
	/**
	 * Fill form data
	 *
	 * This method is called by mkforms via
	 *	<datahandler:RAW>
	 *		<record>
	 *			<userobj extension="tx_mkforms_util_FormBase" method="fillForm" />
	 *		</record>
	 *	</datahandler:RAW>
	 *
	 * @param 	array					$params
	 * @param 	tx_ameosformidable	$form
	 * @return 	array
	 */
	public function fillForm(array $params, tx_ameosformidable $form, $forceFill = false) {
		if(is_array($this->preFilledForm) && !$forceFill) {
			return $this->preFilledForm;
		}
		
		// Hook to handle data
		tx_rnbase_util_Misc::callHook('mkforms','action_formbase_before_filldata',
			array('data' => &$data), $this);
		
		$data = $this->fillData($params);
		
		// Hook to handle data
		tx_rnbase_util_Misc::callHook('mkforms','action_formbase_after_filldata',
			array('data' => &$data), $this);

		if(!is_array($data) || !count($data)){
			return array();
		}

		$confId = $this->getConfId();
		
		tx_rnbase::load('tx_mkforms_util_FormBase');
		$this->preFilledForm = tx_mkforms_util_FormBase::multipleTableStructure2FlatArray($data, $form, $this->getConfigurations(), $confId);
		
		return $this->preFilledForm;
	}

	/**
	 * Actually process the data, e.g. save it to the table...
	 *
	 * @param 	array 	&$data Form data splitted by tables
	 * @return 	array
	 */
	protected function fillData(array $params) {
		return $params;
		// Test zum vorfüllen: EXT:mkforms/tests/xml/renderlets.xml
		$params['widget']['text'] = 'Default Text';
		// die vorselektierten Werte für mehrere Checkboxen müssen kommasepariert angegebenw werden!
		$params['widget']['checkbox'] = array(8,6);
		$params['widget']['checkbox'] = implode(',',$params['widget']['checkbox']);
		
		$params['widget']['radiobutton'] = 7;
		$params['widget']['listbox'] = 7;
		$params['widget']['checksingle'] = 1;
		$params['widget1']['text'] = 'Default Text 1';
		$params['widget2']['text'] = 'Default Text 2';
		$params['textarea'] = 'Ganz Langer vordefinierter Text';
		return $params;
	}

	/**
	 * Get record uid of data to be used for prefilled
	 *
	 * Overwrite this method to provide the uid of the record
	 * to be used for prefilling the given form.
	 * The table name is defined in the data handler itself.
	 *
	 * Note: Record prefill currently applies only for datahandler:DB.
	 *
	 * @return 	int|false
	 */
	protected function getPrefillUid() {
		// Allow all data types, DON'T restrict to integers!
		// Of course, the respective data handler has to handle
		// complex data types in the right way.
		$sParamName = ($this->useTemplateNameAsPrefillParamName()) ? $this->getTemplateName() : 'uid';
		$uid = $this->getConfigurations()->getParameters()->get($sParamName);
		// Use parameter "uid", if available
		return $uid ? $uid : false;		// FALSE as default - DON'T use NULL!!!
	}
	
	/**
	 * Soll der Template name als perfill parameter name herangezogen werden?
	 * @return bool
	 */
	private function useTemplateNameAsPrefillParamName() {
		return $this->bUseTemplateNameAsPrefillParamName;
	}

	/**
	 * Returns the config of the action to use in form
	 * @return 	tx_rnbase_configurations
	 */
	public function getConfigurations(){
		return $this->configurations;
	}
	
	/**
	 * Returns the parameters of the action to use in form
	 * @return 	tx_rnbase_parameters
	 */
	public function getParameters(){
		return $this->parameters;
	}

	/**
	 * Returns the config of the action to use in form
	 * @return 	tx_ameosformidable
	 */
	public function getForm(){
		return $this->form;
	}

	/**
	 * Gibt das Mayday im Falle eines Fehler aus
	 *
	 * @param 	Exception 	$e
	 * @deprecated 	Das Error-Handling erledigt nun das Form!
	 */
	private static function mayday($e){
		// Nachricht zusammenbauen
		$msg = $e->getFile().' Line: '.$e->getLine(). '<br /><br />'. $e->getMessage();
		// devlog
//		tx_rnbase::load('tx_rnbase_util_Logger');
//		if(tx_rnbase_util_Logger::isFatalEnabled()) {
//			tx_rnbase_util_Logger::fatal($msg, 'mkforms');
//		}
		// mayday
		tx_rnbase::load('tx_mkforms_util_Div');
		tx_mkforms_util_Div::mayday($msg, $this->getForm());
	}

	/**
	 * Gibt den Name der zugehörigen View-Klasse zurück
	 *
	 * @return 	string
	 */
	public function getViewClassName() {
		return 'tx_mkforms_view_Form';
	}

	/**
	 * Liefert die ConfId für die Action.
	 *
	 * @return 	string
	 */
	public function getConfId() {
		return 'generic.';
//		return $this->getTemplateName().'.';
	}
	/**
	 * Gibt den Name des zugehörigen Templates zurück
	 *
	 * @return 	string
	 */
	public function getTemplateName() {
		return 'generic';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/action/class.tx_mkforms_action_FormBase.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/action/class.tx_mkforms_action_FormBase.php']);
}