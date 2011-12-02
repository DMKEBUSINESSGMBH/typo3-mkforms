<?php
/** 
 * Plugin 'rdt_damupload' for the 'ameos_formidable' extension.
 * Based on original rdt_upload from Jerome Schneider <typo3dev@ameos.com>
 * @author	René Nitzsche <rene@system25.de>
 */

require_once(PATH_t3lib.'class.t3lib_userauth.php');
require_once(PATH_t3lib.'class.t3lib_userauthgroup.php');
require_once(PATH_t3lib.'class.t3lib_beuserauth.php');

require_once(PATH_t3lib.'class.t3lib_basicfilefunc.php');
if(t3lib_extMgm::isLoaded('dam')) {
	require_once(t3lib_extMgm::extPath('dam') . 'lib/class.tx_dam.php');
	require_once(t3lib_extMgm::extPath('dam') . 'lib/class.tx_dam_db.php');
}


class tx_mkforms_widgets_damupload_Main extends formidable_mainrenderlet {
	var $aLibs = array(
		'widget_damupload_class' => 'res/js/damupload.js',
	);

	var $bArrayValue = true;
	var $sMajixClass = 'DamUpload';
//	var $bCustomIncludeScript = TRUE;
	var $aPossibleCustomEvents = array (
		"onajaxstart",
		"onajaxcomplete",
	);
	
	var $aUploaded = FALSE;	// array if file has just been uploaded

	function _render() {

		$this->includeLibraries();


		$sValue = $this->getValue();
		//@FIXME warum steckt hier manchmal ein array?
		if(is_array($sValue)) $sValue = 0;
//		$sLabel = $this->oForm->getConfig()->getLLLabel($this->aElement['label']);
		$sLabel = $this->getLabel();
		$sInput = '<input type="file" name="' . $this->_getElementHtmlName() . '" id="' . $this->_getElementHtmlId() . '" ' . $this->_getAddInputParams() . ' />';
		$sInput .= '<input type="hidden" name="' . $this->_getElementHtmlName() . '[backup]" value="' . $sValue . '" />';
		$sLis = '<li>' . implode('</li><li>', t3lib_div::trimExplode(',', htmlspecialchars($sValue))) . '</li>';
		// Das Value ist die Liste der Dateinamen
		// Wir brauchen den Tabellennamen und den Spaltennamen
		$sValuePreview = '';
		if(intval($sValue) > 0 && $this->getForm()->_defaultTrue('/data/showfilelist', $this->aElement)) {
			// Okay, there is at least one referenced file
			$damPics = $this->getReferencedMedia();
			$files = array();
			while(list($uid, $fileData) = each($damPics['rows'])) {
				$files[] = $fileData['title'] . ' (' . $fileData['file_name'] . ')';
			}
			$sValuePreview = implode(', ', $files) . '<br />';
		}

		$aRes = array(
			'__compiled' =>  $this->_displayLabel($sLabel) . $sValuePreview . $sInput,
			'input' => $sInput,
			'filelist.' => array(
				'csv' => $sValue,
				'ol' => '<ol>' . $sLis . '</ol>',
				'ul' => '<ul>' . $sLis . '</ul>',
			),
			'value' => $sValue,
			'value.' => array(
				'preview' => $sValuePreview,
			),
		);

		if(!$this->isMultiple()) {
			if(trim($sValue) != '') {
				$aRes['file.']['webpath'] = tx_mkforms_util_Div::toWebPath($this->getServerPath());
			} else {
				$aRes['file.']['webpath'] = '';
			}
		}

		return $aRes;
	}

	function getServerPath($sFileName = FALSE) {
		if(($sTargetFile = $this->getTargetFile()) !== FALSE) {
			return $sTargetFile;
		} elseif($sFileName !== FALSE) {
			return $this->getTargetDir() . $sFileName;
		}

		return $this->getTargetDir() . $this->getValue();
	}

	function getFullServerPath($sFileName = FALSE) {
		// dummy method for compat with renderlet:FILE and validator:FILE
		return $this->getServerPath($sFileName);
	}

