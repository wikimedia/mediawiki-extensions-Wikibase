<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItem;

use MediaWikiIntegrationTestCase;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemErrorResponse;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemRequest;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemSuccessResponse;
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

		$itemResponse = WbRestApi::getGetItem()
			->execute( new GetItemRequest( $item->getId()->getSerialization() ) );

		$this->assertInstanceOf( GetItemSuccessResponse::class, $itemResponse );
		$this->assertSame(
			$item->getId()->getSerialization(),
			$itemResponse->getItem()['id']
		);
		$this->assertSame(
			self::ITEM_LABEL,
			$itemResponse->getItem()['labels']['en']['value']
		);
	}

	public function testItemNotFound(): void {
		$itemResponse = WbRestApi::getGetItem()->execute( new GetItemRequest( 'Q99' ) );

		$this->assertInstanceOf( GetItemErrorResponse::class, $itemResponse );
		$this->assertSame( ErrorResponse::ITEM_NOT_FOUND, $itemResponse->getCode() );
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

		$itemResponse = WbRestApi::getGetItem()
			->execute( new GetItemRequest( $item->getId()->getSerialization(), $fields ) );

		$expectedItem[ "id" ] = $item->getId()->getSerialization();

		$this->assertInstanceOf( GetItemSuccessResponse::class, $itemResponse );
		$this->assertEquals( $expectedItem, $itemResponse->getItem() );
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
