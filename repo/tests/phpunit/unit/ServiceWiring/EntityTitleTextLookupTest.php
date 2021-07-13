<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\Tests\NewEntitySource;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\EntitySourceAndTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Store\EntityTitleTextLookup;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityTitleTextLookupTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$itemId = new ItemId( 'Q123' );

		$sources = [
			NewEntitySource::havingName( 'itemSource' )
				->withEntityNamespaceIdsAndSlots( [ 'item' => [ 'namespaceId' => 100, 'slot' => 'main' ] ] )
				->withConceptBaseUri( 'http://wikidorta.org/schmentity/' )
				->build()
		];

		$this->mockService(
			'WikibaseRepo.EntitySourceDefinitions',
			new EntitySourceDefinitions( $sources, new SubEntityTypesMapper( [] ) )
		);

		$this->mockService( 'WikibaseRepo.EntitySourceAndTypeDefinitions',
			new EntitySourceAndTypeDefinitions(
				[
					EntitySource::TYPE_DB => new EntityTypeDefinitions( [
						Item::ENTITY_TYPE => [
							EntityTypeDefinitions::TITLE_TEXT_LOOKUP_CALLBACK => function () use ( $itemId ) {
								$entityTitleTextLookup = $this->createMock( EntityTitleTextLookup::class );
								$entityTitleTextLookup->expects( $this->once() )
									->method( 'getPrefixedText' )
									->with( $itemId )
									->willReturn( 'Test_item:Q123' );
								return $entityTitleTextLookup;
							},
						],
					] ),
				],
				$sources
			)
		);

		$this->mockService(
			'WikibaseRepo.SubEntityTypesMapper',
			new SubEntityTypesMapper( [] )
		);

		/** @var EntityTitleTextLookup $entityTitleTextLookup */
		$entityTitleTextLookup = $this->getService( 'WikibaseRepo.EntityTitleTextLookup' );

		$this->assertInstanceOf( EntityTitleTextLookup::class, $entityTitleTextLookup );
		$this->assertSame( 'Test_item:Q123', $entityTitleTextLookup->getPrefixedText( $itemId ) );
	}

}
