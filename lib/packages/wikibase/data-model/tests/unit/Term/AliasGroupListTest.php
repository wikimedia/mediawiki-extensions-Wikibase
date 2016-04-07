<?php

namespace Wikibase\DataModel\Tests\Term;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;

/**
 * @covers Wikibase\DataModel\Term\AliasGroupList
 * @uses Wikibase\DataModel\Term\AliasGroup
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class AliasGroupListTest extends PHPUnit_Framework_TestCase {

	public function testIsEmpty() {
		$list = new AliasGroupList();
		$this->assertTrue( $list->isEmpty() );

		$list = new AliasGroupList( array( new AliasGroup( 'en', array( 'foo' ) ) ) );
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
		return array(
			'en' => new AliasGroup( 'en', array( 'foo' ) ),
			'de' => new AliasGroup( 'de', array( 'bar', 'baz' ) ),
		);
	}

	public function testGivenTwoGroups_listContainsThem() {
		$array = $this->getTwoGroups();

		$list = new AliasGroupList( $array );

		$this->assertEquals( $array, iterator_to_array( $list ) );
	}

	public function testGivenGroupsWithTheSameLanguage_onlyTheLastOnesAreRetained() {
		$array = array(
			new AliasGroup( 'en', array( 'foo' ) ),
			new AliasGroup( 'en', array( 'bar' ) ),

			new AliasGroup( 'de', array( 'baz' ) ),

			new AliasGroup( 'nl', array( 'bah' ) ),
			new AliasGroup( 'nl', array( 'blah' ) ),
			new AliasGroup( 'nl', array( 'spam' ) ),
		);

		$list = new AliasGroupList( $array );

		$this->assertEquals(
			array(
				'en' => new AliasGroup( 'en', array( 'bar' ) ),
				'de' => new AliasGroup( 'de', array( 'baz' ) ),
				'nl' => new AliasGroup( 'nl', array( 'spam' ) ),
			),
			iterator_to_array( $list )
		);
	}

	public function testCanIterateOverList() {
		$group = new AliasGroup( 'en', array( 'foo' ) );

		$list = new AliasGroupList( array( $group ) );

		/**
		 * @var AliasGroup $aliasGroup
		 */
		foreach ( $list as $key => $aliasGroup ) {
			$this->assertEquals( $group, $aliasGroup );
			$this->assertEquals( $aliasGroup->getLanguageCode(), $key );
		}
	}

	public function testGivenNonAliasGroups_constructorThrowsException() {
		$this->setExpectedException( 'InvalidArgumentException' );
		new AliasGroupList( array( null ) );
	}

	public function testGivenSetLanguageCode_getByLanguageReturnsGroup() {
		$enGroup = new AliasGroup( 'en', array( 'foo' ) );

		$list = new AliasGroupList( array(
			new AliasGroup( 'de' ),
			$enGroup,
			new AliasGroup( 'nl' ),
		) );

		$this->assertEquals( $enGroup, $list->getByLanguage( 'en' ) );
	}

	/**
	 * @dataProvider invalidLanguageCodeProvider
	 */
	public function testGivenInvalidLanguageCode_getByLanguageThrowsException( $languageCode ) {
		$list = new AliasGroupList();
		$this->setExpectedException( 'OutOfBoundsException' );
		$list->getByLanguage( $languageCode );
	}

	public function testGivenNonSetLanguageCode_getByLanguageThrowsException() {
		$list = new AliasGroupList();

		$this->setExpectedException( 'OutOfBoundsException' );
		$list->getByLanguage( 'en' );
	}

	public function testGivenGroupForNewLanguage_setGroupAddsGroup() {
		$enGroup = new AliasGroup( 'en', array( 'foo', 'bar' ) );
		$deGroup = new AliasGroup( 'de', array( 'baz', 'bah' ) );

		$list = new AliasGroupList( array( $enGroup ) );
		$expectedList = new AliasGroupList( array( $enGroup, $deGroup ) );

		$list->setGroup( $deGroup );

		$this->assertEquals( $expectedList, $list );
	}

	public function testGivenLabelForExistingLanguage_setLabelReplacesLabel() {
		$enGroup = new AliasGroup( 'en', array( 'foo', 'bar' ) );
		$newEnGroup = new AliasGroup( 'en', array( 'foo', 'bar', 'bah' ) );

		$list = new AliasGroupList( array( $enGroup ) );
		$expectedList = new AliasGroupList( array( $newEnGroup ) );

		$list->setGroup( $newEnGroup );
		$this->assertEquals( $expectedList, $list );
	}

	public function testGivenNotSetLanguage_removeByLanguageIsNoOp() {
		$list = new AliasGroupList( array( new AliasGroup( 'en', array( 'foo', 'bar' ) ) ) );
		$originalList = clone $list;

		$list->removeByLanguage( 'de' );

		$this->assertEquals( $originalList, $list );
	}

	public function testGivenSetLanguage_removeByLanguageRemovesIt() {
		$list = new AliasGroupList( array( new AliasGroup( 'en', array( 'foo', 'bar' ) ) ) );

		$list->removeByLanguage( 'en' );

		$this->assertEquals( new AliasGroupList(), $list );
	}

	/**
	 * @dataProvider invalidLanguageCodeProvider
	 */
	public function testGivenInvalidLanguageCode_removeByLanguageIsNoOp( $languageCode ) {
		$list = new AliasGroupList( array( new AliasGroup( 'en', array( 'foo' ) ) ) );
		$list->removeByLanguage( $languageCode );
		$this->assertFalse( $list->isEmpty() );
	}

	public function testGivenEmptyGroups_constructorRemovesThem() {
		$enGroup = new AliasGroup( 'en', array( 'foo' ) );

		$list = new AliasGroupList( array(
			new AliasGroup( 'de' ),
			$enGroup,
			new AliasGroup( 'en' ),
			new AliasGroup( 'nl' ),
		) );

		$expectedList = new AliasGroupList( array(
			new AliasGroup( 'en' ),
		) );

		$this->assertEquals( $expectedList, $list );
	}

	public function testGivenEmptyGroup_setGroupRemovesGroup() {
		$list = new AliasGroupList( array(
			new AliasGroup( 'en', array( 'foo' ) ),
		) );

		$expectedList = new AliasGroupList();

		$list->setGroup( new AliasGroup( 'en' ) );
		$list->setGroup( new AliasGroup( 'de' ) );

		$this->assertEquals( $expectedList, $list );
	}

	public function testEmptyListEqualsEmptyList() {
		$list = new AliasGroupList();
		$this->assertTrue( $list->equals( new AliasGroupList() ) );
	}

	public function testFilledListEqualsItself() {
		$list = new AliasGroupList( array(
			new AliasGroup( 'en', array( 'foo' ) ),
			new AliasGroup( 'de', array( 'bar' ) ),
		) );

		$this->assertTrue( $list->equals( $list ) );
		$this->assertTrue( $list->equals( clone $list ) );
	}

	public function testDifferentListsDoNotEqual() {
		$list = new AliasGroupList( array(
			new AliasGroup( 'en', array( 'foo' ) ),
			new AliasGroup( 'de', array( 'bar' ) ),
		) );

		$this->assertFalse( $list->equals( new AliasGroupList() ) );

		$this->assertFalse( $list->equals(
			new AliasGroupList( array(
				new AliasGroup( 'en', array( 'foo' ) ),
				new AliasGroup( 'de', array( 'bar' ) ),
				new AliasGroup( 'nl', array( 'baz' ) ),
			) )
		) );
	}

	public function testGivenNonAliasGroupList_equalsReturnsFalse() {
		$list = new AliasGroupList();
		$this->assertFalse( $list->equals( null ) );
		$this->assertFalse( $list->equals( new \stdClass() ) );
	}

	public function testGivenListsThatOnlyDifferInOrder_equalsReturnsTrue() {
		$list = new AliasGroupList( array(
			new AliasGroup( 'en', array( 'foo' ) ),
			new AliasGroup( 'de', array( 'bar' ) ),
		) );

		$this->assertTrue( $list->equals(
			new AliasGroupList( array(
				new AliasGroup( 'de', array( 'bar' ) ),
				new AliasGroup( 'en', array( 'foo' ) ),
			) )
		) );
	}

	public function testGivenNonSetLanguageGroup_hasAliasGroupReturnsFalse() {
		$list = new AliasGroupList();
		$this->assertFalse( $list->hasAliasGroup( new AliasGroup( 'en', array( 'kittens' ) ) ) );
	}

	public function testGivenMismatchingGroup_hasAliasGroupReturnsFalse() {
		$list = new AliasGroupList( array( new AliasGroup( 'en', array( 'cats' ) ) ) );
		$this->assertFalse( $list->hasAliasGroup( new AliasGroup( 'en', array( 'kittens' ) ) ) );
	}

	public function testGivenMatchingGroup_hasAliasGroupReturnsTrue() {
		$list = new AliasGroupList( array( new AliasGroup( 'en', array( 'kittens' ) ) ) );
		$this->assertTrue( $list->hasAliasGroup( new AliasGroup( 'en', array( 'kittens' ) ) ) );
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
		return array(
			array( null ),
			array( 21 ),
			array( '' ),
		);
	}

	public function testGivenMismatchingGroup_hasGroupForLanguageReturnsFalse() {
		$list = new AliasGroupList( array( new AliasGroup( 'en', array( 'cats' ) ) ) );
		$this->assertFalse( $list->hasGroupForLanguage( 'de' ) );
	}

	public function testGivenMatchingGroup_hasGroupForLanguageReturnsTrue() {
		$list = new AliasGroupList( array( new AliasGroup( 'en', array( 'kittens' ) ) ) );
		$this->assertTrue( $list->hasGroupForLanguage( 'en' ) );
	}

	public function testGivenAliasGroupArgs_setGroupTextsSetsAliasGroup() {
		$list = new AliasGroupList();

		$list->setAliasesForLanguage( 'en', array( 'foo', 'bar' ) );

		$this->assertEquals(
			new AliasGroup( 'en', array( 'foo', 'bar' ) ),
			$list->getByLanguage( 'en' )
		);
	}

	public function testGivenInvalidLanguageCode_setGroupTextsThrowsException() {
		$list = new AliasGroupList();

		$this->setExpectedException( 'InvalidArgumentException' );
		$list->setAliasesForLanguage( null, array( 'foo', 'bar' ) );
	}

	public function testGivenInvalidAliases_setGroupTextsThrowsException() {
		$list = new AliasGroupList();

		$this->setExpectedException( 'InvalidArgumentException' );
		$list->setAliasesForLanguage( 'en', array( 'foo', null ) );
	}

	public function testToArray() {
		$array = array(
			'en' => new AliasGroup( 'en', array( 'foo' ) ),
			'de' => new AliasGroup( 'de', array( 'bar' ) ),
			'nl' => new AliasGroup( 'nl', array( 'baz' ) ),
		);

		$list = new AliasGroupList( $array );

		$this->assertEquals( $array, $list->toArray() );
	}

	public function testGivenEmptyList_getWithLanguagesReturnsEmptyList() {
		$list = new AliasGroupList();
		$this->assertEquals( new AliasGroupList(), $list->getWithLanguages( array() ) );
		$this->assertEquals( new AliasGroupList(), $list->getWithLanguages( array( 'en', 'de' ) ) );
	}

	public function testGivenNoLanguages_getWithLanguagesReturnsEmptyList() {
		$list = new AliasGroupList();
		$list->setAliasesForLanguage( 'en', array( 'foo' ) );
		$list->setAliasesForLanguage( 'de', array( 'bar' ) );

		$this->assertEquals( new AliasGroupList(), $list->getWithLanguages( array() ) );
	}

	public function testGivenAllLanguages_getWithLanguagesReturnsFullList() {
		$list = new AliasGroupList();
		$list->setAliasesForLanguage( 'en', array( 'foo' ) );
		$list->setAliasesForLanguage( 'de', array( 'bar' ) );

		$this->assertEquals( $list, $list->getWithLanguages( array( 'en', 'de' ) ) );
	}

	public function testGivenSomeLanguages_getWithLanguagesReturnsPartialList() {
		$list = new AliasGroupList();
		$list->setAliasesForLanguage( 'en', array( 'foo' ) );
		$list->setAliasesForLanguage( 'de', array( 'bar' ) );
		$list->setAliasesForLanguage( 'nl', array( 'baz' ) );
		$list->setAliasesForLanguage( 'fr', array( 'hax' ) );

		$expectedList = new AliasGroupList();
		$expectedList->setAliasesForLanguage( 'en', array( 'foo' ) );
		$expectedList->setAliasesForLanguage( 'nl', array( 'baz' ) );

		$this->assertEquals( $expectedList, $list->getWithLanguages( array( 'en', 'nl' ) ) );
	}

	public function testToTextArray() {
		$list = new AliasGroupList();
		$list->setAliasesForLanguage( 'en', array( 'foo', 'baz' ) );
		$list->setAliasesForLanguage( 'de', array( 'bar' ) );

		$expected = array(
			'en' => array( 'foo', 'baz' ),
			'de' => array( 'bar' ),
		);

		$this->assertEquals( $expected, $list->toTextArray() );
	}

}
