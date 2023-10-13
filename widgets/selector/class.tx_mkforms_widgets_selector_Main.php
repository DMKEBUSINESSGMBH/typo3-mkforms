<?php
/**
 * Plugin 'tx_rdtselector' for the 'ameos_formidable' extension.
 *
 * @author  Jerome Schneider <typo3dev@ameos.com>
 */

/**
 * FIXME: Das Widget liefert noch keine Items raus.
 */
class tx_mkforms_widgets_selector_Main extends formidable_mainrenderlet
{
    public $sMajixClass = 'Selector';
    public $aLibs = [
        'rdt_selector_class' => 'res/js/selector.js',
    ];

    public $bCustomIncludeScript = true;

    public $oAvailable = false;
    public $oSelected = false;
    public $oButtonRemove = false;
    public $oButtonMoveTop = false;
    public $oButtonMoveUp = false;
    public $oButtonMoveDown = false;
    public $oButtonMoveBottom = false;
    public $oCustomRenderlet = false;

    public function _render()
    {
        $this->initAvailable();
        $this->initSelected();
        $this->initButtonRemove();
        $this->initButtonMoveTop();
        $this->initButtonMoveUp();
        $this->initButtonMoveDown();
        $this->initButtonMoveBottom();
        $this->initCustomRenderlet();

        $aItems = $this->oForm->_rdtItemsToArray(
            $this->oAvailable->_getItems()
        );

        $aSelected = \Sys25\RnBase\Utility\Strings::trimExplode(',', $this->getValue());
        $aSelectedItems = [];

        foreach ($aSelected as $sValue) {
            if (array_key_exists($sValue, $aItems)) {
                $aSelectedItems[$sValue] = $aItems[$sValue];
                unset($aItems[$sValue]);
            }
        }

        $this->oAvailable->forceItems(
            $this->oForm->_arrayToRdtItems(
                $aItems
            )
        );

        $this->oSelected->forceItems(
            $this->oForm->_arrayToRdtItems(
                $aSelectedItems
            )
        );

        $aAvailableHtml = $this->oForm->_renderElement($this->oAvailable);
        $aSelectedHtml = $this->oForm->_renderElement($this->oSelected);
        $aButtonRemove = $this->oForm->_renderElement($this->oButtonRemove);
        $aButtonMoveTop = $this->oForm->_renderElement($this->oButtonMoveTop);
        $aButtonMoveUp = $this->oForm->_renderElement($this->oButtonMoveUp);
        $aButtonMoveDown = $this->oForm->_renderElement($this->oButtonMoveDown);
        $aButtonMoveBottom = $this->oForm->_renderElement($this->oButtonMoveBottom);

        if (false !== $this->oCustomRenderlet) {
            $aCustom = $this->oCustomRenderlet->render();
            $sCustomId = $this->oCustomRenderlet->_getElementHtmlId();
        } else {
            $aCustom = [
                '__compiled' => '',
            ];
            $sCustomId = false;
        }

        // allowed because of $bCustomIncludeScript = TRUE
        $this->includeScripts(
            [
                'availableId' => $this->oAvailable->_getElementHtmlId(),
                'selectedId' => $this->oSelected->_getElementHtmlId(),
                'buttonRemoveId' => $this->oButtonRemove->_getElementHtmlId(),
                'buttonMoveTopId' => $this->oButtonMoveTop->_getElementHtmlId(),
                'buttonMoveUpId' => $this->oButtonMoveUp->_getElementHtmlId(),
                'buttonMoveDownId' => $this->oButtonMoveDown->_getElementHtmlId(),
                'buttonMoveBottomId' => $this->oButtonMoveBottom->_getElementHtmlId(),
                'customRenderletId' => $sCustomId,
            ]
        );

        $sHidden = '<input type="hidden" name="'.$this->_getElementHtmlName().'" id="'.$this->_getElementHtmlId().'" value="'.htmlspecialchars($this->getValue()).'" />';

        $sLabelTag = $this->_displayLabel($this->getLabel());

        $sCompiled = <<<HTML

			{$sLabelTag}
			<table style='width: 100%'>
				<tr>
					<td valign="top" style='width: 47%;'>{$aSelectedHtml['__compiled']}</td>
					<td valign="top" align="center">
						{$aButtonMoveTop['__compiled']}<br />
						{$aButtonMoveUp['__compiled']}<br />
						{$aButtonMoveDown['__compiled']}<br />
						{$aButtonMoveBottom['__compiled']}<br />
						{$aButtonRemove['__compiled']}<br />
						{$aCustom['__compiled']}
					</td>
					<td valign="top" style='width: 47%;'>{$aAvailableHtml['__compiled']}</td>
				</tr>
			</table>
			{$sHidden}

HTML;

        $aAvailableHtml['__compiled'] .= $sHidden;
        $aAvailableHtml['input'] .= $sHidden;

        return [
            '__compiled' => $sCompiled,
            'available' => $aAvailableHtml,
            'selected' => $aSelectedHtml,
            'buttonUp' => $aButtonMoveUp,
            'buttonDown' => $aButtonMoveDown,
            'buttonTop' => $aButtonMoveTop,
            'buttonBottom' => $aButtonMoveBottom,
            'buttonRemove' => $aButtonRemove,
            'customRenderlet' => $aCustom,
        ];
    }

