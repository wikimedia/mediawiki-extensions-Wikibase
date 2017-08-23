<?php

namespace Wikibase\DataAccess\Tests;

use Serializers\Serializer;
use Wikibase\DataAccess\GenericServices;
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

	public function testGetEntitySerializer() {
		$services = $this->newGenericServices();
		$this->assertInstanceOf( Serializer::class, $services->getEntitySerializer() );
	}
	public function testGetEntitySerializerReusesTheInstanceForMultipleCalls() {
		$services = $this->newGenericServices();

		$serializerOne = $services->getEntitySerializer();
		$serializerTwo = $services->getEntitySerializer();

		$this->assertSame( $serializerOne, $serializerTwo );
	}

	public function testGetCompactEntitySerializer() {
		$services = $this->newGenericServices();
		$this->assertInstanceOf( Serializer::class, $services->getCompactEntitySerializer() );
	}

	public function testGetCompactEntitySerializerReusesTheInstanceForMultipleCalls() {
		$services = $this->newGenericServices();

		$serializerOne = $services->getCompactEntitySerializer();
		$serializerTwo = $services->getCompactEntitySerializer();

		$this->assertSame( $serializerOne, $serializerTwo );
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
		return new GenericServices( new EntityNamespaceLookup( [] ), new EntityTypeDefinitions( [] ) );
	}

}
