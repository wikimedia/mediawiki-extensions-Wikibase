<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\Client\WikibaseClient;
use Wikibase\DataAccess\GenericServices;
use Wikibase\DataAccess\MultiRepositoryServices;
use Wikibase\DataAccess\RepositoryServiceContainerFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParserFactory;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Lib\RepositoryDefinitions;
use Wikibase\Lib\Serialization\RepositorySpecificDataValueDeserializerFactory;
use Wikibase\Lib\Interactors\TermSearchInteractorFactory;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\PropertyInfoLookup;

/**
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class MultiRepositoryServiceWiringTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return RepositoryServiceContainerFactory
	 */
	private function getRepositoryServiceContainerFactory() {
		$idParser = new PrefixMappingEntityIdParserFactory(
			new BasicEntityIdParser(), []
		);

		$client = WikibaseClient::getDefaultInstance();

		return new RepositoryServiceContainerFactory(
			$idParser,
			new EntityIdComposer( [] ),
			new RepositorySpecificDataValueDeserializerFactory( $idParser ),
			[ '' => false ],
			[ __DIR__ . '/../../src/RepositoryServiceWiring.php' ],
			new GenericServices( $client->getEntityNamespaceLookup() ),
			$client
		);
	}

	/**
	 * @return MultiRepositoryServices
	 */
	private function getMultiRepositoryServices() {
		$services = new MultiRepositoryServices(
			$this->getRepositoryServiceContainerFactory(),
			new RepositoryDefinitions( [ '' => [
				'database' => false,
				'base-uri' => '',
				'entity-types' => [],
				'prefix-mapping' => [],
			] ] )
		);

		$services->loadWiringFiles( [ __DIR__ . '/../../src/MultiRepositoryServiceWiring.php' ] );
		return $services;
	}

	public function provideServices() {
		return [
			[ 'EntityInfoBuilderFactory', EntityInfoBuilderFactory::class ],
			[ 'EntityPrefetcher', EntityPrefetcher::class ],
			[ 'EntityRevisionLookup', EntityRevisionLookup::class ],
			[ 'PropertyInfoLookup', PropertyInfoLookup::class ],
			[ 'TermBuffer', TermBuffer::class ],
			[ 'TermSearchInteractorFactory', TermSearchInteractorFactory::class ],
		];
	}

	/**
	 * @dataProvider provideServices
	 */
	public function testGetService( $serviceName, $expectedClass ) {
		$services = $this->getMultiRepositoryServices();

		$service = $services->getService( $serviceName );

		$this->assertInstanceOf( $expectedClass, $service );
	}

	public function testGetServiceNames() {
		$services = $this->getMultiRepositoryServices();

		$this->assertEquals(
			[
				'EntityInfoBuilderFactory',
				'EntityPrefetcher',
				'EntityRevisionLookup',
				'PropertyInfoLookup',
				'TermBuffer',
				'TermSearchInteractorFactory',
			],
			$services->getServiceNames()
		);
	}

}
