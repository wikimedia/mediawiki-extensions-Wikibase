<?php

namespace Wikibase\Lib\Tests\Store;

use HashBagOStuff;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\CachingPropertyInfoLookup;
use Wikibase\Lib\Store\PropertyInfoLookup;

/**
 * @covers Wikibase\Lib\Store\CachingPropertyInfoLookup
 *
 * @group Wikibase
 * @group WikibasePropertyInfo
 * @group WikibaseStore
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class CachingPropertyInfoLookupTest extends \MediaWikiTestCase {

	private function newCachingPropertyInfoLookup( array $info = [] ) {
		$mock = new MockPropertyInfoLookup( $info );
		$cache = new HashBagOStuff();
		return new CachingPropertyInfoLookup( $mock, $cache );
	}

	public function testGivenUnknownPropertyId_getPropertyInfoReturnsNull() {
		$lookup = $this->newCachingPropertyInfoLookup();

		$this->assertNull( $lookup->getPropertyInfo( new PropertyId( 'P32' ) ) );
	}

	public function testGivenKnownPropertyId_getPropertyInfoReturnsTheInfo() {
		$lookup = $this->newCachingPropertyInfoLookup( [ 'P23' => [ PropertyInfoLookup::KEY_DATA_TYPE => 'string' ] ] );

		$this->assertSame(
			[ PropertyInfoLookup::KEY_DATA_TYPE => 'string' ],
			$lookup->getPropertyInfo( new PropertyId( 'P23' ) )
		);
	}

	public function testGetAllPropertyInfo() {
		$lookup = $this->newCachingPropertyInfoLookup( [
			'P23' => [ PropertyInfoLookup::KEY_DATA_TYPE => 'string' ],
			'P42' => [ PropertyInfoLookup::KEY_DATA_TYPE => 'string', 'foo' => 'bar' ],
		] );

		$this->assertSame(
			[
				'P23' => [ PropertyInfoLookup::KEY_DATA_TYPE => 'string' ],
				'P42' => [ PropertyInfoLookup::KEY_DATA_TYPE => 'string', 'foo' => 'bar' ],
			],
			$lookup->getAllPropertyInfo()
		);
	}

	public function testGivenDataTypeWithKnownProperties_getPropertyInfoForDataTypeReturnsTheInfo() {
		$lookup = $this->newCachingPropertyInfoLookup( [
			'P23' => [ PropertyInfoLookup::KEY_DATA_TYPE => 'string' ],
			'P42' => [ PropertyInfoLookup::KEY_DATA_TYPE => 'string', 'foo' => 'bar' ],
		] );

		$this->assertSame(
			[
				'P23' => [ PropertyInfoLookup::KEY_DATA_TYPE => 'string' ],
				'P42' => [ PropertyInfoLookup::KEY_DATA_TYPE => 'string', 'foo' => 'bar' ],
			],
			$lookup->getPropertyInfoForDataType( 'string' )
		);
	}

	public function testGivenDataTypeWithNoProperties_getPropertyInfoForDataTypeReturnsEmptyList() {
		$lookup = $this->newCachingPropertyInfoLookup();

		$this->assertSame(
			[],
			$lookup->getPropertyInfoForDataType( 'external-id' )
		);
	}

}
