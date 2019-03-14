<?php
namespace DMK\MkForms\Frontend;

use Sys25\RnBase\Frontend\Controller\AbstractAction;
use Sys25\RnBase\Frontend\Request\RequestInterface;
use Sys25\RnBase\Configuration\ConfigurationInterface;

/**
 * @package    tx_mkforms
 * @subpackage tx_mkforms_action
 * @author     Michael Wagner
 *
 *  Copyright notice
 *
 *  (c) 2019 Michael Wagner <dev@dmk-business.de>
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
\tx_rnbase::load('tx_mkforms_forms_Factory');

/**
 * Generic form action base class
 *
 * With the optional $parameter['uid'] the form is initialized.
 *
 * @package    tx_mkforms
 * @subpackage tx_mkforms_action
 * @author     Michael Wagner
 */
class FormBase extends AbstractAction
{

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
     * @var \tx_mkforms_forms_Base
     */
    private $form;

    /**
     * Soll der Name des Templates als Name des Prefill Parameters genommen werden? Wenn nicht
     * per default 'uid'
     *
     * @var bool
     */
    protected $bUseTemplateNameAsPrefillParamName = false;

    /**
     * Enthält Fehlermeldungen (zurzeit vom configCheck).
     * Diese werden im FE immer mit ausgegeben.
     *    (@TODO: ausgabe konfigurierbar machen!)
     *
     * @var array
     */
    protected $errors = array();

    private $configurations;

    /**
     * Start the dance...
     *
     * @param RequestInterface $request
     *
     * @return    string
     */
    protected function handleRequest(RequestInterface $request)
    {
        $configurations = $request->getConfigurations();
        $this->configurations = $configurations;
        $this->form = \tx_mkforms_forms_Factory::createForm('generic');
        $confId = $this->getConfId();

        // wir prüfen die konfiguration
        $this->configCheck($configurations, $confId);
        if (!empty($this->errors)) {
            return $this->configCheck($configurations, $confId);
        }

        // befinden wir uns in einem Test? vor allem notwendig wenn
        // extbase installiert ist
        if ($configurations->get($confId . 'testmode')) {
            $this->form->setTestMode();
        }

        $this->form->init(
            $this,
            $this->getXmlPath($configurations, $confId),
            $this->getPrefillUid($request),
            $configurations,
            $confId . 'formconfig.'
        );


        $request->getViewContext()->offsetSet('form', $this->form->render());
        $request->getViewContext()->offsetSet('fullySubmitted', $this->form->isFullySubmitted());
        $request->getViewContext()->offsetSet('hasValidationErrors', $this->form->hasValidationErrors());

        if (is_array($this->filledForm)) {
            $request->getViewContext()->offsetSet('formData', $this->filledForm);
        }

        // Needed in generic view! @todo: sure???
        $request->getViewContext()->offsetSet('actionConfId', $confId);

        // Set Errors
        $request->getViewContext()->offsetSet('errors', !empty($this->errors) ? $this->configCheck($configurations, $confId) : false);

        $this->handleRedirect($request->getViewContext()->offsetGet('redirect_parameters'), $this->form, $configurations, $confId);
    }

    /**
     * Gibt es einen Redirect? Bei Bedarf kann diese Methode
     * in einem eigenen View überschrieben werden
     *
     * @param array $params
     * @param \tx_mkforms_forms_IForm $form
     * @param ConfigurationInterface $configurations
     * @param string $confId
     *
     * @return  void
     */
    // @codingStandardsIgnoreStart (interface/abstract mistake)
    protected function handleRedirect($params, \tx_mkforms_forms_IForm $form, ConfigurationInterface $configurations, $confId)
    {
        // @codingStandardsIgnoreEnd

        if ((
            // redirect if fully submitted
            $this->form->isFullySubmitted()
            // if there are no validation errors
            && !$this->form->hasValidationErrors()
            // and redirect configured
            && (
                $configurations->getBool($confId . 'redirect') ||
                $configurations->get($confId . 'redirect.pid')
                )
        )) {
            // Speichern wir die Sessiondaten vor dem Redirect? Die würden sonst verloren gehen!
            $GLOBALS['TSFE']->fe_user->storeSessionData();

            $link = $this->createRedirectLink($viewData, $configurations, $confId);
            $link = $configurations->createLink();
            $link->initByTS($configurations, $confId.'redirect.', is_array($params) ? $params : array());
            $link->redirect();
            // Und tschüss, ab hier passiert nix mehr.
            exit('REDIRECTED!');
        }
    }

