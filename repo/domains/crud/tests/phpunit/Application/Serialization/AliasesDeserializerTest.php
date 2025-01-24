<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\Serialization;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\Repo\Domains\Crud\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\AliasesInLanguageDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\Exceptions\InvalidFieldException;
use Wikibase\Repo\Domains\Crud\Application\Serialization\Exceptions\SerializationException;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\Serialization\AliasesDeserializer
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
		$this->assertEquals( $expectedAliases, $this->newAliasesDeserializer()->deserialize( $serialization, '' ) );
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

		yield 'duplicate aliases are ignored' => [
			[
				'en' => [ 'alias one', 'alias two', 'alias three', 'alias two' ],
			],
			new AliasGroupList( [
				new AliasGroup( 'en', [ 'alias one', 'alias two', 'alias three' ] ),
			] ),
		];
	}

	/**
	 * @dataProvider provideInvalidAliases
	 */
	public function testGivenInvalidAliases_throwsException(
		SerializationException $expectedException,
		array $invalidAliases,
		string $basePath
	): void {
		try {
			$this->newAliasesDeserializer()->deserialize( $invalidAliases, $basePath );
			$this->fail( 'Expected exception was not thrown' );
		} catch ( SerializationException $e ) {
			$this->assertEquals( $expectedException, $e );
		}
	}

	public static function provideInvalidAliases(): Generator {
		yield "invalid 'aliases' - sequential array" => [
			new InvalidFieldException( 'path', [ 'not', 'an', 'associative', 'array' ], '/base/path' ),
			[ 'not', 'an', 'associative', 'array' ],
			'/base/path',
		];

		yield "invalid 'aliases in language' - string" => [
			new InvalidFieldException( 'de', 'this should be a list of strings', '/item/aliases/de' ),
			[ 'en' => [ 'list', 'of', 'aliases' ], 'de' => 'this should be a list of strings' ],
			'/item/aliases',
		];

		yield "invalid 'aliases in language' - associative array" => [
			new InvalidFieldException( 'de', [ 'not' => 'a', 'sequential' => 'array' ], '/property/aliases/de' ),
			[ 'en' => [ 'list', 'of', 'aliases' ], 'de' => [ 'not' => 'a', 'sequential' => 'array' ] ],
			'/property/aliases',
		];

		yield "invalid 'aliases in language' - associative array and incorrect type for key" => [
			new InvalidFieldException( '5772', [ 'not' => 'a', 'sequential' => 'array' ], '/5772' ),
			[ 'en' => [ 'list', 'of', 'aliases' ], 5772 => [ 'not' => 'a', 'sequential' => 'array' ] ],
			'',
		];

		yield "invalid 'aliases in language' - empty array" => [
			new InvalidFieldException( 'de', [], '/item/aliases/de' ),
			[ 'en' => [ 'list', 'of', 'aliases' ], 'de' => [] ],
			'/item/aliases',
		];

		yield "invalid 'aliases in language' - empty array and incorrect type for key" => [
			new InvalidFieldException( '6071', [], '/property/aliases/6071' ),
			[ 'en' => [ 'list', 'of', 'aliases' ], 6071 => [] ],
			'/property/aliases',
		];

		yield "invalid 'alias' type - integer and incorrect type for key" => [
			new InvalidFieldException( '0', 9183, '/5593/0' ),
			[ 5593 => [ 9183, 'list', 'of', 'aliases' ] ],
			'',
		];

		yield "invalid 'alias' type - integer" => [
			new InvalidFieldException( '0', 9183, '/item/aliases/en/0' ),
			[ 'en' => [ 9183, 'list', 'of', 'aliases' ] ],
			'/item/aliases',
		];

		yield "invalid 'alias' value - zero length string" => [
			new InvalidFieldException( '1', '', '/property/aliases/en/1' ),
			[ 'en' => [ 'list', '', 'of', 'aliases' ] ],
			'/property/aliases',
		];

		yield "invalid 'alias' value - zero length string and incorrect type for key" => [
			new InvalidFieldException( '1', '', '/9667/1' ),
			[ 9667 => [ 'list', '', 'of', 'aliases' ] ],
			'',
		];

		yield "invalid 'alias' value - four spaces" => [
			new InvalidFieldException( '2', '', '/item/aliases/en/2' ),
			[ 'en' => [ 'list', 'of', '    ', 'aliases' ] ],
			'/item/aliases',
		];

		yield "invalid 'alias' value - spaces and tab" => [
			new InvalidFieldException( '3', '', '/property/aliases/en/3' ),
			[ 'en' => [ 'list', 'of', 'aliases', "  \t  " ] ],
			'/property/aliases',
		];
	}

	private function newAliasesDeserializer(): AliasesDeserializer {
		return new AliasesDeserializer( new AliasesInLanguageDeserializer() );
	}

}
