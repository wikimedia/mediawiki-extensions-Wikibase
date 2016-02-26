<?php

namespace Wikibase\Tests\Repo;

use Wikibase\Repo\Notifications\HookChangeTransmitter;

/**
 * @covers Wikibase\Repo\Notifications\HookChangeTransmitter
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseChange
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class HookChangeTransmitterTest extends \MediaWikiTestCase {

	public function testTransmitChange() {
		$change = $this->getMockBuilder( 'Wikibase\EntityChange' )
			->disableOriginalConstructor()
			->getMock();

		$called = false;
		$this->mergeMwGlobalArrayValue( 'wgHooks', array(
			'HookChangeTransmitterTest' => array(
				function ( $actualChange ) use ( $change, &$called ) {
					self::assertEquals( $change, $actualChange );
					$called = true;
				},
			),
		) );

		$transmitter = new HookChangeTransmitter( 'HookChangeTransmitterTest' );
		$transmitter->transmitChange( $change );

		$this->assertTrue( $called, 'The hook function was not called' );
	}

}
