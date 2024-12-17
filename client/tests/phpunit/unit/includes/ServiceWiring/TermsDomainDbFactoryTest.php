<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikibase\Lib\Rdbms\TermsDomainDbFactory;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermsDomainDbFactoryTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->mockService( 'WikibaseClient.RepoDomainDbFactory', $this->createStub( RepoDomainDbFactory::class ) );

		$this->assertInstanceOf(
			TermsDomainDbFactory::class,
			$this->getService( 'WikibaseClient.TermsDomainDbFactory' )
		);
	}

}
