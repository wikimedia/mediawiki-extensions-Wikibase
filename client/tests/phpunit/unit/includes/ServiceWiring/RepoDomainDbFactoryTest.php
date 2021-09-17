<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataAccess\DatabaseEntitySource;
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
		$this->mockService( 'WikibaseClient.ItemAndPropertySource',
			new DatabaseEntitySource(
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

	public function testConstructionWithLocalRepo(): void {
		$this->mockService( 'WikibaseClient.ItemAndPropertySource',
			new DatabaseEntitySource(
				'repo',
				false,
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
