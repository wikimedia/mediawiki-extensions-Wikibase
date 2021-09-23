<?php

namespace Wikibase\Repo\Tests;

use Wikibase\Repo\PidLock;

/**
 * @covers \Wikibase\Repo\PidLock
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class PidLockTest extends \PHPUnit\Framework\TestCase {

	public function testPidLock() {
		$pidLock = new PidLock( 'PidLockTest', 'wikiId' );

		$this->assertTrue( $pidLock->getLock(), 'Get an initial log' );
		$this->assertFalse( $pidLock->getLock(), 'Try to get the lock, although some already has it' );
		$this->assertTrue( $pidLock->getLock( true ), 'Force getting the lock' );

		$this->assertTrue( $pidLock->removeLock() );

		// Make sure that the given file has actually been removed.
		// ignore warning from unlink, e.g. if you use it a file that doesn't exist.
		$this->assertFalse( @$pidLock->removeLock() );
	}

}
