<?php

namespace Wikibase\Test;
use Wikibase\FuzzyCompare;

/**
 * Test FuzzyCompare.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group FuzzyCompare
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 *
 */
class FuzzyCompareTest extends \MediaWikiTestCase {

	protected static $testData = array(
		array( 'This is a lengthy test string', 0, array() ),
		array( 'This is a lengthy string for testing', 0, array() ),
		array( 'This is a lengthy testing string', 0, array() ),
		array( 'This is another lengthy test string', 0, array() ),
		array( 'Here is something completly else', 0, array() ),
	);

	/**
	 * @dataProvider providerFuzzyCompare
	 */
	public function testFuzzyCompare( $testString, $expectedScore, $signature ) {
		$array = array_map( function( $a ){ return $a[0]; }, self::$testData );
		$fuzzy = new FuzzyCompare();
		$fuzzy->addStrings( $array );
		$fuzzy->compile();
		$score = $fuzzy->overallScore( $testString );
		$this->assertEquals( $expectedScore, $score );
	}

	public function providerFuzzyCompare() {
		return self::$testData;
	}
}