    /**
     * Gibt den Pfad zum XML zurück
     *
     * @param    \tx_rnbase_configurations $configurations
     * @param    string                   $confId
     *
     * @return string
     */
    protected function getXmlPath($configurations, $confId)
    {
        return $configurations->get($confId . 'xml');
    }

    /**
     * Wir prüfen die Konfiguration
     *
     * @param    \tx_rnbase_configurations $configurations
     * @param    string                   $confId
     *
     * @return    array
     */
    protected function configCheck($configurations, $confId)
    {

        // wir prüfen die configuration wenn configCheck nicht gesetzt oder wahr ist.
        if (!(is_null($configCheck = $configurations->get('configCheck')) || $configCheck)) {
            return false;
        }

        if (!empty($this->errors)) {
            return '<div style="border:2px solid red; padding:10px; margin: 10px 0; color:red; background: wheat;">'
            . '<h1>MKFORMS - ACTION - FORMBASE</h1>' . '<p>incomplete typoscript configuration found for "' . $confId . '"</p>'
            . '<ul><li>' . implode('</li><li>', $this->errors) . '</li><ul>' . '</div>';
        }

        // wurde ein xml gesetzt
        $xmlPath = $configurations->get($confId . 'xml');
        if (empty($xmlPath)) {
            $this->errors[] = 'No XML file found (TS: ' . $confId . 'xml).';
        }
        // existiert das xml
        $absXmlPath = \Tx_Rnbase_Utility_T3General::getFileAbsFileName($xmlPath);
        if (empty($absXmlPath) || !file_exists($absXmlPath)) {
            $this->errors[] = 'The given XML file path (' . $xmlPath . ') doesn\'t exists.';
        }

        // ist die formconfig gesetzt
        if (!is_array($configurations->get($confId . 'formconfig.'))) {
            $this->errors[] = 'Formconfig not set (TS: ' . $confId . 'formconfig =< config.tx_mkforms).';
        }

        return $this->errors;
    }

    /**
     * Process form data
     *
     * This method is called by mkforms via
     *    <datahandler:RAW>
     *        <callback>
     *            <userobj extension="tx_mkforms_util_FormBase" method="processForm" />
     *        </callback>
     *    </datahandler:RAW>
     *
     * @param array $data
     * @param \tx_mkforms_forms_Base $form
     * @param bool $flattenData Useful for disabling the flattening of data by overwriting this method and calling
     *                                        parent::processForm($data, $form, false)
     */
    public function processForm($data, $form, $flattenData = true)
    {
        // Prepare data
        $confId = $this->getConfId();

        // Flatten array
        if ($flattenData) {
            \tx_rnbase::load('tx_mkforms_util_FormBase');
            $data = \tx_mkforms_util_FormBase::flatArray2MultipleTableStructure($data, $form, $this->getConfigurations(), $confId);
        }

        // Hook to handle data
        \tx_rnbase_util_Misc::callHook(
            'mkforms',
            'action_formbase_before_processdata',
            array('data' => &$data),
            $this
        );

        // wir suchen für jede Tabelle eine Update Methode in der Kindklasse
        if ($flattenData) {
            foreach ($data as $sTable => $aFields) {
                $method = 'process' . \tx_mkforms_util_Div::toCamelCase($sTable) . 'Data';
                if (method_exists($this, $method)) {
                    $data[$sTable] = $this->{$method}($aFields);
                }
            }
        }
        $data = $this->processData($data);

        // Hook to handle data
        \tx_rnbase_util_Misc::callHook(
            'mkforms',
            'action_formbase_after_processdata',
            array('data' => &$data),
            $this
        );

        // Fill $this->filledForm with all the post-processed and possibly completed data
        $this->setFormData($data);

        $this->handleUploads($data);
    }

    /**
     * Actually process the data, e.g. save it to the table...
     *
     * @param    array $data Form data splitted by tables
     *
     * @return    array
     */
    protected function processData(array $data)
    {
        return $data;
    }

