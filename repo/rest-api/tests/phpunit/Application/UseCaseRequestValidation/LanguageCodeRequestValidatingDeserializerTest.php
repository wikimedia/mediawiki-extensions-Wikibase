<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DescriptionLanguageCodeRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\LabelLanguageCodeRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\LanguageCodeRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Infrastructure\ValueValidatorLanguageCodeValidator;
use Wikibase\Repo\Validators\MembershipValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\LanguageCodeRequestValidatingDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LanguageCodeRequestValidatingDeserializerTest extends TestCase {

	public function testGivenValidRequest_returnsLanguageCode(): void {
		$request = $this->createStub( LabelLanguageCodeRequest::class );
		$request->method( 'getLanguageCode' )->willReturn( 'en' );

		$this->assertEquals(
			'en',
			$this->newValidatingDeserializerRequest()->validateAndDeserialize( $request )
		);
	}

	public function testGivenInvalidRequest_throws(): void {
		$request = $this->createStub( DescriptionLanguageCodeRequest::class );
		$invalidLanguageId = 'q4';
		$request->method( 'getLanguageCode' )->willReturn( $invalidLanguageId );

		try {
			$this->newValidatingDeserializerRequest()->validateAndDeserialize( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $useCaseEx ) {
			$this->assertSame( UseCaseError::INVALID_LANGUAGE_CODE, $useCaseEx->getErrorCode() );
			$this->assertSame( "Not a valid language code: $invalidLanguageId", $useCaseEx->getErrorMessage() );
		}
	}

	private function newValidatingDeserializerRequest(): LanguageCodeRequestValidatingDeserializer {
		return new LanguageCodeRequestValidatingDeserializer(
			new ValueValidatorLanguageCodeValidator( new MembershipValidator( [ 'ar', 'de', 'en', 'en-gb' ] ) )
		);
	}

}
