<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use Wikibase\Lib\Rdbms\ClientDomainDb;
use Wikibase\Lib\Rdbms\ClientDomainDbFactory;
use Wikimedia\Rdbms\ILBFactory;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * @covers \Wikibase\Lib\Rdbms\DomainDbFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ClientDomainDbFactoryTest extends \PHPUnit\Framework\TestCase {

	private const CLIENT_DOMAIN = 'localClientDomain';

	/**
	 * @var MockObject|ILBFactory
	 */
	private $lbFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->lbFactory = $this->createStub( ILBFactory::class );
	}

	public function testNewClientDb() {
		$this->lbFactory = $this->newMockLBFactoryForDomain( self::CLIENT_DOMAIN );

		$factory = $this->newFactory();

		$clientDb = $factory->newLocalDb();
		$this->assertInstanceOf(
			ClientDomainDb::class,
			$clientDb
		);

		$clientDb->connections();
	}

	private function newMockLBFactoryForDomain( string $domain ) {
		$mock = $this->createMock( ILBFactory::class );
		$mock->method( 'getLocalDomainID' )->willReturn( $domain );
		$mock->expects( $this->once() )
			->method( 'getMainLB' )
			->with( $domain )
			->willReturn( $this->createStub( ILoadBalancer::class ) );

		return $mock;
	}

	private function newFactory() {
		return new ClientDomainDbFactory(
			$this->lbFactory
		);
	}

}
