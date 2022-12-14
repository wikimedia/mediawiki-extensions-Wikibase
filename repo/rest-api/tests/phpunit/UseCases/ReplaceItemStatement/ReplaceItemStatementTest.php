<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\ReplaceItemStatement;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityPermissionChecker;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\Model\ItemRevision;
use Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetadataResult;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\PermissionChecker;
use Wikibase\Repo\RestApi\Infrastructure\DataTypeFactoryValueTypeLookup;
use Wikibase\Repo\RestApi\Infrastructure\DataTypeValidatorFactoryDataValueValidator;
use Wikibase\Repo\RestApi\Serialization\PropertyValuePairDeserializer;
use Wikibase\Repo\RestApi\Serialization\PropertyValuePairSerializer;
use Wikibase\Repo\RestApi\Serialization\ReferenceDeserializer;
use Wikibase\Repo\RestApi\Serialization\ReferenceSerializer;
use Wikibase\Repo\RestApi\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\Serialization\StatementSerializer;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement\ReplaceItemStatement;
use Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement\ReplaceItemStatementErrorResponse;
use Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement\ReplaceItemStatementRequest;
use Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement\ReplaceItemStatementSuccessResponse;
use Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement\ReplaceItemStatementValidator;
use Wikibase\Repo\RestApi\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementValidator;
use Wikibase\Repo\Tests\RestApi\Domain\Model\EditMetadataHelper;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement\ReplaceItemStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ReplaceItemStatementTest extends TestCase {

	use EditMetadataHelper;

	/**
	 * @var MockObject|PropertyDataTypeLookup
	 */
	private $propertyDataTypeLookup;
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

		$this->propertyDataTypeLookup = $this->createStub( PropertyDataTypeLookup::class );
		$this->propertyDataTypeLookup->method( 'getDataTypeIdForProperty' )->willReturn( 'string' );

		$this->revisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
		$this->permissionChecker = $this->createStub( PermissionChecker::class );
		$this->permissionChecker->method( 'canEdit' )->willReturn( true );
	}

	public function testReplaceStatement(): void {
		$itemId = 'Q123';
		$statementId = $itemId . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		$oldStatement = NewStatement::noValueFor( 'P123' )->withGuid( $statementId )->build();
		$newStatement = NewStatement::forProperty( 'P123' )
			->withGuid( $statementId )
			->withValue( 'new statement value' )
			->build();
		$item = NewItem::withId( $itemId )->andStatement( $oldStatement )->build();
		$modificationRevisionId = 322;
		$modificationTimestamp = '20221111070707';
		$editTags = [ 'some', 'tags' ];
		$isBot = false;
		$comment = 'statement replaced by ' . __method__;

		$this->revisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->revisionMetadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestItemRevisionMetadataResult::concreteRevision( 321, '20201111070707' ) );

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn( $item );

		$this->itemUpdater = $this->createMock( ItemUpdater::class );
		$this->itemUpdater->method( 'update' )
			->with( $item, $this->expectEquivalentMetadata( $editTags, $isBot, $comment, EditSummary::REPLACE_ACTION ) )
			->willReturn( new ItemRevision( $item, $modificationTimestamp, $modificationRevisionId ) );

		$response = $this->newUseCase()->execute(
			$this->newUseCaseRequest( [
				'$statementId' => $statementId,
				'$statement' => $this->getStatementSerialization( $newStatement ),
				'$editTags' => $editTags,
				'$isBot' => $isBot,
				'$comment' => $comment,
				'$itemId' => $itemId
			] )
		);

		$this->assertInstanceOf( ReplaceItemStatementSuccessResponse::class, $response );
		$this->assertSame( $statementId, $response->getStatement()->getGuid() );
		$this->assertEquals( $newStatement, $response->getStatement() );
		$this->assertSame( $modificationRevisionId, $response->getRevisionId() );
		$this->assertSame( $modificationTimestamp, $response->getLastModified() );
	}

	public function testRejectsStatementIdChange(): void {
		$itemId = new ItemId( 'Q123' );
		$originalStatementId = new StatementGuid( $itemId, 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$originalStatement = NewStatement::noValueFor( 'P123' )
			->withGuid( (string)$originalStatementId )
			->build();
		$newStatement = NewStatement::someValueFor( 'P123' )
			->withGuid( $itemId . '$LLLLLLL-MMMM-NNNN-OOOO-PPPPPPPPPPPP' )
			->build();

		$item = NewItem::withId( $itemId )->andStatement( $originalStatement )->build();

		$this->revisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->revisionMetadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestItemRevisionMetadataResult::concreteRevision( 321, '20201111070707' ) );

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn( $item );

		$response = $this->newUseCase()->execute(
			$this->newUseCaseRequest( [
				'$statementId' => (string)$originalStatementId,
				'$statement' => $this->getStatementSerialization( $newStatement ),
			] )
		);

		$this->assertInstanceOf( ReplaceItemStatementErrorResponse::class, $response );
		$this->assertSame(
			ReplaceItemStatementErrorResponse::INVALID_OPERATION_CHANGED_STATEMENT_ID,
			$response->getCode()
		);
	}

	public function testRejectsPropertyIdChange(): void {
		$itemId = new ItemId( 'Q123' );
		$statementId = new StatementGuid( $itemId, 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$originalStatement = NewStatement::noValueFor( 'P123' )
			->withGuid( (string)$statementId )
			->build();
		$newStatement = NewStatement::noValueFor( 'P321' )->build();

		$item = NewItem::withId( $itemId )->andStatement( $originalStatement )->build();

		$this->revisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->revisionMetadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestItemRevisionMetadataResult::concreteRevision( 321, '20201111070707' ) );

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn( $item );

		$response = $this->newUseCase()->execute(
			$this->newUseCaseRequest( [
				'$statementId' => (string)$statementId,
				'$statement' => $this->getStatementSerialization( $newStatement ),
			] )
		);

		$this->assertInstanceOf( ReplaceItemStatementErrorResponse::class, $response );
		$this->assertSame(
			ReplaceItemStatementErrorResponse::INVALID_OPERATION_CHANGED_PROPERTY,
			$response->getCode()
		);
	}

	public function testInvalidStatementId_returnsInvalidStatementId(): void {
		$newStatement = NewStatement::noValueFor( 'P123' )->build();
		$response = $this->newUseCase()->execute(
			$this->newUseCaseRequest( [
				'$statementId' => 'INVALID-STATEMENT-ID',
				'$statement' => $this->getStatementSerialization( $newStatement )
			] )
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

	public function testProtectedItem_returnsErrorResponse(): void {
		$itemId = new ItemId( 'Q123' );
		$this->revisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->revisionMetadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestItemRevisionMetadataResult::concreteRevision( 321, '20201111070707' ) );

		$this->permissionChecker = $this->createMock( WikibaseEntityPermissionChecker::class );
		$this->permissionChecker->expects( $this->once() )
			->method( 'canEdit' )
			->with( User::newAnonymous(), $itemId )
			->willReturn( false );

		$response = $this->newUseCase()->execute(
			$this->newUseCaseRequest( [
				'$statementId' => "$itemId\$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE",
				'$statement' => $this->getValidStatementSerialization(),
			] )
		);
		$this->assertInstanceOf( ReplaceItemStatementErrorResponse::class, $response );
		$this->assertSame( ErrorResponse::PERMISSION_DENIED, $response->getCode() );
	}

	private function newUseCase(): ReplaceItemStatement {
		return new ReplaceItemStatement(
			$this->newValidator(),
			$this->revisionMetadataRetriever,
			$this->itemRetriever,
			$this->itemUpdater,
			$this->permissionChecker
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
		$propertyValuePairDeserializer = new PropertyValuePairDeserializer(
			WikibaseRepo::getEntityIdParser(),
			$this->propertyDataTypeLookup,
			new DataTypeFactoryValueTypeLookup( WikibaseRepo::getDataTypeFactory() ),
			WikibaseRepo::getDataValueDeserializer(),
			new DataTypeValidatorFactoryDataValueValidator(
				WikibaseRepo::getDataTypeValidatorFactory()
			)
		);

		$statementDeserializer = new StatementDeserializer(
			$propertyValuePairDeserializer,
			new ReferenceDeserializer( $propertyValuePairDeserializer )
		);

		return new ReplaceItemStatementValidator(
			new ItemIdValidator(),
			new StatementIdValidator( new ItemIdParser() ),
			new StatementValidator( $statementDeserializer ),
			new EditMetadataValidator( \CommentStore::COMMENT_CHARACTER_LIMIT, self::ALLOWED_TAGS )
		);
	}

	private function getStatementSerialization( Statement $statement ): array {
		$propertyValuePairSerializer = new PropertyValuePairSerializer(
			$this->propertyDataTypeLookup
		);
		$statementSerializer = new StatementSerializer(
			$propertyValuePairSerializer,
			new ReferenceSerializer( $propertyValuePairSerializer )
		);
		return $statementSerializer->serialize( $statement );
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
