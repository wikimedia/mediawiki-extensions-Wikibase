<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\Client\WikibaseClient;
use Wikibase\DataAccess\DispatchingServiceFactory;
use Wikibase\DataAccess\RepositoryServiceContainerFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParserFactory;
use Wikibase\DataModel\Services\Term\TermBuffer;
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
class DispatchingServiceWiringTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return RepositoryServiceContainerFactory
	 */
	private function getRepositoryServiceContainerFactory() {
		$idParser = new PrefixMappingEntityIdParserFactory(
			new BasicEntityIdParser(), []
		);

		return new RepositoryServiceContainerFactory(
			$idParser,
			new RepositorySpecificDataValueDeserializerFactory( $idParser ),
			[ '' => false ],
			[ __DIR__ . '/../../src/RepositoryServiceWiring.php' ],
			WikibaseClient::getDefaultInstance()
		);
	}

	/**
	 * @return DispatchingServiceFactory
	 */
	private function getDispatchingServiceFactory() {
		$factory = new DispatchingServiceFactory(
			$this->getRepositoryServiceContainerFactory(),
			new RepositoryDefinitions( [ '' => [
				'database' => false,
				'base-uri' => '',
				'entity-types' => [],
				'prefix-mapping' => [],
			] ] )
		);

		$factory->loadWiringFiles( [ __DIR__ . '/../../src/DispatchingServiceWiring.php' ] );
		return $factory;
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
		$factory = $this->getDispatchingServiceFactory();

		$service = $factory->getService( $serviceName );

		$this->assertInstanceOf( $expectedClass, $service );
	}

	public function testGetServiceNames() {
		$factory = $this->getDispatchingServiceFactory();

		$this->assertEquals(
			[
				'EntityInfoBuilderFactory',
				'EntityPrefetcher',
				'EntityRevisionLookup',
				'PropertyInfoLookup',
				'TermBuffer',
				'TermSearchInteractorFactory',
			],
			$factory->getServiceNames()
		);
	}

}
