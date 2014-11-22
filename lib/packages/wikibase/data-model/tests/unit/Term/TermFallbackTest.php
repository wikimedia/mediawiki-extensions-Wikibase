<?php

namespace Wikibase\DataModel\Term\Test;

use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;

/**
 * @covers Wikibase\DataModel\Term\TermFallback
 *
 * @licence GNU GPL v2+
 * @author Jan Zerebecki < jan.wikimedia@zerebecki.de >
 */
class TermFallbackTest extends \PHPUnit_Framework_TestCase {

	public function testConstructorSetsFields() {
		$term = new TermFallback( 'foor', 'bar', 'fooa', 'foos' );
		$this->assertEquals( 'foor', $term->getLanguageCode() );
		$this->assertEquals( 'bar', $term->getText() );
		$this->assertEquals( 'fooa', $term->getActualLanguageCode() );
		$this->assertEquals( 'foos', $term->getSourceLanguageCode() );
	}

	public function testConstructorWithNullAsSource() {
		$term = new TermFallback( 'foor', 'bar', 'fooa', null );
		$this->assertNull( $term->getSourceLanguageCode() );
	}

	public function testGivenNonStringActualLanguageCode_constructorThrowsException() {
		$this->setExpectedException( 'InvalidArgumentException' );
		new TermFallback( 'foor', 'bar', null, 'foos' );
	}

	public function testGivenNonStringSourceLanguageCode_constructorThrowsException() {
		$this->setExpectedException( 'InvalidArgumentException' );
		new TermFallback( 'foor', 'bar', 'fooa', 21 );
	}

	public function testEquality() {
		$term = new TermFallback( 'foor', 'bar', 'fooa', 'foos' );

		$this->assertTrue( $term->equals( $term ) );
		$this->assertTrue( $term->equals( clone $term ) );
	}

	/**
	 * @dataProvider inequalTermProvider
	 */
	public function testInequality( $inequalTerm ) {
		$term = new TermFallback( 'foor', 'bar', 'fooa', 'foos' );

		$this->assertFalse( $term->equals( $inequalTerm ) );
	}

	public function inequalTermProvider() {
		return array(
			'text' => array( new TermFallback( 'foor', 'spam', 'fooa', 'foos' ) ),
			'language' => array( new TermFallback( 'spam', 'bar', 'fooa', 'foos' ) ),
			'actualLanguage' => array( new TermFallback( 'foor', 'bar', 'spam', 'foos' ) ),
			'sourceLanguage' => array( new TermFallback( 'foor', 'bar', 'fooa', 'spam' ) ),
			'null sourceLanguage' => array( new TermFallback( 'foor', 'bar', 'fooa', null ) ),
			'all' => array( new TermFallback( 'ham', 'nom', 'nom', 'nom' ) ),
			'instance Term' => array( new Term( 'foor', 'bar' ) ),
		);
	}
}
