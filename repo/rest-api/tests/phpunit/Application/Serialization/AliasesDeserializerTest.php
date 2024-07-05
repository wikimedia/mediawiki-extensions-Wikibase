<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Serialization;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\EmptyAliasException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldException;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class AliasesDeserializerTest extends TestCase {

	/**
	 * @dataProvider provideValidAliases
	 */
	public function testDeserialize( array $serialization, AliasGroupList $expectedAliases ): void {
		$this->assertEquals( $expectedAliases, ( new AliasesDeserializer() )->deserialize( $serialization ) );
	}

	public static function provideValidAliases(): Generator {
		yield 'no aliases' => [
			[],
			new AliasGroupList(),
		];

		yield 'aliases in multiple languages' => [
			[
				'en' => [ 'English alias ', 'alias in English' ],
				'de' => [ 'Deutscher Pseudonym' ],
			],
			new AliasGroupList( [
				new AliasGroup( 'en', [ 'English alias ', 'alias in English' ] ),
				new AliasGroup( 'de', [ 'Deutscher Pseudonym' ] ),
			] ),
		];

		yield 'aliases with leading/trailing whitespace' => [
			[
				'en' => [ '  space before', 'space after ', "tab space\t" ],
				'de' => [ ' Leerzeichen  ' ],
			],
			new AliasGroupList( [
				new AliasGroup( 'en', [ '  space before', 'space after ', "tab space\t" ] ),
				new AliasGroup( 'de', [ 'Leerzeichen' ] ),
			] ),
		];
	}

	/**
	 * @dataProvider provideEmptyAlias
	 */
	public function testGivenEmptyAlias_throwsException( array $aliasesListWithEmptyAlias, string $language, int $index ): void {
		try {
			( new AliasesDeserializer() )->deserialize( $aliasesListWithEmptyAlias );
			$this->fail( 'this should not be reached' );
		} catch ( EmptyAliasException $e ) {
			$this->assertSame( $language, $e->getLanguage() );
			$this->assertSame( $index, $e->getIndex() );
		}
	}

	public static function provideEmptyAlias(): Generator {
		yield 'empty alias in pos 0' => [ [ 'en' => [ '' ] ], 'en', 0 ];
		yield 'empty alias in pos 1' => [ [ 'en' => [ 'foo', '' ] ], 'en', 1 ];
		yield 'whitespace alias' => [ [ 'en' => [ '   ' ] ], 'en', 0 ];
		yield 'whitespace with tab alias' => [ [ 'en' => [ " \t " ] ], 'en', 0 ];
	}

	public function testGivenInvalidAliasType_throwsException(): void {
		try {
			( new AliasesDeserializer() )->deserialize( [ 'en' => [ 123 ] ] );
			$this->fail( 'this should not be reached' );
		} catch ( InvalidFieldException $e ) {
			$this->assertSame( 'en', $e->getField() );
			$this->assertSame( 'en/0', $e->getPath() );
			$this->assertSame( 123, $e->getValue() );
		}
	}

	public function testGivenInvalidAliasesType_throwsException(): void {
		$invalidAliasesInLanguage = [ 'associative' => 'not a list' ];
		try {
			( new AliasesDeserializer() )->deserialize( [ 'en' => $invalidAliasesInLanguage ] );
			$this->fail( 'this should not be reached' );
		} catch ( InvalidFieldException $e ) {
			$this->assertSame( 'en', $e->getField() );
			$this->assertSame( $invalidAliasesInLanguage, $e->getValue() );
		}
	}

}
