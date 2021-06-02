<?php

declare( strict_types=1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Lib\Rdbms\ClientDomainDbFactory;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ClientDomainDbFactoryTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->assertInstanceOf(
			ClientDomainDbFactory::class,
			$this->getService( 'WikibaseClient.ClientDomainDbFactory' )
		);
	}

}