	/**
	 * @see api/formidable_mainrenderlet#checkPoint($aPoints)
	 */
	function checkPoint($aPoints) {

		// Die Verarbeitung der Datei unmittelbar nach der Initialisierung des DataHandlers starten
		if(in_array('after-init-datahandler', $aPoints))
			$this->manageFile();

		// Bei Validierunfs-Fehlern muss die Referenz und die Datei wieder gelöscht werden!
		if(in_array('after-validation-nok', $aPoints))
			$this->manageFile(false);
		
		// Die Value setzen, wenn Validierung OK war.
		if(in_array('after-validation-ok', $aPoints))
			$this->setValue($this->aUploaded['newSize']);

	}

	/**
	 * Hier startet bei einem normalen Submit die Verarbeitung der hochgeladenen Datei
	 */
	public function manageFile($valid=true) {

		$aData = $this->getValue();
		if($valid && (is_array($aData) && $aData['error'] == 0)) {
			// a file has just been uploaded
			$this->handleUpload($aData);
		} elseif(!$valid) {
			// Datei wurde hochgeladen und referenziert,
			// validation ist allerdings fehlgeschlagen.
			// Datei und Referenz löschen!
			if(!empty($this->aUploaded['path']))  $this->deleteFile($this->aUploaded['path']);
			if(!empty($this->aUploaded['damid'])) $this->deleteReferences($this->aUploaded['damid']);
		} else {
			$this->handleNoUpload($aData);
		}
	}

