<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikibase\Lib\Rdbms\TermsDomainDbFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermsDomainDbFactoryTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->mockService( 'WikibaseRepo.RepoDomainDbFactory', $this->createStub( RepoDomainDbFactory::class ) );

		$this->assertInstanceOf(
			TermsDomainDbFactory::class,
			$this->getService( 'WikibaseRepo.TermsDomainDbFactory' )
		);
	}

}
