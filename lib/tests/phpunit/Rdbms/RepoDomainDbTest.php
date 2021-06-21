<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Tests;

use IDatabase;
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

	public function testConvenienceMethod(): void {
		$db = $this->createMock( IDatabase::class );
		$domainDB = RepoDomainDb::newFromTestConnection( $db );

		$this->assertInstanceOf( RepoDomainDb::class, $domainDB );
	}

	public function testValidConstructionAndGetters() {
		$domain = 'imarepo';
		$mainLb = $this->createStub( ILoadBalancer::class );

		$db = new RepoDomainDb( $this->newLbFactoryForDomain( $domain, $mainLb ), $domain );

		$this->assertSame( $domain, $db->domain() );
		$this->assertSame( $mainLb, $db->loadBalancer() );
	}

	private function newLbFactoryForDomain( string $domain, ILoadBalancer $mainLb ): ILBFactory {
		$lbFactory = $this->createMock( ILBFactory::class );
		$lbFactory->expects( $this->atLeastOnce() )
			->method( 'getMainLB' )
			->with( $domain )
			->willReturn( $mainLb );

		return $lbFactory;
	}

}
