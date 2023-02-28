<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\RemoveItemStatement;

use CommentStore;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityPermissionChecker;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetadataResult;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\PermissionChecker;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\RemoveItemStatement\RemoveItemStatement;
use Wikibase\Repo\RestApi\UseCases\RemoveItemStatement\RemoveItemStatementRequest;
use Wikibase\Repo\RestApi\UseCases\RemoveItemStatement\RemoveItemStatementValidator;
use Wikibase\Repo\RestApi\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementIdValidator;
use Wikibase\Repo\Tests\RestApi\Domain\Model\EditMetadataHelper;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\RemoveItemStatement\RemoveItemStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 *
 */
class RemoveItemStatementTest extends TestCase {

	use EditMetadataHelper;

	/**
	 * @var MockObject|ItemRevisionMetadataRetriever
	 */
	private $revisionMetadataRetriever;

	/**
	 * @var MockObject|ItemRetriever
	 */
	private $itemRetriever;

	/**
	 * @var MockObject|ItemUpdater
	 */
	private $itemUpdater;

	/**
	 * @var MockObject|PermissionChecker
	 */
	private $permissionChecker;

	private const ALLOWED_TAGS = [ 'some', 'tags', 'are', 'allowed' ];

	protected function setUp(): void {
		parent::setUp();

		$this->revisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
		$this->permissionChecker = $this->createStub( PermissionChecker::class );
		$this->permissionChecker->method( 'canEdit' )->willReturn( true );
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

		$this->revisionMetadataRetriever = $this->newItemMetadataRetriever(
			LatestItemRevisionMetadataResult::concreteRevision( 223, '20210809030405' )
		);
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
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( $requestData )
			);
		} catch ( UseCaseException $e ) {
			$this->assertSame( ErrorResponse::INVALID_STATEMENT_ID, $e->getErrorCode() );
			$this->assertSame( 'Not a valid statement ID: INVALID-STATEMENT-ID', $e->getErrorMessage() );
		}
	}

	public function testRequestedItemNotFound_throwsItemNotFound(): void {
		$this->revisionMetadataRetriever = $this->newItemMetadataRetriever( LatestItemRevisionMetadataResult::itemNotFound() );
		try {
		$this->newUseCase()->execute(
			$this->newUseCaseRequest( [
				'$itemId' => 'Q999999',
				'$statementId' => 'Q999999$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
			] )
		);
		} catch ( UseCaseException $e ) {
			$this->assertSame( ErrorResponse::ITEM_NOT_FOUND, $e->getErrorCode() );
			$this->assertSame( 'Could not find an item with the ID: Q999999', $e->getErrorMessage() );
		}
	}

	public function testItemForStatementNotFound_throwsStatementNotFound(): void {
		$this->revisionMetadataRetriever = $this->newItemMetadataRetriever( LatestItemRevisionMetadataResult::itemNotFound() );
		$statementId = 'Q42$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( [
					'$statementId' => $statementId,
				] )
			);
		} catch ( UseCaseException $e ) {
			$this->assertSame( ErrorResponse::STATEMENT_NOT_FOUND, $e->getErrorCode() );
			$this->assertSame( "Could not find a statement with the ID: $statementId", $e->getErrorMessage() );
		}
	}

	public function testItemForStatementIsRedirect_throws(): void {
		$this->revisionMetadataRetriever = $this->newItemMetadataRetriever(
			LatestItemRevisionMetadataResult::redirect( new ItemId( 'Q321' ) )
		);
		$statementId = 'Q42$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( [
					'$statementId' => $statementId,
				] )
			);
		} catch ( UseCaseException $e ) {
			$this->assertSame( ErrorResponse::STATEMENT_NOT_FOUND, $e->getErrorCode() );
			$this->assertSame( "Could not find a statement with the ID: $statementId", $e->getErrorMessage() );
		}
	}

	public function testStatementIdMismatchingItemId_throws(): void {
		$this->revisionMetadataRetriever = $this->newItemMetadataRetriever(
			LatestItemRevisionMetadataResult::concreteRevision( 123, '20220708030405' )
		);
		$statementId = 'Q42$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( [
					'$itemId' => 'Q666',
					'$statementId' => $statementId,
				] )
			);
		} catch ( UseCaseException $e ) {
			$this->assertSame( ErrorResponse::STATEMENT_NOT_FOUND, $e->getErrorCode() );
			$this->assertSame( "Could not find a statement with the ID: $statementId", $e->getErrorMessage() );
		}
	}

	public function testStatementNotFoundOnItem_throws(): void {
		$this->revisionMetadataRetriever = $this->newItemMetadataRetriever(
			LatestItemRevisionMetadataResult::concreteRevision( 123, '20220708030405' )
		);
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn( NewItem::withId( 'Q42' )->build() );
		$statementId = 'Q42$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( [
					'$statementId' => $statementId,
				] )
			);
		} catch ( UseCaseException $e ) {
			$this->assertSame( ErrorResponse::STATEMENT_NOT_FOUND, $e->getErrorCode() );
			$this->assertSame( "Could not find a statement with the ID: $statementId", $e->getErrorMessage() );
		}
	}

	public function testProtectedItem_throws(): void {
		$itemId = new ItemId( 'Q123' );

		$this->revisionMetadataRetriever = $this->newItemMetadataRetriever(
			LatestItemRevisionMetadataResult::concreteRevision( 321, '20201111070707' )
		);

		$this->permissionChecker = $this->createStub( WikibaseEntityPermissionChecker::class );
		$this->permissionChecker->expects( $this->once() )
			->method( 'canEdit' )
			->with( User::newAnonymous(), $itemId )
			->willReturn( false );

		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( [
					'$statementId' => "$itemId\$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE",
				] )
			);
		} catch ( UseCaseException $e ) {
			$this->assertSame( ErrorResponse::PERMISSION_DENIED, $e->getErrorCode() );
			$this->assertSame( 'You have no permission to edit this item.', $e->getErrorMessage() );
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
			$this->revisionMetadataRetriever,
			new StatementGuidParser( $itemIdParser ),
			$this->itemRetriever,
			$this->itemUpdater,
			$this->permissionChecker
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

	private function newItemMetadataRetriever( LatestItemRevisionMetadataResult $result ): ItemRevisionMetadataRetriever {
		$metadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$metadataRetriever->method( 'getLatestRevisionMetadata' )->willReturn( $result );

		return $metadataRetriever;
	}
}
