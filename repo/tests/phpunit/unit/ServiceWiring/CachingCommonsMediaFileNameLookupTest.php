<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Repo\CachingCommonsMediaFileNameLookup;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class CachingCommonsMediaFileNameLookupTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->assertInstanceOf(
			CachingCommonsMediaFileNameLookup::class,
			$this->getService( 'WikibaseRepo.CachingCommonsMediaFileNameLookup' )
		);
	}

}
