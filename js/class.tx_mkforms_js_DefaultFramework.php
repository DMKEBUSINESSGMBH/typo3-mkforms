<?php
tx_rnbase::load('tx_mkforms_forms_IJSFramework');

/**
 * Diese Klasse bindet das notwendige Javascript für ein.
 * Die Konfiguration erfolgt vollständig per Typoscript.
 *
 * config.tx_mkforms.jsframework.jscore = jquery | prototype
 * config.tx_mkforms.jsframework.jscore.tx_mkforms_jsbase = EXT:path_to_query.js
 *
 *
 */
class tx_mkforms_js_DefaultFramework implements tx_mkforms_forms_IJSFramework {

	private $configurations, $confId;

	public function __construct($configurations, $confId) {
		$this->configurations = $configurations;
		$this->confId = $confId;
	}

	/**
	 * Liefert einen Wert aus der TS-Config
	 *
	 * @param string $confid
	 *
	 * @return mixed
	 */
	private function getConf($confid) {
		return $this->configurations->get($this->confId . $confid);
	}

	public function getId() {
		return $this->getConf('jscore');
	}

	public function getBaseIncludes() {
		return $this->loadIncludes('jscore.');
	}

	private function loadIncludes($confId) {
		$server = Tx_Rnbase_Utility_T3General::getIndpEnv('TYPO3_SITE_URL');

		// TODO: Wir benötigen den Pfad für die URL und den absoluten Serverpfad.
		// Ob das wirklich so ist, muss aber noch geklärt werden: minify und zip
		$jsCore = $this->getConf($confId);
		$ret = array();
		if (!is_array($jsCore)) {
			return $ret;
		}
		foreach ($jsCore As $key => $jsPath) {
			$pagePath = $jsPath;
			if (tx_mkforms_util_Div::isExtensionPath($jsPath)) {
				$pagePath = tx_mkforms_util_Div::getRelExtensionPath($jsPath);
			}
			$pagePath = $server . $pagePath;
			$serverPath = Tx_Rnbase_Utility_T3General::getFileAbsFileName($jsPath);
			$ret[] = tx_mkforms_forms_PageInclude::createInstance($pagePath, $serverPath, $key);
		}

		return $ret;
	}

	public function includeBase() {
	}

	public function getEffectIncludes() {
		return $this->loadIncludes('effects.');
	}

	public function includeTooltips() {
	}

	public function includeDragDrop() {
	}
}
