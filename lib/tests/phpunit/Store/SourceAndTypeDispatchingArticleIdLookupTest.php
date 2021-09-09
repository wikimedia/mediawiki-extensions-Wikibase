<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataAccess\Tests\NewDatabaseEntitySource;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Lib\ServiceBySourceAndTypeDispatcher;
use Wikibase\Lib\Store\EntityArticleIdLookup;
use Wikibase\Lib\Store\SourceAndTypeDispatchingArticleIdLookup;

/**
 * @covers \Wikibase\Lib\Store\SourceAndTypeDispatchingArticleIdLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SourceAndTypeDispatchingArticleIdLookupTest extends TestCase {

	public function testGivenLookupDefinedForEntityType_usesRespectiveLookup() {
		$entityId = new NumericPropertyId( 'P123' );
		$articleId = 23;

		$stubArticleIdLookup = $this->createStub( EntityArticleIdLookup::class );
		$stubArticleIdLookup->method( 'getArticleId' )->willReturn( $articleId );
		$sourceName = 'some-source-name';
		$source = NewDatabaseEntitySource::havingName( $sourceName )->build();

		$dispatcher = $this->createMock( ServiceBySourceAndTypeDispatcher::class );
		$dispatcher->expects( $this->atLeastOnce() )->method( 'getServiceForSourceAndType' )->with( $sourceName, 'property' )->willReturn(
			$stubArticleIdLookup
		);

		$lookup = $this->createMock( EntitySourceLookup::class );
		$lookup->expects( $this->atLeastOnce() )->method( 'getEntitySourceById' )->with( $entityId )->willReturn( $source );

		$lookup = new SourceAndTypeDispatchingArticleIdLookup(
			$lookup,
			$dispatcher
		);

		$this->assertSame( $articleId, $lookup->getArticleId( $entityId ) );
	}

	private function newNeverCalledMockLookup(): EntityArticleIdLookup {
		$lookup = $this->createMock( EntityArticleIdLookup::class );
		$lookup->expects( $this->never() )->method( $this->anything() );

		return $lookup;
	}

}
