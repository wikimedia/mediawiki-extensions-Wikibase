<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use MediaWiki\Storage\SlotRecord;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\EntitySourceAndTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Store\EntityExistenceChecker;
use Wikibase\Lib\Store\EntityTitleLookup;
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
		$this->mockService( 'WikibaseRepo.EntityTitleLookup',
			$this->createMock( EntityTitleLookup::class ) );

		$itemSourceName = 'local';
		$itemId = new ItemId( 'Q123' );
		$entityTypeDefinitions = new EntityTypeDefinitions( [
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
		] );
		$entitySourceDefinitions = new EntitySourceDefinitions(
			[ new EntitySource(
				$itemSourceName,
				false,
				[ Item::ENTITY_TYPE => [ 'namespaceId' => 120, 'slot' => SlotRecord::MAIN ] ],
				'http://wikidata.org/entity/',
				'',
				'',
				''
			) ],
			$entityTypeDefinitions
		);
		$this->mockService(
			'WikibaseRepo.EntitySourceDefinitions',
			$entitySourceDefinitions
		);
		$this->mockService(
			'WikibaseRepo.EntitySourceAndTypeDefinitions',
			new EntitySourceAndTypeDefinitions(
				$entityTypeDefinitions,
				$this->createStub( EntityTypeDefinitions::class ),
				$entitySourceDefinitions->getSources()
			)
		);
		$this->mockService(
			'WikibaseRepo.SubEntityTypesMap',
			[]
		);

		/** @var EntityExistenceChecker $entityExistenceChecker */
		$entityExistenceChecker = $this->getService( 'WikibaseRepo.EntityExistenceChecker' );

		$this->assertInstanceOf( EntityExistenceChecker::class, $entityExistenceChecker );
		$this->assertTrue( $entityExistenceChecker->exists( $itemId ) );
	}

}
