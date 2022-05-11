<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\DataAccess;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\UnresolvedEntityRedirectException;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityRevisionLookupItemRevisionRetriever;
use Wikibase\Repo\RestApi\Domain\Model\ItemRevision;
use Wikibase\Repo\Tests\NewItem;

/**
 * @covers \Wikibase\Repo\RestApi\DataAccess\WikibaseEntityRevisionLookupItemRevisionRetriever
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WikibaseEntityRevisionLookupItemRevisionRetrieverTest extends TestCase {

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
			->with( $item->getId() )
			->willReturn( $entityRevision );

		$retriever = new WikibaseEntityRevisionLookupItemRevisionRetriever( $entityRevisionLookup );

		$this->assertEquals(
			new ItemRevision( $item, $lastModified, $revisionId ),
			$retriever->getItemRevision( $item->getId() )->getRevision()
		);
	}

	public function testGivenItemDoesNotExist_returnsItemNotFound(): void {
		$nonexistentItem = new ItemId( 'Q321' );
		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with( $nonexistentItem )
			->willReturn( null );

		$retriever = new WikibaseEntityRevisionLookupItemRevisionRetriever( $entityRevisionLookup );
		$result = $retriever->getItemRevision( $nonexistentItem );

		$this->assertFalse( $result->itemExists() );
	}

	public function testGivenItemIsARedirect_returnsRedirectResult(): void {
		$redirectedItem = new ItemId( 'Q666' );
		$redirectTarget = new ItemId( 'Q777' );

		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with( $redirectedItem )
			->willThrowException( new UnresolvedEntityRedirectException( $redirectedItem, $redirectTarget ) );

		$retriever = new WikibaseEntityRevisionLookupItemRevisionRetriever( $entityRevisionLookup );
		$result = $retriever->getItemRevision( $redirectedItem );

		$this->assertTrue( $result->isRedirect() );
		$this->assertSame( $redirectTarget, $result->getRedirectTarget() );
	}

}
