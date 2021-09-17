<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataAccess\Tests\NewDatabaseEntitySource;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\EntitySourceAndTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Store\EntityArticleIdLookup;
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

		$sourceAndTypeDefinitions = $this->createMock( EntitySourceAndTypeDefinitions::class );
		$sourceName = 'some-source';
		$sourceAndTypeDefinitions->expects( $this->once() )->method( 'getServiceBySourceAndType' )->with(
				EntityTypeDefinitions::ARTICLE_ID_LOOKUP_CALLBACK
			)->willReturn( [ $sourceName => [ 'item' => function () use ( $itemId ) {
				$entityArticleIdLookup = $this->createMock( EntityArticleIdLookup::class );
				$entityArticleIdLookup->expects( $this->once() )->method( 'getArticleId' )->with( $itemId )->willReturn( 123 );

				return $entityArticleIdLookup;
			} ] ] );
		$this->mockService( 'WikibaseRepo.EntitySourceAndTypeDefinitions', $sourceAndTypeDefinitions );

		$stubSourceLookup = $this->createStub( EntitySourceLookup::class );
		$stubSourceLookup->method( 'getEntitySourceById' )
			->willReturn( NewDatabaseEntitySource::havingName( $sourceName )->build() );
		$this->mockService( 'WikibaseRepo.EntitySourceLookup', $stubSourceLookup );

		/** @var EntityArticleIdLookup $entityArticleIdLookup */
		$entityArticleIdLookup = $this->getService( 'WikibaseRepo.EntityArticleIdLookup' );

		$this->assertInstanceOf( EntityArticleIdLookup::class, $entityArticleIdLookup );
		$this->assertSame( 123, $entityArticleIdLookup->getArticleId( $itemId ) );
	}

}
