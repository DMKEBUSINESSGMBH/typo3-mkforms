<?php

/**
 * Diese Klasse bindet das notwendige Javascript für ein.
 * Die Konfiguration erfolgt vollständig per Typoscript.
 *
 * config.tx_mkforms.jsframework.jscore = jquery | prototype
 * config.tx_mkforms.jsframework.jscore.tx_mkforms_jsbase = EXT:path_to_query.js
 */
class tx_mkforms_js_DefaultFramework implements tx_mkforms_forms_IJSFramework
{
    private $configurations;
    private $confId;

    public function __construct($configurations, $confId)
    {
        $this->configurations = $configurations;
        $this->confId = $confId;
    }

    /**
     * Liefert einen Wert aus der TS-Config.
     *
     * @param string $confid
     *
     * @return mixed
     */
    private function getConf($confid)
    {
        return $this->configurations->get($this->confId.$confid);
    }

    public function getId()
    {
        return $this->getConf('jscore');
    }

    public function getBaseIncludes($absRefPrefix)
    {
        return $this->loadIncludes('jscore.', $absRefPrefix);
    }

    private function loadIncludes($confId, $absRefPrefix)
    {
        $server = $absRefPrefix;

        // TODO: Wir benötigen den Pfad für die URL und den absoluten Serverpfad.
        // Ob das wirklich so ist, muss aber noch geklärt werden: minify und zip
        $jsCore = $this->getConf($confId);
        $ret = [];
        if (!is_array($jsCore)) {
            return $ret;
        }
        foreach ($jsCore as $key => $jsPath) {
            $pagePath = $jsPath;
            if (tx_mkforms_util_Div::isExtensionPath($jsPath)) {
                $pagePath = tx_mkforms_util_Div::getRelExtensionPath($jsPath);
            }
            $pagePath = $server.$pagePath;
            $serverPath = \Sys25\RnBase\Utility\T3General::getFileAbsFileName($jsPath);
            $ret[] = tx_mkforms_forms_PageInclude::createInstance($pagePath, $serverPath, $key);
        }

        return $ret;
    }

    public function includeBase()
    {
    }

    /**
     * {@inheritdoc}
     *
     * @see tx_mkforms_forms_IJSFramework::getEffectIncludes()
     */
    public function getEffectIncludes($absRefPrefix)
    {
        return $this->loadIncludes('effects.', $absRefPrefix);
    }

    public function includeTooltips()
    {
    }

    public function includeDragDrop()
    {
    }
}
