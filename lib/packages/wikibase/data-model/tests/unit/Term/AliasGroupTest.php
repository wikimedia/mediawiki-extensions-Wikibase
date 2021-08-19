<?php

namespace Wikibase\DataModel\Tests\Term;

use InvalidArgumentException;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupFallback;

/**
 * @covers \Wikibase\DataModel\Term\AliasGroup
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class AliasGroupTest extends \PHPUnit\Framework\TestCase {

	public function testConstructorSetsValues() {
		$language = 'en';
		$aliases = [ 'foo', 'bar', 'baz' ];

		$group = new AliasGroup( $language, $aliases );

		$this->assertSame( $language, $group->getLanguageCode() );
		$this->assertSame( $aliases, $group->getAliases() );
	}

	public function testIsEmpty() {
		$emptyGroup = new AliasGroup( 'en' );
		$this->assertTrue( $emptyGroup->isEmpty() );

		$filledGroup = new AliasGroup( 'en', [ 'foo' ] );
		$this->assertFalse( $filledGroup->isEmpty() );
	}

	public function testGroupEqualsItself() {
		$group = new AliasGroup( 'en', [ 'foo', 'bar' ] );

		$this->assertTrue( $group->equals( $group ) );
		$this->assertTrue( $group->equals( clone $group ) );
	}

	public function testGroupDoesNotEqualOnesWithMoreOrFewerValues() {
		$group = new AliasGroup( 'en', [ 'foo', 'bar' ] );

		$this->assertFalse( $group->equals( new AliasGroup( 'en', [ 'foo' ] ) ) );
		$this->assertFalse( $group->equals( new AliasGroup( 'en', [ 'foo', 'bar', 'baz' ] ) ) );
	}

	public function testGroupDoesNotEqualWhenLanguageMismatches() {
		$group = new AliasGroup( 'en', [ 'foo', 'bar' ] );

		$this->assertFalse( $group->equals( new AliasGroup( 'de', [ 'foo', 'bar' ] ) ) );
		$this->assertFalse( $group->equals( new AliasGroup( 'de' ) ) );
	}

	public function testGroupDoesNotEqualWhenOrderIsDifferent() {
		$group = new AliasGroup( 'en', [ 'foo', 'bar', 'baz' ] );

		$this->assertFalse( $group->equals( new AliasGroup( 'en', [ 'foo', 'baz', 'bar' ] ) ) );
		$this->assertFalse( $group->equals( new AliasGroup( 'en', [ 'baz', 'bar', 'foo' ] ) ) );
	}

	public function testGivenSimilarFallbackObject_equalsReturnsFalse() {
		$aliasGroup = new AliasGroup( 'de' );
		$aliasGroupFallback = new AliasGroupFallback( 'de', [], 'en', null );
		$this->assertFalse( $aliasGroup->equals( $aliasGroupFallback ) );
	}

	public function testDuplicatesAreRemoved() {
		$group = new AliasGroup( 'en', [ 'foo', 'bar', 'spam', 'spam', 'spam', 'foo' ] );

		$expectedGroup = new AliasGroup( 'en', [ 'foo', 'bar', 'spam' ] );

		$this->assertEquals( $expectedGroup, $group );
	}

	public function testIsCountable() {
		$this->assertCount( 0, new AliasGroup( 'en' ) );
		$this->assertCount( 1, new AliasGroup( 'en', [ 'foo' ] ) );
		$this->assertCount( 2, new AliasGroup( 'en', [ 'foo', 'bar' ] ) );
	}

	public function testGivenEmptyStringAlias_aliasIsRemoved() {
		$group = new AliasGroup( 'en', [ 'foo', '', 'bar', '  ' ] );

		$expectedGroup = new AliasGroup( 'en', [ 'foo', 'bar' ] );

		$this->assertEquals( $expectedGroup, $group );
	}

	public function testAliasesAreTrimmed() {
		$group = new AliasGroup( 'en', [ ' foo', 'bar ', '   baz   ' ] );

		$expectedGroup = new AliasGroup( 'en', [ 'foo', 'bar', 'baz' ] );

		$this->assertEquals( $expectedGroup, $group );
	}

	/**
	 * @dataProvider invalidLanguageCodeProvider
	 */
	public function testGivenInvalidLanguageCode_constructorThrowsException( $languageCode ) {
		$this->expectException( InvalidArgumentException::class );
		new AliasGroup( $languageCode, [ 'foo' ] );
	}

	public function invalidLanguageCodeProvider() {
		return [
			[ null ],
			[ 21 ],
			[ '' ],
		];
	}

	public function testGivenInvalidAlias_constructorThrowsException() {
		$this->expectException( InvalidArgumentException::class );
		new AliasGroup( 'en', [ 21 ] );
	}

}
