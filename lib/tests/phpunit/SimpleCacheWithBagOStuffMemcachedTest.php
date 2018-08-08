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
class SimpleCacheWithBagOStuffMemcachedTest extends SimpleCacheTest {

	use \PHPUnit4And6Compat;

	protected $skippedTests = [
		'testClear' => 'Not possible to implement for BagOStuff',
		'testSetTtl' => 'Test is flacky',
		'testSetExpiredTtl' => 'Test is flacky',
		'testSetMultipleTtl' => 'Test is flacky',
		'testSetMultipleExpiredTtl' => 'Test is flacky',
	];

	protected function setUp() {
		if ( !extension_loaded( 'memcached' ) ) {
			$this->markTestSkipped( "Memcached extension is not enabled" );
			return;
		}

		$m = new \Memcached();
		$m->addServer( $this->getHost(), $this->getPort() );
		if ( !$m->getVersion() ) {
			$this->fail( "Memcached error: {$m->getLastErrorMessage()}" );
		}

		parent::setUp();
	}

	protected function tearDown() {
		parent::tearDown();

		$m = new \Memcached();
		$m->addServer( $this->getHost(), $this->getPort() );
		$m->flush();
	}

	/**
	 * @return CacheInterface that is used in the tests
	 */
	public function createSimpleCache() {
		$params = [
			'persistent' => false,
			'timeout' => 1000000, // microseconds
			'servers' => [ $this->getHost() . ':' . $this->getPort() ]
		];
		return new SimpleCacheWithBagOStuff(
			new \MemcachedPeclBagOStuff( $params ),
			'somePrefix',
			'some secret'
		);
	}

	private function getHost() {
		return getenv( 'TEST_MEMCACHED_HOST', true ) ?: 'localhost';
	}

	private function getPort() {
		return (int)getenv( 'TEST_MEMCACHED_PORT', true ) ?: 11211;
	}
}
