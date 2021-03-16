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
class TermsLanguagesTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$termsLanguages = new StaticContentLanguages( [ 'test' ] );
		$this->mockService( 'WikibaseRepo.WikibaseContentLanguages',
			new WikibaseContentLanguages( [
				WikibaseContentLanguages::CONTEXT_TERM => $termsLanguages,
			] ) );

		$this->assertSame(
			$termsLanguages,
			$this->getService( 'WikibaseRepo.TermsLanguages' )
		);
	}

}
