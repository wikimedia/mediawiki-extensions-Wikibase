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

	protected function setUp() : void {
		$snakDeserializer = new LegacySnakDeserializer( $this->createMock( Deserializer::class ) );
		$qualifiersDeserializer = new LegacySnakListDeserializer( $snakDeserializer );

		$this->deserializer = new LegacyStatementDeserializer( $snakDeserializer, $qualifiersDeserializer );
	}

	public function invalidSerializationProvider() {
		return array(
			array( null ),
			array( array() ),
			array( array( 'm' => array( 'novalue', 42 ) ) ),
			array( array( 'm' => array( 'novalue', 42 ), 'q' => array() ) ),
			array( array( 'm' => array( 'novalue', 42 ), 'q' => array( null ), 'g' => null ) ),
			array( array( 'm' => array( 'novalue', 42 ), 'q' => array(), 'g' => 'kittens' ) ),
			array( array(
				'm' => array( 'novalue', 42 ),
				'q' => array(),
				'g' => 9001,
				'refs' => array(),
				'rank' => Statement::RANK_PREFERRED
			) ),
			array( array(
				'm' => array( 'novalue', 42 ),
				'q' => array(),
				'g' => null,
				'refs' => array(),
				'rank' => 'not a rank',
			) ),
		);
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testGivenInvalidSerialization_deserializeThrowsException( $serialization ) {
		$this->setExpectedException( DeserializationException::class );
		$this->deserializer->deserialize( $serialization );
	}

	public function testGivenValidSerialization_deserializeReturnsStatement() {
		$statement = new Statement(
			new PropertyNoValueSnak( 42 )
		);

		$serialization = array(
			'm' => array( 'novalue', 42 ),
			'q' => array(),
			'g' => null,
			'rank' => Statement::RANK_NORMAL,
			'refs' => array()
		);

		$this->assertEquals(
			$statement,
			$this->deserializer->deserialize( $serialization )
		);
	}

	public function testGivenValidSerialization_deserializeReturnsStatementWithQualifiers() {
		$statement = new Statement(
			new PropertyNoValueSnak( 42 ),
			new SnakList( array(
				new PropertyNoValueSnak( 23 ),
				new PropertyNoValueSnak( 1337 ),
			) )
		);

		$statement->setGuid( 'foo bar baz' );

		$serialization = array(
			'm' => array( 'novalue', 42 ),
			'q' => array(
				array( 'novalue', 23 ),
				array( 'novalue', 1337 )
			),
			'g' => 'foo bar baz',
			'rank' => Statement::RANK_NORMAL,
			'refs' => array()
		);

		$this->assertEquals(
			$statement,
			$this->deserializer->deserialize( $serialization )
		);
	}

	public function testGivenValidSerialization_deserializeReturnsStatementWithReferences() {
		$statement = new Statement(
			new PropertyNoValueSnak( 42 ),
			new SnakList( array(
				new PropertyNoValueSnak( 23 ),
				new PropertyNoValueSnak( 1337 ),
			) ),
			new ReferenceList( array(
				new Reference(
					new SnakList( array(
						new PropertyNoValueSnak( 1 ),
						new PropertyNoValueSnak( 2 ),
					) )
				)
			) )
		);

		$statement->setGuid( 'foo bar baz' );
		$statement->setRank( Statement::RANK_PREFERRED );

		$serialization = array(
			'm' => array( 'novalue', 42 ),
			'q' => array(
				array( 'novalue', 23 ),
				array( 'novalue', 1337 )
			),
			'g' => 'foo bar baz',
			'rank' => Statement::RANK_PREFERRED,
			'refs' => array(
				array(
					array( 'novalue', 1 ),
					array( 'novalue', 2 )
				)
			)
		);

		$deserialized = $this->deserializer->deserialize( $serialization );

		$this->assertEquals(
			$statement->getHash(),
			$deserialized->getHash()
		);
	}

}
