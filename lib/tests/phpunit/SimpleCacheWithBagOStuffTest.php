<?php

// phpcs:disable

namespace Cache\IntegrationTests {

	use PHPUnit\Framework\TestCase;

	if ( !class_exists( 'Cache\IntegrationTests\SimpleCacheTest' ) ) {
		abstract class SimpleCacheTest extends TestCase {}
	}
}

namespace Wikibase\Lib\Tests {

	use Cache\IntegrationTests\SimpleCacheTest;
	use HashBagOStuff;
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
			return new SimpleCacheWithBagOStuff( new HashBagOStuff(), 'somePrefix' );
		}

		public function testUsesPrefixWhenSetting() {
			$inner = new HashBagOStuff();

			$prefix = 'somePrefix_';
			$simpleCache = new SimpleCacheWithBagOStuff( $inner, $prefix );

			$simpleCache->set( 'test', 'value' );
			$this->assertNotFalse( $inner->get( 'somePrefix_test' ) );
		}

		public function testUsesPrefixWhenSettingMultiple() {
			$inner = new HashBagOStuff();

			$prefix = 'somePrefix_';
			$simpleCache = new SimpleCacheWithBagOStuff( $inner, $prefix );

			$simpleCache->setMultiple( [ 'test' => 'value' ] );
			$this->assertNotFalse( $inner->get( 'somePrefix_test' ) );
		}

		public function testGivenPrefixContainsForbiddenCharacters_ConstructorThrowsException() {
			$prefix = '@somePrefix_';
			$inner = new HashBagOStuff();

			$this->setExpectedException( \InvalidArgumentException::class );
			new SimpleCacheWithBagOStuff( $inner, $prefix );
		}

	}
}
