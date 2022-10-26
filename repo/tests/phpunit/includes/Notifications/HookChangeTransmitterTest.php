<?php

namespace Wikibase\Repo\Tests\Notifications;

use MediaWikiIntegrationTestCase;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Repo\Notifications\HookChangeTransmitter;

/**
 * @covers \Wikibase\Repo\Notifications\HookChangeTransmitter
 *
 * @group Wikibase
 * @group WikibaseChange
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class HookChangeTransmitterTest extends MediaWikiIntegrationTestCase {

	public function testTransmitChange() {
		$change = $this->createMock( EntityChange::class );

		$called = false;
		$this->setTemporaryHook(
			'HookChangeTransmitterTest',
			function ( $actualChange ) use ( $change, &$called ) {
				self::assertEquals( $change, $actualChange );
				$called = true;
			}
		);

		$transmitter = new HookChangeTransmitter(
			$this->getServiceContainer()->getHookContainer(),
			'HookChangeTransmitterTest'
		);
		$transmitter->transmitChange( $change );

		$this->assertTrue( $called, 'The hook function was not called' );
	}

}
