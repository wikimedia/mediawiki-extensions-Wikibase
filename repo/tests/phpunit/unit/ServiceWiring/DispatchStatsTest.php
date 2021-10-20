<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikibase\Repo\Store\Sql\DispatchStats;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DispatchStatsTest extends ServiceWiringTestCase {
	public function testConstruction(): void {
		$repoDomainDbFactory = $this->createMock( RepoDomainDbFactory::class );
		$repoDomainDbFactory->expects( $this->once() )->method( 'newRepoDb' );
		$this->mockService( 'WikibaseRepo.RepoDomainDbFactory', $repoDomainDbFactory );

		$this->assertInstanceOf(
			DispatchStats::class,
			$this->getService( 'WikibaseRepo.DispatchStats' )
		);
	}
}
