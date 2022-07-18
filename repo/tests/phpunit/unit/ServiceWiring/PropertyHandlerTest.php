<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\EntityTermStoreWriter;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\PropertyInfoStore;
use Wikibase\Repo\Content\PropertyHandler;
use Wikibase\Repo\PropertyInfoBuilder;
use Wikibase\Repo\Search\Fields\FieldDefinitionsFactory;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyHandlerTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.PropertyTermStoreWriter',
			$this->createMock( EntityTermStoreWriter::class ) );
		$this->mockService( 'WikibaseRepo.EntityContentDataCodec',
			$this->createMock( EntityContentDataCodec::class ) );
		$this->mockService( 'WikibaseRepo.EntityConstraintProvider',
			$this->createMock( EntityConstraintProvider::class ) );
		$this->mockService( 'WikibaseRepo.ValidatorErrorLocalizer',
			$this->createMock( ValidatorErrorLocalizer::class ) );
		$this->mockService( 'WikibaseRepo.EntityIdParser',
			new BasicEntityIdParser() );
		$this->mockService( 'WikibaseRepo.EntityIdLookup',
			$this->createMock( EntityIdLookup::class ) );
		$this->mockService( 'WikibaseRepo.FallbackLabelDescriptionLookupFactory',
			$this->createMock( FallbackLabelDescriptionLookupFactory::class ) );
		$store = $this->createMock( Store::class );
		$store->expects( $this->once() )
			->method( 'getPropertyInfoStore' )
			->willReturn( $this->createMock( PropertyInfoStore::class ) );
		$this->mockService( 'WikibaseRepo.Store',
			$store );
		$this->mockService( 'WikibaseRepo.PropertyInfoBuilder',
			new PropertyInfoBuilder() );
		$fieldDefinitionsFactory = $this->createMock( FieldDefinitionsFactory::class );
		$fieldDefinitionsFactory->expects( $this->once() )
			->method( 'getFieldDefinitionsByType' )
			->with( Property::ENTITY_TYPE );
		$this->mockService( 'WikibaseRepo.FieldDefinitionsFactory',
			$fieldDefinitionsFactory );
		$this->mockService( 'WikibaseRepo.LegacyFormatDetectorCallback',
			null );

		$this->assertInstanceOf(
			PropertyHandler::class,
			$this->getService( 'WikibaseRepo.PropertyHandler' )
		);
	}

}
