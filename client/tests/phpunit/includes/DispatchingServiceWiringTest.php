<?php

namespace Wikibase\Client\Tests;

use Wikibase\Client\DispatchingServiceFactory;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lib\Store\EntityRevisionLookup;

/**
 * @covers client/includes/DispatchingServiceWiring.php
 *
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0+
 */
class DispatchingServiceWiringTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return DispatchingServiceFactory
	 */
	private function getDispatchingServiceFactory() {
		$factory = new DispatchingServiceFactory( WikibaseClient::getDefaultInstance() );
		$factory->loadWiringFiles( [ __DIR__ . '/../../../includes/DispatchingServiceWiring.php' ] );
		return $factory;
	}

	public function provideServices() {
		return [
			[ 'EntityRevisionLookup', EntityRevisionLookup::class ],
			[ 'TermBuffer', TermBuffer::class ],
		];
	}

	/**
	 * @dataProvider provideServices
	 */
	public function testGetService( $serviceName, $expectedClass ) {
		$factory = $this->getDispatchingServiceFactory();

		$service = $factory->getService( $serviceName );

		$this->assertInstanceOf( $expectedClass, $service );
	}

	public function testGetServiceNames() {
		$factory = $this->getDispatchingServiceFactory();

		$this->assertEquals( [ 'EntityRevisionLookup', 'TermBuffer' ], $factory->getServiceNames() );
	}

}
