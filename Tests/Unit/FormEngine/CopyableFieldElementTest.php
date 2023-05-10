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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tx\Tinyurls\FormEngine\CopyableFieldElement;
use Tx\Tinyurls\Utils\GeneralUtilityWrapper;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Fluid\View\StandaloneView;

class CopyableFieldElementTest extends TestCase
{
    private CopyableFieldElement $copyableFieldElement;

    private StandaloneView|MockObject $formFieldViewMock;

    private GeneralUtilityWrapper|MockObject $generalUtilityWrapperMock;

    private MockObject|IconFactory $iconFactoryMock;

    protected function setUp(): void
    {
        $data = ['parameterArray' => ['itemFormElValue' => 'testval']];
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
            ->expects(self::exactly(2))
            ->method('assign')
            ->willReturnCallback(fn (string $name, string $value) => match (true) {
                $name === 'fieldValue' && $value === 'testval' => 1,
                $name === 'clipboardIcon' && $value === 'icon html' => 2,
                default => throw new \LogicException('Unexpected name or value: ' . $name . ' ' . $value),
            });

        $this->copyableFieldElement->render();
    }

    public function testRenderCreatesSmallClipboardIcon(): void
    {
        $this->iconFactoryMock->expects(self::once())
            ->method('getIcon')
            ->with('actions-edit-copy', Icon::SIZE_SMALL);

        $this->copyableFieldElement->render();
    }

    public function testRenderInitializesResultArray(): void
    {
        // Test for some common array keys. This way we do not need to mock the test subject.
        self::assertArrayHasKey('additionalJavaScriptPost', $this->copyableFieldElement->render());
        self::assertArrayHasKey('additionalHiddenFields', $this->copyableFieldElement->render());
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
            $this->copyableFieldElement->render()['additionalInlineLanguageLabelFiles']
        );
    }

    public function testRenderLoadsCopyToClipboardJsModule(): void
    {
        self::assertSame(
            ['TYPO3/CMS/Tinyurls/CopyToClipboard'],
            $this->copyableFieldElement->render()['requireJsModules']
        );
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
        $nodeFactory = $this->getNodeFactoryMock();
        $this->copyableFieldElement = new CopyableFieldElement($nodeFactory, $data);

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

    private function getNodeFactoryMock(): MockObject|NodeFactory
    {
        return $this->createMock(NodeFactory::class);
    }
}
