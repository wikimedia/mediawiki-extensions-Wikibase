<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItem;

use MediaWikiIntegrationTestCase;
use Wikibase\Repo\RestApi\Domain\Serializers\ItemSerializer;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionRetriever;
use Wikibase\Repo\RestApi\UseCases\ErrorResult;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItem;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemErrorResult;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemRequest;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemSuccessResult;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemValidator;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikibase\Repo\Tests\NewItem;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\GetItem\GetItem
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class GetItemIntegrationTest extends MediaWikiIntegrationTestCase {

	private const ITEM_LABEL = "potato";
	private const ITEM_DESCRIPTION = "a root vegetable";

	public function testGetExistingItem(): void {
		$entityStore = WikibaseRepo::getEntityStore();

		$item = NewItem::withLabel( "en", self::ITEM_LABEL )->build();
		$entityStore->saveEntity( $item, self::class, self::getTestUser()->getUser(), EDIT_NEW );

		$itemResult = WbRestApi::getGetItem()
			->execute( new GetItemRequest( $item->getId()->getSerialization() ) );

		$this->assertInstanceOf( GetItemSuccessResult::class, $itemResult );
		$this->assertSame(
			$item->getId()->getSerialization(),
			$itemResult->getItem()['id']
		);
		$this->assertSame(
			self::ITEM_LABEL,
			$itemResult->getItem()['labels']['en']['value']
		);
	}

	public function testItemNotFound(): void {
		$itemResult = WbRestApi::getGetItem()->execute( new GetItemRequest( 'Q99' ) );

		$this->assertInstanceOf( GetItemErrorResult::class, $itemResult );
		$this->assertSame( ErrorResult::ITEM_NOT_FOUND, $itemResult->getCode() );
	}

	public function testUnexpectedError(): void {
		$retriever = $this->createStub( ItemRevisionRetriever::class );
		$retriever->method( "getItemRevision" )->willThrowException( new \RuntimeException() );
		$serializer = new ItemSerializer(
			WikibaseRepo::getBaseDataModelSerializerFactory()->newItemSerializer()
		);
		$validator = new GetItemValidator();

		$this->setService( 'WbRestApi.GetItem', new GetItem( $retriever, $serializer, $validator ) );

		$itemResult = WbRestApi::getGetItem()->execute( new GetItemRequest( "Q1" ) );

		$this->assertInstanceOf( GetItemErrorResult::class, $itemResult );
		$this->assertSame( ErrorResult::UNEXPECTED_ERROR, $itemResult->getCode() );
	}

	/**
	 * @dataProvider filterDataProvider
	 */
	public function testGetItemWithFilter( array $fields, array $expectedItem ): void {
		$entityStore = WikibaseRepo::getEntityStore();
		$item = NewItem::withLabel( "en", self::ITEM_LABEL )
			->andDescription( "en", self::ITEM_DESCRIPTION )
			->build();
		$entityStore->saveEntity( $item, self::class, self::getTestUser()->getUser(), EDIT_NEW );

		$itemResult = WbRestApi::getGetItem()
			->execute( new GetItemRequest( $item->getId()->getSerialization(), $fields ) );

		$expectedItem[ "id" ] = $item->getId()->getSerialization();

		$this->assertInstanceOf( GetItemSuccessResult::class, $itemResult );
		$this->assertEquals( $expectedItem, $itemResult->getItem() );
	}

	public function filterDataProvider(): \Generator {
		yield "labels only" => [
			[ "labels" ],
			[
				"labels" => [
					"en" => [ "language" => "en", "value" => self::ITEM_LABEL ]
				]
			]
		];

		yield "type and labels" => [
			[ "type", "labels" ],
			[
				"type" => "item",
				"labels" => [
					"en" => [ "language" => "en", "value" => self::ITEM_LABEL ]
				]
			]
		];

		yield "type, labels, and descriptions" => [
			[ "type", "labels", "descriptions" ],
			[
				"type" => "item",
				"labels" => [
					"en" => [ "language" => "en", "value" => self::ITEM_LABEL ]
				],
				"descriptions" => [
					"en" => [ "language" => "en", "value" => self::ITEM_DESCRIPTION ]
				],
			]
		];

		yield "type and descriptions" => [
			[ "type", "descriptions" ],
			[
				"descriptions" => [
					"en" => [ "language" => "en", "value" => self::ITEM_DESCRIPTION ]
				],
				"type" => "item",
			]
		];
	}
}
