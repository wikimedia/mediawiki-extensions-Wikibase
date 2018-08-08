<?php

namespace Wikibase\Lib\Tests;

use Cache\IntegrationTests\SimpleCacheTest;
use HashBagOStuff;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Wikibase\Lib\SimpleCacheWithBagOStuff;

/**
 * @group Wikibase
 *
 * @covers \Wikibase\Lib\SimpleCacheWithBagOStuff
 *
 * @license GPL-2.0-or-later
 */
class SimpleCacheWithBagOStuffApcuTest extends SimpleCacheTest {

	use \PHPUnit4And6Compat;

	protected $skippedTests = [
		'testClear' => 'Not possible to implement for BagOStuff'
	];

	protected function tearDown() {
		parent::tearDown();

		apcu_clear_cache();
	}

	/**
	 * @return CacheInterface that is used in the tests
	 */
	public function createSimpleCache() {
		return new SimpleCacheWithBagOStuff( new \APCUBagOStuff(), 'somePrefix', 'some secret' );
	}
}
