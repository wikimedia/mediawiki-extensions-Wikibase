<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItem;

use MediaWikiIntegrationTestCase;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityRevisionLookupItemRevisionRetriever;
use Wikibase\Repo\RestApi\Domain\Model\ItemRevision;
use Wikibase\Repo\Tests\NewItem;

/**
 * @covers \Wikibase\Repo\RestApi\DataAccess\WikibaseEntityRevisionLookupItemRevisionRetriever
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class WikibaseEntityRevisionLookupItemRevisionRetrieverTest extends MediaWikiIntegrationTestCase {

	public function testGetItemRevision(): void {
		$item = NewItem::withId( 'Q123' )->build();
		$revisionId = 42;
		$lastModified = '20201111070707';

		$entityRevision = $this->createMock( EntityRevision::class );
		$entityRevision->expects( $this->once() )
			->method( 'getEntity' )
			->willReturn( $item );
		$entityRevision->expects( $this->once() )
			->method( 'getRevisionId' )
			->willReturn( $revisionId );
		$entityRevision->expects( $this->once() )
			->method( 'getTimestamp' )
			->willReturn( $lastModified );

		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->willReturn( $entityRevision );

		$retriever = new WikibaseEntityRevisionLookupItemRevisionRetriever( $entityRevisionLookup );

		$this->assertEquals(
			new ItemRevision( $item, $lastModified, $revisionId ),
			$retriever->getItemRevision( $item->getId() )
		);
	}
}
