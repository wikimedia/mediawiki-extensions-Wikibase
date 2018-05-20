<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\MonolingualTextValue;
use Wikibase\Lib\Formatters\WikitextMonolingualTextFormatter;

/**
 * @covers Wikibase\Lib\Formatters\WikitextMonolingualTextFormatter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class WikitextMonolingualTextFormatterTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider monolingualTextFormatProvider
	 */
	public function testFormat( MonolingualTextValue $value, $output ) {
		$formatter = new WikitextMonolingualTextFormatter();
		$this->assertEquals( $output, $formatter->format( $value ) );
	}

	public function monolingualTextFormatProvider() {
		return [
			'formatting' => [
				new MonolingualTextValue( 'de', 'Hallo Welt' ),
				'<span lang="de">Hallo Welt</span>'
			],
			'wikitext escaping' => [
				new MonolingualTextValue( 'de', '[[Hallo&Welt]]' ),
				'<span lang="de">&#91;&#91;Hallo&#38;Welft&#93;&#93;</span>'
			],
		];
	}

}
