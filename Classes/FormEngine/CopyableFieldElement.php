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
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * A custom TCA field type that renders a read only field of which the value
 * can be copied to the clipboard using JavaScript.
 */
class CopyableFieldElement extends AbstractNode implements NodeInterface
{
    public const TEMPLATE_PATH = 'EXT:tinyurls/Resources/Private/Templates/FormEngine/CopyableField.html';

    protected ?StandaloneView $formFieldView = null;

    protected ?GeneralUtilityWrapper $generalUtility = null;

    protected ?IconFactory $iconFactory = null;

    public function injectGeneralUtilityWrapper(GeneralUtilityWrapper $generalUtility): void
    {
        $this->generalUtility = $generalUtility;
    }

    public function injectIconFactory(IconFactory $iconFactory): void
    {
        $this->iconFactory = $iconFactory;
    }

    /**
     * Renders the copyable field and loads all required JavaScript / language files.
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render(): array
    {
        $result = $this->initializeResultArray();

        $template = $this->getFormFieldView();
        $this->initializeFormFieldViewTemplatePath($template);
        $template->assign('fieldValue', $this->getFieldValue());
        $template->assign('clipboardIcon', $this->getClipboardIcon());
        $result['html'] = $template->render();

        $result['requireJsModules'][] = JavaScriptModuleInstruction::create(
            '@de-swebhosting/tinyurls/copy-to-clipboard.js',
        );

        $result['additionalInlineLanguageLabelFiles'][] = 'EXT:tinyurls/Resources/Private/Language/locallang_db_js.xlf';

        return $result;
    }

    public function setFormFieldView(StandaloneView $formFieldView): void
    {
        $this->formFieldView = $formFieldView;
    }

    /**
     * Uses the icon factory to build a clipboard icon.
     */
    protected function getClipboardIcon(): string
    {
        /** @extensionScannerIgnoreLine */
        $iconFactory = $this->getIconFactory();
        return $iconFactory->getIcon('actions-edit-copy', IconSize::SMALL)->render();
    }

    /**
     * Returns the value that should be displayed in the field.
     *
     * If configured, a custom user function is called to retrieve the value.
     * Otherwise the current field value is used.
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
            $this,
        );
    }

    /**
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
     * @codeCoverageIgnore
     */
    protected function getIconFactory(): IconFactory
    {
        if ($this->iconFactory === null) {
            $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        }
        return $this->iconFactory;
    }

    protected function initializeFormFieldViewTemplatePath(StandaloneView $template): void
    {
        $template->setTemplatePathAndFilename(
            $this->getGeneralUtility()->getFileAbsFileName(static::TEMPLATE_PATH),
        );
    }
}
