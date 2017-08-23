<?php

namespace Wikibase\DataAccess\Tests;

use Serializers\Serializer;
use Wikibase\DataAccess\GenericServices;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\StringNormalizer;

/**
 * @covers Wikibase\DataAccess\GenericServices
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class GenericServicesTest extends \PHPUnit_Framework_TestCase {

	public function testGetEntityNamespaceLookup() {
		$services = $this->newGenericServices();
		$this->assertInstanceOf( EntityNamespaceLookup::class, $services->getEntityNamespaceLookup() );
	}

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

	public function testGetLanguageFallbackChainFactory() {
		$services = $this->newGenericServices();

		$this->assertInstanceOf( LanguageFallbackChainFactory::class, $services->getLanguageFallbackChainFactory() );
	}

	public function testGetLanguageFallbackChainFactoryReusesTheInstanceForMultipleCalls() {
		$services = $this->newGenericServices();

		$serviceOne = $services->getLanguageFallbackChainFactory();
		$serviceTwo = $services->getLanguageFallbackChainFactory();

		$this->assertSame( $serviceOne, $serviceTwo );
	}

	public function testGetStringNormalizer() {
		$services = $this->newGenericServices();

		$this->assertInstanceOf( StringNormalizer::class, $services->getStringNormalizer() );
	}

	public function testGetStringNormalizerReusesTheInstanceForMultipleCalls() {
		$services = $this->newGenericServices();

		$serviceOne = $services->getStringNormalizer();
		$serviceTwo = $services->getStringNormalizer();

		$this->assertSame( $serviceOne, $serviceTwo );
	}

	private function newGenericServices() {
		return new GenericServices( new EntityTypeDefinitions( [] ), [] );
	}

}
