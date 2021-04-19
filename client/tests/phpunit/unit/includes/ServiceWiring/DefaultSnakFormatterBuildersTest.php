<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Store\ClientStore;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\Formatters\WikibaseSnakFormatterBuilders;
use Wikibase\Lib\Formatters\WikibaseValueFormatterBuilders;
use Wikibase\Lib\Store\PropertyInfoLookup;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DefaultSnakFormatterBuildersTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseClient.DefaultValueFormatterBuilders',
			$this->createMock( WikibaseValueFormatterBuilders::class )
		);

		$this->mockService(
			'WikibaseClient.Store',
			$this->getMockStore()
		);

		$this->mockService(
			'WikibaseClient.PropertyDataTypeLookup',
			$this->createMock( PropertyDataTypeLookup::class )
		);

		$this->mockService(
			'WikibaseClient.DataTypeFactory',
			$this->createMock( DataTypeFactory::class )
		);

		$this->assertInstanceOf(
			WikibaseSnakFormatterBuilders::class,
			$this->getService( 'WikibaseClient.DefaultSnakFormatterBuilders' )
		);
	}

	private function getMockStore(): ClientStore {
		$store = $this->createMock( ClientStore::class );
		$propertyInfoLookup = $this->createMock( PropertyInfoLookup::class );

		$store->expects( $this->once() )
			->method( 'getPropertyInfoLookup' )
			->willReturn( $propertyInfoLookup );

		return $store;
	}

}
