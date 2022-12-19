<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\InternalSerialization\Deserializers\LegacySnakDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacySnakListDeserializer;
use Wikibase\InternalSerialization\Deserializers\LegacyStatementDeserializer;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\LegacyStatementDeserializer
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class LegacyStatementDeserializerTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	protected function setUp(): void {
		$snakDeserializer = new LegacySnakDeserializer( $this->createMock( Deserializer::class ) );
		$qualifiersDeserializer = new LegacySnakListDeserializer( $snakDeserializer );

		$this->deserializer = new LegacyStatementDeserializer( $snakDeserializer, $qualifiersDeserializer );
	}

	public function invalidSerializationProvider() {
		return [
			[ null ],
			[ [] ],
			[ [ 'm' => [ 'novalue', 42 ] ] ],
			[ [ 'm' => [ 'novalue', 42 ], 'q' => [] ] ],
			[ [ 'm' => [ 'novalue', 42 ], 'q' => [ null ], 'g' => null ] ],
			[ [ 'm' => [ 'novalue', 42 ], 'q' => [], 'g' => 'kittens' ] ],
			[ [
				'm' => [ 'novalue', 42 ],
				'q' => [],
				'g' => 9001,
				'refs' => [],
				'rank' => Statement::RANK_PREFERRED,
			] ],
			[ [
				'm' => [ 'novalue', 42 ],
				'q' => [],
				'g' => null,
				'refs' => [],
				'rank' => 'not a rank',
			] ],
		];
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testGivenInvalidSerialization_deserializeThrowsException( $serialization ) {
		$this->expectException( DeserializationException::class );
		$this->deserializer->deserialize( $serialization );
	}

	public function testGivenValidSerialization_deserializeReturnsStatement() {
		$statement = new Statement(
			new PropertyNoValueSnak( 42 )
		);

		$serialization = [
			'm' => [ 'novalue', 42 ],
			'q' => [],
			'g' => null,
			'rank' => Statement::RANK_NORMAL,
			'refs' => [],
		];

		$this->assertEquals(
			$statement,
			$this->deserializer->deserialize( $serialization )
		);
	}

	public function testGivenValidSerialization_deserializeReturnsStatementWithQualifiers() {
		$statement = new Statement(
			new PropertyNoValueSnak( 42 ),
			new SnakList( [
				new PropertyNoValueSnak( 23 ),
				new PropertyNoValueSnak( 1337 ),
			] )
		);

		$statement->setGuid( 'foo bar baz' );

		$serialization = [
			'm' => [ 'novalue', 42 ],
			'q' => [
				[ 'novalue', 23 ],
				[ 'novalue', 1337 ],
			],
			'g' => 'foo bar baz',
			'rank' => Statement::RANK_NORMAL,
			'refs' => [],
		];

		$this->assertEquals(
			$statement,
			$this->deserializer->deserialize( $serialization )
		);
	}

	public function testGivenValidSerialization_deserializeReturnsStatementWithReferences() {
		$statement = new Statement(
			new PropertyNoValueSnak( 42 ),
			new SnakList( [
				new PropertyNoValueSnak( 23 ),
				new PropertyNoValueSnak( 1337 ),
			] ),
			new ReferenceList( [
				new Reference(
					new SnakList( [
						new PropertyNoValueSnak( 1 ),
						new PropertyNoValueSnak( 2 ),
					] )
				),
			] )
		);

		$statement->setGuid( 'foo bar baz' );
		$statement->setRank( Statement::RANK_PREFERRED );

		$serialization = [
			'm' => [ 'novalue', 42 ],
			'q' => [
				[ 'novalue', 23 ],
				[ 'novalue', 1337 ],
			],
			'g' => 'foo bar baz',
			'rank' => Statement::RANK_PREFERRED,
			'refs' => [
				[
					[ 'novalue', 1 ],
					[ 'novalue', 2 ],
				],
			],
		];

		$deserialized = $this->deserializer->deserialize( $serialization );

		$this->assertEquals(
			$statement->getHash(),
			$deserialized->getHash()
		);
	}

}
