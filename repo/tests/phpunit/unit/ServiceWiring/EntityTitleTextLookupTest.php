<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\EntityTitleTextLookup;
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
		$this->mockService( 'WikibaseRepo.EntityTypeDefinitions',
			new EntityTypeDefinitions( [
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
			] ) );
		$this->mockService( 'WikibaseRepo.EntityTitleLookup',
			$this->createMock( EntityTitleLookup::class ) );

		/** @var EntityTitleTextLookup $entityTitleTextLookup */
		$entityTitleTextLookup = $this->getService( 'WikibaseRepo.EntityTitleTextLookup' );

		$this->assertInstanceOf( EntityTitleTextLookup::class, $entityTitleTextLookup );
		$this->assertSame( 'Test_item:Q123', $entityTitleTextLookup->getPrefixedText( $itemId ) );
	}

}
