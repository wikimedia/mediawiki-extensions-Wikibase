<?php
declare( strict_types=1 );

namespace Wikibase\View\Tests;

use PHPUnit\Framework\TestCase;
use Wikibase\View\RawMessageParameter;

/**
 * @covers \Wikibase\View\RawMessageParameter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RawMessageParameterTest extends TestCase {

	public function testGetContents() {
		$contents = '<b>hi</b>';

		$this->assertSame(
			$contents,
			( new RawMessageParameter( $contents ) )->getContents()
		);
	}

}
