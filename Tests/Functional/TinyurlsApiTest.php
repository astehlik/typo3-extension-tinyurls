<?php
namespace Tx\Tinyurls\Tests\Functional;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\CMS\Core\Tests\FunctionalTestCase;

/**
 * Functional tests for the tinyurls API.
 */
class TinyurlsApiTest extends FunctionalTestCase {

	/**
	 * @var array
	 */
	protected $testExtensionsToLoad = array(
		'typo3conf/ext/tinyurls',
	);

	/**
	 * @test
	 */
	public function dummyTest() {
		$this->assertTrue(TRUE);
	}
}