	/**
	 * Returns data about new file on server
	 *
	 * @param array $aData 
	 * @return array keys: sTargetDir, sName, sTarget
	 */
	function getTargetFileData($aData) {
		$ret = array();
		if(($sTargetFile = $this->getTargetFile()) !== FALSE) {
			$ret['sTargetDir'] = t3lib_div::dirname($sTargetFile);
			$ret['sName'] = basename($sTargetFile);
			$ret['sTarget'] = $sTargetFile;
		} else {
			$sTargetDir = $this->getTargetDir();

//			$sName = basename($aData['name']);
			$sName = $aData['name'];
			if($this->getForm()->_defaultTrue('/data/cleanfilename', $this->aElement)) {
				// Bug 548: Sonderzeichen beim Upload werden nicht ersetzt.
				// Die TYPO3 Filefunktions wirken nicht wie gewünscht...
				setlocale (LC_ALL, 'de_DE@euro');
				$sName = preg_replace('/[äÄüÜöÖß]/','_',trim($sName));
//				$sName = preg_replace('/[^.[:alnum:]_-]/','_',trim($sName));
				$sName = preg_replace('/\.*$/','',$sName);
//				$oFileTool = t3lib_div::makeInstance('t3lib_basicFileFunctions');
//				$sName = strtolower($oFileTool->cleanFileName($sName));
			}
			
			$sTarget = $sTargetDir . $sName;
			if(!$this->oForm->_defaultFalse('/data/overwrite', $this->aElement)) {
				// rename the file if same name already exists
				$sExt = ((strpos($sName,'.') === FALSE) ? '' : '.' . substr(strrchr($sName, '.'), 1));
				for($i=1; file_exists($sTarget); $i++) {
					$sTarget = $sTargetDir . substr($sName, 0, strlen($sName)-strlen($sExt)).'['.$i.']'.$sExt;
				}
				$sName = basename($sTarget);
			}
			$ret['sTargetDir'] = $sTargetDir;
			$ret['sName'] = $sName;
			$ret['sTarget'] = $sTarget;
		}
		return $ret;
	}
	/**
	 * ALGORITHM of file management
	 * 
			0: PAGE DISPLAY:
				1: file has been uploaded
					1.1: moving file to targetdir and setValue(newfilename)
						1.1.1: multiple == TRUE
							1.1.1.1: data *are not* stored in database (creation mode)
								1.1.1.1.1: setValue to backupdata . newfilename
							1.1.1.2: data *are* stored in database
								1.1.1.2.1: setValue to storeddata . newfilename
	 */
	private function handleUpload($aData) {
		$targetData = $this->getTargetFileData($aData);
		$sTarget = $targetData['sTarget'];
		$sName = $targetData['sName'];
		$sTargetDir = $targetData['sTargetDir'];
		$max = $this->getMaxObjects();
		$count = $this->getReferencedMedia(); 

		if ($max && count($count['files']) >= $max) {
			$this->setValue(count($count['files']));
			return;
		}
		if(!move_uploaded_file($aData['tmp_name'], $sTarget)) {
			tx_mkforms_util_Div::debug4ajax('FEHLER: '.$sTarget);
			return; // Error
		}

		// success
		$this->aUploaded = array(
			'dir' => $sTargetDir,
			'name' => $sName,
			'path' => $sTarget,
			'infos' => $aData,
		);

		// In Set Value kommt die Anzahl der Zuordnungen rein
		// Bei nur einer erlaubten Zuordnung muss die ggf. vorhandene Datei dereferenziert werden
		// Wir brauchen ein Max-Referenzes
		// Zuerst prüfen, ob die Datei schon in der DB existiert. Dies kann bei overwrite der Fall sein.
		$damUid = tx_dam::file_isIndexed($sTarget);
		if(!$damUid) {
			// process file indexing
			$this->initBE4DAM($this->getBeUserId());
			$damData = tx_dam::index_process($sTarget);
			$damUid = $damData[0]['uid'];
		}
		
		// save damuid
		$this->aUploaded['damid'] = $damUid;
		
		// Wir haben nun die UID des Bildes und müssen prüfen, ob es bereits zugeordnet ist
		$refPics = $this->getReferencedMedia();
		$refFiles = $refPics['files'];
		
		if(is_array($refFiles) && array_key_exists($damUid, $refFiles)) {
			// The file is already referenced. Nothing to do
			$this->setValue($aData['backup']);
			return;
		}
		if(!$this->isMultiple()) {
			// Only one file is allowed. So remove all old references
			$this->deleteReferences();
		}
		// Bei der Neuanlage des Datensatzes gibt es noch keine UID für die Zuordnung. In dem Fall
		// müssen wir das später nachholen.
		$newSize = 1;
		if(!$this->getEntryId()) {
			// Wir sind bei der Neuanlage und haben noch keine UID. Daher merken wir uns die ID des Bildes
			$this->openUid = $damUid;
		}
		else {
			// Set the new reference
			$newSize = $this->addReference($damUid);
		}
		
		// save size
		$this->aUploaded['newSize'] = $newSize;
		
		// darf hier noch nicht gesetzt werden,
		// da sonst der file validator nicht funktioniert.
		// wird in checkPoint gesetzt!
//		$this->setValue($newSize);
		return $newSize;
	}
	/**
	 * Liefert die UID des Datensatzes, mit dem die Mediadatei verknüpft werden soll.
	 * TODO: Ein Runnable setzen, damit der Wert bei Ajax-Calls gesetzt werden kann.
	 * @return uid
	 */
	function getEntryId() {
		
		$entryId = $this->getForm()->getConfig()->get('/data/refuid/', $this->aElement);
		if($entryId) {
			$entryId = $this->getForm()->getRunnable()->callRunnable($entryId);
			return $entryId;
		}

		$entryId = intval($this->getDataHandler()->entryId);
		// Im CreationMode steht die EntryID in einer anderen Variablen
		$entryId = $entryId ? $entryId : intval($this->getDataHandler()->newEntryId);
		return $entryId;
	}
	function handleCreation() {
		if(!$this->openUid) return;
		$newSize = $this->addReference($this->openUid);
		$this->openUid = 0;
	}
	/**
	 *
			2: file has not been uploaded
				2.1: data *are not* stored in database, as it's a creation mode not fully completed yet
					2.1.1: formdata is a string
						2.1.1.1: formdata != backupdata ( value has been set by some server event with setValue )
							2.1.1.1.1: no need to setValue as it already contains what we need
						2.2.1.2: formdata == backupdata
							2.2.1.2.1: setValue to backupdata
					2.1.2: formdata is an array, and error!=0 (form submitted but no file given)
						2.1.2.1: setValue to backupdata
				2.2: data *are* stored in database
						2.2.1: formdata is a string
							2.2.1.1: formdata != storeddata ( value has been set by some server event with setValue )
								2.2.1.1.1: no need to setValue as it already contains what we need
							2.2.1.2: formdata == storeddata
								2.2.1.2.1: setValue to storeddata
						2.2.2: formdata is an array, and error!=0 ( form submitted but no file given)
							2.2.2.1: setValue to storeddata
	 * 
	 */
	function handleNoUpload($aData) {
		// $aData ist normalerweise ein Array mit den Daten des Uploads. es kann aber auch ein String sein,
		// dann gab es aber wohl eine Sonderbehandlung
		// Für DAM ist ist aber wohl nicht relevant

		if(is_string($aData) && ($this->bForcedValue === TRUE || trim($aData) !== $sStoredData)) {
			// Nothing to do here
			return;
		}
		
		// Hier holen wir den kompletten Record.
		$aStoredData = $this->getDataHandler()->_getStoredData();
		$cValue = $aStoredData[$this->_getName()];
		if(($this->getDataHandler()->_edition() === FALSE) || (!array_key_exists($this->_getName(), $aStoredData))) {
			// Keine Bearbeitung oder das Feld ist nicht im aktuellen Record
			$cValue = (is_array($aData) && array_key_exists('backup', $aData)) ? $aData['backup'] : 
								(is_string($aData) ? $aData : '');
		}
		$this->setValue($cValue);
	}

