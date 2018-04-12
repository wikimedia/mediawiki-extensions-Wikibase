<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\NumberValue;
use DataValues\StringValue;
use InvalidArgumentException;
use PHPUnit4And6Compat;
use ValueFormatters\FormatterOptions;
use Wikibase\Lib\HtmlUrlFormatter;

/**
 * @covers Wikibase\Lib\HtmlUrlFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class HtmlUrlFormatterTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @dataProvider urlFormatProvider
	 */
	public function testFormat( $value, $options, $pattern ) {
		$formatter = new HtmlUrlFormatter( $options );

		$html = $formatter->format( $value );
		$this->assertRegExp( $pattern, $html );
	}

	public function urlFormatProvider() {
		$options = new FormatterOptions();

		return [
			[
				new StringValue( 'http://acme.com' ),
				$options,
				'@<a .*href="http://acme\.com".*>.*http://acme\.com.*</a>@'
			],
		];
	}

	public function testFormatError() {
		$formatter = new HtmlUrlFormatter( new FormatterOptions() );
		$value = new NumberValue( 23 );

		$this->setExpectedException( InvalidArgumentException::class );
		$formatter->format( $value );
	}

}
