<?php

namespace Wikibase\Lib\Tests\Store;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\RedirectRevision;

/**
 * @covers \Wikibase\Lib\Store\RedirectRevision
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class RedirectRevisionTest extends \PHPUnit\Framework\TestCase {

	private function newRedirect() {
		return new EntityRedirect( new ItemId( 'Q1' ), new ItemId( 'Q2' ) );
	}

	/**
	 * @dataProvider invalidConstructorArgumentsProvider
	 */
	public function testConstructorThrowsException( $revisionId, $mwTimestamp ) {
		$this->expectException( InvalidArgumentException::class );
		new RedirectRevision( $this->newRedirect(), $revisionId, $mwTimestamp );
	}

	public function invalidConstructorArgumentsProvider() {
		return [
			[ -1, '' ],
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
