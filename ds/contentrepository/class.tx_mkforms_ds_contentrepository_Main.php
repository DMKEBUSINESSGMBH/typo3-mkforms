<?php

/**
 * Plugin 'ds_php' for the 'ameos_formidable' extension.
 *
 * @author    Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_ds_contentrepository_Main extends formidable_maindatasource
{
    public $sKey = false;

    public $oRepo = false;

    public function _init(&$oForm, $aElement, $aObjectType, $sXPath, $sNamePrefix = false)
    {
        parent::_init($oForm, $aElement, $aObjectType, $sXPath, $sNamePrefix);

        if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('extbase')) {
            $this->oForm->mayday(
                'datasource:CONTENTREPOSITORY[name=\''.$this->getName()
                .'\'] The Content Repository API is <b>not loaded</b>, and should be (<b>EXT:extbase</b>).'
            );
        }

        $this->loadContentRepositoryFramework();
        $this->loadAggregates();
        $this->loadRepository();
    }

    public function loadContentRepositoryFramework()
    {
        $sExtBasePath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('extbase');
        require_once $sExtBasePath.'Classes/Utility/Strings.php';
        require_once $sExtBasePath.'Classes/Exception.php';
        require_once $sExtBasePath.'Classes/Persistence/Session.php';
        require_once $sExtBasePath.'Classes/Persistence/ObjectStorage.php';
        require_once $sExtBasePath.'Classes/Persistence/Mapper/DataMap.php';
        require_once $sExtBasePath.'Classes/Persistence/Mapper/ColumnMap.php';
        require_once $sExtBasePath.'Classes/Persistence/Mapper/ObjectRelationalMapper.php';
        require_once $sExtBasePath.'Classes/Persistence/RepositoryInterface.php';
        require_once $sExtBasePath.'Classes/Persistence/Repository.php';
        require_once $sExtBasePath.'Classes/DomainObject/DomainObjectInterface.php';
        require_once $sExtBasePath.'Classes/DomainObject/AbstractDomainObject.php';
    }

    public function loadRepository()
    {
        if (false === $this->oRepo) {
            if (false === $this->_navConf('/repository')) {
                $this->oForm->mayday(
                    'datasource:CONTENTREPOSITORY[name=\''.$this->getName().'\'] You have to provide <b>/repository</b>.'
                );
            }

            if (false === ($sClassFile = $this->_navConf('/repository/classfile'))) {
                $this->oForm->mayday(
                    "datasource:CONTENTREPOSITORY[name='".$this->getName()
                    ."'] You have to provide <b>/repository/classFile</b>."
                );
            } else {
                $sClassFile = $this->oForm->toServerPath($sClassFile);

                if (!file_exists($sClassFile)) {
                    $this->oForm->mayday(
                        "datasource:CONTENTREPOSITORY[name='".$this->getName()
                        ."'] The given <b>/repository/classFile</b> given (".$sClassFile.') does not exist.'
                    );
                }

                if (!is_readable($sClassFile)) {
                    $this->oForm->mayday(
                        "datasource:CONTENTREPOSITORY[name='".$this->getName()
                        ."'] The given <b>/repository/classFile</b> given (".$sClassFile.') exists, but is not readable.'
                    );
                }

                require_once $sClassFile;
            }

            if (false === ($sClassName = $this->_navConf('/repository/classname'))) {
                $this->oForm->mayday(
                    "datasource:CONTENTREPOSITORY[name='".$this->getName()
                    ."'] You have to provide <b>/repository/className</b>."
                );
            } else {
                if (!class_exists($sClassName)) {
                    $this->oForm->mayday(
                        "datasource:CONTENTREPOSITORY[name='".$this->getName()."'] The given <b>/repository/className</b> ("
                        .$sClassName.') does not exist.'
                    );
                }
            }

            $this->oRepo = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($sClassName);
        }
    }

    public function loadAggregates()
    {
        reset($this->aElement);
        foreach ($this->aElement as $sElementName => $notNeeded) {
            if ('a' === $sElementName[0] && \Sys25\RnBase\Utility\Strings::isFirstPartOfStr($sElementName, 'aggregate')) {
                if (false === ($sClassFile = $this->_navConf('/'.$sElementName.'/classfile'))) {
                    $this->oForm->mayday(
                        "datasource:CONTENTREPOSITORY[name='".$this->getName()
                        ."'] You have to provide <b>/aggregate/classFile</b>."
                    );
                } else {
                    $sClassFile = $this->oForm->toServerPath($sClassFile);

                    if (!file_exists($sClassFile)) {
                        $this->oForm->mayday(
                            "datasource:CONTENTREPOSITORY[name='".$this->getName()
                            ."'] The given <b>/aggregate/classFile</b> given (".$sClassFile.') does not exist.'
                        );
                    }

                    if (!is_readable($sClassFile)) {
                        $this->oForm->mayday(
                            "datasource:CONTENTREPOSITORY[name='".$this->getName()
                            ."'] The given <b>/aggregate/classFile</b> given (".$sClassFile.') exists, but is not readable.'
                        );
                    }

                    require_once $sClassFile;
                }

                if (false === ($sClassName = $this->_navConf('/'.$sElementName.'/classname'))) {
                    $this->oForm->mayday(
                        "datasource:CONTENTREPOSITORY[name='".$this->getName()
                        ."'] You have to provide <b>/aggregate/className</b>."
                    );
                } else {
                    if (!class_exists($sClassName)) {
                        $this->oForm->mayday(
                            "datasource:CONTENTREPOSITORY[name='".$this->getName()
                            ."'] The given <b>/aggregate/className</b> (".$sClassName.') does not exist.'
                        );
                    }
                }
            }
        }
    }

    public function writable()
    {
        return $this->defaultTrue('/writable');
    }

    public function initDataSet($sKey)
    {
        $oDataSet = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('formidable_maindataset');

        if ('new' === $sKey) {
            // new record to create
            $oDataSet->initFloating($this);
        } else {
            // existing record to grab

            if (false !== ($aDataSet = $this->getSyncData($sKey))) {
                $oDataSet->initAnchored(
                    $this,
                    $aDataSet,
                    $sKey
                );
            } else {
                $this->oForm->mayday(
                    "datasource:CONTENTREPOSITORY[name='".$this->getName()."'] No dataset matching key '".$sKey
                    ."' was found."
                );
            }
        }

        $sSignature = $oDataSet->getSignature();
        $this->aODataSets[$sSignature] = &$oDataSet;

        return $sSignature;
    }

    public function getSyncData($sKey)
    {
        $oObject = $this->getObject($sKey);

        return get_object_vars($oObject);
    }

    public function setSyncData($sSignature, $sKey, $aData)
    {
        $oObject = $this->getObject($sKey);
        reset($aData);
        foreach ($aData as $sKey => $notNeeded) {
            $oObject->$sKey = $aData[$sKey];
        }

        $oSession = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TX_EXTBASE_Persistence_Session');
        $oSession->registerAddedObject($oObject);
        $oSession->commit();
    }

    public function getObject($sKey)
    {
        return $this->oRepo->findOneByUid($sKey);
    }
}
