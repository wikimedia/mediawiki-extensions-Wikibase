<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use MediaWiki\Languages\LanguageNameUtils;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\WikibaseContentLanguages;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WikibaseContentLanguagesTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$testLanguages = new StaticContentLanguages( [ 'test' ] );
		$this->configureHookContainer( [
			'WikibaseContentLanguages' => [ function ( array &$contentLanguages ) use ( $testLanguages ) {
				$contentLanguages['test'] = $testLanguages;
			} ],
		] );
		$languageNameUtils = $this->createMock( LanguageNameUtils::class );
		$languageNameUtils->expects( $this->once() )
			->method( 'getLanguageNames' )
			->willReturn( [ 'en' => 'English' ] );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getLanguageNameUtils' )
			->willReturn( $languageNameUtils );

		/** @var WikibaseContentLanguages $wikibaseContentLanguages */
		$wikibaseContentLanguages = $this->getService( 'WikibaseClient.WikibaseContentLanguages' );

		$this->assertInstanceOf( WikibaseContentLanguages::class, $wikibaseContentLanguages );
		$this->assertContains( 'en',
			$wikibaseContentLanguages->getContentLanguages( WikibaseContentLanguages::CONTEXT_TERM )
				->getLanguages() );
		$this->assertSame( $testLanguages, $wikibaseContentLanguages->getContentLanguages( 'test' ) );
	}

}
