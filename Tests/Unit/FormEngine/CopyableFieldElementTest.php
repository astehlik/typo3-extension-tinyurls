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
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Core\View\ViewInterface;

#[BackupGlobals(true)]
class CopyableFieldElementTest extends TestCase
{
    private CopyableFieldElement $copyableFieldElement;

    private GeneralUtilityWrapper|MockObject $generalUtilityWrapperMock;

    private IconFactory|MockObject $iconFactoryMock;

    private ViewFactoryInterface|MockObject $viewFactoryMock;

    private MockObject|ViewInterface $viewMock;

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

        $this->generalUtilityWrapperMock->expects($this->once())
            ->method('callUserFunction')
            ->with('thefunc', $data, $this->copyableFieldElement);

        $this->copyableFieldElement->render();
    }

    public function testRenderAssignsExpectedVariablesToTemplate(): void
    {
        $this->viewMock
            ->expects($this->exactly(3))
            ->method('assign')
            ->willReturn($this->viewMock);

        $this->copyableFieldElement->render();
    }

    public function testRenderCreatesSmallClipboardIcon(): void
    {
        $this->iconFactoryMock->expects($this->once())
            ->method('getIcon')
            ->with('actions-edit-copy', IconSize::SMALL);

        $this->copyableFieldElement->render();
    }

    public function testRenderInitializesResultArray(): void
    {
        // Test for some common array keys. This way we do not need to mock the test subject.
        $this->assertArrayHasKey('additionalInlineLanguageLabelFiles', $this->copyableFieldElement->render());
        $this->assertArrayHasKey('javaScriptModules', $this->copyableFieldElement->render());
        $this->assertArrayHasKey('inlineData', $this->copyableFieldElement->render());
    }

    public function testRenderInitializesTemplatePathInFormFieldView(): void
    {
        $this->generalUtilityWrapperMock->expects($this->once())
            ->method('getFileAbsFileName')
            ->with(CopyableFieldElement::TEMPLATE_PATH)
            ->willReturn('the template path');

        $this->viewFactoryMock->expects($this->once())
            ->method('create')
            ->with(new ViewFactoryData(templatePathAndFilename: 'the template path'));

        $this->copyableFieldElement->render();
    }

    public function testRenderLoadsAdditionalLanguageLabels(): void
    {
        $this->assertSame(
            ['EXT:tinyurls/Resources/Private/Language/locallang_db_js.xlf'],
            $this->copyableFieldElement->render()['additionalInlineLanguageLabelFiles'],
        );
    }

    public function testRenderLoadsCopyToClipboardJsModule(): void
    {
        $this->assertCount(1, $this->copyableFieldElement->render()['javaScriptModules']);

        $instruction = $this->copyableFieldElement->render()['javaScriptModules'][0];

        $this->assertInstanceOf(JavaScriptModuleInstruction::class, $instruction);

        $this->assertSame('@de-swebhosting/tinyurls/copy-to-clipboard.js', $instruction->getName());
    }

    public function testRenderReturnsRenderedFieldTemplate(): void
    {
        $this->viewMock->expects($this->once())
            ->method('render')
            ->willReturn('The final html');

        $result = $this->copyableFieldElement->render();
        $this->assertSame('The final html', $result['html']);
    }

    protected function createCopyableFieldElement(array $data): void
    {
        $this->copyableFieldElement = new CopyableFieldElement();
        $this->copyableFieldElement->setData($data);

        $this->copyableFieldElement->injectGeneralUtilityWrapper($this->getGeneralUtilityWrapperMock());
        $this->copyableFieldElement->injectIconFactory($this->getIconFactoryMock());
        $this->copyableFieldElement->injectViewFactory($this->createViewFactoryMock());
    }

    private function createViewFactoryMock(): MockObject|ViewFactoryInterface
    {
        $this->viewMock = $this->createMock(ViewInterface::class);

        $this->viewFactoryMock = $this->createMock(ViewFactoryInterface::class);
        $this->viewFactoryMock->method('create')->willReturn($this->viewMock);

        return $this->viewFactoryMock;
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
