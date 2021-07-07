<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\Tests\NewEntitySource;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\EntitySourceAndTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Store\EntityUrlLookup;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityUrlLookupTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$itemId = new ItemId( 'Q123' );
		$sources = [
			NewEntitySource::havingName( 'item-source' )
				->withEntityNamespaceIdsAndSlots( [ 'item' => [ 'namespaceId' => 100, 'slot' => 'main' ] ] )
				->withConceptBaseUri( 'http://wikidata.org/entity/' )
				->build()
		];
		$this->mockService(
			'WikibaseRepo.EntitySourceDefinitions',
			new EntitySourceDefinitions( $sources, $this->createStub( EntityTypeDefinitions::class ) )
		);
		$this->mockService( 'WikibaseRepo.EntitySourceAndTypeDefinitions',
			new EntitySourceAndTypeDefinitions(
				new EntityTypeDefinitions( [
					Item::ENTITY_TYPE => [
						EntityTypeDefinitions::URL_LOOKUP_CALLBACK => function () use ( $itemId ) {
							$entityUrlLookup = $this->createMock( EntityUrlLookup::class );
							$entityUrlLookup->expects( $this->once() )
								->method( 'getLinkUrl' )
								->with( $itemId )
								->willReturn( '/test/Q123' );
							return $entityUrlLookup;
						},
					],
				] ),
				$this->createStub( EntityTypeDefinitions::class ),
				$sources
			)
		);
		$this->mockService(
			'WikibaseRepo.SubEntityTypesMapper',
			new SubEntityTypesMapper( [] )
		);

		/** @var EntityUrlLookup $entityUrlLookup */
		$entityUrlLookup = $this->getService( 'WikibaseRepo.EntityUrlLookup' );

		$this->assertInstanceOf( EntityUrlLookup::class, $entityUrlLookup );
		$this->assertSame( '/test/Q123', $entityUrlLookup->getLinkUrl( $itemId ) );
	}

}
