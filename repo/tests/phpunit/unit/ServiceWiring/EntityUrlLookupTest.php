<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use MediaWiki\Revision\SlotRecord;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataAccess\Tests\NewDatabaseEntitySource;
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
			NewDatabaseEntitySource::havingName( 'item-source' )
				->withEntityNamespaceIdsAndSlots( [ 'item' => [ 'namespaceId' => 100, 'slot' => SlotRecord::MAIN ] ] )
				->withConceptBaseUri( 'http://wikidata.org/entity/' )
				->build(),
		];
		$this->mockService(
			'WikibaseRepo.EntitySourceLookup',
			new EntitySourceLookup( new EntitySourceDefinitions(
				$sources,
				new SubEntityTypesMapper( [] )
			), new SubEntityTypesMapper( [] ) )
		);
		$this->mockService( 'WikibaseRepo.EntitySourceAndTypeDefinitions',
			new EntitySourceAndTypeDefinitions(
				[
					DatabaseEntitySource::TYPE => new EntityTypeDefinitions( [
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
				],
				$sources
			)
		);

		/** @var EntityUrlLookup $entityUrlLookup */
		$entityUrlLookup = $this->getService( 'WikibaseRepo.EntityUrlLookup' );

		$this->assertInstanceOf( EntityUrlLookup::class, $entityUrlLookup );
		$this->assertSame( '/test/Q123', $entityUrlLookup->getLinkUrl( $itemId ) );
	}

}
