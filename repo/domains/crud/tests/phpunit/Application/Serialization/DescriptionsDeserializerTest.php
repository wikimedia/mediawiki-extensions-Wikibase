<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\Serialization;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\Domains\Crud\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\Exceptions\EmptyDescriptionException;
use Wikibase\Repo\Domains\Crud\Application\Serialization\Exceptions\InvalidDescriptionException;
use Wikibase\Repo\Domains\Crud\Application\Serialization\Exceptions\SerializationException;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\Serialization\DescriptionsDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DescriptionsDeserializerTest extends TestCase {

	/**
	 * @dataProvider descriptionsProvider
	 */
	public function testDeserialize( array $serialization, TermList $expectedDescriptions ): void {
		$this->assertEquals( $expectedDescriptions, ( new DescriptionsDeserializer() )->deserialize( $serialization ) );
	}

	public static function descriptionsProvider(): Generator {
		yield 'no descriptions' => [
			[],
			new TermList(),
		];

		yield 'multiple descriptions' => [
			[ 'en' => 'english description', 'de' => 'Deutsche Beschreibung' ],
			new TermList( [ new Term( 'en', 'english description' ), new Term( 'de', 'Deutsche Beschreibung' ) ] ),
		];

		yield 'descriptions with leading/trailing whitespace' => [
			[ 'en' => '  space', 'de' => " \tLeerzeichen  " ],
			new TermList( [ new Term( 'en', 'space' ), new Term( 'de', 'Leerzeichen' ) ] ),
		];
	}

	/**
	 * @dataProvider provideInvalidDescriptions
	 */
	public function testGivenEmptyDescription_throwsException(
		SerializationException $expectedException,
		array $description
	): void {
		try {
			( new DescriptionsDeserializer() )->deserialize( $description );
			$this->fail( 'Expected exception not thrown' );
		} catch ( SerializationException $e ) {
			$this->assertEquals( $expectedException, $e );
		}
	}

	public static function provideInvalidDescriptions(): Generator {
		yield 'invalid description - int' => [
			new InvalidDescriptionException( 'en', 1954 ),
			[ 'en' => 1954 ],
		];

		yield 'invalid description - zero length string' => [
			new EmptyDescriptionException( 'en', '' ),
			[ 'en' => '' ],
		];

		yield 'invalid description - whitespace only' => [
			new EmptyDescriptionException( '5971', '' ),
			[ 5971 => " \t " ],
		];
	}

}
