<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Serialization;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\EmptyDescriptionException;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidFieldException;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer
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
	 * @dataProvider emptyDescriptionsProvider
	 */
	public function testGivenEmptyDescription_throwsException( string $emptyDescription ): void {
		try {
			( new DescriptionsDeserializer() )->deserialize( [ 'en' => $emptyDescription ] );
			$this->fail( 'this should not be reached' );
		} catch ( EmptyDescriptionException $e ) {
			$this->assertSame( 'en', $e->getField() );
		}
	}

	public static function emptyDescriptionsProvider(): Generator {
		yield 'empty description' => [ '' ];
		yield 'empty description with spaces' => [ '   ' ];
		yield 'empty description with tabs' => [ "\t\t" ];
		yield 'empty description with spaces and tabs' => [ " \t \t " ];
	}

	public function testGivenInvalidDescriptionType_throwsException(): void {
		try {
			( new DescriptionsDeserializer() )->deserialize( [ 'en' => 123 ] );
			$this->fail( 'this should not be reached' );
		} catch ( InvalidFieldException $e ) {
			$this->assertSame( 'en', $e->getField() );
			$this->assertSame( 123, $e->getValue() );
		}
	}

}
