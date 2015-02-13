<?php

namespace Wikibase\Lib\Test;

use PHPUnit_Framework_TestCase;
use Wikibase\Lib\PidLock;

/**
 * @covers Wikibase\Lib\PidLock
 *
 * @group WikibaseLib
 * @group Wikibase
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class PidLockTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider wikiIdProvider
	 */
	public function testPidLock( $wikiId ) {
		$pidLock = new PidLock( 'PidLockTest', $wikiId );

		$this->assertTrue( $pidLock->getPidLock(), 'Get an initial log' );
		$this->assertFalse( $pidLock->getPidLock(), 'Try to get the lock, although some already has it' );
		$this->assertTrue( $pidLock->getPidLock( true ), 'Force getting the lock' );

		$this->assertTrue( $pidLock->removePidLock() );

		// Make sure that the given file has actually been removed.
		// unlink gives a warning if you use it a file that doesn't exist, suppress that
		wfSuppressWarnings();
		$this->assertFalse( $pidLock->removePidLock() );
		wfRestoreWarnings();
	}

	public function wikiIdProvider() {
		return array(
			array( wfWikiID() ),
			array( null )
		);
	}
}
