<?php

namespace Wikibase\Repo\Tests\Diff;

use MediaWikiTestCaseTrait;
use Wikibase\Repo\Diff\DiffOpValueFormatter;

/**
 * @covers \Wikibase\Repo\Diff\DiffOpValueFormatter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class DiffOpValueFormatterTest extends \PHPUnit\Framework\TestCase {
	use MediaWikiTestCaseTrait;

	/**
	 * @dataProvider provideGenerateHtml
	 */
	public function testGenerateHtml( $name, $oldValues, $newValues, $pattern ) {
		$formatter = new DiffOpValueFormatter( $name, $name, $oldValues, $newValues );

		$html = $formatter->generateHtml();
		$this->assertMatchesRegularExpression( $pattern, $html );
	}

	public function provideGenerateHtml() {
		return [
			[ 'null', null, null, '@<tr>.*</tr>@' ],
			[ 'empty strings', '', '', '@<tr>.*</tr>@' ],
			[ 'empty array', [], [], '@<tr>.*</tr>@' ],

			[ 'old string', '<i>old</i>', null,
				'@<i>old</i>@' ],
			[ 'new string', null, '<i>new</i>',
				'@<i>new</i>@' ],
			[ 'old and new string', '<i>old</i>', '<i>new</i>',
				'@<i>old</i>.*<i>new</i>@' ],

			[ 'old array', [ '<i>old 1</i>', '<i>old 2</i>' ], null,
				'@<i>old 1</i>.*<i>old 2</i>@' ],
			[ 'new array', null, [ '<i>new 1</i>', '<i>new 2</i>' ],
				'@<i>new 1</i>.*<i>new 2</i>@' ],
			[ 'old and new array',
				[ '<i>old 1</i>', '<i>old 2</i>' ],
				[ '<i>new 1</i>', '<i>new 2</i>' ],
				'@<i>old 1</i>.*<i>old 2</i>.*<i>new 1</i>.*<i>new 2</i>@' ],
		];
	}

}
