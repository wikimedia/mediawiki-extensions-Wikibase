<?php

namespace Wikibase\Lib\Tests;

use Cache\IntegrationTests\SimpleCacheTest;
use Wikibase\Lib\SimpleWANObjectCache;

/**
 * @covers \Wikibase\Lib\SimpleWANObjectCache
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SimpleWANObjectCacheTest extends SimpleCacheTest {

	protected $skippedTests = [
		'testClear' => 'Not possible to implement for WANObjectCache'
	];

	public function createSimpleCache() {
		return new SimpleWANObjectCache( $this->newWANObjectCache() );
	}

	private function newWANObjectCache() {
		return new \WANObjectCache( [ 'cache' => new \HashBagOStuff() ] );
	}

}
