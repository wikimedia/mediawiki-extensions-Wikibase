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

	public function commonsLinkFormatProvider() {
		$options = new FormatterOptions();

		return array(
			array(
				new StringValue( 'example.jpg' ), // Lower-case file name
				$options,
				'@<a .*href="//commons.wikimedia.org/wiki/File:Example.jpg".*>.*Example.jpg.*</a>@'
			),
			array(
				new StringValue( 'Example.jpg' ),
				$options,
				'@<a .*href="//commons.wikimedia.org/wiki/File:Example.jpg".*>.*Example.jpg.*</a>@'
			),
			array(
				new StringValue( 'Example space.jpg' ),
				$options,
				'@<a .*href="//commons.wikimedia.org/wiki/File:Example_space.jpg".*>.*Example space.jpg.*</a>@'
			),
			array(
				new StringValue( 'Example_underscore.jpg' ),
				$options,
				'@<a .*href="//commons.wikimedia.org/wiki/File:Example_underscore.jpg".*>.*Example underscore.jpg.*</a>@'
			),
			array(
				new StringValue( 'Example+plus.jpg' ),
				$options,
				'@<a .*href="//commons.wikimedia.org/wiki/File:Example%2Bplus.jpg".*>.*Example\+plus.jpg.*</a>@'
			),
			array(
				new StringValue( '[[File:Invalid_title.mid]]' ),
				$options,
				'@^\[\[File:Invalid_title.mid\]\]$@'
			),
			array(
				new StringValue( '<a onmouseover=alert(0xF000)>ouch</a>' ),
				$options,
				'@^&lt;a onmouseover=alert\(0xF000\)&gt;ouch&lt;/a&gt;$@'
			),
			array(
				new StringValue( '' ),
				$options,
				'@^$@'
			),
		);
	}

	/**
	 * @dataProvider commonsLinkFormatProvider
	 */
	public function testFormat( $value, $options, $pattern ) {
		$formatter = new CommonsLinkFormatter( $options );

		$html = $formatter->format( $value );
		$this->assertRegExp( $pattern, $html );
	}

	public function testFormatError() {
		$formatter = new CommonsLinkFormatter( new FormatterOptions() );
		$value = new NumberValue( 23 );

		$this->setExpectedException( 'InvalidArgumentException' );
		$formatter->format( $value );
	}

}
