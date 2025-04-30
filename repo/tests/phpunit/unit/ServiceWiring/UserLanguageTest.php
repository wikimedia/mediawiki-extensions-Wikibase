<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use MediaWiki\Language\Language;
use RuntimeException;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class UserLanguageTest extends ServiceWiringTestCase {

	private Language $cachedLang;

	protected function setUp(): void {
		parent::setUp();
		global $wgLang;

		// Need to call 'getService' at least once during setup to populate $wgLang
		// in the first place. As we remove users of  the `WikibaseRepo.UserLanguage`
		// service, this is no longer the case by default and we need to explicitly
		// make the call here.
		$this->getService( 'WikibaseRepo.UserLanguage' );

		$this->cachedLang = clone $wgLang;
	}

	protected function tearDown(): void {
		parent::tearDown();

		global $wgLang;

		$wgLang = $this->cachedLang;
	}

	public function testReturnsGlobal(): void {
		global $wgLang;

		$wgLang = $this->createMock( Language::class );

		$this->assertSame(
			$wgLang,
			$this->getService( 'WikibaseRepo.UserLanguage' )
		);
	}

	public function testThrowsWhenNoLanguageDefined(): void {
		global $wgLang;

		$wgLang = null;

		$this->expectException( RuntimeException::class );
		$this->getService( 'WikibaseRepo.UserLanguage' );
	}
}
