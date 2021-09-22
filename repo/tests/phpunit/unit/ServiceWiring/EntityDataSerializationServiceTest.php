<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Serializers\Serializer;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\LinkedData\EntityDataSerializationService;
use Wikibase\Repo\Rdf\RdfBuilderFactory;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityDataSerializationServiceTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.EntityTitleStoreLookup',
			$this->createMock( EntityTitleStoreLookup::class ) );
		$this->mockService( 'WikibaseRepo.PropertyDataTypeLookup',
			$this->createMock( PropertyDataTypeLookup::class ) );
		$this->mockService( 'WikibaseRepo.EntityDataFormatProvider',
			new EntityDataFormatProvider() );
		$this->mockService( 'WikibaseRepo.BaseDataModelSerializerFactory',
			$this->createMock( SerializerFactory::class ) );
		$this->mockService( 'WikibaseRepo.AllTypesEntitySerializer',
			$this->createMock( Serializer::class ) );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getSiteLookup' );
		$this->mockService( 'WikibaseRepo.RdfBuilderFactory',
			$this->createMock( RdfBuilderFactory::class ) );
		$this->mockService( 'WikibaseRepo.EntityIdParser',
			$this->createMock( EntityIdParser::class ) );

		$this->assertInstanceOf(
			EntityDataSerializationService::class,
			$this->getService( 'WikibaseRepo.EntityDataSerializationService' )
		);
	}

}
