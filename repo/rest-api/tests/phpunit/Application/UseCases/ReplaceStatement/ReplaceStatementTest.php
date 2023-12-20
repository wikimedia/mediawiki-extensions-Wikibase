<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\ReplaceStatement;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Exception\PropertyChangedException;
use Wikibase\DataModel\Exception\StatementNotFoundException;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Application\UseCases\AssertStatementSubjectExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatement;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatementResponse;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\StatementEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\Services\StatementUpdater;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Domain\ReadModel\NewStatementReadModel;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\InMemoryStatementRepository;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ReplaceStatementTest extends TestCase {

	private ReplaceStatementValidator $replaceStatementValidator;
	private AssertStatementSubjectExists $assertStatementSubjectExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private StatementUpdater $statementUpdater;

	protected function setUp(): void {
		parent::setUp();

		$this->replaceStatementValidator = new TestValidatingRequestDeserializer();
		$this->assertStatementSubjectExists = $this->createStub( AssertStatementSubjectExists::class );
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
		$this->statementUpdater = $this->createStub( StatementUpdater::class );
	}

	/**
	 * @dataProvider provideSubjectId
	 */
	public function testReplaceStatement( EntityId $subjectId ): void {
		$statementId = new StatementGuid( $subjectId, 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$newStatementSerialization = [
			'id' => (string)$statementId,
			'property' => [ 'id' => TestValidatingRequestDeserializer::EXISTING_STRING_PROPERTY ],
			'value' => [
				'type' => 'somevalue',
			],
		];
		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'statement replaced by ' . __method__;

		[ $expectedStatementReadModel, $expectedStatementWriteModel ] = NewStatementReadModel::someValueFor( 'P123' )
			->withGuid( $statementId )
			->buildReadAndWriteModel();

		$statementsRepo = new InMemoryStatementRepository();
		$statementsRepo->addStatement( NewStatement::noValueFor( 'P123' )->withGuid( $statementId )->build() );
		$this->statementUpdater = $statementsRepo;

		$response = $this->newUseCase()->execute(
			$this->newUseCaseRequest( [
				'$statementId' => (string)$statementId,
				'$statement' => $newStatementSerialization,
				'$editTags' => $editTags,
				'$isBot' => $isBot,
				'$comment' => $comment,
			] )
		);

		$this->assertInstanceOf( ReplaceStatementResponse::class, $response );
		$this->assertEquals( $expectedStatementReadModel, $response->getStatement() );
		$this->assertSame( $statementsRepo->getLatestRevisionId( $statementId ), $response->getRevisionId() );
		$this->assertSame( $statementsRepo->getLatestRevisionTimestamp( $statementId ), $response->getLastModified() );
		$this->assertEquals(
			new EditMetadata( $editTags, $isBot, StatementEditSummary::newReplaceSummary(
				$comment,
				$expectedStatementWriteModel
			) ),
			$statementsRepo->getLatestRevisionEditMetadata( $statementId )
		);
	}

	public function testGivenInvalidUseCaseRequest_throwsUseCaseError(): void {
		$expectedUseCaseRequest = $this->createStub( ReplaceStatementRequest::class );
		$expectedUseCaseError = $this->createStub( UseCaseError::class );

		$this->replaceStatementValidator = $this->createMock( ReplaceStatementValidator::class );
		$this->replaceStatementValidator->expects( $this->once() )
			->method( 'validateAndDeserialize' )
			->with( $expectedUseCaseRequest )
			->willThrowException( $expectedUseCaseError );

		try {
			$this->newUseCase()->execute( $expectedUseCaseRequest );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedUseCaseError, $e );
		}
	}

	/**
	 * @dataProvider provideSubjectId
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
					'$statement' => $this->getValidStatementSerialization(),
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	/**
	 * @dataProvider provideSubjectId
	 */
	public function testGivenProtectedStatementSubject_throwsUseCaseError( EntityId $subjectId ): void {
		$expectedError = $this->createStub( UseCaseError::class );
		$this->assertUserIsAuthorized = $this->createMock( AssertUserIsAuthorized::class );
		$this->assertUserIsAuthorized->expects( $this->once() )
			->method( 'execute' )
			->with( $subjectId, User::newAnonymous() )
			->willThrowException( $expectedError );

		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( [
					'$statementId' => "$subjectId\$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE",
					'$statement' => $this->getValidStatementSerialization(),
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	/**
	 * @dataProvider provideSubjectId
	 */
	public function testGivenStatementIdChangedInSerialization_throwsUseCaseError( EntityId $subjectId ): void {
		$originalStatementId = "$subjectId\$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE";
		$changedStatementID = "$subjectId\$LLLLLLL-MMMM-NNNN-OOOO-PPPPPPPPPPPP";

		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( [
					'$statementId' => $originalStatementId,
					'$statement' => [
						'id' => "$changedStatementID",
						'property' => [ 'id' => TestValidatingRequestDeserializer::EXISTING_STRING_PROPERTY ],
						'value' => [ 'type' => 'novalue' ],
					],
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_OPERATION_CHANGED_STATEMENT_ID, $e->getErrorCode() );
		}
	}

	/**
	 * @dataProvider provideSubjectId
	 */
	public function testStatementNotFoundOnSubject_throwsUseCaseError( EntityId $subjectId ): void {
		$statementId = new StatementGuid( $subjectId, 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$newStatementWriteModel = NewStatement::someValueFor(
			TestValidatingRequestDeserializer::EXISTING_STRING_PROPERTY
		)->withGuid( $statementId )->build();
		$statementSerialization = [
			'id' => "$statementId",
			'property' => [ 'id' => TestValidatingRequestDeserializer::EXISTING_STRING_PROPERTY ],
			'value' => [ 'type' => 'somevalue' ],
		];

		$this->statementUpdater = $this->createMock( StatementUpdater::class );
		$this->statementUpdater->expects( $this->once() )
			->method( 'update' )
			->with( $newStatementWriteModel, $this->isInstanceOf( EditMetadata::class ) )
			->willThrowException( new StatementNotFoundException() );

		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( [
					'$statementId' => (string)$statementId,
					'$statement' => $statementSerialization,
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::STATEMENT_NOT_FOUND, $e->getErrorCode() );
		}
	}

	/**
	 * @dataProvider provideSubjectId
	 */
	public function testGivenPropertyChanged_throwsUseCaseError( EntityId $subjectId ): void {
		$this->statementUpdater = $this->createStub( StatementUpdater::class );
		$this->statementUpdater->method( 'update' )->willThrowException( new PropertyChangedException() );

		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( [
					'$statementId' => "$subjectId\$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE",
					'$statement' => $this->getValidStatementSerialization(),
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_OPERATION_CHANGED_PROPERTY, $e->getErrorCode() );
		}
	}

	public function provideSubjectId(): Generator {
		yield 'item id' => [ new ItemId( 'Q123' ) ];
		yield 'property id' => [ new NumericPropertyId( 'P123' ) ];
	}

	private function newUseCase(): ReplaceStatement {
		return new ReplaceStatement(
			$this->replaceStatementValidator,
			$this->assertStatementSubjectExists,
			$this->assertUserIsAuthorized,
			$this->statementUpdater,
		);
	}

	private function newUseCaseRequest( array $requestData ): ReplaceStatementRequest {
		return new ReplaceStatementRequest(
			$requestData['$statementId'],
			$requestData['$statement'],
			$requestData['$editTags'] ?? [],
			$requestData['$isBot'] ?? false,
			$requestData['$comment'] ?? null,
			$requestData['$username'] ?? null
		);
	}

	private function getValidStatementSerialization(): array {
		return [
			'property' => [ 'id' => TestValidatingRequestDeserializer::EXISTING_STRING_PROPERTY ],
			'value' => [ 'type' => 'novalue' ],
		];
	}

}
