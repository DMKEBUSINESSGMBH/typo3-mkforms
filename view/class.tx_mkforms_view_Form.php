<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2011 - 2017 DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
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
 * Generic form view.
 *
 * @author Michael Wagner
 */
class tx_mkforms_view_Form extends \Sys25\RnBase\Frontend\View\Marker\BaseView
{
    /**
     * @var \Sys25\RnBase\Frontend\Request\RequestInterface
     */
    protected $request;

    public function render($view, Sys25\RnBase\Frontend\Request\RequestInterface $request)
    {
        $this->request = $request;

        return parent::render($view, $request);
    }

    /**
     * Do the output rendering.
     *
     * @param string                     $template
     * @param RequestInterface           $configurations
     * @param \tx_rnbase_util_FormatUtil $formatter
     *
     * @return mixed Ready rendered output or HTTP redirect
     */
    protected function createOutput($template, Sys25\RnBase\Frontend\Request\RequestInterface $request, $formatter)
    {
        // Winn konfiguriert, einen redirekt nach erfolgreichem absenden der form durchführen
        $viewData = $this->request->getViewContext();
        $this->handleRedirect();

        $confId = $this->request->getConfId();

        $markerArray = $subpartArray = $wrappedSubpartArray = [];

        // Wir holen die Daten von der Action ab
        // @TODO: mal auslagern! (handleFormData)
        $data = null;
        if ($viewData->offsetExists('formData') && ($data = $viewData->offsetGet('formData'))) {
            // Successfully filled in form?
            if (is_array($data)) {
                // else:

                $markerArrays = $subpartArrays = $wrappedSubpartArrays = [];

                foreach ($data as $key => $values) {
                    $currentMarkerPrefix = strtoupper($key).'_';
                    $currentConfId = $confId.$key.'.';
                    /*
                     * @TODO: bei den Values sollte man auch Objekte übergeben können und die
                     * Markerklasse wird konfiguriert
                     * @TODO: Lister ausgeben
                     * @TODO: sollte über markerklassen geregelt werden!
                     *
                     * mwagner: Das hier ist eine generische Ausgabe.
                     * Markerklassen oder Listen sind nicht nötig.
                     * Wenn spezielle Markerklassen und Listen benötigt werden,
                     * müssen für den speziellen Fall die add Methoden
                     * oder die globale addAdditionalMarkers Methode angelegt werden!
                     */
                    if (\Sys25\RnBase\Frontend\Marker\BaseMarker::containsMarker($template, $currentMarkerPrefix)) {
                        $currentSubpartArray = $currentWrappedSubpartArray = [];
                        $currentMarkerArray = $formatter->getItemMarkerArrayWrapped(
                            $values,
                            $currentConfId,
                            0,
                            $currentMarkerPrefix,
                            null
                        );
                        // wir suchen für jede Tabelle eine parse Methode in der Kindklasse!
                        $method = 'add'.tx_mkforms_util_Div::toCamelCase($key).'Markers';
                        if (method_exists($this, $method)) {
                            $template = $this->{$method}(
                                $values, $currentMarkerPrefix,
                                $currentMarkerArray, $currentSubpartArray, $currentWrappedSubpartArray,
                                $currentConfId, $formatter, $template, $viewData
                            );
                        }
                        if (!empty($currentMarkerArray)) {
                            $markerArrays[] = $currentMarkerArray;
                        }
                        if (!empty($currentSubpartArray)) {
                            $subpartArrays[] = $currentSubpartArray;
                        }
                        if (!empty($currentWrappedSubpartArray)) {
                            $wrappedSubpartArrays[] = $currentWrappedSubpartArray;
                        }
                    }
                }
                // die marker arrays zusammenführen
                $markerArray = empty($markerArrays) ? [] : call_user_func_array('array_merge', $markerArrays);
                $subpartArray = empty($subpartArrays) ? [] : call_user_func_array('array_merge', $subpartArrays);
                $wrappedSubpartArray = empty($wrappedSubpartArrays) ? [] : call_user_func_array('array_merge', $wrappedSubpartArrays);
            }
        }

        $template = $this->addAdditionalMarkers(
            $data,
            'DATA',
            $markerArray,
            $subpartArray,
            $wrappedSubpartArray,
            $confId,
            $formatter,
            $template,
            $viewData
        );

        $markerArray['###FORM###'] = $viewData->offsetGet('form');
        $out = \Sys25\RnBase\Frontend\Marker\Templates::substituteMarkerArrayCached(
            $template,
            $markerArray,
            $subpartArray,
            $wrappedSubpartArray
        );

        // Fehler ausgeben, wenn gesetzt.
        if (strlen($errors = $viewData->offsetGet('errors')) > 0) {
            $out .= $errors;
        }

        return $out;
    }

