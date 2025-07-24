<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\Validation;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Crud\Application\Validation\SiteIdValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\ValidationError;
use Wikibase\Repo\Domains\Crud\Domain\Services\SiteIdsRetriever;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\Validation\SiteIdValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SiteIdValidatorTest extends TestCase {

	public function testGivenValidSiteId_returnsNull(): void {
		$siteIdsRetriever = $this->createStub( SiteIdsRetriever::class );
		$siteIdsRetriever->method( 'getValidSiteIds' )->willReturn( [ 'enwiki', 'arwiki' ] );
		$validator = new SiteIdValidator( $siteIdsRetriever );

		$this->assertNull( $validator->validate( 'enwiki' ) );
	}

	public function testGivenInvalidSiteId_returnsValidationError(): void {
		$siteIdsRetriever = $this->createStub( SiteIdsRetriever::class );
		$siteIdsRetriever->method( 'getValidSiteIds' )->willReturn( [ 'enwiki', 'arwiki' ] );
		$validator = new SiteIdValidator( $siteIdsRetriever );
		$invalidSiteId = 'unknown-site-id';

		$error = $validator->validate( $invalidSiteId );

		$this->assertInstanceOf( ValidationError::class, $error );
		$this->assertSame( SiteIdValidator::CODE_INVALID_SITE_ID, $error->getCode() );
		$this->assertSame( $invalidSiteId, $error->getContext()[SiteIdValidator::CONTEXT_SITE_ID_VALUE] );
	}

}
