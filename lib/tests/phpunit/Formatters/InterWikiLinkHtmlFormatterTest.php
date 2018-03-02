<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\NumberValue;
use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\Lib\CommonsLinkFormatter;
use Wikibase\Lib\Formatters\InterWikiLinkHtmlFormatter;

/**
 * @covers Wikibase\Lib\Formatters\InterWikiLinkHtmlFormatter
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Jonas Kress
 */
class InterWikiLinkHtmlFormatterTest extends \MediaWikiTestCase {

	public function linkFormatProvider() {
		return [
			'simple case' => [
				new StringValue( 'LINK' ),
				'@<a .*href="http://base.url/LINK".*>LINK</a>@',
			],
			'value with spaces' => [
				new StringValue( 'LINK WITH SPACES' ),
				'@<a .*href="http://base.url/LINK_WITH_SPACES".*>LINK WITH SPACES</a>@',
			],
			'value with underscores' => [
				new StringValue( 'LINK_WITH_UNDERSCORE' ),
				'@<a .*href="http://base.url/LINK_WITH_UNDERSCORE".*>LINK_WITH_UNDERSCORE</a>@',
			],
			'value with pluses' => [
				new StringValue( 'LINK+WITH+PLUS' ),
				'@<a .*href="http://base.url/LINK%2BWITH%2BPLUS".*>LINK\+WITH\+PLUS</a>@',
			],
			'value with ampersands' => [
				new StringValue( 'LINK&WITH&AMPERSAND' ),
				'@<a .*href="http://base.url/LINK%26WITH%26AMPERSAND".*>LINK&amp;WITH&amp;AMPERSAND</a>@',
			],
		];
	}

	/**
	 * @dataProvider linkFormatProvider
	 */
	public function testFormat( StringValue $value, $pattern ) {
		$formatter = new InterWikiLinkHtmlFormatter( 'http://base.url/' );

		$html = $formatter->format( $value );
		$this->assertRegExp( $pattern, $html );
	}

	public function testFormatError() {
		$formatter = new CommonsLinkFormatter();
		$value = new NumberValue( 23 );

		$this->setExpectedException( InvalidArgumentException::class );
		$formatter->format( $value );
	}

	public function testBasePathContainsSpace_EncodesSpaceWhenFormats() {
		$formatter = new InterWikiLinkHtmlFormatter( '//base.url/some wiki/' );

		$html = $formatter->format( new StringValue( 'LINK' ) );

		$this->assertSame( '<a class="extiw" href="//base.url/some+wiki/LINK">LINK</a>', $html );
	}

}
