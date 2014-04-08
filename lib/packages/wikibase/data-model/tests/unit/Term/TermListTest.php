<?php

namespace Wikibase\DataModel\Term\Test;

use Wikibase\DataModel\Term\Label;
use Wikibase\DataModel\Term\TermList;

/**
 * @covers Wikibase\DataModel\Term\TermList
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TermListTest extends \PHPUnit_Framework_TestCase {

	public function testGivenNoTerms_sizeIsZero() {
		$list = new TermList( array() );
		$this->assertCount( 0, $list );
	}

	public function testGivenTwoTerms_countReturnsTwo() {
		$list = new TermList( $this->getTwoTerms() );

		$this->assertCount( 2, $list );
	}

	private function getTwoTerms() {
		return array(
			'en' => new Label( 'en', 'foo' ),
			'de' => new Label( 'de', 'bar' ),
		);
	}

	public function testGivenTwoTerms_listContainsThem() {
		$array = $this->getTwoTerms();

		$list = new TermList( $array );

		$this->assertEquals( $array, iterator_to_array( $list ) );
	}

	public function testGivenTermsWithTheSameLanguage_onlyTheLastOnesAreRetained() {
		$array = array(
			new Label( 'en', 'foo' ),
			new Label( 'en', 'bar' ),

			new Label( 'de', 'baz' ),

			new Label( 'nl', 'bah' ),
			new Label( 'nl', 'blah' ),
			new Label( 'nl', 'spam' ),
		);

		$list = new TermList( $array );

		$this->assertEquals(
			array(
				'en' => new Label( 'en', 'bar' ),
				'de' => new Label( 'de', 'baz' ),
				'nl' => new Label( 'nl', 'spam' ),
			),
			iterator_to_array( $list )
		);
	}

	public function testGivenNoTerms_toTextArrayReturnsEmptyArray() {
		$list = new TermList( array() );
		$this->assertEquals( array(), $list->toTextArray() );
	}

	public function testGivenTerms_toTextArrayReturnsTermsInFormat() {
		$list = new TermList( array(
			new Label( 'en', 'foo' ),
			new Label( 'de', 'bar' ),
		) );

		$this->assertEquals(
			array(
				'en' => 'foo',
				'de' => 'bar',
			),
			$list->toTextArray()
		);
	}

	public function testCanIterateOverList() {
		$list = new TermList( array(
			new Label( 'en', 'foo' ),
		) );

		foreach ( $list as $key => $term ) {
			$this->assertEquals( 'en', $key );
			$this->assertEquals( new Label( 'en', 'foo' ), $term );
		}
	}

	public function testGivenSetLanguageCode_getByLanguageReturnsGroup() {
		$enTerm = new Label( 'en', 'a' );

		$list = new TermList( array(
			new Label( 'de', 'b' ),
			$enTerm,
			new Label( 'nl', 'c' ),
		) );

		$this->assertEquals( $enTerm, $list->getByLanguage( 'en' ) );
	}

	public function testGivenNonString_getByLanguageThrowsException() {
		$list = new TermList( array() );

		$this->setExpectedException( 'InvalidArgumentException' );
		$list->getByLanguage( null );
	}

	public function testGivenNonSetLanguageCode_getByLanguageThrowsException() {
		$list = new TermList( array() );

		$this->setExpectedException( 'OutOfBoundsException' );
		$list->getByLanguage( 'en' );
	}

	public function testGivenTermForNewLanguage_getWithTermReturnsListWithTerm() {
		$enTerm = new Label( 'en', 'foo' );
		$deTerm = new Label( 'de', 'bar' );

		$list = new TermList( array( $enTerm ) );
		$expectedList = new TermList( array( $enTerm, $deTerm ) );

		$actualList = $list->getWithTerm( $deTerm );

		$this->assertEquals( $expectedList, $actualList );
		$this->assertCount( 1, $list );
	}

}
