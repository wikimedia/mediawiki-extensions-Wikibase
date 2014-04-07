<?php

namespace Tests\Integration\Wikibase\InternalSerialization;

use Wikibase\InternalSerialization\DeserializerFactory;

/**
 * @covers Wikibase\InternalSerialization\DeserializerFactory
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DeserializerFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var DeserializerFactory
	 */
	private $factory;

	protected function setUp() {
		$this->factory = TestFactoryBuilder::newDeserializerFactory( $this );
	}

	public function testGetEntityDeserializerReturnsDeserializer() {
		$deserializer = $this->factory->newEntityDeserializer();
		$this->assertInstanceOf( 'Deserializers\Deserializer', $deserializer );
	}

}