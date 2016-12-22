<?php

namespace Wikibase\Client\Tests\Store;

use DataValues\Deserializers\DataValueDeserializer;
use HashSiteStore;
use Language;
use Wikibase\Client\Store\RepositoryServiceContainer;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParser;
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
			new EntityTypeDefinitions( [] ),
			new HashSiteStore()
		);
	}

	/**
	 * @return RepositoryServiceContainer
	 */
	private function getRepositoryServiceContainer() {
		/** @var EntityIdParser $idParser */
		$idParser = $this->getMock( EntityIdParser::class );

		$services = new RepositoryServiceContainer(
			'foowiki',
			'foo',
			new PrefixMappingEntityIdParser( [ '' => 'foo' ], $idParser ),
			new DataValueDeserializer( [] ),
			$this->getWikibaseClient()
		);

		$services->defineService( 'EntityRevisionLookup', function() {
			return $this->getMock( EntityRevisionLookup::class );
		} );

		return $services;
	}

	public function testGetService() {
		$repositoryServiceContainer = $this->getRepositoryServiceContainer();

		$serviceOne = $repositoryServiceContainer->getService( 'EntityRevisionLookup' );
		$serviceTwo = $repositoryServiceContainer->getService( 'EntityRevisionLookup' );

		$this->assertInstanceOf( EntityRevisionLookup::class, $serviceOne );
		$this->assertInstanceOf( EntityRevisionLookup::class, $serviceTwo );

		$this->assertSame( $serviceOne, $serviceTwo );
	}

	public function testGetServiceNames() {
		$repositoryServiceContainer = $this->getRepositoryServiceContainer();

		$this->assertEquals(
			[ 'EntityRevisionLookup' ],
			$repositoryServiceContainer->getServiceNames()
		);
	}

	public function testGetRepositoryName() {
		$repositoryServiceContainer = $this->getRepositoryServiceContainer();

		$this->assertEquals( 'foo', $repositoryServiceContainer->getRepositoryName() );
	}

	public function testGetDatabaseName() {
		$repositoryServiceContainer = $this->getRepositoryServiceContainer();

		$this->assertEquals( 'foowiki', $repositoryServiceContainer->getDatabaseName() );
	}

}
