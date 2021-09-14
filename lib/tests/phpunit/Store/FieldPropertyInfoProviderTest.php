<?php

namespace Wikibase\Lib\Tests\Store;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Lib\Store\FieldPropertyInfoProvider;
use Wikibase\Lib\Store\PropertyInfoLookup;

/**
 * @covers \Wikibase\Lib\Store\FieldPropertyInfoProvider
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class FieldPropertyInfoProviderTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider provideGetPropertyInfo
	 */
	public function testGetPropertyInfo( $info, $key, $expected ) {
		$propertyId = new NumericPropertyId( 'P1' );

		$lookup = $this->createMock( PropertyInfoLookup::class );
		$lookup->expects( $this->once() )
			->method( 'getPropertyInfo' )
			->with( $propertyId )
			->willReturn( $info );

		$instance = new FieldPropertyInfoProvider( $lookup, $key );
		$this->assertSame( $expected, $instance->getPropertyInfo( $propertyId ) );
	}

	public function provideGetPropertyInfo() {
		return [
			'no info array' => [ null, 'foo', null ],
			'empty info array' => [ [], 'foo', null ],
			'found info field' => [ [ 'hrmf' => 'Mitten', 'foo' => 'Kitten' ], 'foo', 'Kitten' ],
		];
	}

}
