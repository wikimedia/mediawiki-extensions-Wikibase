<?php

namespace Wikibase\Lib\Tests\Formatters;

use PHPUnit\Framework\TestCase;
use Wikibase\Lib\DataValue\UnmappedEntityIdValue;
use Wikibase\Lib\Formatters\UnmappedEntityIdValueFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\UnmappedEntityIdValueFormatter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class UnmappedEntityIdValueFormatterTest extends TestCase {

	public function testFormat() {
		$formatter = new UnmappedEntityIdValueFormatter();

		$this->assertSame( 'FOOBAR17', $formatter->format( new UnmappedEntityIdValue( 'FOOBAR17' ) ) );
	}

}