	/**
	 * Returns the target dir
	 *
	 * @return string
	 */
	function getTargetDir() {
		$oFileTool = t3lib_div::makeInstance('t3lib_basicFileFunctions');
		if($this->oForm->isRunneable(($sTargetDir = $this->_navConf('/data/targetdir/')))) {
			$sTargetDir = $this->getForm()->getRunnable()->callRunnableWidget($this, $sTargetDir);
		}
		return t3lib_div::fixWindowsFilePath(
			$oFileTool->slashPath(
				$oFileTool->rmDoubleSlash($sTargetDir)
			)
		);
	}
	
	function getTargetFile() {
		if(($mTargetFile = $this->_navConf('/data/targetfile')) !== FALSE) {
			if($this->oForm->isRunneable($mTargetFile)) {
				$mTargetFile = $this->getForm()->getRunnable()->callRunnableWidget($this, $mTargetFile);
			}

			if(is_string($mTargetFile) && trim($mTargetFile) !== '') {
				return $this->oForm->toServerPath(trim($mTargetFile));
			}
		}
		return FALSE;
	}
	/**
	 * Returns the reference table for DAM
	 *
	 * @return string
	 */
	function getRefTable () {
		if($this->oForm->isRunneable(($uid = $this->_navConf('/data/reftable/')))) 
			$tableName = $this->getForm()->getRunnable()->callRunnableWidget($this, $uid);
		else
			$tableName = $this->_navConf("/data/reftable/", $this->aElement);
			
		return (strlen($tableName)) ? $tableName : $this->getDataHandler()->tableName();
	}
	
	/**
	 * Returns the a parameter maxobjects from the xml
	 *
	 * @return string
	 */
	function getMaxObjects () {
		$maxobjects = $this->_navConf("/data/maxobjects/", $this->aElement);
		return (strlen($maxobjects)) ? $maxobjects : FALSE;
	}
	/**
	 * Returns the reference field for DAM
	 *
	 * @return string
	 */
	function getRefField () {
		$fieldName = $this->_navConf("/data/reffield/", $this->aElement);
		return strlen($fieldName) ? $fieldName : $this->getAbsName();
	}
	/**
	 * Returns the defined beuser id for dam processing
	 *
	 * @return int
	 */
	function getBeUserId () {
		if($this->oForm->isRunneable(($uid = $this->_navConf('/data/beuser/')))) {
			$uid = $this->getForm()->getRunnable()->callRunnableWidget($this, $uid);
		}
		$uid = intval($uid);
		return $uid ? $uid : 1;
	}
	/**
	 * allow field to contain multiple files, comma-separated value 
	 *
	 * @return boolean
	 */
	function isMultiple() {
		return $this->oForm->_defaultFalse('/data/multiple', $this->aElement);
	}
	/**
	 * Returns the form instance
	 *
	 * @return tx_ameosformidable
	 */
	function getForm() {
		return $this->oForm;
	}

