<?php

namespace Wikibase\Formatters\Test;

use DataValues\MonolingualTextValue;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Formatters\MonolingualHtmlFormatter;
use Wikibase\Lib\LanguageNameLookup;

/**
 * @covers Wikibase\Formatters\MonolingualHtmlFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class MonolingualHtmlFormatterTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider monolingualHtmlFormatProvider
	 */
	public function testFormat( $value, $options, $pattern, $not = '' ) {
		$languageNameLookup = $this->getMock( LanguageNameLookup::class );
		$languageNameLookup->expects( $this->any() )
			->method( 'getName' )
			->will( $this->returnValue( 'Deutsch' ) );

		$formatter = new MonolingualHtmlFormatter( $options, $languageNameLookup );

		$text = $formatter->format( $value );

		if ( $not === 'not' ) {
			$this->assertNotRegExp( $pattern, $text );
		} else {
			$this->assertRegExp( $pattern, $text );
		}
	}

	public function monolingualHtmlFormatProvider() {
		$options = new FormatterOptions();
		$options->setOption( ValueFormatter::OPT_LANG, 'en' );

		return array(
			'formatting' => array(
				new MonolingualTextValue( 'de', 'Hallo Welt' ),
				$options,
				'@^<span lang="de".*?>Hallo Welt<\/span>.*\Deutsch.*$@'
			),
			'html/wikitext escaping' => array(
				new MonolingualTextValue( 'de', '[[Hallo&Welt]]' ),
				$options,
				'@^<span .*?>(\[\[|&#91;&#91;)Hallo(&amp;|&#38;)Welt(\]\]|&#93;&#93;)<\/span>.*$@'
			),
			'evil html' => array(
				new MonolingualTextValue(
					'" onclick="alert(\'gotcha!\')"',
					'Hallo<script>alert(\'gotcha!\')</script>Welt'
						.'<a href="javascript:alert(\'gotcha!\')">evil</a>'
				),
				$options,
				'@ onclick="alert|<script|<a @',
				'not'
			),
		);
	}

}
