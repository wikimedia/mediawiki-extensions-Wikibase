<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetPropertyStatement;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement\GetPropertyStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement\GetPropertyStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement\GetPropertyStatementValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetPropertyStatementValidatorTest extends TestCase {

	private PropertyIdValidator $propertyIdValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->propertyIdValidator = $this->createStub( PropertyIdValidator::class );
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testGivenValidRequest_doesNothing(): void {
		$this->newPropertyStatementValidator()->assertValidRequest(
			new GetPropertyStatementRequest( 'P111', 'P11$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
		);
	}

	public function testGivenRequestedPropertyIdValidatorReturnsValidationError_throwsUseCaseError(): void {
		$invalidPropertyId = 'X123';
		$this->propertyIdValidator = $this->createMock( PropertyIdValidator::class );
		$this->propertyIdValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $invalidPropertyId )
			->willReturn( new ValidationError(
				PropertyIdValidator::CODE_INVALID,
				[ PropertyIdValidator::CONTEXT_VALUE => $invalidPropertyId ]
			) );

		try {
			$this->newPropertyStatementValidator()->assertValidRequest(
				new GetPropertyStatementRequest( $invalidPropertyId, "$invalidPropertyId\$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE" )
			);
			$this->fail( 'Exception not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_PROPERTY_ID, $e->getErrorCode() );
			$this->assertStringContainsString( $invalidPropertyId, $e->getErrorMessage() );
		}
	}

	private function newPropertyStatementValidator(): GetPropertyStatementValidator {
		return new GetPropertyStatementValidator( $this->propertyIdValidator );
	}

}