	/**
	 * Returns the current data handler
	 *
	 * @return formidable_maindatahandler
	 */
	function getDataHandler() {
		return $this->getForm()->oDataHandler;
	}
	
	function deleteFile($sFile) {
		$mValues = t3lib_div::trimExplode(',', $this->getValue());
		if(is_array($mValues))
			unset($mValues[array_search($sFile, $mValues)]);
		
		@unlink($this->getFullServerPath($sFile));
		
		if(is_array($mValues))
			$this->setValue(implode(',', $mValues));
	}

	/**
	 * Add a reference to a DAM media file
	 * Problem: Im CreationMode haben wir für das Ziel-Objekt noch kein valide UID.
	 * Diese ist erst vorhanden, wenn das Ziel-Objekt wirklich gespeichert wurde.
	 *
	 * @param int $damUid
	 */
	function addReference($damUid) {
		$tableName = trim($this->getRefTable());
		$fieldName = $this->getRefField();
		$data = array();
		$data['uid_foreign'] = $this->getEntryId();
		$data['uid_local'] = $damUid;
		$data['tablenames'] = $tableName;
		$data['ident'] = $fieldName;

		$sSql = $GLOBALS['TYPO3_DB']->INSERTquery('tx_dam_mm_ref',$data);

		$this->getForm()->_watchOutDB(
			$GLOBALS['TYPO3_DB']->sql_query($sSql),
			$sSql
		);
		// Now count all items
		$newSize = 0;
		$where = 'tablenames=\'' . $tableName . '\' AND ident=\'' . $fieldName .'\' AND uid_foreign=' . $data['uid_foreign'];
		$sSql = $GLOBALS['TYPO3_DB']->SELECTquery('count(*) As \'cnt\'','tx_dam_mm_ref',$where);
		$rSql = $this->getForm()->_watchOutDB($GLOBALS['TYPO3_DB']->sql_query($sSql), $sSql);
		if(($aRes = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($rSql)) !== FALSE) {
			$newSize = $aRes['cnt'];
			//Update Count
			$where = '1=1 AND `'.$tableName . '`.`uid`='.$GLOBALS['TYPO3_DB']->fullQuoteStr($data['uid_foreign'], $tableName);
			$values = array($fieldName => $newSize);
			tx_rnbase::load('tx_rnbase_util_DB');
			$res = tx_rnbase_util_DB::doUpdate($tableName, $where, $values);
		}
		
		return $newSize;
	}
	/**
	 * Removes dam references. If no parameter is given, all references will be removed.
	 *
	 * @param string $uids commaseperated uids
	 */
	function deleteReferences($uids = '') {
		$tableName = trim($this->getRefTable());
		$fieldName = $this->getRefField();

		$where = 'tablenames=\'' . $tableName . '\' AND ident=\'' . $fieldName .'\' AND uid_foreign=' . $this->getEntryId();
		if(strlen(trim($uids))) {
			$uids = implode(',',t3lib_div::intExplode(',',$uids));
			$where .= ' AND uid_local IN (' . $uids .') ';
		}
		$sSql = $GLOBALS['TYPO3_DB']->DELETEquery('tx_dam_mm_ref',$where);

		$this->getForm()->_watchOutDB(
			$GLOBALS['TYPO3_DB']->sql_query($sSql),
			$sSql
		);
	}
	/**
	 * Returns all referenced media of current field
	 *
	 * @return array keys: files and rows
	 */
	function getReferencedMedia() {
		if(!$this->getEntryId()) {
			// Ohne ID gibt es auch keine Bilder
			return array('files'=>'');
		}
		$tableName = trim($this->getRefTable());
		$fieldName = $this->getRefField();
		$ret = tx_dam_db::getReferencedFiles($tableName, $this->getEntryId(), $fieldName);
		return $ret;
	}
	/**
	 * DAM functionality requires a working BE. This method initializes all necessary stuff.
	 *
	 */
	function initBE4DAM($beUserId) {
		global $PAGES_TYPES, $BE_USER, $TCA;
		if(!is_array($PAGES_TYPES) || !array_key_exists(254)) {
			// SysFolder als definieren
			$PAGES_TYPES[254] = array(
				'type' => 'sys',
				'icon' => 'sysf.gif',
				'allowedTables' => '*',
			);
		}
		// Check BE User
		if(!is_object($BE_USER) || !is_array($BE_USER->user)) {
			if(!$beUserId) $this->getForm()->mayday('NO BE User id given!');
			require_once(PATH_t3lib.'class.t3lib_tsfebeuserauth.php');
			unset($BE_USER);
			$BE_USER = t3lib_div::makeInstance('t3lib_tsfeBeUserAuth');
			$BE_USER->OS = TYPO3_OS;
			$BE_USER->setBeUserByUid($beUserId);
			$BE_USER->fetchGroupData();
			$BE_USER->backendSetUC();
			// Ohne Admin-Rechte gibt es leider Probleme bei der Verarbeitung mit der TCE.
			$BE_USER->user['admin'] = 1;
			$GLOBALS['BE_USER'] = $BE_USER;
		}

		if(!$GLOBALS['LANG']) {
			// Bei Ajax-Calls fehlt das Objekt
			require_once(t3lib_extMgm::extPath('lang').'lang.php');
			$GLOBALS['LANG'] = t3lib_div::makeInstance('language');
			$GLOBALS['LANG']->init($BE_USER->uc['lang']);
		}
		
	}

