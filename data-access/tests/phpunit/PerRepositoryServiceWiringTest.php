<?php

namespace Wikibase\DataAccess\Tests;

use DataValues\Deserializers\DataValueDeserializer;
use LogicException;
use MediaWiki\Storage\NameTableStore;
use Wikibase\DataAccess\GenericServices;
use Wikibase\DataAccess\PerRepositoryServiceContainer;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParser;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Store\MatchingTermsLookup;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataAccessor;

/**
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PerRepositoryServiceWiringTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @return NameTableStore|object
	 */
	private function getNameTableStoreProphecy() {
		return $this->prophesize( NameTableStore::class )->reveal();
	}

	/**
	 * @return PerRepositoryServiceContainer
	 */
	private function getRepositoryServiceContainer() {
		$container = new PerRepositoryServiceContainer(
			false,
			'',
			new PrefixMappingEntityIdParser( [ '' => '' ], new ItemIdParser() ),
			new EntityIdComposer( [] ),
			new DataValueDeserializer( [] ),
			new GenericServices( new EntityTypeDefinitions( [] ), [] ),
			DataAccessSettingsFactory::repositoryPrefixBasedFederation(),
			[],
			[],
			$this->getNameTableStoreProphecy()
		);

		$container->loadWiringFiles( [ __DIR__ . '/../../src/PerRepositoryServiceWiring.php' ] );

		return $container;
	}

	public function provideServices() {
		return [
			[ 'MatchingTermsLookup', MatchingTermsLookup::class ]
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
				'EntityInfoBuilder',
				'EntityPrefetcher',
				'EntityRevisionLookup',
				'PrefetchingTermLookup',
				'PropertyInfoLookup',
				'TermBuffer',
				'TermIndex',
				'MatchingTermsLookup',
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
		$container->redefineService( 'WikiPageEntityMetaDataAccessor', function () {
			return $this->createMock( WikiPageEntityMetaDataAccessor::class );
		} );

		$this->expectException( LogicException::class );

		$container->getService( 'EntityPrefetcher' );
	}

}
