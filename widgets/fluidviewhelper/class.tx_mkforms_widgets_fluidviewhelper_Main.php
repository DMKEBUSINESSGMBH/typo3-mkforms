<?php
/***************************************************************
 *  Copyright notice
 *
 * (c) 2014 DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
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
 * widget für view helper.
 *
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mkforms_widgets_fluidviewhelper_Main extends formidable_mainrenderlet
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $_objectManager = null;
    /**
     * the viewhelper class to use.
     * it was build by the viewhelper config from xml.
     *
     * @var string
     */
    protected $_viewHelperClass = null;

    /**
     * erzeugt den object manager, um die helper zu instanzieren.
     *
     * @return \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected function getObjectManager()
    {
        if (null === $this->_objectManager) {
            $this->_objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                'TYPO3\\CMS\\Extbase\\Object\\ObjectManager'
            );
        }

        return $this->_objectManager;
    }

    /**
     * creates the view helper class name.
     *
     * @return string
     */
    protected function getViewHelperClass()
    {
        if (null === $this->_viewHelperClass) {
            $viewHelper = null;
            $helperClass = $this->_navConf('/viewhelper');
            try {
                $viewHelper = $this->getObjectManager()->get($helperClass);
            } catch (\TYPO3\CMS\Extbase\Object\Container\Exception\UnknownObjectException $e) {
                // try to add the fluid base namespace
                try {
                    $viewHelper = $this->getObjectManager()->get(
                        '\\TYPO3\\CMS\\Fluid\\ViewHelpers\\'.ucfirst($helperClass).'ViewHelper'
                    );
                } catch (\TYPO3\CMS\Extbase\Object\Container\Exception\UnknownObjectException $e) {
                }
            }
            if (!$viewHelper instanceof \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper) {
                throw new Exception('Could not find ViewHelperClass: '.$helperClass);
            }
            $this->_viewHelperClass = get_class($viewHelper);
        }

        return $this->_viewHelperClass;
    }

    /**
     * creates the view helper.
     *
     * @return \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
     */
    protected function getViewHelper()
    {
        $helperClass = $this->getViewHelperClass();
        $viewHelper = $this->getObjectManager()->get($helperClass);
        $viewHelper->setArguments($this->getArguments());

        return $viewHelper;
    }

    /**
     * liefert die parameter aus dem xml.
     *
     * @return array
     */
    protected function getParams()
    {
        $params = $this->_navConf('/params');
        $params = is_array($params) ? $params : [];

        return $this->getForm()->getRunnable()->parseParams($params);
    }

    /**
     * erzeugt die parameter, welche dem helper übergeben werden.
     *
     * @return array
     */
    protected function getArguments()
    {
        $params = $this->getParams();
        foreach ($params as $key => $value) {
            switch ($value) {
                case 'rdt:value':
                    $value = $this->getValue();
                    break;
                case 'true':
                    $value = true;
                    break;
                case 'false':
                    $value = false;
                    break;
            }
            if ($value !== $params[$key]) {
                $params[$key] = $value;
            }
        }

        return $params;
    }

    /**
     * (non-PHPdoc).
     *
     * @see formidable_mainrenderlet::_render()
     */
    public function _render()
    {
        $label = $this->getLabel();

        $error = [];
        try {
            $rendered = $this->getViewHelper()->initializeArgumentsAndRender();
        } catch (Exception $e) {
            $rendered = '<span class="error">'.$e->getMessage().'</span>';
            $error = [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }

        $htmlBag = [
            '__compiled' => $this->_displayLabel($label).$rendered,
            'rendered' => $rendered,
            'label' => $label,
            'value' => $this->getValue(),
        ];

        if (!empty($error)) {
            $htmlBag['renderError'] = true;
            $htmlBag['renderError.'] = $error;
        }

        return $htmlBag;
    }
}
