<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Serialization;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\EmptyAliasException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidAliasesInLanguageException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\SerializationException;

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
	 * @dataProvider provideInvalidAliases
	 */
	public function testGivenInvalidAliases_throwsException(
		SerializationException $expectedException,
		array $invalidAliases
	): void {
		try {
			( new AliasesDeserializer() )->deserialize( $invalidAliases );
			$this->fail( 'Expected exception was not thrown' );
		} catch ( SerializationException $e ) {
			$this->assertEquals( $expectedException, $e );
		}
	}

	public function provideInvalidAliases(): Generator {
		yield "invalid 'aliases in language' - string" => [
			new InvalidAliasesInLanguageException( 'de', 'this should be a list of strings', 'de' ),
			[ 'en' => [ 'list', 'of', 'aliases' ], 'de' => 'this should be a list of strings' ],
		];

		yield "invalid 'aliases in language' - associative array" => [
			new InvalidAliasesInLanguageException( 'de', [ 'not' => 'a', 'sequential' => 'array' ], 'de' ),
			[ 'en' => [ 'list', 'of', 'aliases' ], 'de' => [ 'not' => 'a', 'sequential' => 'array' ] ],
		];

		yield "invalid 'alias' type - integer" => [
			new InvalidAliasesInLanguageException( 'en', 9183, 'en/0' ),
			[ 'en' => [ 9183, 'list', 'of', 'aliases' ] ],
		];

		yield "invalid 'alias' value - zero length string" => [
			new EmptyAliasException( 'en', 1 ),
			[ 'en' => [ 'list', '', 'of', 'aliases' ] ],
		];

		yield "invalid 'alias' value - four spaces" => [
			new EmptyAliasException( 'en', 2 ),
			[ 'en' => [ 'list', 'of', '    ', 'aliases' ] ],
		];

		yield "invalid 'alias' value - spaces and tab" => [
			new EmptyAliasException( 'en', 3 ),
			[ 'en' => [ 'list', 'of', 'aliases', "  \t  " ] ],
		];
	}

}