	private function includeLibraries() {
		if($this->getForm()->issetAdditionalHeaderData('mkforms_damupload_includeonce')) 
			return;

		if(!$this->getForm()->getConfig()->defaultFalse('/ajaxupload', $this->aElement)) return;


		$oJsLoader = $this->getForm()->getJSLoader();
		// JS-Lib ermitteln
		$dir = $oJsLoader->getJSFrameworkId();
		$sFile = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . $this->sExtRelPath . 'res/js/'.$dir.'/ajaxfileupload.js';
		
		$oJsLoader->additionalHeaderData(
			'				
				<script type="text/javascript" src="' . $oJsLoader->getScriptPath($sFile) . '"></script>
			',
			'mkforms_damupload_includeonce'
		);
	}


	/**
	 * Liefert den Namen der JS-Klasse des Widgets
	 * @return string
	 */
	protected function getMajixClass() {
		return ($this->getForm()->getConfig()->defaultFalse('/ajaxupload', $this->aElement)) ? 'DamUpload' : 'RdtBaseClass';
	}

	/**
	 * Liefert die JS-Dateien, die für ein Widget eingebunden werden sollen.
	 * @return array
	 */
	protected function getJSLibs() {
		if($this->getForm()->getConfig()->defaultFalse('/ajaxupload', $this->aElement)) {
			return $this->aLibs;
		}
		return array();
	}
	/**
	 * Im Ajax-Upload muss das Widget in einen postInit-Task initialisiert werden.
	 * @see api/formidable_mainrenderlet#includeScripts($aConfig)
	 */
	function includeScripts($aConf = array()) {
		if($this->getForm()->getConfig()->defaultFalse('/ajaxupload', $this->aElement)) {
			// Die Config um weitere Werte erweitern
			$url = $this->createUploadUrl();
			$aConf = array(
				'uploadUrl' => $url,
			);
		}
		parent::includeScripts($aConf);
		if(!$this->getForm()->getConfig()->defaultFalse('/ajaxupload', $this->aElement)) return;

		$button = $this->getForm()->getConfig()->get('/ajaxbutton', $this->aElement);
		if($button)
			$button = $this->getForm()->getWidget($button);
		$button = $button ? $button->_getElementHtmlId() : '';


		$sAbsName = $this->_getElementHtmlIdWithoutFormId();
		
		$sInitScript =<<<INITSCRIPT
		Formidable.f("{$this->getForm()->getFormId()}").o("{$sAbsName}").initAjaxUpload('{$button}');
INITSCRIPT;
		
		$this->getForm()->attachPostInitTask($sInitScript,'postinit Ajax upload initialization', $this->_getElementHtmlId());
	}


