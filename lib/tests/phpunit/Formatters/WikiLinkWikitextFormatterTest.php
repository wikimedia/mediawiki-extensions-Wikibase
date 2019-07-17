<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\StringValue;
use MediaWikiCoversValidator;
use PHPUnit4And6Compat;
use Wikibase\Lib\Formatters\WikiLinkWikitextFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\WikiLinkWikitextFormatter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WikiLinkWikitextFormatterTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;
	use MediaWikiCoversValidator;

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
		$this->assertRegExp( $pattern, $html );
	}

}
