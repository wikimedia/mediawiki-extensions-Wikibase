<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\SiteIdRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\SiteIdRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\SiteIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\SiteIdRequestValidatingDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SiteIdRequestValidatingDeserializerTest extends TestCase {

	public function testGivenValidRequest_returnsSiteId(): void {
		$request = $this->createStub( SiteIdRequest::class );
		$request->method( 'getSiteId' )->willReturn( 'enwiki' );

		$this->assertEquals(
			'enwiki',
			$this->newValidatingDeserializerRequest()->validateAndDeserialize( $request )
		);
	}

	public function testGivenInvalidRequest_throws(): void {
		$request = $this->createStub( SiteIdRequest::class );
		$invalidSiteId = 'not-a-valid-site-id';
		$request->method( 'getSiteId' )->willReturn( $invalidSiteId );

		try {
			$this->newValidatingDeserializerRequest()->validateAndDeserialize( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $useCaseEx ) {
			$this->assertSame( UseCaseError::INVALID_SITE_ID, $useCaseEx->getErrorCode() );
			$this->assertSame( "Not a valid site id: $invalidSiteId", $useCaseEx->getErrorMessage() );
		}
	}

	private function newValidatingDeserializerRequest(): SiteIdRequestValidatingDeserializer {
		return new SiteIdRequestValidatingDeserializer( new SiteIdValidator( [ 'enwiki' ] ) );
	}

}
