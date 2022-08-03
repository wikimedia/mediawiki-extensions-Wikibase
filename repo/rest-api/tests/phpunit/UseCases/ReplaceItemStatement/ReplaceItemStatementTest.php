<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\ReplaceItemStatement;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\DataAccess\SnakValidatorStatementValidator;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\ItemRevision;
use Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetadataResult;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement\ReplaceItemStatement;
use Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement\ReplaceItemStatementErrorResponse;
use Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement\ReplaceItemStatementRequest;
use Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement\ReplaceItemStatementSuccessResponse;
use Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement\ReplaceItemStatementValidator;
use Wikibase\Repo\RestApi\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementIdValidator;
use Wikibase\Repo\Tests\NewItem;
use Wikibase\Repo\Tests\NewStatement;
use Wikibase\Repo\Validators\SnakValidator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement\ReplaceItemStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ReplaceItemStatementTest extends TestCase {

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

	private const ALLOWED_TAGS = [ 'some', 'tags', 'are', 'allowed' ];

	protected function setUp(): void {
		parent::setUp();

		$this->revisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
	}

	public function testReplaceStatement(): void {
		$itemId = 'Q123';
		$statementId = $itemId . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		$oldStatementValue = "old statement value";
		$newStatementValue = "new statement value";
		$oldStatement = NewStatement::someValueFor( 'P123' )
			->withGuid( $statementId )
			->withValue( $oldStatementValue )
			->build();
		$newStatement = NewStatement::someValueFor( 'P123' )
			->withGuid( $statementId )
			->withValue( $newStatementValue )
			->build();
		$item = NewItem::withId( $itemId )
			->andStatement( $oldStatement )
			->build();
		$updatedItem = NewItem::withId( $itemId )
			->andStatement( $newStatement )
			->build();
		$postModificationRevisionId = 322;
		$modificationTimestamp = '20221111070707';
		$editTags = [ 'some', 'tags' ];
		$isBot = false;
		$comment = 'statement replaced by ' . __method__;

		$requestData = [
			'$statementId' => $statementId,
			'$statement' => $this->getStatementSerialization( $newStatement ),
			'$editTags' => $editTags,
			'$isBot' => $isBot,
			'$comment' => $comment,
			'$username' => null,
			'$itemId' => $itemId
		];
		$request = $this->newUseCaseRequest( $requestData );

		$this->revisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->revisionMetadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestItemRevisionMetadataResult::concreteRevision( 321, '20201111070707' ) );

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn( $item );

		$this->itemUpdater = $this->createMock( ItemUpdater::class );
		$this->itemUpdater->method( 'update' )
			->with( $item, $this->equalTo( new EditMetadata( $editTags, $isBot, $comment ) ) )
			->willReturn( new ItemRevision( $updatedItem, $modificationTimestamp, $postModificationRevisionId ) );

		$useCase = $this->newUseCase();

		$response = $useCase->execute( $request );

		$this->assertInstanceOf( ReplaceItemStatementSuccessResponse::class, $response );
		$this->assertSame( $statementId, $response->getStatement()->getGuid() );
		$this->assertEquals( $newStatement, $response->getStatement() );
		$this->assertSame( $postModificationRevisionId, $response->getRevisionId() );
		$this->assertSame( $modificationTimestamp, $response->getLastModified() );
	}

	public function testInvalidStatementId_returnsInvalidStatementId(): void {
		$newStatement = NewStatement::noValueFor( 'P123' )->build();
		$requestData = [
			'$statementId' => 'INVALID-STATEMENT-ID',
			'$statement' => $this->getStatementSerialization( $newStatement ),
			'$editTags' => [],
			'$isBot' => false,
			'$comment' => null,
			'$username' => null,
			'$itemId' => null
		];

		$response = $this->newUseCase()->execute(
			$this->newUseCaseRequest( $requestData )
		);

		$this->assertInstanceOf( ReplaceItemStatementErrorResponse::class, $response );
		$this->assertSame( ErrorResponse::INVALID_STATEMENT_ID, $response->getCode() );
	}

	public function testRequestedItemNotFound_returnsItemNotFound(): void {
		$this->revisionMetadataRetriever = $this->newItemMetadataRetriever( LatestItemRevisionMetadataResult::itemNotFound() );

		$response = $this->newUseCase()->execute(
			$this->newUseCaseRequest( [
				'$itemId' => 'Q42',
				'$statementId' => 'Q42$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
				'$statement' => $this->getValidStatementSerialization(),
			] )
		);

		$this->assertInstanceOf( ReplaceItemStatementErrorResponse::class, $response );
		$this->assertSame( ErrorResponse::ITEM_NOT_FOUND, $response->getCode() );
	}

	public function testItemForStatementNotFound_returnsStatementNotFound(): void {
		$this->revisionMetadataRetriever = $this->newItemMetadataRetriever( LatestItemRevisionMetadataResult::itemNotFound() );

		$response = $this->newUseCase()->execute(
			$this->newUseCaseRequest( [
				'$statementId' => 'Q42$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
				'$statement' => $this->getValidStatementSerialization(),
			] )
		);

		$this->assertInstanceOf( ReplaceItemStatementErrorResponse::class, $response );
		$this->assertSame( ErrorResponse::STATEMENT_NOT_FOUND, $response->getCode() );
	}

	public function testItemForStatementIsRedirect_returnsStatementNotFound(): void {
		$this->revisionMetadataRetriever = $this->newItemMetadataRetriever(
			LatestItemRevisionMetadataResult::redirect( new ItemId( 'Q321' ) )
		);

		$response = $this->newUseCase()->execute(
			$this->newUseCaseRequest( [
				'$statementId' => 'Q42$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
				'$statement' => $this->getValidStatementSerialization(),
			] )
		);

		$this->assertInstanceOf( ReplaceItemStatementErrorResponse::class, $response );
		$this->assertSame( ErrorResponse::STATEMENT_NOT_FOUND, $response->getCode() );
	}

	public function testStatementIdMismatchingItemId_returnsStatementNotFound(): void {
		$this->revisionMetadataRetriever = $this->newItemMetadataRetriever(
			LatestItemRevisionMetadataResult::concreteRevision( 123, '20220708030405' )
		);

		$response = $this->newUseCase()->execute(
			$this->newUseCaseRequest( [
				'$itemId' => 'Q666',
				'$statementId' => 'Q42$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
				'$statement' => $this->getValidStatementSerialization(),
			] )
		);

		$this->assertInstanceOf( ReplaceItemStatementErrorResponse::class, $response );
		$this->assertSame( ErrorResponse::STATEMENT_NOT_FOUND, $response->getCode() );
	}

	public function testStatementNotFoundOnItem_returnsStatementNotFound(): void {
		$this->revisionMetadataRetriever = $this->newItemMetadataRetriever(
			LatestItemRevisionMetadataResult::concreteRevision( 123, '20220708030405' )
		);
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn( NewItem::withId( 'Q42' )->build() );

		$response = $this->newUseCase()->execute(
			$this->newUseCaseRequest( [
				'$statementId' => 'Q42$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
				'$statement' => $this->getValidStatementSerialization(),
			] )
		);

		$this->assertInstanceOf( ReplaceItemStatementErrorResponse::class, $response );
		$this->assertSame( ErrorResponse::STATEMENT_NOT_FOUND, $response->getCode() );
	}

	private function newUseCase(): ReplaceItemStatement {
		return new ReplaceItemStatement(
			$this->newValidator(),
			$this->revisionMetadataRetriever,
			$this->itemRetriever,
			$this->itemUpdater
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
		$snakValidator = $this->createStub( SnakValidator::class );
		$snakValidator->method( 'validateStatementSnaks' )->willReturn( Result::newSuccess() );

		$itemIdParser = new ItemIdParser();
		return new ReplaceItemStatementValidator(
			new ItemIdValidator(),
			new StatementIdValidator( $itemIdParser ),
			new SnakValidatorStatementValidator(
				WikibaseRepo::getBaseDataModelDeserializerFactory()->newStatementDeserializer(),
				$snakValidator
			),
			new EditMetadataValidator( \CommentStore::COMMENT_CHARACTER_LIMIT, self::ALLOWED_TAGS )
		);
	}

	private function getStatementSerialization( Statement $statement ): array {
		$serializer = WikibaseRepo::getBaseDataModelSerializerFactory()->newStatementSerializer();
		return $serializer->serialize( $statement );
	}

	private function getValidStatementSerialization(): array {
		return $this->getStatementSerialization( NewStatement::noValueFor( 'P666' )->build() );
	}

	private function newItemMetadataRetriever( LatestItemRevisionMetadataResult $result ): ItemRevisionMetadataRetriever {
		$metadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$metadataRetriever->method( 'getLatestRevisionMetadata' )->willReturn( $result );

		return $metadataRetriever;
	}

}
