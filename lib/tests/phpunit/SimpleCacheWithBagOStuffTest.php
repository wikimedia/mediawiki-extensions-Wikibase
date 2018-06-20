<?php

namespace Wikibase\Lib\Tests;

use Cache\IntegrationTests\SimpleCacheTest;
use HashBagOStuff;
use Psr\SimpleCache\CacheInterface;
use Wikibase\Lib\SimpleCacheWithBagOStuff;

class SimpleCacheWithBagOStuffTest extends SimpleCacheTest {

	protected $skippedTests = [
		'testClear' => 'Not possible to implement for BagOStuff'
	];

	/**
	 * @return CacheInterface that is used in the tests
	 */
	public function createSimpleCache() {
		return new SimpleCacheWithBagOStuff( new HashBagOStuff() );
	}

}
