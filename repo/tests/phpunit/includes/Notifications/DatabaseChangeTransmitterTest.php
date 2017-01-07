<?php

namespace Wikibase\Repo\Tests\Notifications;

use Wikibase\Change;
use Wikibase\Repo\Notifications\DatabaseChangeTransmitter;
use Wikibase\Repo\Store\ChangeStore;

/**
 * @covers Wikibase\Repo\Notifications\DatabaseChangeTransmitter
 *
 * @group Database
 *
 * @group Wikibase
 * @group WikibaseChange
 *
 * @license GPL-2.0+
 * @author Marius Hoch
 */
class DatabaseChangeTransmitterTest extends \PHPUnit_Framework_TestCase {

	public function testTransmitChange() {
		$change = $this->getMock( Change::class );

		$changeStore = $this->getMock( ChangeStore::class );
		$changeStore->expects( $this->once() )
			->method( 'saveChange' )
			->with( $change );

		$transmitter = new DatabaseChangeTransmitter( $changeStore );
		$transmitter->transmitChange( $change );
	}

}
