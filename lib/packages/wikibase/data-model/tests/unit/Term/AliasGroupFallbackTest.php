<?php

namespace Wikibase\DataModel\Tests\Term;

use InvalidArgumentException;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupFallback;

/**
 * @covers \Wikibase\DataModel\Term\AliasGroupFallback
 *
 * @license GPL-2.0-or-later
 * @author Jan Zerebecki < jan.wikimedia@zerebecki.de >
 */
class AliasGroupFallbackTest extends \PHPUnit\Framework\TestCase {

	public function testConstructorSetsValues() {
		$language = 'en-real';
		$aliases = [ 'foo', 'bar', 'baz' ];
		$actual = 'en-actual';
		$source = 'en-source';

		$group = new AliasGroupFallback( $language, $aliases, $actual, $source );

		$this->assertSame( $language, $group->getLanguageCode() );
		$this->assertSame( $aliases, $group->getAliases() );
		$this->assertSame( $actual, $group->getActualLanguageCode() );
		$this->assertSame( $source, $group->getSourceLanguageCode() );
	}

	public function testConstructorWithNullForSource() {
		$language = 'en-real';
		$aliases = [];
		$actual = 'en-actual';
		$source = null;

		$group = new AliasGroupFallback( $language, $aliases, $actual, $source );

		$this->assertSame( $source, $group->getSourceLanguageCode() );
	}

	/**
	 * @dataProvider invalidLanguageCodeProvider
	 */
	public function testGivenInvalidActualLanguageCode_constructorThrowsException( $languageCode ) {
		$this->expectException( InvalidArgumentException::class );
		new AliasGroupFallback( 'en-real', [], $languageCode, 'en-source' );
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
		new AliasGroupFallback( 'en-real', [], 'en-actual', $languageCode );
	}

	public function invalidSourceLanguageCodeProvider() {
		return [
			[ 21 ],
			[ '' ],
		];
	}

	public function testGroupEqualsItself() {
		$group = new AliasGroupFallback( 'en-real', [ 'foo', 'bar' ], 'en-actual', 'en-source' );

		$this->assertTrue( $group->equals( $group ) );
		$this->assertTrue( $group->equals( clone $group ) );
	}

	/**
	 * @dataProvider inequalAliasGroupProvider
	 */
	public function testGroupDoesNotEqualOnesWithMoreOrFewerValues( $inequalGroup ) {
		$group = new AliasGroupFallback( 'en-real', [ 'foo' ], 'en-actual', 'en-source' );

		$this->assertFalse( $group->equals( $inequalGroup ) );
	}

	public function inequalAliasGroupProvider() {
		return [
			'aliases' => [ new AliasGroupFallback( 'en-real', [ 'moo' ], 'en-actual', 'en-source' ) ],
			'language' => [ new AliasGroupFallback( 'en-moo', [ 'foo' ], 'en-actual', 'en-source' ) ],
			'actualLanguage' => [ new AliasGroupFallback( 'en-real', [ 'foo' ], 'en-moo', 'en-source' ) ],
			'sourceLanguage' => [ new AliasGroupFallback( 'en-real', [ 'foo' ], 'en-actual', 'en-moo' ) ],
			'null sourceLanguage' => [ new AliasGroupFallback( 'en-real', [ 'foo' ], 'en-actual', null ) ],
			'all' => [ new AliasGroupFallback( 'en-moo', [ 'moo' ], 'en-moo', 'en-moo' ) ],
			'class AliasGroup' => [ new AliasGroup( 'en-real', [ 'foo' ] ) ],
		];
	}

}
