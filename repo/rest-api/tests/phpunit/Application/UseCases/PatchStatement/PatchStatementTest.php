<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchStatement;

use Exception;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Exception\PropertyChangedException;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyValuePairSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\ReferenceSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementSerializer;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ValidatingRequestDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\AssertStatementSubjectExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestStatementSubjectRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchedStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatement;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatementResponse;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementRevision;
use Wikibase\Repo\RestApi\Domain\Services\StatementReadModelConverter;
use Wikibase\Repo\RestApi\Domain\Services\StatementRetriever;
use Wikibase\Repo\RestApi\Domain\Services\StatementUpdater;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatcher;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestFieldDeserializerFactory;
use Wikibase\Repo\Tests\RestApi\Domain\Model\EditMetadataHelper;
use Wikibase\Repo\Tests\RestApi\Domain\ReadModel\NewStatementReadModel;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\StatementReadModelHelper;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchStatementTest extends TestCase {

	use EditMetadataHelper;
	use StatementReadModelHelper;

	private const STRING_PROPERTY = 'P123';

	private PatchStatementValidator $useCaseValidator;
	private PatchedStatementValidator $patchedStatementValidator;
	private StatementSerializer $statementSerializer;
	private StatementRetriever $statementRetriever;
	private StatementUpdater $statementUpdater;
	private GetLatestStatementSubjectRevisionMetadata $getRevisionMetadata;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private StatementReadModelConverter $statementReadModelConverter;

	protected function setUp(): void {
		parent::setUp();

		$this->useCaseValidator = new ValidatingRequestDeserializer( TestValidatingRequestFieldDeserializerFactory::newFactory() );
		$this->patchedStatementValidator = $this->createStub( PatchedStatementValidator::class );
		$this->statementRetriever = $this->createStub( StatementRetriever::class );
		$this->statementUpdater = $this->createStub( StatementUpdater::class );
		$this->getRevisionMetadata = $this->createStub( GetLatestStatementSubjectRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ 456, '20221111070607' ] );
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
		$this->statementSerializer = $this->newStatementSerializer();
		$this->statementReadModelConverter = $this->newStatementReadModelConverter();
	}

	/**
	 * @dataProvider provideSubjectId
	 */
	public function testPatchStatement_success( EntityId $subjectId ): void {
		$statementId = new StatementGuid( $subjectId, 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$oldStatementValue = 'old statement value';
		$newStatementValue = 'new statement value';

		$statementToPatch = NewStatementReadModel::forProperty( self::STRING_PROPERTY )
			->withGuid( $statementId )
			->withValue( $oldStatementValue )
			->build();

		$postModificationRevisionId = 567;
		$modificationTimestamp = '20221111070707';
		$editTags = TestValidatingRequestFieldDeserializerFactory::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'statement replaced by ' . __method__;

		$patch = $this->getValidValueReplacingPatch( $newStatementValue );

		$patchedStatement = NewStatement::forProperty( self::STRING_PROPERTY )
			->withGuid( $statementId )
			->withValue( $newStatementValue )
			->build();

		$readModelPatchedStatement = $this->statementReadModelConverter->convert( $patchedStatement );

		$this->statementRetriever->method( 'getStatement' )->willReturn( $statementToPatch );
		$this->patchedStatementValidator->method( 'validateAndDeserializeStatement' )->willReturn( $patchedStatement );

		$this->statementUpdater = $this->createMock( StatementUpdater::class );
		$this->statementUpdater->expects( $this->once() )
			->method( 'update' )
			->with(
				$patchedStatement,
				$this->expectEquivalentMetadata( $editTags, $isBot, $comment, EditSummary::PATCH_ACTION )
			)
			->willReturn( new StatementRevision( $readModelPatchedStatement,	$modificationTimestamp, $postModificationRevisionId ) );

		$response = $this->newUseCase()->execute(
			$this->newUseCaseRequest( [
				'$statementId' => (string)$statementId,
				'$patch' => $patch,
				'$editTags' => $editTags,
				'$isBot' => $isBot,
				'$comment' => $comment,
				'$username' => null,
			] )
		);

		$this->assertInstanceOf( PatchStatementResponse::class, $response );
		$this->assertSame( $readModelPatchedStatement, $response->getStatement() );
		$this->assertSame( $modificationTimestamp, $response->getLastModified() );
		$this->assertSame( $postModificationRevisionId, $response->getRevisionId() );
	}

	/**
	 * @dataProvider provideSubjectId
	 */
	public function testStatementNotFoundOnSubject_throwsUseCaseError( EntityId $subjectId ): void {
		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( [
					'$statementId' => "$subjectId\$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE",
					'$patch' => $this->getValidValueReplacingPatch(),
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::STATEMENT_NOT_FOUND, $e->getErrorCode() );
			$this->assertSame(
				"Could not find a statement with the ID: $subjectId\$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE",
				$e->getErrorMessage()
			);
		}
	}

	/**
	 * @dataProvider provideSubjectId
	 */
	public function testRejectsPropertyIdChange( EntityId $subjectId ): void {
		$guid = $subjectId->getSerialization() . '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		$statementToPatch = NewStatementReadModel::noValueFor( self::STRING_PROPERTY )
			->withGuid( $guid )
			->build();

		$patchedStatement = NewStatement::noValueFor( 'P321' )->withGuid( $guid )->build();

		$this->statementRetriever->method( 'getStatement' )->willReturn( $statementToPatch );
		$this->patchedStatementValidator->method( 'validateAndDeserializeStatement' )->willReturn( $patchedStatement );

		$this->statementUpdater = $this->createStub( StatementUpdater::class );
		$this->statementUpdater->method( 'update' )->willThrowException( new PropertyChangedException() );

		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( [
					'$statementId' => $guid,
					'$patch' => [ [ 'op' => 'replace', 'path' => '/property/id', 'value' => 'P321' ] ],
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_OPERATION_CHANGED_PROPERTY, $e->getErrorCode() );
			$this->assertSame( 'Cannot change the property of the existing statement', $e->getErrorMessage() );
		}
	}

	/**
	 * @dataProvider provideSubjectId
	 */
	public function testRejectsStatementIdChange( EntityId $subjectId ): void {
		$originalGuid = $subjectId->getSerialization() . '$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		$newGuid = $subjectId->getSerialization() . '$FFFFFFFF-BBBB-CCCC-DDDD-EEEEEEEEEEEE';

		$statementToPatch = NewStatementReadModel::noValueFor( self::STRING_PROPERTY )
			->withGuid( $originalGuid )
			->build();

		$patchedStatement = NewStatement::noValueFor( self::STRING_PROPERTY )->withGuid( $newGuid )->build();

		$this->statementRetriever->method( 'getStatement' )->willReturn( $statementToPatch );
		$this->patchedStatementValidator->method( 'validateAndDeserializeStatement' )->willReturn( $patchedStatement );

		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( [
					'$statementId' => $originalGuid,
					'$patch' => [ [ 'op' => 'replace', 'path' => '/id', 'value' => $newGuid ] ],
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_OPERATION_CHANGED_STATEMENT_ID, $e->getErrorCode() );
			$this->assertSame( 'Cannot change the ID of the existing statement', $e->getErrorMessage() );
		}
	}

	/**
	 * @dataProvider inapplicablePatchProvider
	 */
	public function testGivenValidInapplicablePatch_throwsUseCaseError(
		array $patch,
		string $expectedErrorCode,
		array $subjectIds
	): void {
		foreach ( $subjectIds as $subjectId ) {
			$statementId = new StatementGuid( $subjectId, 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );

			$this->statementRetriever->method( 'getStatement' )->willReturn(
				NewStatementReadModel::forProperty( self::STRING_PROPERTY )
					->withGuid( $statementId )
					->withValue( 'abc' )
					->build()
			);

			try {
				$this->newUseCase()->execute(
					$this->newUseCaseRequest( [ '$statementId' => "$statementId", '$patch' => $patch ] )
				);
				$this->fail( 'this should not be reached' );
			} catch ( UseCaseError $e ) {
				$this->assertSame( $expectedErrorCode, $e->getErrorCode() );
			}
		}
	}

	public static function inapplicablePatchProvider(): Generator {
		yield 'patch test operation failed' => [
			[
				[
					'op' => 'test',
					'path' => '/value/content',
					'value' => 'these are not the droids you are looking for',
				],
			],
			UseCaseError::PATCH_TEST_FAILED,
			[ new ItemId( 'Q123' ), new NumericPropertyId( 'P123' ) ],
		];

		yield 'non-existent path' => [
			[
				[
					'op' => 'remove',
					'path' => '/this/path/does/not/exist',
				],
			],
			UseCaseError::PATCH_TARGET_NOT_FOUND,
			[ new ItemId( 'Q123' ), new NumericPropertyId( 'P123' ) ],
		];
	}

	/**
	 * @dataProvider provideSubjectId
	 */
	public function testGivenPatchedStatementInvalid_throwsUseCaseError( EntityId $subjectId ): void {
		$patch = [
			[
				'op' => 'remove',
				'path' => '/property',
			],
		];

		$statementId = new StatementGuid( $subjectId, 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );

		$this->statementRetriever->method( 'getStatement' )->willReturn(
			NewStatementReadModel::forProperty( self::STRING_PROPERTY )
				->withGuid( $statementId )
				->withValue( 'abc' )
				->build()
		);

		$expectedException = $this->createStub( UseCaseError::class );

		$this->patchedStatementValidator->method( 'validateAndDeserializeStatement' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( [ '$statementId' => "$statementId", '$patch' => $patch ] )
			);

			$this->fail( 'this should not be reached' );
		} catch ( Exception $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	/**
	 * @dataProvider provideSubjectId
	 */
	public function testGivenProtectedStatementSubject_throwsUseCaseError( EntityId $subjectId ): void {
		$statementId = "$subjectId\$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE";
		$statementReadModel = NewStatementReadModel::forProperty( self::STRING_PROPERTY )
			->withGuid( $statementId )
			->withValue( 'abc' )
			->build();

		$expectedError = $this->createStub( UseCaseError::class );

		$this->assertUserIsAuthorized = $this->createMock( AssertUserIsAuthorized::class );
		$this->assertUserIsAuthorized->method( 'execute' )
			->with( $subjectId, null )
			->willThrowException( $expectedError );

		$this->statementRetriever->method( 'getStatement' )->willReturn( $statementReadModel );

		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( [
					'$statementId' => $statementId,
					'$patch' => $this->getValidValueReplacingPatch(),
				] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	public function provideSubjectId(): Generator {
		yield 'item id' => [ new ItemId( 'Q123' ) ];
		yield 'property id' => [ new NumericPropertyId( 'P123' ) ];
	}

	private function newUseCase(): PatchStatement {
		return new PatchStatement(
			$this->useCaseValidator,
			$this->patchedStatementValidator,
			new JsonDiffJsonPatcher(),
			$this->statementSerializer,
			new AssertStatementSubjectExists( $this->getRevisionMetadata ),
			$this->statementRetriever,
			$this->statementUpdater,
			$this->assertUserIsAuthorized
		);
	}

	private function newUseCaseRequest( array $requestData ): PatchStatementRequest {
		return new PatchStatementRequest(
			$requestData['$statementId'],
			$requestData['$patch'],
			$requestData['$editTags'] ?? [],
			$requestData['$isBot'] ?? false,
			$requestData['$comment'] ?? null,
			$requestData['$username'] ?? null
		);
	}

	private function getValidValueReplacingPatch( string $newStatementValue = '' ): array {
		return [
			[
				'op' => 'replace',
				'path' => '/value/content',
				'value' => $newStatementValue,
			],
		];
	}

	private function newStatementSerializer(): StatementSerializer {
		$propertyValuePairSerializer = new PropertyValuePairSerializer();

		return new StatementSerializer(
			$propertyValuePairSerializer,
			new ReferenceSerializer( $propertyValuePairSerializer )
		);
	}

}
