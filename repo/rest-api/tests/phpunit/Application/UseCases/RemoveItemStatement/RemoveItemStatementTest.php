<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\RemoveItemStatement;

use CommentStore;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemStatement\RemoveItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemStatement\RemoveItemStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemStatement\RemoveItemStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\Tests\RestApi\Domain\Model\EditMetadataHelper;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\RemoveItemStatement\RemoveItemStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 *
 */
class RemoveItemStatementTest extends TestCase {

	use EditMetadataHelper;

	private GetLatestItemRevisionMetadata $getRevisionMetadata;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	private const ALLOWED_TAGS = [ 'some', 'tags', 'are', 'allowed' ];

	protected function setUp(): void {
		parent::setUp();

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ 223, '20210809030405' ] );
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
	}

	public function testRemoveStatement_success(): void {
		$itemId = 'Q123';
		$statementId = $itemId . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		$statement = NewStatement::forProperty( 'P123' )->withGuid( $statementId )->withValue( 'statement value' )->build();
		$item = NewItem::withId( $itemId )->andStatement( $statement )->build();

		$requestData = [
			'$statementId' => $statementId,
			'$editTags' => [ 'some', 'tags' ],
			'$isBot' => false,
			'$comment' => 'statement removed by ' . __method__,
			'$username' => null,
			'$itemId' => $itemId,
		];

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->expects( $this->once() )
			->method( 'getItem' )
			->willReturn( $item );

		$this->itemUpdater = $this->createMock( ItemUpdater::class );
		$this->itemUpdater->expects( $this->once() )
			->method( 'update' )
			->with(
				$this->callback( fn( Item $item ) => $item->getStatements()->isEmpty() ),
				$this->expectEquivalentMetadata(
					$requestData['$editTags'],
					$requestData['$isBot'],
					$requestData['$comment'],
					EditSummary::REMOVE_ACTION
				)
			);

		$this->newUseCase()->execute( $this->newUseCaseRequest( $requestData ) );
	}

	public function testRemoveStatement_invalidRequest(): void {
		$requestData = [
			'$statementId' => 'INVALID-STATEMENT-ID',
			'$editTags' => [],
			'$isBot' => false,
			'$comment' => null,
			'$username' => null,
			'$itemId' => null,
		];

		try {
			$this->newUseCase()->execute( $this->newUseCaseRequest( $requestData ) );

			$this->fail( 'Exception was not thrown.' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_STATEMENT_ID, $e->getErrorCode() );
			$this->assertSame( 'Not a valid statement ID: INVALID-STATEMENT-ID', $e->getErrorMessage() );
		}
	}

	public function testRequestedItemNotFoundOrRedirect_throws(): void {
		$itemId = new ItemId( 'Q999999' );
		$expectedException = $this->createStub( UseCaseException::class );
		$this->getRevisionMetadata = $this->createMock( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )
			->with( $itemId )
			->willThrowException( $expectedException );
		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( [
					'$itemId' => "$itemId",
					'$statementId' => 'Q199999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
				] )
			);

			$this->fail( 'Exception was not thrown.' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testItemSubjectForStatementNotFoundOrRedirect_throws(): void {
		$itemId = 'Q42';
		$expectedException = $this->createStub( UseCaseException::class );
		$this->getRevisionMetadata = $this->createMock( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )
			->with( $itemId )
			->willThrowException( $expectedException );
		$statementId = $itemId . '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( [
					'$statementId' => $statementId,
				] )
			);

			$this->fail( 'Exception was not thrown.' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testStatementIdMismatchingItemId_throws(): void {
		$statementId = 'Q42$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( [
					'$itemId' => 'Q666',
					'$statementId' => $statementId,
				] )
			);

			$this->fail( 'Exception was not thrown.' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::STATEMENT_NOT_FOUND, $e->getErrorCode() );
			$this->assertSame( "Could not find a statement with the ID: $statementId", $e->getErrorMessage() );
		}
	}

	public function testStatementNotFoundOnItem_throws(): void {
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn( NewItem::withId( 'Q42' )->build() );
		$statementId = 'Q42$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( [ '$statementId' => $statementId ] )
			);

			$this->fail( 'Exception was not thrown.' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::STATEMENT_NOT_FOUND, $e->getErrorCode() );
			$this->assertSame( "Could not find a statement with the ID: $statementId", $e->getErrorMessage() );
		}
	}

	public function testProtectedItem_throws(): void {
		$itemId = new ItemId( 'Q123' );

		$expectedError = new UseCaseError(
			UseCaseError::PERMISSION_DENIED,
			'You have no permission to edit this item.'
		);
		$this->assertUserIsAuthorized = $this->createMock( AssertUserIsAuthorized::class );
		$this->assertUserIsAuthorized->method( 'execute' )
			->with( $itemId, null )
			->willThrowException( $expectedError );

		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( [
					'$statementId' => "$itemId\$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE",
				] )
			);

			$this->fail( 'Exception was not thrown.' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	private function newUseCase(): RemoveItemStatement {
		$itemIdParser = new ItemIdParser();
		return new RemoveItemStatement(
			new RemoveItemStatementValidator(
				new ItemIdValidator(),
				new StatementIdValidator( $itemIdParser ),
				new EditMetadataValidator( CommentStore::COMMENT_CHARACTER_LIMIT, self::ALLOWED_TAGS )
			),
			new StatementGuidParser( $itemIdParser ),
			new AssertItemExists( $this->getRevisionMetadata ),
			$this->itemRetriever,
			$this->itemUpdater,
			$this->assertUserIsAuthorized
		);
	}

	private function newUseCaseRequest( array $requestData ): RemoveItemStatementRequest {
		return new RemoveItemStatementRequest(
			$requestData['$statementId'],
			$requestData['$editTags'] ?? [],
			$requestData['$isBot'] ?? false,
			$requestData['$comment'] ?? null,
			$requestData['$username'] ?? null,
			$requestData['$itemId'] ?? null
		);
	}

}
