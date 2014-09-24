<?php

namespace Wikibase\Test;

use Wikibase\Repo\Diff\DiffOpValueFormatter;

/**
 * @covers Wikibase\Repo\Diff\DiffOpValueFormatter
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseClaim
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DiffOpValueFormatterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider provideGenerateHtml
	 *
	 * @param $name
	 * @param $oldValues
	 * @param $newValues
	 * @param $pattern
	 */
	public function testGenerateHtml( $name, $oldValues, $newValues, $pattern ) {
		$formatter = new DiffOpValueFormatter( $name, $oldValues, $newValues );

		$html = $formatter->generateHtml();
		$this->assertRegExp( $pattern, $html );
	}

	public function provideGenerateHtml() {
		return array(
			array( 'null', null, null, '@<tr>.*</tr>@' ),
			array( 'empty strings', '', '', '@<tr>.*</tr>@' ),
			array( 'empty array', array(), array(), '@<tr>.*</tr>@' ),

			array( 'old string', '<i>old</i>', null,
				'@<i>old</i>@' ),
			array( 'new string', null, '<i>new</i>',
				'@<i>new</i>@' ),
			array( 'old and new string', '<i>old</i>', '<i>new</i>',
				'@<i>old</i>.*<i>new</i>@' ),

			array( 'old array', array( '<i>old 1</i>', '<i>old 2</i>' ), null,
				'@<i>old 1</i>.*<i>old 2</i>@' ),
			array( 'new array', null, array( '<i>new 1</i>', '<i>new 2</i>' ),
				'@<i>new 1</i>.*<i>new 2</i>@' ),
			array( 'old and new array',
				array( '<i>old 1</i>', '<i>old 2</i>' ),
				array( '<i>new 1</i>', '<i>new 2</i>' ),
				'@<i>old 1</i>.*<i>old 2</i>.*<i>new 1</i>.*<i>new 2</i>@' ),
		);
	}
}
