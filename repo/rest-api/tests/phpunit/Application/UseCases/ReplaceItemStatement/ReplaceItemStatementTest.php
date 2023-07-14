<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\ReplaceItemStatement;

use CommentStore;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Application\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement\ReplaceItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement\ReplaceItemStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement\ReplaceItemStatementResponse;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement\ReplaceItemStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Item as ReadModelItem;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemRevision;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\Tests\RestApi\Application\Serialization\DeserializerFactory;
use Wikibase\Repo\Tests\RestApi\Domain\Model\EditMetadataHelper;
use Wikibase\Repo\Tests\RestApi\Domain\ReadModel\NewStatementReadModel;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement\ReplaceItemStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ReplaceItemStatementTest extends TestCase {

	use EditMetadataHelper;

	private GetLatestItemRevisionMetadata $getRevisionMetadata;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	private const ALLOWED_TAGS = [ 'some', 'tags', 'are', 'allowed' ];

	protected function setUp(): void {
		parent::setUp();

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ 321, '20201111070707' ] );
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
	}

	public function testReplaceStatement(): void {
		$itemId = 'Q123';
		$statementId = new StatementGuid( new ItemId( $itemId ), 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$oldStatement = NewStatement::noValueFor( 'P123' )->withGuid( $statementId )->build();
		$newStatementSerialization = [
			'id' => (string)$statementId,
			'property' => [ 'id' => 'P123' ],
			'value' => [
				'type' => 'value',
				'content' => 'new statement value',
			],
		];
		$item = NewItem::withId( $itemId )->andStatement( $oldStatement )->build();
		$modificationRevisionId = 322;
		$modificationTimestamp = '20221111070707';
		$editTags = [ 'some', 'tags' ];
		$isBot = false;
		$comment = 'statement replaced by ' . __method__;

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn( $item );

		$updatedItem = new ReadModelItem(
			new Labels(),
			new Descriptions(),
			new StatementList( NewStatementReadModel::someValueFor( 'P123' )->withGuid( $statementId )->build() )
		);
		$this->itemUpdater = $this->createMock( ItemUpdater::class );
		$this->itemUpdater->expects( $this->once() )
			->method( 'update' )
			->with(
				$this->callback(
					fn( Item $item ) => $item->getStatements()->getFirstStatementWithGuid( (string)$statementId )
						->equals( $this->newDeserializer()->deserialize( $newStatementSerialization ) )
				),
				$this->expectEquivalentMetadata( $editTags, $isBot, $comment, EditSummary::REPLACE_ACTION )
			)
			->willReturn( new ItemRevision( $updatedItem, $modificationTimestamp, $modificationRevisionId ) );

		$response = $this->newUseCase()->execute(
			$this->newUseCaseRequest( [
				'$statementId' => (string)$statementId,
				'$statement' => $newStatementSerialization,
				'$editTags' => $editTags,
				'$isBot' => $isBot,
				'$comment' => $comment,
				'$itemId' => $itemId,
			] )
		);

		$this->assertInstanceOf( ReplaceItemStatementResponse::class, $response );
		$this->assertSame(
			$updatedItem->getStatements()->getStatementById( $statementId ),
			$response->getStatement()
		);
		$this->assertSame( $modificationRevisionId, $response->getRevisionId() );
		$this->assertSame( $modificationTimestamp, $response->getLastModified() );
	}

	public function testRejectsStatementIdChange_throws(): void {
		$itemId = new ItemId( 'Q123' );
		$originalStatementId = new StatementGuid( $itemId, 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$originalStatement = NewStatement::noValueFor( 'P123' )
			->withGuid( (string)$originalStatementId )
			->build();
		$newStatementSerialization = [
			'id' => $itemId . '$LLLLLLL-MMMM-NNNN-OOOO-PPPPPPPPPPPP',
			'property' => [ 'id' => 'P123' ],
			'value' => [ 'type' => 'somevalue' ],
		];

		$item = NewItem::withId( $itemId )->andStatement( $originalStatement )->build();

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn( $item );

		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( [
					'$statementId' => (string)$originalStatementId,
					'$statement' => $newStatementSerialization,
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame(
				UseCaseError::INVALID_OPERATION_CHANGED_STATEMENT_ID,
				$e->getErrorCode()
			);
		}
	}

	public function testRejectsPropertyIdChange_throwsUseCaseError(): void {
		$itemId = new ItemId( 'Q123' );
		$statementId = new StatementGuid( $itemId, 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$originalStatement = NewStatement::noValueFor( 'P123' )
			->withGuid( (string)$statementId )
			->build();
		$newStatementSerialization = [
			'property' => [ 'id' => 'P321' ],
			'value' => [ 'type' => 'somevalue' ],
		];

		$item = NewItem::withId( $itemId )->andStatement( $originalStatement )->build();

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn( $item );

		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( [
					'$statementId' => (string)$statementId,
					'$statement' => $newStatementSerialization,
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame(
				UseCaseError::INVALID_OPERATION_CHANGED_PROPERTY,
				$e->getErrorCode()
			);
		}
	}

	public function testInvalidStatementId_throwsUseCaseError(): void {
		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( [
					'$statementId' => 'INVALID-STATEMENT-ID',
					'$statement' => [
						'property' => [ 'id' => 'P123' ],
						'value' => [ 'type' => 'novalue' ],
					],
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame(
				UseCaseError::INVALID_STATEMENT_ID,
				$e->getErrorCode()
			);
		}
	}

	public function testRequestedItemNotFoundOrRedirect_throws(): void {
		$expectedException = $this->createStub( UseCaseException::class );
		$itemId = new ItemId( 'Q42' );
		$this->getRevisionMetadata->method( 'execute' )
			->with( $itemId )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( [
					'$itemId' => "$itemId",
					'$statementId' => 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
					'$statement' => $this->getValidStatementSerialization(),
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testItemSubjectForStatementNotFoundOrRedirect_throws(): void {
		$expectedException = $this->createStub( UseCaseException::class );
		$itemId = new ItemId( 'Q42' );
		$this->getRevisionMetadata->method( 'execute' )
			->with( $itemId )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( [
					'$statementId' => "$itemId\$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE",
					'$statement' => $this->getValidStatementSerialization(),
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testStatementIdMismatchingItemId_throwsUseCaseError(): void {
		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( [
					'$itemId' => 'Q666',
					'$statementId' => 'Q42$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
					'$statement' => $this->getValidStatementSerialization(),
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame(
				UseCaseError::STATEMENT_NOT_FOUND,
				$e->getErrorCode()
			);
		}
	}

	public function testStatementNotFoundOnItem_throwsUseCaseError(): void {
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn( NewItem::withId( 'Q42' )->build() );

		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( [
					'$statementId' => 'Q42$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
					'$statement' => $this->getValidStatementSerialization(),
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame(
				UseCaseError::STATEMENT_NOT_FOUND,
				$e->getErrorCode()
			);
		}
	}

	public function testProtectedItem_throwsUseCaseError(): void {
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
					'$statement' => $this->getValidStatementSerialization(),
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	private function newUseCase(): ReplaceItemStatement {
		return new ReplaceItemStatement(
			$this->newValidator(),
			new AssertItemExists( $this->getRevisionMetadata ),
			$this->itemRetriever,
			$this->itemUpdater,
			$this->assertUserIsAuthorized
		);
	}

	private function newUseCaseRequest( array $requestData ): ReplaceItemStatementRequest {
		return new ReplaceItemStatementRequest(
			$requestData['$statementId'],
			$requestData['$statement'],
			$requestData['$editTags'] ?? [],
			$requestData['$isBot'] ?? false,
			$requestData['$comment'] ?? null,
			$requestData['$username'] ?? null,
			$requestData['$itemId'] ?? null
		);
	}

	private function newValidator(): ReplaceItemStatementValidator {
		return new ReplaceItemStatementValidator(
			new ItemIdValidator(),
			new StatementIdValidator( new ItemIdParser() ),
			new StatementValidator( $this->newDeserializer() ),
			new EditMetadataValidator( CommentStore::COMMENT_CHARACTER_LIMIT, self::ALLOWED_TAGS )
		);
	}

	private function newDeserializer(): StatementDeserializer {
		$propertyDataTypeLookup = $this->createStub( PropertyDataTypeLookup::class );
		$propertyDataTypeLookup->method( 'getDataTypeIdForProperty' )->willReturn( 'string' );

		return DeserializerFactory::newStatementDeserializer( $propertyDataTypeLookup );
	}

	private function getValidStatementSerialization(): array {
		return [
			'property' => [ 'id' => 'P666' ],
			'value' => [ 'type' => 'novalue' ],
		];
	}

}
