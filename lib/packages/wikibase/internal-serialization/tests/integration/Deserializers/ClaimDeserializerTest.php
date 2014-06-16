<?php

namespace Tests\Integration\Wikibase\InternalSerialization\Deserializers;

use DataValues\NumberValue;
use DataValues\StringValue;
use Deserializers\Deserializer;
use Serializers\Serializer;
use Tests\Integration\Wikibase\InternalSerialization\TestFactoryBuilder;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;

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

	/**
	 * @var Serializer
	 */
	private $currentSerializer;

	public function setUp() {
		$this->deserializer = TestFactoryBuilder::newDeserializerFactoryWithDataValueSupport()->newClaimDeserializer();
		$this->currentSerializer = TestFactoryBuilder::newSerializerFactory()->newClaimSerializer();
	}

	private function assertDeserializesToClaim( $serialization ) {
		$claim = $this->deserializer->deserialize( $serialization );

		$this->assertInstanceOf( 'Wikibase\DataModel\Claim\Claim', $claim );
	}

	public function testGivenGeneratedSerialization_claimIsDeserialized() {
		$this->assertDeserializesToClaim( $this->currentSerializer->serialize( $this->newTestClaim() ) );
	}

	private function newTestClaim() {
		$claim = new Claim( new PropertySomeValueSnak( 42, new StringValue( 'kittens' ) ) );

		$claim->setQualifiers( new SnakList( array(
			new PropertyNoValueSnak( 1337 ),
			new PropertyValueSnak( 23, new NumberValue( 42 ) )
		) ) );

		$claim->setGuid( 'some guid be here' );

		return $claim;
	}

	/**
	 * @dataProvider claimsInLegacyFormatProvider
	 */
	public function testCanDeseriaizeLegacyFormat( $serialization ) {
		$this->assertDeserializesToClaim( json_decode( $serialization, true ) );
	}

	public function claimsInLegacyFormatProvider() {
		return array(
			array( '{"m":["somevalue",42],"q":[],"g":"some guid be here"}' ),
			array( '{"m":["somevalue",42],"q":[["novalue",1337],["value",23,"number",42]],"g":"some guid be here"}' ),
		);
	}

}