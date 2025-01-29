<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\Validation;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Crud\Application\Validation\SiteIdValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\Validation\SiteIdValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SiteIdValidatorTest extends TestCase {

	public function testGivenValidSiteId_returnsNull(): void {
		$validSiteIds = [ 'arwiki', 'enwiki' ];
		$validator = new SiteIdValidator( $validSiteIds );

		$this->assertNull( $validator->validate( 'enwiki' ) );
	}

	public function testGivenInvalidSiteId_returnsValidationError(): void {
		$validSiteIds = [ 'arwiki', 'enwiki' ];
		$validator = new SiteIdValidator( $validSiteIds );
		$invalidSiteId = 'unknown-site-id';

		$error = $validator->validate( $invalidSiteId );

		$this->assertInstanceOf( ValidationError::class, $error );
		$this->assertSame( SiteIdValidator::CODE_INVALID_SITE_ID, $error->getCode() );
		$this->assertSame( $invalidSiteId, $error->getContext()[SiteIdValidator::CONTEXT_SITE_ID_VALUE] );
	}

}
