<?php

namespace Wikibase\Client\Tests\Store;

use Wikibase\Client\Store\RepositoryServiceContainer;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\Store\EntityRevisionLookup;

/**
 * @covers Wikibase\Client\Store\RepositoryServiceContainer
 *
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0+
 */
class RepositoryServiceContainerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return RepositoryServiceContainer
	 */
	private function getRepositoryServiceContainer() {
		$client = WikibaseClient::getDefaultInstance();

		$settings = $client->getSettings();
		$settings->setSetting(
			'foreignRepositories',
			[ 'foo' => [ 'repoDatabase' => 'foowiki', 'prefixMapping' => [ 'bar' => 'xyz' ] ] ]
		);

		$client->overrideSettings( $settings );

		 return new RepositoryServiceContainer(
			'foo',
			$client,
			[ __DIR__ . '/../../../../includes/Store/RepositoryServiceWiring.php' ] // TODO: horrible!
		);
	}

	public function provideServices() {
		return [
			[ 'EntityRevisionLookup', EntityRevisionLookup::class ],
		];
	}

	/**
	 * @dataProvider provideServices
	 */
	public function testGetService( $serviceName, $expectedClass ) {
		$repositoryServiceContainer = $this->getRepositoryServiceContainer();

		$serviceOne = $repositoryServiceContainer->getService( $serviceName );
		$serviceTwo = $repositoryServiceContainer->getService( $serviceName );

		$this->assertInstanceOf( $expectedClass, $serviceOne );
		$this->assertInstanceOf( $expectedClass, $serviceTwo );

		$this->assertSame( $serviceOne, $serviceTwo );
	}

	public function testGetServiceNames() {
		$repositoryServiceContainer = $this->getRepositoryServiceContainer();

		$this->assertEquals( [ 'EntityRevisionLookup' ], $repositoryServiceContainer->getServiceNames() );
	}

	public function testGetRepositoryName() {
		$repositoryServiceContainer = $this->getRepositoryServiceContainer();

		$this->assertEquals( 'foo', $repositoryServiceContainer->getRepositoryName() );
	}

}
