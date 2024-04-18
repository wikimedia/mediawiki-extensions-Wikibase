<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use DataValues\Deserializers\DataValueDeserializer;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\DataTypeDefinitions;
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
			'WikibaseRepo.DataValueDeserializer',
			$this->createMock( DataValueDeserializer::class )
		);
		$dataTypeDefinitions = $this->createStub( DataTypeDefinitions::class );
		$dataTypeDefinitions->method( 'getParserFactoryCallbacks' )->willReturn( [] );
		$this->mockService( 'WikibaseRepo.DataTypeDefinitions', $dataTypeDefinitions );
		$this->mockService(
			'WikibaseRepo.DataTypeFactory',
			$this->createMock( DataTypeFactory::class )
		);
		$this->mockService(
			'WikibaseRepo.PropertyDataTypeLookup',
			$this->createMock( PropertyDataTypeLookup::class )
		);
		$this->assertInstanceOf(
			SnakFactory::class,
			$this->getService( 'WikibaseRepo.SnakFactory' )
		);
	}
}
