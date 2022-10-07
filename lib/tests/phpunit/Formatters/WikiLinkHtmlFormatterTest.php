<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\NumberValue;
use DataValues\StringValue;
use InvalidArgumentException;
use MediaWikiCoversValidator;
use MediaWikiTestCaseTrait;
use Wikibase\Lib\Formatters\CommonsLinkFormatter;
use Wikibase\Lib\Formatters\WikiLinkHtmlFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\WikiLinkHtmlFormatter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WikiLinkHtmlFormatterTest extends \PHPUnit\Framework\TestCase {
	use MediaWikiCoversValidator;
	use MediaWikiTestCaseTrait;

	public function linkFormatProvider() {
		return [
			'simple case' => [
				new StringValue( 'LINK' ),
				'@<a .*href=".*\:LINK".*>LINK</a>@',
			],
			'value with spaces' => [
				new StringValue( 'LINK WITH SPACES' ),
				'@<a .*href=".*\:LINK_WITH_SPACES".*>LINK WITH SPACES</a>@',
			],
			'value with underscores' => [
				new StringValue( 'LINK_WITH_UNDERSCORE' ),
				'@<a .*href=".*\:LINK_WITH_UNDERSCORE".*>LINK_WITH_UNDERSCORE</a>@',
			],
			'value with pluses' => [
				new StringValue( 'LINK+WITH+PLUS' ),
				'@<a .*href=".*\:LINK%2BWITH%2BPLUS".*>LINK\+WITH\+PLUS</a>@',
			],
			'value with ampersands' => [
				new StringValue( 'LINK&WITH&AMPERSAND' ),
				'@<a .*href=".*\:LINK%26WITH%26AMPERSAND".*>LINK&amp;WITH&amp;AMPERSAND</a>@',
			],
		];
	}

	/**
	 * @dataProvider linkFormatProvider
	 */
	public function testFormat( StringValue $value, $pattern ) {
		$formatter = new WikiLinkHtmlFormatter( 1 );

		$html = $formatter->format( $value );
		$this->assertMatchesRegularExpression( $pattern, $html );
	}

	public function testFormatError() {
		$formatter = new CommonsLinkFormatter();
		$value = new NumberValue( 23 );

		$this->expectException( InvalidArgumentException::class );
		$formatter->format( $value );
	}

}
