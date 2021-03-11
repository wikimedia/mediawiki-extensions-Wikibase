<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\EntityUrlLookup;
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
		$this->mockService( 'WikibaseRepo.EntityTypeDefinitions',
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
			] ) );
		$this->mockService( 'WikibaseRepo.EntityTitleLookup',
			$this->createMock( EntityTitleLookup::class ) );

		/** @var EntityUrlLookup $entityUrlLookup */
		$entityUrlLookup = $this->getService( 'WikibaseRepo.EntityUrlLookup' );

		$this->assertInstanceOf( EntityUrlLookup::class, $entityUrlLookup );
		$this->assertSame( '/test/Q123', $entityUrlLookup->getLinkUrl( $itemId ) );
	}

}
