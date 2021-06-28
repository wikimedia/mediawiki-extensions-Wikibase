<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Serializers\Serializer;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\LinkedData\EntityDataSerializationService;
use Wikibase\Repo\Rdf\EntityRdfBuilderFactory;
use Wikibase\Repo\Rdf\EntityStubRdfBuilderFactory;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\ValueSnakRdfBuilderFactory;
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
		$this->mockService( 'WikibaseRepo.EntityRevisionLookup',
			$this->createMock( EntityRevisionLookup::class ) );
		$this->mockService( 'WikibaseRepo.EntityTitleStoreLookup',
			$this->createMock( EntityTitleStoreLookup::class ) );
		$this->mockService( 'WikibaseRepo.EntityContentFactory',
			$this->createMock( EntityContentFactory::class ) );
		$this->mockService( 'WikibaseRepo.PropertyDataTypeLookup',
			$this->createMock( PropertyDataTypeLookup::class ) );
		$this->mockService( 'WikibaseRepo.ValueSnakRdfBuilderFactory',
			$this->createMock( ValueSnakRdfBuilderFactory::class ) );
		$this->mockService( 'WikibaseRepo.EntityRdfBuilderFactory',
			$this->createMock( EntityRdfBuilderFactory::class ) );
		$this->mockService( 'WikibaseRepo.EntityStubRdfBuilderFactory',
			$this->createMock( EntityStubRdfBuilderFactory::class ) );
		$this->mockService( 'WikibaseRepo.EntityDataFormatProvider',
			new EntityDataFormatProvider() );
		$this->mockService( 'WikibaseRepo.BaseDataModelSerializerFactory',
			$this->createMock( SerializerFactory::class ) );
		$this->mockService( 'WikibaseRepo.AllTypesEntitySerializer',
			$this->createMock( Serializer::class ) );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getSiteLookup' );
		$this->mockService( 'WikibaseRepo.RdfVocabulary',
			$this->createMock( RdfVocabulary::class ) );

		$this->assertInstanceOf(
			EntityDataSerializationService::class,
			$this->getService( 'WikibaseRepo.EntityDataSerializationService' )
		);
	}

}
