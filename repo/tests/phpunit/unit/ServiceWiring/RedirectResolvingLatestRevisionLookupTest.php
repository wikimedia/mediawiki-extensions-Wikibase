<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RedirectResolvingLatestRevisionLookup;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RedirectResolvingLatestRevisionLookupTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.EntityRevisionLookup',
			$this->createMock( EntityRevisionLookup::class ) );

		$this->assertInstanceOf( RedirectResolvingLatestRevisionLookup::class,
			$this->getService( 'WikibaseRepo.RedirectResolvingLatestRevisionLookup' ) );
	}

}
