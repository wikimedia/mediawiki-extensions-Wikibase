<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\StringValue;
use MediaWikiCoversValidator;
use MediaWikiTestCaseTrait;
use Wikibase\Lib\Formatters\WikiLinkWikitextFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\WikiLinkWikitextFormatter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WikiLinkWikitextFormatterTest extends \PHPUnit\Framework\TestCase {
	use MediaWikiCoversValidator;
	use MediaWikiTestCaseTrait;

	public function linkFormatProvider() {
		return [
			'simple case' => [
				new StringValue( 'LINK' ),
				'@\[\[:.+?\:LINK\|LINK\]\]@',
			],
			'value with spaces' => [
				new StringValue( 'LINK WITH SPACES' ),
				'@\[\[:.+?\:LINK WITH SPACES\|LINK WITH SPACES\]\]@',
			],
		];
	}

	/**
	 * @dataProvider linkFormatProvider
	 */
	public function testFormat( StringValue $value, $pattern ) {
		$formatter = new WikiLinkWikitextFormatter( 1 );

		$html = $formatter->format( $value );
		$this->assertMatchesRegularExpression( $pattern, $html );
	}

}
