<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;
use Wikibase\View\Wbui2025ComponentsFactory;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class Wbui2025ComponentsFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$wbui2025ComponentsFactory = $this->getService( 'WikibaseRepo.Wbui2025ComponentsFactory' );

		$this->assertInstanceOf( Wbui2025ComponentsFactory::class, $wbui2025ComponentsFactory );
	}

}
