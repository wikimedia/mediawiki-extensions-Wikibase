<?php

namespace Wikibase\Client\Tests\Store\Sql;

use PHPUnit4And6Compat;

use Wikibase\Client\Store\Sql\PageRandomLookup;
use Wikimedia\Rdbms\Database;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\LoadBalancer;

/**
 * @covers \Wikibase\Client\Store\Sql\PageRandomLookup
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseStore
 *
 * @license GPL-2.0-or-later
 */
class PageRandomLookupTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @dataProvider providerGetPageRandom
	 */
	public function testGetPageRandom( $expected, $pageRandom, $msg ) {
		$lookup = new PageRandomLookup( $this->mockLoadBalancer( $this->mockDatabase( $pageRandom ) ) );
		$this->assertEquals( $expected, $lookup->getPageRandom( 0 ), $msg );
	}

	public function providerGetPageRandom() {
		return [
			[ null, false, 'Invalid: false' ],
			[ null, -0.1, 'Invalid: negative' ],
			[ null, 1.1, 'Invalid: positive' ],
			[ 0, 0, 'Valid: zero' ],
			[ 0.5, 0.5, 'Valid: float' ],
			[ 1, 1, 'Valid: one' ]
		];
	}

	/**
	 * @param Database $database
	 * @return ILoadBalancer
	 */
	private function mockLoadBalancer( $database ) {
		$mock = $this->getMock( LoadBalancer::class, [], [], '', false );
		$mock->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );
		return $mock;
	}

	/**
	 * @param number|bool $pageRandom
	 * @return Database
	 */
	private function mockDatabase( $pageRandom ) {
		$mock = $this->getMockForAbstractClass(
			Database::class, [], '', false, false, true, [ 'selectField' ]
		);
		$mock->expects( $this->any() )
			->method( 'selectField' )
			->will( $this->returnValue( $pageRandom ) );
		return $mock;
	}

}
