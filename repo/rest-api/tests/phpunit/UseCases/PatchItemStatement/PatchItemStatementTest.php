<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\PatchItemStatement;

use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityPermissionChecker;
use Wikibase\Repo\RestApi\Domain\Exceptions\InapplicablePatchException;
use Wikibase\Repo\RestApi\Domain\Exceptions\InvalidPatchedSerializationException;
use Wikibase\Repo\RestApi\Domain\Exceptions\InvalidPatchedStatementException;
use Wikibase\Repo\RestApi\Domain\Exceptions\PatchTestConditionFailedException;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\Model\ItemRevision;
use Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetadataResult;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\PermissionChecker;
use Wikibase\Repo\RestApi\Domain\Services\StatementPatcher;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatement;
use Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatementErrorResponse;
use Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatementRequest;
use Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatementSuccessResponse;
use Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatementValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;
use Wikibase\Repo\Tests\RestApi\Domain\Model\EditMetadataHelper;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchItemStatementTest extends TestCase {

	use EditMetadataHelper;

	/**
	 * @var MockObject|PatchItemStatementValidator
	 */
	private $validator;

	/**
	 * @var MockObject|ItemRetriever
	 */
	private $itemRetriever;

	/**
	 * @var MockObject|StatementPatcher
	 */
	private $statementPatcher;

	/**
	 * @var MockObject|ItemUpdater
	 */
	private $itemUpdater;

	/**
	 * @var MockObject|ItemRevisionMetadataRetriever
	 */
	private $revisionMetadataRetriever;

	/**
	 * @var MockObject|PermissionChecker
	 */
	private $permissionChecker;

	protected function setUp(): void {
		parent::setUp();

		$this->validator = $this->createStub( PatchItemStatementValidator::class );
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->statementPatcher = $this->createStub( StatementPatcher::class );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
		$this->revisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->permissionChecker = $this->createStub( PermissionChecker::class );
		$this->permissionChecker->method( 'canEdit' )->willReturn( true );
	}

	public function testPatchItemStatement_success(): void {
		$itemId = 'Q123';
		$statementId = $itemId . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		$oldStatementValue = "old statement value";
		$newStatementValue = "new statement value";
		$statement = NewStatement::someValueFor( 'P123' )
			->withGuid( $statementId )
			->withValue( $oldStatementValue )
			->build();

		$patchedStatement = NewStatement::someValueFor( 'P123' )
			->withGuid( $statementId )
			->withValue( $newStatementValue )
			->build();
		$item = NewItem::withId( $itemId )
			->andStatement( $statement )
			->build();
		$updatedItem = NewItem::withId( $itemId )
			->andStatement( $patchedStatement )
			->build();
		$postModificationRevisionId = 567;
		$modificationTimestamp = '20221111070707';
		$editTags = [ 'some', 'tags' ];
		$isBot = false;
		$comment = 'statement replaced by ' . __method__;

		$patch = $this->getValidValueReplacingPatch( $newStatementValue );

		$requestData = [
			'$statementId' => $statementId,
			'$patch' => $patch,
			'$editTags' => $editTags,
			'$isBot' => $isBot,
			'$comment' => $comment,
			'$username' => null,
			'$itemId' => $itemId
		];

		$request = $this->newUseCaseRequest( $requestData );

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever
			->method( 'getItem' )
			->with( $itemId )
			->willReturn( $item );

		$this->statementPatcher = $this->createStub( StatementPatcher::class );
		$this->statementPatcher
			->method( 'patch' )
			->with( $statement, $patch )
			->willReturn( $patchedStatement );

		$this->itemUpdater = $this->createStub( ItemUpdater::class );
		$this->itemUpdater
			->method( 'update' )
			->with( $item, $this->expectEquivalentMetadata( $editTags, $isBot, $comment, EditSummary::PATCH_ACTION ) )
			->willReturn( new ItemRevision( $updatedItem, $modificationTimestamp, $postModificationRevisionId ) );

		$this->revisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->revisionMetadataRetriever->method( 'getLatestRevisionMetadata' )->willReturn(
			LatestItemRevisionMetadataResult::concreteRevision( 456, '20221111070607' )
		);

		$response = $this->newUseCase()->execute( $request );

		$this->assertInstanceOf( PatchItemStatementSuccessResponse::class, $response );
		$this->assertEquals( $response->getStatement(), $patchedStatement );
		$this->assertSame( $response->getLastModified(), $modificationTimestamp );
		$this->assertSame( $response->getRevisionId(), $postModificationRevisionId );
	}

	public function testPatchItemStatement_requestValidationError(): void {
		$requestData = [
			'$statementId' => 'INVALID-STATEMENT-ID',
			'$patch' => [ 'INVALID-PATCH' ],
			'$editTags' => [],
			'$isBot' => false,
			'$comment' => null,
			'$username' => null,
			'$itemId' => null
		];

		$request = $this->newUseCaseRequest( $requestData );
		$this->validator = $this->createStub( PatchItemStatementValidator::class );
		$this->validator
			->method( 'validate' )
			->with( $request )
			->willReturn(
				new ValidationError( 'INVALID-ID', PatchItemStatementValidator::SOURCE_ITEM_ID )
			);

		$response = $this->newUseCase()->execute( $request );

		$this->assertInstanceOf( PatchItemStatementErrorResponse::class, $response );
		$this->assertSame( ErrorResponse::INVALID_ITEM_ID, $response->getCode() );
	}

	public function testRequestedItemNotFound_returnsItemNotFound(): void {
		$this->revisionMetadataRetriever = $this->newItemRevisionMetadataRetriever( LatestItemRevisionMetadataResult::itemNotFound() );

		$response = $this->newUseCase()->execute(
			$this->newUseCaseRequest( [
				'$itemId' => 'Q42',
				'$statementId' => 'Q42$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
				'$patch' => $this->getValidValueReplacingPatch(),
			] )
		);

		$this->assertInstanceOf( PatchItemStatementErrorResponse::class, $response );
		$this->assertSame( ErrorResponse::ITEM_NOT_FOUND, $response->getCode() );
	}

	public function testItemForStatementNotFound_returnsStatementNotFound(): void {
		$this->revisionMetadataRetriever = $this->newItemRevisionMetadataRetriever( LatestItemRevisionMetadataResult::itemNotFound() );

		$response = $this->newUseCase()->execute(
			$this->newUseCaseRequest( [
				'$statementId' => 'Q42$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
				'$patch' => $this->getValidValueReplacingPatch(),
			] )
		);

		$this->assertInstanceOf( PatchItemStatementErrorResponse::class, $response );
		$this->assertSame( ErrorResponse::STATEMENT_NOT_FOUND, $response->getCode() );
	}

	public function testItemForStatementIsRedirect_returnsStatementNotFound(): void {
		$this->revisionMetadataRetriever = $this->newItemRevisionMetadataRetriever(
			LatestItemRevisionMetadataResult::redirect( new ItemId( 'Q321' ) )
		);

		$response = $this->newUseCase()->execute(
			$this->newUseCaseRequest( [
				'$statementId' => 'Q42$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
				'$patch' => $this->getValidValueReplacingPatch(),
			] )
		);

		$this->assertInstanceOf( PatchItemStatementErrorResponse::class, $response );
		$this->assertSame( ErrorResponse::STATEMENT_NOT_FOUND, $response->getCode() );
	}

	public function testStatementIdMismatchingItemId_returnsStatementNotFound(): void {
		$this->revisionMetadataRetriever = $this->newRevisionMetadataRetrieverWithSomeConcreteRevision();

		$response = $this->newUseCase()->execute(
			$this->newUseCaseRequest( [
				'$itemId' => 'Q666',
				'$statementId' => 'Q42$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
				'$patch' => $this->getValidValueReplacingPatch(),
			] )
		);

		$this->assertInstanceOf( PatchItemStatementErrorResponse::class, $response );
		$this->assertSame( ErrorResponse::STATEMENT_NOT_FOUND, $response->getCode() );
	}

	public function testStatementNotFoundOnItem_returnsStatementNotFound(): void {
		$this->revisionMetadataRetriever = $this->newRevisionMetadataRetrieverWithSomeConcreteRevision();
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn( NewItem::withId( 'Q42' )->build() );

		$response = $this->newUseCase()->execute(
			$this->newUseCaseRequest( [
				'$statementId' => 'Q42$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
				'$patch' => $this->getValidValueReplacingPatch(),
			] )
		);

		$this->assertInstanceOf( PatchItemStatementErrorResponse::class, $response );
		$this->assertSame( ErrorResponse::STATEMENT_NOT_FOUND, $response->getCode() );
	}

	public function testRejectsPropertyIdChange(): void {
		$itemId = 'Q123';
		$guid = $itemId . '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		$originalStatement = NewStatement::noValueFor( 'P123' )->withGuid( $guid )->build();
		$patchedStatement = NewStatement::noValueFor( 'P321' )->withGuid( $guid )->build();
		$item = NewItem::withId( $itemId )->andStatement( $originalStatement )->build();

		$this->revisionMetadataRetriever = $this->newRevisionMetadataRetrieverWithSomeConcreteRevision();

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn( $item );

		$this->statementPatcher = $this->createStub( StatementPatcher::class );
		$this->statementPatcher->method( 'patch' )->willReturn( $patchedStatement );

		$response = $this->newUseCase()->execute(
			$this->newUseCaseRequest( [
				'$statementId' => $originalStatement->getGuid(),
				'$patch' => [ [ 'op' => 'replace', 'path' => '/mainsnak/property', 'value' => 'P321' ] ],
			] )
		);

		$this->assertInstanceOf( PatchItemStatementErrorResponse::class, $response );
		$this->assertSame( $response->getCode(), ErrorResponse::INVALID_OPERATION_CHANGED_PROPERTY );
	}

	public function testRejectsStatementIdChange(): void {
		$itemId = 'Q123';
		$originalGuid = $itemId . '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		$newGuid = $itemId . '$FFFFFFFF-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		$originalStatement = NewStatement::noValueFor( 'P123' )->withGuid( $originalGuid )->build();
		$patchedStatement = NewStatement::noValueFor( 'P123' )->withGuid( $newGuid )->build();
		$item = NewItem::withId( $itemId )->andStatement( $originalStatement )->build();

		$this->revisionMetadataRetriever = $this->newRevisionMetadataRetrieverWithSomeConcreteRevision();

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn( $item );

		$this->statementPatcher = $this->createStub( StatementPatcher::class );
		$this->statementPatcher->method( 'patch' )->willReturn( $patchedStatement );

		$response = $this->newUseCase()->execute(
			$this->newUseCaseRequest( [
				'$statementId' => $originalStatement->getGuid(),
				'$patch' => [ [ 'op' => 'replace', 'path' => '/id', 'value' => $newGuid ] ],
			] )
		);

		$this->assertInstanceOf( PatchItemStatementErrorResponse::class, $response );
		$this->assertSame( $response->getCode(), ErrorResponse::INVALID_OPERATION_CHANGED_STATEMENT_ID );
	}

	/**
	 * @dataProvider patchExceptionProvider
	 */
	public function testGivenPatcherThrows_returnsCorrespondingErrorResponse(
		\Exception $patcherException,
		string $expectedMessage,
		string $expectedErrorCode,
		array $expectedContext = null
	): void {
		$originalStatement = NewStatement::noValueFor( 'P123' )
			->withGuid( 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
			->build();
		$item = NewItem::withId( 'Q123' )->andStatement( $originalStatement )->build();

		$this->revisionMetadataRetriever = $this->newRevisionMetadataRetrieverWithSomeConcreteRevision();

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn( $item );

		$this->statementPatcher = $this->createStub( StatementPatcher::class );
		$this->statementPatcher->method( 'patch' )->willThrowException( $patcherException );

		$response = $this->newUseCase()->execute(
			$this->newUseCaseRequest( [
				'$statementId' => $originalStatement->getGuid(),
				'$patch' => $this->getValidValueReplacingPatch(),
			] )
		);

		$this->assertInstanceOf( PatchItemStatementErrorResponse::class, $response );
		$this->assertEquals( $expectedMessage, $response->getMessage() );
		$this->assertEquals( $expectedErrorCode, $response->getCode() );
		$this->assertEquals( $expectedContext, $response->getContext() );
	}

	public function patchExceptionProvider(): Generator {
		yield [
			new InvalidPatchedSerializationException(),
			'The patch results in an invalid statement which cannot be stored',
			'patched-statement-invalid'
		];
		yield [
			new InvalidPatchedStatementException(),
			'The patch results in an invalid statement which cannot be stored',
			'patched-statement-invalid'
		];
		yield [
			new InapplicablePatchException(),
			'The provided patch cannot be applied to the statement Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
			'cannot-apply-patch'
		];
		$testOperation = [
			'op' => 'test',
			'path' => '/mainsnak/snaktype',
			'value' => 'value',
		];
		yield [
			new PatchTestConditionFailedException(
				'message',
				$testOperation,
				[ 'key' => 'actualValue' ]
			),
			"Test operation in the provided patch failed. At path '${testOperation['path']}' " .
			"expected '" . json_encode( $testOperation[ 'value' ] ) . "', " .
			"actual: '" . json_encode( [ 'key' => 'actualValue' ] ) . "'",
			'patch-test-failed',
			[ "operation" => $testOperation, "actual-value" => [ 'key' => 'actualValue' ] ]

		];
	}

	public function testGivenProtectedItem_returnsErrorResponse(): void {
		$itemId = new ItemId( 'Q123' );
		$statementId = "$itemId\$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE";

		$this->permissionChecker = $this->createMock( WikibaseEntityPermissionChecker::class );
		$this->permissionChecker->expects( $this->once() )
			->method( 'canEdit' )
			->with( User::newAnonymous(), $itemId )
			->willReturn( false );

		$this->revisionMetadataRetriever = $this->newRevisionMetadataRetrieverWithSomeConcreteRevision();

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn(
			NewItem::withId( $itemId )
				->andStatement(
					NewStatement::forProperty( 'P123' )
						->withGuid( $statementId )
						->withValue( 'abc' )
						->build()
				)->build()
		);

		$response = $this->newUseCase()->execute(
			$this->newUseCaseRequest( [
				'$statementId' => $statementId,
				'$patch' => $this->getValidValueReplacingPatch(),
			] )
		);
		$this->assertInstanceOf( PatchItemStatementErrorResponse::class, $response );
		$this->assertSame( ErrorResponse::PERMISSION_DENIED, $response->getCode() );
	}

	private function newUseCase(): PatchItemStatement {
		return new PatchItemStatement(
			$this->validator,
			new StatementGuidParser( new ItemIdParser() ),
			$this->itemRetriever,
			$this->statementPatcher,
			$this->itemUpdater,
			$this->revisionMetadataRetriever,
			$this->permissionChecker
		);
	}

	private function newUseCaseRequest( array $requestData ): PatchItemStatementRequest {
		return new PatchItemStatementRequest(
			$requestData['$statementId'],
			$requestData['$patch'],
			$requestData['$editTags'] ?? [],
			$requestData['$isBot'] ?? false,
			$requestData['$comment'] ?? null,
			$requestData['$username'] ?? null,
			$requestData['$itemId'] ?? null
		);
	}

	private function newItemRevisionMetadataRetriever( LatestItemRevisionMetadataResult $result ): ItemRevisionMetadataRetriever {
		$metadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$metadataRetriever->method( 'getLatestRevisionMetadata' )->willReturn( $result );

		return $metadataRetriever;
	}

	private function getValidValueReplacingPatch( string $newStatementValue = '' ): array {
		return [
			[
				'op' => 'replace',
				'path' => '/mainsnak/datavalue/value',
				'value' => $newStatementValue
			],
		];
	}

	private function newRevisionMetadataRetrieverWithSomeConcreteRevision(): ItemRevisionMetadataRetriever {
		return $this->newItemRevisionMetadataRetriever(
			LatestItemRevisionMetadataResult::concreteRevision( 123, '20220708030405' )
		);
	}

}
