<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetItem;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\GetItem\GetItem;
use Wikibase\Repo\RestApi\Application\UseCases\GetItem\GetItemRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItem\GetItemResponse;
use Wikibase\Repo\RestApi\Application\UseCases\GetItem\GetItemValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemParts;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemPartsBuilder;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\ItemPartsRetriever;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItem\GetItem
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
		$requestedFields = [ ItemParts::FIELD_LABELS, ItemParts::FIELD_STATEMENTS ];
		$expectedItemParts = ( new ItemPartsBuilder( new ItemId( self::ITEM_ID ), $requestedFields ) )
			->setLabels( new Labels( new Label( 'en', self::ITEM_LABEL ) ) )
			->setStatements( new StatementList() )
			->build();

		$getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$getRevisionMetadata->method( 'execute' )
			->willReturn( [ $revisionId, $lastModifiedTimestamp ] );

		$itemPartsRetriever = $this->createMock( ItemPartsRetriever::class );
		$itemPartsRetriever->expects( $this->once() )
			->method( 'getItemParts' )
			->with( self::ITEM_ID, $requestedFields )
			->willReturn( $expectedItemParts );

		$itemResponse = ( new GetItem(
			$getRevisionMetadata,
			$itemPartsRetriever,
			new GetItemValidator( new ItemIdValidator() )
		) )->execute( new GetItemRequest( self::ITEM_ID, $requestedFields ) );

		$this->assertInstanceOf( GetItemResponse::class, $itemResponse );
		$this->assertSame( $expectedItemParts, $itemResponse->getItemParts() );
		$this->assertSame( $lastModifiedTimestamp, $itemResponse->getLastModified() );
		$this->assertSame( $revisionId, $itemResponse->getRevisionId() );
	}

	public function testGivenItemNotFoundOrRedirect_throws(): void {
		$itemId = 'Q123';
		$expectedException = $this->createStub( UseCaseException::class );

		$getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$getRevisionMetadata->method( 'execute' )
			->willThrowException( $expectedException );

		try {
			( new GetItem(
				$getRevisionMetadata,
				$this->createStub( ItemPartsRetriever::class ),
				new GetItemValidator( new ItemIdValidator() )
			) )->execute( new GetItemRequest( $itemId ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testInvalidItemId(): void {
		$itemId = 'X123';
		try {
			( new GetItem(
				$this->createStub( GetLatestItemRevisionMetadata::class ),
				$this->createStub( ItemPartsRetriever::class ),
				new GetItemValidator( new ItemIdValidator() )
			) )->execute( new GetItemRequest( $itemId ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_ITEM_ID, $e->getErrorCode() );
		}
	}

}
