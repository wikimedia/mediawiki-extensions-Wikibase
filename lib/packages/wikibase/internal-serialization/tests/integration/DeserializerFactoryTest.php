<?php

namespace Tests\Integration\Wikibase\InternalSerialization;

use Deserializers\Deserializer;
use Deserializers\DispatchableDeserializer;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\InternalSerialization\DeserializerFactory;

/**
 * @covers Wikibase\InternalSerialization\DeserializerFactory
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class DeserializerFactoryTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var DeserializerFactory
	 */
	private $factory;

	protected function setUp(): void {
		$this->factory = TestFactoryBuilder::newDeserializerFactory( $this );
	}

	public function testNewEntityDeserializerReturnsDeserializer() {
		$deserializer = $this->factory->newEntityDeserializer();
		$this->assertInstanceOf( Deserializer::class, $deserializer );
	}

	public function testNewStatementDeserializerReturnsDeserializer() {
		$deserializer = $this->factory->newStatementDeserializer();
		$this->assertInstanceOf( Deserializer::class, $deserializer );
	}

	public function testConstructWithCustomEntityDeserializer() {
		$factory = new DeserializerFactory(
			$this->createMock( Deserializer::class ),
			new BasicEntityIdParser(),
			$this->createMock( DispatchableDeserializer::class )
		);

		$deserializer = $factory->newEntityDeserializer();
		$this->assertInstanceOf( Deserializer::class, $deserializer );
	}

}
