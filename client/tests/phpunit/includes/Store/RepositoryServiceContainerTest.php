<?php

namespace Wikibase\Client\Tests\Store;

use Wikibase\Client\Store\RepositoryServiceContainer;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RepositorySpecificEntityRevisionLookupFactory;

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
		$entityRevisionLookupFactory = $this->getMockBuilder( RepositorySpecificEntityRevisionLookupFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$entityRevisionLookupFactory->expects( $this->any() )
			->method( 'getLookup' )
			->willReturn( $this->getMock( EntityRevisionLookup::class ) );

		 return new RepositoryServiceContainer(
			'foo',
			[ __DIR__ . '/../../../../includes/Store/RepositoryServiceWiring.php' ], // TODO: horrible!
			[ $entityRevisionLookupFactory ]
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
