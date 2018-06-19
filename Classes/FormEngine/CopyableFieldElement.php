<?php
declare(strict_types=1);

namespace Tx\Tinyurls\FormEngine;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Tx\Tinyurls\Utils\GeneralUtilityWrapper;
use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Backend\Form\NodeInterface;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * A custom TCA field type that renders a read only field of which the value
 * can be copied to the clipboard using JavaScript.
 */
class CopyableFieldElement extends AbstractNode implements NodeInterface
{
    const TEMPLATE_PATH = 'EXT:tinyurls/Resources/Private/Templates/FormEngine/CopyableField.html';

    /**
     * @var StandaloneView
     */
    protected $formFieldView;

    /**
     * @var GeneralUtilityWrapper
     */
    protected $generalUtility;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @param GeneralUtilityWrapper $generalUtility
     */
    public function injectGeneralUtilityWrapper(GeneralUtilityWrapper $generalUtility)
    {
        $this->generalUtility = $generalUtility;
    }

    /**
     * @param IconFactory $iconFactory
     */
    public function injectIconFactory(IconFactory $iconFactory)
    {
        $this->iconFactory = $iconFactory;
    }

    /**
     * Renders the copyable field and loads all required JavaScript / language files.
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $result = $this->initializeResultArray();

        $template = $this->getFormFieldView();
        $this->initializeFormFieldViewTemplatePath($template);
        $template->assign('fieldValue', $this->getFieldValue());
        $template->assign('clipboardIcon', $this->getClipboardIcon());
        $result['html'] = $template->render();

        $result['requireJsModules'][] = 'TYPO3/CMS/Tinyurls/CopyToClipboard';

        $result['additionalInlineLanguageLabelFiles'][] = 'EXT:tinyurls/Resources/Private/Language/locallang_db_js.xlf';

        return $result;
    }

    /**
     * @param StandaloneView $formFieldView
     */
    public function setFormFieldView(StandaloneView $formFieldView)
    {
        $this->formFieldView = $formFieldView;
    }

    /**
     * Uses the icon factory to build a clipboard icon.
     *
     * @return string
     */
    protected function getClipboardIcon(): string
    {
        $iconFactory = $this->getIconFactory();
        return $iconFactory->getIcon('actions-edit-copy', Icon::SIZE_SMALL)->render();
    }

    /**
     * Returns the value that should be displayed in the field.
     *
     * If configured, a custom user function is called to retrieve the value.
     * Otherwise the current field value is used.
     *
     * @return string
     */
    protected function getFieldValue(): string
    {
        $parameterArray = $this->data['parameterArray'];
        if (empty($parameterArray['fieldConf']['config']['valueFunc'])) {
            return $parameterArray['itemFormElValue'];
        }
        return (string)$this->generalUtility->callUserFunction(
            $parameterArray['fieldConf']['config']['valueFunc'],
            $this->data,
            $this
        );
    }

    /**
     * @return StandaloneView
     * @codeCoverageIgnore
     */
    protected function getFormFieldView(): StandaloneView
    {
        if ($this->formFieldView === null) {
            $this->formFieldView = GeneralUtility::makeInstance(StandaloneView::class);
        }
        return $this->formFieldView;
    }

    /**
     * @return GeneralUtilityWrapper
     * @codeCoverageIgnore
     */
    protected function getGeneralUtility(): GeneralUtilityWrapper
    {
        if ($this->generalUtility === null) {
            $this->generalUtility = GeneralUtility::makeInstance(GeneralUtilityWrapper::class);
        }
        return $this->generalUtility;
    }

    /**
     * @return IconFactory
     * @codeCoverageIgnore
     */
    protected function getIconFactory(): IconFactory
    {
        if ($this->iconFactory === null) {
            $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        }
        return $this->iconFactory;
    }

    protected function initializeFormFieldViewTemplatePath(StandaloneView $template)
    {
        $template->setTemplatePathAndFilename(
            $this->getGeneralUtility()->getFileAbsFileName(static::TEMPLATE_PATH)
        );
    }
}
