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
	 * @return DispatchingServiceFactory
	 */
	private function getDispatchingServiceFactory() {
		$client = WikibaseClient::getDefaultInstance();
		$settings = $client->getSettings();
		$settings->setSetting( 'foreignRepositories', [ 'foo' => [ 'repoDatabase' => 'foowiki' ] ] );

		return new DispatchingServiceFactory(
			$client,
			[ __DIR__ . '/../../../includes/DispatchingServiceWiring.php' ] // TODO: fix me
		);
	}

	public function testGetServiceNames() {
		$factory = $this->getDispatchingServiceFactory();

		$this->assertEquals(
			[ 'EntityRevisionLookup' ],
			$factory->getServiceNames()
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

	/**
	 * @dataProvider provideServiceNames
	 */
	public function testGetService( $serviceName, $expectedClass ) {
		$factory = $this->getDispatchingServiceFactory();

		$serviceOne = $factory->getService( $serviceName );
		$serviceTwo = $factory->getService( $serviceName );

		$this->assertInstanceOf( $expectedClass, $serviceOne );
		$this->assertInstanceOf( $expectedClass, $serviceTwo );
		$this->assertSame( $serviceOne, $serviceTwo );
	}

}
