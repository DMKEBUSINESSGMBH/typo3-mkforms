<?php
/**
 * Plugin 'tx_fluid' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */
class tx_mkforms_renderer_fluid_Main extends formidable_mainrenderer
{
    private $sFluidPath = false;
    private $sExtbasePath = false;
    private $sFluidClass = 'Tx_Fluid_View_TemplateView';

    private $oFluid;

    private function assertFluid()
    {
        if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('extbase')) {
            $this->oForm->mayday('<b>renderer:FLUID</b> needs the extension <b>extbase</b> to be loaded.');
        }

        if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('fluid')) {
            $this->oForm->mayday('<b>renderer:FLUID</b> needs the extension <b>fluid</b> to be loaded.');
        }
    }

    private function initFluid()
    {
        $this->includeFluid();

        $this->oFluid = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($this->sFluidClass);
        $this->oFluid->initializeView();
    }

    public function _render($aRendered)
    {
        $this->assertFluid();
        $this->initFluid();

        $mRes = $this->executeFluid($aRendered);

        return $this->_wrapIntoForm($mRes);
    }

    private function getTemplatePath()
    {
        $sPath = $this->_navConf('/template/path/');
        if ($this->oForm->isRunneable($sPath)) {
            $sPath = $this->callRunneable(
                $sPath
            );
        }

        if (is_string($sPath)) {
            return $this->oForm->toServerPath($sPath);
        }

        return false;
    }

    private function executeFluid($aRendered)
    {
        if (false === ($sTemplatePath = $this->getTemplatePath())) {
            $this->oForm->mayday('<b>renderer:FLUID</b>: you have to provide <b>/template/path</b>');
        }

        if (!file_exists($sTemplatePath) || !is_readable($sTemplatePath)) {
            $this->oForm->mayday('<b>renderer:FLUID</b>: the given template path does not exist or is nor readable');
        }

        $oParsedTemplate = $this->oFluid->parseTemplate($sTemplatePath);
        $oRootNode = $oParsedTemplate->getRootNode();
        $oObjectFactory = new Tx_Fluid_Compatibility_ObjectFactory();

        $oVariableContainer = $oObjectFactory->create(
            'Tx_Fluid_Core_VariableContainer',
            [
                'renderlets' => $aRendered,
            ]
        );

        return $oRootNode->evaluate($oVariableContainer);
    }

    private function includeFluid()
    {
        // no autoinclude for the moment

        $this->sExtbasePath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('extbase');
        $this->sFluidPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('fluid');

        require_once $this->sExtbasePath.'Classes/MVC/View/ViewInterface.php';
        require_once $this->sExtbasePath.'Classes/MVC/View/AbstractView.php';
        require_once $this->sExtbasePath.'Classes/MVC/View/Helper/URIHelper.php';
        require_once $this->sExtbasePath.'Classes/Reflection/DocCommentParser.php';
        require_once $this->sExtbasePath.'Classes/Reflection/ClassReflection.php';
        require_once $this->sExtbasePath.'Classes/Reflection/ParameterReflection.php';
        require_once $this->sExtbasePath.'Classes/Reflection/MethodReflection.php';
        require_once $this->sExtbasePath.'Classes/Reflection/Service.php';

        require_once $this->sFluidPath.'Classes/Fluid.php';
        require_once $this->sFluidPath.'Classes/Exception.php';

        require_once $this->sFluidPath.'Classes/Core/VariableContainer.php';
        require_once $this->sFluidPath.'Classes/Core/ParsedTemplateInterface.php';
        require_once $this->sFluidPath.'Classes/Core/ParsingState.php';
        require_once $this->sFluidPath.'Classes/Core/TemplateParser.php';
        require_once $this->sFluidPath.'Classes/Core/ArgumentDefinition.php';
        require_once $this->sFluidPath.'Classes/Core/ViewHelperInterface.php';
        require_once $this->sFluidPath.'Classes/Core/AbstractViewHelper.php';
        require_once $this->sFluidPath.'Classes/Core/ViewHelperArguments.php';
        require_once $this->sFluidPath.'Classes/Core/Exception.php';
        require_once $this->sFluidPath.'Classes/Core/RuntimeException.php';
        require_once $this->sFluidPath.'Classes/Core/ParsingException.php';
        require_once $this->sFluidPath.'Classes/Core/TagBasedViewHelper.php';

        require_once $this->sFluidPath.'Classes/Core/SyntaxTree/AbstractNode.php';
        require_once $this->sFluidPath.'Classes/Core/SyntaxTree/RootNode.php';
        require_once $this->sFluidPath.'Classes/Core/SyntaxTree/ViewHelperNode.php';
        require_once $this->sFluidPath.'Classes/Core/SyntaxTree/ObjectAccessorNode.php';
        require_once $this->sFluidPath.'Classes/Core/SyntaxTree/TextNode.php';
        require_once $this->sFluidPath.'Classes/Core/SyntaxTree/ArrayNode.php';

        require_once $this->sFluidPath.'Classes/Compatibility/ObjectFactory.php';
        require_once $this->sFluidPath.'Classes/Compatibility/TemplateParserBuilder.php';
        require_once $this->sFluidPath.'Classes/Compatibility/Validation/ValidatorResolver.php';
        require_once $this->sFluidPath.'Classes/Compatibility/Validation/DummyValidator.php';
        require_once $this->sFluidPath.'Classes/Compatibility/Validation/Errors.php';

        require_once $this->sFluidPath.'Classes/View/TemplateView.php';
        require_once $this->sFluidPath.'Classes/ViewHelpers/ForViewHelper.php';
        require_once $this->sFluidPath.'Classes/ViewHelpers/TypolinkViewHelper.php';
    }
}
