<?php

namespace Wikibase\Lib\Tests\Formatters;

use PHPUnit_Framework_TestCase;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\UnDeserializableValueFormatter;

/**
 * @covers Wikibase\Lib\UnDeserializableValueFormatter
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class UnDeserializableValueFormatterTest extends PHPUnit_Framework_TestCase {

	public function testFormat() {
		$options = new FormatterOptions( [
			ValueFormatter::OPT_LANG => 'qqx',
		] );

		$formatter = new UnDeserializableValueFormatter( $options );

		$this->assertSame( '(wikibase-undeserializable-value)', $formatter->format( null ) );
	}

}
