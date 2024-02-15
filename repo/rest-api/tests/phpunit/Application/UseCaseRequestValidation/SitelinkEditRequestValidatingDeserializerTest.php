<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\SiteLink;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\SitelinkEditRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\SitelinkEditRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\SitelinkValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\SitelinkEditRequestValidatingDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SitelinkEditRequestValidatingDeserializerTest extends TestCase {

	private SitelinkValidator $sitelinkValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->sitelinkValidator = $this->createStub( SitelinkValidator::class );
	}

	public function testGivenValidRequest_returnsSitelink(): void {
		$request = $this->createStub( SitelinkEditRequest::class );
		$expectedSitelink = $this->createStub( SiteLink::class );

		$this->sitelinkValidator = $this->createStub( SitelinkValidator::class );
		$this->sitelinkValidator->method( 'getValidatedSitelink' )->willReturn( $expectedSitelink );

		$this->assertEquals(
			$expectedSitelink,
			$this->newValidatingDeserializer()->validateAndDeserialize( $request )
		);
	}

	/**
	 * @dataProvider sitelinkValidationErrorProvider
	 */
	public function testGivenInvalidRequest_throws(
		ValidationError $validationError,
		string $expectedErrorCode,
		string $expectedErrorMessage
	): void {
		$request = $this->createStub( SitelinkEditRequest::class );

		$this->sitelinkValidator = $this->createStub( SitelinkValidator::class );
		$this->sitelinkValidator->method( 'validate' )->willReturn( $validationError );

		try {
			$this->newValidatingDeserializer()->validateAndDeserialize( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedErrorCode, $e->getErrorCode() );
			$this->assertSame( $expectedErrorMessage, $e->getErrorMessage() );
		}
	}

	public function sitelinkValidationErrorProvider(): \Generator {
		yield 'missing title' => [
			new ValidationError( SitelinkValidator::CODE_TITLE_MISSING ),
			UseCaseError::SITELINK_DATA_MISSING_TITLE,
			'Mandatory sitelink title missing',
		];
		yield 'title is empty' => [
			new ValidationError( SitelinkValidator::CODE_EMPTY_TITLE ),
			UseCaseError::TITLE_FIELD_EMPTY,
			'Title must not be empty',
		];
		yield 'invalid title' => [
			new ValidationError( SitelinkValidator::CODE_INVALID_TITLE ),
			UseCaseError::INVALID_TITLE_FIELD,
			'Not a valid input for title field',
		];
	}

	private function newValidatingDeserializer(): SitelinkEditRequestValidatingDeserializer {
		return new SitelinkEditRequestValidatingDeserializer( $this->sitelinkValidator );
	}
}
