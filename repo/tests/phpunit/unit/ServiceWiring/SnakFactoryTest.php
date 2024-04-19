<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataModel\Deserializers\SnakValueParser;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Repo\SnakFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SnakFactoryTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->mockService(
			'WikibaseRepo.DataTypeFactory',
			$this->createMock( DataTypeFactory::class )
		);
		$this->mockService(
			'WikibaseRepo.PropertyDataTypeLookup',
			$this->createMock( PropertyDataTypeLookup::class )
		);
		$this->mockService(
			'WikibaseRepo.SnakValueParser',
			$this->createMock( SnakValueParser::class )
		);
		$this->assertInstanceOf(
			SnakFactory::class,
			$this->getService( 'WikibaseRepo.SnakFactory' )
		);
	}
}
