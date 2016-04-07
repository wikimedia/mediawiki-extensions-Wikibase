<?php

namespace Tests\Integration\Wikibase\InternalSerialization;

use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\InternalSerialization\LegacyDeserializerFactory;

/**
 * @covers Wikibase\InternalSerialization\LegacyDeserializerFactory
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacyDeserializerFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var LegacyDeserializerFactory
	 */
	private $factory;

	protected function setUp() {
		$this->factory = TestFactoryBuilder::newLegacyDeserializerFactory( $this );
	}

	public function testEntityDeserializer() {
		$this->assertEquals(
			Property::newFromType( 'foo' ),
			$this->factory->newEntityDeserializer()->deserialize( array( 'datatype' => 'foo' ) )
		);
	}

	public function testSnakDeserializer() {
		$this->assertEquals(
			new PropertyNoValueSnak( 1 ),
			$this->factory->newSnakDeserializer()->deserialize( array( 'novalue', 1 ) )
		);
	}

}
