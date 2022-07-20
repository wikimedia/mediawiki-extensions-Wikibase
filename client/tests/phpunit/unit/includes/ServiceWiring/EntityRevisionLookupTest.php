<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Mocks\MockClientStore;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Lib\Store\EntityRevisionLookup;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityRevisionLookupTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->mockService( 'WikibaseClient.Store',
			new MockClientStore() );

		$this->assertInstanceOf( EntityRevisionLookup::class,
			$this->getService( 'WikibaseClient.EntityRevisionLookup' ) );
	}
}
