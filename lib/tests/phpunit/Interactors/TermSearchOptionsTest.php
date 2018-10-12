<?php

namespace Wikibase\Lib\Tests\Interactors;

use Wikibase\Lib\Interactors\TermSearchOptions;

/**
 * @covers \Wikibase\Lib\Interactors\TermSearchOptions
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class TermSearchOptionsTest extends \PHPUnit\Framework\TestCase {

	public function provideLimitInputAndExpected() {
		return [
			[ 1, 1 ],
			[ 2500, 2500 ],
			[ 999999, 2500 ],
		];
	}

	/**
	 * @dataProvider provideLimitInputAndExpected
	 */
	public function testSetLimit( $input, $expected ) {
		$options = new TermSearchOptions();
		$options->setLimit( $input );
		$this->assertEquals( $expected, $options->getLimit() );
	}

	public function provideBooleanOptions() {
		return [
			[ true ],
			[ false ],
		];
	}

	/**
	 * @dataProvider provideBooleanOptions
	 */
	public function testSetIsCaseSensitive( $booleanValue ) {
		$options = new TermSearchOptions();
		$options->setIsCaseSensitive( $booleanValue );
		$this->assertEquals( $booleanValue, $options->getIsCaseSensitive() );
	}

	/**
	 * @dataProvider provideBooleanOptions
	 */
	public function testSetIsPrefixSearch( $booleanValue ) {
		$options = new TermSearchOptions();
		$options->setIsPrefixSearch( $booleanValue );
		$this->assertEquals( $booleanValue, $options->getIsPrefixSearch() );
	}

	/**
	 * @dataProvider provideBooleanOptions
	 */
	public function testSetUseLanguageFallback( $booleanValue ) {
		$options = new TermSearchOptions();
		$options->setUseLanguageFallback( $booleanValue );
		$this->assertEquals( $booleanValue, $options->getUseLanguageFallback() );
	}

}
