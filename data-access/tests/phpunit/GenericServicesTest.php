<?php

namespace Wikibase\DataAccess\Tests;

use Serializers\Serializer;
use Wikibase\DataAccess\GenericServices;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Store\EntityNamespaceLookup;

/**
 * @covers Wikibase\DataAccess\GenericServices
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class GenericServicesTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider provideSerializerFactoryOptions
	 */
	public function testGetEntitySerializer( $options ) {
		$services = $this->newGenericServices();
		$this->assertInstanceOf( Serializer::class, $services->getEntitySerializer( $options ) );
	}

	public function provideSerializerFactoryOptions() {
		return [
			[ SerializerFactory::OPTION_DEFAULT ],
			[ SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH ],
			[ SerializerFactory::OPTION_SERIALIZE_QUALIFIER_SNAKS_WITHOUT_HASH ],
			[ SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH ],
			[ SerializerFactory::OPTION_OBJECTS_FOR_MAPS ],
			[
				SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH +
				SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH +
				SerializerFactory::OPTION_SERIALIZE_QUALIFIER_SNAKS_WITHOUT_HASH
			]
		];
	}

	public function testGetEntitySerializerReturnsSingleInstanceForSameOptions() {
		$services = $this->newGenericServices();

		$defaultSerializerOne = $services->getEntitySerializer( SerializerFactory::OPTION_DEFAULT );
		$defaultSerializerTwo = $services->getEntitySerializer( SerializerFactory::OPTION_DEFAULT );

		$otherSerializer = $services->getEntitySerializer( SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH );

		$this->assertSame( $defaultSerializerOne, $defaultSerializerTwo );
		$this->assertNotSame( $defaultSerializerOne, $otherSerializer );
	}

	private function newGenericServices() {
		return new GenericServices( new EntityNamespaceLookup( [] ), new EntityTypeDefinitions( [] ) );
	}

}
