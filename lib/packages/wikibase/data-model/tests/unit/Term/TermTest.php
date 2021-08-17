<?php

namespace Wikibase\DataModel\Tests\Term;

use InvalidArgumentException;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;

/**
 * @covers \Wikibase\DataModel\Term\Term
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TermTest extends \PHPUnit\Framework\TestCase {

	public function testConstructorSetsFields() {
		$term = new Term( 'foo', 'bar' );
		$this->assertSame( 'foo', $term->getLanguageCode() );
		$this->assertSame( 'bar', $term->getText() );
	}

	/**
	 * @dataProvider invalidLanguageCodeProvider
	 */
	public function testGivenInvalidLanguageCode_constructorThrowsException( $languageCode ) {
		$this->expectException( InvalidArgumentException::class );
		new Term( $languageCode, 'bar' );
	}

	public function invalidLanguageCodeProvider() {
		return [
			[ null ],
			[ 21 ],
			[ '' ],
		];
	}

	/**
	 * @dataProvider nonStringProvider
	 */
	public function testGivenNonStringText_constructorThrowsException( $nonString ) {
		$this->expectException( InvalidArgumentException::class );
		new Term( 'foo', $nonString );
	}

	public function nonStringProvider() {
		return [
			[ null ],
			[ [] ],
			[ 42 ],
			[ true ],
		];
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
