<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Tests;

use Wikibase\Lib\Rdbms\ReplicationWaiter;
use Wikimedia\Rdbms\LBFactory;

/**
 * @covers \Wikibase\Lib\Rdbms\ReplicationWaiter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class ReplicationWaiterTest extends \PHPUnit\Framework\TestCase {

	private function getMockLbFactory( $expectedDomain ) {
		$mock = $this->createMock( LBFactory::class );
		$mock->expects( $this->once() )
			->method( 'waitForReplication' )
			->with( [ 'domain' => $expectedDomain ] );

		return $mock;
	}

	public function testWait() {
		$domain = 'imadomain';
		$lbFactory = $this->getMockLbFactory( $domain );

		$sut = new ReplicationWaiter( $lbFactory, $domain );

		$sut->wait();
	}

}
