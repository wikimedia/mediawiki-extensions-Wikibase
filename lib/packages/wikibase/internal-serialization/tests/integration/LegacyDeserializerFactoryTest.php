<?php

namespace Tests\Integration\Wikibase\InternalSerialization;

use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\InternalSerialization\LegacyDeserializerFactory;

/**
 * @covers Wikibase\InternalSerialization\LegacyDeserializerFactory
 *
 * @license GPL-2.0-or-later
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
			new Property( new PropertyId( 'P1' ), null, 'foo' ),
			$this->factory->newEntityDeserializer()->deserialize( array(
				'entity' => array( 'property', 1 ),
				'datatype' => 'foo',
			) )
		);
	}

	public function testSnakDeserializer() {
		$this->assertEquals(
			new PropertyNoValueSnak( 1 ),
			$this->factory->newSnakDeserializer()->deserialize( array( 'novalue', 1 ) )
		);
	}

}
