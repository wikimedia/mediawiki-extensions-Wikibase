<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Tests;

use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikimedia\Rdbms\ILBFactory;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * @covers \Wikibase\Lib\Rdbms\RepoDomainDb
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RepoDomainDbTest extends \PHPUnit\Framework\TestCase {

	public function testValidConstructionAndGetters() {
		$domain = 'imarepo';
		new RepoDomainDb( $this->newLbFactoryForDomain( $domain ), $domain );
	}

	private function newLbFactoryForDomain( string $domain ): ILBFactory {
		$lbFactory = $this->createMock( ILBFactory::class );
		$lbFactory->expects( $this->once() )
			->method( 'getMainLB' )
			->with( $domain )
			->willReturn( $this->createStub( ILoadBalancer::class ) );

		return $lbFactory;
	}

}