    /**
     * Beispiel Methode um susätzliche marker zu füllen oder das Template zu parsen!
     *
     * @param array                     $data
     * @param string                    $markerPrefix
     * @param array                     $markerArray
     * @param array                     $subpartArray
     * @param array                     $wrappedSubpartArray
     * @param \Sys25\RnBase\Frontend\Marker\FormatUtil $formatter
     * @param string                    $template
     *
     * @return string
     */
    // @codingStandardsIgnoreStart (interface/abstract mistake)
    protected function addAdditionalMarkers(
        $data,
        $markerPrefix,
        &$markerArray,
        &$subpartArray,
        &$wrappedSubpartArray,
        $confId,
        &$formatter,
        $template,
        $viewData
    ) {
        // @codingStandardsIgnoreEnd

        // links parsen
        // @TODO: über rnbase simple marker parsen?
        $linkIds = $formatter->getConfigurations()->getKeyNames($confId.'links.');
        foreach ($linkIds as $linkId) {
            $params = $formatter->getConfigurations()->get(
                $confId.'links.'.$linkId.'.params.'
            );
            \Sys25\RnBase\Frontend\Marker\BaseMarker::initLink(
                $markerArray,
                $subpartArray,
                $wrappedSubpartArray,
                $formatter,
                $confId,
                $linkId,
                $markerPrefix,
                empty($params) ? [] : $params,
                $template
            );
        }

        return $template;
    }

    /**
     * Gibt es einen Redirect? Bei Bedarf kann diese Methode
     * in einem eigenen View überschrieben werden.
     *
     * @param ArrayObject              $viewData
     * @param \Sys25\RnBase\Configuration\Processor $configurations
     */
    protected function handleRedirect()
    {
        $viewData = $this->request->getViewContext();
        $configurations = $this->request->getConfigurations();

        $confId = $this->request->getConfId();
        if (
            // redirect if fully submitted
            $viewData->offsetGet('fullySubmitted')
            // if there are no validation errors
            && !$viewData->offsetGet('hasValidationErrors')
            && !empty($configurations->get($confId.'redirect.pid'))
        ) {
            // Speichern wir die Sessiondaten vor dem Redirect? Die würden sonst verloren gehen!
            $GLOBALS['TSFE']->fe_user->storeSessionData();

            $link = $this->createRedirectLink($viewData, $configurations, $confId);
            $link->redirect();
            // Und tschüss, ab hier passiert nix mehr.
            exit('REDIRECTED!');
        }
    }

    /**
     * Erzeugt den Link für den Redirect. Kind-Klassen haben die Möglchkeit diese Methode zu überschreiben.
     *
     * @param ArrayObject              $viewData
     * @param \Sys25\RnBase\Configuration\Processor $configurations
     * @param string                   $confId
     */
    // @codingStandardsIgnoreStart (interface/abstract mistake)
    protected function createRedirectLink($viewData, $configurations, $confId)
    {
        // @codingStandardsIgnoreStart (interface/abstract mistake)
        $params = $viewData->offsetExists('redirect_parameters') ? $viewData->offsetGet('redirect_parameters') : [];
        $link = $configurations->createLink();
        $link->initByTS($configurations, $confId.'redirect.', is_array($params) ? $params : []);

        return $link;
    }

    /**
     * Subpart der im HTML-Template geladen werden soll. Dieser wird der Methode
     * createOutput automatisch als $template übergeben.
     *
     * @return string
     */
    public function getMainSubpart(Sys25\RnBase\Frontend\View\ContextInterface $viewData)
    {
        return $this->request->getConfigurations()->get($this->request->getConfId().'mainSubpart') ?: '###DATA###';
    }
}
