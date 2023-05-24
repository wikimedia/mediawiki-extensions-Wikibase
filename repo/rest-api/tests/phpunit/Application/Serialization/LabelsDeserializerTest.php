<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Serialization;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Serialization\EmptyLabelException;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidFieldException;
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

	public function testGivenEmptyLabel_throwsException(): void {
		try {
			( new LabelsDeserializer() )->deserialize( [ 'en' => '' ] );
			$this->fail( 'this should not be reached' );
		} catch ( EmptyLabelException $e ) {
			$this->assertSame( 'en', $e->getField() );
		}
	}

	public function testGivenInvalidLabelType_throwsException(): void {
		try {
			( new LabelsDeserializer() )->deserialize( [ 'en' => 123 ] );
			$this->fail( 'this should not be reached' );
		} catch ( InvalidFieldException $e ) {
			$this->assertSame( 'en', $e->getField() );
			$this->assertSame( 123, $e->getValue() );
		}
	}

}
