<?php

namespace Wikibase\Client\Tests;

use Wikibase\Client\DispatchingServiceFactory;
use Wikibase\Client\Store\RepositoryServiceContainerFactory;
use Wikibase\Client\WikibaseClient;
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
 * @group WikibaseClient
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
			[ __DIR__ . '/../../../includes/Store/RepositoryServiceWiring.php' ],
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
				'entity-types' => [],
				'prefix-mapping' => [],
			] ] )
		);

		return $factory;
	}

	public function provideServices() {
		return [
			[ 'getEntityInfoBuilderFactory', EntityInfoBuilderFactory::class ],
			[ 'getEntityPrefetcher', EntityPrefetcher::class ],
			[ 'getEntityRevisionLookup', EntityRevisionLookup::class ],
			[ 'getPropertyInfoLookup', PropertyInfoLookup::class ],
			[ 'getTermBuffer', TermBuffer::class ],
			[ 'getTermSearchInteractorFactory', TermSearchInteractorFactory::class ],
		];
	}

	/**
	 * @dataProvider provideServices
	 */
	public function testGetService( $method, $expectedClass ) {
		$factory = $this->getDispatchingServiceFactory();

		$service = $factory->$method();

		$this->assertInstanceOf( $expectedClass, $service );
	}

}