	private function createUploadUrl() {
		$sThrower = $this->_getElementHtmlId();
		$sObject = 'widget_damupload';
		$sServiceKey = 'upload'; // Die Daten sind in der ext_localconf vordefiniert.
		$sFormId = $this->getForm()->getFormId();
		$sSafeLock = $this->_getSessionDataHashKey();
		$sUploadUrl = tx_mkforms_util_Div::removeEndingSlash(
			t3lib_div::getIndpEnv('TYPO3_SITE_URL')) . 
			'/index.php?eID='.tx_mkforms_util_Div::getAjaxEId().'&object=' . $sObject . '&servicekey=' . $sServiceKey . '&formid=' . $sFormId . '&safelock=' . $sSafeLock . '&thrower=' . $sThrower;

		$GLOBALS['_SESSION']['ameos_formidable']['ajax_services'][$sObject][$sServiceKey][$sSafeLock] = array(
			'requester' => array(
				'name' => $this->_getName(),
				'xpath' => $this->sXPath,
			),
		);
		return $sUploadUrl;
	}

	function handleAjaxRequest($oRequest) {
		require_once(PATH_t3lib.'class.t3lib_basicfilefunc.php');
	
		$oFile = t3lib_div::makeInstance('t3lib_basicFileFunctions');
		$aData = $this->getForm()->getRawFile(FALSE, true);

		//Wir müssen uns das Element anhand der XML-Struktur aus $aData besorgen 
		$path = array(); 
		$widget = $this;
		do {
			$path[] = $widget->getName();
			$widget = $widget->getParent();
		} while (is_object($widget));
		$myData = $aData;
		foreach(array_reverse($path) as $p) $myData = $myData[$p];
	
		//Validieren
		if($validate = $this->_navConf('/validate')) {
			$errors = $this->getForm()->getValidationTool()->validateWidgets4Ajax(
								array ($this->getName() => $myData)
							);
			if(count($errors)) {
				$this->getForm()->attachErrorsByJS($errors, $validate);
				// Replace value which contains the submitted data as ARRAY, while value must be a string!
				$this->setValue('');
				return array();
			}	else {
				// wenn keine validationsfehler aufgetreten sind,
				// eventuell vorherige validierungs fehler entfernen
				$this->getForm()->attachErrorsByJS(null, $validate, true);
			}
		}

		if(is_array($myData) && $myData['error'] == 0) {
			// a file has just been uploaded
			$newSize = $this->handleUpload($myData);
		} else {
			// Wenn nicht hochgeladen wurde haben wir hier nichts zu tun
//			$this->handleNoUpload($aData);
		}

		//Update Count
		if(isset($newSize)) {
			$refTableame = trim($this->getRefTable());
			$refField = $this->getRefField();
			$refUid = $this->getEntryId();
			$where = '1=1 AND `'.$refTableame . '`.`uid`='.$GLOBALS['TYPO3_DB']->fullQuoteStr($refUid, $refTableame);
			$values = array($refField => $newSize);
			tx_rnbase::load('tx_rnbase_util_DB');
			$res = tx_rnbase_util_DB::doUpdate($refTableame, $where, $values);
		}

		return array(array(
						'data' => $newSize,
						'databag' => '{}',
						'method' => 'doNothing',
						'object' => $this->getAbsName(),
					));
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/widgets/damupload/class.tx_mkforms_widgets_damupload_Main.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkforms/widgets/damupload/class.tx_mkforms_widgets_damupload_Main.php']);
}
?>