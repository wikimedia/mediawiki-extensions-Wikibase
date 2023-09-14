<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\RemoveStatement;

use CommentStore;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Application\UseCases\AssertStatementSubjectExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement\RemoveStatement;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement\RemoveStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement\RemoveStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\StatementRemover;
use Wikibase\Repo\RestApi\Domain\Services\StatementWriteModelRetriever;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\StatementSubjectRetriever;
use Wikibase\Repo\Tests\RestApi\Domain\Model\EditMetadataHelper;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\StatementReadModelHelper;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement\RemoveStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 *
 */
class RemoveStatementTest extends TestCase {

	use EditMetadataHelper;
	use StatementReadModelHelper;

	private AssertStatementSubjectExists $assertStatementSubjectExists;
	private StatementWriteModelRetriever $statementRetriever;
	private StatementRemover $statementRemover;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private StatementSubjectRetriever $statementSubjectRetriever;

	private const ALLOWED_TAGS = [ 'some', 'tags', 'are', 'allowed' ];

	protected function setUp(): void {
		parent::setUp();

		$this->assertStatementSubjectExists = $this->createStub( AssertStatementSubjectExists::class );
		$this->statementRetriever = $this->createStub( StatementWriteModelRetriever::class );
		$this->statementRemover = $this->createStub( StatementRemover::class );
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
		$this->statementSubjectRetriever = $this->createStub( StatementSubjectRetriever::class );
	}

	public function testRemoveStatement_success(): void {
		$statementGuid = new StatementGuid( new ItemId( 'Q123' ), 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$statement = NewStatement::forProperty( 'P123' )
			->withGuid( $statementGuid )
			->withValue( 'statement value' )
			->build();

		$requestData = [
			'$statementId' => (string)$statementGuid,
			'$editTags' => [ 'some', 'tags' ],
			'$isBot' => false,
			'$comment' => 'statement removed by ' . __method__,
			'$username' => null,
		];

		$this->statementRetriever->expects( $this->once() )
			->method( 'getStatementWriteModel' )
			->willReturn( $statement );

		$this->statementRemover = $this->createMock( StatementRemover::class );
		$this->statementRemover->expects( $this->once() )
			->method( 'remove' )
			->with(
				$statementGuid,
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
		];

		try {
			$this->newUseCase()->execute( $this->newUseCaseRequest( $requestData ) );

			$this->fail( 'Exception was not thrown.' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_STATEMENT_ID, $e->getErrorCode() );
			$this->assertSame( 'Not a valid statement ID: INVALID-STATEMENT-ID', $e->getErrorMessage() );
		}
	}

	public function testGivenStatementSubjectNotFoundOrRedirect_throwsUseCaseError(): void {
		$statementId = new StatementGuid( new ItemId( 'Q42' ), 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$expectedException = $this->createStub( UseCaseException::class );

		$this->assertStatementSubjectExists = $this->createMock( AssertStatementSubjectExists::class );
		$this->assertStatementSubjectExists->expects( $this->once() )
			->method( 'execute' )
			->with( $statementId )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( [
					'$statementId' => (string)$statementId,
				] )
			);
			$this->fail( 'this should not be reached' );
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
		$itemRetriever = $this->createMock( ItemRetriever::class );
		$itemRetriever->method( 'getItem' )->willReturn( NewItem::withId( 'Q42' )->build() );
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

	private function newUseCase(): RemoveStatement {
		$itemIdParser = new ItemIdParser();
		return new RemoveStatement(
			new RemoveStatementValidator(
				new StatementIdValidator( $itemIdParser ),
				new EditMetadataValidator( CommentStore::COMMENT_CHARACTER_LIMIT, self::ALLOWED_TAGS )
			),
			new StatementGuidParser( $itemIdParser ),
			$this->assertUserIsAuthorized,
			$this->assertStatementSubjectExists,
			$this->statementRetriever,
			$this->statementRemover
		);
	}

	private function newUseCaseRequest( array $requestData ): RemoveStatementRequest {
		return new RemoveStatementRequest(
			$requestData['$statementId'],
			$requestData['$editTags'] ?? [],
			$requestData['$isBot'] ?? false,
			$requestData['$comment'] ?? null,
			$requestData['$username'] ?? null
		);
	}

}
