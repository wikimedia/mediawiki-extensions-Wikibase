<?php

namespace Wikibase\Repo\Tests\Hooks\Helpers;

use PHPUnit\Framework\TestCase;
use User;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\UserLanguageLookup;
use Wikibase\Repo\Hooks\Helpers\UserPreferredContentLanguagesLookup;

/**
 * @covers \Wikibase\Repo\Hooks\Helpers\UserPreferredContentLanguagesLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class UserPreferredContentLanguagesLookupTest extends TestCase {

	private $userLanguageLookup;
	private $contentLanguages;

	protected function setUp(): void {
		parent::setUp();

		$this->userLanguageLookup = $this->createMock( UserLanguageLookup::class );
		$this->userLanguageLookup->method( 'getAllUserLanguages' )->willReturn( [] );
		$this->contentLanguages = new StaticContentLanguages( [ 'en', 'de', 'fr' ] );
	}

	public function testGivenValidUserInterfaceLanguage() {
		$wikiDefaultLanguage = 'en';
		$lookup = $this->newUserPreferredTermsLanguagesLookup( $wikiDefaultLanguage );

		$this->assertSame( [ 'de' ], $lookup->getLanguages( 'de', $this->createMock( User::class ) ) );
	}

	public function testGivenAdditionalUserLanguages() {
		$user = $this->createMock( User::class );
		$this->userLanguageLookup = $this->createMock( UserLanguageLookup::class );
		$this->userLanguageLookup->expects( $this->once() )
			->method( 'getAllUserLanguages' )
			->with( $user )
			->willReturn( [ 'de', 'fr' ] );

		$lookup = $this->newUserPreferredTermsLanguagesLookup();

		$this->assertSame( [ 'en', 'de', 'fr' ], $lookup->getLanguages( 'en', $user ) );
	}

	public function testDoesNotReturnDuplicateLanguages() {
		$user = $this->createMock( User::class );
		$this->userLanguageLookup = $this->createMock( UserLanguageLookup::class );
		$this->userLanguageLookup->expects( $this->once() )
			->method( 'getAllUserLanguages' )
			->with( $user )
			->willReturn( [ 'en', 'fr' ] );

		$lookup = $this->newUserPreferredTermsLanguagesLookup();

		$this->assertEquals( [ 'en', 'fr' ], $lookup->getLanguages( 'en', $user ) );
	}

	public function testGivenNoValidUserLanguages_returnsWikiDefaultContentLanguage() {
		$this->userLanguageLookup = $this->createMock( UserLanguageLookup::class );
		$this->userLanguageLookup->expects( $this->once() )
			->method( 'getAllUserLanguages' )
			->willReturn( [ 'potato' ] );
		$wikiDefaultLanguage = 'en';

		$lookup = $this->newUserPreferredTermsLanguagesLookup( $wikiDefaultLanguage );

		$this->assertSame(
			[ $wikiDefaultLanguage ],
			$lookup->getLanguages( 'kartoffel', $this->createMock( User::class ) )
		);
	}

	public function testFiltersInvalidContentLanguages() {
		$this->userLanguageLookup = $this->createMock( UserLanguageLookup::class );
		$this->userLanguageLookup->expects( $this->once() )
			->method( 'getAllUserLanguages' )
			->willReturn( [ 'potato' ] );

		$lookup = $this->newUserPreferredTermsLanguagesLookup( 'en' );

		$this->assertSame(
			[ 'en' ],
			$lookup->getLanguages( 'en', $this->createMock( User::class ) )
		);
	}

	/**
	 * @return UserPreferredContentLanguagesLookup
	 */
	private function newUserPreferredTermsLanguagesLookup( $wikiDefaultLanguage = 'en' ) {
		return new UserPreferredContentLanguagesLookup(
			$this->contentLanguages, $this->userLanguageLookup, $wikiDefaultLanguage
		);
	}

}
