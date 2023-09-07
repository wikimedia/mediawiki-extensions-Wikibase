<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\RequestValidation;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\LanguageCodeRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\LanguageCodeRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\LanguageCodeRequestValidatingDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LanguageCodeRequestValidatingDeserializerTest extends TestCase {

	public function testGivenValidRequest_returnsLanguageCode(): void {
		$request = $this->createStub( LanguageCodeRequest::class );
		$request->method( 'getLanguageCode' )->willReturn( 'en' );

		$this->assertEquals(
			'en',
			$this->newValidatingDeserializerRequest()->validateAndDeserialize( $request )
		);
	}

	public function testGivenInvalidRequest_throws(): void {
		$request = $this->createStub( LanguageCodeRequest::class );
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
			new LanguageCodeValidator( WikibaseRepo::getTermsLanguages()->getLanguages() )
		);
	}

}
