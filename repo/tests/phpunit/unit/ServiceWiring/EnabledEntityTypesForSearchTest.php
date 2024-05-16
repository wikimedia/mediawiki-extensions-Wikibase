<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EnabledEntityTypesForSearchTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.EntitySearchHelperCallbacks', [
			'type1' => fn () => null,
			'type2' => fn () => null,
		] );

		$this->assertSame( [ 'type1', 'type2' ],
			$this->getService( 'WikibaseRepo.EnabledEntityTypesForSearch' ) );
	}

}
