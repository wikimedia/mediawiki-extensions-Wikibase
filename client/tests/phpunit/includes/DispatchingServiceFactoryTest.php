<?php

namespace Wikibase\Client\Tests;

use Wikibase\Client\DispatchingServiceFactory;
use Wikibase\Client\WikibaseClient;
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

		$factory = new DispatchingServiceFactory( $client );

		$factory->defineService( 'EntityRevisionLookup', function() {
			return $this->getMock( EntityRevisionLookup::class );
		} );

		return $factory;
	}

	public function testGetServiceNames() {
		$factory = $this->getDispatchingServiceFactory();

		$this->assertEquals(
			[ 'EntityRevisionLookup' ],
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

	public function testGetService() {
		$factory = $this->getDispatchingServiceFactory();

		$serviceOne = $factory->getService( 'EntityRevisionLookup' );
		$serviceTwo = $factory->getService( 'EntityRevisionLookup' );

		$this->assertInstanceOf( EntityRevisionLookup::class, $serviceOne );
		$this->assertInstanceOf( EntityRevisionLookup::class, $serviceTwo );
		$this->assertSame( $serviceOne, $serviceTwo );
	}

}
