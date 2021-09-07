<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Notifications;

use Wikibase\Lib\Changes\Change;
use Wikibase\Repo\Notifications\ChangeHolder;

/**
 * @covers \Wikibase\Repo\Notifications\ChangeHolder
 *
 * @group Wikibase
 * @group WikibaseChange
 *
 * @license GPL-2.0-or-later
 */
class ChangeHolderTest extends \PHPUnit\Framework\TestCase {

	public function testTransmitChange() {
		$change = $this->createMock( Change::class );

		$holder = new ChangeHolder();
		$holder->transmitChange( $change );

		$this->assertSame( [ $change ], $holder->getChanges() );
	}

}
