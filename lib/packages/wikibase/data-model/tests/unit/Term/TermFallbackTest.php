<?php

namespace Wikibase\DataModel\Tests\Term;

use InvalidArgumentException;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;

/**
 * @covers \Wikibase\DataModel\Term\TermFallback
 *
 * @license GPL-2.0-or-later
 * @author Jan Zerebecki < jan.wikimedia@zerebecki.de >
 */
class TermFallbackTest extends \PHPUnit\Framework\TestCase {

	public function testConstructorSetsFields() {
		$term = new TermFallback( 'foor', 'bar', 'fooa', 'foos' );
		$this->assertSame( 'foor', $term->getLanguageCode() );
		$this->assertSame( 'bar', $term->getText() );
		$this->assertSame( 'fooa', $term->getActualLanguageCode() );
		$this->assertSame( 'foos', $term->getSourceLanguageCode() );
	}

	public function testConstructorWithNullAsSource() {
		$term = new TermFallback( 'foor', 'bar', 'fooa', null );
		$this->assertNull( $term->getSourceLanguageCode() );
	}

	/**
	 * @dataProvider invalidLanguageCodeProvider
	 */
	public function testGivenInvalidActualLanguageCode_constructorThrowsException( $languageCode ) {
		$this->expectException( InvalidArgumentException::class );
		new TermFallback( 'foor', 'bar', $languageCode, 'foos' );
	}

	public function invalidLanguageCodeProvider() {
		return [
			[ null ],
			[ 21 ],
			[ '' ],
		];
	}

	/**
	 * @dataProvider invalidSourceLanguageCodeProvider
	 */
	public function testGivenInvalidSourceLanguageCode_constructorThrowsException( $languageCode ) {
		$this->expectException( InvalidArgumentException::class );
		new TermFallback( 'foor', 'bar', 'fooa', $languageCode );
	}

	public function invalidSourceLanguageCodeProvider() {
		return [
			[ 21 ],
			[ '' ],
		];
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
		return [
			'text' => [ new TermFallback( 'foor', 'spam', 'fooa', 'foos' ) ],
			'language' => [ new TermFallback( 'spam', 'bar', 'fooa', 'foos' ) ],
			'actualLanguage' => [ new TermFallback( 'foor', 'bar', 'spam', 'foos' ) ],
			'sourceLanguage' => [ new TermFallback( 'foor', 'bar', 'fooa', 'spam' ) ],
			'null sourceLanguage' => [ new TermFallback( 'foor', 'bar', 'fooa', null ) ],
			'all' => [ new TermFallback( 'ham', 'nom', 'nom', 'nom' ) ],
			'instance Term' => [ new Term( 'foor', 'bar' ) ],
		];
	}

}
