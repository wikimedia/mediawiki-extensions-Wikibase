<?php
declare( strict_types=1 );

namespace Wikibase\Lib\Tests;

use HashBagOStuff;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Wikibase\Lib\SimpleCacheWithBagOStuff;

/**
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @covers \Wikibase\Lib\SimpleCacheWithBagOStuff
 */
class SimpleCacheWithBagOStuffTest extends SimpleCacheTestCase {

	protected $skippedTests = [
		'testClear' => 'Not possible to implement for BagOStuff',
	];

	/**
	 * @return CacheInterface that is used in the tests
	 */
	public function createSimpleCache() {
		return new SimpleCacheWithBagOStuff( new HashBagOStuff(), 'somePrefix', 'some secret' );
	}

	public function testUsesPrefixWhenSetting() {
		$inner = new HashBagOStuff();

		$prefix = 'somePrefix';
		$simpleCache = new SimpleCacheWithBagOStuff( $inner, $prefix, 'some secret' );

		$simpleCache->set( 'test', 'value' );
		$key = $inner->makeKey( $prefix, 'test' );
		$this->assertNotFalse( $inner->get( $key ) );
	}

	public function testUsesPrefixWhenSettingMultiple() {
		$inner = new HashBagOStuff();

		$prefix = 'somePrefix';
		$simpleCache = new SimpleCacheWithBagOStuff( $inner, $prefix, 'some secret' );

		$simpleCache->setMultiple( [ 'test' => 'value' ] );
		$key = $inner->makeKey( $prefix, 'test' );
		$this->assertNotFalse( $inner->get( $key ) );
	}

	public function testGivenPrefixContainsForbiddenCharacters_ConstructorThrowsException() {
		$prefix = '@somePrefix';
		$inner = new HashBagOStuff();

		$this->expectException( \InvalidArgumentException::class );
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

		$cache = new SimpleCacheWithBagOStuff( $inner, 'prefix', 'some secret' );
		$cache->set( 'key', 'some_string' );
		$key = $inner->makeKey( 'prefix', 'key' );
		$this->spoilTheSignature( $inner, $key );

		$got = $cache->get( 'key', 'some default value' );
		$this->assertEquals( 'some default value', $got );
	}

	public function testGetMultiple_GivenSignatureIsWrong_ReturnsDefaultValue() {
		$inner = new HashBagOStuff();

		$cache = new SimpleCacheWithBagOStuff( $inner, 'prefix', 'some secret' );
		$cache->set( 'key', 'some_string' );
		$key = $inner->makeKey( 'prefix', 'key' );
		$this->spoilTheSignature( $inner, $key );

		$got = $cache->getMultiple( [ 'key' ], 'some default value' );
		$this->assertEquals( [ 'key' => 'some default value' ], $got );
	}

	public function testGet_GivenSignatureIsWrong_LoggsTheEvent() {
		$logger = $this->createMock( LoggerInterface::class );
		$logger->expects( $this->atLeastOnce() )->method( 'alert' );

		$inner = new HashBagOStuff();

		$cache = new SimpleCacheWithBagOStuff( $inner, 'prefix', 'some secret' );
		$cache->setLogger( $logger );
		$cache->set( 'key', 'some_string' );
		$key = $inner->makeKey( 'prefix', 'key' );
		$value = $inner->get( $key );
		list( $signature, $data ) = json_decode( $value );
		$inner->set( $key, json_encode( [ 'wrong signature', $data ] ) );

		$got = $cache->get( 'key', 'some default value' );
	}

	public function testCachedValueCannotBeUnserialized_ReturnsDefaultValue() {
		$inner = new HashBagOStuff();
		$brokenData = 'O:1';

		$correctSignature = hash_hmac( 'sha256', $brokenData, 'secret' );

		$cache = new SimpleCacheWithBagOStuff( $inner, 'prefix', 'secret' );
		$cache->set( 'key', 'some_string' );
		$key = $inner->makeKey( 'prefix', 'key' );
		$inner->set( $key, json_encode( [ $correctSignature, $brokenData ] ) );

		$got = $cache->get( 'key', 'some default value' );
		$this->assertEquals( 'some default value', $got );
	}

	public function testSecretCanNotBeEmpty() {
		$inner = new HashBagOStuff();

		$this->expectException( \Exception::class );
		new SimpleCacheWithBagOStuff( $inner, 'prefix', '' );
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

		$prefix = 'someprefix';
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

		$prefix = 'someprefix';
		$cache = new SimpleCacheWithBagOStuff( $inner, $prefix, 'some secret' );

		$cache->setMultiple( [ 'key2' => 'value2', 'key3' => 'value3' ], 1 );
		$this->assertEquals( 'value2', $cache->get( 'key2' ) );
		$this->assertEquals( 'value3', $cache->get( 'key3' ) );

		$now += 3;
		$this->assertNull( $cache->get( 'key2' ), 'Value must expire after ttl.' );
		$this->assertNull( $cache->get( 'key3' ), 'Value must expire after ttl.' );
	}

	public function testUTF8KeysAreValid() {
		$inner = new HashBagOStuff();

		$prefix = 'someprefix';
		$cache = new SimpleCacheWithBagOStuff( $inner, $prefix, 'some secret' );

		$this->assertTrue( $cache->set( '🏄', 'some value' ) );
		$this->assertTrue( $cache->set( '⧼Lang⧽', 'some value' ) );
	}

}
