<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Language;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\LanguageNameLookupFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LanguageNameLookupTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$userLanguage = $this->createMock( Language::class );
		$this->mockService( 'WikibaseRepo.UserLanguage',
			$userLanguage );
		$languageNameLookup = $this->createMock( LanguageNameLookup::class );
		$languageNameLookupFactory = $this->createMock( LanguageNameLookupFactory::class );
		$languageNameLookupFactory->expects( $this->once() )
			->method( 'getForLanguage' )
			->with( $userLanguage )
			->willReturn( $languageNameLookup );
		$this->mockService( 'WikibaseRepo.LanguageNameLookupFactory',
			$languageNameLookupFactory );

		$this->assertSame(
			$languageNameLookup,
			$this->getService( 'WikibaseRepo.LanguageNameLookup' )
		);
	}

}
