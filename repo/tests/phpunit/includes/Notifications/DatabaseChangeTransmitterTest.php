<?php

namespace Wikibase\Tests\Repo;

use Wikibase\Repo\Notifications\DatabaseChangeTransmitter;

/**
 * @covers Wikibase\Repo\Notifications\DatabaseChangeTransmitter
 *
 * @group Database
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseChange
 *
 * @license GNU GPL v2+
 * @author Marius Hoch
 */
class DatabaseChangeTransmitterTest extends \PHPUnit_Framework_TestCase {

	public function testTransmitChange() {
		$change = $this->getMock( 'Wikibase\Change' );

		$changeStore = $this->getMock( 'Wikibase\Repo\Store\ChangeStore' );
		$changeStore->expects( $this->once() )
			->method( 'saveChange' )
			->with( $change );

		$transmitter = new DatabaseChangeTransmitter( $changeStore );
		$transmitter->transmitChange( $change );
	}

}
