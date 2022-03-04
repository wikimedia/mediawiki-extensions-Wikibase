<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItem;

use MediaWikiIntegrationTestCase;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityLookupItemRetriever;
use Wikibase\Repo\Tests\NewItem;

/**
 * @covers \Wikibase\Repo\RestApi\DataAccess\WikibaseEntityLookupItemRetriever
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class WikibaseEntityLookupItemRetrieverTest extends MediaWikiIntegrationTestCase {

	public function testGetItem(): void {
		$item = NewItem::withId( 'Q123' )->build();

		$itemRevision = $this->createMock( EntityRevision::class );
		$itemRevision->expects( $this->once() )
			->method( 'getEntity' )
			->willReturn( $item );

		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->willReturn( $itemRevision );

		$retriever = new WikibaseEntityLookupItemRetriever( $entityRevisionLookup );

		$this->assertEquals(
			$item,
			$retriever->getItem( $item->getId() )
		);
	}

}
