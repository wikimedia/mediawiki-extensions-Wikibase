<?php

namespace Wikibase\Repo\Tests\Notifications;

use Wikibase\Lib\Changes\Change;
use Wikibase\Lib\Changes\ChangeStore;
use Wikibase\Repo\Notifications\DatabaseChangeTransmitter;

/**
 * @covers \Wikibase\Repo\Notifications\DatabaseChangeTransmitter
 *
 * @group Database
 *
 * @group Wikibase
 * @group WikibaseChange
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class DatabaseChangeTransmitterTest extends \PHPUnit\Framework\TestCase {

	public function testTransmitChange() {
		$change = $this->createMock( Change::class );

		$changeStore = $this->createMock( ChangeStore::class );
		$changeStore->expects( $this->once() )
			->method( 'saveChange' )
			->with( $change );

		$transmitter = new DatabaseChangeTransmitter( $changeStore );
		$transmitter->transmitChange( $change );
	}

}
