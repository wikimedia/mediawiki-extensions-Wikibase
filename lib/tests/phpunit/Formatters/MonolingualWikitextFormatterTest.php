<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\MonolingualTextValue;
use MediaWikiCoversValidator;
use Wikibase\Lib\Formatters\MonolingualWikitextFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\MonolingualWikitextFormatter
 *
 * @group ValueFormatters
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class MonolingualWikitextFormatterTest extends \PHPUnit\Framework\TestCase {
	use MediaWikiCoversValidator;

	/**
	 * @dataProvider monolingualWikitextProvider
	 */
	public function testFormat( MonolingualTextValue $value, $expected ) {
		$formatter = new MonolingualWikitextFormatter();
		$wikitext = $formatter->format( $value );
		$this->assertSame( $expected, $wikitext );
	}

	public function monolingualWikitextProvider() {
		return [
			'formatting' => [
				new MonolingualTextValue( 'de', 'Hallo Welt' ),
				'<span lang="de">Hallo Welt</span>',
			],
			'wikitext escaping' => [
				new MonolingualTextValue( 'de', '[[Hallo&Welt]]' ),
				'<span lang="de">&#91;&#91;Hallo&#38;Welt&#93;&#93;</span>',
			],
			'code injection' => [
				new MonolingualTextValue( 'de', '<script>alert("Hallo Welt")</script>' ),
				'<span lang="de">&#60;script&#62;alert(&#34;Hallo Welt&#34;)&#60;/script&#62;</span>',
			],
		];
	}

}
