<?php

namespace Wikibase\Lib\Test;

use PHPUnit_Framework_TestCase;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\UnDeserializableValueFormatter;

/**
 * @covers Wikibase\Lib\UnDeserializableValueFormatter
 *
 * @group WikibaseLib
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class UnDeserializableValueFormatterTest extends PHPUnit_Framework_TestCase {

	public function testFormat() {
		$options = new FormatterOptions( array(
			ValueFormatter::OPT_LANG => 'qqx',
		) );

		$formatter = new UnDeserializableValueFormatter( $options );

		$this->assertSame( '(wikibase-undeserializable-value)', $formatter->format( null ) );
	}

}
