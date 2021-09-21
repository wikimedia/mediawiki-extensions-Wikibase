<?php

namespace Tests\Integration\Wikibase\InternalSerialization;

use Serializers\Serializer;
use Wikibase\DataModel\Entity\Item;
use Wikibase\InternalSerialization\SerializerFactory;

/**
 * @covers Wikibase\InternalSerialization\SerializerFactory
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SerializerFactoryTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var SerializerFactory
	 */
	private $factory;

	protected function setUp(): void {
		$this->factory = new SerializerFactory( $this->createMock( Serializer::class ) );
	}

	public function testEntitySerializerConstruction() {
		$this->factory->newEntitySerializer()->serialize( new Item() );

		$this->assertTrue(
			true,
			'The serializer returned by newEntitySerializer can serialize an Item'
		);
	}

}
