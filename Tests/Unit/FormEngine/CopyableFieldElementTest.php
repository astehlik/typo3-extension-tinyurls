<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Tests\Unit\FormEngine;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use LogicException;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tx\Tinyurls\FormEngine\CopyableFieldElement;
use Tx\Tinyurls\Utils\GeneralUtilityWrapper;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Fluid\View\StandaloneView;

#[BackupGlobals(true)]
class CopyableFieldElementTest extends TestCase
{
    private CopyableFieldElement $copyableFieldElement;

    private MockObject|StandaloneView $formFieldViewMock;

    private GeneralUtilityWrapper|MockObject $generalUtilityWrapperMock;

    private IconFactory|MockObject $iconFactoryMock;

    protected function setUp(): void
    {
        $data = ['parameterArray' => ['itemFormElValue' => 'testval']];
        $GLOBALS['LANG'] = $this->createMock(LanguageService::class);
        $this->createCopyableFieldElement($data);
    }

    public function testGetFieldValueCallsConfiguredUserFunc(): void
    {
        $data = [
            'parameterArray' => [
                'itemFormElValue' => 'testval',
                'fieldConf' => ['config' => ['valueFunc' => 'thefunc']],
            ],
        ];

        $this->createCopyableFieldElement($data);

        $this->generalUtilityWrapperMock->expects(self::once())
            ->method('callUserFunction')
            ->with('thefunc', $data, $this->copyableFieldElement);

        $this->copyableFieldElement->render();
    }

    public function testRenderAssignsExpectedVariablesToTemplate(): void
    {
        $this->formFieldViewMock
            ->expects(self::exactly(3))
            ->method('assign')
            ->willReturnCallback(
                static fn(string $name, string $value) => match (true) {
                    $name === 'fieldValue' && $value === 'testval' => 1,
                    $name === 'clipboardIcon' && $value === 'icon html' => 2,
                    $name === 'clipboardButtonLabel' && $value === '' => 3,
                    default => throw new LogicException('Unexpected name or value: ' . $name . ' => ' . $value),
                },
            );

        $this->copyableFieldElement->render();
    }

    public function testRenderCreatesSmallClipboardIcon(): void
    {
        $this->iconFactoryMock->expects(self::once())
            ->method('getIcon')
            ->with('actions-edit-copy', IconSize::SMALL);

        $this->copyableFieldElement->render();
    }

    public function testRenderInitializesResultArray(): void
    {
        // Test for some common array keys. This way we do not need to mock the test subject.
        self::assertArrayHasKey('additionalInlineLanguageLabelFiles', $this->copyableFieldElement->render());
        self::assertArrayHasKey('javaScriptModules', $this->copyableFieldElement->render());
        self::assertArrayHasKey('inlineData', $this->copyableFieldElement->render());
    }

    public function testRenderInitializesTemplatePathInFormFieldView(): void
    {
        $this->generalUtilityWrapperMock->expects(self::once())
            ->method('getFileAbsFileName')
            ->with(CopyableFieldElement::TEMPLATE_PATH)
            ->willReturn('the template path');

        $this->formFieldViewMock->expects(self::once())
            ->method('setTemplatePathAndFilename')
            ->with('the template path');

        $this->copyableFieldElement->render();
    }

    public function testRenderLoadsAdditionalLanguageLabels(): void
    {
        self::assertSame(
            ['EXT:tinyurls/Resources/Private/Language/locallang_db_js.xlf'],
            $this->copyableFieldElement->render()['additionalInlineLanguageLabelFiles'],
        );
    }

    public function testRenderLoadsCopyToClipboardJsModule(): void
    {
        self::assertCount(1, $this->copyableFieldElement->render()['javaScriptModules']);

        $instruction = $this->copyableFieldElement->render()['javaScriptModules'][0];

        self::assertInstanceOf(JavaScriptModuleInstruction::class, $instruction);

        self::assertSame('@de-swebhosting/tinyurls/copy-to-clipboard.js', $instruction->getName());
    }

    public function testRenderReturnsRenderedFieldTemplate(): void
    {
        $this->formFieldViewMock->expects(self::once())
            ->method('render')
            ->willReturn('The final html');

        $result = $this->copyableFieldElement->render();
        self::assertSame('The final html', $result['html']);
    }

    protected function createCopyableFieldElement(array $data): void
    {
        $this->copyableFieldElement = new CopyableFieldElement();
        $this->copyableFieldElement->setData($data);

        $this->copyableFieldElement->injectGeneralUtilityWrapper($this->getGeneralUtilityWrapperMock());
        $this->copyableFieldElement->injectIconFactory($this->getIconFactoryMock());
        $this->copyableFieldElement->setFormFieldView($this->createFormFieldViewMock());
    }

    private function createFormFieldViewMock(): MockObject|StandaloneView
    {
        $this->formFieldViewMock = $this->createMock(StandaloneView::class);
        return $this->formFieldViewMock;
    }

    private function getGeneralUtilityWrapperMock(): GeneralUtilityWrapper|MockObject
    {
        $this->generalUtilityWrapperMock = $this->createMock(GeneralUtilityWrapper::class);
        return $this->generalUtilityWrapperMock;
    }

    private function getIconFactoryMock(): IconFactory|MockObject
    {
        $iconFactoryMock = $this->createMock(IconFactory::class);
        $iconMock = $this->createMock(Icon::class);
        $iconMock->method('render')->willReturn('icon html');
        $iconFactoryMock->method('getIcon')->willReturn($iconMock);
        $this->iconFactoryMock = $iconFactoryMock;
        return $this->iconFactoryMock;
    }
}
