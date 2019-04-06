<?php

namespace Wikibase\View\Tests\Termbox;

use PHPUnit\Framework\TestCase;
use Wikibase\View\Termbox\TermboxModule;

/**
 * @covers \Wikibase\View\Termbox\TermboxModule
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermboxModuleTest extends TestCase {

	public function testGetMessagesFromJsonFile() {
		$module = new TermboxModule(
			[],
			__DIR__ . '/data'
		);
		$this->assertSame(
			[ 'foo', 'bar' ],
			$module->getMessages()
		);
	}

	public function testMergeMessagesWithParent() {
		$module = new TermboxModule(
			[
				'messages' => [ 'baz', 'quux' ],
			],
			__DIR__ . '/data'
		);
		$this->assertSame(
			[ 'baz', 'quux', 'foo', 'bar' ],
			$module->getMessages()
		);
	}

}
