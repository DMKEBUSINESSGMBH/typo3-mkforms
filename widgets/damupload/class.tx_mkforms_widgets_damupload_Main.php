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

	private $uploadsWithoutReferences = array();

	private $damPics = array();

	/**
	 * folgendes brauch man um eine Liste der DAM Uploads auszugeben:
	 *
	    <datasources>
			<datasource:PHPARRAY name="mediaUploadList">
				<bindsto>
					<userobj extension="tx_mkforms_util_DamUpload" method="getUploadsByWidget">
						<params>
							<param name="damWidget" value="locationDescription-mediaUpload" />
						</params>
					</userobj>
				</bindsto>
			</datasource:PHPARRAY>
		</datasources>

		<renderlet:DAMUPLOAD name="locationDescription-mediaUpload" label="LLL:label_media">
			<data multiple="true" showFileList="false" cleanfilename="true">
				<reftable>tx_a4base_locdescriptions</reftable>
				<reffield>media</reffield>
				<targetdir><userobj extension="tx_mklib_util_MiscTools" method="getPicturesUploadPath" /></targetdir>
				<beuser><userobj extension="tx_mklib_util_MiscTools" method="getProxyBeUserId" /></beuser>
			</data>

			<!-- damit können uploads gelöscht werden -->
			<deleteWidget>tab_step1__lister-mediaUploadList__delete</deleteWidget>

			<validators>
				<validator:FILE>
					<extension value="gif,jpg,jpeg,bmp,png,pdf" message="LLL:msg_picture_filetype" />
				</validator:FILE>
			</validators>
		</renderlet:DAMUPLOAD>

		<renderlet:LISTER
			name="lister-mediaUploadList" uidColumn="uid"
		>
			<datasource use="mediaUploadList" />
			<ifEmpty message="" />
			<template
				path="EXT:mkaltstadt/Resources/Private/Templates/Html/editAdPurchase.html"
				subpart="###MEDIAUPLOADLIST###"
				alternateRows="###ROW###"
			/>
			<pager window="10"><rows perpage="-1"/></pager>
			<columns>
				<column name="title" type="renderlet:TEXT"/>
				<column name="file_name" type="renderlet:TEXT"/>
				<column name="delete" type="renderlet:CHECKSINGLE" activelistable="true" >
					<data><defaultValue>0</defaultValue></data>
				</column>
			</columns>
		</renderlet:LISTER>

		<renderlet:SUBMIT name="deleteUpload" label="LLL:delete_upload" mode="draft"/>
		<renderlet:SUBMIT name="upload" label="LLL:upload" mode="draft"/>

		im DAM Widget wird keine refuid gesetzt. Das liegt daran dass diese beim erstellen
		noch nicht bekannt ist. daher setzen wir diese im formhandler.
		dazu muss folgendes in processForm und fillForm aufgerufen werden. Vorrausgesetzt
		es wird von tx_mkforms_util_FormBase geerbt:

		public function fillForm(array $formParameters, tx_mkforms_forms_Base $form) {
			// DAM Uploads vorbefüllen
			$form->getDataHandler()->newEntryId = HIER DIE UID DES JEWEILIGEN MODELS BZW. NICHTS;

			return $formData;
		}

		public function processForm(array $formParameters, tx_mkforms_forms_Base $form) {
			// damit die Formularverarbeitung bei DAM Uploads nicht anspringt
			if(!$form->isFullySubmitted()) {
				return;
			}

			// UID für DAM Uploads setzen
			$form->getDataHandler()->newEntryId = HIER DIE UID DES JEWEILIGEN MODELS, DAS GERADE ERSTELLT ODER BEARBEITET WURDE
			parent::processForm($formParameters, $form);
		}


	 *
	 * (non-PHPdoc)
	 * @see formidable_mainrenderlet::_render()
	 */
	function _render() {
		$this->includeLibraries();


		$sValue = $this->getValue();

		//wenn die datei hochgeladen wurde und der value wurde nicht richtig
		//gesetzt, dann holen wir das nach. das kann z.B. passieren wenn ein
		//anderes widget nicht validiert wurde weil dann der checkpoint in checkPoint()
		//nicht anspringt, der den value setzt. Das liegt am eigentlichen Bug beim setzen der
		//checkpoints. after-validation-ok wird NACH dem render der widgets gesetzt. Also
		//nachdem wir hier waren. Wir brauchen den value aber hier!
		if(!is_string($sValue) && $this->aUploaded){
			$this->setValue($this->aUploaded['newSize']);
			$sValue = $this->aUploaded['newSize'];
		}

		$sLabel = $this->getLabel();
		$sInput = '<input type="file" name="' . $this->_getElementHtmlName() . '" id="' . $this->_getElementHtmlId() . '" ' . $this->_getAddInputParams() . ' />';

		$aRes = array(
			'__compiled' =>  $this->_displayLabel($sLabel) . $sInput,
			'input' => $sInput,
			'value' => $sValue,
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

	/**
	 *
	 * @return array
	 */
	public function getDamPics() {
		return $this->damPics;
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
			if(!empty($this->aUploaded['path']))  $this->deleteFile($this->aUploaded['path'], $this->aUploaded['damid']);
			if(!empty($this->aUploaded['damid'])) $this->deleteReferences($this->aUploaded['damid']);
		} else {
			$this->handleNoUpload($aData);
		}

		// wurden bereits referenzen angelegt?
		$damPics = $this->getReferencedMedia();

		// die bisher hochgeladenen Dam IDs sammeln, damit wir diese auch in
		// einem lister ausgeben können
		$uploadedFileIds = array();
		$sessionData = $GLOBALS['TSFE']->fe_user->getKey('ses', 'mkforms');
		$sessionIdForCurrentWidget = $GLOBALS['TSFE']->id . $this->_getElementHtmlId();
		// nur aus session holen wenn form abgeschickt
		if(
			$this->getForm()->getDataHandler()->_isSubmitted() &&
			$uploadedFileIdsFromSession = $sessionData[$sessionIdForCurrentWidget . '_fileIds']
		) {
			$uploadedFileIds = t3lib_div::trimExplode(',', $uploadedFileIdsFromSession);
		} else { // hochgeladene Dateien in Session löschen wenn nicht submitted
			$sessionData[$sessionIdForCurrentWidget . '_fileIds'] = '';
			$GLOBALS['TSFE']->fe_user->setKey('ses', 'mkforms', $sessionData);
		}

		// aktuellen upload hinzufügen
		if($this->openUid) {
			$uploadedFileIds[] = $this->openUid;
		}

		// es wurden wahrscheinlich noch keine referenzen angelegt, sondern nur
		// dateien hochgeladen
		if(
			(empty($damPics) || empty($damPics['rows'])) &&
			!empty($uploadedFileIds)
		) {
			$damPics = tx_mklib_util_DAM::getRecords($uploadedFileIds);

			foreach ($damPics['rows'] as $uid => $damPic) {
				$this->uploadsWithoutReferences[$uid] = $uid;
			}
		}

		// jetzt kümmern wir uns um die Dateien, die gelöscht werden sollen
		$currentFileIds = array();// die DAM Ids, welche übrig sind nachdem gelöscht wurde
		$deleteWidgetName = $this->getForm()->_navConf('/deletewidget', $this->aElement);
		if($damPics['rows']) {
			foreach ($damPics['rows'] as $uid => $damPic) {
				if(
					$deleteWidgetName &&
					($deleteWidget = $this->getForm()->getWidget($deleteWidgetName))
				) {
					$deleteWidget->setIteratingId($uid);
					if($deleteWidget->getValue()) {
						unset($damPics['rows'][$uid]);
						unset($this->uploadsWithoutReferences[$uid]);
						$this->deleteFile($damPic['file_name'], $uid);
						$this->deleteReferences($uid);
						continue;
					}
				}

				// kommen zurück in die session um diese nachdem submit anzeigen zu können
				$currentFileIds[] = $uid;
			}

			$sessionData[$sessionIdForCurrentWidget . '_fileIds'] = join(',', $currentFileIds);
			$GLOBALS['TSFE']->fe_user->setKey('ses', 'mkforms', $sessionData);
			$GLOBALS['TSFE']->fe_user->storeSessionData();
		}

		// wird von tx_mkforms_util_DamUpload::getUploadsByWidget benötigt um die Liste
		// der DAM Uploads ausgeben zu können
		$this->damPics = $damPics;
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
				$sName = tx_mkforms_util_Div::cleanupFileName($sName);
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
		// ACHTUNG: wenn die Feld Collation der DB-Felder file_name und file_path
		// 			in der tx_dam Tabelle auf *_ci (utfa_general_ci) stehen,
		// 			wird bei der Prüfung Gruß-/Kleinschreibung ignoriert,
		// 			was bei unix-Systemen zu Fehlern führt!
		// 			Die einfache Lösung ist, die Collation der beiden Felder
		// 			auf *_bin (utf8_bin) zu setzen!
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
		if(!$this->openUid && !$this->uploadsWithoutReferences) {
			return;
		}
		if($this->openUid && !$this->uploadsWithoutReferences) {
			$damUids = array($this->openUid);
		} else {
			$damUids = $this->uploadsWithoutReferences;
		}

		foreach ($damUids as $damUid) {
			$newSize = $this->addReference($damUid);
		}
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

	function deleteFile($sFile, $damUid) {
		$mValues = t3lib_div::trimExplode(',', $this->getValue());
		if(is_array($mValues))
			unset($mValues[array_search($sFile, $mValues)]);

		@unlink($this->getFullServerPath($sFile));
		tx_rnbase_util_DB::doDelete('tx_dam', 'tx_dam.uid = '.$damUid);

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
		if(!is_array($PAGES_TYPES) || !array_key_exists(254, $PAGES_TYPES)) {
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
			'<script type="text/javascript" src="' . $oJsLoader->getScriptPath($sFile) . '"></script>',
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