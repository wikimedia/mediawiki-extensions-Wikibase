<?php

namespace Wikibase\View\Tests\Termbox;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\View\Termbox\TermboxDependencyLoader;

/**
 * @covers \Wikibase\View\Termbox\TermboxDependencyLoader
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermboxDependencyLoaderTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testCanBeConstructedWithoutDataOption() {
		$loader = new TermboxDependencyLoader( [] );
		$this->assertEmpty( $loader->getMessages() );
	}

	public function testLoadsDataFromJsonFile() {
		$loader = new TermboxDependencyLoader(
			[ 'data' => 'resources.json' ],
			__DIR__ . '/data'
		);
		$this->assertEquals(
			[ 'foo', 'bar' ],
			$loader->getMessages()
		);
	}

}
