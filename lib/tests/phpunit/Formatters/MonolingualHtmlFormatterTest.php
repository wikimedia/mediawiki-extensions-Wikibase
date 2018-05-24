<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\MonolingualTextValue;
use Wikibase\Formatters\MonolingualHtmlFormatter;
use Wikibase\Lib\LanguageNameLookup;

/**
 * @covers Wikibase\Formatters\MonolingualHtmlFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class MonolingualHtmlFormatterTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider monolingualHtmlFormatProvider
	 */
	public function testFormat( MonolingualTextValue $value, $pattern, $not = '' ) {
		$languageNameLookup = $this->getMock( LanguageNameLookup::class );
		$languageNameLookup->expects( $this->any() )
			->method( 'getName' )
			->will( $this->returnValue( 'Deutsch' ) );

		$formatter = new MonolingualHtmlFormatter( $languageNameLookup );

		$text = $formatter->format( $value );

		if ( $not === 'not' ) {
			$this->assertNotRegExp( $pattern, $text );
		} else {
			$this->assertRegExp( $pattern, $text );
		}
	}

	public function monolingualHtmlFormatProvider() {
		return [
			'formatting' => [
				new MonolingualTextValue( 'de', 'Hallo Welt' ),
				'@^<span lang="de".*?>Hallo Welt<\/span>.*\Deutsch.*$@'
			],
			'html/wikitext escaping' => [
				new MonolingualTextValue( 'de', '[[Hallo&Welt]]' ),
				'@^<span .*?>(\[\[|&#91;&#91;)Hallo(&amp;|&#38;)Welt(\]\]|&#93;&#93;)<\/span>.*$@'
			],
			'evil html' => [
				new MonolingualTextValue(
					'" onclick="alert(\'gotcha!\')"',
					'Hallo<script>alert(\'gotcha!\')</script>Welt'
						.'<a href="javascript:alert(\'gotcha!\')">evil</a>'
				),
				'@ onclick="alert|<script|<a @',
				'not'
			],
		];
	}

}
