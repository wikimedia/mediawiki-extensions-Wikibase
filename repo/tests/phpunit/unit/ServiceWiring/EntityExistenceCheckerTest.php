<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
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
		$itemId = new ItemId( 'Q123' );
		$this->mockService( 'WikibaseRepo.EntityTypeDefinitions',
			new EntityTypeDefinitions( [
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
			] ) );
		$this->mockService( 'WikibaseRepo.EntityTitleLookup',
			$this->createMock( EntityTitleLookup::class ) );

		/** @var EntityExistenceChecker $entityExistenceChecker */
		$entityExistenceChecker = $this->getService( 'WikibaseRepo.EntityExistenceChecker' );

		$this->assertInstanceOf( EntityExistenceChecker::class, $entityExistenceChecker );
		$this->assertTrue( $entityExistenceChecker->exists( $itemId ) );
	}

}
