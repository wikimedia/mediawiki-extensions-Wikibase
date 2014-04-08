<?php

namespace Wikibase\DataModel\Term\Test;

use Wikibase\DataModel\Term\Term;

/**
 * @covers Wikibase\DataModel\Term\Term
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TermTest extends \PHPUnit_Framework_TestCase {

	public function testConstructorSetsFields() {
		$term = new Term( 'foo', 'bar' );
		$this->assertEquals( 'foo', $term->getLanguageCode() );
		$this->assertEquals( 'bar', $term->getText() );
	}

	/**
	 * @dataProvider nonStringProvider
	 */
	public function testGivenNonStringLanguageCode_constructorThrowsException( $nonString ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		new Term( $nonString, 'bar' );
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

}
