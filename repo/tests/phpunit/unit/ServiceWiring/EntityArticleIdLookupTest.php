<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Store\EntityArticleIdLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityArticleIdLookupTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$itemId = new ItemId( 'Q123' );
		$this->mockService( 'WikibaseRepo.EntityTypeDefinitions',
			new EntityTypeDefinitions( [
				Item::ENTITY_TYPE => [
					EntityTypeDefinitions::ARTICLE_ID_LOOKUP_CALLBACK => function () use ( $itemId ) {
						$entityArticleIdLookup = $this->createMock( EntityArticleIdLookup::class );
						$entityArticleIdLookup->expects( $this->once() )
							->method( 'getArticleId' )
							->with( $itemId )
							->willReturn( 123 );
						return $entityArticleIdLookup;
					},
				],
			] ) );
		$this->mockService( 'WikibaseRepo.EntityTitleLookup',
			$this->createMock( EntityTitleLookup::class ) );

		/** @var EntityArticleIdLookup $entityArticleIdLookup */
		$entityArticleIdLookup = $this->getService( 'WikibaseRepo.EntityArticleIdLookup' );

		$this->assertInstanceOf( EntityArticleIdLookup::class, $entityArticleIdLookup );
		$this->assertSame( 123, $entityArticleIdLookup->getArticleId( $itemId ) );
	}

}
