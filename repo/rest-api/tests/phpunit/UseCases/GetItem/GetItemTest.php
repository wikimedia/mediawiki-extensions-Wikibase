<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItem;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
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

		$retriever = $this->createStub( ItemRetriever::class );
		$retriever->method( "getItem" )->willReturn( NewItem::withId( $itemId )->andLabel( "en", "potato" )->build() );
		$serializer = WikibaseRepo::getBaseDataModelSerializerFactory()->newItemSerializer();

		$itemRequest = new GetItemRequest( $itemId );
		$itemResult = ( new GetItem( $retriever, $serializer ) )->execute( $itemRequest );
		$item = $itemResult->getItem();

		$this->assertInstanceOf( GetItemResult::class, $itemResult );
		$this->assertSame( $itemId, $item['id'] );
		$this->assertSame( $itemLabel, $item['labels']['en']['value'] );
	}

}
