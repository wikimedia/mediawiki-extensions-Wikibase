<?php

namespace Wikibase\Lib\Tests\Formatters;

use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\Formatters\UnDeserializableValueFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\UnDeserializableValueFormatter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class UnDeserializableValueFormatterTest extends \PHPUnit\Framework\TestCase {

	public function testFormat() {
		$options = new FormatterOptions( [
			ValueFormatter::OPT_LANG => 'qqx',
		] );

		$formatter = new UnDeserializableValueFormatter( $options );

		$this->assertSame( '(wikibase-undeserializable-value)', $formatter->format( null ) );
	}

}