    public function initAvailable()
    {
        if (false === $this->oAvailable) {
            $sSelectorName = $this->getAbsName();
            $sEvent = <<<PHP

				return array(
					\$this->aORenderlets["{$sSelectorName}"]->oAvailable->majixTransferSelectedTo(
						\$this->aORenderlets["{$sSelectorName}"]->oSelected->getAbsName()
					),
					\$this->aORenderlets["{$sSelectorName}"]->majixUpdateHidden(),
				);
PHP;

            $aConf = [
                'onmouseup-999' => [
                    'userobj' => [
                        'php' => $sEvent,
                    ],
                ],
                'style' => 'width: 100%;',    // 100% of TD
            ];

            if (false !== ($aCustomConf = $this->getConfigValue('/available'))) {
                if (!is_array($aCustomConf)) {
                    $aCustomConf = [];
                }

                $aConf = \Sys25\RnBase\Utility\Arrays::mergeRecursiveWithOverrule(
                    $aConf,
                    $aCustomConf
                );
            }
            $aConf['type'] = 'LISTBOX';
            $aConf['name'] = $this->_getName().'_available';
            $aConf['multiple'] = true;
            $aConf['renderonly'] = true;

            $this->oAvailable = $this->oForm->_makeRenderlet(
                $aConf,
                $this->sXPath.'available/',
                false,
                $this,
                false,
                false
            );

            $this->oForm->aORenderlets[$this->oAvailable->getAbsName()] = &$this->oAvailable;
        }
    }

    public function initSelected()
    {
        if (false === $this->oSelected) {
            $aConf = [
                'style' => 'width: 100%;',    //	100% of TD
            ];
            if (false !== ($aCustomConf = $this->getConfigValue('/selected'))) {
                $aConf = \Sys25\RnBase\Utility\Arrays::mergeRecursiveWithOverrule(
                    $aConf,
                    $aCustomConf
                );
            }

            $aConf['type'] = 'LISTBOX';
            $aConf['name'] = $this->_getName().'_selected';
            $aConf['multiple'] = true;
            $aConf['renderonly'] = true;

            $this->oSelected = $this->oForm->_makeRenderlet(
                $aConf,
                $this->sXPath.'selected/',
                false,
                $this,
                false,
                false
            );

            $this->oForm->aORenderlets[$this->oSelected->getAbsName()] = &$this->oSelected;
        }
    }

