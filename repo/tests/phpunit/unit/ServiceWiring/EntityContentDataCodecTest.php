<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Deserializers\Deserializer;
use Serializers\Serializer;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityContentDataCodecTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.EntityIdParser',
			new ItemIdParser() );
		$this->mockService( 'WikibaseRepo.StorageEntitySerializer',
			$this->createMock( Serializer::class ) );
		$this->mockService( 'WikibaseRepo.InternalFormatEntityDeserializer',
			$this->createMock( Deserializer::class ) );
		$dataAccessSettings = $this->createMock( DataAccessSettings::class );
		$dataAccessSettings->expects( $this->once() )
			->method( 'maxSerializedEntitySizeInBytes' )
			->willReturn( 1 );
		$this->mockService( 'WikibaseRepo.DataAccessSettings',
			$dataAccessSettings );

		$this->assertInstanceOf(
			EntityContentDataCodec::class,
			$this->getService( 'WikibaseRepo.EntityContentDataCodec' )
		);
	}

}
