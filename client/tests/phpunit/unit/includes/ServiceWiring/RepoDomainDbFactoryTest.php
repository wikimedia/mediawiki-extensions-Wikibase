<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RepoDomainDbFactoryTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->mockService(
			'WikibaseClient.EntitySourceDefinitions',
			$this->createStub( EntitySourceDefinitions::class )
		);
		$this->mockService( 'WikibaseClient.ItemAndPropertySource',
			new EntitySource(
				'repo',
				'repowiki',
				[],
				'',
				'',
				'',
				''
			)
		);

		$this->assertInstanceOf(
			RepoDomainDbFactory::class,
			$this->getService( 'WikibaseClient.RepoDomainDbFactory' )
		);
	}

}
