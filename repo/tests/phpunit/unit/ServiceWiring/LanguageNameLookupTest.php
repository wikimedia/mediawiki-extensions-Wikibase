<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Language;
use Wikibase\Lib\LanguageNameLookup;
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
		$userLanguage->expects( $this->once() )
			->method( 'getCode' )
			->willReturn( 'en' );
		$this->mockService( 'WikibaseRepo.UserLanguage',
			$userLanguage );

		$this->assertInstanceOf(
			LanguageNameLookup::class,
			$this->getService( 'WikibaseRepo.LanguageNameLookup' )
		);
	}

}
