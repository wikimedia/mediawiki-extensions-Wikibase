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
 * @license GPL-2.0-or-later
 */
class SimpleCacheWithBagOStuffTest extends SimpleCacheTest {

	use \PHPUnit4And6Compat;

	protected $skippedTests = [
		'testClear' => 'Not possible to implement for BagOStuff'
	];

	/**
	 * @return CacheInterface that is used in the tests
	 */
	public function createSimpleCache() {
		return new SimpleCacheWithBagOStuff( new HashBagOStuff(), 'somePrefix', 'some secret' );
	}

	public function testUsesPrefixWhenSetting() {
		$inner = new HashBagOStuff();

		$prefix = 'somePrefix_';
		$simpleCache = new SimpleCacheWithBagOStuff( $inner, $prefix, 'some secret' );

		$simpleCache->set( 'test', 'value' );
		$this->assertNotFalse( $inner->get( 'somePrefix_test' ) );
	}

	public function testUsesPrefixWhenSettingMultiple() {
		$inner = new HashBagOStuff();

		$prefix = 'somePrefix_';
		$simpleCache = new SimpleCacheWithBagOStuff( $inner, $prefix, 'some secret' );

		$simpleCache->setMultiple( [ 'test' => 'value' ] );
		$this->assertNotFalse( $inner->get( 'somePrefix_test' ) );
	}

	public function testGivenPrefixContainsForbiddenCharacters_ConstructorThrowsException() {
		$prefix = '@somePrefix_';
		$inner = new HashBagOStuff();

		$this->setExpectedException( \InvalidArgumentException::class );
		new SimpleCacheWithBagOStuff( $inner, $prefix, 'some secret' );
	}

	/**
	 * This test ensures that we cannot accidentally deserialize arbitrary classes
	 * because it is unsecure.
	 *
	 * @see https://phabricator.wikimedia.org/T161647
	 * @see https://secure.php.net/manual/en/function.unserialize.php
	 * @see https://www.owasp.org/index.php/PHP_Object_Injection
	 */
	public function testObjectsCanNotBeStored_WhenRetrievedGetIncompleteClass() {
		$initialValue = new \DateTime();

		$cache = $this->createSimpleCache();
		$cache->set( 'key', $initialValue );
		$gotValue = $cache->get( 'key' );

		$this->assertInstanceOf( \__PHP_Incomplete_Class::class, $gotValue );
		$this->assertFalse( $initialValue == $gotValue );
	}

	/**
	 * This test ensures that if data in cache storage is compromised we won't accidentally
	 * use it.
	 *
	 * @see https://phabricator.wikimedia.org/T161647
	 * @see https://secure.php.net/manual/en/function.unserialize.php
	 * @see https://www.owasp.org/index.php/PHP_Object_Injection
	 */
	public function testGet_GivenSignatureIsWrong_ReturnsDefaultValue() {
		$inner = new HashBagOStuff();

		$cache = new SimpleCacheWithBagOStuff( $inner, 'prefix_', 'some secret' );
		$cache->set( 'key', 'some_string' );
		$this->spoilTheSignature( $inner, 'prefix_key' );

		$got = $cache->get( 'key', 'some default value' );
		$this->assertEquals( 'some default value', $got );
	}

	public function testGetMultiple_GivenSignatureIsWrong_ReturnsDefaultValue() {
		$inner = new HashBagOStuff();

		$cache = new SimpleCacheWithBagOStuff( $inner, 'prefix_', 'some secret' );
		$cache->set( 'key', 'some_string' );
		$this->spoilTheSignature( $inner, 'prefix_key' );

		$got = $cache->getMultiple( [ 'key' ], 'some default value' );
		$this->assertEquals( [ 'key' => 'some default value' ], $got );
	}

	public function testGet_GivenSignatureIsWrong_LoggsTheEvent() {
		$logger = $this->prophesize( LoggerInterface::class );

		$inner = new HashBagOStuff();

		$cache = new SimpleCacheWithBagOStuff( $inner, 'prefix_', 'some secret' );
		$cache->setLogger( $logger->reveal() );
		$cache->set( 'key', 'some_string' );
		$value = $inner->get( 'prefix_key' );
		list( $signature, $data ) = json_decode( $value );
		$inner->set( 'prefix_key', json_encode( [ 'wrong signature', $data ] ) );

		$got = $cache->get( 'key', 'some default value' );

		$logger->alert( Argument::any(), Argument::any() )->shouldHaveBeenCalled();
	}

	public function testCachedValueCannotBeUnserialized_ReturnsDefaultValue() {
		$inner = new HashBagOStuff();
		$brokenData = 'O:1';

		$correctSignature = hash_hmac( 'sha256', $brokenData, 'secret' );

		$cache = new SimpleCacheWithBagOStuff( $inner, 'prefix_', 'secret' );
		$cache->set( 'key', 'some_string' );
		$inner->set( 'prefix_key', json_encode( [ $correctSignature, $brokenData ] ) );

		$got = $cache->get( 'key', 'some default value' );
		$this->assertEquals( 'some default value', $got );
	}

	public function testSecretCanNotBeEmpty() {
		$inner = new HashBagOStuff();

		$this->setExpectedException( \Exception::class );
		new SimpleCacheWithBagOStuff( $inner, 'prefix_', '' );
	}

	/**
	 * @param $inner
	 * @param $key
	 */
	protected function spoilTheSignature( $inner, $key ) {
		$value = $inner->get( $key );
		list( $signature, $data ) = json_decode( $value );
		$inner->set( $key, json_encode( [ 'wrong signature', $data ] ) );
	}

	public function testSetTtl() {
		$inner = new HashBagOStuff();
		$now = microtime( true );
		$inner->setMockTime( $now );

		$prefix = 'somePrefix_';
		$cache = new SimpleCacheWithBagOStuff( $inner, $prefix, 'some secret' );

		$result = $cache->set( 'key1', 'value', 1 );
		$this->assertTrue( $result, 'set() must return true if success' );
		$this->assertEquals( 'value', $cache->get( 'key1' ) );
		$now += 3;
		$this->assertNull( $cache->get( 'key1' ), 'Value must expire after ttl.' );
	}

	public function testSetMultipleTtl() {
		$inner = new HashBagOStuff();
		$now = microtime( true );
		$inner->setMockTime( $now );

		$prefix = 'somePrefix_';
		$cache = new SimpleCacheWithBagOStuff( $inner, $prefix, 'some secret' );

		$cache->setMultiple( [ 'key2' => 'value2', 'key3' => 'value3' ], 1 );
		$this->assertEquals( 'value2', $cache->get( 'key2' ) );
		$this->assertEquals( 'value3', $cache->get( 'key3' ) );

		$now += 3;
		$this->assertNull( $cache->get( 'key2' ), 'Value must expire after ttl.' );
		$this->assertNull( $cache->get( 'key3' ), 'Value must expire after ttl.' );
	}

}
