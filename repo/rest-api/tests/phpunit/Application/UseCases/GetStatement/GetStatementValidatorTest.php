<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetStatement;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\RequestedSubjectIdValidator;
use Wikibase\Repo\RestApi\Application\UseCases\RequiredRequestedSubjectIdValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UnexpectedRequestedSubjectIdValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\EntityIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetStatementValidatorTest extends TestCase {

	private RequestedSubjectIdValidator $requestedSubjectIdValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->statementIdValidator = $this->createStub( StatementIdValidator::class );
		$this->requestedSubjectIdValidator = $this->createStub( RequestedSubjectIdValidator::class );
	}

	/**
	 * @dataProvider provideSubjectId
	 * @doesNotPerformAssertions
	 */
	public function testWithValidStatementId( string $subjectId ): void {
		$this->requestedSubjectIdValidator = new UnexpectedRequestedSubjectIdValidator();
		$this->newStatementValidator()->assertValidRequest(
			new GetStatementRequest( "$subjectId\$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE" )
		);
	}

	public static function provideSubjectId(): Generator {
		yield 'item id' => [ 'Q123' ];
		yield 'property id' => [ 'P123' ];
	}

	/**
	 * @dataProvider provideEntityIdValidatorAndValidSubjectId
	 * @doesNotPerformAssertions
	 */
	public function testWithValidStatementIdAndSubjectId( EntityIdValidator $entityIdValidator, string $subjectId ): void {
		$this->requestedSubjectIdValidator = new RequiredRequestedSubjectIdValidator( $entityIdValidator );
		$this->newStatementValidator()->assertValidRequest(
			new GetStatementRequest( "$subjectId\$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE", $subjectId )
		);
	}

	public static function provideEntityIdValidatorAndValidSubjectId(): Generator {
		yield 'item id' => [ new ItemIdValidator(), 'Q123' ];
		yield 'property id' => [ new PropertyIdValidator(), 'P123' ];
	}

	public function testGivenRequestedSubjectIdValidatorThrowsUseCaseError_throwsUseCaseError(): void {
		$invalidSubjectId = 'X123';
		$useCaseError = $this->createStub( UseCaseError::class );
		$this->requestedSubjectIdValidator = $this->createMock( RequestedSubjectIdValidator::class );
		$this->requestedSubjectIdValidator->expects( $this->once() )
			->method( 'assertValid' )
			->with( $invalidSubjectId )
			->willThrowException( $useCaseError );

		$this->expectExceptionObject( $useCaseError );

		$this->newStatementValidator()->assertValidRequest(
			new GetStatementRequest( "$invalidSubjectId\$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE", $invalidSubjectId )
		);
	}

	public function testGivenStatementIdValidatorReturnsValidationError_throwsUseCaseError(): void {
		$invalidStatementId = 'X123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		$validationError = new ValidationError(
			StatementIdValidator::CODE_INVALID,
			[ StatementIdValidator::CONTEXT_VALUE => $invalidStatementId ]
		);
		$this->statementIdValidator = $this->createMock( StatementIdValidator::class );
		$this->statementIdValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $invalidStatementId )
			->willReturn( $validationError );

		try {
			$this->newStatementValidator()->assertValidRequest( new GetStatementRequest( $invalidStatementId ) );
			$this->fail( 'Exception not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_STATEMENT_ID, $e->getErrorCode() );
			$this->assertStringContainsString( $invalidStatementId, $e->getErrorMessage() );
		}
	}

	private function newStatementValidator(): GetStatementValidator {
		return new GetStatementValidator( $this->statementIdValidator, $this->requestedSubjectIdValidator );
	}

}
