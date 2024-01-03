<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 René Nitzsche (dev@dmk-business.de)
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
 * Factory for forms.
 */
interface tx_mkforms_forms_IForm
{
    /**
     * @param                          object          Parent extension using FORMidable
     * @param                          mixed           Absolute path to the XML configuration file
     * @param int                      $iForcedEntryId :
     * @param Sys25\RnBase\Configuration\Processor $configurations TS-Configuration
     * @param string                   $confid         ;
     */
    public function init(&$oParent, $mXml, $iForcedEntryId = false, $configurations = false, $confid = '');

    public function getFormId();

    /**
     * Return the typoscript configurations object.
     *
     * @return Sys25\RnBase\Configuration\Processor
     */
    public function getConfigurations();

    /**
     * Basic typoscript confid-path.
     *
     * @return string
     */
    public function getConfId();

    /**
     * Returns a value from TS configurations. The confid will be used relativ to $this->confid.
     *
     * @param string $confid
     *
     * @return mixed
     */
    public function getConfTS($confid);

    /**
     * Liefert den aktuellen DataHandler.
     *
     * @return formidable_maindatahandler
     */
    public function getDataHandler();
}