    /**
     *
     * @param array $data
     */
    protected function handleUploads($data)
    {
        $form = $this->getForm();
        //update newEntryId to create dam references
        if (array_key_exists('newEntryId', $data)) {
            // newEntryId steht im creation mode und auch sonst zur verfügung
            $form->getDataHandler()->newEntryId = $data['newEntryId'];
        }
        //update dam references
        $tempId = $form->getDataHandler()->entryId;
        foreach ($this->getForm()->getWidgetNames() as $rdtName) {
            if (($widget = $form->getWidget($rdtName)) && ($widget instanceof \tx_mkforms_widgets_mediaupload_Main)
                && method_exists(
                    $widget,
                    'handleCreation'
                )
            ) {
                if ($widget->getEntryId()) {
                    $widget->handleCreation();
                }
            }
        }
        $form->getDataHandler()->entryId = $tempId;
    }

    /**
     * Setzt die Formulardaten für den View.
     *
     * @param    array $data
     */
    public function setFormData($data = false)
    {
        $this->filledForm = is_array($data) ? $data : false;
    }

    /**
     * Fill form data
     *
     * This method is called by mkforms via
     *    <datahandler:RAW>
     *        <record>
     *            <userobj extension="tx_mkforms_util_FormBase" method="fillForm" />
     *        </record>
     *    </datahandler:RAW>
     *
     * @param array $params
     * @param \tx_mkforms_forms_Base $form
     *
     * @return array
     */
    public function fillForm(array $params, \tx_mkforms_forms_Base $form, $forceFill = false)
    {
        if (is_array($this->preFilledForm) && !$forceFill) {
            return $this->preFilledForm;
        }

        // Hook to handle data
        \tx_rnbase_util_Misc::callHook(
            'mkforms',
            'action_formbase_before_filldata',
            ['data' => &$params],
            $this
        );

        $data = $this->fillData($params);

        // Hook to handle data
        \tx_rnbase_util_Misc::callHook(
            'mkforms',
            'action_formbase_after_filldata',
            array('data' => &$data),
            $this
        );

        $confId = $this->getConfId();
        \tx_rnbase::load('tx_mkforms_util_FormBase');

        if (!is_array($data) || empty($data)) {
            $data = array();
            // @see self::flatArray2MultipleTableStructure -> addfields
            $addFields = $this->getConfigurations()->get($confId . 'addfields.', true);
            // Felder setzen, überschreiben oder löschen
            if (is_array($addFields) && count($addFields)) {
                $data = \tx_mkforms_util_FormBase::addFields($data, $addFields);
            }

            return $data;
        }

        $this->preFilledForm = \tx_mkforms_util_FormBase::multipleTableStructure2FlatArray(
            $data,
            $form,
            $this->getConfigurations(),
            $confId
        );

        return $this->preFilledForm;
    }

    /**
     * Actually fill the data to be published in form
     *
     * @param    array $params Parameters from the form
     *
     * @return    array
     */
    protected function fillData(array $params)
    {
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
     * @return    int|false
     */
    protected function getPrefillUid(RequestInterface $request)
    {
        // Allow all data types, DON'T restrict to integers!
        // Of course, the respective data handler has to handle
        // complex data types in the right way.
        $sParamName = ($this->useTemplateNameAsPrefillParamName()) ? $this->getTemplateName() : 'uid';
        $uid = $request->getParameters()->get($sParamName);

        // Use parameter "uid", if available
        return $uid ? $uid : false;        // FALSE as default - DON'T use NULL!!!
    }

    /**
     * Soll der Template name als perfill parameter name herangezogen werden?
     *
     * @return bool
     */
    private function useTemplateNameAsPrefillParamName()
    {
        return $this->bUseTemplateNameAsPrefillParamName;
    }

    /**
     * Returns the form instance
     *
     * @return \tx_mkforms_forms_Base
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Gibt den Name der zugehörigen View-Klasse zurück
     *
     * @return    string
     */
    protected function getViewClassName()
    {
        // I don't really like this access to configurations here...
        $class = $this->configurations->get($this->getConfId() . 'viewClassName');

        return $class ? $class : FormView::class;
    }

    /**
     * Gibt den Name des zugehörigen Templates zurück.
     *
     * Das kann so bleiben da dann immer das form template (formonly)
     * von mkforms genutzt wird. Also eigentlich NIE überschreiben.
     * Nur das TS in die eigene ConfId übernehmen:
     *
     * plugin.tx_myext {
     *        genericTemplate =< plugin.tx_mkforms.genericTemplate
     *        myConfId =< lib.mkforms.formbase
     * }
     *
     * @return    string
     */
    protected function getTemplateName()
    {
        return 'generic';
    }
}
