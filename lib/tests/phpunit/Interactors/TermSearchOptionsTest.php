<?php

namespace Wikibase\Lib\Tests\Interactors;

use PHPUnit_Framework_TestCase;

/**
 * @covers Wikibase\Lib\Interactors\TermSearchOptions
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Addshore
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class TermSearchOptionsTest extends PHPUnit_Framework_TestCase {

	public function provideLimitInputAndExpected() {
		return array(
			array( 1, 1 ),
			array( 5000, 5000 ),
			array( 999999, 5000 ),
		);
	}

	/**
	 * @dataProvider provideLimitInputAndExpected
	 */
	public function testSetLimit( $input, $expected ) {
		$options = new \Wikibase\Edrsf\TermSearchOptions();
		$options->setLimit( $input );
		$this->assertEquals( $expected, $options->getLimit() );
	}

	public function provideBooleanOptions() {
		return array(
			array( true ),
			array( false ),
		);
	}

	/**
	 * @dataProvider provideBooleanOptions
	 */
	public function testSetIsCaseSensitive( $booleanValue ) {
		$options = new \Wikibase\Edrsf\TermSearchOptions();
		$options->setIsCaseSensitive( $booleanValue );
		$this->assertEquals( $booleanValue, $options->getIsCaseSensitive() );
	}

	/**
	 * @dataProvider provideBooleanOptions
	 */
	public function testSetIsPrefixSearch( $booleanValue ) {
		$options = new \Wikibase\Edrsf\TermSearchOptions();
		$options->setIsPrefixSearch( $booleanValue );
		$this->assertEquals( $booleanValue, $options->getIsPrefixSearch() );
	}

	/**
	 * @dataProvider provideBooleanOptions
	 */
	public function testSetUseLanguageFallback( $booleanValue ) {
		$options = new \Wikibase\Edrsf\TermSearchOptions();
		$options->setUseLanguageFallback( $booleanValue );
		$this->assertEquals( $booleanValue, $options->getUseLanguageFallback() );
	}

}
