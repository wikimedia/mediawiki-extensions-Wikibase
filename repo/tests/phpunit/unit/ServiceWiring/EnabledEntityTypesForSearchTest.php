<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Repo\ControllerRegistry;
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
		$this->mockService( 'WikibaseRepo.ControllerRegistry', new ControllerRegistry( [
			'type1' => [ ControllerRegistry::WB_SEARCH_ENTITIES_CONTROLLER => fn () => null ],
			'type2' => [ ControllerRegistry::WB_SEARCH_ENTITIES_CONTROLLER => fn () => null ],
		] ) );

		$this->assertSame( [ 'type1', 'type2' ],
			$this->getService( 'WikibaseRepo.EnabledEntityTypesForSearch' ) );
	}

}
