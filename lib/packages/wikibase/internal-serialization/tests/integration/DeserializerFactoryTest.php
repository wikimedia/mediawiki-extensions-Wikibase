<?php

namespace Tests\Integration\Wikibase\InternalSerialization;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\InternalSerialization\DeserializerFactory;

/**
 * @covers Wikibase\InternalSerialization\DeserializerFactory
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class DeserializerFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var DeserializerFactory
	 */
	private $factory;

	protected function setUp() {
		$this->factory = TestFactoryBuilder::newDeserializerFactory( $this );
	}

	public function testNewEntityDeserializerReturnsDeserializer() {
		$deserializer = $this->factory->newEntityDeserializer();
		$this->assertInstanceOf( 'Deserializers\Deserializer', $deserializer );
	}

	public function testNewStatementDeserializerReturnsDeserializer() {
		$deserializer = $this->factory->newStatementDeserializer();
		$this->assertInstanceOf( 'Deserializers\Deserializer', $deserializer );
	}

	public function testConstructWithCustomEntityDeserializer() {
		$factory = new DeserializerFactory(
			$this->getMock( 'Deserializers\Deserializer' ),
			new BasicEntityIdParser(),
			$this->getMock( 'Deserializers\DispatchableDeserializer' )
		);

		$deserializer = $factory->newEntityDeserializer();
		$this->assertInstanceOf( 'Deserializers\Deserializer', $deserializer );
	}

}
