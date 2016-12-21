<?php

namespace Wikibase\Client\Tests;

use Wikibase\Client\DispatchingServiceFactory;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikimedia\Assert\ParameterAssertionException;

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

		$factory = new DispatchingServiceFactory(
			$client,
			$client->getSettings()->getSetting( 'repoDatabase' ),
			[ 'property' => 'Property' ],
			[ 'foo' => [ 'repoDatabase' => 'foowiki', 'supportedEntityTypes' => [ 'item' ] ] ]
		);

		$factory->defineService( 'EntityRevisionLookup', function() {
			return $this->getMock( EntityRevisionLookup::class );
		} );

		return $factory;
	}

	public function testGetEntityTypeToRepoMapping() {
		$factory = $this->getDispatchingServiceFactory();

		$this->assertEquals(
			[
				'item' => 'foo',
				'property' => '',
			],
			$factory->getEntityTypeToRepoMapping()
		);
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

	public function provideInvalidConstructorArguments() {
		$validDatabase = false;
		$validNamespaces = [ 'item' => 'Item' ];
		$validForeignRepoSettings = [
			'foo' => [
				'repoDatabase' => 'foodb',
				'supportedEntityTypes' => [ 'item' ]
			]
		];

		return [
			'invalid database name (int)' => [
				100,
				$validNamespaces,
				$validForeignRepoSettings
			],
			'invalid database name (true)' => [
				true,
				$validNamespaces,
				$validForeignRepoSettings
			],
			'invalid database name (null)' => [
				null,
				$validNamespaces,
				$validForeignRepoSettings
			],
			'not entity type name' => [
				$validDatabase,
				[ 100 => 'Item' ],
				$validForeignRepoSettings
			],
			'not namespace name' => [
				$validDatabase,
				[ 'item' => 300 ],
				$validForeignRepoSettings
			],
			'repository name containing colon' => [
				$validDatabase,
				$validNamespaces,
				[ 'fo:o' => [ 'repoDatabase' => 'foodb', 'supportedEntityTypes' => [ 'item' ] ] ]
			],
		];
	}

	/**
	 * @dataProvider provideInvalidConstructorArguments
	 */
	public function testGivenInvalidConstructorArguments_constructorThrowsException(
		$repoDatabase,
		array $repoNamespaces,
		array $foreignRepositories
	) {
		$this->setExpectedException( ParameterAssertionException::class );

		new DispatchingServiceFactory(
			WikibaseClient::getDefaultInstance(),
			$repoDatabase,
			$repoNamespaces,
			$foreignRepositories
		);
	}

}
