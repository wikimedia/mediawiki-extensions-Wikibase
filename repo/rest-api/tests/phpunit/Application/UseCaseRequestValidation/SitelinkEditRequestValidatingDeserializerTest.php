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
		UseCaseError $expectedError,
		ValidationError $validationError,
		?array $sitelinkSerialization = [ 'sitelink serialization stub' ]
	): void {
		$request = $this->createStub( SitelinkEditRequest::class );
		$request->method( 'getSitelink' )->willReturn( $sitelinkSerialization );

		$this->sitelinkValidator = $this->createStub( SitelinkValidator::class );
		$this->sitelinkValidator->method( 'validate' )->willReturn( $validationError );

		try {
			$this->newValidatingDeserializer()->validateAndDeserialize( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public function sitelinkValidationErrorProvider(): \Generator {
		yield 'missing title' => [
			UseCaseError::newMissingField( '/sitelink', 'title' ),
			new ValidationError(
				SitelinkValidator::CODE_TITLE_MISSING,
				[ SitelinkValidator::CONTEXT_PATH => '/sitelink' ]
			),
		];

		yield 'title is empty' => [
			UseCaseError::newInvalidValue( '/sitelink/title' ),
			new ValidationError(
				SitelinkValidator::CODE_EMPTY_TITLE,
				[ SitelinkValidator::CONTEXT_PATH => '/sitelink/title', SitelinkValidator::CONTEXT_VALUE => '' ]
			),
		];

		yield 'invalid title' => [
			UseCaseError::newInvalidValue( '/sitelink/title' ),
			new ValidationError(
				SitelinkValidator::CODE_INVALID_TITLE,
				[ SitelinkValidator::CONTEXT_PATH => '/sitelink/title' ]
			),
		];

		yield 'title field is not a string' => [
			UseCaseError::newInvalidValue( '/sitelink/title' ),
			new ValidationError(
				SitelinkValidator::CODE_INVALID_FIELD_TYPE,
				[ SitelinkValidator::CONTEXT_PATH => '/sitelink/title' ]
			),
		];

		yield 'badges field is not an array' => [
			UseCaseError::newInvalidValue( '/sitelink/badges' ),
			new ValidationError(
				SitelinkValidator::CODE_INVALID_FIELD_TYPE,
				[ SitelinkValidator::CONTEXT_PATH => '/sitelink/badges' ]
			),
		];

		yield 'badge is not a valid item id' => [
			UseCaseError::newInvalidValue( '/sitelink/badges/0' ),
			new ValidationError(
				SitelinkValidator::CODE_INVALID_BADGE,
				[ SitelinkValidator::CONTEXT_VALUE => 'P3', SitelinkValidator::CONTEXT_SITE_ID => 'arwiki' ]
			),
			[ 'title' => 'بعض_العنوان', 'badges' => [ 'P3' ] ],
		];

		yield 'badge is not allowed' => [
			UseCaseError::newInvalidValue( '/sitelink/badges/1' ),
			new ValidationError(
				SitelinkValidator::CODE_BADGE_NOT_ALLOWED,
				[ SitelinkValidator::CONTEXT_VALUE => 'Q8172', SitelinkValidator::CONTEXT_SITE_ID => 'enwiki' ]
			),
			[ 'title' => 'Some_title', 'badges' => [ 'Q8622', 'Q8172' ] ],
		];

		yield 'title not found' => [
			new UseCaseError(
				UseCaseError::SITELINK_TITLE_NOT_FOUND,
				'Page with title Irgendein_titel does not exist on the given site'
			),
			new ValidationError(
				SitelinkValidator::CODE_TITLE_NOT_FOUND,
				[ SitelinkValidator::CONTEXT_SITE_ID => 'dewiki' ]
			),
			[ 'title' => 'Irgendein_titel', 'badges' => [ 'Q8622', 'Q8172' ] ],
		];

		yield 'another item has the same sitelink' => [
			UseCaseError::newDataPolicyViolation(
				UseCaseError::POLICY_VIOLATION_SITELINK_CONFLICT,
				[ UseCaseError::CONTEXT_CONFLICTING_ITEM_ID => 'Q654' ],
			),
			new ValidationError(
				SitelinkValidator::CODE_SITELINK_CONFLICT,
				[ SitelinkValidator::CONTEXT_CONFLICTING_ITEM_ID => 'Q654', SitelinkValidator::CONTEXT_SITE_ID => 'arwiki' ],
			),
		];
	}

	private function newValidatingDeserializer(): SitelinkEditRequestValidatingDeserializer {
		return new SitelinkEditRequestValidatingDeserializer( $this->sitelinkValidator );
	}
}
