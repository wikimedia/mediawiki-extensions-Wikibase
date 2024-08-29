<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Serialization;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\EmptyLabelException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidLabelException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\SerializationException;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LabelsDeserializerTest extends TestCase {

	/**
	 * @dataProvider labelsProvider
	 */
	public function testDeserialize( array $serialization, TermList $expectedLabels ): void {
		$this->assertEquals(
			$expectedLabels,
			( new LabelsDeserializer() )->deserialize( $serialization )
		);
	}

	public static function labelsProvider(): Generator {
		yield 'no labels' => [
			[],
			new TermList(),
		];

		yield 'multiple labels' => [
			[
				'en' => 'potato',
				'de' => 'Kartoffel',
			],
			new TermList( [
				new Term( 'en', 'potato' ),
				new Term( 'de', 'Kartoffel' ),
			] ),
		];

		yield 'labels with leading/trailing whitespace' => [
			[
				'en' => '  space',
				'de' => ' Leerzeichen  ',
			],
			new TermList( [
				new Term( 'en', 'space' ),
				new Term( 'de', 'Leerzeichen' ),
			] ),
		];
	}

	/**
	 * @dataProvider provideInvalidLabels
	 */
	public function testGivenInvalidSerialization_throwsException(
		SerializationException $expectedException,
		array $labels
	): void {
		try {
			( new LabelsDeserializer() )->deserialize( $labels );
			$this->fail( 'Expected exception not thrown' );
		} catch ( SerializationException $e ) {
			$this->assertEquals( $expectedException, $e );
		}
	}

	public static function provideInvalidLabels(): Generator {
		yield 'invalid label - int' => [
			new InvalidLabelException( 'en', 1954 ),
			[ 'en' => 1954 ],
		];

		yield 'invalid label - zero length string' => [
			new EmptyLabelException( 'en', '' ),
			[ 'en' => '' ],
		];

		yield 'invalid label - whitespace only' => [
			new EmptyLabelException( '5971', '' ),
			[ 5971 => " \t " ],
		];
	}

}
