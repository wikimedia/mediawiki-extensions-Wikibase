<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Repo\Store\BagOStuffSiteLinkConflictLookup;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class BagOStuffSiteLinkConflictLookupTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->assertInstanceOf(
			BagOStuffSiteLinkConflictLookup::class,
			$this->getService( 'WikibaseRepo.BagOStuffSiteLinkConflictLookup' )
		);
	}

}
