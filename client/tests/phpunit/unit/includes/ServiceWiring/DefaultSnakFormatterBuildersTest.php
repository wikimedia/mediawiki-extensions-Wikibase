<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

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
			'WikibaseClient.PropertyInfoLookup',
			$this->createMock( PropertyInfoLookup::class )
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

}
