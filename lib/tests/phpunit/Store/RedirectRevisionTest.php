<?php

namespace Wikibase\Lib\Tests\Store;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\RedirectRevision;

/**
 * @covers Wikibase\Lib\Store\RedirectRevision
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @license GPL-2.0+
 * @author Thiemo Kreuz
 */
class RedirectRevisionTest extends PHPUnit_Framework_TestCase {

	private function newRedirect() {
		return new EntityRedirect( new ItemId( 'Q1' ), new ItemId( 'Q2' ) );
	}

	/**
	 * @dataProvider invalidConstructorArgumentsProvider
	 */
	public function testConstructorThrowsException( $revisionId, $mwTimestamp ) {
		$this->setExpectedException( InvalidArgumentException::class );
		new RedirectRevision( $this->newRedirect(), $revisionId, $mwTimestamp );
	}

	public function invalidConstructorArgumentsProvider() {
		return [
			[ null, '' ],
			[ true, '' ],
			[ -1, '' ],
			[ 0, null ],
			[ 0, true ],
			[ 0, 1 ],
			[ 0, '20141231' ],
			[ 0, "20141231000000\n" ],
			[ 0, '2014-12-31T00:00:00' ],
		];
	}

	public function testGetRedirect() {
		$redirect = $this->newRedirect();
		$instance = new RedirectRevision( $redirect );
		$this->assertSame( $redirect, $instance->getRedirect() );
	}

	public function testGetRevisionId() {
		$instance = new RedirectRevision( $this->newRedirect() );
		$this->assertSame( 0, $instance->getRevisionId() );
	}

	public function testGetTimestamp() {
		$instance = new RedirectRevision( $this->newRedirect() );
		$this->assertSame( '', $instance->getTimestamp() );
	}

}
