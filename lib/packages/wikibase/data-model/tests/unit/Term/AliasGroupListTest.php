<?php

namespace Wikibase\DataModel\Tests\Term;

use InvalidArgumentException;
use OutOfBoundsException;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;

/**
 * @covers \Wikibase\DataModel\Term\AliasGroupList
 * @uses \Wikibase\DataModel\Term\AliasGroup
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class AliasGroupListTest extends \PHPUnit\Framework\TestCase {

	public function testIsEmpty() {
		$list = new AliasGroupList();
		$this->assertTrue( $list->isEmpty() );

		$list = new AliasGroupList( [ new AliasGroup( 'en', [ 'foo' ] ) ] );
		$this->assertFalse( $list->isEmpty() );
	}

	public function testGivenNoTerms_sizeIsZero() {
		$list = new AliasGroupList();
		$this->assertCount( 0, $list );
	}

	public function testGivenTwoTerms_countReturnsTwo() {
		$list = new AliasGroupList( $this->getTwoGroups() );

		$this->assertCount( 2, $list );
	}

	private function getTwoGroups() {
		return [
			'en' => new AliasGroup( 'en', [ 'foo' ] ),
			'de' => new AliasGroup( 'de', [ 'bar', 'baz' ] ),
		];
	}

	public function testGivenTwoGroups_listContainsThem() {
		$array = $this->getTwoGroups();

		$list = new AliasGroupList( $array );

		$this->assertSame( $array, iterator_to_array( $list ) );
	}

	public function testGivenGroupsWithTheSameLanguage_onlyTheLastOnesAreRetained() {
		$array = [
			new AliasGroup( 'en', [ 'foo' ] ),
			new AliasGroup( 'en', [ 'bar' ] ),

			new AliasGroup( 'de', [ 'baz' ] ),

			new AliasGroup( 'nl', [ 'bah' ] ),
			new AliasGroup( 'nl', [ 'blah' ] ),
			new AliasGroup( 'nl', [ 'spam' ] ),
		];

		$list = new AliasGroupList( $array );

		$this->assertEquals(
			[
				'en' => new AliasGroup( 'en', [ 'bar' ] ),
				'de' => new AliasGroup( 'de', [ 'baz' ] ),
				'nl' => new AliasGroup( 'nl', [ 'spam' ] ),
			],
			iterator_to_array( $list )
		);
	}

	public function testCanIterateOverList() {
		$group = new AliasGroup( 'en', [ 'foo' ] );

		$list = new AliasGroupList( [ $group ] );

		/**
		 * @var AliasGroup $aliasGroup
		 */
		foreach ( $list as $key => $aliasGroup ) {
			$this->assertEquals( $group, $aliasGroup );
			$this->assertSame( $aliasGroup->getLanguageCode(), $key );
		}
	}

	public function testGivenNonAliasGroups_constructorThrowsException() {
		$this->expectException( InvalidArgumentException::class );
		new AliasGroupList( [ null ] );
	}

	public function testGivenSetLanguageCode_getByLanguageReturnsGroup() {
		$enGroup = new AliasGroup( 'en', [ 'foo' ] );

		$list = new AliasGroupList( [
			new AliasGroup( 'de' ),
			$enGroup,
			new AliasGroup( 'nl' ),
		] );

		$this->assertEquals( $enGroup, $list->getByLanguage( 'en' ) );
	}

	/**
	 * @dataProvider invalidLanguageCodeProvider
	 */
	public function testGivenInvalidLanguageCode_getByLanguageThrowsException( $languageCode ) {
		$list = new AliasGroupList();
		$this->expectException( OutOfBoundsException::class );
		$list->getByLanguage( $languageCode );
	}

	public function testGivenNonSetLanguageCode_getByLanguageThrowsException() {
		$list = new AliasGroupList();

		$this->expectException( OutOfBoundsException::class );
		$list->getByLanguage( 'en' );
	}

	public function testGivenGroupForNewLanguage_setGroupAddsGroup() {
		$enGroup = new AliasGroup( 'en', [ 'foo', 'bar' ] );
		$deGroup = new AliasGroup( 'de', [ 'baz', 'bah' ] );

		$list = new AliasGroupList( [ $enGroup ] );
		$expectedList = new AliasGroupList( [ $enGroup, $deGroup ] );

		$list->setGroup( $deGroup );

		$this->assertEquals( $expectedList, $list );
	}

	public function testGivenLabelForExistingLanguage_setLabelReplacesLabel() {
		$enGroup = new AliasGroup( 'en', [ 'foo', 'bar' ] );
		$newEnGroup = new AliasGroup( 'en', [ 'foo', 'bar', 'bah' ] );

		$list = new AliasGroupList( [ $enGroup ] );
		$expectedList = new AliasGroupList( [ $newEnGroup ] );

		$list->setGroup( $newEnGroup );
		$this->assertEquals( $expectedList, $list );
	}

	public function testGivenNotSetLanguage_removeByLanguageIsNoOp() {
		$list = new AliasGroupList( [ new AliasGroup( 'en', [ 'foo', 'bar' ] ) ] );
		$originalList = clone $list;

		$list->removeByLanguage( 'de' );

		$this->assertEquals( $originalList, $list );
	}

	public function testGivenSetLanguage_removeByLanguageRemovesIt() {
		$list = new AliasGroupList( [ new AliasGroup( 'en', [ 'foo', 'bar' ] ) ] );

		$list->removeByLanguage( 'en' );

		$this->assertTrue( $list->isEmpty() );
	}

	/**
	 * @dataProvider invalidLanguageCodeProvider
	 */
	public function testGivenInvalidLanguageCode_removeByLanguageIsNoOp( $languageCode ) {
		$list = new AliasGroupList( [ new AliasGroup( 'en', [ 'foo' ] ) ] );
		$list->removeByLanguage( $languageCode );
		$this->assertFalse( $list->isEmpty() );
	}

	public function testGivenEmptyGroups_constructorRemovesThem() {
		$list = new AliasGroupList( [
			new AliasGroup( 'de' ),
			new AliasGroup( 'en', [ 'foo' ] ),
			new AliasGroup( 'en' ),
		] );

		$this->assertTrue( $list->isEmpty() );
	}

	public function testGivenEmptyGroup_setGroupRemovesGroup() {
		$list = new AliasGroupList( [
			new AliasGroup( 'en', [ 'foo' ] ),
		] );

		$list->setGroup( new AliasGroup( 'en' ) );
		$list->setGroup( new AliasGroup( 'de' ) );

		$this->assertEquals( new AliasGroupList(), $list );
	}

	public function testEmptyListEqualsEmptyList() {
		$list = new AliasGroupList();
		$this->assertTrue( $list->equals( new AliasGroupList() ) );
	}

	public function testFilledListEqualsItself() {
		$list = new AliasGroupList( [
			new AliasGroup( 'en', [ 'foo' ] ),
			new AliasGroup( 'de', [ 'bar' ] ),
		] );

		$this->assertTrue( $list->equals( $list ) );
		$this->assertTrue( $list->equals( clone $list ) );
	}

	public function testDifferentListsDoNotEqual() {
		$list = new AliasGroupList( [
			new AliasGroup( 'en', [ 'foo' ] ),
			new AliasGroup( 'de', [ 'bar' ] ),
		] );

		$this->assertFalse( $list->equals( new AliasGroupList() ) );

		$this->assertFalse( $list->equals(
			new AliasGroupList( [
				new AliasGroup( 'en', [ 'foo' ] ),
				new AliasGroup( 'de', [ 'bar' ] ),
				new AliasGroup( 'nl', [ 'baz' ] ),
			] )
		) );
	}

	public function testGivenNonAliasGroupList_equalsReturnsFalse() {
		$list = new AliasGroupList();
		$this->assertFalse( $list->equals( null ) );
		$this->assertFalse( $list->equals( new \stdClass() ) );
	}

	public function testGivenListsThatOnlyDifferInOrder_equalsReturnsTrue() {
		$list = new AliasGroupList( [
			new AliasGroup( 'en', [ 'foo' ] ),
			new AliasGroup( 'de', [ 'bar' ] ),
		] );

		$this->assertTrue( $list->equals(
			new AliasGroupList( [
				new AliasGroup( 'de', [ 'bar' ] ),
				new AliasGroup( 'en', [ 'foo' ] ),
			] )
		) );
	}

	public function testGivenNonSetLanguageGroup_hasAliasGroupReturnsFalse() {
		$list = new AliasGroupList();
		$this->assertFalse( $list->hasAliasGroup( new AliasGroup( 'en', [ 'kittens' ] ) ) );
	}

	public function testGivenMismatchingGroup_hasAliasGroupReturnsFalse() {
		$list = new AliasGroupList( [ new AliasGroup( 'en', [ 'cats' ] ) ] );
		$this->assertFalse( $list->hasAliasGroup( new AliasGroup( 'en', [ 'kittens' ] ) ) );
	}

	public function testGivenMatchingGroup_hasAliasGroupReturnsTrue() {
		$list = new AliasGroupList( [ new AliasGroup( 'en', [ 'kittens' ] ) ] );
		$this->assertTrue( $list->hasAliasGroup( new AliasGroup( 'en', [ 'kittens' ] ) ) );
	}

	public function testGivenNonSetLanguageGroup_hasGroupForLanguageReturnsFalse() {
		$list = new AliasGroupList();
		$this->assertFalse( $list->hasGroupForLanguage( 'en' ) );
	}

	/**
	 * @dataProvider invalidLanguageCodeProvider
	 */
	public function testGivenInvalidLanguageCode_hasGroupForLanguageReturnsFalse( $languageCode ) {
		$list = new AliasGroupList();
		$this->assertFalse( $list->hasGroupForLanguage( $languageCode ) );
	}

	public function invalidLanguageCodeProvider() {
		return [
			[ null ],
			[ 21 ],
			[ '' ],
		];
	}

	public function testGivenMismatchingGroup_hasGroupForLanguageReturnsFalse() {
		$list = new AliasGroupList( [ new AliasGroup( 'en', [ 'cats' ] ) ] );
		$this->assertFalse( $list->hasGroupForLanguage( 'de' ) );
	}

	public function testGivenMatchingGroup_hasGroupForLanguageReturnsTrue() {
		$list = new AliasGroupList( [ new AliasGroup( 'en', [ 'kittens' ] ) ] );
		$this->assertTrue( $list->hasGroupForLanguage( 'en' ) );
	}

	public function testGivenAliasGroupArgs_setGroupTextsSetsAliasGroup() {
		$list = new AliasGroupList();

		$list->setAliasesForLanguage( 'en', [ 'foo', 'bar' ] );

		$this->assertEquals(
			new AliasGroup( 'en', [ 'foo', 'bar' ] ),
			$list->getByLanguage( 'en' )
		);
	}

	public function testGivenInvalidLanguageCode_setGroupTextsThrowsException() {
		$list = new AliasGroupList();

		$this->expectException( InvalidArgumentException::class );
		$list->setAliasesForLanguage( null, [ 'foo', 'bar' ] );
	}

	public function testGivenInvalidAliases_setGroupTextsThrowsException() {
		$list = new AliasGroupList();

		$this->expectException( InvalidArgumentException::class );
		$list->setAliasesForLanguage( 'en', [ 'foo', null ] );
	}

	public function testToArray() {
		$array = [
			'en' => new AliasGroup( 'en', [ 'foo' ] ),
			'de' => new AliasGroup( 'de', [ 'bar' ] ),
			'nl' => new AliasGroup( 'nl', [ 'baz' ] ),
		];

		$list = new AliasGroupList( $array );

		$this->assertSame( $array, $list->toArray() );
	}

	public function testGivenEmptyList_getWithLanguagesReturnsEmptyList() {
		$list = new AliasGroupList();
		$this->assertEquals( new AliasGroupList(), $list->getWithLanguages( [] ) );
		$this->assertEquals( new AliasGroupList(), $list->getWithLanguages( [ 'en', 'de' ] ) );
	}

	public function testGivenNoLanguages_getWithLanguagesReturnsEmptyList() {
		$list = new AliasGroupList();
		$list->setAliasesForLanguage( 'en', [ 'foo' ] );
		$list->setAliasesForLanguage( 'de', [ 'bar' ] );

		$this->assertEquals( new AliasGroupList(), $list->getWithLanguages( [] ) );
	}

	public function testGivenAllLanguages_getWithLanguagesReturnsFullList() {
		$list = new AliasGroupList();
		$list->setAliasesForLanguage( 'en', [ 'foo' ] );
		$list->setAliasesForLanguage( 'de', [ 'bar' ] );

		$this->assertEquals( $list, $list->getWithLanguages( [ 'en', 'de' ] ) );
	}

	public function testGivenSomeLanguages_getWithLanguagesReturnsPartialList() {
		$list = new AliasGroupList();
		$list->setAliasesForLanguage( 'en', [ 'foo' ] );
		$list->setAliasesForLanguage( 'de', [ 'bar' ] );
		$list->setAliasesForLanguage( 'nl', [ 'baz' ] );
		$list->setAliasesForLanguage( 'fr', [ 'hax' ] );

		$expectedList = new AliasGroupList();
		$expectedList->setAliasesForLanguage( 'en', [ 'foo' ] );
		$expectedList->setAliasesForLanguage( 'nl', [ 'baz' ] );

		$this->assertEquals( $expectedList, $list->getWithLanguages( [ 'en', 'nl' ] ) );
	}

	public function testToTextArray() {
		$list = new AliasGroupList();
		$list->setAliasesForLanguage( 'en', [ 'foo', 'baz' ] );
		$list->setAliasesForLanguage( 'de', [ 'bar' ] );

		$expected = [
			'en' => [ 'foo', 'baz' ],
			'de' => [ 'bar' ],
		];

		$this->assertEquals( $expected, $list->toTextArray() );
	}

	public function testClear() {
		$list = new AliasGroupList();
		$list->setAliasesForLanguage( 'en', [ 'foo', 'baz' ] );
		$list->setAliasesForLanguage( 'de', [ 'bar' ] );

		$list->clear();

		$this->assertEquals( new AliasGroupList(), $list );
	}

}
