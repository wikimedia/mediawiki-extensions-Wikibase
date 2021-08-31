<?php

namespace Wikibase\DataModel\Tests\Term;

use InvalidArgumentException;
use OutOfBoundsException;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * @covers \Wikibase\DataModel\Term\TermList
 * @uses \Wikibase\DataModel\Term\Term
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TermListTest extends \PHPUnit\Framework\TestCase {

	public function testIsEmpty() {
		$list = new TermList();
		$this->assertTrue( $list->isEmpty() );

		$list = new TermList( [ new Term( 'en', 'foo' ) ] );
		$this->assertFalse( $list->isEmpty() );
	}

	public function testGivenNoTerms_sizeIsZero() {
		$list = new TermList();
		$this->assertCount( 0, $list );
	}

	public function testGivenTwoTerms_countReturnsTwo() {
		$list = new TermList( $this->getTwoTerms() );

		$this->assertCount( 2, $list );
	}

	private function getTwoTerms() {
		return [
			'en' => new Term( 'en', 'foo' ),
			'de' => new Term( 'de', 'bar' ),
		];
	}

	public function testGivenTwoTerms_listContainsThem() {
		$array = $this->getTwoTerms();

		$list = new TermList( $array );

		$this->assertEquals( $array, iterator_to_array( $list ) );
	}

	public function testGivenTermsWithTheSameLanguage_onlyTheLastOnesAreRetained() {
		$array = [
			new Term( 'en', 'foo' ),
			new Term( 'en', 'bar' ),

			new Term( 'de', 'baz' ),

			new Term( 'nl', 'bah' ),
			new Term( 'nl', 'blah' ),
			new Term( 'nl', 'spam' ),
		];

		$list = new TermList( $array );

		$this->assertEquals(
			[
				'en' => new Term( 'en', 'bar' ),
				'de' => new Term( 'de', 'baz' ),
				'nl' => new Term( 'nl', 'spam' ),
			],
			iterator_to_array( $list )
		);
	}

	public function testGivenNoTerms_toTextArrayReturnsEmptyArray() {
		$list = new TermList();
		$this->assertSame( [], $list->toTextArray() );
	}

	public function testGivenTerms_toTextArrayReturnsTermsInFormat() {
		$list = new TermList( [
			new Term( 'en', 'foo' ),
			new Term( 'de', 'bar' ),
		] );

		$this->assertSame(
			[
				'en' => 'foo',
				'de' => 'bar',
			],
			$list->toTextArray()
		);
	}

	public function testCanIterateOverList() {
		$list = new TermList( [
			new Term( 'en', 'foo' ),
		] );

		foreach ( $list as $key => $term ) {
			$this->assertSame( 'en', $key );
			$this->assertEquals( new Term( 'en', 'foo' ), $term );
		}
	}

	public function testGivenSetLanguageCode_getByLanguageReturnsGroup() {
		$enTerm = new Term( 'en', 'a' );

		$list = new TermList( [
			new Term( 'de', 'b' ),
			$enTerm,
			new Term( 'nl', 'c' ),
		] );

		$this->assertEquals( $enTerm, $list->getByLanguage( 'en' ) );
	}

	/**
	 * @dataProvider invalidLanguageCodeProvider
	 */
	public function testGivenInvalidLanguageCode_getByLanguageThrowsException( $languageCode ) {
		$list = new TermList();
		$this->expectException( OutOfBoundsException::class );
		$list->getByLanguage( $languageCode );
	}

	public function testGivenNonSetLanguageCode_getByLanguageThrowsException() {
		$list = new TermList();

		$this->expectException( OutOfBoundsException::class );
		$list->getByLanguage( 'en' );
	}

	public function testHasTermForLanguage() {
		$list = new TermList( [
			new Term( 'en', 'foo' ),
			new Term( 'de', 'bar' ),
		] );

		$this->assertTrue( $list->hasTermForLanguage( 'en' ) );
		$this->assertTrue( $list->hasTermForLanguage( 'de' ) );

		$this->assertFalse( $list->hasTermForLanguage( 'nl' ) );
		$this->assertFalse( $list->hasTermForLanguage( 'fr' ) );

		$this->assertFalse( $list->hasTermForLanguage( 'EN' ) );
		$this->assertFalse( $list->hasTermForLanguage( ' de ' ) );
	}

	/**
	 * @dataProvider invalidLanguageCodeProvider
	 */
	public function testGivenInvalidLanguageCode_hasTermForLanguageReturnsFalse( $languageCode ) {
		$list = new TermList();
		$this->assertFalse( $list->hasTermForLanguage( $languageCode ) );
	}

	public function invalidLanguageCodeProvider() {
		return [
			[ null ],
			[ 21 ],
			[ '' ],
		];
	}

	public function testGivenNotSetLanguageCode_removeByLanguageDoesNoOp() {
		$list = new TermList( [
			new Term( 'en', 'foo' ),
			new Term( 'de', 'bar' ),
		] );

		$list->removeByLanguage( 'nl' );

		$this->assertCount( 2, $list );
	}

	public function testGivenSetLanguageCode_removeByLanguageRemovesIt() {
		$deTerm = new Term( 'de', 'bar' );

		$list = new TermList( [
			new Term( 'en', 'foo' ),
			$deTerm,
		] );

		$list->removeByLanguage( 'en' );

		$this->assertEquals(
			[ 'de' => $deTerm ],
			iterator_to_array( $list )
		);
	}

	/**
	 * @dataProvider invalidLanguageCodeProvider
	 */
	public function testGivenInvalidLanguageCode_removeByLanguageDoesNoOp( $languageCode ) {
		$list = new TermList( [ new Term( 'en', 'foo' ) ] );
		$list->removeByLanguage( $languageCode );
		$this->assertFalse( $list->isEmpty() );
	}

	public function testGivenTermForNewLanguage_setTermAddsTerm() {
		$enTerm = new Term( 'en', 'foo' );
		$deTerm = new Term( 'de', 'bar' );

		$list = new TermList( [ $enTerm ] );
		$expectedList = new TermList( [ $enTerm, $deTerm ] );

		$list->setTerm( $deTerm );

		$this->assertEquals( $expectedList, $list );
	}

	public function testGivenTermForExistingLanguage_setTermReplacesTerm() {
		$enTerm = new Term( 'en', 'foo' );
		$newEnTerm = new Term( 'en', 'bar' );

		$list = new TermList( [ $enTerm ] );
		$expectedList = new TermList( [ $newEnTerm ] );

		$list->setTerm( $newEnTerm );
		$this->assertEquals( $expectedList, $list );
	}

	public function testEmptyListEqualsEmptyList() {
		$list = new TermList();
		$this->assertTrue( $list->equals( new TermList() ) );
	}

	public function testFilledListEqualsItself() {
		$list = new TermList( [
			new Term( 'en', 'foo' ),
			new Term( 'de', 'bar' ),
		] );

		$this->assertTrue( $list->equals( $list ) );
		$this->assertTrue( $list->equals( clone $list ) );
	}

	public function testDifferentListsDoNotEqual() {
		$list = new TermList( [
			new Term( 'en', 'foo' ),
			new Term( 'de', 'bar' ),
		] );

		$this->assertFalse( $list->equals( new TermList() ) );

		$this->assertFalse( $list->equals(
			new TermList( [
				new Term( 'en', 'foo' ),
			] )
		) );

		$this->assertFalse( $list->equals(
			new TermList( [
				new Term( 'en', 'foo' ),
				new Term( 'de', 'HAX' ),
			] )
		) );

		$this->assertFalse( $list->equals(
			new TermList( [
				new Term( 'en', 'foo' ),
				new Term( 'de', 'bar' ),
				new Term( 'nl', 'baz' ),
			] )
		) );
	}

	public function testGivenNonTermList_equalsReturnsFalse() {
		$list = new TermList();
		$this->assertFalse( $list->equals( null ) );
		$this->assertFalse( $list->equals( new \stdClass() ) );
	}

	public function testGivenListsThatOnlyDifferInOrder_equalsReturnsTrue() {
		$list = new TermList( [
			new Term( 'en', 'foo' ),
			new Term( 'de', 'bar' ),
		] );

		$this->assertTrue( $list->equals(
			new TermList( [
				new Term( 'de', 'bar' ),
				new Term( 'en', 'foo' ),
			] )
		) );
	}

	public function testGivenNonSetLanguageTerm_hasTermReturnsFalse() {
		$list = new TermList();
		$this->assertFalse( $list->hasTerm( new Term( 'en', 'kittens' ) ) );
	}

	public function testGivenMismatchingTerm_hasTermReturnsFalse() {
		$list = new TermList( [ new Term( 'en', 'cats' ) ] );
		$this->assertFalse( $list->hasTerm( new Term( 'en', 'kittens' ) ) );
	}

	public function testGivenMatchingTerm_hasTermReturnsTrue() {
		$list = new TermList( [ new Term( 'en', 'kittens' ) ] );
		$this->assertTrue( $list->hasTerm( new Term( 'en', 'kittens' ) ) );
	}

	public function testGivenValidArgs_setTermTextSetsTerm() {
		$list = new TermList();

		$list->setTextForLanguage( 'en', 'kittens' );

		$this->assertTrue( $list->getByLanguage( 'en' )->equals( new Term( 'en', 'kittens' ) ) );
	}

	public function testGivenInvalidLanguageCode_setTermTextThrowsException() {
		$list = new TermList();

		$this->expectException( InvalidArgumentException::class );
		$list->setTextForLanguage( null, 'kittens' );
	}

	public function testGivenInvalidTermText_setTermTextThrowsException() {
		$list = new TermList();

		$this->expectException( InvalidArgumentException::class );
		$list->setTextForLanguage( 'en', null );
	}

	public function testGivenEmptyList_getWithLanguagesReturnsEmptyList() {
		$list = new TermList();
		$this->assertEquals( new TermList(), $list->getWithLanguages( [] ) );
		$this->assertEquals( new TermList(), $list->getWithLanguages( [ 'en', 'de' ] ) );
	}

	public function testGivenNoLanguages_getWithLanguagesReturnsEmptyList() {
		$list = new TermList();
		$list->setTextForLanguage( 'en', 'foo' );
		$list->setTextForLanguage( 'de', 'bar' );

		$this->assertEquals( new TermList(), $list->getWithLanguages( [] ) );
	}

	public function testGivenAllLanguages_getWithLanguagesReturnsFullList() {
		$list = new TermList();
		$list->setTextForLanguage( 'en', 'foo' );
		$list->setTextForLanguage( 'de', 'bar' );

		$this->assertEquals( $list, $list->getWithLanguages( [ 'en', 'de' ] ) );
	}

	public function testGivenSomeLanguages_getWithLanguagesReturnsPartialList() {
		$list = new TermList();
		$list->setTextForLanguage( 'en', 'foo' );
		$list->setTextForLanguage( 'de', 'bar' );
		$list->setTextForLanguage( 'nl', 'baz' );
		$list->setTextForLanguage( 'fr', 'hax' );

		$expectedList = new TermList();
		$expectedList->setTextForLanguage( 'en', 'foo' );
		$expectedList->setTextForLanguage( 'nl', 'baz' );

		$this->assertEquals( $expectedList, $list->getWithLanguages( [ 'en', 'nl' ] ) );
	}

	public function testGivenEmptyTerms_constructorOnlyAddsNonEmptyTerms() {
		$list = new TermList( [
			new Term( 'en', 'foo' ),
			new Term( 'de', '' ),
			new Term( 'nl', 'baz' ),
			new Term( 'fr', '' ),
		] );

		$this->assertEquals(
			[
				'en' => new Term( 'en', 'foo' ),
				'nl' => new Term( 'nl', 'baz' ),
			],
			iterator_to_array( $list )
		);
	}

	public function testGivenEmptyTerm_setTermDoesNotAddIt() {
		$list = new TermList();
		$list->setTerm( new Term( 'en', '' ) );

		$this->assertEquals( new TermList(), $list );
	}

	public function testGivenEmptyTerm_setTermRemovesExistingOne() {
		$list = new TermList();
		$list->setTerm( new Term( 'en', 'foo' ) );
		$list->setTerm( new Term( 'de', 'bar' ) );
		$list->setTerm( new Term( 'en', '' ) );

		$this->assertEquals(
			new TermList( [ new Term( 'de', 'bar' ) ] ),
			$list
		);
	}

	public function testClear() {
		$list = new TermList();
		$list->setTextForLanguage( 'en', 'foo' );
		$list->setTextForLanguage( 'de', 'bar' );

		$list->clear();

		$this->assertEquals( new TermList(), $list );
	}

	public function testWhenAddingTermsToAListThatDoesNotContainThem_theyGetAdded() {
		$enTerm = new Term( 'en', 'foo' );
		$deTerm = new Term( 'de', 'bar' );

		$terms = new TermList();
		$terms->addAll( [ $enTerm, $deTerm ] );

		$this->assertSame( $enTerm, $terms->getByLanguage( 'en' ) );
		$this->assertSame( $deTerm, $terms->getByLanguage( 'de' ) );
	}

	public function testWhenAddingTermsToAListThatDoesContainThem_theyOverrideTheExistingOnes() {
		$enTerm = new Term( 'en', 'foo' );

		$newEnTerm = new Term( 'en', 'NEW' );

		$terms = new TermList( [ $enTerm ] );
		$terms->addAll( [ $newEnTerm ] );

		$this->assertSame( $newEnTerm, $terms->getByLanguage( 'en' ) );
	}

	public function testWhenAddingTerms_existingOnesAreNotLost() {
		$enTerm = new Term( 'en', 'foo' );
		$deTerm = new Term( 'de', 'bar' );

		$terms = new TermList( [ $enTerm ] );
		$terms->addAll( [ $deTerm ] );

		$this->assertSame( $enTerm, $terms->getByLanguage( 'en' ) );
	}

	public function testCanAddTermIterables() {
		$enTerm = new Term( 'en', 'foo' );

		$terms = new TermList();
		$terms->addAll( new TermList( [ $enTerm ] ) );

		$this->assertSame( $enTerm, $terms->getByLanguage( 'en' ) );
	}

	public function testWhenAddingEmptyTerms_theyRemoveExistingOnes() {
		$terms = new TermList( [ new Term( 'en', 'not-empty' ) ] );

		$terms->addAll( [ new Term( 'en', '' ) ] );

		$this->assertEquals( new TermList(), $terms );
	}

	public function testCanConstructWithIterables() {
		$enTerm = new Term( 'en', 'foo' );
		$deTerm = new Term( 'de', 'bar' );

		$terms = new TermList( new TermList( [ $enTerm, $deTerm ] ) );

		$this->assertSame( $enTerm, $terms->getByLanguage( 'en' ) );
		$this->assertSame( $deTerm, $terms->getByLanguage( 'de' ) );
	}

	public function testWhenProvidingNonTerms_constructorThrowsException() {
		$this->expectException( InvalidArgumentException::class );
		new TermList( [ 'no-a-term' ] );
	}

	public function testWhenProvidingNonTerms_addAllThrowsException() {
		$list = new TermList( [] );

		$this->expectException( InvalidArgumentException::class );
		$list->addAll( [ 'no-a-term' ] );
	}

	public function testWhenProvidingNonIterable_addAllThrowsException() {
		$list = new TermList( [] );

		$this->expectException( InvalidArgumentException::class );
		$list->addAll( new Term( 'en', 'foo' ) );
	}

}
