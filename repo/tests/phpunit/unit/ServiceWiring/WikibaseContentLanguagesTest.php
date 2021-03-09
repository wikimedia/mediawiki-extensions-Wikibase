<?php

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\WikibaseContentLanguages;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

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

		/** @var WikibaseContentLanguages $wikibaseContentLanguages */
		$wikibaseContentLanguages = $this->getService( 'WikibaseRepo.WikibaseContentLanguages' );

		$this->assertInstanceOf( WikibaseContentLanguages::class, $wikibaseContentLanguages );
		$this->assertContains( 'en',
			$wikibaseContentLanguages->getContentLanguages( WikibaseContentLanguages::CONTEXT_TERM )
				->getLanguages() );
		$this->assertSame( $testLanguages, $wikibaseContentLanguages->getContentLanguages( 'test' ) );
	}

}
