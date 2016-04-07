<?php

namespace Wikibase\DataModel\Tests\Term;

use InvalidArgumentException;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupFallback;

/**
 * @covers Wikibase\DataModel\Term\AliasGroup
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class AliasGroupTest extends \PHPUnit_Framework_TestCase {

	public function testConstructorSetsValues() {
		$language = 'en';
		$aliases = array( 'foo', 'bar', 'baz' );

		$group = new AliasGroup( $language, $aliases );

		$this->assertEquals( $language, $group->getLanguageCode() );
		$this->assertEquals( $aliases, $group->getAliases() );
	}

	public function testIsEmpty() {
		$emptyGroup = new AliasGroup( 'en' );
		$this->assertTrue( $emptyGroup->isEmpty() );

		$filledGroup = new AliasGroup( 'en', array( 'foo' ) );
		$this->assertFalse( $filledGroup->isEmpty() );
	}

	public function testGroupEqualsItself() {
		$group = new AliasGroup( 'en', array( 'foo', 'bar' ) );

		$this->assertTrue( $group->equals( $group ) );
		$this->assertTrue( $group->equals( clone $group ) );
	}

	public function testGroupDoesNotEqualOnesWithMoreOrFewerValues() {
		$group = new AliasGroup( 'en', array( 'foo', 'bar' ) );

		$this->assertFalse( $group->equals( new AliasGroup( 'en', array( 'foo' ) ) ) );
		$this->assertFalse( $group->equals( new AliasGroup( 'en', array( 'foo', 'bar', 'baz' ) ) ) );
	}

	public function testGroupDoesNotEqualWhenLanguageMismatches() {
		$group = new AliasGroup( 'en', array( 'foo', 'bar' ) );

		$this->assertFalse( $group->equals( new AliasGroup( 'de', array( 'foo', 'bar' ) ) ) );
		$this->assertFalse( $group->equals( new AliasGroup( 'de' ) ) );
	}

	public function testGroupDoesNotEqualWhenOrderIsDifferent() {
		$group = new AliasGroup( 'en', array( 'foo', 'bar', 'baz' ) );

		$this->assertFalse( $group->equals( new AliasGroup( 'en', array( 'foo', 'baz', 'bar' ) ) ) );
		$this->assertFalse( $group->equals( new AliasGroup( 'en', array( 'baz', 'bar', 'foo' ) ) ) );
	}

	public function testGivenSimilarFallbackObject_equalsReturnsFalse() {
		$aliasGroup = new AliasGroup( 'de' );
		$aliasGroupFallback = new AliasGroupFallback( 'de', array(), 'en', null );
		$this->assertFalse( $aliasGroup->equals( $aliasGroupFallback ) );
	}

	public function testDuplicatesAreRemoved() {
		$group = new AliasGroup( 'en', array( 'foo', 'bar', 'spam', 'spam', 'spam', 'foo' ) );

		$expectedGroup = new AliasGroup( 'en', array( 'foo', 'bar', 'spam' ) );

		$this->assertEquals( $expectedGroup, $group );
	}

	public function testIsCountable() {
		$this->assertCount( 0, new AliasGroup( 'en' ) );
		$this->assertCount( 1, new AliasGroup( 'en', array( 'foo' ) ) );
		$this->assertCount( 2, new AliasGroup( 'en', array( 'foo', 'bar' ) ) );
	}

	public function testGivenEmptyStringAlias_aliasIsRemoved() {
		$group = new AliasGroup( 'en', array( 'foo', '', 'bar', '  ' ) );

		$expectedGroup = new AliasGroup( 'en', array( 'foo', 'bar' ) );

		$this->assertEquals( $expectedGroup, $group );
	}

	public function testAliasesAreTrimmed() {
		$group = new AliasGroup( 'en', array( ' foo', 'bar ', '   baz   ' ) );

		$expectedGroup = new AliasGroup( 'en', array( 'foo', 'bar', 'baz' ) );

		$this->assertEquals( $expectedGroup, $group );
	}

	/**
	 * @dataProvider invalidLanguageCodeProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testGivenInvalidLanguageCode_constructorThrowsException( $languageCode ) {
		new AliasGroup( $languageCode, array( 'foo' ) );
	}

	public function invalidLanguageCodeProvider() {
		return array(
			array( null ),
			array( 21 ),
			array( '' ),
		);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testGivenInvalidAlias_constructorThrowsException() {
		new AliasGroup( 'en', array( 21 ) );
	}

}
