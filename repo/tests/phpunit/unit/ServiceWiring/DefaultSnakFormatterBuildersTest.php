<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\Formatters\WikibaseSnakFormatterBuilders;
use Wikibase\Lib\Formatters\WikibaseValueFormatterBuilders;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DefaultSnakFormatterBuildersTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.DefaultValueFormatterBuilders',
			$this->createMock( WikibaseValueFormatterBuilders::class ) );
		$store = $this->createMock( Store::class );
		$store->expects( $this->once() )
			->method( 'getPropertyInfoLookup' )
			->willReturn( $this->createMock( PropertyInfoLookup::class ) );
		$this->mockService( 'WikibaseRepo.Store',
			$store );
		$this->mockService( 'WikibaseRepo.PropertyDataTypeLookup',
			new InMemoryDataTypeLookup() );
		$this->mockService( 'WikibaseRepo.DataTypeFactory',
			new DataTypeFactory( [] ) );

		$this->assertInstanceOf(
			WikibaseSnakFormatterBuilders::class,
			$this->getService( 'WikibaseRepo.DefaultSnakFormatterBuilders' )
		);
	}

}
