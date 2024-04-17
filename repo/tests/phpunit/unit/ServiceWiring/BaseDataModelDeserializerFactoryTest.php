<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use DataValues\Deserializers\DataValueDeserializer;
use Psr\Log\LoggerInterface;
use Wikibase\DataModel\Deserializers\DeserializerFactory;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class BaseDataModelDeserializerFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseRepo.DataValueDeserializer',
			$this->createMock( DataValueDeserializer::class )
		);

		$this->mockService(
			'WikibaseRepo.EntityIdParser',
			$this->createMock( EntityIdParser::class )
		);

		$dataTypeDefinitions = $this->createStub( DataTypeDefinitions::class );
		$dataTypeDefinitions->method( 'getValueTypes' )->willReturn( [] );
		$dataTypeDefinitions->method( 'getParserFactoryCallbacks' )->willReturn( [] );
		$this->mockService(
			'WikibaseRepo.DataTypeDefinitions',
			$dataTypeDefinitions
		);

		$this->mockService(
			'WikibaseRepo.PropertyInfoLookup',
			$this->createMock( PropertyInfoLookup::class )
		);

		$this->mockService(
			'WikibaseRepo.Logger',
			$this->createMock( LoggerInterface::class )
		);

		$this->assertInstanceOf(
			DeserializerFactory::class,
			$this->getService( 'WikibaseRepo.BaseDataModelDeserializerFactory' )
		);
	}

}