    public function initCustomRenderlet()
    {
        if (false === $this->oCustomRenderlet) {
            if (false !== ($aConf = $this->getConfigValue('/customrenderlet'))) {
                $aConf['name'] = $this->_getName().'_customrenderlet';
                $this->oCustomRenderlet = $this->oForm->_makeRenderlet(
                    $aConf,
                    $this->sXPath.'customrenderlet/',
                    false,
                    $this,
                    false,
                    false
                );

                $this->oForm->aORenderlets[$this->oCustomRenderlet->getAbsName()] = &$this->oCustomRenderlet;
            }
        }
    }

    public function initButtonRemove()
    {
        if (false === $this->oButtonRemove) {
            $sSelectorName = $this->getAbsName();
            $sSourceName = $this->oSelected->getAbsName();
            $sTargetName = $this->oAvailable->getAbsName();
            $sEvent = <<<PHP

				return array(
					\$this->aORenderlets["{$sSourceName}"]->majixTransferSelectedTo("{$sTargetName}"),
					\$this->aORenderlets["{$sSelectorName}"]->majixUpdateHidden(),
				);

PHP;

            $aConf = [
                'type' => 'IMAGE',
                'path' => $this->sExtPath.'res/img/remove.gif',
                'onclick-999' => [            // 999 to avoid overruling by potential customly defined event
                    'runat' => 'client',
                    'userobj' => [
                        'php' => $sEvent,
                    ],
                ],
            ];

            if (false !== ($aCustomConf = $this->getConfigValue('/buttonremove'))) {
                $aConf = \Sys25\RnBase\Utility\Arrays::mergeRecursiveWithOverrule(
                    $aConf,
                    $aCustomConf
                );
            }

            $aConf['name'] = $sSelectorName.'_btnremove';

            $this->oButtonRemove = $this->oForm->_makeRenderlet(
                $aConf,
                $this->sXPath.'buttonremove/',
                false,
                $this,
                false,
                false
            );

            $this->oForm->aORenderlets[$this->oButtonRemove->getAbsName()] = &$this->oButtonRemove;
        }
    }

    public function initButtonMoveTop()
    {
        if (false === $this->oButtonMoveTop) {
            $sSelectorName = $this->getAbsName();
            $sEvent = <<<PHP

			return array(
				\$this->aORenderlets["{$sSelectorName}"]->oSelected->majixMoveSelectedTop(),
				\$this->aORenderlets["{$sSelectorName}"]->majixUpdateHidden(),
			);

PHP;
            $aConf = [
                'type' => 'IMAGE',
                'path' => $this->sExtPath.'res/img/top.gif',
                'onclick-999' => [
                    'runat' => 'client',
                    'userobj' => [
                        'php' => $sEvent,
                    ],
                ],
            ];

            if (false !== ($aCustomConf = $this->getConfigValue('/buttontop'))) {
                $aConf = \Sys25\RnBase\Utility\Arrays::mergeRecursiveWithOverrule(
                    $aConf,
                    $aCustomConf
                );
            }

            $aConf['name'] = $sSelectorName.'_btntop';

            $this->oButtonMoveTop = $this->oForm->_makeRenderlet(
                $aConf,
                $this->sXPath.'buttonmovetop/',
                false,
                $this,
                false,
                false
            );

            $this->oForm->aORenderlets[$this->oButtonMoveTop->getAbsName()] = &$this->oButtonMoveTop;
        }
    }

    public function initButtonMoveUp()
    {
        if (false === $this->oButtonMoveUp) {
            $sSelectorName = $this->getAbsName();
            $sEvent = <<<PHP

			return array(
				\$this->aORenderlets["{$sSelectorName}"]->oSelected->majixMoveSelectedUp(),
				\$this->aORenderlets["{$sSelectorName}"]->majixUpdateHidden(),
			);

PHP;
            $aConf = [
                'type' => 'IMAGE',
                'path' => $this->sExtPath.'res/img/up.gif',
                'onclick-999' => [
                    'runat' => 'client',
                    'userobj' => [
                        'php' => $sEvent,
                    ],
                ],
            ];

            if (false !== ($aCustomConf = $this->getConfigValue('/buttonup'))) {
                $aConf = \Sys25\RnBase\Utility\Arrays::mergeRecursiveWithOverrule(
                    $aConf,
                    $aCustomConf
                );
            }

            $aConf['name'] = $sSelectorName.'_btnup';
            $this->oButtonMoveUp = $this->oForm->_makeRenderlet(
                $aConf,
                $this->sXPath.'buttonmoveup/',
                false,
                $this,
                false,
                false
            );
            $this->oForm->aORenderlets[$this->oButtonMoveUp->getAbsName()] = &$this->oButtonMoveUp;
        }
    }

