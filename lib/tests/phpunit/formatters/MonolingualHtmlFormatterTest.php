<?php

namespace Wikibase\Formatters\Test;

use DataValues\MonolingualTextValue;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Formatters\MonolingualHtmlFormatter;

/**
 * @covers Wikibase\Formatters\MonolingualHtmlFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class MonolingualHtmlFormatterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider urlFormatProvider
	 *
	 * @covers HtmlUrlFormatter::format()
	 */
	public function testFormat( $value, $options, $pattern ) {
		$formatter = new MonolingualHtmlFormatter( $options );

		$text = $formatter->format( $value );
		$this->assertRegExp( $pattern, $text );
	}

	public function urlFormatProvider() {
		$options = new FormatterOptions();
		$options->setOption( ValueFormatter::OPT_LANG, 'en' );

		return array(
			array(
				new MonolingualTextValue( 'de', 'Hallo Welt' ),
				$options,
				'@^<span lang="de".*?>Hallo Welt<\/span>.*\((German|Deutsch)\).*$@'
			),
		);
	}

}
