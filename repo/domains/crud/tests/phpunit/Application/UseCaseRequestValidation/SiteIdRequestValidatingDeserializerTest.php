<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\UseCaseRequestValidation;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\SiteIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\SiteIdRequestValidatingDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Application\Validation\SiteIdValidator;
use Wikibase\Repo\Domains\Crud\Domain\Services\SiteIdsRetriever;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\SiteIdRequestValidatingDeserializer
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
			$this->newValidatingDeserializer()->validateAndDeserialize( $request )
		);
	}

	public function testGivenInvalidRequest_throws(): void {
		$request = $this->createStub( SiteIdRequest::class );
		$request->method( 'getSiteId' )->willReturn( 'not-a-valid-site-id' );

		try {
			$this->newValidatingDeserializer()->validateAndDeserialize( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $useCaseEx ) {
			$this->assertSame( UseCaseError::INVALID_PATH_PARAMETER, $useCaseEx->getErrorCode() );
			$this->assertSame( "Invalid path parameter: 'site_id'", $useCaseEx->getErrorMessage() );
			$this->assertSame( [ UseCaseError::CONTEXT_PARAMETER => 'site_id' ], $useCaseEx->getErrorContext() );
		}
	}

	private function newValidatingDeserializer(): SiteIdRequestValidatingDeserializer {
		$siteIdsRetriever = $this->createStub( SiteIdsRetriever::class );
		$siteIdsRetriever->method( 'getValidSiteIds' )->willReturn( [ 'enwiki' ] );

		return new SiteIdRequestValidatingDeserializer( new SiteIdValidator( $siteIdsRetriever ) );
	}

}