    public function initButtonMoveDown()
    {
        if (false === $this->oButtonMoveDown) {
            $sSelectorName = $this->getAbsName();
            $sEvent = <<<PHP

				return array(
					\$this->aORenderlets["{$sSelectorName}"]->oSelected->majixMoveSelectedDown(),
					\$this->aORenderlets["{$sSelectorName}"]->majixUpdateHidden(),
				);

PHP;
            $aConf = [
                'type' => 'IMAGE',
                'path' => $this->sExtPath.'res/img/down.gif',
                'onclick-999' => [
                    'runat' => 'client',
                    'userobj' => [
                        'php' => $sEvent,
                    ],
                ],
            ];

            if (false !== ($aCustomConf = $this->getConfigValue('/buttondown'))) {
                $aConf = \Sys25\RnBase\Utility\Arrays::mergeRecursiveWithOverrule(
                    $aConf,
                    $aCustomConf
                );
            }

            $aConf['name'] = $sSelectorName.'_btndown';

            $this->oButtonMoveDown = $this->oForm->_makeRenderlet(
                $aConf,
                $this->sXPath.'buttonmovedown/',
                false,
                $this,
                false,
                false
            );
            $this->oForm->aORenderlets[$this->oButtonMoveDown->getAbsName()] = &$this->oButtonMoveDown;
        }
    }

    public function initButtonMoveBottom()
    {
        if (false === $this->oButtonMoveBottom) {
            $sSelectorName = $this->getAbsName();
            $sEvent = <<<PHP

				return array(
					\$this->aORenderlets["{$sSelectorName}"]->oSelected->majixMoveSelectedBottom(),
					\$this->aORenderlets["{$sSelectorName}"]->majixUpdateHidden(),
				);

PHP;
            $aConf = [
                'type' => 'IMAGE',
                'path' => $this->sExtPath.'res/img/bottom.gif',
                'onclick-999' => [
                    'runat' => 'client',
                    'userobj' => [
                        'php' => $sEvent,
                    ],
                ],
            ];

            if (false !== ($aCustomConf = $this->getConfigValue('/buttonbottom'))) {
                $aConf = \Sys25\RnBase\Utility\Arrays::mergeRecursiveWithOverrule(
                    $aConf,
                    $aCustomConf
                );
            }

            $aConf['name'] = $sSelectorName.'_btnbottom';

            $this->oButtonMoveBottom = $this->oForm->_makeRenderlet(
                $aConf,
                $this->sXPath.'buttonmovebottom/',
                false,
                $this,
                false,
                false
            );

            $this->oForm->aORenderlets[$this->oButtonMoveBottom->getAbsName()] = &$this->oButtonMoveBottom;
        }
    }

    public function majixUpdateHidden()
    {
        return $this->buildMajixExecuter(
            'updateHidden'
        );
    }

    public function majixUnSelectAll()
    {
        return $this->buildMajixExecuter(
            'unSelectAll'
        );
    }

    public function cleanBeforeSession()
    {
        unset($this->oAvailable);
        unset($this->oSelected);
        unset($this->oButtonAdd);
        unset($this->oButtonRemove);
        unset($this->oButtonMoveTop);
        unset($this->oButtonMoveUp);
        unset($this->oButtonMoveDown);
        unset($this->oButtonMoveBottom);
        $this->baseCleanBeforeSession();
    }
}
