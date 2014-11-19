<?php

namespace Wikibase\DataModel\Term\Test;

use Wikibase\DataModel\Term\AliasGroup;

/**
 * @covers Wikibase\DataModel\Term\AliasGroup
 *
 * @licence GNU GPL v2+
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

	public function testGivenInvalidLanguageCode_constructorThrowsException() {
		$this->setExpectedException( 'InvalidArgumentException' );
		new AliasGroup( null, array( 'foo' ) );
	}

	public function testGivenInvalidAlias_constructorThrowsException() {
		$this->setExpectedException( 'InvalidArgumentException' );
		new AliasGroup( 'en', array( 21 ) );
	}

}
