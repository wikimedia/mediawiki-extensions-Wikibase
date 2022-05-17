<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItem;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Model\ItemRevision;
use Wikibase\Repo\RestApi\Domain\Model\ItemRevisionResult;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionRetriever;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItem;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemErrorResponse;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemRequest;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemSuccessResponse;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemValidator;
use Wikibase\Repo\RestApi\UseCases\ItemRedirectResponse;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\Tests\NewItem;

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

	public function testGetExistingItem(): void {
		$lastModifiedTimestamp = '20201111070707';
		$revisionId = 42;

		$retriever = $this->createStub( ItemRevisionRetriever::class );
		$retriever->method( "getItemRevision" )->willReturn(
			ItemRevisionResult::concreteRevision( new ItemRevision(
				NewItem::withId( self::ITEM_ID )->andLabel( "en", self::ITEM_LABEL )->build(),
				$lastModifiedTimestamp,
				$revisionId
			) )
		);
		$itemRequest = new GetItemRequest( self::ITEM_ID );
		$itemResponse = ( new GetItem(
			$retriever,
			new GetItemValidator( new ItemIdValidator() )
		) )->execute( $itemRequest );
		$itemData = $itemResponse->getItemData();

		$this->assertInstanceOf( GetItemSuccessResponse::class, $itemResponse );
		$this->assertSame( self::ITEM_ID, $itemData->getId()->getSerialization() );
		$this->assertSame( self::ITEM_LABEL, $itemData->getLabels()->getByLanguage( 'en' )->getText() );
		$this->assertSame( $lastModifiedTimestamp, $itemResponse->getLastModified() );
		$this->assertSame( $revisionId, $itemResponse->getRevisionId() );
	}

	public function testItemNotFound(): void {
		$itemId = "Q123";

		$retriever = $this->createStub( ItemRevisionRetriever::class );
		$retriever->method( "getItemRevision" )
			->willReturn( ItemRevisionResult::itemNotFound() );
		$itemRequest = new GetItemRequest( $itemId );
		$itemResponse = ( new GetItem(
			$retriever,
			new GetItemValidator( new ItemIdValidator() )
		) )->execute( $itemRequest );
		$this->assertInstanceOf( GetItemErrorResponse::class, $itemResponse );
		$this->assertSame( ErrorResponse::ITEM_NOT_FOUND, $itemResponse->getCode() );
	}

	public function testInvalidItemId(): void {
		$itemId = "X123";

		$retriever = $this->createStub( ItemRevisionRetriever::class );
		$itemRequest = new GetItemRequest( $itemId );
		$itemResponse = ( new GetItem(
			$retriever,
			new GetItemValidator( new ItemIdValidator() )
		) )->execute( $itemRequest );

		$this->assertInstanceOf( GetItemErrorResponse::class, $itemResponse );
		$this->assertSame( ErrorResponse::INVALID_ITEM_ID, $itemResponse->getCode() );
	}

	public function testRedirect(): void {
		$redirectTarget = 'Q321';
		$request = new GetItemRequest( 'Q123' );

		$revisionRetriever = $this->createStub( ItemRevisionRetriever::class );
		$revisionRetriever->method( 'getItemRevision' )
			->willReturn( ItemRevisionResult::redirect( new ItemId( $redirectTarget ) ) );

		$response = ( new GetItem(
			$revisionRetriever,
			new GetItemValidator( new ItemIdValidator() )
		) )->execute( $request );

		$this->assertInstanceOf( ItemRedirectResponse::class, $response );
		$this->assertSame( $redirectTarget, $response->getRedirectTargetId() );
	}

}
