<?php

namespace Wikibase\Lib\Tests\Store;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\RedirectRevision;

/**
 * @covers Wikibase\RedirectRevision
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStore
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
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
		return array(
			array( null, '' ),
			array( true, '' ),
			array( -1, '' ),
			array( 0, null ),
			array( 0, true ),
			array( 0, 1 ),
			array( 0, '20141231' ),
			array( 0, "20141231000000\n" ),
			array( 0, '2014-12-31T00:00:00' ),
		);
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
