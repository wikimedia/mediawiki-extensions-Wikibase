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

	private const SITELINK_TITLE = 'Potato';

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
		string $expectedErrorMessage,
		array $expectedErrorContext = []
	): void {
		$request = $this->createStub( SitelinkEditRequest::class );
		$request->method( 'getSitelink' )->willReturn( [ 'title' => self::SITELINK_TITLE ] );

		$this->sitelinkValidator = $this->createStub( SitelinkValidator::class );
		$this->sitelinkValidator->method( 'validate' )->willReturn( $validationError );

		try {
			$this->newValidatingDeserializer()->validateAndDeserialize( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedErrorCode, $e->getErrorCode() );
			$this->assertSame( $expectedErrorMessage, $e->getErrorMessage() );
			$this->assertSame( $expectedErrorContext, $e->getErrorContext() );
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
		yield 'title field is not a string' => [
			new ValidationError( SitelinkValidator::CODE_INVALID_TITLE_TYPE ),
			UseCaseError::INVALID_TITLE_FIELD,
			'Not a valid input for title field',
		];
		yield 'badges field is not an array' => [
			new ValidationError( SitelinkValidator::CODE_INVALID_BADGES_TYPE ),
			UseCaseError::INVALID_SITELINK_BADGES_FORMAT,
			'Value of badges field is not a list',
		];
		yield 'badge is not a valid item id' => [
			new ValidationError( SitelinkValidator::CODE_INVALID_BADGE, [ SitelinkValidator::CONTEXT_BADGE => 'invalid' ] ),
			UseCaseError::INVALID_INPUT_SITELINK_BADGE,
			'Badge input is not an item ID: invalid',
			[ UseCaseError::CONTEXT_BADGE => 'invalid' ],
		];
		yield 'badge is not allowed' => [
			new ValidationError( SitelinkValidator::CODE_BADGE_NOT_ALLOWED, [ SitelinkValidator::CONTEXT_BADGE => 'Q654' ] ),
			UseCaseError::ITEM_NOT_A_BADGE,
			'Item ID provided as badge is not allowed as a badge: Q654',
			[ UseCaseError::CONTEXT_BADGE => 'Q654' ],
		];
		yield 'title not found' => [
			new ValidationError( SitelinkValidator::CODE_TITLE_NOT_FOUND ),
			UseCaseError::SITELINK_TITLE_NOT_FOUND,
			'Page with title ' . self::SITELINK_TITLE . ' does not exist on the given site',
		];
		yield 'another item has the same sitelink' => [
			new ValidationError( SitelinkValidator::CODE_SITELINK_CONFLICT, [ SitelinkValidator::CONTEXT_CONFLICT_ITEM_ID => 'Q654' ] ),
			UseCaseError::SITELINK_CONFLICT,
			'Sitelink is already being used on Q654',
			[ UseCaseError::CONTEXT_MATCHING_ITEM_ID => 'Q654' ],
		];
	}

	private function newValidatingDeserializer(): SitelinkEditRequestValidatingDeserializer {
		return new SitelinkEditRequestValidatingDeserializer( $this->sitelinkValidator );
	}
}
