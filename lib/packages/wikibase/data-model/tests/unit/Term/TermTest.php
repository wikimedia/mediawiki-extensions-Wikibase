<?php

namespace Wikibase\DataModel\Tests\Term;

use InvalidArgumentException;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;

/**
 * @covers Wikibase\DataModel\Term\Term
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TermTest extends \PHPUnit_Framework_TestCase {

	public function testConstructorSetsFields() {
		$term = new Term( 'foo', 'bar' );
		$this->assertEquals( 'foo', $term->getLanguageCode() );
		$this->assertEquals( 'bar', $term->getText() );
	}

	/**
	 * @dataProvider invalidLanguageCodeProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testGivenInvalidLanguageCode_constructorThrowsException( $languageCode ) {
		new Term( $languageCode, 'bar' );
	}

	public function invalidLanguageCodeProvider() {
		return array(
			array( null ),
			array( 21 ),
			array( '' ),
		);
	}

	/**
	 * @dataProvider nonStringProvider
	 */
	public function testGivenNonStringText_constructorThrowsException( $nonString ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		new Term( 'foo', $nonString );
	}

	public function nonStringProvider() {
		return array(
			array( null ),
			array( array() ),
			array( 42 ),
			array( true ),
		);
	}

	public function testEquality() {
		$term = new Term( 'foo', 'bar' );

		$this->assertTrue( $term->equals( $term ) );
		$this->assertTrue( $term->equals( clone $term ) );

		$this->assertFalse( $term->equals( new Term( 'foo', 'spam' ) ) );
		$this->assertFalse( $term->equals( new Term( 'spam', 'bar' ) ) );
		$this->assertFalse( $term->equals( new Term( 'spam', 'spam' ) ) );
	}

	public function testGivenSimilarFallbackObject_equalsReturnsFalse() {
		$term = new Term( 'de', 'foo' );
		$termFallback = new TermFallback( 'de', 'foo', 'en', null );
		$this->assertFalse( $term->equals( $termFallback ) );
	}

}
