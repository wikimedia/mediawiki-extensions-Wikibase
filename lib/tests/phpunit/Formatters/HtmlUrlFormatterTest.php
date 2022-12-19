<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\NumberValue;
use DataValues\StringValue;
use InvalidArgumentException;
use MediaWikiTestCaseTrait;
use ValueFormatters\FormatterOptions;
use Wikibase\Lib\Formatters\HtmlUrlFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\HtmlUrlFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class HtmlUrlFormatterTest extends \PHPUnit\Framework\TestCase {
	use MediaWikiTestCaseTrait;

	/**
	 * @dataProvider urlFormatProvider
	 */
	public function testFormat( $value, $options, $pattern ) {
		$formatter = new HtmlUrlFormatter( $options );

		$html = $formatter->format( $value );
		$this->assertMatchesRegularExpression( $pattern, $html );
	}

	public function urlFormatProvider() {
		$options = new FormatterOptions();

		return [
			[
				new StringValue( 'http://acme.com' ),
				$options,
				'@<a .*href="http://acme\.com".*>.*http://acme\.com.*</a>@',
			],
		];
	}

	public function testFormatError() {
		$formatter = new HtmlUrlFormatter( new FormatterOptions() );
		$value = new NumberValue( 23 );

		$this->expectException( InvalidArgumentException::class );
		$formatter->format( $value );
	}

}
