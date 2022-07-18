<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\EntityTermStoreWriter;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\HashSiteLinkStore;
use Wikibase\Repo\Content\ItemHandler;
use Wikibase\Repo\Search\Fields\FieldDefinitionsFactory;
use Wikibase\Repo\Store\BagOStuffSiteLinkConflictLookup;
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
class ItemHandlerTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.ItemTermStoreWriter',
			$this->createMock( EntityTermStoreWriter::class ) );
		$this->mockService( 'WikibaseRepo.EntityContentDataCodec',
			$this->createMock( EntityContentDataCodec::class ) );
		$this->mockService( 'WikibaseRepo.EntityConstraintProvider',
			$this->createMock( EntityConstraintProvider::class ) );
		$this->mockService( 'WikibaseRepo.ValidatorErrorLocalizer',
			$this->createMock( ValidatorErrorLocalizer::class ) );
		$this->mockService( 'WikibaseRepo.EntityIdParser',
			new ItemIdParser() );
		$store = $this->createMock( Store::class );
		$store->expects( $this->once() )
			->method( 'newSiteLinkStore' )
			->willReturn( new HashSiteLinkStore() );
		$this->mockService( 'WikibaseRepo.Store',
			$store );
		$this->mockService( 'WikibaseRepo.BagOStuffSiteLinkConflictLookup',
			$this->createMock( BagOStuffSiteLinkConflictLookup::class ) );
		$this->mockService( 'WikibaseRepo.EntityIdLookup',
			$this->createMock( EntityIdLookup::class ) );
		$this->mockService( 'WikibaseRepo.FallbackLabelDescriptionLookupFactory',
			$this->createMock( FallbackLabelDescriptionLookupFactory::class ) );
		$fieldDefinitionsFactory = $this->createMock( FieldDefinitionsFactory::class );
		$fieldDefinitionsFactory->expects( $this->once() )
			->method( 'getFieldDefinitionsByType' )
			->with( Item::ENTITY_TYPE );
		$this->mockService( 'WikibaseRepo.FieldDefinitionsFactory',
			$fieldDefinitionsFactory );
		$this->mockService( 'WikibaseRepo.PropertyDataTypeLookup',
			new InMemoryDataTypeLookup() );
		$repoDomainDbFactory = $this->createMock( RepoDomainDbFactory::class );
		$repoDomainDbFactory->expects( $this->once() )
			->method( 'newRepoDb' );
		$this->mockService( 'WikibaseRepo.RepoDomainDbFactory',
			$repoDomainDbFactory );
		$this->mockService( 'WikibaseRepo.LegacyFormatDetectorCallback',
			null );

		$this->assertInstanceOf(
			ItemHandler::class,
			$this->getService( 'WikibaseRepo.ItemHandler' )
		);
	}

}
