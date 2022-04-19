<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItem;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Domain\Model\ItemRevision;
use Wikibase\Repo\RestApi\Domain\Model\ItemRevisionResult;
use Wikibase\Repo\RestApi\Domain\Serializers\ItemSerializer;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionRetriever;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItem;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemErrorResponse;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemRequest;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemSuccessResponse;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemValidator;
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

	private const ITEM_ID = "Q123";
	private const ITEM_LABEL = "potato";
	private const ITEM_DESCRIPTION = "a root vegetable";

	public function testGetExistingItem(): void {
		$itemId = "Q123";
		$itemLabel = "potato";
		$lastModifiedTimestamp = '20201111070707';
		$revisionId = 42;

		$retriever = $this->createStub( ItemRevisionRetriever::class );
		$retriever->method( "getItemRevision" )->willReturn(
			ItemRevisionResult::concreteRevision( new ItemRevision(
				NewItem::withId( $itemId )->andLabel( "en", $itemLabel )->build(),
				$lastModifiedTimestamp,
				$revisionId
			) )
		);
		$serializer = new ItemSerializer(
			WikibaseRepo::getBaseDataModelSerializerFactory()->newItemSerializer()
		);
		$validator = new GetItemValidator();

		$itemRequest = new GetItemRequest( $itemId );
		$itemResponse = ( new GetItem( $retriever, $serializer, $validator ) )->execute( $itemRequest );
		$item = $itemResponse->getItem();

		$this->assertInstanceOf( GetItemSuccessResponse::class, $itemResponse );
		$this->assertSame( $itemId, $item['id'] );
		$this->assertSame( $itemLabel, $item['labels']['en'] );
		$this->assertSame( $lastModifiedTimestamp, $itemResponse->getLastModified() );
		$this->assertSame( $revisionId, $itemResponse->getRevisionId() );
	}

	public function testItemNotFound(): void {
		$itemId = "Q123";

		$retriever = $this->createStub( ItemRevisionRetriever::class );
		$retriever->method( "getItemRevision" )
			->willReturn( ItemRevisionResult::itemNotFound() );
		$serializer = new ItemSerializer(
			WikibaseRepo::getBaseDataModelSerializerFactory()->newItemSerializer()
		);
		$validator = new GetItemValidator();

		$itemRequest = new GetItemRequest( $itemId );
		$itemResponse = ( new GetItem( $retriever, $serializer, $validator ) )->execute( $itemRequest );
		$this->assertInstanceOf( GetItemErrorResponse::class, $itemResponse );
		$this->assertSame( ErrorResponse::ITEM_NOT_FOUND, $itemResponse->getCode() );
	}

	public function testInvalidItemId(): void {
		$itemId = "X123";

		$retriever = $this->createStub( ItemRevisionRetriever::class );
		$serializer = new ItemSerializer(
			WikibaseRepo::getBaseDataModelSerializerFactory()->newItemSerializer()
		);
		$validator = new GetItemValidator();

		$itemRequest = new GetItemRequest( $itemId );
		$itemResponse = ( new GetItem( $retriever, $serializer, $validator ) )->execute( $itemRequest );

		$this->assertInstanceOf( GetItemErrorResponse::class, $itemResponse );
		$this->assertSame( ErrorResponse::INVALID_ITEM_ID, $itemResponse->getCode() );
	}

	/**
	 * @dataProvider filterDataProvider
	 */
	public function testGetItemWithFilter( array $fields, array $expectedItem ): void {
		$lastModifiedTimestamp = '20201111070707';
		$revisionId = 42;

		$retriever = $this->createStub( ItemRevisionRetriever::class );
		$retriever->method( "getItemRevision" )->willReturn(
			ItemRevisionResult::concreteRevision( new ItemRevision(
				NewItem::withId( self::ITEM_ID )
					->andLabel( "en", self::ITEM_LABEL )
					->andDescription( "en", self::ITEM_DESCRIPTION )
					->build(),
				$lastModifiedTimestamp,
				$revisionId
			) )
		);
		$serializer = new ItemSerializer(
			WikibaseRepo::getBaseDataModelSerializerFactory()->newItemSerializer()
		);
		$validator = new GetItemValidator();

		$itemRequest = new GetItemRequest( self::ITEM_ID, $fields );
		$itemResponse = ( new GetItem( $retriever, $serializer, $validator ) )->execute( $itemRequest );
		$item = $itemResponse->getItem();

		$this->assertInstanceOf( GetItemSuccessResponse::class, $itemResponse );
		$this->assertEquals( $expectedItem, $item );
	}

	public function filterDataProvider(): \Generator {
		yield "labels only" => [
			[ "labels" ],
			[
				"id" => self::ITEM_ID,
				"labels" => [
					"en" => self::ITEM_LABEL
				]
			]
		];

		yield "type and labels" => [
			[ "type", "labels" ],
			[
				"id" => self::ITEM_ID,
				"type" => "item",
				"labels" => [
					"en" => self::ITEM_LABEL
				]
			]
		];

		yield "type, labels, and descriptions" => [
			[ "type", "labels", "descriptions" ],
			[
				"id" => self::ITEM_ID,
				"type" => "item",
				"labels" => [
					"en" => self::ITEM_LABEL
				],
				"descriptions" => [
					"en" => self::ITEM_DESCRIPTION
				],
			]
		];

		yield "type and descriptions" => [
			[ "type", "descriptions" ],
			[
				"id" => self::ITEM_ID,
				"type" => "item",
				"descriptions" => [
					"en" => self::ITEM_DESCRIPTION
				],
			]
		];
	}
}
