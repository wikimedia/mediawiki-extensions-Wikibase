<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Client\Usage\Sql\EntityUsageDomainDb;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityUsageDomainDbTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->assertInstanceOf(
			EntityUsageDomainDb::class,
			$this->getService( 'WikibaseClient.EntityUsageDomainDb' )
		);
	}

}
