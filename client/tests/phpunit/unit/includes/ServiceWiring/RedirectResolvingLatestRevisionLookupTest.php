<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RedirectResolvingLatestRevisionLookup;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RedirectResolvingLatestRevisionLookupTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseClient.EntityRevisionLookup',
			$this->createMock( EntityRevisionLookup::class ) );

		$this->assertInstanceOf( RedirectResolvingLatestRevisionLookup::class,
			$this->getService( 'WikibaseClient.RedirectResolvingLatestRevisionLookup' ) );
	}

}
