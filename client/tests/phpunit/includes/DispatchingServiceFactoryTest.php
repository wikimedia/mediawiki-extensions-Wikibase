<?php

namespace Wikibase\Client\Tests;

use Wikibase\Client\DispatchingServiceFactory;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\SettingsArray;

/**
 * @covers Wikibase\Client\DispatchingServiceFactory
 *
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0+
 */
class DispatchingServiceFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return callable[]
	 */
	private function getDeserializerFactoryCallbacks() {
		return [
			'item' => function( DeserializerFactory $deserializerFactory ) {
				return $deserializerFactory->newItemDeserializer();
			},
			'property' => function( DeserializerFactory $deserializerFactory ) {
				return $deserializerFactory->newPropertyDeserializer();
			}
		];
	}

	/**
	 * @return DispatchingServiceFactory
	 */
	private function getDispatchingServiceFactory() {
		$settings = WikibaseClient::getDefaultInstance()->getSettings();
		$settings['foreignRepositories'] = [
			'foo' => [ 'repoDatabase' => 'foowiki' ]
		];

		return new DispatchingServiceFactory(
			$this->getMock( EntityIdParser::class ),
			new EntityNamespaceLookup( [] ),
			$this->getDeserializerFactoryCallbacks(),
			$settings
		);
	}

	public function provideServiceNames() {
		return [
			[ 'EntityRevisionLookup', EntityRevisionLookup::class ],
		];
	}

	/**
	 * @dataProvider provideServiceNames
	 */
	public function testGetServiceMap( $serviceName, $expectedServiceClass ) {
		$factory = $this->getDispatchingServiceFactory();

		$serviceMap = $factory->getServiceMap( $serviceName );

		$this->assertEquals(
			[ '', 'foo' ],
			array_keys( $serviceMap )
		);
		$this->assertContainsOnlyInstancesOf( $expectedServiceClass, $serviceMap );
	}

}
