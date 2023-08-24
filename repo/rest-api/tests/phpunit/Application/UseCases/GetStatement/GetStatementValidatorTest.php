<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetStatement;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
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

	private StatementIdValidator $statementIdValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->statementIdValidator = $this->createStub( StatementIdValidator::class );
	}

	/**
	 * @dataProvider provideStatementIdPrefix
	 * @doesNotPerformAssertions
	 */
	public function testGivenValidStatementId_noErrorIsThrown( string $statementIdPrefix ): void {
		$this->newStatementValidator()->assertValidRequest(
			new GetStatementRequest( "$statementIdPrefix\$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE" )
		);
	}

	public static function provideStatementIdPrefix(): Generator {
		yield 'item id' => [ 'Q123' ];
		yield 'property id' => [ 'P123' ];
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
		return new GetStatementValidator( $this->statementIdValidator );
	}

}
