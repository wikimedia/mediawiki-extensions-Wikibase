<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

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
class TermsLanguagesTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$termsLanguages = new StaticContentLanguages( [ 'test' ] );
		$this->mockService( 'WikibaseClient.WikibaseContentLanguages',
			new WikibaseContentLanguages( [
				WikibaseContentLanguages::CONTEXT_TERM => $termsLanguages,
			] ) );

		$this->assertSame(
			$termsLanguages,
			$this->getService( 'WikibaseClient.TermsLanguages' )
		);
	}

}
