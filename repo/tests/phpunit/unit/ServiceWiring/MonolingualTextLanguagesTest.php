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
class MonolingualTextLanguagesTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$testLanguages = new StaticContentLanguages( [ 'test' ] );
		$this->mockService( 'WikibaseRepo.WikibaseContentLanguages',
			new WikibaseContentLanguages( [
				WikibaseContentLanguages::CONTEXT_MONOLINGUAL_TEXT => $testLanguages,
			] ) );

		$monolingualTextLanguages = $this->getService( 'WikibaseRepo.MonolingualTextLanguages' );

		$this->assertSame( $testLanguages, $monolingualTextLanguages );
	}

}
