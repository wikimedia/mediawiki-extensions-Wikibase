<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\InternalSerialization\Deserializers\ClaimDeserializer;
use Wikibase\InternalSerialization\Deserializers\SnakDeserializer;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\ClaimDeserializer
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ClaimDeserializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	public function setUp() {
		$snakDeserializer = new SnakDeserializer( $this->getMock( 'Deserializers\Deserializer' ) );

		$qualifiersDeserializer = $this->getMock( 'Deserializers\Deserializer' );

		$this->deserializer = new ClaimDeserializer( $snakDeserializer, $qualifiersDeserializer );
	}

	public function invalidSerializationProvider() {
		return array(
			array( null ),
			array( array() ),
			array( array( 'm' => array( 'novalue', 42 ) ) ),
			array( array( 'm' => array( 'novalue', 42 ), 'q' => array() ) ),
		);
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testGivenInvalidSerialization_deserializeThrowsException( $serialization ) {
		$this->setExpectedException( 'Deserializers\Exceptions\DeserializationException' );
		$this->deserializer->deserialize( $serialization );
	}

	public function testGivenValidSerialization_deserializeReturnsSimpleClaim() {
		$claim = new Claim(
			new PropertyNoValueSnak( 42 )
		);

		$serialization = array(
			'm' => array( 'novalue', 42 ),
			'q' => array(),
			'g' => null
		);

		$this->assertEquals(
			$claim,
			$this->deserializer->deserialize( $serialization )
		);
	}

	public function testGivenValidSerialization_deserializeReturnsComplexClaim() {
		$claim = new Claim(
			new PropertyNoValueSnak( 42 ),
			new SnakList() // TODO
		);

		$claim->setGuid( 'foo bar baz' );

		$serialization = array(
			'm' => array( 'novalue', 42 ),
			'q' => array(),
			'g' => 'foo bar baz'
		);

		$this->assertEquals(
			$claim,
			$this->deserializer->deserialize( $serialization )
		);
	}

}