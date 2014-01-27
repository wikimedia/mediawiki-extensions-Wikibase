<?php

namespace Wikibase\Lib\Test;

use DataValues\NumberValue;
use DataValues\StringValue;
use ValueFormatters\FormatterOptions;
use Wikibase\Lib\CommonsLinkFormatter;

/**
 * @covers Wikibase\Lib\CommonsLinkFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Adrian Lang
 */
class CommonsLinkFormatterTest extends \PHPUnit_Framework_TestCase {

	public function urlFormatProvider() {
		$options = new FormatterOptions();

		return array(
			array(
				new StringValue( 'example.jpg' ),
				$options,
				'@<a .*href="//commons.wikimedia.org/wiki/File:example.jpg".*>.*example.jpg.*</a>@'
			),
		);
	}

	/**
	 * @dataProvider urlFormatProvider
	 *
	 * @covers CommonsLinkFormatter::format()
	 */
	public function testFormat( $value, $options, $pattern ) {
		$formatter = new CommonsLinkFormatter( $options );

		$html = $formatter->format( $value );
		$this->assertRegExp( $pattern, $html );
	}

	/**
	 * @covers CommonsLinkFormatter::format()
	 */
	public function testFormatError() {
		$formatter = new CommonsLinkFormatter( new FormatterOptions() );
		$value = new NumberValue( 23 );

		$this->setExpectedException( 'InvalidArgumentException' );
		$formatter->format( $value );
	}
}
