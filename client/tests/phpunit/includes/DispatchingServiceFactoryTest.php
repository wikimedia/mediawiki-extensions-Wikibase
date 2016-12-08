<?php

namespace Wikibase\Client\Tests;

use Wikibase\Client\DispatchingServiceFactory;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lib\Store\EntityRevisionLookup;

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
			[ 'EntityRevisionLookup', 'TermBuffer' ],
			$factory->getServiceNames()
		);
	}

	public function testGetServiceMap() {
		$factory = $this->getDispatchingServiceFactory();

		$serviceMap = $factory->getServiceMap( 'EntityRevisionLookup' );

		$this->assertEquals(
			[ '', 'foo' ],
			array_keys( $serviceMap )
		);
		$this->assertContainsOnlyInstancesOf( EntityRevisionLookup::class, $serviceMap );
	}

	public function provideServiceNames() {
		return [
			[ 'EntityRevisionLookup', EntityRevisionLookup::class ],
			[ 'TermBuffer', TermBuffer::class ]
		];
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
