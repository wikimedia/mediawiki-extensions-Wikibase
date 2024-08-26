<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Serialization;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesInLanguageDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidAliasesInLanguageException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\SerializationException;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Serialization\AliasesInLanguageDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class AliasesInLanguageDeserializerTest extends TestCase {

	/**
	 * @dataProvider provideValidAliases
	 */
	public function testDeserialize( array $serialization, array $expectedAliases ): void {
		$this->assertEquals( $expectedAliases, ( new AliasesInLanguageDeserializer() )->deserialize( $serialization, '/aliases' ) );
	}

	public static function provideValidAliases(): Generator {
		yield 'multiple aliases' => [
			[ 'English alias', 'alias in English' ],
			[ 'English alias', 'alias in English' ],
		];

		yield 'aliases with leading/trailing whitespace' => [
			[ '  space before', 'space after ', "tab whitespace\t" ],
			[ 'space before', 'space after', 'tab whitespace' ],
		];

		yield 'duplicate aliases are ignored' => [
			[ 'alias one', 'alias two', 'alias three', 'alias two' ],
			[ 'alias one', 'alias two', 'alias three' ],
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
			( new AliasesInLanguageDeserializer() )->deserialize( $invalidAliases, $basePath );
			$this->fail( 'Expected exception was not thrown' );
		} catch ( SerializationException $e ) {
			$this->assertEquals( $expectedException, $e );
		}
	}

	public function provideInvalidAliases(): Generator {
		yield 'invalid serialization - associative array' => [
			new InvalidAliasesInLanguageException( '/aliases', [ 'not' => 'a', 'sequential' => 'array' ], '/aliases' ),
			[ 'not' => 'a', 'sequential' => 'array' ],
			'/aliases',
		];

		yield 'invalid serialization - empty array' => [
			new InvalidAliasesInLanguageException( '/item/aliases/de', [], '/item/aliases/de' ),
			[],
			'/item/aliases/de',
		];

		yield 'invalid alias type - integer' => [
			new InvalidFieldException( '0', 9183, '/property/aliases/en/0' ),
			[ 9183, 'list', 'of', 'aliases' ],
			'/property/aliases/en',
		];

		yield 'invalid alias value - zero length string' => [
			new InvalidFieldException( '1', '', '/aliases/1' ),
			[ 'list', '', 'of', 'aliases' ],
			'/aliases',
		];

		yield 'invalid alias value - four spaces' => [
			new InvalidFieldException( '2', '', '/item/aliases/en/2' ),
			[ 'list', 'of', '    ', 'aliases' ],
			'/item/aliases/en',
		];

		yield "invalid 'alias' value - spaces and tab" => [
			new InvalidFieldException( '3', '', '/property/aliases/en/3' ),
			[ 'list', 'of', 'aliases', "  \t  " ],
			'/property/aliases/en',
		];
	}

}
