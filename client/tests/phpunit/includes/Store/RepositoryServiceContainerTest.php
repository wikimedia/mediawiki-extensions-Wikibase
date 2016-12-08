<?php

namespace Wikibase\Client\Tests\Store;

use HashSiteStore;
use Language;
use Wikibase\Client\Store\RepositoryServiceContainer;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\SettingsArray;

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
	 * @return WikibaseClient
	 */
	private function getWikibaseClient() {
		$settings = WikibaseClient::getDefaultInstance()->getSettings()->getArrayCopy();
		$settings['foreignRepositories'] = [
			'foo' => [ 'repoDatabase' => 'foowiki', 'prefixMapping' => [ 'bar' => 'xyz' ] ]
		];

		return new WikibaseClient(
			new SettingsArray( $settings ),
			Language::factory( 'en' ),
			new DataTypeDefinitions( [] ),
			$this->getEntityTypeDefinitions(),
			new HashSiteStore()
		);
	}

	/**
	 * Provides a dummy entity type definitions to satisfy asserts of RepositorySpecificEntityRevisionLookupFactory
	 *
	 * @return EntityTypeDefinitions
	 */
	private function getEntityTypeDefinitions() {
		return new EntityTypeDefinitions( [
			'item' => [
				'deserializer-factory-callback' => function( DeserializerFactory $deserializerFactory ) {
					return $deserializerFactory->newItemDeserializer();
				}
			],
		] );
	}

	/**
	 * @return RepositoryServiceContainer
	 */
	private function getRepositoryServiceContainer() {
		 return new RepositoryServiceContainer(
			'foo',
			$this->getWikibaseClient(),
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
