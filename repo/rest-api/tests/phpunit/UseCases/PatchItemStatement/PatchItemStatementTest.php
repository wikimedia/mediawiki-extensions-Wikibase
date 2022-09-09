<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\PatchItemStatement;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\ItemRevision;
use Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetadataResult;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\StatementPatcher;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatement;
use Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatementErrorResponse;
use Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatementRequest;
use Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatementSuccessResponse;
use Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatementValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchItemStatementTest extends TestCase {

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

	protected function setUp(): void {
		parent::setUp();

		$this->validator = $this->createStub( PatchItemStatementValidator::class );
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->statementPatcher = $this->createStub( StatementPatcher::class );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
		$this->revisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
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
			->with( $item, new EditMetadata( $editTags, $isBot, $comment ) )
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
		$this->revisionMetadataRetriever = $this->newItemRevisionMetadataRetriever(
			LatestItemRevisionMetadataResult::concreteRevision( 123, '20220708030405' )
		);

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
		$this->revisionMetadataRetriever = $this->newItemRevisionMetadataRetriever(
			LatestItemRevisionMetadataResult::concreteRevision( 123, '20220708030405' )
		);
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

	private function newUseCase(): PatchItemStatement {
			return new PatchItemStatement(
				$this->validator,
				new StatementGuidParser( new ItemIdParser() ),
				$this->itemRetriever,
				$this->statementPatcher,
				$this->itemUpdater,
				$this->revisionMetadataRetriever
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

}
