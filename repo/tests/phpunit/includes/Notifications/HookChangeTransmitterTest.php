<?php

namespace Wikibase\Repo\Tests\Notifications;

use Wikibase\EntityChange;
use Wikibase\Repo\Notifications\HookChangeTransmitter;

/**
 * @covers Wikibase\Repo\Notifications\HookChangeTransmitter
 *
 * @group Wikibase
 * @group WikibaseChange
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class HookChangeTransmitterTest extends \MediaWikiTestCase {

	public function testTransmitChange() {
		$change = $this->getMockBuilder( EntityChange::class )
			->disableOriginalConstructor()
			->getMock();

		$called = false;
		$this->mergeMwGlobalArrayValue( 'wgHooks', [
			'HookChangeTransmitterTest' => [
				function ( $actualChange ) use ( $change, &$called ) {
					self::assertEquals( $change, $actualChange );
					$called = true;
				},
			],
		] );

		$transmitter = new HookChangeTransmitter( 'HookChangeTransmitterTest' );
		$transmitter->transmitChange( $change );

		$this->assertTrue( $called, 'The hook function was not called' );
	}

}
