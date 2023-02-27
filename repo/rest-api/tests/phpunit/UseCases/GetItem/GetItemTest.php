<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItem;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetadataResult;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemData;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemDataBuilder;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\ItemDataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItem;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemRequest;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemResponse;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemValidator;
use Wikibase\Repo\RestApi\UseCases\ItemRedirectException;
use Wikibase\Repo\RestApi\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\GetItem\GetItem
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemTest extends TestCase {

	private const ITEM_ID = 'Q123';
	private const ITEM_LABEL = 'potato';

	public function testGetExistingItem(): void {
		$lastModifiedTimestamp = '20201111070707';
		$revisionId = 42;
		$requestedFields = [ ItemData::FIELD_LABELS, ItemData::FIELD_STATEMENTS ];
		$expectedItemData = ( new ItemDataBuilder( new ItemId( self::ITEM_ID ), $requestedFields ) )
			->setLabels( new Labels( new Label( 'en', self::ITEM_LABEL ) ) )
			->setStatements( new StatementList() )
			->build();

		$revisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$revisionMetadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestItemRevisionMetadataResult::concreteRevision( $revisionId, $lastModifiedTimestamp ) );

		$itemDataRetriever = $this->createMock( ItemDataRetriever::class );
		$itemDataRetriever->expects( $this->once() )
			->method( 'getItemData' )
			->with( self::ITEM_ID, $requestedFields )
			->willReturn( $expectedItemData );

		$itemResponse = ( new GetItem(
			$revisionMetadataRetriever,
			$itemDataRetriever,
			new GetItemValidator( new ItemIdValidator() )
		) )->execute( new GetItemRequest( self::ITEM_ID, $requestedFields ) );

		$this->assertInstanceOf( GetItemResponse::class, $itemResponse );
		$this->assertSame( $expectedItemData, $itemResponse->getItemData() );
		$this->assertSame( $lastModifiedTimestamp, $itemResponse->getLastModified() );
		$this->assertSame( $revisionId, $itemResponse->getRevisionId() );
	}

	public function testItemNotFound_throws(): void {
		$itemId = 'Q123';

		$revisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$revisionMetadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestItemRevisionMetadataResult::itemNotFound() );

		try {
			( new GetItem(
				$revisionMetadataRetriever,
				$this->createStub( ItemDataRetriever::class ),
				new GetItemValidator( new ItemIdValidator() )
			) )->execute( new GetItemRequest( $itemId ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( ErrorResponse::ITEM_NOT_FOUND, $e->getErrorCode() );
		}
	}

	public function testInvalidItemId(): void {
		$itemId = 'X123';
		try {
			( new GetItem(
				$this->createStub( ItemRevisionMetadataRetriever::class ),
				$this->createStub( ItemDataRetriever::class ),
				new GetItemValidator( new ItemIdValidator() )
			) )->execute( new GetItemRequest( $itemId ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( ErrorResponse::INVALID_ITEM_ID, $e->getErrorCode() );
		}
	}

	public function testRedirect(): void {
		$redirectTarget = 'Q321';
		$request = new GetItemRequest( 'Q123' );

		$revisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$revisionMetadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestItemRevisionMetadataResult::redirect( new ItemId( $redirectTarget ) ) );

		try {
			( new GetItem(
				$revisionMetadataRetriever,
				$this->createStub( ItemDataRetriever::class ),
				new GetItemValidator( new ItemIdValidator() )
			) )->execute( $request );

			$this->fail( 'this should not be reached' );
		} catch ( ItemRedirectException $e ) {
			$this->assertSame( $redirectTarget, $e->getRedirectTargetId() );
		}
	}

}
