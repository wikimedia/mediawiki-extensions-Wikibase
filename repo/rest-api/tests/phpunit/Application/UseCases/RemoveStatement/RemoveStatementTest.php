<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\RemoveStatement;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Application\UseCases\AssertStatementSubjectExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement\RemoveStatement;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement\RemoveStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\StatementEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\Services\StatementRemover;
use Wikibase\Repo\RestApi\Domain\Services\StatementWriteModelRetriever;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\InMemoryStatementRepository;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement\RemoveStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 *
 */
class RemoveStatementTest extends TestCase {

	private AssertStatementSubjectExists $assertStatementSubjectExists;
	private StatementWriteModelRetriever $statementRetriever;
	private StatementRemover $statementRemover;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	protected function setUp(): void {
		parent::setUp();

		$this->assertStatementSubjectExists = $this->createStub( AssertStatementSubjectExists::class );
		$this->statementRetriever = $this->createStub( StatementWriteModelRetriever::class );
		$this->statementRemover = $this->createStub( StatementRemover::class );
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
	}

	/**
	 * @dataProvider provideSubjectIds
	 */
	public function testRemoveStatement_success( EntityId $subjectId ): void {
		$statementGuid = new StatementGuid( $subjectId, 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$statement = NewStatement::forProperty( 'P123' )
			->withGuid( $statementGuid )
			->withValue( 'statement value' )
			->build();

		$requestData = [
			'$statementId' => (string)$statementGuid,
			'$editTags' => [ TestValidatingRequestDeserializer::ALLOWED_TAGS[0] ],
			'$isBot' => false,
			'$comment' => 'statement removed by ' . __method__,
			'$username' => null,
		];

		$statementsRepo = new InMemoryStatementRepository();
		$statementsRepo->addStatement( $statement );
		$this->statementRetriever = $statementsRepo;
		$this->statementRemover = $statementsRepo;

		$this->newUseCase()->execute( $this->newUseCaseRequest( $requestData ) );

		$this->assertNull( $statementsRepo->getStatementWriteModel( $statementGuid ) );
		$this->assertEquals(
			new EditMetadata(
				$requestData['$editTags'],
				$requestData['$isBot'],
				StatementEditSummary::newRemoveSummary( $requestData['$comment'], $statement )
			),
			$statementsRepo->getLatestRevisionEditMetadata( $statementGuid )
		);
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

	/**
	 * @dataProvider provideSubjectIds
	 */
	public function testGivenStatementSubjectNotFoundOrRedirect_throwsUseCaseError( EntityId $subjectId ): void {
		$statementId = new StatementGuid( $subjectId, 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
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

	/**
	 * @dataProvider provideSubjectIds
	 */
	public function testStatementNotFoundOnSubject_throwsUseCaseError( EntityId $subjectId ): void {
		$statementId = "$subjectId\$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE";

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

	/**
	 * @dataProvider provideSubjectIds
	 */
	public function testGivenProtectedStatementSubject_throwsUseCaseError( EntityId $subjectId ): void {
		$expectedError = new UseCaseError(
			UseCaseError::PERMISSION_DENIED,
			'You have no permission to edit this item.'
		);

		$this->assertUserIsAuthorized = $this->createMock( AssertUserIsAuthorized::class );
		$this->assertUserIsAuthorized->method( 'execute' )
			->with( $subjectId, User::newAnonymous() )
			->willThrowException( $expectedError );

		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( [
					'$statementId' => "$subjectId\$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE",
				] )
			);

			$this->fail( 'Exception was not thrown.' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	public function provideSubjectIds(): Generator {
		yield 'item id' => [ new ItemId( 'Q123' ) ];
		yield 'property id' => [ new NumericPropertyId( 'P123' ) ];
	}

	private function newUseCase(): RemoveStatement {
		return new RemoveStatement(
			new TestValidatingRequestDeserializer(),
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
