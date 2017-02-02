<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\NumberValue;
use DataValues\StringValue;
use InvalidArgumentException;
use ValueFormatters\FormatterOptions;
use Wikibase\Lib\CommonsLinkFormatter;
use Wikibase\Lib\Formatters\InterWikiLinkFormatter;

/**
 * @covers Wikibase\Lib\InterWikiLinkFormatter
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0+
 * @author Jonas Kress
 */
class InterWikiLinkFormatterTest extends \MediaWikiTestCase {

	public function linkFormatProvider() {
		return [
			[
				new StringValue( 'LINK' ),
				'@<a .*href="http://base.url/LINK".*>LINK</a>@',
			],
			[
				new StringValue( 'LINK WITH SPACES' ),
				'@<a .*href="http://base.url/LINK_WITH_SPACES".*>LINK WITH SPACES</a>@',
			],
			[
				new StringValue( 'LINK_WITH_UNDERSCORE' ),
				'@<a .*href="http://base.url/LINK_WITH_UNDERSCORE".*>LINK_WITH_UNDERSCORE</a>@',
			],
			[
				new StringValue( 'LINK+WITH+PLUS' ),
				'@<a .*href="http://base.url/LINK%2BWITH%2BPLUS".*>LINK\+WITH\+PLUS</a>@',
			],
		];
	}

	/**
	 * @dataProvider linkFormatProvider
	 */
	public function testFormat( StringValue $value, $pattern ) {

		$options = new FormatterOptions();
		$options->setOption( InterWikiLinkFormatter::OPTION_BASE_URL, 'http://base.url/' );
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
