<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Tests\Acceptance\Application;

use Tx\Tinyurls\Tests\Acceptance\Support\BackendTester;
use Tx\Tinyurls\Tests\Acceptance\Support\Helper\PageTree;

class TinyurlCreationCest
{
    public function _before(BackendTester $I): void
    {
        $I->useExistingSession('admin');
    }

    public function tinyurlCanBeCreated(BackendTester $I, PageTree $pageTree): void
    {
        $I->click('List');

        $pageTree->openPath(['New TYPO3 site']);

        $I->wait(0.2);

        $I->switchToContentFrame();

        $I->click('Create new record');

        $I->waitForText('Tiny URL');

        $I->click('Tiny URL');

        $targetUrlInputSelector = 'input[data-formengine-input-name$="[target_url]"]';
        $targetUrlHashInputSelector = 'input[name$="[target_url_hash]"]';
        $generatedUrlInputSelector = 'input.tx-tinyurls-copyable-field-value';

        $I->waitForElement($targetUrlInputSelector);

        $I->fillField($targetUrlInputSelector, 'https://www.typo3.org');

        $I->click('Save');

        $I->waitForElement($generatedUrlInputSelector);

        $I->dontSeeInSource('alert-danger');

        $I->assertStringStartsWith(
            'http://web/?eID=tx_tinyurls&tx_tinyurls[key]=b-',
            $I->grabValueFrom($generatedUrlInputSelector),
        );

        $I->seeInField($targetUrlHashInputSelector, 'd1cc2438a81b8257891a2379b5b9e8af43ad0916');
    }
}
