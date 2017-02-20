<?php
declare(strict_types = 1);
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
    /**
     * Renders the copyable field and loads all required JavaScript / language files.
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $result = $this->initializeResultArray();


        $template = $this->getFormFieldView();
        $template->assign('fieldValue', $this->getFieldValue());
        $template->assign('clipboardIcon', $this->getClipboardIcon());
        $result['html'] = $template->render();

        $result['requireJsModules'][] = 'TYPO3/CMS/Tinyurls/CopyToClipboard';

        $result['additionalInlineLanguageLabelFiles'][] = 'EXT:tinyurls/Resources/Private/Language/locallang_db_js.xlf';

        return $result;
    }

    /**
     * Uses the icon factory to build a clipboard icon.
     *
     * @return string
     */
    protected function getClipboardIcon()
    {
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        return $iconFactory->getIcon('actions-edit-copy', Icon::SIZE_SMALL)->render();
    }

    /**
     * Returns the value that should be displayed in the field.
     * If configured, a custom user function is called to retrieve the value.
     * Otherwise the current field value is used.
     *
     * @return string
     */
    protected function getFieldValue()
    {
        $parameterArray = $this->data['parameterArray'];
        if (empty($parameterArray['fieldConf']['config']['valueFunc'])) {
            return $parameterArray['itemFormElValue'];
        }
        return GeneralUtility::callUserFunction(
            $parameterArray['fieldConf']['config']['valueFunc'],
            $this->data,
            $this
        );
    }

    /**
     * Returns a Fluid standalone view for the copyable form field template.
     *
     * @return StandaloneView
     */
    protected function getFormFieldView()
    {
        $template = GeneralUtility::makeInstance(StandaloneView::class);
        $template->setTemplatePathAndFilename(
            GeneralUtility::getFileAbsFileName('EXT:tinyurls/Resources/Private/Templates/FormEngine/CopyableField.html')
        );
        return $template;
    }
}
