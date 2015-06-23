<?php

namespace Wikibase\Tests\Repo;

use Wikibase\Repo\Notifications\MulticastChangeTransmitter;

/**
 * @covers Wikibase\Repo\Notifications\MulticastChangeTransmitter
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseChange
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class MulticastChangeTransmitterTest extends \MediaWikiTestCase {

	public function testTransmitChange() {
		$change = $this->getMockBuilder( 'Wikibase\EntityChange' )
			->disableOriginalConstructor()
			->getMock();

		$mock = $this->getMock( 'Wikibase\Repo\Notifications\ChangeTransmitter' );
		$mock->expects( $this->once() )
			->method( 'transmitChange' )
			->with( $change );

		$mutlicastTransmitter = new MulticastChangeTransmitter( array( $mock ) );

		$mutlicastTransmitter->transmitChange( $change );
	}

}
