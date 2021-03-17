<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use DataValues\Deserializers\DataValueDeserializer;
use Deserializers\DispatchableDeserializer;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\InternalSerialization\DeserializerFactory as InternalDeserializerFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class InternalFormatDeserializerFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.DataValueDeserializer',
			new DataValueDeserializer( [] ) );
		$this->mockService( 'WikibaseRepo.EntityIdParser',
			new ItemIdParser() );
		$this->mockService( 'WikibaseRepo.AllTypesEntityDeserializer',
			$this->createMock( DispatchableDeserializer::class ) );

		$this->assertInstanceOf(
			InternalDeserializerFactory::class,
			$this->getService( 'WikibaseRepo.InternalFormatDeserializerFactory' )
		);
	}

}
