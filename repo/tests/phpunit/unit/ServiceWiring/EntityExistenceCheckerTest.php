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
use Wikibase\Lib\Store\EntityExistenceChecker;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityExistenceCheckerTest extends ServiceWiringTestCase {

	public function testConstruction(): void {

		$itemId = new ItemId( 'Q123' );
		$sources = [
			NewDatabaseEntitySource::havingName( 'itemSource' )
				->withEntityNamespaceIdsAndSlots( [ 'item' => [ 'namespaceId' => 100, 'slot' => SlotRecord::MAIN ] ] )
				->withConceptBaseUri( 'http://wikidorta.org/schmentity/' )
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
							EntityTypeDefinitions::EXISTENCE_CHECKER_CALLBACK => function () use ( $itemId ) {
								$entityExistenceChecker = $this->createMock( EntityExistenceChecker::class );
								$entityExistenceChecker->expects( $this->once() )
									->method( 'exists' )
									->with( $itemId )
									->willReturn( true );
								return $entityExistenceChecker;
							},
						],
					] ),
				],
				$sources
			)
		);

		/** @var EntityExistenceChecker $entityExistenceChecker */
		$entityExistenceChecker = $this->getService( 'WikibaseRepo.EntityExistenceChecker' );

		$this->assertInstanceOf( EntityExistenceChecker::class, $entityExistenceChecker );
		$this->assertTrue( $entityExistenceChecker->exists( $itemId ) );
	}

}
