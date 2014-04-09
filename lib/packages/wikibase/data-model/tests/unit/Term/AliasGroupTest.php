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
		$emptyGroup = new AliasGroup( 'en', array() );
		$this->assertTrue( $emptyGroup->isEmpty() );

		$filledGroup = new AliasGroup( 'en', array( 'foo' ) );
		$this->assertFalse( $filledGroup->isEmpty() );
	}

	public function testEquality() {
		$group = new AliasGroup( 'en', array( 'foo', 'bar' ) );

		$this->assertTrue( $group->equals( $group ) );
		$this->assertTrue( $group->equals( clone $group ) );

		$this->assertFalse( $group->equals( new AliasGroup( 'en', array( 'foo' ) ) ) );
		$this->assertFalse( $group->equals( new AliasGroup( 'de', array( 'foo' ) ) ) );
		$this->assertFalse( $group->equals( new AliasGroup( 'de', array() ) ) );
	}

}
