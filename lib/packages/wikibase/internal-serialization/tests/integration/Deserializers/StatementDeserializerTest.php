<?php

namespace Tests\Integration\Wikibase\InternalSerialization\Deserializers;

use DataValues\NumberValue;
use Deserializers\Deserializer;
use Serializers\Serializer;
use Tests\Integration\Wikibase\InternalSerialization\TestFactoryBuilder;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\StatementDeserializer
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StatementDeserializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	/**
	 * @var Serializer
	 */
	private $currentSerializer;

	protected function setUp() {
		$this->deserializer = TestFactoryBuilder::newDeserializerFactoryWithDataValueSupport()
			->newStatementDeserializer();
		$this->currentSerializer = TestFactoryBuilder::newSerializerFactory()->newStatementSerializer();
	}

	private function assertDeserializesToStatement( $serialization ) {
		$statement = $this->deserializer->deserialize( $serialization );

		$this->assertInstanceOf( 'Wikibase\DataModel\Statement\Statement', $statement );
	}

	public function testGivenGeneratedSerialization_statementIsDeserialized() {
		$this->assertDeserializesToStatement( $this->currentSerializer->serialize( $this->newTestStatement() ) );
	}

	private function newTestStatement() {
		$statement = new Statement( new PropertySomeValueSnak( 42 ) );

		$statement->setQualifiers( new SnakList( array(
			new PropertyNoValueSnak( 1337 ),
			new PropertyValueSnak( 23, new NumberValue( 42 ) )
		) ) );

		$statement->setGuid( 'some guid be here' );

		return $statement;
	}

	/**
	 * @dataProvider statementsInLegacyFormatProvider
	 */
	public function testCanDeseriaizeLegacyFormat( $serialization ) {
		$this->assertDeserializesToStatement( json_decode( $serialization, true ) );
	}

	public function statementsInLegacyFormatProvider() {
		return array(
			array( '{"m":["somevalue",42],"q":[],"g":"some guid be here","rank":1,"refs":[]}' ),
			array( '{"m":["somevalue",42],"q":[["novalue",1337],["value",23,"number",42]],'
				. '"g":"some guid be here","rank":1,"refs":[]}' ),
		);
	}

}
