<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItem;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Domain\Model\ItemRevision;
use Wikibase\Repo\RestApi\Domain\Serializers\ItemSerializer;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionRetriever;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItem;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemRequest;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemResult;
use Wikibase\Repo\Tests\NewItem;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\GetItem\GetItem
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemTest extends TestCase {

	public function testGetExistingItem(): void {
		$itemId = "Q123";
		$itemLabel = "potato";
		$lastModifiedTimestamp = '20201111070707';
		$revisionId = 42;

		$retriever = $this->createStub( ItemRevisionRetriever::class );
		$retriever->method( "getItemRevision" )->willReturn(
			new ItemRevision(
				NewItem::withId( $itemId )->andLabel( "en", $itemLabel )->build(),
				$lastModifiedTimestamp,
				$revisionId
			)
		);
		$serializer = new ItemSerializer(
			WikibaseRepo::getBaseDataModelSerializerFactory()->newItemSerializer()
		);

		$itemRequest = new GetItemRequest( $itemId );
		$itemResult = ( new GetItem( $retriever, $serializer ) )->execute( $itemRequest );
		$item = $itemResult->getItem();

		$this->assertInstanceOf( GetItemResult::class, $itemResult );
		$this->assertSame( $itemId, $item['id'] );
		$this->assertSame( $itemLabel, $item['labels']['en']['value'] );
		$this->assertSame( $lastModifiedTimestamp, $itemResult->getLastModified() );
		$this->assertSame( $revisionId, $itemResult->getRevisionId() );
	}

	public function testItemNotFound(): void {
		$itemId = "Q123";

		$retriever = $this->createStub( ItemRevisionRetriever::class );
		$retriever->method( "getItemRevision" )->willReturn( null );
		$serializer = new ItemSerializer(
			WikibaseRepo::getBaseDataModelSerializerFactory()->newItemSerializer()
		);

		$itemRequest = new GetItemRequest( $itemId );
		$itemResult = ( new GetItem( $retriever, $serializer ) )->execute( $itemRequest );

		$this->assertFalse( $itemResult->isSuccessful() );
		$this->assertNull( $itemResult->getItem() );
		$this->assertNull( $itemResult->getLastModified() );
		$this->assertNull( $itemResult->getRevisionId() );
		$this->assertSame( 'item-not-found', $itemResult->getError()->getCode() );
	}
}
