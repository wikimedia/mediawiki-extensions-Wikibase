<?php

namespace Wikibase\Repo\Tests\Localizer;

use InvalidArgumentException;
use Message;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\ChangeOp\ChangeOpApplyException;
use Wikibase\Repo\Localizer\ChangeOpApplyExceptionLocalizer;

/**
 * @covers \Wikibase\Repo\Localizer\ChangeOpApplyExceptionLocalizer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpApplyExceptionLocalizerTest extends TestCase {

	public function testGivenExceptionOfOtherType_hasExceptionMessageReturnsFalse() {
		$localizer = new ChangeOpApplyExceptionLocalizer();

		$this->assertFalse( $localizer->hasExceptionMessage( new InvalidArgumentException( 'foo' ) ) );
	}

	public function testGivenExceptionAndNoLocalizableMessageExists_hasExceptionMessageReturnsFalse() {
		$localizer = new ChangeOpApplyExceptionLocalizer();

		$this->assertFalse( $localizer->hasExceptionMessage(
			$this->getExceptionWithoutLocalizableMessage()
		) );
	}

	public function testGivenExceptionAndLocalizableMessageExists_hasExceptionMessageReturnsTrue() {
		$localizer = new ChangeOpApplyExceptionLocalizer();

		$this->assertTrue( $localizer->hasExceptionMessage(
			$this->getExceptionWithLocalizableMessage()
		) );
	}

	public function testGivenExceptionAndNoLocalizableMessageExists_getExceptionMessageThrowsException() {
		$localizer = new ChangeOpApplyExceptionLocalizer();

		$this->expectException( InvalidArgumentException::class );

		$localizer->getExceptionMessage( $this->getExceptionWithoutLocalizableMessage() );
	}

	public function testGivenExceptionAndLocalizableMessageExists_getExceptionMessageReturnsIt() {
		$localizer = new ChangeOpApplyExceptionLocalizer();

		$this->assertEquals(
			new Message( 'wikibase-desc', [ 'lorem' ] ),
			$localizer->getExceptionMessage( $this->getExceptionWithLocalizableMessage() )
		);
	}

	private function getExceptionWithoutLocalizableMessage() {
		return new ChangeOpApplyException( 'Foooo', [] );
	}

	private function getExceptionWithLocalizableMessage() {
		return new ChangeOpApplyException( 'wikibase-desc', [ 'lorem' ] ); // FIXME mock message
	}

}
