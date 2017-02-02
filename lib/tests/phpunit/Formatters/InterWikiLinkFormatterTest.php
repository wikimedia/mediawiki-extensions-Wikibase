<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\NumberValue;
use DataValues\StringValue;
use InvalidArgumentException;
use ValueFormatters\FormatterOptions;
use Wikibase\Lib\CommonsLinkFormatter;
use Wikibase\Lib\InterWikiLinkFormatter;

/**
 * @covers Wikibase\Lib\InterWikiLinkFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0+
 * @author Jonas Kress
 */
class InterWikiLinkFormatterTest extends \MediaWikiTestCase {

	public function linkFormatProvider() {
		return array(
			array(
				new StringValue( '[LINK]' ),
				'@<a .*href="\[BASE_URL\]%5BLINK%5D".*>.*\[LINK\].*</a>@'
			),
			array(
				new StringValue( '[LINK WITH SPACES]' ),
				'@<a .*href="\[BASE_URL\]%5BLINK_WITH_SPACES%5D".*>.*\[LINK WITH SPACES\].*</a>@'
			),
			array(
					new StringValue( '[LINK_WITH_UNDERSCORE]' ),
					'@<a .*href="\[BASE_URL\]%5BLINK_WITH_UNDERSCORE%5D".*>.*\[LINK_WITH_UNDERSCORE\].*</a>@'
			),
			array(
					new StringValue( '[LINK+WITH+PLUS]' ),
					'@<a .*href="\[BASE_URL\]%5BLINK%2BWITH%2BPLUS%5D".*>.*\[LINK\+WITH\+PLUS\].*</a>@'
			),
		);
	}

	/**
	 * @dataProvider linkFormatProvider
	 */
	public function testFormat( StringValue $value, $pattern ) {

		$options = new FormatterOptions();
		$options->setOption( 'baseUrl', '[BASE_URL]' );
		$formatter = new InterWikiLinkFormatter( $options );

		$html = $formatter->format( $value );
		$this->assertRegExp( $pattern, $html );
	}

	public function testFormatError() {
		$formatter = new CommonsLinkFormatter();
		$value = new NumberValue( 23 );

		$this->setExpectedException( InvalidArgumentException::class );
		$formatter->format( $value );
	}

}
