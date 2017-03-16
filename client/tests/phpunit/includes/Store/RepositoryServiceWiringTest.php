<?php

namespace Wikibase\Client\Tests\Store;

use DataValues\Deserializers\DataValueDeserializer;
use LogicException;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParser;
use Wikibase\Edrsf\RepositoryServiceContainer;
use Wikibase\Edrsf\TermIndex;
use Wikibase\Edrsf\TermSearchInteractorFactory;
use Wikibase\Edrsf\WikiPageEntityMetaDataAccessor;

/**
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0+
 */
class RepositoryServiceWiringTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return RepositoryServiceContainer
	 */
	private function getRepositoryServiceContainer() {
		$container = new RepositoryServiceContainer(
			false,
			'',
			new PrefixMappingEntityIdParser( [ '' => '' ], $this->getMock( EntityIdParser::class ) ),
			new DataValueDeserializer( [] ),
			WikibaseClient::getDefaultInstance()
		);

		$container->loadWiringFiles( [ __DIR__ . '/../../../../includes/Store/RepositoryServiceWiring.php' ] );

		return $container;
	}

	public function provideServices() {
		return [
			[ 'EntityInfoBuilderFactory', \Wikibase\Edrsf\EntityInfoBuilderFactory::class ],
			[ 'EntityPrefetcher', EntityPrefetcher::class ],
			[ 'EntityRevisionLookup', \Wikibase\Edrsf\EntityRevisionLookup::class ],
			[ 'PrefetchingTermLookup', \Wikibase\Edrsf\PrefetchingTermLookup::class ],
			[ 'PropertyInfoLookup', \Wikibase\Edrsf\PropertyInfoLookup::class ],
			[ 'TermIndex', TermIndex::class ],
			[ 'TermSearchInteractorFactory', TermSearchInteractorFactory::class ],
			[ 'WikiPageEntityMetaDataAccessor', WikiPageEntityMetaDataAccessor::class ],
		];
	}

	/**
	 * @dataProvider provideServices
	 */
	public function testGetService( $serviceName, $expectedClass ) {
		$container = $this->getRepositoryServiceContainer();

		$service = $container->getService( $serviceName );

		$this->assertInstanceOf( $expectedClass, $service );
	}

	public function testGetServiceNames() {
		$container = $this->getRepositoryServiceContainer();

		$this->assertEquals(
			[
				'EntityInfoBuilderFactory',
				'EntityPrefetcher',
				'EntityRevisionLookup',
				'PrefetchingTermLookup',
				'PropertyInfoLookup',
				'TermIndex',
				'TermSearchInteractorFactory',
				'WikiPageEntityMetaDataAccessor'
			],
			$container->getServiceNames()
		);
	}

	public function testGetEntityPrefetcherThrowsAnExceptionIfNoPrefetcherService() {
		$container = $this->getRepositoryServiceContainer();

		// Make 'WikiPageEntityMetaDataAccessor' service not an implementation
		// of EntityPrefetcher interface
		$container->redefineService( 'WikiPageEntityMetaDataAccessor', function() {
			return $this->getMock( WikiPageEntityMetaDataAccessor::class );
		} );

		$this->setExpectedException( LogicException::class );

		$container->getService( 'EntityPrefetcher' );
	}

}
